<?php
header('Content-Type: text/plain; charset=utf-8');

// Enable error reporting to catch direct compile or runtime errors
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

try {
    define('LARAVEL_START', microtime(true));
    require __DIR__.'/../vendor/autoload.php';
    $app = require_once __DIR__.'/../bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
} catch (\Throwable $e) {
    echo "❌ Laravel Bootstrap Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " on line " . $e->getLine() . "\n";
    echo "Trace: \n" . $e->getTraceAsString() . "\n";
    exit;
}

use Illuminate\Support\Facades\DB;

echo "=== SIMULATING ADMIN VISIT TO DIGITAL CARDS ===\n\n";

try {
    // Get Admin User row
    $adminUser = DB::table('utilisateur')->where('role_id', 1)->first();
    if (!$adminUser) {
        // Try role_code or fallback
        $adminUser = DB::table('utilisateur')->where('role_code', 'admin')->first() 
                  ?? DB::table('utilisateur')->first();
    }
    
    if (!$adminUser) {
        echo "No Admin user found in the database.\n";
        exit;
    }

    $adminUser = (array)$adminUser;
    $adminUser['role_code'] = $adminUser['role_code'] ?? 'admin'; // ensure role_code is admin

    echo "Simulated User: " . ($adminUser['username'] ?? 'unknown') . " | Role: " . ($adminUser['role_code'] ?? 'admin') . "\n\n";

    // Set session
    session(['user' => $adminUser]);
} catch (\Throwable $ex) {
    echo "❌ Error in user initialization: " . $ex->getMessage() . "\n";
    echo "Stack trace: \n" . $ex->getTraceAsString() . "\n\n";
    exit;
}

// Run the logic from EspaceEmployeController::digitalCards() for 'trainee'
$scope = [
    'role' => 'admin',
    'iddfep' => 0,
    'etabId' => 0,
    'user' => $adminUser
];
$type = 'trainee';
$search = null;
$wilaya = null;
$etab = null;
$mode = null;
$branche = null;
$page = 1;
$limit = 10;
$offset = 0;

$clauses1 = ['1=1'];
$clauses2 = ['s.IDSection IS NULL'];
$params1 = [];
$params2 = [];

$whereClause1 = implode(' AND ', $clauses1);
$whereClause2 = implode(' AND ', $clauses2);
$allParams = array_merge($params1, $params2);

$isUnfiltered = ($whereClause1 === '1=1' && $whereClause2 === 's.IDSection IS NULL');

echo "isUnfiltered: " . ($isUnfiltered ? "TRUE" : "FALSE") . "\n";
echo "whereClause1: $whereClause1\n";
echo "whereClause2: $whereClause2\n\n";

// 1. Test Fetch Total Count
$totalCount = 0;
try {
    $db = new \App\Core\LaravelDbAdapter();
    if ($isUnfiltered) {
        $countSql = "SELECT COUNT(*) FROM apprenant";
    } else {
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
    }
    
    $stmtCount = $db->prepare($countSql);
    $stmtCount->execute();
    $totalCount = (int)$stmtCount->fetchColumn();
    echo "✓ Total Count: $totalCount\n";
} catch (\Exception $e) {
    echo "❌ Error in Total Count: " . $e->getMessage() . "\n";
}

// 2. Test Fetch Trainees list
$records = [];
try {
    if ($isUnfiltered) {
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
            LIMIT ? OFFSET ?
        ";
        $stmtSelect = $db->prepare($selectSql);
        $stmtSelect->bindValue(1, $limit, \PDO::PARAM_INT);
        $stmtSelect->bindValue(2, $offset, \PDO::PARAM_INT);
        $stmtSelect->execute();
    } else {
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
        $i = 1;
        foreach ($allParams as $val) {
            $stmtSelect->bindValue($i++, $val);
        }
        $stmtSelect->bindValue($i++, $limit, \PDO::PARAM_INT);
        $stmtSelect->bindValue($i, $offset, \PDO::PARAM_INT);
        $stmtSelect->execute();
    }
    $records = $stmtSelect->fetchAll(\PDO::FETCH_ASSOC);
    echo "✓ Trainees list: Loaded " . count($records) . " records.\n";
    if (count($records) > 0) {
        echo "First trainee: ID: {$records[0]['id']} | Name: {$records[0]['nom']} {$records[0]['prenom']}\n";
    }
} catch (\Exception $e) {
    echo "❌ Error loading trainees: " . $e->getMessage() . "\n";
}
