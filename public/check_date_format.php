<?php
header('Content-Type: text/plain; charset=utf-8');

define('LARAVEL_START', microtime(true));
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== DIAGNOSING ACTUAL DATES AND COLUMNS ===\n\n";

try {
    $row = DB::table('section')->whereNotNull('DateDF')->first();
    if ($row) {
        echo "✓ Found Section record!\n";
        echo "DateDF value: '" . ($row->DateDF ?? 'NULL') . "' (Type: " . gettype($row->DateDF) . ")\n";
        echo "DateFF value: '" . ($row->DateFF ?? 'NULL') . "'\n\n";
        echo "All fields in this row:\n";
        print_r((array)$row);
    } else {
        echo "❌ No sections found with DateDF not null.\n";
    }
} catch (\Exception $e) {
    echo "❌ Error reading section: " . $e->getMessage() . "\n";
}

try {
    $rowOffre = DB::table('offre')->first();
    if ($rowOffre) {
        echo "\n✓ Found Offre record!\n";
        echo "All fields in this row:\n";
        print_r((array)$rowOffre);
    } else {
        echo "❌ No offre records found.\n";
    }
} catch (\Exception $e) {
    echo "❌ Error reading offre: " . $e->getMessage() . "\n";
}
