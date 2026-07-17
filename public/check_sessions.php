<?php
define('LARAVEL_START', microtime(true));
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

header('Content-Type: text/plain; charset=utf-8');

try {
    $firstRows = DB::select("SELECT * FROM session ORDER BY DateD DESC LIMIT 10");
    print_r($firstRows);
} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
