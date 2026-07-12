<?php
header('Content-Type: text/plain; charset=utf-8');

define('LARAVEL_START', microtime(true));
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== TESTING DIGITAL CARDS TRAINEE QUERIES ===\n\n";

$limit = 10;
$offset = 0;

echo "1. Testing the UNFILTERED query (used by Admin):\n";
try {
    $selectSql = "
        SELECT a.IDapprenant as id, a.Nccp as numero_matricule,
               c.Nom as nom, c.Prenom as prenom, 
               c.NomFr as nom_fr, c.PrenomFr as prenom_fr, c.nin, c.nss,
               sp.Nom as spec_ar, e.Nom AS etab_nom
        FROM apprenant a
        JOIN candidat c ON a.IDCandidat = c.IDCandidat
        LEFT JOIN section s ON a.IDSection = s.IDSection
        LEFT JOIN offre o_cand ON c.IDOffre = o_cand.IDOffre
        LEFT JOIN specialite sp ON sp.IDSpecialite = COALESCE(NULLIF(s.IDSpecialite, 0), NULLIF(o_cand.IDSpecialite, 0))
        LEFT JOIN etablissement e ON e.IDetablissement = COALESCE(NULLIF(s.IDEts_Form, 0), NULLIF(o_cand.IDEts_Form, 0))
        ORDER BY a.IDapprenant DESC
        LIMIT $limit OFFSET $offset
    ";
    $records = DB::select($selectSql);
    echo "✓ Success! Fetched " . count($records) . " records.\n";
} catch (\Exception $e) {
    echo "❌ Error in Unfiltered Query: " . $e->getMessage() . "\n";
}

echo "\n2. Testing the FILTERED query (used by DFEP/Etablissement with dummy parameters):\n";
try {
    $whereClause1 = "w.IDWilayaa = 14";
    $whereClause2 = "s.IDSection IS NULL AND w.IDWilayaa = 14";
    
    $selectSql = "
        SELECT id, numero_matricule, nom, prenom, nom_fr, prenom_fr, nin, nss, spec_ar, etab_nom
        FROM (
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
            WHERE $whereClause1
            
            UNION ALL
            
            SELECT a.IDapprenant as id, a.Nccp as numero_matricule,
                   c.Nom as nom, c.Prenom as prenom, 
                   c.NomFr as nom_fr, c.PrenomFr as prenom_fr, c.nin, c.nss,
                   sp.Nom as spec_ar, e.Nom AS etab_nom
            FROM apprenant a
            JOIN candidat c ON a.IDCandidat = c.IDCandidat
            LEFT JOIN section s ON a.IDSection = s.IDSection
            JOIN offre o_cand ON c.IDOffre = o_cand.IDOffre
            LEFT JOIN specialite sp ON o_cand.IDSpecialite = sp.IDSpecialite
            LEFT JOIN etablissement e ON o_cand.IDEts_Form = e.IDetablissement
            LEFT JOIN wilaya w ON w.IDWilayaa = e.IDDFEP
            WHERE $whereClause2
        ) tmp
        ORDER BY id DESC
        LIMIT $limit OFFSET $offset
    ";
    $records = DB::select($selectSql);
    echo "✓ Success! Fetched " . count($records) . " records.\n";
} catch (\Exception $e) {
    echo "❌ Error in Filtered Query: " . $e->getMessage() . "\n";
}
