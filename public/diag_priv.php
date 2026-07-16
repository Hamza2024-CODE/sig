<?php
header('Content-Type: application/json; charset=utf-8');

$results = [];

try {
    // 1. Bootstrap Laravel
    define('LARAVEL_START', microtime(true));
    require __DIR__.'/../vendor/autoload.php';
    $app = require_once __DIR__.'/../bootstrap/app.php';
    $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

    // 2. Clear all cache (artisan cache:clear equivalent)
    \Illuminate\Support\Facades\Artisan::call('cache:clear');
    $results['cache_clear'] = 'success';

    // 3. Download missing files from GitHub
    $files = [
        __DIR__.'/../app/Http/Controllers/Admin/ApprenantController.php'
            => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/app/Http/Controllers/Admin/ApprenantController.php',
        __DIR__.'/../app/Http/Controllers/Admin/ModulesController.php'
            => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/app/Http/Controllers/Admin/ModulesController.php',
        __DIR__.'/pull.php'
            => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/public/pull.php',
        __DIR__.'/clear_priv_cache.php'
            => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/public/clear_priv_cache.php',
    ];

    foreach ($files as $dest => $url) {
        $ctx = stream_context_create(['http' => ['timeout' => 15, 'header' => 'User-Agent: PHP']]);
        $content = @file_get_contents($url.'?v='.time(), false, $ctx);
        if ($content !== false && strlen($content) > 100) {
            file_put_contents($dest, $content);
            $results['downloaded'][] = basename($dest) . ' (' . strlen($content) . ' bytes)';
        } else {
            $results['failed'][] = basename($dest);
        }
    }

    // 4. Clear opcache for updated files
    if (function_exists('opcache_reset')) {
        opcache_reset();
        $results['opcache'] = 'reset';
    }

    $results['status'] = 'done';

} catch (\Throwable $e) {
    $results['error'] = $e->getMessage();
}

echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
