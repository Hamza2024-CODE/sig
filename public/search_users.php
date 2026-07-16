<?php
define('LARAVEL_START', microtime(true));
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

header('Content-Type: text/plain; charset=utf-8');

try {
    echo "--- Search by username like '%1300%' ---\n";
    $users = DB::select("SELECT * FROM utilisateur WHERE NomUser LIKE '%1300%'");
    foreach ($users as $u) {
        echo "IDUtilisateur: {$u->IDUtilisateur} | NomUser: {$u->NomUser} | Nom: {$u->Nom} | IDBureau: {$u->IDBureau} | IDdirection: {$u->IDdirection} | activee: {$u->activee}\n";
    }

    echo "\n--- Search by name like '%تاج%' ---\n";
    $users2 = DB::select("SELECT * FROM utilisateur WHERE Nom LIKE '%تاج%' OR NomUser LIKE '%taj%'");
    foreach ($users2 as $u) {
        echo "IDUtilisateur: {$u->IDUtilisateur} | NomUser: {$u->NomUser} | Nom: {$u->Nom} | IDBureau: {$u->IDBureau} | IDdirection: {$u->IDdirection} | activee: {$u->activee}\n";
    }

    echo "\n--- Search by etablissement like '%التاج%' ---\n";
    $etabs = DB::select("SELECT * FROM etablissement WHERE Nom LIKE '%التاج%' OR Nom LIKE '%تاج%' OR NomFr LIKE '%taj%' OR NomFr LIKE '%crown%' OR IDetablissement = 1300");
    foreach ($etabs as $e) {
        echo "IDetablissement: {$e->IDetablissement} | Nom: {$e->Nom} | NomFr: {$e->NomFr} | IDDFEP: {$e->IDDFEP}\n";
    }
} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
