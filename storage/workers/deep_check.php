<?php
/**
 * DEEP CHECK — تحقق تفصيلي من candidat و apprenant لمعرفة لماذا أنهوا بدون إضافة سجلات
 */
require __DIR__ . '/../../vendor/autoload.php';
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
use Illuminate\Support\Facades\DB;
$db = DB::getPdo();

$tables = ['candidat', 'apprenant', 'apprenant_section_semstre', 'apprenant_fin', 'section', 'cal_tachedetail'];

echo "═══════════════════════════════════════════════════════════\n";
echo "  DEEP CHECK — " . date('H:i:s') . "\n";
echo "═══════════════════════════════════════════════════════════\n\n";

foreach ($tables as $table) {
    $real = (int)$db->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
    
    // Get ALL recent jobs (last 5 per table)
    $jobs = $db->prepare("
        SELECT id, job_id, status, total_rows, synced_rows, last_synced_id, started_at, finished_at
        FROM sync_jobs WHERE table_name = ? ORDER BY id DESC LIMIT 5
    ");
    $jobs->execute([$table]);
    $allJobs = $jobs->fetchAll(PDO::FETCH_ASSOC);

    echo "┌── $table (COUNT(*)=" . number_format($real) . ") ──\n";
    foreach ($allJobs as $j) {
        printf("│  id=%-4d status=%-8s synced=%-12s total=%-12s last_id=%-12s started=%s\n",
            $j['id'], $j['status'],
            number_format((int)$j['synced_rows']),
            number_format((int)$j['total_rows']),
            $j['last_synced_id'] ?? '—',
            substr($j['started_at'] ?? '—', 11, 8)
        );
    }
    echo "└─────────────────────────────────────────\n\n";
}

// Also check last sync_log entries for candidat to understand why it stopped
echo "=== Last 10 sync_logs for candidat ===\n";
try {
    $jobId = $db->query("SELECT job_id FROM sync_jobs WHERE table_name='candidat' ORDER BY id DESC LIMIT 1")->fetchColumn();
    if ($jobId) {
        $logs = $db->prepare("SELECT level, message, created_at FROM sync_logs WHERE job_id = ? ORDER BY id DESC LIMIT 10");
        $logs->execute([$jobId]);
        $logRows = $logs->fetchAll(PDO::FETCH_ASSOC);
        foreach (array_reverse($logRows) as $l) {
            printf("  [%s] %s — %s\n", $l['level'], substr($l['created_at'],11,8), $l['message']);
        }
    } else {
        echo "  No jobs found for candidat.\n";
    }
} catch (\Throwable $e) {
    echo "  ERROR: " . $e->getMessage() . "\n";
}

// Also check last 10 sync_log entries for apprenant
echo "\n=== Last 10 sync_logs for apprenant ===\n";
try {
    $jobId = $db->query("SELECT job_id FROM sync_jobs WHERE table_name='apprenant' ORDER BY id DESC LIMIT 1")->fetchColumn();
    if ($jobId) {
        $logs = $db->prepare("SELECT level, message, created_at FROM sync_logs WHERE job_id = ? ORDER BY id DESC LIMIT 10");
        $logs->execute([$jobId]);
        $logRows = $logs->fetchAll(PDO::FETCH_ASSOC);
        foreach (array_reverse($logRows) as $l) {
            printf("  [%s] %s — %s\n", $l['level'], substr($l['created_at'],11,8), $l['message']);
        }
    } else {
        echo "  No jobs found for apprenant.\n";
    }
} catch (\Throwable $e) {
    echo "  ERROR: " . $e->getMessage() . "\n";
}

