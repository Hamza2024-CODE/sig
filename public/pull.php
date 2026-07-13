<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Artisan;

echo "<h1>Auto-Updating All Modified Files...</h1>";

$files = [
    'app/Domains/Academic/Repositories/OffresRepository.php' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/app/Domains/Academic/Repositories/OffresRepository.php',
    'resources/views/admin/offres/index.blade.php' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/resources/views/admin/offres/index.blade.php',
    'app/Http/Controllers/Admin/PreinscritController.php' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/app/Http/Controllers/Admin/PreinscritController.php',
    'app/Http/Controllers/Admin/ModulesController.php' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/app/Http/Controllers/Admin/ModulesController.php',
    'app/Domains/Academic/Services/OffresService.php' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/app/Domains/Academic/Services/OffresService.php',
    'public/upload_photos.php' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/public/upload_photos.php',
    'public/check_ssh_port.php' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/public/check_ssh_port.php'
];

foreach ($files as $localPath => $remoteUrl) {
    $fullPath = __DIR__ . '/../' . $localPath;
    
    // Create directory if it doesn't exist
    $dir = dirname($fullPath);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    
    $content = file_get_contents($remoteUrl);
    if ($content !== false) {
        file_put_contents($fullPath, $content);
        echo "✓ Updated: $localPath (" . strlen($content) . " bytes)<br>";
    } else {
        echo "<span style='color:red;'>✗ Failed to download: $localPath</span><br>";
    }
}

echo "<h2>Clearing Laravel Cache and Views...</h2>";
try {
    Artisan::call('view:clear');
    echo "✓ View Cache Cleared: " . Artisan::output() . "<br>";
    
    Artisan::call('cache:clear');
    echo "✓ Application Cache Cleared: " . Artisan::output() . "<br>";
    
    Artisan::call('route:clear');
    echo "✓ Route Cache Cleared: " . Artisan::output() . "<br>";
    
    if (function_exists('opcache_reset')) {
        opcache_reset();
        echo "✓ OPCache Cleared!<br>";
    }
} catch (\Throwable $e) {
    echo "<span style='color:red;'>Error clearing cache: " . $e->getMessage() . "</span><br>";
}

echo "<br><h3 style='color:green;'>All updates completed successfully!</h3>";
