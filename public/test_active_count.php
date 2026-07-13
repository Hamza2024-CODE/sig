<?php
header('Content-Type: text/plain; charset=utf-8');

define('LARAVEL_START', microtime(true));
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    $db = new \App\Core\LaravelDbAdapter();
    
    // Test 1: Without FORCE INDEX
    $start = microtime(true);
    $selectSql1 = "
        SELECT a.IDapprenant as id, a.Nccp as numero_matricule,
               c.Nom as nom, c.Prenom as prenom, 
               c.NomFr as nom_fr, c.PrenomFr as prenom_fr, c.nin, c.nss,
               sp.Nom as spec_ar, e.Nom AS etab_nom
        FROM apprenant a
        JOIN candidat c ON a.IDCandidat = c.IDCandidat
        JOIN section s ON a.IDSection = s.IDSection
        LEFT JOIN specialite sp ON s.IDSpecialite = sp.IDSpecialite
        LEFT JOIN etablissement e ON s.IDEts_Form = e.IDetablissement
        LEFT JOIN wilaya w ON w.IDWilayaa = e.IDDFEP
        WHERE a.IDSection != 0 AND s.IDSession IN (31, 32, 33, 34, 35)
        ORDER BY a.IDapprenant DESC
        LIMIT 10 OFFSET 0
    ";
    $stmt1 = $db->prepare($selectSql1);
    $stmt1->execute();
    $records1 = $stmt1->fetchAll(\PDO::FETCH_ASSOC);
    $elapsed1 = microtime(true) - $start;
    echo "SELECT without FORCE INDEX took: " . round($elapsed1, 4) . " seconds (Loaded " . count($records1) . " rows)\n";

    // Test 2: With FORCE INDEX (PRIMARY)
    $start = microtime(true);
    $selectSql2 = "
        SELECT a.IDapprenant as id, a.Nccp as numero_matricule,
               c.Nom as nom, c.Prenom as prenom, 
               c.NomFr as nom_fr, c.PrenomFr as prenom_fr, c.nin, c.nss,
               sp.Nom as spec_ar, e.Nom AS etab_nom
        FROM apprenant a FORCE INDEX (PRIMARY)
        JOIN candidat c ON a.IDCandidat = c.IDCandidat
        JOIN section s ON a.IDSection = s.IDSection
        LEFT JOIN specialite sp ON s.IDSpecialite = sp.IDSpecialite
        LEFT JOIN etablissement e ON s.IDEts_Form = e.IDetablissement
        LEFT JOIN wilaya w ON w.IDWilayaa = e.IDDFEP
        WHERE a.IDSection != 0 AND s.IDSession IN (31, 32, 33, 34, 35)
        ORDER BY a.IDapprenant DESC
        LIMIT 10 OFFSET 0
    ";
    $stmt2 = $db->prepare($selectSql2);
    $stmt2->execute();
    $records2 = $stmt2->fetchAll(\PDO::FETCH_ASSOC);
    $elapsed2 = microtime(true) - $start;
    echo "SELECT with FORCE INDEX (PRIMARY) took: " . round($elapsed2, 4) . " seconds (Loaded " . count($records2) . " rows)\n";
    
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
