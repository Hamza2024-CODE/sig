<?php
header('Content-Type: text/plain; charset=utf-8');

define('LARAVEL_START', microtime(true));
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== DATABASE INDEXES CHECK ===\n\n";

$tables = ['section', 'apprenant', 'candidat', 'offre'];

foreach ($tables as $table) {
    echo "--- Indexes for table: $table ---\n";
    try {
        $indexes = DB::select("SHOW INDEX FROM `$table`");
        foreach ($indexes as $idx) {
            echo "Key_name: {$idx->Key_name} | Column_name: {$idx->Column_name} | Non_unique: {$idx->Non_unique}\n";
        }
    } catch (\Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
    echo "\n";
}
