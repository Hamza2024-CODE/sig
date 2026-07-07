<?php
/**
 * ═══════════════════════════════════════════════════════
 *  SYNC STATUS SNAPSHOT — snapshot of all pending/paused jobs
 * ═══════════════════════════════════════════════════════
 */
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
use Illuminate\Support\Facades\DB;
$db = DB::getPdo();

$rows = $db->query("
    SELECT j1.table_name, j1.status, j1.total_rows, j1.synced_rows, j1.last_synced_id,
           j1.job_id, q.sync_type, q.filter_id, j1.finished_at, j1.updated_at
    FROM sync_jobs j1
    LEFT JOIN sync_queue q ON j1.job_id = q.job_id
    WHERE j1.id = (SELECT MAX(id) FROM sync_jobs j2 WHERE j2.table_name = j1.table_name)
    ORDER BY j1.table_name
")->fetchAll(PDO::FETCH_ASSOC);

foreach ($rows as $r) {
    $real = 0;
    try { $real = (int)$db->query("SELECT COUNT(*) FROM `{$r['table_name']}`")->fetchColumn(); } catch (\Throwable $e) {}
    $needed = max(0, (int)$r['total_rows'] - $real);
    printf(
        "%-45s status=%-8s real=%12s hfsql=%12s needed=%12s last_id=%s\n",
        $r['table_name'], $r['status'],
        number_format($real), number_format((int)$r['total_rows']),
        number_format($needed), $r['last_synced_id'] ?? '—'
    );
}
