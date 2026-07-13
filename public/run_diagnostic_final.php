<?php
header('Content-Type: text/plain; charset=utf-8');

define('LARAVEL_START', microtime(true));
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== DIAGNOSING DIGITAL CARDS QUERY (NEW FILE) ===\n\n";

try {
    $db = new \App\Core\LaravelDbAdapter();
    
    $limit = 10;
    $offset = 0;
    
    $whereClause1 = "1=1 AND s.DateDF >= '2024-02-01'";
    $whereClause2 = "s.IDSection IS NULL AND (o_cand.Session_rentree LIKE '2024%' OR o_cand.Session_rentree LIKE '2025%' OR o_cand.Session_rentree LIKE '2026%')";

    echo "Running Count Query...\n";
    $countSql = "
        SELECT SUM(cnt) FROM (
            SELECT COUNT(*) as cnt
            FROM apprenant a
            JOIN candidat c ON a.IDCandidat = c.IDCandidat
            JOIN section s ON a.IDSection = s.IDSection
            LEFT JOIN specialite sp ON s.IDSpecialite = sp.IDSpecialite
            LEFT JOIN etablissement e ON s.IDEts_Form = e.IDetablissement
            LEFT JOIN wilaya w ON w.IDWilayaa = e.IDDFEP
            WHERE $whereClause1
            
            UNION ALL
            
            SELECT COUNT(*) as cnt
            FROM apprenant a
            JOIN candidat c ON a.IDCandidat = c.IDCandidat
            LEFT JOIN section s ON a.IDSection = s.IDSection
            JOIN offre o_cand ON c.IDOffre = o_cand.IDOffre
            LEFT JOIN specialite sp ON o_cand.IDSpecialite = sp.IDSpecialite
            LEFT JOIN etablissement e ON o_cand.IDEts_Form = e.IDetablissement
            LEFT JOIN wilaya w ON w.IDWilayaa = e.IDDFEP
            WHERE $whereClause2
        ) tmp
    ";
    
    $stmtCount = $db->prepare($countSql);
    $stmtCount->execute();
    $totalCount = (int)$stmtCount->fetchColumn();
    echo "✓ Total Count: $totalCount\n\n";

    echo "Running Select Query...\n";
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
        LIMIT ? OFFSET ?
    ";
    
    $stmtSelect = $db->prepare($selectSql);
    $stmtSelect->bindValue(1, $limit, \PDO::PARAM_INT);
    $stmtSelect->bindValue(2, $offset, \PDO::PARAM_INT);
    $stmtSelect->execute();
    $records = $stmtSelect->fetchAll(\PDO::FETCH_ASSOC);
    
    echo "✓ Select Query Success! Loaded " . count($records) . " records.\n";
    if (count($records) > 0) {
        echo "Sample Record:\n";
        print_r($records[0]);
    }
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
