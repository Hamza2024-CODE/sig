<?php
header('Content-Type: text/plain; charset=utf-8');

define('LARAVEL_START', microtime(true));
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== DIAGNOSING SECTION DATE FORMATS AND OFFRE RENTREE ===\n\n";

try {
    $sections = DB::table('section')
        ->select('IDSection', 'NomSection', 'DateDF', 'DateFF')
        ->whereNotNull('DateDF')
        ->limit(5)
        ->get();
    
    echo "--- Section Date Samples ---\n";
    foreach ($sections as $s) {
        echo "ID: {$s->IDSection} | Name: {$s->NomSection} | DateDF: '{$s->DateDF}' | DateFF: '{$s->DateFF}'\n";
    }
} catch (\Exception $e) {
    echo "Error querying section dates: " . $e->getMessage() . "\n";
}

try {
    $offres = DB::table('offre')
        ->select('IDOffre', 'rentree', 'Session_rentree')
        ->whereNotNull('rentree')
        ->limit(5)
        ->get();
    
    echo "\n--- Offre Rentree/Session Samples ---\n";
    foreach ($offres as $o) {
        echo "ID: {$o->IDOffre} | rentree: '{$o->rentree}' | Session_rentree: '{$o->Session_rentree}'\n";
    }
} catch (\Exception $e) {
    echo "Error querying offre rentree: " . $e->getMessage() . "\n";
}
