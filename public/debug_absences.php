<?php
define('LARAVEL_START', microtime(true));
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use App\Http\Controllers\Admin\AbsencesController;

try {
    // Mock user session
    session(['user' => [
        'IDUtilisateur' => 1,
        'Code' => 'admin',
        'Nom' => 'Admin',
        'admin' => 1,
        'activee' => 1
    ]]);

    $controller = app(AbsencesController::class);
    $response = $controller->add();
    echo "SUCCESS!\n";
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "STACK TRACE:\n" . $e->getTraceAsString() . "\n";
}
