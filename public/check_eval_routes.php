<?php
define('LARAVEL_START', microtime(true));
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Route;

header('Content-Type: text/plain; charset=utf-8');

try {
    if (Route::has('evaluation.inspecteurs')) {
        echo "SUCCESS: Route 'evaluation.inspecteurs' exists!\n";
        echo "URL: " . route('evaluation.inspecteurs') . "\n";
    } else {
        echo "ERROR: Route 'evaluation.inspecteurs' does NOT exist!\n";
    }

    if (Route::has('evaluation.inspecteurs.details')) {
        echo "SUCCESS: Route 'evaluation.inspecteurs.details' exists!\n";
        echo "URL: " . route('evaluation.inspecteurs.details', ['name' => 'test']) . "\n";
    } else {
        echo "ERROR: Route 'evaluation.inspecteurs.details' does NOT exist!\n";
    }
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
