<?php
define('LARAVEL_START', microtime(true));
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

header('Content-Type: application/json; charset=utf-8');

try {
    $private_etabs = DB::select("
        SELECT IDetablissement, IDEts_Form, Nom, PublPrive 
        FROM etablissement 
        WHERE PublPrive = 1 OR Nom LIKE '%خاص%' OR Nom LIKE '%مدرسة%'
        LIMIT 10
    ");

    $public_etabs = DB::select("
        SELECT IDetablissement, IDEts_Form, Nom, PublPrive 
        FROM etablissement 
        WHERE PublPrive = 0 OR Nom LIKE '%مركز%' OR Nom LIKE '%معهد%'
        LIMIT 10
    ");

    $taj = DB::select("
        SELECT IDetablissement, IDEts_Form, Nom, PublPrive 
        FROM etablissement 
        WHERE IDetablissement = 1301 OR Nom LIKE '%التاج%'
    ");

    echo json_encode([
        'status' => 'success',
        'taj' => $taj,
        'private' => $private_etabs,
        'public' => $public_etabs
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (\Throwable $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
