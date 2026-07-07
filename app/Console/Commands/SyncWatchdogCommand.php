<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Domains\Sync\Services\SyncLogger;
use App\Jobs\SyncTableJob;

class SyncWatchdogCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:watchdog {--daemon : تشغيل السكربت كخلفية مستمرة}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'يراقب طابور المزامنة ويعيد إحياء المهام المتوقفة تلقائياً، ويعيد محاولة مزامنة السجلات التالفة';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $this->info('🛡️ بدء مراقب المزامنة الذاتي (Sync Watchdog)...');

        $isDaemon = $this->option('daemon');

        do {
            $this->checkAndRevive();
            $this->retryFailedRows();

            if ($isDaemon) {
                sleep(30); // فحص كل 30 ثانية في وضع daemon
            }
        } while ($isDaemon);

        return Command::SUCCESS;
    }

    /**
     * Check for stuck running jobs and revive them.
     */
    private function checkAndRevive(): void
    {
        try {
            $db = new \App\Core\LaravelDbAdapter();

            // Find running jobs that haven't updated in the last 2 minutes
            $stuckJobs = $db->query("
                SELECT job_id, table_name, last_synced_id 
                FROM sync_jobs 
                WHERE status = 'running' 
                  AND updated_at < NOW() - INTERVAL 2 MINUTE
            ")->fetchAll(\PDO::FETCH_ASSOC);

            if (!empty($stuckJobs)) {
                $this->warn('⚠️ تم اكتشاف ' . count($stuckJobs) . ' مهمة مزامنة متوقفة!');

                foreach ($stuckJobs as $job) {
                    $jobId  = $job['job_id'];
                    $table  = $job['table_name'];
                    $lastId = $job['last_synced_id'] ?? null;

                    $this->info("🔄 إعادة إحياء مهمة الجدول [$table] (المعرف: $jobId) من النقطة: " . ($lastId ?? 'البداية'));

                    // Reset status to pending so queue worker picks it up and resumes from last_synced_id
                    $db->prepare("UPDATE sync_queue SET status = 'pending' WHERE job_id = ?")->execute([$jobId]);
                    $db->prepare("UPDATE sync_jobs SET status = 'pending' WHERE job_id = ?")->execute([$jobId]);

                    // Log warning to sync job logs
                    try {
                        $logger = new SyncLogger($jobId);
                        $logger->warning('تم إعادة إحياء المهمة تلقائياً بواسطة المراقب (Sync Watchdog) بعد توقفها.');
                    } catch (\Throwable $e) {}

                    // إعادة إرسال المهمة إلى Laravel Queue (يستبدل popen تماماً)
                    SyncTableJob::dispatch(
                        jobId:    $jobId,
                        table:    $table,
                        syncType: null,
                        filterId: null,
                        resumeId: $lastId,
                    );
                }
            }
        } catch (\Throwable $e) {
            $this->error('Watchdog checkAndRevive Error: ' . $e->getMessage());
        }
    }

    /**
     * Retry syncing rows that were isolated into sync_errors.
     */
    private function retryFailedRows(): void
    {
        try {
            $db = new \App\Core\LaravelDbAdapter();

            // Fetch pending retries
            $stmt = $db->query("
                SELECT id, job_id, table_name, record_id, payload, retry_count 
                FROM sync_errors 
                WHERE status = 'pending_retry' 
                  AND retry_count < 3 
                LIMIT 50
            ");
            $errors = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            if (empty($errors)) {
                return;
            }

            $this->info('🔄 جاري إعادة محاولة مزامنة ' . count($errors) . ' من السجلات الموقوفة...');

            foreach ($errors as $err) {
                $errId      = $err['id'];
                $jobId      = $err['job_id'];
                $table      = $err['table_name'];
                $recordId   = $err['record_id'];
                $retryCount = (int)$err['retry_count'] + 1;

                $row = json_decode($err['payload'], true);
                if (empty($row)) {
                    $db->prepare("UPDATE sync_errors SET status = 'manual_intervention', retry_count = ?, updated_at = NOW() WHERE id = ?")->execute([$retryCount, $errId]);
                    continue;
                }

                try {
                    $this->mysqlUpsertSingleRow($table, $row);

                    // Successfully synced single row! Update status to resolved.
                    $db->prepare("UPDATE sync_errors SET status = 'resolved', retry_count = ?, updated_at = NOW() WHERE id = ?")->execute([$retryCount, $errId]);
                    $this->info("   ✅ تم حل مشكلة السجل [$recordId] في الجدول [$table]");
                    
                    if ($jobId) {
                        try {
                            $logger = new SyncLogger($jobId);
                            $logger->info("تم معالجة وإدخال السجل الموقوف [$recordId] بنجاح بعد إعادة المحاولة.");
                        } catch (\Throwable $e) {}
                    }
                } catch (\Throwable $rowException) {
                    $newErrMsg = $rowException->getMessage();
                    $newStatus = ($retryCount >= 3) ? 'manual_intervention' : 'pending_retry';

                    $upd = $db->prepare("
                        UPDATE sync_errors 
                        SET status = ?, retry_count = ?, error_message = ?, updated_at = NOW() 
                        WHERE id = ?
                    ");
                    $upd->execute([$newStatus, $retryCount, $newErrMsg, $errId]);
                    $this->warn("   ❌ فشلت محاولة إعادة المزامنة ($retryCount/3) للسجل [$recordId] في الجدول [$table]: $newErrMsg");
                }
            }
        } catch (\Throwable $e) {
            $this->error('Watchdog retryFailedRows Error: ' . $e->getMessage());
        }
    }

    /**
     * Upsert a single isolated row into MySQL.
     */
    private function mysqlUpsertSingleRow(string $table, array $row): void
    {
        $db = new \App\Core\LaravelDbAdapter();
        $columns = array_keys($row);
        $numCols = count($columns);

        $colsString   = implode(', ', array_map(fn($c) => "`$c`", $columns));
        $placeholders = implode(', ', array_fill(0, $numCols, '?'));
        $updateParts  = array_map(fn($c) => "`$c` = VALUES(`$c`)", $columns);
        $updateString = implode(', ', $updateParts);

        $sql = "INSERT INTO `$table` ($colsString) VALUES ($placeholders) ON DUPLICATE KEY UPDATE $updateString";

        $stmt   = $db->prepare($sql);
        $values = [];
        foreach ($columns as $col) {
            $values[] = $row[$col] ?? null;
        }

        $db->exec('SET FOREIGN_KEY_CHECKS = 0;');
        try {
            $stmt->execute($values);
        } finally {
            $db->exec('SET FOREIGN_KEY_CHECKS = 1;');
        }
    }

    /**
     * Trigger background worker popen start.
     * 
     * @deprecated استُبدلت بـ SyncTableJob::dispatch() — هذه الدالة لم تعد مستخدمة.
     * الـ Watchdog يُرسل المهام الآن مباشرة عبر Laravel Queue بدون popen/shell_exec.
     * لتشغيل الـ worker بشكل دائم:
     *   php artisan queue:work --queue=sync --timeout=3600 --tries=3
     */
    // private function startQueueWorker() — REMOVED
}
