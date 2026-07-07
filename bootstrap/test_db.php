<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    $count = DB::select('SELECT COUNT(*) as count FROM etablissement');
    echo "Etablissement Count: " . $count[0]->count . "\n";
    
    $directions = DB::select("
        SELECT e.IDetablissement, e.Nom as name_ar, e.NomFr as name_fr, ed.Latitude as lat, ed.Longitude as lng
        FROM etablissement e
        JOIN etablisement_detail ed ON ed.IDetablissement = e.IDetablissement
        WHERE e.IDNature_etsF = 5
    ");
    echo "Directions Count: " . count($directions) . "\n";
    if (count($directions) > 0) {
        print_r($directions[0]);
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
