<?php
define('LARAVEL_START', microtime(true));
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

header('Content-Type: text/plain; charset=utf-8');

try {
    echo "--- UNIQUE QualiteMembre in membrepvfinal ---\n";
    $rows = DB::select("SELECT QualiteMembre, COUNT(*) as cnt FROM membrepvfinal GROUP BY QualiteMembre");
    foreach ($rows as $r) {
        echo "QualiteMembre: {$r->QualiteMembre} | Count: {$r->cnt}\n";
    }

    echo "\n--- UNIQUE NomFonction in membrepvfinal (Top 20) ---\n";
    $rows = DB::select("SELECT NomFonction, COUNT(*) as cnt FROM membrepvfinal GROUP BY NomFonction ORDER BY cnt DESC LIMIT 20");
    foreach ($rows as $r) {
        echo "NomFonction: {$r->NomFonction} | Count: {$r->cnt}\n";
    }

    echo "\n--- SAMPLE membrepvfinal with QualiteMembre/NomFonction ---\n";
    $rows = DB::select("SELECT NomPrenom, QualiteMembre, NomFonction, IDSection FROM membrepvfinal LIMIT 20");
    foreach ($rows as $r) {
        echo "NomPrenom: {$r->NomPrenom} | Qualite: {$r->QualiteMembre} | Fonction: {$r->NomFonction} | Section: {$r->IDSection}\n";
    }

} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage();
}
