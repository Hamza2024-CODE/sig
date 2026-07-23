<?php
// SIG Auto-Update Deployment Script - Clean Build 2026-07-23
require __DIR__ . '/../vendor/autoload.php';
ini_set('user_agent', 'PHP');
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

try {
    $columns = DB::select("DESCRIBE utilisateur");
    $altered = false;
    foreach ($columns as $col) {
        if ($col->Field === 'NomUser' && strpos($col->Type, 'varchar(50)') !== false) {
            DB::statement("ALTER TABLE utilisateur MODIFY COLUMN NomUser VARCHAR(255)");
            $altered = true;
            break;
        }
    }
    if ($altered) {
        echo "✓ Database migration: NomUser column size increased to VARCHAR(255) successfully!<br>";
    }
} catch (\Throwable $e) {
    echo "⚠️ Database migration check failed: " . $e->getMessage() . "<br>";
}

echo "<h1>Auto-Updating All Modified Files...</h1>";

$files = [
    'public/pull.php' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/public/pull.php',
    'public/check_ansim_1316_v1.php' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/4ba1420a0b3904d945a29918af0df9161bd0ee2d/public/check_ansim_1316_v1.php'
];

$filesToDownload = $files;
if (isset($_GET['file']) && isset($_GET['commit'])) {
    $fileParam = trim($_GET['file']);
    $commitParam = trim($_GET['commit']);
    if (strpos($fileParam, '..') === false) {
        $filesToDownload = [
            $fileParam => "https://raw.githubusercontent.com/Hamza2024-CODE/sig/{$commitParam}/{$fileParam}"
        ];
    }
}

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

$downloadUrl = function($url) {
    $content = @file_get_contents($url);
    if ($content !== false) {
        return $content;
    }
    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        $res = curl_exec($ch);
        curl_close($ch);
        if ($res !== false) {
            return $res;
        }
    }
    return false;
};

foreach ($filesToDownload as $localPath => $remoteUrl) {
    $fullPath = __DIR__ . '/../' . $localPath;
    $makeWritable($fullPath);
    
    try {
        $content = $downloadUrl($remoteUrl . '?ts=' . microtime(true));
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

echo "<h2>Clearing OPcache...</h2>";
if (function_exists('opcache_reset')) {
    @opcache_reset();
    echo "✓ OPCache Cleared!<br>";
}
echo "<h3 style='color:green;'>All updates completed successfully!</h3>";

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
