<?php
header('Content-Type: text/plain; charset=utf-8');

define('LARAVEL_START', microtime(true));
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    $db = new \App\Core\LaravelDbAdapter();
    
    $start = microtime(true);
    $cnt = DB::selectOne("
        SELECT COUNT(*) as c
        FROM apprenant a
        JOIN section s ON a.IDSection = s.IDSection
        WHERE a.IDSection != 0 AND s.IDSession IN (31, 32, 33, 34, 35)
    ");
    $elapsed = microtime(true) - $start;
    echo "Count of Active (Simplified Joins): " . $cnt->c . " (Took: " . round($elapsed, 4) . " seconds)\n";
    
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
