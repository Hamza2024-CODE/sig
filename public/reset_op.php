<?php
header('Content-Type: text/plain; charset=utf-8');

if (function_exists('opcache_reset')) {
    $res = opcache_reset();
    echo "✓ OPcache reset result: " . ($res ? "Success (تم بنجاح)" : "Failed (فشل)") . "\n";
} else {
    echo "✗ OPcache is not enabled or function opcache_reset is disabled on this server.\n";
}

// Also clear compiled Laravel views
$baseDir = realpath(__DIR__ . '/..');
$files = glob($baseDir . '/storage/framework/views/*.php');
foreach ($files as $file) {
    if (is_file($file)) {
        unlink($file);
    }
}
echo "✓ Laravel view cache cleared.\n";
