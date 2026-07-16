<?php
define('LARAVEL_START', microtime(true));
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

header('Content-Type: text/plain; charset=utf-8');

try {
    echo "--- Querying utilisateur for rUQ1300 / jyc:@1300 ---\n";
    $users = DB::select("SELECT * FROM utilisateur WHERE NomUser = 'rUQ1300' OR NomUser = 'jyc:@1300' OR Nom LIKE '%التاج%'");
    foreach ($users as $u) {
        echo "IDUtilisateur: {$u->IDUtilisateur} | NomUser: {$u->NomUser} | Nom: {$u->Nom} | IDBureau: {$u->IDBureau} | IDdirection: {$u->IDdirection} | IDNature: {$u->IDNature} | activee: {$u->activee} | admin: {$u->admin}\n";
    }

    if (count($users) > 0) {
        $etabId = $users[0]->IDBureau;
        echo "\n--- Etablissement Info for IDBureau = $etabId ---\n";
        $etab = DB::select("SELECT * FROM etablissement WHERE IDetablissement = ?", [$etabId]);
        foreach ($etab as $e) {
            echo "IDetablissement: {$e->IDetablissement} | Nom: {$e->Nom} | NomFr: {$e->NomFr} | IDDFEP: {$e->IDDFEP}\n";
        }

        echo "\n--- Trainees under this Etablissement (IDetablissement = $etabId) ---\n";
        // Let's count active trainees
        $cntDirect = DB::select("
            SELECT COUNT(*) as cnt 
            FROM apprenant a
            LEFT JOIN candidat c ON a.IDCandidat = c.IDCandidat
            LEFT JOIN offre o ON c.IDOffre = o.IDOffre
            WHERE o.IDEts_Form = ? AND a.statut = 'actif'
        ", [$etabId]);
        echo "Direct trainees count (via candidat.IDOffre): {$cntDirect[0]->cnt}\n";

        // Let's see some samples
        $samples = DB::select("
            SELECT a.IDapprenant, c.Nom, c.Prenom, o.IDEts_Form, e.Nom as etab_nom
            FROM apprenant a
            LEFT JOIN candidat c ON a.IDCandidat = c.IDCandidat
            LEFT JOIN offre o ON c.IDOffre = o.IDOffre
            LEFT JOIN etablissement e ON o.IDEts_Form = e.IDetablissement
            WHERE o.IDEts_Form = ? AND a.statut = 'actif'
            LIMIT 5
        ", [$etabId]);
        foreach ($samples as $s) {
            echo "Apprenant ID: {$s->IDapprenant} | Name: {$s->Nom} {$s->Prenom} | Etab ID in Offre: {$s->IDEts_Form} | Etab Name: {$s->etab_nom}\n";
        }
    }
} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
