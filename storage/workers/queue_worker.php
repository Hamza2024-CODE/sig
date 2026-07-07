<?php
/**
 * ═══════════════════════════════════════════════════════════════════
 *  SEQUENTIAL QUEUE WORKER — معالج طابور المزامنة المتسلسل
 *  يعالج المهام واحدة تلو الأخرى مع معالجة انقطاع الاتصال وإعادة المحاولة تلقائياً.
 * ═══════════════════════════════════════════════════════════════════
 */

define('ROOT', dirname(__DIR__, 2));
require ROOT . '/vendor/autoload.php';
$app = require_once ROOT . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Core\Database;
use App\Domains\Sync\Services\SyncService;
use App\Domains\Sync\Services\SyncLogger;

// Remove timeout limit & raise memory limit
set_time_limit(0);
ini_set('memory_limit', '2G');
date_default_timezone_set('Africa/Algiers');

// Ensure only one instance of queue_worker.php runs concurrently
$lockFile = ROOT . '/storage/framework/queue_worker.lock';
$lockDir = dirname($lockFile);
if (!is_dir($lockDir)) {
    mkdir($lockDir, 0755, true);
}

$lock = fopen($lockFile, 'c+');
if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) {
    echo "[INFO] Another queue worker is already running. Exiting.\n";
    exit(0);
}

// Write PID to lock file
ftruncate($lock, 0);
fwrite($lock, getmypid());
fflush($lock);

echo "=========================================================\n";
echo " Queue Worker started. PID: " . getmypid() . "\n";
echo " Time: " . date('Y-m-d H:i:s') . "\n";
echo "=========================================================\n\n";

$db = Database::getInstance()->getConnection();

while (true) {
    try {
        // Find next pending job
        // We select the oldest pending task
        $stmt = $db->query("SELECT * FROM sync_queue WHERE status = 'pending' ORDER BY id ASC LIMIT 1");
        $job = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$job) {
            // No pending jobs. Sleep and check again.
            sleep(3);
            continue;
        }

        $jobId    = $job['job_id'];
        $table    = $job['table_name'];
        $syncType = $job['sync_type'];
        $filterId = $job['filter_id'];

        echo "[QUEUE] Found pending job: $jobId for table: $table\n";

        // 1) IMMEDIATELY update status in database so the dashboard shows 'running'
        $db->prepare("UPDATE sync_queue SET status = 'running', started_at = NOW() WHERE job_id = ?")->execute([$jobId]);
        $db->prepare("UPDATE sync_jobs SET status = 'running', started_at = NOW() WHERE job_id = ?")->execute([$jobId]);

        // Get initial resume ID from sync_jobs
        $resumeStmt = $db->prepare("SELECT last_synced_id FROM sync_jobs WHERE job_id = ?");
        $resumeStmt->execute([$jobId]);
        $initialResumeId = $resumeStmt->fetchColumn() ?: null;

        $maxRetries = 3;
        $retryCount = 0;
        $success    = false;
        $resumeId   = $initialResumeId;
        $status     = 'done';
        $errorMessage = null;

        // 2) Run sync with reconnection/retry logic
        while ($retryCount < $maxRetries && !$success) {
            try {
                // Ensure connections are active before starting/retrying
                $db = Database::getInstance()->getConnection();

                $service = new SyncService();
                $result  = $service->syncTableWithResume(
                    $jobId,
                    $table,
                    ($syncType === 'global' ? null : $syncType),
                    $filterId,
                    $resumeId
                );

                if ($result['success']) {
                    if (isset($result['paused']) && $result['paused']) {
                        $status = 'paused';
                    } else {
                        $status = 'done';
                    }
                    $success = true;
                } else {
                    throw new Exception($result['message'] ?? 'Unknown error');
                }

            } catch (\Throwable $e) {
                $retryCount++;
                $errorMessage = $e->getMessage();
                echo "[ERROR] Job $jobId failed (Attempt $retryCount/$maxRetries): $errorMessage\n";

                if ($retryCount >= $maxRetries) {
                    $status = 'failed';
                    break;
                }

                // Check if it is a database connection issue
                $isConnectionError = false;
                $connKeywords = ['gone away', 'lost connection', 'link failure', 'odbc', 'connection', 'Communication link', 'driver'];
                foreach ($connKeywords as $keyword) {
                    if (stripos($errorMessage, $keyword) !== false) {
                        $isConnectionError = true;
                        break;
                    }
                }

                // If connection error, wait 5 seconds and reconnect
                if ($isConnectionError) {
                    echo "[RETRY] Connection issue detected. Waiting 5 seconds before re-establishing connections...\n";
                } else {
                    echo "[RETRY] Error detected. Waiting 5 seconds before retrying...\n";
                }
                
                sleep(5);

                // Disconnect and purge PDO to force fresh connection
                try {
                    \Illuminate\Support\Facades\DB::disconnect();
                    \Illuminate\Support\Facades\DB::purge();
                } catch (\Throwable $disE) {}

                // Retrieve latest last_synced_id from sync_jobs so we resume exactly where it was committed
                try {
                    $dbFresh = Database::getInstance()->getConnection();
                    $chkStmt = $dbFresh->prepare("SELECT last_synced_id FROM sync_jobs WHERE job_id = ?");
                    $chkStmt->execute([$jobId]);
                    $latestResumeId = $chkStmt->fetchColumn();
                    if ($latestResumeId !== false && $latestResumeId !== null && $latestResumeId !== '') {
                        $resumeId = $latestResumeId;
                        echo "[RETRY] Resuming table $table from ID/offset: $resumeId\n";
                    }
                } catch (\Throwable $dbE) {
                    echo "[ERROR] Could not fetch fresh resume ID: " . $dbE->getMessage() . "\n";
                }
            }
        }

        // 3) Update job queue status upon completion/failure
        $finishedAt = ($status === 'paused') ? null : date('Y-m-d H:i:s');
        $db = Database::getInstance()->getConnection();
        $db->prepare("UPDATE sync_queue SET status = ?, finished_at = ? WHERE job_id = ?")->execute([$status, $finishedAt, $jobId]);
        
        if ($status === 'failed' && $errorMessage) {
            $db->prepare("UPDATE sync_jobs SET status = 'failed', error_message = ?, finished_at = NOW() WHERE job_id = ?")->execute([$errorMessage, $jobId]);
        }

        echo "[QUEUE] Finished job $jobId for table: $table. Status: $status\n\n";

    } catch (\Throwable $e) {
        echo "[FATAL] Queue Worker encountered an exception: " . $e->getMessage() . "\n";
        sleep(5);
    }
}
