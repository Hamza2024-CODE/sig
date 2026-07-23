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

echo "Etablissement: {$etab->Nom} (ID: {$etab->IDetablissement})\n";

// Let's count how many offers exist for this establishment in total, and their sessions
$totalOffers = DB::table('offre')->where('IDEts_Form', $etabId)->orWhere('IDEts_FormM', $etabId)->count();
echo "Total offers in 'offre' table (IDEts_Form or IDEts_FormM = 1317): {$totalOffers}\n";

$sessionsWithOffers = DB::table('offre as o')
    ->join('session as s', 'o.IDSession', '=', 's.IDSession')
    ->where(function($q) use ($etabId) {
        $q->where('o.IDEts_Form', $etabId)
          ->orWhere('o.IDEts_FormM', $etabId);
    })
    ->select('s.IDSession', 's.Nom', 's.Encour', DB::raw('count(*) as cnt'), DB::raw('sum(o.NbrInscr) as sum_inscr'))
    ->groupBy('s.IDSession', 's.Nom', 's.Encour')
    ->get();

echo "Sessions with offers:\n";
foreach ($sessionsWithOffers as $so) {
    echo " - Session: {$so->Nom} (ID: {$so->IDSession}, Encour: {$so->Encour}) | Count: {$so->cnt} | Sum NbrInscr: {$so->sum_inscr}\n";
}

// Let's list some details of the offers
$offers = DB::table('offre as o')
    ->join('session as s', 'o.IDSession', '=', 's.IDSession')
    ->where(function($q) use ($etabId) {
        $q->where('o.IDEts_Form', $etabId)
          ->orWhere('o.IDEts_FormM', $etabId);
    })
    ->select('o.IDOffre', 'o.NbrInscr', 'o.Valide', 'o.ValidDfp', 'o.ValideCentral', 's.Nom as session_name', 's.Encour')
    ->orderBy('s.DateD', 'DESC')
    ->get();

echo "\nDetails of all offers:\n";
foreach ($offers as $o) {
    echo " - IDOffre: {$o->IDOffre} | Session: {$o->session_name} | NbrInscr: {$o->NbrInscr} | Valide: {$o->Valide} | ValidDfp: {$o->ValidDfp} | ValideCentral: {$o->ValideCentral}\n";
}
