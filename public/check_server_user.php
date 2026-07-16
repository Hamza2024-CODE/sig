<?php
header('Content-Type: text/plain; charset=utf-8');
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== DIAGNOSTICS FOR ALL PRIVATE ESTABLISHMENTS ===\n";

try {
    // Get all private establishments (PublPrive = 1)
    $etabs = DB::table('etablissement')
        ->where('PublPrive', 1)
        ->get(['IDetablissement', 'Nom', 'nomUser', 'activee']);

    echo "Found " . $etabs->count() . " private establishments in server database:\n\n";

    foreach ($etabs as $e) {
        echo "ID: {$e->IDetablissement} | Nom: '{$e->Nom}' | nomUser: '{$e->nomUser}' | activee: {$e->activee}\n";
    }

    echo "\n=== SEARCH FOR ANY NOMUSER CONTAINING '1300' OR '1301' ===\n";
    $search = DB::table('etablissement')
        ->where('nomUser', 'LIKE', '%1300%')
        ->orWhere('nomUser', 'LIKE', '%1301%')
        ->get(['IDetablissement', 'Nom', 'nomUser']);
        
    echo "Found " . $search->count() . " matches:\n";
    foreach ($search as $s) {
        echo "ID: {$s->IDetablissement} | Nom: '{$s->Nom}' | nomUser: '{$s->nomUser}'\n";
    }

} catch (\Throwable $e) {
    echo "Query Error: " . $e->getMessage() . "\n";
}
