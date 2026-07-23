<?php
header('Content-Type: text/plain; charset=utf-8');
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== ETABLISSEMENT 1316 ===\n";
try {
    $etab = DB::table('etablissement')->where('IDetablissement', 1316)->first();
    if ($etab) {
        foreach ((array)$etab as $k => $v) {
            echo " - $k: " . (is_null($v) ? 'NULL' : $v) . "\n";
        }
    } else {
        echo "Etablissement 1316 not found!\n";
    }
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
