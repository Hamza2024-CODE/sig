<?php
define('LARAVEL_START', microtime(true));
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

header('Content-Type: text/plain; charset=utf-8');

try {
    $filter = "1=1";
    $params = [];

    echo "--- Querying with fallback name ---\n";
    $sql = "
        SELECT 
            COALESCE(NULLIF(TRIM(mpv.NomPrenom), ''), (SELECT CONCAT(enc.Nom, ' ', enc.Prenom) FROM encadrement enc WHERE enc.IDEncadrement = mpv.IDEncadrement), 'مفتش بيداغوجي') as name,
            mpv.NomFonction as rank,
            COUNT(DISTINCT mpv.IDSection) as count_inspections,
            COALESCE(GROUP_CONCAT(DISTINCT d.Nom SEPARATOR '، '), 'كل الولايات') as wilayas,
            ROUND(AVG(af.MoyGen), 2) as average_grade
        FROM membrepvfinal mpv
        JOIN section s ON mpv.IDSection = s.IDSection
        JOIN section_semestre ss ON s.IDSection = ss.IDSection
        LEFT JOIN apprenant_fin af ON ss.IDSection_Semestre = af.IDSection_Semestre
        LEFT JOIN etablissement e ON s.IDEts_Form = e.IDetablissement
        LEFT JOIN dfep d ON e.IDDFEP = d.IDDFEP
        WHERE (mpv.NomFonction LIKE '%مفتش%' OR mpv.NomFonction LIKE '%inspecteur%')
          AND $filter
        GROUP BY name, mpv.NomFonction
        ORDER BY count_inspections DESC
        LIMIT 20
    ";
    
    $rows = DB::select($sql, $params);
    echo "Found: " . count($rows) . " rows\n";
    foreach ($rows as $r) {
        echo "Name: '{$r->name}' | Rank: {$r->rank} | Inspections: {$r->count_inspections}\n";
    }

} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
