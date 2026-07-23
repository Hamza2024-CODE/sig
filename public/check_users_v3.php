<?php
header('Content-Type: text/plain; charset=utf-8');
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== UTILISATEUR TABLE ===\n";
try {
    $count = DB::table('utilisateur')->count();
    echo "Total users in utilisateur: $count\n";
    $users = DB::table('utilisateur')->select('Code', 'NomUser', 'Nom', 'IDNature', 'admin')->limit(30)->get();
    foreach ($users as $u) {
        echo " - Code: {$u->Code} | NomUser: {$u->NomUser} | Nom: {$u->Nom} | IDNature: {$u->IDNature} | Admin: {$u->admin}\n";
    }
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

echo "\n=== ETABLISSEMENT 1317 ===\n";
try {
    $etab = DB::table('etablissement')->where('IDetablissement', 1317)->first();
    if ($etab) {
        foreach ((array)$etab as $k => $v) {
            echo " - $k: " . (is_null($v) ? 'NULL' : $v) . "\n";
        }
    } else {
        echo "Etablissement 1317 not found!\n";
    }
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

echo "\n=== DESCRIBE ETABLISSEMENT ===\n";
try {
    $cols = DB::select("DESCRIBE etablissement");
    foreach ($cols as $c) {
        if (in_array(strtolower($c->Field), ['idetablissement', 'nom', 'nomuser', 'motdepass', 'publprive', 'deidetablissementratache', 'deidetablissementratacheinsfp', 'idnature_etsf'])) {
            echo " - Column: {$c->Field} | Type: {$c->Type}\n";
        }
    }
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
