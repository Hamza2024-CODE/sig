<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Start Laravel session manually for standalone script
try {
    $request = request();
    $request->setLaravelSession($app['session']->driver());
    $request->session()->start();
} catch (\Throwable $e) {
    echo "Warning starting session: " . $e->getMessage() . "<br>";
}

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

echo "<h1>Apprenants Query Debugging Page</h1>";

// 1. Session check
$user   = session('user') ?? [];
echo "<h3>User Session Data:</h3>";
echo "<pre>" . print_r($user, true) . "</pre>";

$role   = strtolower($user['role_code'] ?? '');
$etabId = (int)($user['etablissement_id'] ?? $user['IDEts_Form'] ?? 0);
$dfepId = (int)($user['iddfep'] ?? $user['IDDFEP'] ?? 0);

// Emulate request parameters
$request = request();
$search = trim($request->query('search', ''));
$filterSection = (int)$request->query('filter_section', 0);
$filterStatus = $request->has('filter_status') ? trim($request->query('filter_status', '')) : 'actif';
$filterEtab = (int)$request->query('filter_etab', 0);

echo "<h3>Filters:</h3>";
echo "Search: '$search'<br>";
echo "Filter Section: $filterSection<br>";
echo "Filter Status: '$filterStatus'<br>";
echo "Filter Etab: $filterEtab<br>";

// Build query conditions
$where  = [];
$params = [];

if (in_array($role, ['admin', 'ministre', 'secretaire_general', 'central', 'high_admin'])) {
    echo "Role: Admin/Central (No role restrictions)<br>";
} elseif ($role === 'dfep' && $dfepId > 0) {
    echo "Role: DFEP (Restricted to IDDFEP = $dfepId)<br>";
    $where[]  = 'et.IDDFEP = ?';
    $params[] = $dfepId;
} elseif ($etabId > 0) {
    echo "Role: Etablissement (Restricted to IDEts_Form = $etabId)<br>";
    $where[]  = 'o.IDEts_Form = ?';
    $params[] = $etabId;
} else {
    echo "Role: Unknown / No ID (Restricted to 1=0)<br>";
    $where[] = '1=0';
}

if ((int)($user['IDMode_formation'] ?? 0) === 10) {
    $where[] = 'o.IDMode_formation = 10';
} elseif (strtolower($user['username'] ?? '') === 'sdtpp') {
    $where[] = 'o.IDMode_formation != 10';
}

if ($search !== '') {
    $where[]  = "(a.Nccp LIKE ? OR c.Nom LIKE ? OR c.Prenom LIKE ? OR c.NomFr LIKE ? OR c.PrenomFr LIKE ?)";
    $like     = "%{$search}%";
    $params   = array_merge($params, [$like, $like, $like, $like, $like]);
}

if ($filterSection > 0) {
    $where[]  = 'a.IDSection = ?';
    $params[] = $filterSection;
}

if ($filterStatus === 'actif') {
    $where[] = 'a.statut = ?';
    $params[] = 'actif';
    $where[] = 'NOT EXISTS (SELECT 1 FROM apprenant_fin af WHERE af.IDapprenant = a.IDapprenant)';
    $where[] = 'DATE_ADD(sess.DateD, INTERVAL COALESCE(NULLIF(sp.dureeM, 0), sp.NbrSem * 6, 24) MONTH) >= CURRENT_DATE()';
} elseif ($filterStatus !== '' && $filterStatus !== 'all') {
    $where[]  = 'a.statut = ?';
    $params[] = $filterStatus;
}

if ($filterEtab > 0 && $etabId === 0) {
    $where[]  = 'o.IDEts_Form = ?';
    $params[] = $filterEtab;
}

$whereSQL = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
echo "<h3>Generated WHERE Clause:</h3>";
echo "<pre>" . htmlspecialchars($whereSQL) . "</pre>";
echo "<h3>Bind Parameters:</h3>";
echo "<pre>" . print_r($params, true) . "</pre>";

// Test Total Count Query
$joins = "";
if (strpos($whereSQL, 'c.') !== false) {
    $joins .= " INNER JOIN candidat c ON a.IDCandidat = c.IDCandidat";
}
if (strpos($whereSQL, 's.') !== false || strpos($whereSQL, 'o.') !== false || strpos($whereSQL, 'et.') !== false || strpos($whereSQL, 'sess.') !== false || strpos($whereSQL, 'sp.') !== false) {
    $joins .= " LEFT JOIN section s ON a.IDSection = s.IDSection";
}
if (strpos($whereSQL, 'o.') !== false || strpos($whereSQL, 'et.') !== false || strpos($whereSQL, 'sess.') !== false || strpos($whereSQL, 'sp.') !== false) {
    $joins .= " LEFT JOIN offre o ON s.IDOffre = o.IDOffre";
}
if (strpos($whereSQL, 'et.') !== false) {
    $joins .= " LEFT JOIN etablissement et ON o.IDEts_Form = et.IDetablissement";
}
if (strpos($whereSQL, 'sess.') !== false) {
    $joins .= " LEFT JOIN session sess ON o.IDSession = sess.IDSession";
}
if (strpos($whereSQL, 'sp.') !== false) {
    $joins .= " LEFT JOIN specialite sp ON o.IDSpecialite = sp.IDSpecialite";
}

$countSql = "SELECT COUNT(*) as c FROM apprenant a {$joins} {$whereSQL}";
echo "<h3>Count SQL Query:</h3>";
echo "<pre>" . htmlspecialchars($countSql) . "</pre>";

try {
    $res = DB::selectOne($countSql, $params);
    echo "<h2>✓ Count Query Success! Result: " . ($res->c ?? 0) . "</h2>";
} catch (\Throwable $e) {
    echo "<h2 style='color:red;'>✗ Count Query Failed! Error:</h2>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
}

// Test Main List Query
$listSql = "SELECT a.IDapprenant as id,
                    c.NumIns as numero_matricule,
                    a.statut,
                    a.Valide as valide,
                    a.Groupe as groupe,
                    a.IDSection as section_id,
                    c.Nom as nom_ar,
                    c.Prenom as prenom_ar,
                    c.NomFr as nom_fr,
                    c.PrenomFr as prenom_fr,
                    c.Civ as civ,
                    s.Nom as section_nom,
                    sp.Nom as spec_ar,
                    et.Nom as etab_ar,
                    sess.Nom as session_nom,
                    c.dateInscr as date_inscription
             FROM apprenant a
             INNER JOIN candidat c ON a.IDCandidat = c.IDCandidat
             LEFT JOIN section s   ON a.IDSection = s.IDSection
             LEFT JOIN offre o     ON s.IDOffre = o.IDOffre
             LEFT JOIN specialite sp ON o.IDSpecialite = sp.IDSpecialite
             LEFT JOIN etablissement et ON o.IDEts_Form = et.IDetablissement
             LEFT JOIN session sess ON o.IDSession = sess.IDSession
             {$whereSQL}
             ORDER BY a.IDapprenant DESC
             LIMIT 10";

echo "<h3>Main List SQL Query:</h3>";
echo "<pre>" . htmlspecialchars($listSql) . "</pre>";

try {
    $rows = DB::select($listSql, $params);
    echo "<h2>✓ Main List Query Success! Returned Rows: " . count($rows) . "</h2>";
    if (count($rows) > 0) {
        echo "<pre>" . print_r(array_slice($rows, 0, 2), true) . "</pre>";
    }
} catch (\Throwable $e) {
    echo "<h2 style='color:red;'>✗ Main List Query Failed! Error:</h2>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
}
