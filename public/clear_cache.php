<?php
define('LARAVEL_START', microtime(true));

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    if (function_exists('opcache_reset')) {
        opcache_reset();
        echo "<h1>OPCache Reset Successfully!</h1>";
    }
    
    // Manually delete cached files to bypass file ownership/permission issues
    $cacheDir = __DIR__ . '/../bootstrap/cache';
    $filesToClear = ['routes-v7.php', 'config.php', 'services.php', 'packages.php'];
    foreach ($filesToClear as $f) {
        $path = $cacheDir . '/' . $f;
        if (file_exists($path)) {
            if (@unlink($path)) {
                echo "<h1>Manually Deleted Cache File: $f</h1>";
            } else {
                echo "<h1>Failed to Delete Cache File: $f (Permission Denied)</h1>";
            }
        }
    }
    
    \Illuminate\Support\Facades\Artisan::call('route:clear');
    echo "<h1>Route Cache Cleared!</h1>";
    
    \Illuminate\Support\Facades\Artisan::call('view:clear');
    echo "<h1>View Cache Cleared!</h1>";
    
    \Illuminate\Support\Facades\Artisan::call('config:clear');
    echo "<h1>Config Cache Cleared!</h1>";
    
    \Illuminate\Support\Facades\Artisan::call('cache:clear');
    echo "<h1>Cache Cleared Successfully!</h1>";
    echo "<pre>" . \Illuminate\Support\Facades\Artisan::output() . "</pre>";
} catch (\Exception $e) {
    echo "<h1>Error clearing cache:</h1>";
    echo "<pre>" . $e->getMessage() . "</pre>";
}
