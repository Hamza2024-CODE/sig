<?php
// SIG Auto-Update Deployment Script - Clean Build 2026-07-20
require __DIR__ . '/../vendor/autoload.php';
ini_set('user_agent', 'PHP');
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

echo "<h1>Auto-Updating All Modified Files...</h1>";

$files = [

    'app/Http/Controllers/Admin/ModulesController.php' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/app/Http/Controllers/Admin/ModulesController.php',
    'resources/views/admin/modules/distribution_globale.blade.php' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/resources/views/admin/modules/distribution_globale.blade.php'
];

foreach ($files as $localPath => $remoteUrl) {
    $fullPath = __DIR__ . '/../' . $localPath;
    $dir = dirname($fullPath);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    
    try {
        $content = @file_get_contents($remoteUrl . '?ts=' . microtime(true));
        if ($content !== false) {
            file_put_contents($fullPath, $content);
            clearstatcache(true, $fullPath);
            if (function_exists('opcache_invalidate')) {
                @opcache_invalidate($fullPath, true);
            }
            echo "✓ Updated: $localPath (" . strlen($content) . " bytes)<br>";
        } else {
            echo "<span style='color:red;'>✗ Failed to download: $localPath</span><br>";
        }
    } catch (\Throwable $e) {
        echo "<span style='color:red;'>✗ Exception downloading $localPath: " . $e->getMessage() . "</span><br>";
    }
}

echo "<h2>Clearing Laravel Cache and Views...</h2>";

$clearDir = function($dir) {
    if (!is_dir($dir)) return;
    try {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($files as $fileinfo) {
            $todo = $fileinfo->getRealPath();
            if ($fileinfo->isDir()) {
                @rmdir($todo);
            } else {
                @unlink($todo);
            }
        }
    } catch (\Throwable $ex) {}
};

// 1. Clear view cache via filesystem
$clearDir(__DIR__ . '/../storage/framework/views');
echo "✓ View Cache Cleared<br>";

// 2. Clear application cache via filesystem
$clearDir(__DIR__ . '/../storage/framework/cache/data');
echo "✓ Application Cache Cleared<br>";

try {
    \Illuminate\Support\Facades\Cache::flush();
    echo "✓ Laravel Cache::flush() executed successfully!<br>";
} catch (\Throwable $ex) {
    echo "✗ Direct Cache::flush() failed: " . $ex->getMessage() . "<br>";
}

// 3. Clear bootstrap cache
$bootstrapCacheDir = __DIR__ . '/../bootstrap/cache';
if (is_dir($bootstrapCacheDir)) {
    foreach (glob($bootstrapCacheDir . '/*.php') as $f) {
        @unlink($f);
    }
    echo "✓ Bootstrap Cache files Cleared<br>";
}

// 4. Try Artisan commands
try {
    @Artisan::call('view:clear');
    @Artisan::call('cache:clear');
    @Artisan::call('route:clear');
    echo "✓ Optional Artisan Cache Commands invoked<br>";
} catch (\Throwable $e) {}

if (function_exists('opcache_reset')) {
    @opcache_reset();
    echo "✓ OPCache Cleared!<br>";
}

echo "<h2>Running View Compilation Diagnostics...</h2>";
try {
    $html = view('dashboard.departments.exam')->render();
    echo "✓ SUCCESS: Exam view compiled and rendered fine!<br>";
} catch (\Throwable $e) {
    echo "<span style='color:red;font-weight:bold;'>[DIAGNOSTICS EXCEPTION]: " . get_class($e) . "</span><br>";
    echo "<span style='color:red;'>MESSAGE: " . $e->getMessage() . "</span><br>";
}

echo "<br><h3 style='color:green;'>All updates completed successfully!</h3>";

echo "<h2>Latest Server Log Entries (laravel.log):</h2>";
$logFile = __DIR__ . '/../storage/logs/laravel.log';
if (file_exists($logFile)) {
    $lines = file($logFile);
    $lastLines = array_slice($lines, -40);
    echo "<pre style='background:#1e1e1e;color:#00ff00;padding:10px;border-radius:6px;max-height:350px;overflow:auto;font-family:monospace;font-size:12px;'>" . htmlspecialchars(implode("", $lastLines)) . "</pre>";
} else {
    echo "<i>No laravel.log file found.</i>";
}
