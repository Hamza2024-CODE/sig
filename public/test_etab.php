<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

header('Content-Type: text/plain; charset=utf-8');

echo "=== ETABLISSEMENT DIAGNOSTICS ===\n";

try {
    $total = DB::table('etablissement')->count();
    echo "Total etablissements in DB: {$total}\n\n";
    
    echo "Grouped by IDNature_etsF:\n";
    $rows = DB::table('etablissement')
        ->select('IDNature_etsF', DB::raw('count(*) as count'))
        ->groupBy('IDNature_etsF')
        ->get();
    foreach ($rows as $row) {
        echo "Nature: " . ($row->IDNature_etsF ?? 'NULL') . " -> Count: " . $row->count . "\n";
    }

    echo "\nGrouped by activee:\n";
    $rows2 = DB::table('etablissement')
        ->select('activee', DB::raw('count(*) as count'))
        ->groupBy('activee')
        ->get();
    foreach ($rows2 as $row) {
        echo "Activee: " . var_export($row->activee, true) . " -> Count: " . $row->count . "\n";
    }

    echo "\nSample rows with Nature & Activee:\n";
    $samples = DB::table('etablissement')
        ->select('IDetablissement', 'Nom', 'IDNature_etsF', 'activee')
        ->limit(5)
        ->get();
    foreach ($samples as $s) {
        echo "ID: {$s->IDetablissement} | Nom: {$s->Nom} | Nature: {$s->IDNature_etsF} | Activee: " . var_export($s->activee, true) . "\n";
    }

} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
