<?php
header('Content-Type: text/plain; charset=utf-8');

define('LARAVEL_START', microtime(true));
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== FRESH DIAGNOSIS: TRAINEES AND SECTIONS BY YEAR ===\n\n";

try {
    echo "--- Section Counts by Year (DateDF) ---\n";
    $years = [2022, 2023, 2024, 2025, 2026];
    foreach ($years as $yr) {
        $count = DB::table('section')
            ->where('DateDF', '>=', "$yr-01-01")
            ->where('DateDF', '<=', "$yr-12-31")
            ->count();
        echo "Year $yr: $count sections\n";
    }

    echo "\n--- Trainee Count for s.DateDF >= '2024-02-01' ---\n";
    $countTrainees = DB::table('apprenant as a')
        ->join('section as s', 'a.IDSection', '=', 's.IDSection')
        ->where('s.DateDF', '>=', '2024-02-01')
        ->count();
    echo "Trainees with section starting >= 2024-02-01: $countTrainees\n";

    echo "\n--- Sample sections with DateDF starting in 2024 ---\n";
    $samples = DB::table('section')
        ->where('DateDF', '>=', '2024-01-01')
        ->select('IDSection', 'DateDF', 'DateFF')
        ->limit(5)
        ->get();
    foreach ($samples as $sm) {
        echo "ID: {$sm->IDSection} | DateDF: {$sm->DateDF} | DateFF: {$sm->DateFF}\n";
    }

} catch (\Exception $e) {
    echo "❌ Error in diagnosis: " . $e->getMessage() . "\n";
}
