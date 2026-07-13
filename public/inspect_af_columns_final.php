<?php
header('Content-Type: text/plain; charset=utf-8');

define('LARAVEL_START', microtime(true));
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Schema;

echo "=== INSPECTING APPRENANT_FIN COLUMNS ===\n\n";

try {
    $columns = Schema::getColumnListing('apprenant_fin');
    echo "Columns: " . implode(', ', $columns) . "\n\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
