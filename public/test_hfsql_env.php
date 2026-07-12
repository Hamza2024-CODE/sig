<?php
header('Content-Type: text/plain; charset=utf-8');

define('LARAVEL_START', microtime(true));
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== HFSQL CONFIG DIAGNOSTIC ===\n";
echo "DSN: " . config('security.hfsql.dsn') . "\n";
echo "Username: " . config('security.hfsql.username') . "\n";
echo "Password Configured (Length): " . strlen(config('security.hfsql.password', '')) . "\n";
echo "Password via env() (Length): " . strlen(env('HFSQL_PASSWORD', '')) . "\n";

echo "\nTrying to connect to HFSQL database using Laravel connection class...\n";
try {
    $conn = \App\Core\HFSQLConnection::getInstance()->getConnection();
    echo "✓ Connection Success!\n";
} catch (\Exception $e) {
    echo "✗ Connection Failed: " . $e->getMessage() . "\n";
}
