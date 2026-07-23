<?php
header('Content-Type: text/plain; charset=utf-8');
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$etabId = 1317;

$etab = DB::table('etablissement')->where('IDetablissement', $etabId)->first();
if (!$etab) {
    echo "Etablissement $etabId not found!\n";
    exit;
}

// Let's print related fields of the establishment
echo "PublPrive: {$etab->PublPrive}\n";
echo "DeIDetablissementRatache: " . ($etab->DeIDetablissementRatache ?? 'null') . "\n";
echo "DeIDetablissementRatacheInsfp: " . ($etab->DeIDetablissementRatacheInsfp ?? 'null') . "\n";

try {
    // Let's count how many trainees exist for this establishment in total, and their sessions/sections
    $trainees = DB::table('apprenant as a')
        ->join('section as sec', 'a.IDSection', '=', 'sec.IDSection')
        ->join('offre as o', 'sec.IDOffre', '=', 'o.IDOffre')
        ->join('session as s', 'o.IDSession', '=', 's.IDSession')
        ->where('sec.IDEts_Form', $etabId)
        ->orWhere('sec.IDEts_FormM', $etabId)
        ->orWhere('o.IDEts_Form', $etabId)
        ->orWhere('o.IDEts_FormM', $etabId)
        ->select('s.Nom as session_name', DB::raw('count(*) as count'))
        ->groupBy('s.Nom')
        ->get();

    echo "\nTrainees matching etab 1317 by session:\n";
    foreach ($trainees as $t) {
        echo " - Session: {$t->session_name} | Count: {$t->count}\n";
    }

    // Let's see some random trainee records to check their section's IDEts_Form
    $sampleTrainees = DB::table('apprenant as a')
        ->join('section as sec', 'a.IDSection', '=', 'sec.IDSection')
        ->join('offre as o', 'sec.IDOffre', '=', 'o.IDOffre')
        ->where('sec.IDEts_Form', $etabId)
        ->orWhere('sec.IDEts_FormM', $etabId)
        ->orWhere('o.IDEts_Form', $etabId)
        ->orWhere('o.IDEts_FormM', $etabId)
        ->select('a.IDApprenant', 'sec.IDSection', 'sec.Nom as sec_name', 'sec.IDEts_Form as sec_etab', 'sec.IDEts_FormM as sec_etab_m', 'o.IDOffre', 'o.IDEts_Form as offre_etab', 'o.IDEts_FormM as offre_etab_m')
        ->limit(5)
        ->get();
    echo "\nSample trainees matching etab 1317:\n";
    foreach ($sampleTrainees as $st) {
        echo " - Trainee ID: {$st->IDApprenant} | SecID: {$st->IDSection} | SecName: {$st->sec_name} | SecEtab: {$st->sec_etab} | SecEtabM: {$st->sec_etab_m} | OffreID: {$st->IDOffre} | OffreEtab: {$st->offre_etab} | OffreEtabM: {$st->offre_etab_m}\n";
    }
} catch (\Throwable $e) {
    echo "\nERROR in trainees query: " . $e->getMessage() . "\n";
}


// Let's find other etabs containing Ansim or أنسيم
$etabsLike = DB::table('etablissement')
    ->where('Nom', 'LIKE', '%أنسيم%')
    ->orWhere('Nom', 'LIKE', '%Ansim%')
    ->select('IDetablissement', 'Nom', 'IDDFEP', 'PublPrive')
    ->get();
echo "\nEtablissements like 'Ansim':\n";
foreach ($etabsLike as $el) {
    echo " - ID: {$el->IDetablissement} | Nom: {$el->Nom} | IDDFEP: {$el->IDDFEP} | PublPrive: {$el->PublPrive}\n";
}

// Let's search if there are offers in session Sept 2025, Sept 2024, etc. for the attached public schools
$attachedIds = [];
if (!empty($etab->DeIDetablissementRatache)) $attachedIds[] = (int)$etab->DeIDetablissementRatache;
if (!empty($etab->DeIDetablissementRatacheInsfp)) $attachedIds[] = (int)$etab->DeIDetablissementRatacheInsfp;

if (!empty($attachedIds)) {
    echo "\nAttached public school IDs: " . implode(', ', $attachedIds) . "\n";
    $attachedEtabs = DB::table('etablissement')->whereIn('IDetablissement', $attachedIds)->select('IDetablissement', 'Nom')->get();
    foreach ($attachedEtabs as $ae) {
        echo " - ID: {$ae->IDetablissement} | Nom: {$ae->Nom}\n";
    }

    // Find offers of these public schools that might be related to 1317 (perhaps through a branch, section, or description)
    // Or let's see how many offers these public schools have in Sept 2025, Sept 2024
    $attachedOffers = DB::table('offre as o')
        ->join('session as s', 'o.IDSession', '=', 's.IDSession')
        ->whereIn('o.IDEts_Form', $attachedIds)
        ->select('s.Nom as session_name', DB::raw('count(*) as count'))
        ->groupBy('s.Nom')
        ->get();
    echo "\nOffers of attached public schools by session:\n";
    foreach ($attachedOffers as $ao) {
        echo " - Session: {$ao->session_name} | Count: {$ao->count}\n";
    }
}
