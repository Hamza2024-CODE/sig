<?php
define('LARAVEL_START', microtime(true));
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

header('Content-Type: application/json; charset=utf-8');

try {
    // Get Etablissement along with nature_id
    $etab = DB::select("
        SELECT e.IDetablissement, e.Nom, n.IDNature as nature_id, n.Nom as nature_nom
        FROM etablissement e
        INNER JOIN nature_etsf n ON e.IDNature_etsF = n.IDNature_etsF
        WHERE e.IDetablissement = 1301
    ");

    if (count($etab) > 0) {
        $natureId = $etab[0]->nature_id;
        
        // Find matching utilisateur records for this nature_id
        $users = DB::select("
            SELECT IDUtilisateur, NomUser, Nom, MotPass, IDBureau, IDNature 
            FROM utilisateur 
            WHERE IDNature = ?
        ", [$natureId]);
        
        echo json_encode([
            'status' => 'success',
            'etab' => $etab[0],
            'users' => $users
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Etablissement 1301 not found or has no matching Nature_etsF'
        ]);
    }

} catch (\Throwable $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
