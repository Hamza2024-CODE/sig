<?php
header('Content-Type: text/plain; charset=utf-8');
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== ETABLISSEMENT ID VS IDEts_Form IN WILAYA 35 ===\n";
try {
    $etabs = DB::table('etablissement')
        ->where('IDDFEP', 35)
        ->select('IDetablissement', 'Nom', 'IDEts_Form', 'PublPrive')
        ->get();
    foreach ($etabs as $et) {
        echo " - ID: {$et->IDetablissement} | IDEts_Form: " . ($et->IDEts_Form ?? 'NULL') . " | PublPrive: {$et->PublPrive} | Nom: {$et->Nom}\n";
    }
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
