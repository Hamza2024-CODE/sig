<?php
/**
 * ═══════════════════════════════════════════════════════════════════
 *  LIVE MONITOR — يُحدّث كل 10 ثوانٍ ويعرض حالة جميع الجداول
 *  Run: php storage/workers/monitor_live.php
 * ═══════════════════════════════════════════════════════════════════
 */
require __DIR__ . '/../../vendor/autoload.php';
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
use Illuminate\Support\Facades\DB;

$db = DB::getPdo();

$targetTables = [
    'candidat', 'apprenant', 'apprenant_section_semstre',
    'apprenant_fin', 'section', 'apprenant_section_semstre_module',
    'actions_titre_souscategorie', 'cal_tachedetail',
];

$iteration = 0;
while (true) {
    $iteration++;
    system('cls'); // Windows clear screen

    echo "╔════════════════════════════════════════════════════════════════════╗\n";
    printf("║  LIVE MONITOR — %s  (iter #%d)          ║\n", date('H:i:s'), $iteration);
    echo "╚════════════════════════════════════════════════════════════════════╝\n\n";

    printf("  %-40s %-10s %12s %12s %8s %s\n",
        'الجدول', 'الحالة', 'فعلي MySQL', 'HFSQL الكل', 'نسبة%', 'آخر تحديث');
    echo "  " . str_repeat('─', 105) . "\n";

    $allDone = true;
    foreach ($targetTables as $table) {
        try {
            $real = (int)$db->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
        } catch (\Throwable $e) {
            $real = 0;
        }

        $job = $db->prepare("
            SELECT status, total_rows, synced_rows, last_synced_id, updated_at
            FROM sync_jobs WHERE table_name = ? ORDER BY id DESC LIMIT 1
        ");
        $job->execute([$table]);
        $j = $job->fetch(PDO::FETCH_ASSOC);

        $status   = $j ? $j['status'] : '—';
        $hfsql    = $j ? (int)$j['total_rows']  : 0;
        $pct      = ($hfsql > 0) ? round($real / $hfsql * 100, 1) : 0;
        $updated  = $j ? substr($j['updated_at'] ?? '—', 11, 8) : '—';

        $icon = match($status) {
            'done'    => '✅',
            'running' => '🔄',
            'paused'  => '⏸️ ',
            'pending' => '⏳',
            'failed'  => '❌',
            default   => '❓',
        };

        if (!in_array($status, ['done'])) $allDone = false;

        // Progress bar
        $barLen = 20;
        $filled = (int)round($pct / 100 * $barLen);
        $filled = max(0, min($barLen, $filled));
        $bar    = str_repeat('█', $filled) . str_repeat('░', $barLen - $filled);

        printf("  %-40s %s %-8s %12s %12s %6s%%  [%s]  %s\n",
            $table, $icon, $status,
            number_format($real), $hfsql > 0 ? number_format($hfsql) : '—',
            $pct, $bar, $updated
        );
    }

    echo "\n  " . str_repeat('─', 105) . "\n";

    // Overall totals
    try {
        $totals = $db->query("
            SELECT status, COUNT(*) as cnt FROM sync_jobs
            WHERE table_name IN ('" . implode("','", $targetTables) . "')
            AND id IN (SELECT MAX(id) FROM sync_jobs GROUP BY table_name)
            GROUP BY status
        ")->fetchAll(PDO::FETCH_ASSOC);
        $summary = [];
        foreach ($totals as $t) { $summary[$t['status']] = $t['cnt']; }
        printf("  ✅ مكتمل: %d | 🔄 يعمل: %d | ⏸️  متوقف: %d | ⏳ في انتظار: %d | ❌ فاشل: %d\n",
            $summary['done']    ?? 0,
            $summary['running'] ?? 0,
            $summary['paused']  ?? 0,
            $summary['pending'] ?? 0,
            $summary['failed']  ?? 0
        );
    } catch (\Throwable $e) {}

    if ($allDone) {
        echo "\n  🎉 جميع الجداول اكتملت! انتهى التحقق.\n";
        break;
    }

    echo "\n  [تحديث كل 15 ثانية — Ctrl+C للإيقاف]\n";
    sleep(15);
}
