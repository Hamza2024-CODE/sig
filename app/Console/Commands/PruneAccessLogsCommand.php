<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Core\AuditLogger;

class PruneAccessLogsCommand extends Command
{
    protected $signature = 'logs:prune-access';
    protected $description = 'تطهير وحذف سجلات الوصول والاتصال القديمة الأكثر من 180 يوماً تلقائياً';

    public function handle()
    {
        $this->info('بدء عملية تطهير سجلات الوصول (accesuser)...');

        $cutoffDate = now()->subDays(180)->format('Y-m-d');
        
        try {
            $deletedCount = DB::table('accesuser')
                ->where('Date', '<', $cutoffDate)
                ->delete();

            $this->info("تم بنجاح تطهير {$deletedCount} سجل وصول قديم (تاريخ أقدم من {$cutoffDate}) من جدول accesuser.");
            AuditLogger::logWarning("[LOG_ROTATION] Pruned {$deletedCount} access log records older than 180 days from 'accesuser' table.");
            return 0;
        } catch (\Exception $e) {
            $this->error("حدث خطأ أثناء تطهير السجلات: " . $e->getMessage());
            AuditLogger::logError("[LOG_ROTATION] Error pruning access logs: " . $e->getMessage());
            return 1;
        }
    }
}
