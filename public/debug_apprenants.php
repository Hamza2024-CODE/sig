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
        echo "Active Session: ID: {$activeSession->IDSession} | Nom: {$activeSession->Nom} | DateD: {$activeSession->DateD}\n";
    } else {
        echo "No active session found!\n";
    }

    // 2. Count apprenants for this session vs all
    $allCount = DB::table('apprenant')->count();
    echo "Total apprenants in DB: {$allCount}\n";

    if ($activeSession) {
        $sessionCount = DB::table('apprenant as a')
            ->join('candidat as c', 'a.IDCandidat', '=', 'candidat_id') // Wait, let's use exact query joins
            ->count();
        
        // Let's run raw SQL count with active session
        $rawCount = DB::selectOne("
            SELECT COUNT(*) as c
            FROM apprenant a
            INNER JOIN candidat c ON a.IDCandidat = c.IDCandidat
            LEFT JOIN section s   ON a.IDSection = s.IDSection
            LEFT JOIN offre o     ON c.IDOffre = o.IDOffre
            WHERE o.IDSession = ?
        ", [$activeSession->IDSession])->c;
        echo "Total with raw active session count: {$rawCount}\n";
    }

} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage() . "\nFile: " . $e->getFile() . "\nLine: " . $e->getLine();
}
