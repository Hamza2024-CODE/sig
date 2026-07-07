<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Domains\Sync\Services\SyncService;
use App\Domains\Sync\Services\SyncLogger;

/**
 * SyncTableJob — مهمة مزامنة جدول واحد من HFSQL إلى MySQL
 *
 * استبدل popen/shell_exec القديمة ببنية Laravel Queue الكاملة:
 *   - مراقبة: تظهر المهام في جدول `jobs` وقابلة للتتبع.
 *   - موثوقية: retry تلقائي عند الفشل (3 محاولات).
 *   - أمان: لا تنفيذ shell خارجية، لا خطر Command Injection.
 *   - قابلية توسع: يمكن توزيعها على workers متعددة لاحقاً.
 *
 * الاستخدام:
 *   SyncTableJob::dispatch($jobId, $table, $syncType, $filterId, $resumeId);
 *
 * تشغيل الـ worker:
 *   php artisan queue:work --queue=sync --timeout=3600 --tries=3
 */
class SyncTableJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * عدد محاولات إعادة التشغيل عند الفشل.
     * قيمة 3 تعني 3 محاولات إجمالية قبل تحويل المهمة إلى failed_jobs.
     */
    public int $tries = 3;

    /**
     * مهلة التنفيذ القصوى بالثانية.
     * المزامنة قد تستغرق وقتاً طويلاً للجداول الكبيرة — نضع ساعة كحد أقصى.
     */
    public int $timeout = 3600;

    /**
     * الوقت المنتظر بالثانية قبل إعادة المحاولة عند الفشل.
     * يتضاعف تلقائياً: 60s → 300s → 900s
     */
    public int $backoff = 60;

    public function __construct(
        private string  $jobId,
        private string  $table,
        private ?string $syncType  = null,
        private ?int    $filterId  = null,
        private ?string $resumeId  = null,
    ) {
        // توجيه المهمة لطابور 'sync' المخصص
        $this->onQueue('sync');
    }

    /**
     * تنفيذ مهمة المزامنة.
     * يستدعي SyncService::syncTableWithResume() الذي يحتوي على كامل منطق المزامنة.
     */
    public function handle(): void
    {
        Log::info("[SyncTableJob] Starting job {$this->jobId} for table: {$this->table}");

        try {
            // تحديث حالة المهمة إلى 'running' في sync_queue
            DB::table('sync_queue')
                ->where('job_id', $this->jobId)
                ->update(['status' => 'running', 'started_at' => now()]);

            $syncService = new SyncService();

            // Load progress from database if resuming and not explicitly provided in the constructor
            $resumeId = $this->resumeId;
            if ($resumeId === null) {
                try {
                    $dbJob = DB::table('sync_jobs')->where('job_id', $this->jobId)->first();
                    if ($dbJob && $dbJob->last_synced_id !== null && trim((string)$dbJob->last_synced_id) !== '') {
                        $resumeId = (string)$dbJob->last_synced_id;
                        Log::info("[SyncTableJob] Resuming job {$this->jobId} from DB saved ID: $resumeId");
                    }
                } catch (\Throwable $ex) {
                    Log::warning("[SyncTableJob] Failed to load saved progress: " . $ex->getMessage());
                }
            }

            $result = $syncService->syncTableWithResume(
                jobId:    $this->jobId,
                table:    $this->table,
                syncType: $this->syncType,
                filterId: $this->filterId,
                resumeId: $resumeId,
            );

            if ($result['success'] ?? false) {
                // تحديث sync_queue إلى done عند نجاح المهمة
                DB::table('sync_queue')
                    ->where('job_id', $this->jobId)
                    ->update(['status' => 'done', 'finished_at' => now()]);

                Log::info("[SyncTableJob] Completed job {$this->jobId}: {$result['message']}");
            } else {
                throw new \RuntimeException($result['message'] ?? 'Unknown sync error');
            }

        } catch (\Throwable $e) {
            Log::error("[SyncTableJob] Failed job {$this->jobId}: " . $e->getMessage());

            // عند الفشل: تحديث sync_queue إلى 'failed'
            DB::table('sync_queue')
                ->where('job_id', $this->jobId)
                ->update(['status' => 'failed', 'finished_at' => now()]);

            // إعادة رمي الاستثناء حتى تقوم Laravel بإعادة المحاولة (retry)
            throw $e;
        }
    }

    /**
     * عند استنفاد جميع محاولات إعادة التشغيل.
     * تسجيل النهائي وتحديث sync_jobs.
     */
    public function failed(\Throwable $exception): void
    {
        Log::critical(
            "[SyncTableJob] Job {$this->jobId} permanently failed for table '{$this->table}': "
            . $exception->getMessage()
        );

        $logger = new SyncLogger($this->jobId);
        $logger->error("فشل نهائي: " . $exception->getMessage());

        DB::table('sync_jobs')
            ->where('job_id', $this->jobId)
            ->update([
                'status'        => 'failed',
                'error_message' => $exception->getMessage(),
                'finished_at'   => now(),
                'updated_at'    => now(),
            ]);

        DB::table('sync_queue')
            ->where('job_id', $this->jobId)
            ->update(['status' => 'failed', 'finished_at' => now()]);
    }
}
