<?php
// Safety check
$controllerPath = __DIR__ . '/../app/Http/Controllers/Admin/DiplomeController.php';
if (file_exists($controllerPath)) {
    $content = file_get_contents($controllerPath);
    echo "<h1>File check:</h1>";
    echo "File size: " . strlen($content) . " bytes<br>";
    if (strpos($content, 'liste2021') !== false) {
        echo "✓ File contains 'liste2021' method<br>";
    } else {
        echo "✗ File DOES NOT contain 'liste2021' method<br>";
    }
    if (strpos($content, "'has_more'") !== false) {
        echo "✓ File contains 'has_more' key<br>";
    } else {
        echo "✗ File DOES NOT contain 'has_more' key<br>";
    }
} else {
    echo "✗ File not found at: $controllerPath<br>";
}

// Runtime check using reflection
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    $ref = new ReflectionClass(\App\Http\Controllers\Admin\DiplomeController::class);
    echo "<h1>Runtime check:</h1>";
    if ($ref->hasMethod('liste2021')) {
        echo "✓ Runtime class HAS method 'liste2021'<br>";
    } else {
        echo "✗ Runtime class DOES NOT have method 'liste2021'<br>";
    }
    
    // Check if OPCache is caching this file
    if (function_exists('opcache_get_status')) {
        $status = opcache_get_status();
        $realPath = realpath($controllerPath);
        if (isset($status['scripts'][$realPath])) {
            echo "✓ OPCache is caching this file: $realPath<br>";
            echo "Cached time: " . date('Y-m-d H:i:s', $status['scripts'][$realPath]['last_force_key_validate']) . "<br>";
        } else {
            echo "✗ OPCache is not caching this file.<br>";
        }
    }
} catch (\Exception $e) {
    echo "Error during runtime check: " . $e->getMessage() . "<br>";
}
