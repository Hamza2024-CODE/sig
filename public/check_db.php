<?php
define('LARAVEL_START', microtime(true));
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

header('Content-Type: text/plain; charset=utf-8');

try {
    $years = DB::table('annee_formation')->get();
    foreach ($years as $y) {
        echo "ID: {$y->IDAnnee_Formation} | Nom: {$y->Nom} | NomFr: {$y->NomFr}\n";
    }

    $sessions = DB::table('session as s')
        ->join('semestre_formation as sf', 's.IDSemestre_formation', '=', 'sf.IDSemestre_formation')
        ->join('annee_formation as af', 'sf.IDAnnee_Formation', '=', 'af.IDAnnee_Formation')
        ->select('s.IDSession', 's.Nom as s_nom', 'af.Nom as af_nom', 'af.IDAnnee_Formation')
        ->orderBy('s.IDSession', 'desc')
        ->limit(10)
        ->get();

    echo "\nLatest Sessions:\n";
    foreach ($sessions as $s) {
        echo "Session ID: {$s->IDSession} | Nom: {$s->s_nom} | Année: {$s->af_nom} (ID: {$s->IDAnnee_Formation})\n";
    }

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
}
