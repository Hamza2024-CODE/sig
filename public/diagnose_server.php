<?php
header('Content-Type: text/plain; charset=utf-8');

define('LARAVEL_START', microtime(true));
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== DIAGNOSTIC FOR ACTIVE WEBSITE ===\n";
$baseDir = realpath(__DIR__ . '/..');
echo "1. Real Base Directory on Server: " . $baseDir . "\n\n";

$viewPath = $baseDir . '/resources/views/admin/offres/index.blade.php';
echo "2. View File Path: " . $viewPath . "\n";
if (file_exists($viewPath)) {
    $content = file_get_contents($viewPath);
    $hasBreakdown = strpos($content, 'Breakdown by Year') !== false;
    echo "   - File Exists: Yes\n";
    echo "   - Contains 'Breakdown by Year' text: " . ($hasBreakdown ? 'Yes' : 'No') . "\n";
}

$repoPath = $baseDir . '/app/Domains/Academic/Repositories/OffresRepository.php';
echo "3. Repository File Path: " . $repoPath . "\n";
if (file_exists($repoPath)) {
    $content = file_get_contents($repoPath);
    $hasByYear = strpos($content, 'by_year') !== false;
    echo "   - File Exists: Yes\n";
    echo "   - Contains 'by_year' mapping: " . ($hasByYear ? 'Yes' : 'No') . "\n";
}

echo "\n4. Running SQL test for 'by_year' directly on server...\n";
try {
    // We want to test Tizi Ouzou (IDDFEP = 15)
    $scopeWhere = 'e.IDDFEP = ?';
    $scopeParams = [15];
    $joinEtab = "LEFT JOIN etablissement e ON o.IDEts_Form = e.IDetablissement";

    $results = \Illuminate\Support\Facades\DB::select("
        SELECT 
            YEAR(sess.DateD) as year,
            COUNT(o.IDOffre) as count_offres,
            COALESCE(SUM(o.nbrPrevision), 0) as count_places
        FROM offre o
        JOIN session sess ON o.IDSession = sess.IDSession
        $joinEtab
        WHERE $scopeWhere
        GROUP BY YEAR(sess.DateD)
        ORDER BY YEAR(sess.DateD) ASC
    ", $scopeParams);

    echo "Query executed successfully. Result count: " . count($results) . "\n";
    foreach ($results as $r) {
        $rArr = (array)$r;
        echo "   Year: " . ($rArr['year'] ?? 'NULL') . 
             " | Offers: " . ($rArr['count_offres'] ?? 0) . 
             " | Places: " . ($rArr['count_places'] ?? 0) . "\n";
    }

} catch (\Exception $e) {
    echo "   Error running query: " . $e->getMessage() . "\n";
}

echo "\n5. Checking what is in the cache for offres stats...\n";
try {
    $cacheKey = 'offres_stats_v2_' . md5('e.IDDFEP = ?' . '_' . serialize([15]));
    $cachedData = \Illuminate\Support\Facades\Cache::get($cacheKey);
    if ($cachedData) {
        echo "   Cache key '$cacheKey' found.\n";
        echo "   Has 'by_year' key: " . (isset($cachedData['by_year']) ? 'Yes' : 'No') . "\n";
        if (isset($cachedData['by_year'])) {
            echo "   Count in cached by_year: " . count($cachedData['by_year']) . "\n";
        }
    } else {
        echo "   Cache key '$cacheKey' is empty / not found.\n";
    }
} catch (\Exception $e) {
    echo "   Error reading cache: " . $e->getMessage() . "\n";
}
