<?php
header('Content-Type: text/plain; charset=utf-8');

define('LARAVEL_START', microtime(true));
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== TESTING getTrainee QUERY SPEED ===\n\n";

try {
    // Get a sample IDapprenant
    $sample = DB::table('apprenant')->where('IDSection', '!=', 0)->first();
    if (!$sample) {
        echo "No trainees found.\n";
        exit;
    }
    $id = $sample->IDapprenant;
    echo "Sample Trainee ID: $id\n\n";

    $start = microtime(true);
    $sql = "
        SELECT a.IDapprenant as id, a.Nccp as numero_matricule, 
               c.Nom as nom_ar, c.Prenom as prenom_ar, c.NomFr as nom_fr, c.PrenomFr as prenom_fr,
               c.nin, c.nss, c.photo, c.Civ, c.DateNais, c.LieuNais,
               sp.Nom as spec_ar, e.Nom as etab_nom, w.Nom as wilaya_nom, w.IDWilayaa as id_wilaya,
               mf.Nom as mode_nom, s.DateDF as date_deb, s.DateFF as date_fin,
               ar.Nom as regime_nom
        FROM apprenant a
        JOIN candidat c ON a.IDCandidat = c.IDCandidat
        LEFT JOIN section s ON a.IDSection = s.IDSection
        LEFT JOIN offre o ON o.IDOffre = COALESCE(NULLIF(s.IDOffre, 0), NULLIF(c.IDOffre, 0))
        LEFT JOIN specialite sp ON sp.IDSpecialite = COALESCE(NULLIF(s.IDSpecialite, 0), NULLIF(o.IDSpecialite, 0))
        LEFT JOIN etablissement e ON e.IDetablissement = COALESCE(NULLIF(s.IDEts_Form, 0), NULLIF(o.IDEts_Form, 0))
        LEFT JOIN wilaya w ON e.IDDFEP = w.IDWilayaa
        LEFT JOIN mode_formation mf ON mf.IDMode_formation = COALESCE(NULLIF(s.IDMode_formation, 0), NULLIF(o.IDMode_formation, 0))
        LEFT JOIN apprenant_section_semstre ass ON a.IDapprenant = ass.IDapprenant
        LEFT JOIN apprenant_regime ar ON ass.IDapprenant_Regime = ar.IDapprenant_Regime
        WHERE a.IDapprenant = ?
        LIMIT 1
    ";
    $trainee = DB::selectOne($sql, [$id]);
    $elapsed = microtime(true) - $start;
    
    echo "Query took: " . round($elapsed, 4) . " seconds\n";
    if ($trainee) {
        print_r((array)$trainee);
    } else {
        echo "No trainee found with ID $id\n";
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
