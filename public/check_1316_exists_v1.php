<?php
header('Content-Type: text/plain; charset=utf-8');
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== CHECKING ETABLISSEMENT ID 1316 ===\n";
try {
    $etab1316 = DB::table('etablissement')->where('IDetablissement', 1316)->first();
    if ($etab1316) {
        echo "Etablissement 1316 exists!\n";
        echo " - Name: {$etab1316->Nom}\n";
        echo " - PublPrive: {$etab1316->PublPrive}\n";
        echo " - nomUser: {$etab1316->nomUser}\n";
    } else {
        echo "Etablissement 1316 does NOT exist in the etablissement table!\n";
    }
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
