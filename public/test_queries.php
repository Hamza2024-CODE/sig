<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

header('Content-Type: text/plain; charset=utf-8');

echo "=== DIAGNOSTIC ACTIVE TRAINEES BREAKDOWN ===\n";

try {
    $activeCount = DB::selectOne("
        SELECT COUNT(a.IDapprenant) as c
        FROM apprenant a
        JOIN section s ON a.IDSection = s.IDSection
        LEFT JOIN apprenant_fin af ON a.IDapprenant = af.IDapprenant
        WHERE a.statut = 'actif'
          AND af.IDapprenant IS NULL
          AND s.DateDF <= CURRENT_DATE()
          AND s.DateFF >= CURRENT_DATE()
    ")->c;
    echo "Total active trainees: " . $activeCount . "\n";
} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

try {
    $newCount = DB::selectOne("
        SELECT COUNT(a.IDapprenant) as c
        FROM apprenant a
        JOIN section s ON a.IDSection = s.IDSection
        JOIN section_semestre ss ON s.IDSection = ss.IDSection
        LEFT JOIN apprenant_fin af ON a.IDapprenant = af.IDapprenant
        WHERE a.statut = 'actif'
          AND af.IDapprenant IS NULL
          AND s.DateDF <= CURRENT_DATE()
          AND s.DateFF >= CURRENT_DATE()
          AND ss.Dernier = 1
          AND ss.NumSem = 1
    ")->c;
    echo "S1 (New) active trainees: " . $newCount . "\n";
} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

try {
    $continuingCount = DB::selectOne("
        SELECT COUNT(a.IDapprenant) as c
        FROM apprenant a
        JOIN section s ON a.IDSection = s.IDSection
        JOIN section_semestre ss ON s.IDSection = ss.IDSection
        LEFT JOIN apprenant_fin af ON a.IDapprenant = af.IDapprenant
        WHERE a.statut = 'actif'
          AND af.IDapprenant IS NULL
          AND s.DateDF <= CURRENT_DATE()
          AND s.DateFF >= CURRENT_DATE()
          AND ss.Dernier = 1
          AND ss.NumSem > 1
    ")->c;
    echo "S2-S5 (Continuing) active trainees: " . $continuingCount . "\n";
} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

try {
    $fillesCount = DB::selectOne("
        SELECT COUNT(a.IDapprenant) as c
        FROM apprenant a
        JOIN section s ON a.IDSection = s.IDSection
        JOIN candidat c ON a.IDCandidat = c.IDCandidat
        LEFT JOIN apprenant_fin af ON a.IDapprenant = af.IDapprenant
        WHERE a.statut = 'actif'
          AND af.IDapprenant IS NULL
          AND s.DateDF <= CURRENT_DATE()
          AND s.DateFF >= CURRENT_DATE()
          AND c.Civ = 2
    ")->c;
    echo "Female active trainees: " . $fillesCount . "\n";
} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
