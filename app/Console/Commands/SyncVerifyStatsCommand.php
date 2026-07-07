<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Domains\Sync\Services\SyncService;

class SyncVerifyStatsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:verify-stats';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'يجري فحصاً ومقارنة للمقارنة الكلية بين HFSQL و MySQL ويصحح جدول sync_reports تلقائياً';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $this->info('══════════════════════════════════════════════');
        $this->info('  🛡️ تدقيق الإحصائيات الدوري — sync:verify-stats');
        $this->info('══════════════════════════════════════════════');

        try {
            $syncService = new SyncService();
            $allTables = SyncService::getAllMysqlTables();
            $excludedTables = ['sync_queue', 'sync_jobs', 'sync_logs', 'migrations', 'sessions', 'sync_reports', 'sync_status'];
            $allTables = array_filter($allTables, fn($t) => !in_array($t, $excludedTables));
            $allTables = array_values($allTables);

            $this->info('جاري التحقق من ' . count($allTables) . ' جدولاً...');

            $bar = $this->output->createProgressBar(count($allTables));
            $bar->start();

            foreach ($allTables as $table) {
                // getTableCounts with $forceRefresh = true queries absolute counts and updates sync_reports/sync_status
                $syncService->getTableCounts($table, true);
                $bar->advance();
            }

            $bar->finish();
            $this->info('');
            $this->info('✅ تم التحقق وتحديث إحصائيات جدول مقارنة البيانات بنجاح.');
            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("\n❌ حدث خطأ أثناء التحقق من الإحصائيات: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
