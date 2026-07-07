<?php
/**
 * ═══════════════════════════════════════════════════════════════════
 *  MASTER RESUME SCRIPT — يستأنف ويشغّل جميع الجداول الناقصة
 *  يعمل في الخلفية ويُسجّل التقدم في sync_jobs
 * ═══════════════════════════════════════════════════════════════════
 */
require __DIR__ . '/../../vendor/autoload.php';
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
use Illuminate\Support\Facades\DB;

$db = DB::getPdo();

// ─── الجداول المطلوب مزامنتها مع آخر نقطة توقف ──────────────────────
// [table, sync_type, filter_id, resume_id]
// resume_id = last_synced_id من sync_jobs
$tasksToRun = [
    // ── active range-based resume tasks ──
    ['apprenant',                 'global', null, '4046159'],   // Range — resumes from ID 4046159 up to 5544990
    ['apprenant_section_semstre', 'global', null, '1081727'],   // Range — resumes from ID 1081727 up to 8867117
    ['section',                   'global', null, '159208'],    // Range — resumes from ID 159208 up to 203041
    ['cal_tachedetail',           'global', null, null],        // Range — fresh start from ID 1 up to 10257
];

$phpBin     = 'C:\\xampp\\php\\php.exe';
$workerPath = __DIR__ . '/background_worker.php';

echo "═══════════════════════════════════════════════════════════════\n";
echo "  MASTER SYNC — بدء جلب البيانات لجميع الجداول الناقصة\n";
echo "  الوقت: " . date('Y-m-d H:i:s') . "\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

function generateUuid(): string {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

$launched = [];

foreach ($tasksToRun as [$table, $syncType, $filterId, $resumeId]) {
    $jobId     = generateUuid();
    $filterArg = $filterId ?? 0;
    $resumeArg = $resumeId ?? '';

    // Insert into sync_queue
    $db->prepare("INSERT INTO sync_queue (job_id, table_name, sync_type, filter_id, status) VALUES (?, ?, ?, ?, 'pending')")
       ->execute([$jobId, $table, $syncType, $filterId]);

    // Insert into sync_jobs
    $db->prepare("INSERT IGNORE INTO sync_jobs (job_id, table_name, status) VALUES (?, ?, 'pending')")
       ->execute([$jobId, $table]);

    // Fire background worker
    $cmd = sprintf(
        'start /B "" "%s" "%s" "%s" "%s" "%s" "%s" "%s"',
        $phpBin, $workerPath, $jobId, $table, $syncType, $filterArg, $resumeArg
    );

    pclose(popen($cmd, 'r'));
    sleep(1); // space launches 1 second apart to avoid DB locks

    $launched[] = ['job_id' => $jobId, 'table' => $table, 'resume' => $resumeArg ?: '(fresh start)'];
    printf("  ✅ launched: %-45s job_id=%s  resume=%s\n", $table, $jobId, $resumeArg ?: '—');
}

echo "\n═══════════════════════════════════════════════════════════════\n";
echo "  " . count($launched) . " مهمة أُطلقت في الخلفية.\n";
echo "  راقب التقدم عبر: php storage/workers/monitor_live.php\n";
echo "═══════════════════════════════════════════════════════════════\n";
