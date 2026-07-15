<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

header('Content-Type: text/plain; charset=utf-8');

echo "=== REAL QUERY DIAGNOSTICS ===\n";
echo "Cached total_stagiaires (sgfep:kpi:minister:total_stagiaires_correct): " . var_export(\Illuminate\Support\Facades\Cache::get('sgfep:kpi:minister:total_stagiaires_correct'), true) . "\n";
echo "Cached sessions_breakdown: " . var_export(\Illuminate\Support\Facades\Cache::has('sgfep:kpi:minister:sessions_breakdown'), true) . "\n";


// Query 1: DEOH active trainees count
$q1 = DB::table('apprenant as a')
    ->join('section as s', 'a.IDSection', '=', 's.IDSection')
    ->leftJoin('apprenant_fin as af', 'a.IDapprenant', '=', 'af.IDapprenant')
    ->where('a.statut', 'actif')
    ->whereNull('af.IDapprenant')
    ->where('s.DateDF', '<=', now())
    ->where('s.DateFF', '>=', now())
    ->count();
echo "DEOH Query 1 count (total_stagiaires): $q1\n";

// Query 2: sessions_breakdown sum
$sessions = DB::table('session as sess')
    ->join('section as s', 's.IDSession', '=', 'sess.IDSession')
    ->join('apprenant as a', 'a.IDSection', '=', 's.IDSection')
    ->join('offre as o', 's.IDOffre', '=', 'o.IDOffre')
    ->join('specialite as sp', 'o.IDSpecialite', '=', 'sp.IDSpecialite')
    ->leftJoin('apprenant_fin as af', 'a.IDapprenant', '=', 'af.IDapprenant')
    ->whereNull('af.IDapprenant')
    ->whereRaw("DATE_ADD(sess.DateD, INTERVAL COALESCE(NULLIF(sp.dureeM, 0), sp.NbrSem * 6, 24) MONTH) >= CURRENT_DATE()")
    ->select('sess.IDSession', 'sess.Nom', DB::raw('count(a.IDapprenant) as count'))
    ->groupBy('sess.IDSession', 'sess.Nom')
    ->orderBy('sess.IDSession', 'desc')
    ->limit(5)
    ->get();

$sum = collect($sessions)->sum('count');
echo "Sessions Breakdown count: $sum\n";
foreach ($sessions as $s) {
    echo "  - Session {$s->Nom} (ID: {$s->IDSession}): {$s->count}\n";
}
