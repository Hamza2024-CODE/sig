<?php
/**
 * ============================================================
 * Background Worker — PHP CLI Script
 * ============================================================
 * يُشغَّل في الخلفية عبر proc_open() من SyncController.
 * يقرأ job_id من argv, يُنفذ المزامنة بدون قيود timeout.
 *
 * الاستخدام:
 *   php background_worker.php <job_id> <table> <sync_type> <filter_id> [resume_id]
 * ============================================================
 */

// Bootstrap — worker lives at storage/workers/background_worker.php
// So we go up 2 levels to get to the project root: workers → storage → sig (root)
define('ROOT', dirname(__DIR__, 2));
require ROOT . '/vendor/autoload.php';

// Bootstrap Laravel application
$app = require_once ROOT . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Core\Database;
use App\Domains\Sync\Services\SyncService;
use App\Domains\Sync\Services\SyncLogger;

// Remove all time/memory limits — we are in CLI
set_time_limit(0);
ini_set('memory_limit', '2G');
date_default_timezone_set('Africa/Algiers');

// ---- Parse Arguments ----
$jobId    = $argv[1] ?? null;
$table    = $argv[2] ?? null;
$syncType = $argv[3] ?? null; // 'wilaya' | 'etab' | 'global'
$filterId = isset($argv[4]) && is_numeric($argv[4]) ? (int)$argv[4] : null;
$resumeId = $argv[5] ?? null;
if (trim((string)$resumeId) === '') {
    $resumeId = null;
}


if (!$jobId || !$table) {
    echo "[ERROR] Usage: php background_worker.php <job_id> <table> <sync_type> <filter_id> [resume_id]\n";
    exit(1);
}

$logger = new SyncLogger($jobId);
$logger->info("Worker started. PID: " . getmypid());
$logger->info("Table: $table | SyncType: $syncType | FilterID: $filterId | ResumeID: $resumeId");

try {
    // Update queue status
    $db = Database::getInstance()->getConnection();
    $db->prepare(
        "UPDATE sync_queue SET status = 'running', started_at = NOW() WHERE job_id = ?"
    )->execute([$jobId]);

    // Run sync
    $service = new SyncService();
    $result  = $service->syncTableWithResume(
        $jobId,
        $table,
        ($syncType === 'global' ? null : $syncType),
        $filterId,
        $resumeId
    );

    // Update queue status based on result
    $status = 'done';
    if (!$result['success']) {
        $status = 'failed';
    } elseif (isset($result['paused']) && $result['paused']) {
        $status = 'paused';
    }

    $finishedAt = ($status === 'paused') ? null : date('Y-m-d H:i:s');

    $db->prepare(
        "UPDATE sync_queue SET status = ?, finished_at = ? WHERE job_id = ?"
    )->execute([$status, $finishedAt, $jobId]);

    if ($result['success']) {
        $logger->info("Worker finished successfully. Synced: " . ($result['synced'] ?? 0) . " records.");
    } else {
        $logger->error("Worker finished with error: " . ($result['message'] ?? 'Unknown error'));
    }

} catch (\Throwable $e) {
    $logger->error("Fatal worker error: " . $e->getMessage());

    try {
        $db = Database::getInstance()->getConnection();
        $db->prepare(
            "UPDATE sync_queue SET status = 'failed', finished_at = NOW() WHERE job_id = ?"
        )->execute([$jobId]);
        $db->prepare(
            "UPDATE sync_jobs SET status = 'failed', error_message = ?, finished_at = NOW() WHERE job_id = ?"
        )->execute([$e->getMessage(), $jobId]);
    } catch (\Throwable $dbE) {
        // ignore
    }

    terminateProcess(1);
}

terminateProcess(0);

function terminateProcess(int $exitCode) {
    if (PHP_OS_FAMILY === 'Windows') {
        exec("taskkill /F /PID " . getmypid());
    } else {
        if (function_exists('posix_kill')) {
            posix_kill(getmypid(), 9);
        } else {
            exec("kill -9 " . getmypid());
        }
    }
    exit($exitCode);
}
