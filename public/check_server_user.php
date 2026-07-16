<?php
header('Content-Type: text/plain; charset=utf-8');
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    echo "=== DATA DIAGNOSTICS FOR ETABLISSEMENT 1301 ===\n\n";

    // 1. Check Offers
    $offersCount = DB::table('offre')->where('IDEts_Form', 1301)->count();
    echo "1. Total Offers in 'offre' for 1301: {$offersCount}\n";
    if ($offersCount > 0) {
        $modes = DB::table('offre')
            ->where('IDEts_Form', 1301)
            ->select('IDMode_formation', DB::raw('count(*) as count'))
            ->groupBy('IDMode_formation')
            ->get();
        foreach ($modes as $m) {
            echo "   - Mode {$m->IDMode_formation}: {$m->count} offers\n";
        }
    }

    // 2. Check Sections
    $sectionsCount = DB::table('section')->where('IDEts_Form', 1301)->count();
    echo "2. Total Sections in 'section' for 1301: {$sectionsCount}\n";
    if ($sectionsCount > 0) {
        $secs = DB::table('section')
            ->where('IDEts_Form', 1301)
            ->limit(5)
            ->get(['IDSection', 'Nom', 'DateDF', 'DateFF']);
        foreach ($secs as $s) {
            echo "   - Section ID {$s->IDSection}: '{$s->Nom}' | Start: {$s->DateDF} | End: {$s->DateFF}\n";
        }
    }

    // 3. Check Trainees
    $appCount = DB::table('apprenant as a')
        ->join('section as s', 'a.IDSection', '=', 's.IDSection')
        ->join('offre as o', 's.IDOffre', '=', 'o.IDOffre')
        ->where('o.IDEts_Form', 1301)
        ->count();
    echo "3. Total Trainees connected to 1301: {$appCount}\n";

    // 4. Check if the active trainees table has active records
    if ($appCount > 0) {
        $activeCount = DB::table('apprenant as a')
            ->join('section as s', 'a.IDSection', '=', 's.IDSection')
            ->join('offre as o', 's.IDOffre', '=', 'o.IDOffre')
            ->where('o.IDEts_Form', 1301)
            ->where('a.statut', 'actif')
            ->count();
        echo "   - Active Trainees (a.statut = 'actif'): {$activeCount}\n";

        $dateFiltered = DB::table('apprenant as a')
            ->join('section as s', 'a.IDSection', '=', 's.IDSection')
            ->join('offre as o', 's.IDOffre', '=', 'o.IDOffre')
            ->where('o.IDEts_Form', 1301)
            ->where('a.statut', 'actif')
            ->where('s.DateDF', '<=', date('Y-m-d'))
            ->where('s.DateFF', '>=', date('Y-m-d'))
            ->count();
        echo "   - Trainees with Active Section Dates: {$dateFiltered}\n";
    }

} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
