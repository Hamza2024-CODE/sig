<?php
define('LARAVEL_START', microtime(true));
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

header('Content-Type: text/plain; charset=utf-8');

try {
    // 1. Get active session
    $activeSession = DB::table('session')
        ->join('semestre_formation', 'session.IDSemestre_formation', '=', 'semestre_formation.IDSemestre_formation')
        ->orderBy('semestre_formation.IDAnnee_Formation', 'desc')
        ->orderBy('session.DateD', 'desc')
        ->select('session.IDSession', 'session.Nom', 'session.DateD')
        ->first();

    if ($activeSession) {
        echo "Active Session: ID: {$activeSession->IDSession} | Nom: {$activeSession->Nom}\n";
    }

    // 2. Trainees counts
    // Count 1: Active session only (IDSession = 35)
    $activeSessionCount = DB::selectOne("
        SELECT COUNT(*) as c
        FROM apprenant a
        INNER JOIN candidat c ON a.IDCandidat = c.IDCandidat
        LEFT JOIN section s   ON a.IDSection = s.IDSection
        LEFT JOIN offre o     ON c.IDOffre = o.IDOffre
        WHERE o.IDSession = ?
    ", [$activeSession ? $activeSession->IDSession : 0])->c;
    echo "Count for Active Session (ID 35): {$activeSessionCount}\n";

    // Count 2: Active status but without session filter
    $activeStatusCount = DB::selectOne("
        SELECT COUNT(*) as c
        FROM apprenant a
        INNER JOIN candidat c ON a.IDCandidat = c.IDCandidat
        LEFT JOIN section s   ON a.IDSection = s.IDSection
        LEFT JOIN offre o     ON c.IDOffre = o.IDOffre
        LEFT JOIN etablissement et ON o.IDEts_Form = et.IDetablissement
        LEFT JOIN session sess ON o.IDSession = sess.IDSession
        LEFT JOIN specialite sp ON o.IDSpecialite = sp.IDSpecialite
        WHERE a.statut = 'actif'
          AND NOT EXISTS (SELECT 1 FROM apprenant_fin af WHERE af.IDapprenant = a.IDapprenant)
          AND DATE_ADD(sess.DateD, INTERVAL COALESCE(NULLIF(sp.dureeM, 0), sp.NbrSem * 6, 24) MONTH) >= CURRENT_DATE()
    ")->c;
    echo "Count for Active Status (No session filter): {$activeStatusCount}\n";

    // 3. Let's see some samples of page 456 from the filtered query
    $page = 456;
    $perPage = 30;
    $offset = ($page - 1) * $perPage;
    
    $rows = DB::select("
        SELECT a.IDapprenant as id,
               sess.Nom as session_nom,
               o.IDSession,
               c.Nom as nom_ar
        FROM apprenant a
        INNER JOIN candidat c ON a.IDCandidat = c.IDCandidat
        LEFT JOIN section s   ON a.IDSection = s.IDSection
        LEFT JOIN offre o     ON c.IDOffre = o.IDOffre
        LEFT JOIN etablissement et ON o.IDEts_Form = et.IDetablissement
        LEFT JOIN session sess ON o.IDSession = sess.IDSession
        LEFT JOIN specialite sp ON o.IDSpecialite = sp.IDSpecialite
        WHERE a.statut = 'actif'
          AND NOT EXISTS (SELECT 1 FROM apprenant_fin af WHERE af.IDapprenant = a.IDapprenant)
          AND DATE_ADD(sess.DateD, INTERVAL COALESCE(NULLIF(sp.dureeM, 0), sp.NbrSem * 6, 24) MONTH) >= CURRENT_DATE()
          AND o.IDSession = ?
        ORDER BY a.IDapprenant DESC
        LIMIT ? OFFSET ?
    ", [$activeSession ? $activeSession->IDSession : 0, $perPage, $offset]);

    echo "Fetched " . count($rows) . " rows for page {$page} with session filter.\n";
    if (count($rows) > 0) {
        echo "First Row Session: ID: {$rows[0]->IDSession} | Nom: {$rows[0]->session_nom}\n";
    }

} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage() . "\nFile: " . $e->getFile() . "\nLine: " . $e->getLine();
}
