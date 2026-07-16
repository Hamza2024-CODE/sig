<?php
// Clear Laravel KPI cache for private institutions so new scope logic takes effect immediately
header('Content-Type: application/json; charset=utf-8');

try {
    define('LARAVEL_START', microtime(true));
    require __DIR__.'/../vendor/autoload.php';
    $app = require_once __DIR__.'/../bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

    $cache = app('cache');

    // Clear KPI cache for known private institution IDs
    $etabIds = [1096, 1095, 1301, 1300, 376];
    $cleared = [];

    foreach ($etabIds as $id) {
        $keys = [
            "sgfep:kpi:etab:{$id}:sessions_breakdown",
            "sgfep:kpi:etab:{$id}:s1_sections",
            "sgfep:kpi:etab:{$id}:active_filles",
            "sgfep:kpi:etab:{$id}:total_graduates",
            "sgfep:ref:etablissement_{$id}",
        ];
        foreach ($keys as $key) {
            $cache->forget($key);
            $cleared[] = $key;
        }
        // Also clear the main KPI cache key
        $cache->forget("sgfep:kpi:etab:{$id}");
        $cleared[] = "sgfep:kpi:etab:{$id}";
    }

    // Also flush all sgfep:kpi:etab keys using pattern if possible
    try {
        $store = $cache->getStore();
        if (method_exists($store, 'flush')) {
            // Only flush if it's a safe operation (file driver etc)
        }
    } catch (\Throwable $e) {}

    echo json_encode([
        'status' => 'success',
        'cleared_count' => count($cleared),
        'cleared_keys' => $cleared,
        'message' => 'KPI cache cleared for private institutions. Scope logic will now re-compute on next page visit.'
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (\Throwable $e) {
    echo json_encode(['error' => $e->getMessage(), 'trace' => substr($e->getTraceAsString(), 0, 500)]);
}
