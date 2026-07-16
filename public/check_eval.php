<?php
define('LARAVEL_START', microtime(true));
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

header('Content-Type: text/plain; charset=utf-8');

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

try {
    // login as admin
    $admin = DB::table('utilisateur')->first();
    if ($admin) {
        $admin = (array)$admin;
        $admin['role_code'] = 'admin';
        session(['user' => $admin]);
    }

    $controller = new \App\Http\Controllers\Evaluation\EvaluationController();
    $res = $controller->gestionEvaluations();
    echo "SUCCESS: loaded successfully!\n";
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\nFile: " . $e->getFile() . "\nLine: " . $e->getLine() . "\n";
    echo $e->getTraceAsString();
}
