<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

header('Content-Type: text/plain');

$filePath = __DIR__ . '/../app/Http/Controllers/Auth/LoginController.php';
echo "File exists check: " . (file_exists($filePath) ? "YES" : "NO") . "\n";
if (file_exists($filePath)) {
    echo "File size: " . filesize($filePath) . " bytes\n";
    echo "MD5 hash: " . md5_file($filePath) . "\n";
}

try {
    echo "Attempting to reflect class App\Http\Controllers\Auth\LoginController...\n";
    $ref = new ReflectionClass('App\Http\Controllers\Auth\LoginController');
    echo "SUCCESS: Class exists and is loadable!\n";
    echo "Filename: " . $ref->getFileName() . "\n";
} catch (\Throwable $e) {
    echo "ERROR LOADING CLASS: " . $e->getMessage() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}
