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

// Let's search the section table for IDEts_Form = 1317 or IDEts_FormM = 1317
$sections = DB::table('section as sec')
    ->join('offre as o', 'sec.IDOffre', '=', 'o.IDOffre')
    ->join('session as s', 'o.IDSession', '=', 's.IDSession')
    ->where('sec.IDEts_Form', $etabId)
    ->orWhere('sec.IDEts_FormM', $etabId)
    ->select('sec.IDSection', 'sec.Nom as sec_name', 'sec.IDEts_Form', 'sec.IDEts_FormM', 'o.IDOffre', 's.Nom as session_name')
    ->get();

echo "\nSections matching etab 1317 in 'section' table: " . count($sections) . "\n";
foreach ($sections as $sec) {
    echo " - SecID: {$sec->IDSection} | Name: {$sec->sec_name} | IDEts_Form: {$sec->IDEts_Form} | IDEts_FormM: {$sec->IDEts_FormM} | OffreID: {$sec->IDOffre} | Session: {$sec->session_name}\n";
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

