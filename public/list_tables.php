<?php
define('LARAVEL_START', microtime(true));
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

header('Content-Type: text/plain; charset=utf-8');

try {
    $tables = DB::select("SHOW TABLES");
    foreach ($tables as $t) {
        $arr = (array)$t;
        echo array_values($arr)[0] . "\n";
    }
} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage();
}
