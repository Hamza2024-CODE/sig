<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "<h1>Apprenants Query Emulation & Debugging</h1>";

// 1. Fetch an admin user and a dfep user from DB to emulate sessions
try {
    // Find admin user
    $adminUser = DB::table('utilisateur')
        ->where('role_code', 'admin')
        ->first();
    
    // Find dfep user
    $dfepUser = DB::table('utilisateur')
        ->where('role_code', 'dfep')
        ->first();

    $testUsers = [];
    if ($adminUser) {
        $testUsers['Admin (' . $adminUser->username . ')'] = (array)$adminUser;
    }
    if ($dfepUser) {
        $testUsers['DFEP (' . $dfepUser->username . ')'] = (array)$dfepUser;
    }

    foreach ($testUsers as $label => $userObj) {
        echo "<hr><h2>Testing Role: $label</h2>";
        
        $role   = strtolower($userObj['role_code'] ?? '');
        $etabId = (int)($userObj['etablissement_id'] ?? $userObj['IDEts_Form'] ?? 0);
        $dfepId = (int)($userObj['iddfep'] ?? $userObj['IDDFEP'] ?? 0);
        
        echo "<b>Parsed User:</b> Role=$role, EtabID=$etabId, DfepID=$dfepId<br>";

        // Emulate request parameters (test with no establishment filter first)
        $filterEtab = 0;
        $filterStatus = 'actif';
        $filterSection = 0;
        $search = '';

        // Build query conditions
        $where  = [];
        $params = [];

        if (in_array($role, ['admin', 'ministre', 'secretaire_general', 'central', 'high_admin'])) {
            // no restriction
        } elseif ($role === 'dfep' && $dfepId > 0) {
            $where[]  = 'et.IDDFEP = ?';
            $params[] = $dfepId;
        } elseif ($etabId > 0) {
            $where[]  = 'o.IDEts_Form = ?';
            $params[] = $etabId;
        } else {
            $where[] = '1=0';
        }

        if ((int)($userObj['IDMode_formation'] ?? 0) === 10) {
            $where[] = 'o.IDMode_formation = 10';
        }

        if ($filterStatus === 'actif') {
            $where[] = 'a.statut = ?';
            $params[] = 'actif';
            $where[] = 'NOT EXISTS (SELECT 1 FROM apprenant_fin af WHERE af.IDapprenant = a.IDapprenant)';
            $where[] = 'DATE_ADD(sess.DateD, INTERVAL COALESCE(NULLIF(sp.dureeM, 0), sp.NbrSem * 6, 24) MONTH) >= CURRENT_DATE()';
        }

        $whereSQL = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        echo "<b>WHERE Clause:</b> " . htmlspecialchars($whereSQL) . "<br>";

        // Total Count Query Joins
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

        try {
            $res = DB::selectOne($countSql, $params);
            echo "<h3 style='color:green;'>✓ Count result: " . ($res->c ?? 0) . "</h3>";
        } catch (\Throwable $e) {
            echo "<h3 style='color:red;'>✗ Count query failed!</h3>";
            echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
        }
    }

} catch (\Exception $e) {
    echo "Error querying users: " . $e->getMessage();
}
