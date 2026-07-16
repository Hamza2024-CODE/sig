<?php
define('LARAVEL_START', microtime(true));
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

header('Content-Type: application/json; charset=utf-8');

try {
    // 1. Let's find all rows in utilisateur table where IDBureau is 1301 (Taj Al Azraq) or 1300
    $users_1301 = DB::select("SELECT * FROM utilisateur WHERE IDBureau = 1301");
    $users_1300 = DB::select("SELECT * FROM utilisateur WHERE IDBureau = 1300");

    // 2. Let's find all active users in the system that are general template sub-accounts (like @Pedago, SDTPA, etc.)
    $templates = DB::select("SELECT IDUtilisateur, NomUser, Nom, MotPass, IDBureau, IDNature FROM utilisateur WHERE IDBureau IS NULL OR IDBureau = 0 OR NomUser IN ('sdtpa', 'sdtpp', 'admfin', 'pedago', 'direts') LIMIT 20");

    echo json_encode([
        'users_1301' => $users_1301,
        'users_1300' => $users_1300,
        'templates' => $templates
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (\Throwable $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
