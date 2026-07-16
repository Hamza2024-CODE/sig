<?php
define('LARAVEL_START', microtime(true));
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

header('Content-Type: application/json; charset=utf-8');

try {
    // 1. Get Etablissement 1301 details
    $etab = DB::select("SELECT IDetablissement, IDDFEP, Nom FROM etablissement WHERE IDetablissement = 1301");

    // 2. Query dfep for IDDFEP = 19
    $dfep_19 = DB::select("SELECT * FROM dfep WHERE IDDFEP = 19");

    // 3. Query dfep for etablissement's IDDFEP
    $dfep_etab = [];
    if (count($etab) > 0) {
        $dfep_etab = DB::select("SELECT * FROM dfep WHERE IDDFEP = ?", [$etab[0]->IDDFEP]);
    }

    // 4. Query wilaya where IDWilayaa = 19
    $wilaya_19 = DB::select("SELECT * FROM wilaya WHERE IDWilayaa = 19");

    // 5. Query wilaya for the dfep's IDWilayaa
    $wilaya_dfep = [];
    if (count($dfep_etab) > 0) {
        $wilaya_dfep = DB::select("SELECT * FROM wilaya WHERE IDWilayaa = ?", [$dfep_etab[0]->IDWilayaa]);
    }

    echo json_encode([
        'status' => 'success',
        'etab' => $etab,
        'dfep_19' => $dfep_19,
        'dfep_etab' => $dfep_etab,
        'wilaya_19' => $wilaya_19,
        'wilaya_dfep' => $wilaya_dfep
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (\Throwable $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
