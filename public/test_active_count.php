<?php
header('Content-Type: text/plain; charset=utf-8');

define('LARAVEL_START', microtime(true));
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    $db = new \App\Core\LaravelDbAdapter();
    
    // Test count with outer join only
    $cnt1 = DB::selectOne("
        SELECT COUNT(*) as c
        FROM apprenant a
        LEFT JOIN apprenant_fin af ON a.IDapprenant = af.IDapprenant
        WHERE a.IDSection != 0 AND (af.IDapprenant IS NULL OR af.SituationFin IN (0,4))
    ");
    echo "Count 1 (Simple Active): " . $cnt1->c . "\n";

    // Test count with candidat and section joins
    $cnt2 = DB::selectOne("
        SELECT COUNT(*) as c
        FROM apprenant a
        JOIN candidat c ON a.IDCandidat = c.IDCandidat
        JOIN section s ON a.IDSection = s.IDSection
        LEFT JOIN apprenant_fin af ON a.IDapprenant = af.IDapprenant
        WHERE a.IDSection != 0 AND (af.IDapprenant IS NULL OR af.SituationFin IN (0,4))
    ");
    echo "Count 2 (With joins): " . $cnt2->c . "\n";
    
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
