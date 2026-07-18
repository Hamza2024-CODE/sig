<?php
define('LARAVEL_START', microtime(true));
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

header('Content-Type: text/plain; charset=utf-8');

try {
    $row = DB::selectOne("SELECT * FROM etablissement LIMIT 1");
    if ($row) {
        echo "Columns in etablissement table:\n";
        print_r(array_keys((array)$row));
    } else {
        echo "No rows found in etablissement table.\n";
    }
} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
