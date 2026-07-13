<?php
header('Content-Type: text/plain; charset=utf-8');

define('LARAVEL_START', microtime(true));
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "=== INSPECTING EQUIPEMENT_MEMO SCHEMAS AND VALUES (NEW FILE) ===\n\n";

try {
    $columns = Schema::getColumnListing('equipement_memo');
    echo "Columns: " . implode(', ', $columns) . "\n\n";

    $photos = DB::table('equipement_memo')
        ->whereNotNull('photo')
        ->where('photo', '<>', '')
        ->limit(5)
        ->get();
        
    echo "Records with photos:\n";
    foreach ($photos as $p) {
        print_r((array)$p);
    }
} catch (\Exception $ex) {
    echo "Error: " . $ex->getMessage() . "\n";
}
