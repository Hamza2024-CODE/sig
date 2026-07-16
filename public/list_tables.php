<?php
define('LARAVEL_START', microtime(true));
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

header('Content-Type: text/plain; charset=utf-8');

try {
    echo "--- Search users matching 1300 or taj or crown ---\n";
    $users = DB::select("SELECT * FROM utilisateur WHERE NomUser LIKE '%1300%' OR NomUser LIKE '%taj%' OR NomUser LIKE '%crown%' OR Nom LIKE '%تاج%' OR Nom LIKE '%التاج%'");
    foreach ($users as $u) {
        echo "IDUtilisateur: {$u->IDUtilisateur} | NomUser: {$u->NomUser} | Nom: {$u->Nom} | IDBureau: {$u->IDBureau} | IDdirection: {$u->IDdirection} | IDNature: {$u->IDNature} | activee: {$u->activee}\n";
    }

    echo "\n--- Search etablissement matching تاج or taj or crown or 1300 ---\n";
    $etabs = DB::select("SELECT * FROM etablissement WHERE Nom LIKE '%تاج%' OR Nom LIKE '%التاج%' OR NomFr LIKE '%taj%' OR NomFr LIKE '%crown%' OR IDetablissement = 1300 OR IDetablissement LIKE '%1300%'");
    foreach ($etabs as $e) {
        echo "IDetablissement: {$e->IDetablissement} | Nom: {$e->Nom} | NomFr: {$e->NomFr} | IDDFEP: {$e->IDDFEP}\n";
    }

    // Let's run a check on one of the etablissement IDs found
    if (count($etabs) > 0) {
        foreach ($etabs as $e) {
            $etabId = $e->IDetablissement;
            $cnt = DB::select("
                SELECT COUNT(*) as cnt 
                FROM apprenant a
                LEFT JOIN candidat c ON a.IDCandidat = c.IDCandidat
                LEFT JOIN offre o ON c.IDOffre = o.IDOffre
                WHERE o.IDEts_Form = ? AND a.statut = 'actif'
            ", [$etabId]);
            echo "Etab ID {$etabId} ({$e->Nom}) has active trainees: {$cnt[0]->cnt}\n";

            // Also check via section IDEts_Form
            $cntSec = DB::select("
                SELECT COUNT(*) as cnt 
                FROM apprenant a
                LEFT JOIN section s ON a.IDSection = s.IDSection
                WHERE s.IDEts_Form = ? AND a.statut = 'actif'
            ", [$etabId]);
            echo "Etab ID {$etabId} ({$e->Nom}) has active trainees via section: {$cntSec[0]->cnt}\n";
        }
    }
} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
