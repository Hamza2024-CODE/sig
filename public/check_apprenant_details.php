<?php
header('Content-Type: text/plain; charset=utf-8');

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$etabId = 1301;
echo "=== SERVER DIAGNOSTICS FOR ETABLISSEMENT $etabId ===\n\n";

try {
    // 1. Total trainees count
    $total = DB::table('apprenant as a')
        ->join('section as s', 'a.IDSection', '=', 's.IDSection')
        ->where('s.IDEts_Form', $etabId)
        ->count();
    echo "1. Total Trainees: $total\n";

    // 2. Trainees count by status
    $byStatus = DB::table('apprenant as a')
        ->join('section as s', 'a.IDSection', '=', 's.IDSection')
        ->where('s.IDEts_Form', $etabId)
        ->select('a.statut', DB::raw('count(*) as count'))
        ->groupBy('a.statut')
        ->get();
    echo "\n2. Trainees count by 'statut':\n";
    foreach ($byStatus as $bs) {
        echo "   Status: '{$bs->statut}' | Count: {$bs->count}\n";
    }

    // 3. Trainees count by session
    $bySession = DB::table('apprenant as a')
        ->join('section as s', 'a.IDSection', '=', 's.IDSection')
        ->join('session as sess', 's.IDSession', '=', 'sess.IDSession')
        ->where('s.IDEts_Form', $etabId)
        ->select('s.IDSession', 'sess.Nom', DB::raw('count(*) as count'))
        ->groupBy('s.IDSession', 'sess.Nom')
        ->get();
    echo "\n3. Trainees count by Session:\n";
    foreach ($bySession as $bs) {
        echo "   Session ID: {$bs->IDSession} ({$bs->Nom}) | Count: {$bs->count}\n";
    }

    // 4. First 10 trainees list
    $list = DB::table('apprenant as a')
        ->join('section as s', 'a.IDSection', '=', 's.IDSection')
        ->join('candidat as c', 'a.IDCandidat', '=', 'c.IDCandidat')
        ->join('session as sess', 's.IDSession', '=', 'sess.IDSession')
        ->where('s.IDEts_Form', $etabId)
        ->select('a.IDapprenant', 'c.Nom', 'c.Prenom', 'a.statut', 'sess.Nom as session_nom')
        ->limit(10)
        ->get();
    echo "\n4. First 10 Trainees:\n";
    foreach ($list as $t) {
        echo "   ID: {$t->IDapprenant} | Name: {$t->Nom} {$t->Prenom} | Status: {$t->statut} | Session: {$t->session_nom}\n";
    }

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
