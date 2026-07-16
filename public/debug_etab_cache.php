<?php
header('Content-Type: text/plain; charset=utf-8');

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

$etabId = 1301;
$etabScopeIds = [$etabId];

echo "=== SERVER CACHE & DB DIAGNOSTICS FOR ETABLISSEMENT $etabId ===\n\n";

try {
    // 1. Active cache driver
    $defaultDriver = config('cache.default');
    echo "1. Default Cache Driver: $defaultDriver\n";

    // 2. Read cached session breakdown
    $cacheKey = "sgfep:kpi:etab:{$etabId}:sessions_breakdown";
    $cachedData = Cache::get($cacheKey);
    if ($cachedData !== null) {
        echo "2. Cached sessions_breakdown exists:\n";
        print_r($cachedData);
    } else {
        echo "2. Cached sessions_breakdown does not exist (null).\n";
    }

    // 3. Force invalidate the cache keys for etab 1301
    Cache::forget($cacheKey);
    Cache::forget("sgfep:kpi:etab:{$etabId}:s1_sections");
    Cache::forget("sgfep:kpi:etab:{$etabId}:active_filles");
    Cache::forget("sgfep:kpi:etab:{$etabId}:total_graduates");
    
    // Also clear general etab cache
    \App\Services\KpiCache::invalidateEtab($etabId);
    echo "3. Force cleared cache keys for etab $etabId.\n";

    // 4. Run breakdown query directly on Server DB
    $breakdown = DB::table('session as sess')
        ->join('section as s', 's.IDSession', '=', 'sess.IDSession')
        ->join('apprenant as a', 'a.IDSection', '=', 's.IDSection')
        ->join('offre as o', 's.IDOffre', '=', 'o.IDOffre')
        ->join('specialite as sp', 'o.IDSpecialite', '=', 'sp.IDSpecialite')
        ->leftJoin('apprenant_fin as af', 'a.IDapprenant', '=', 'af.IDapprenant')
        ->whereIn('s.IDEts_Form', $etabScopeIds)
        ->whereNull('af.IDapprenant')
        ->whereRaw("DATE_ADD(sess.DateD, INTERVAL COALESCE(NULLIF(sp.dureeM, 0), sp.NbrSem * 6, 24) MONTH) >= CURRENT_DATE()")
        ->select('sess.IDSession', 'sess.Nom', DB::raw('count(a.IDapprenant) as count'))
        ->groupBy('sess.IDSession', 'sess.Nom')
        ->orderBy('sess.IDSession', 'desc')
        ->limit(5)
        ->get();

    echo "\n4. Server DB Breakdown Query Results:\n";
    $total = 0;
    foreach ($breakdown as $row) {
        echo "   Session ID: {$row->IDSession} ({$row->Nom}) | Count: {$row->count}\n";
        $total += $row->count;
    }
    echo "   Total Stagiaires (Sum of breakdown): $total\n";
    
    $reconduits = collect($breakdown)->slice(1)->sum('count');
    echo "   Total Reconduits (slice 1 to end): $reconduits\n";

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
