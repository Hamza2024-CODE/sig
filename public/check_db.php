<?php
define('LARAVEL_START', microtime(true));
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

header('Content-Type: text/plain; charset=utf-8');

try {
    echo "--- Search Etablissement matching IDetablissement = 1301 ---\n";
    $etab = DB::select("SELECT * FROM etablissement WHERE IDetablissement = 1301");
    foreach ($etab as $e) {
        echo "IDetablissement: {$e->IDetablissement} | IDEts_Form: {$e->IDEts_Form} | Nom: {$e->Nom} | NomFr: {$e->NomFr} | IDDFEP: {$e->IDDFEP}\n";
    }

    echo "\n--- Etablissement table schema (columns) ---\n";
    $cols = DB::select("DESCRIBE etablissement");
    foreach ($cols as $c) {
        echo "Field: {$c->Field} | Type: {$c->Type}\n";
    }

} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
