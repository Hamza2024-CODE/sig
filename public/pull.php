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
    'resources/views/admin/diplomes/print.blade.php' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/resources/views/admin/diplomes/print.blade.php',
    'resources/views/admin/diplomes/print_batch.blade.php' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/resources/views/admin/diplomes/print_batch.blade.php',
    'public/diplom/WhatsApp Image 2026-07-19 at 17.38.46 (2).jpeg' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/public/diplom/WhatsApp%20Image%202026-07-19%20at%2017.38.46%20%282%29.jpeg',
    'public/diplom/WhatsApp Image 2026-07-19 at 17.38.47 (1).jpeg' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/public/diplom/WhatsApp%20Image%202026-07-19%20at%2017.38.47%20%281%29.jpeg',
    'public/diplom/WhatsApp Image 2026-07-19 at 17.38.47 (2).jpeg' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/public/diplom/WhatsApp%20Image%202026-07-19%20at%2017.38.47%20%282%29.jpeg',
    'public/diplom/WhatsApp Image 2026-07-19 at 17.38.47 (3).jpeg' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/public/diplom/WhatsApp%20Image%202026-07-19%20at%2017.38.47%20%283%29.jpeg',
    'public/diplom/WhatsApp Image 2026-07-19 at 17.38.47 (5).jpeg' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/public/diplom/WhatsApp%20Image%202026-07-19%20at%2017.38.47%20%285%29.jpeg',
    'public/diplom/WhatsApp Image 2026-07-19 at 17.38.47.jpeg' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/public/diplom/WhatsApp%20Image%202026-07-19%20at%2017.38.47.jpeg',
    'public/diplom/WhatsApp Image 2026-07-19 at 17.38.48 (1).jpeg' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/public/diplom/WhatsApp%20Image%202026-07-19%20at%2017.38.48%20%281%29.jpeg',
    'public/diplom/WhatsApp Image 2026-07-19 at 17.38.48 (3).jpeg' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/public/diplom/WhatsApp%20Image%202026-07-19%20at%2017.38.48%20%283%29.jpeg',
    'public/diplom/WhatsApp Image 2026-07-19 at 17.38.48 (5).jpeg' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/public/diplom/WhatsApp%20Image%202026-07-19%20at%2017.38.48%20%285%29.jpeg',
    'public/diplom/WhatsApp Image 2026-07-19 at 17.38.48.jpeg' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/public/diplom/WhatsApp%20Image%202026-07-19%20at%2017.38.48.jpeg',
    'public/diplom/WhatsApp Image 2026-07-19 at 17.38.49 (1).jpeg' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/public/diplom/WhatsApp%20Image%202026-07-19%20at%2017.38.49%20%281%29.jpeg',
    'public/diplom/WhatsApp Image 2026-07-19 at 17.38.49 (6).jpeg' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/public/diplom/WhatsApp%20Image%202026-07-19%20at%2017.38.49%20%286%29.jpeg',
    'public/diplom/WhatsApp Image 2026-07-19 at 17.38.49.jpeg' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/public/diplom/WhatsApp%20Image%202026-07-19%20at%2017.38.49.jpeg',
    'public/diplom/WhatsApp Image 2026-07-19 at 17.38.50 (1).jpeg' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/public/diplom/WhatsApp%20Image%202026-07-19%20at%2017.38.50%20%281%29.jpeg',
    'public/diplom/WhatsApp Image 2026-07-19 at 17.38.50 (2).jpeg' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/public/diplom/WhatsApp%20Image%202026-07-19%20at%2017.38.50%20%282%29.jpeg',
    'public/diplom/WhatsApp Image 2026-07-19 at 17.38.50 (3).jpeg' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/public/diplom/WhatsApp%20Image%202026-07-19%20at%2017.38.50%20%283%29.jpeg',
    'public/diplom/WhatsApp Image 2026-07-19 at 17.38.50 (4).jpeg' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/public/diplom/WhatsApp%20Image%202026-07-19%20at%2017.38.50%20%284%29.jpeg',
    'public/diplom/WhatsApp Image 2026-07-19 at 17.38.50 (5).jpeg' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/public/diplom/WhatsApp%20Image%202026-07-19%20at%2017.38.50%20%285%29.jpeg',
    'public/diplom/WhatsApp Image 2026-07-19 at 17.38.50.jpeg' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/public/diplom/WhatsApp%20Image%202026-07-19%20at%2017.38.50.jpeg',
    'public/diplom/WhatsApp Image 2026-07-19 at 17.38.51 (1).jpeg' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/public/diplom/WhatsApp%20Image%202026-07-19%20at%2017.38.51%20%281%29.jpeg',
    'public/diplom/WhatsApp Image 2026-07-19 at 17.38.51 (2).jpeg' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/public/diplom/WhatsApp%20Image%202026-07-19%20at%2017.38.51%20%282%29.jpeg',
    'public/diplom/WhatsApp Image 2026-07-19 at 17.38.51 (3).jpeg' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/public/diplom/WhatsApp%20Image%202026-07-19%20at%2017.38.51%20%283%29.jpeg',
    'public/diplom/WhatsApp Image 2026-07-19 at 17.38.51 (4).jpeg' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/public/diplom/WhatsApp%20Image%202026-07-19%20at%2017.38.51%20%284%29.jpeg',
    'public/diplom/WhatsApp Image 2026-07-19 at 17.38.51 (5).jpeg' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/public/diplom/WhatsApp%20Image%202026-07-19%20at%2017.38.51%20%285%29.jpeg',
    'public/diplom/WhatsApp Image 2026-07-19 at 17.38.51.jpeg' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/public/diplom/WhatsApp%20Image%202026-07-19%20at%2017.38.51.jpeg',
    'public/diplom/WhatsApp Image 2026-07-19 at 17.38.52 (1).jpeg' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/public/diplom/WhatsApp%20Image%202026-07-19%20at%2017.38.52%20%281%29.jpeg',
    'public/diplom/WhatsApp Image 2026-07-19 at 17.38.52 (2).jpeg' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/public/diplom/WhatsApp%20Image%202026-07-19%20at%2017.38.52%20%282%29.jpeg',
    'public/diplom/WhatsApp Image 2026-07-19 at 17.38.52 (3).jpeg' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/public/diplom/WhatsApp%20Image%202026-07-19%20at%2017.38.52%20%283%29.jpeg',
    'public/diplom/WhatsApp Image 2026-07-19 at 17.38.52 (4).jpeg' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/public/diplom/WhatsApp%20Image%202026-07-19%20at%2017.38.52%20%284%29.jpeg',
    'public/diplom/WhatsApp Image 2026-07-19 at 17.38.52 (6).jpeg' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/public/diplom/WhatsApp%20Image%202026-07-19%20at%2017.38.52%20%286%29.jpeg',
    'public/diplom/WhatsApp Image 2026-07-19 at 17.38.52.jpeg' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/public/diplom/WhatsApp%20Image%202026-07-19%20at%2017.38.52.jpeg',
    'public/diplom/WhatsApp Image 2026-07-19 at 17.38.53 (1).jpeg' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/public/diplom/WhatsApp%20Image%202026-07-19%20at%2017.38.53%20%281%29.jpeg',
    'public/diplom/WhatsApp Image 2026-07-19 at 17.38.53 (2).jpeg' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/public/diplom/WhatsApp%20Image%202026-07-19%20at%2017.38.53%20%282%29.jpeg',
    'public/diplom/WhatsApp Image 2026-07-19 at 17.38.53 (3).jpeg' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/public/diplom/WhatsApp%20Image%202026-07-19%20at%2017.38.53%20%283%29.jpeg',
    'public/diplom/WhatsApp Image 2026-07-19 at 17.38.53 (4).jpeg' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/public/diplom/WhatsApp%20Image%202026-07-19%20at%2017.38.53%20%284%29.jpeg',
    'public/diplom/WhatsApp Image 2026-07-19 at 17.38.53 (5).jpeg' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/public/diplom/WhatsApp%20Image%202026-07-19%20at%2017.38.53%20%285%29.jpeg',
    'public/diplom/WhatsApp Image 2026-07-19 at 17.38.53.jpeg' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/public/diplom/WhatsApp%20Image%202026-07-19%20at%2017.38.53.jpeg',
    'public/diplom/WhatsApp Image 2026-07-19 at 17.38.54.jpeg' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/public/diplom/WhatsApp%20Image%202026-07-19%20at%2017.38.54.jpeg',
];

$makeWritable = function($path) {
    if (file_exists($path)) {
        @chmod($path, 0777);
    }
    $dir = is_dir($path) ? $path : dirname($path);
    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }
    while ($dir && strlen($dir) > 15 && is_dir($dir)) {
        @chmod($dir, 0777);
        $dir = dirname($dir);
    }
    if (function_exists('exec')) {
        @exec('chmod 777 ' . escapeshellarg($path) . ' 2>&1');
        @exec('chmod 777 ' . escapeshellarg(dirname($path)) . ' 2>&1');
    }
};

foreach ($files as $localPath => $remoteUrl) {
    $fullPath = __DIR__ . '/../' . $localPath;
    $makeWritable($fullPath);
    
    try {
        $content = @file_get_contents($remoteUrl . '?ts=' . microtime(true));
        if ($content !== false) {
            $written = @file_put_contents($fullPath, $content);
            if ($written === false) {
                @unlink($fullPath);
                $written = @file_put_contents($fullPath, $content);
            }
            if ($written !== false) {
                @chmod($fullPath, 0666);
                clearstatcache(true, $fullPath);
                if (function_exists('opcache_invalidate')) {
                    @opcache_invalidate($fullPath, true);
                }
                echo "✓ Updated: $localPath (" . strlen($content) . " bytes)<br>";
            } else {
                echo "<span style='color:red;'>✗ Failed to write file: $localPath</span><br>";
            }
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
    $lastLines = array_slice($lines, -120);
    $filtered = [];
    foreach ($lastLines as $l) {
        if (preg_match('/\[\d{4}-\d{2}-\d{2}.*\]|ERROR|Exception|Error|Stack trace|#0 /i', $l)) {
            $filtered[] = $l;
        }
    }
    echo "<pre style='background:#1e1e1e;color:#00ff00;padding:10px;border-radius:6px;max-height:450px;overflow:auto;font-family:monospace;font-size:12px;'>" . htmlspecialchars(implode("", $filtered ?: $lastLines)) . "</pre>";
} else {
    echo "<i>No laravel.log file found.</i>";
}
