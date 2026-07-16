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

$roles = [
    'admin' => [
        'IDUtilisateur' => 1,
        'Code' => 'admin',
        'Nom' => 'Admin',
        'admin' => 1,
        'activee' => 1,
        'role_code' => 'admin'
    ],
    'dfep' => [
        'IDUtilisateur' => 2,
        'Code' => 'dfep31',
        'Nom' => 'DFEP Oran',
        'admin' => 0,
        'activee' => 1,
        'role_code' => 'dfep',
        'iddfep' => 31,
        'IDDFEP' => 31
    ],
    'etablissement' => [
        'IDUtilisateur' => 3,
        'Code' => 'etab_test',
        'Nom' => 'Etab Test',
        'admin' => 0,
        'activee' => 1,
        'role_code' => 'etablissement',
        'etablissement_id' => 90022
    ]
];

foreach ($roles as $roleName => $sessionUser) {
    echo "--- Testing Role: $roleName ---\n";
    try {
        session(['user' => $sessionUser]);
        $controller = app(AbsencesController::class);
        $response = $controller->add();
        echo "SUCCESS for $roleName!\n";
    } catch (\Throwable $e) {
        echo "ERROR for $roleName: " . $e->getMessage() . "\n";
        echo "STACK TRACE:\n" . $e->getTraceAsString() . "\n\n";
    }
}
