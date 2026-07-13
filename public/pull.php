<?php
// Auto-updater script
$sourceUrl = 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/public/debug_apprenants.php';
$destPath = __DIR__ . '/debug_apprenants.php';

try {
    $content = file_get_contents($sourceUrl);
    if ($content === false) {
        throw new \Exception("Failed to download from GitHub.");
    }
    
    file_put_contents($destPath, $content);
    echo "<h1>✓ debug_apprenants.php updated successfully!</h1>";
    
    // Clear OPCache
    if (function_exists('opcache_reset')) {
        opcache_reset();
        echo "OPCache Cleared!<br>";
    }
    
    echo "<script>setTimeout(function() { window.location.href = 'debug_apprenants.php'; }, 1500);</script>";
    echo "Redirecting to debug page in 1.5 seconds...";
} catch (\Exception $e) {
    echo "<h1>Error: " . $e->getMessage() . "</h1>";
}
