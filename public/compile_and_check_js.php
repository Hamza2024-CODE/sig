<?php
header('Content-Type: text/plain; charset=utf-8');

define('LARAVEL_START', microtime(true));
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\View;

echo "=== COMPILING BLADE AND EXTRACTING JS TO CHECK SYNTAX ===\n\n";

try {
    // Setup dummy variables for the view
    $type = 'trainee';
    $records = [
        [
            'id' => 1,
            'nom' => 'Test',
            'prenom' => 'User',
            'nin' => '123456',
            'spec_ar' => 'Computer Science',
            'etab_nom' => 'Etab 1'
        ]
    ];
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
        'search' => '',
        'wilaya' => '',
        'etab' => '',
        'mode' => '',
        'branche' => '',
        'grade' => '',
        'fonction' => ''
    ];

    // Compile view
    $html = View::make('admin.digital-cards.index', compact(
        'type', 'records', 'filter_wilayas', 'filter_etablissements', 
        'filter_branches', 'filter_modes', 'filter_grades', 'filter_fonctions', 
        'page', 'totalPages', 'totalCount', 'scope', 'selected_filters'
    ))->render();

    echo "Blade Compilation: SUCCESS!\n";

    // Extract JS script content
    // We want the script block inside @section('scripts')
    if (preg_match_all('/<script\b[^>]*>(.*?)<\/script>/is', $html, $matches)) {
        echo "Found " . count($matches[1]) . " script blocks.\n\n";
        
        foreach ($matches[1] as $idx => $js) {
            // Ignore small CDN script blocks or inline tiny scripts
            if (strlen($js) < 100) continue;
            
            echo "--- Checking Script Block #$idx (" . strlen($js) . " bytes) ---\n";
            
            // Save to temp file
            $tempJsFile = __DIR__ . '/temp_check_' . $idx . '.js';
            file_put_contents($tempJsFile, $js);
            
            // Run node syntax check
            $output = [];
            $retval = 0;
            exec("node --check " . escapeshellarg($tempJsFile) . " 2>&1", $output, $retval);
            
            if ($retval === 0) {
                echo "✓ Syntax OK!\n";
            } else {
                echo "❌ SYNTAX ERROR FOUND:\n";
                echo implode("\n", $output) . "\n";
            }
            
            // Clean up
            @unlink($tempJsFile);
        }
    } else {
        echo "No script blocks found in compiled HTML.\n";
    }

} catch (\Exception $e) {
    echo "❌ Error during compilation: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
