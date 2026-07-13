<?php
header('Content-Type: text/plain; charset=utf-8');

define('LARAVEL_START', microtime(true));
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\View;

echo "=== COMPILED JAVASCRIPT EXTRACTOR ===\n\n";

try {
    $type = 'trainee';
    $records = [];
    $totalPages = 1;
    $page = 1;
    $totalCount = 1;
    $scope = ['role' => 'admin', 'iddfep' => 0, 'etabId' => 0];
    $filter_wilayas = [];
    $filter_etablissements = [];
    $filter_branches = [];
    $filter_modes = [];
    $filter_grades = [];
    $filter_fonctions = [];
    $selected_filters = [
        'search' => '', 'wilaya' => '', 'etab' => '', 'mode' => '', 'branche' => '', 'grade' => '', 'fonction' => ''
    ];

    $html = View::make('admin.digital-cards.index', compact(
        'type', 'records', 'filter_wilayas', 'filter_etablissements', 
        'filter_branches', 'filter_modes', 'filter_grades', 'filter_fonctions', 
        'page', 'totalPages', 'totalCount', 'scope', 'selected_filters'
    ))->render();

    if (preg_match_all('/<script\b[^>]*>(.*?)<\/script>/is', $html, $matches)) {
        foreach ($matches[1] as $idx => $js) {
            if (strlen($js) < 100) continue;
            echo "--- SCRIPT BLOCK #$idx ---\n";
            echo $js . "\n\n";
        }
    } else {
        echo "No script blocks found.\n";
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
