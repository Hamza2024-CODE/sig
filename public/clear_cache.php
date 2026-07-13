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
