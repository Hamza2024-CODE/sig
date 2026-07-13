<?php
header('Content-Type: text/plain; charset=utf-8');

define('LARAVEL_START', microtime(true));
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== SESSIONS LIST IN DATABASE ===\n\n";

try {
    $sessions = DB::table('session')->get();
    foreach ($sessions as $s) {
        print_r((array)$s);
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
