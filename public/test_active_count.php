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
    
    $start = microtime(true);
    $cnt = DB::selectOne("
        SELECT COUNT(*) as c
        FROM apprenant a
        JOIN candidat c ON a.IDCandidat = c.IDCandidat
        JOIN section s ON a.IDSection = s.IDSection
        LEFT JOIN specialite sp ON s.IDSpecialite = sp.IDSpecialite
        LEFT JOIN etablissement e ON s.IDEts_Form = e.IDetablissement
        LEFT JOIN wilaya w ON w.IDWilayaa = e.IDDFEP
        WHERE a.IDSection != 0 AND s.IDSession IN (31, 32, 33, 34, 35)
    ");
    $elapsed = microtime(true) - $start;
    echo "Count of Active (IDSession IN 31-35): " . $cnt->c . " (Took: " . round($elapsed, 4) . " seconds)\n";
    
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
