<?php
// PHP-FPM cache buster utility
header('Content-Type: text/plain; charset=utf-8');

if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "✓ OPcache reset successfully!\n";
} else {
    echo "ℹ OPcache is not enabled or opcache_reset is not available.\n";
}

try {
    require __DIR__ . '/../vendor/autoload.php';
    
    $app = require_once __DIR__ . '/../bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();
    
    \Illuminate\Support\Facades\Artisan::call('view:clear');
    echo "✓ Laravel View Cache Cleared!\n";
    
    \Illuminate\Support\Facades\Artisan::call('route:clear');
    echo "✓ Laravel Route Cache Cleared!\n";
    
    \Illuminate\Support\Facades\Artisan::call('cache:clear');
    echo "✓ Laravel App Cache Cleared!\n";
} catch (\Throwable $e) {
    echo "✗ Laravel Cache Clear Error: " . $e->getMessage() . "\n";
}
