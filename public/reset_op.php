<?php
header('Content-Type: text/plain; charset=utf-8');

define('LARAVEL_START', microtime(true));
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Artisan;

try {
    Artisan::call('config:clear');
    echo "✓ Laravel config cache cleared successfully.\n";
} catch (\Exception $e) {
    echo "✗ Error clearing config cache: " . $e->getMessage() . "\n";
}

if (function_exists('opcache_reset')) {
    $res = opcache_reset();
    echo "✓ OPcache reset result: " . ($res ? "Success (تم بنجاح)" : "Failed (فشل)") . "\n";
} else {
    echo "✗ OPcache is not enabled or function opcache_reset is disabled on this server.\n";
}

// Clear compiled views
$baseDir = realpath(__DIR__ . '/..');
$files = glob($baseDir . '/storage/framework/views/*.php');
foreach ($files as $file) {
    if (is_file($file)) {
        unlink($file);
    }
}
echo "✓ Laravel view cache cleared.\n";
