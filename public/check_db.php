<?php
header('Content-Type: text/plain; charset=utf-8');

define('LARAVEL_START', microtime(true));
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== CHECKING EMPLOYEE (ENCADREMENT) PHOTO PATHS ===\n";

try {
    $rows = DB::table('encadrement')
        ->whereNotNull('photo')
        ->where('photo', '!=', '')
        ->select('IDEncadrement', 'Nom', 'Prenom', 'photo')
        ->limit(10)
        ->get();

    if ($rows->isEmpty()) {
        echo "No records found in 'encadrement' table with a non-empty photo.\n";
    } else {
        foreach ($rows as $row) {
            echo "ID: {$row->IDEncadrement} | Name: {$row->Nom} {$row->Prenom} | Photo path in DB: '{$row->photo}'\n";
        }
    }
} catch (\Exception $e) {
    echo "Error querying 'encadrement': " . $e->getMessage() . "\n";
}

echo "\n=== CHECKING MEMO (ENCADREMEN_MEMO) PHOTO PATHS ===\n";

try {
    $rowsMemo = DB::table('encadremen_memo')
        ->whereNotNull('photo')
        ->where('photo', '!=', '')
        ->select('IDEncadremen_memo', 'IDEncadrement', 'photo')
        ->limit(10)
        ->get();

    if ($rowsMemo->isEmpty()) {
        echo "No records found in 'encadremen_memo' table with a non-empty photo.\n";
    } else {
        foreach ($rowsMemo as $row) {
            echo "Memo ID: {$row->IDEncadremen_memo} | Employee ID: {$row->IDEncadrement} | Photo path in DB: '{$row->photo}'\n";
        }
    }
} catch (\Exception $e) {
    echo "Error querying 'encadremen_memo': " . $e->getMessage() . "\n";
}
