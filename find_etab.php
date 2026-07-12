<?php

/**
 * Script to find specific institution credentials (like Khutwa Saida).
 */

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    // Search for keywords in both Arabic and French names
    $results = DB::select("
        SELECT 
            IDetablissement as id, 
            Nom as name_ar, 
            NomFr as name_fr, 
            nomUser as username, 
            MotDePass as password_hash 
        FROM etablissement 
        WHERE Nom LIKE '%خطوة%' 
           OR Nom LIKE '%سعيدة%' 
           OR NomFr LIKE '%khutwa%' 
           OR NomFr LIKE '%saida%'
    ");

    if (empty($results)) {
        echo "\nNo institutions found matching those keywords.\n";
        exit;
    }

    echo "\n=== Search Results ===\n";
    foreach ($results as $etab) {
        echo "ID: " . $etab->id . "\n";
        echo "Name (Ar): " . $etab->name_ar . "\n";
        echo "Name (Fr): " . $etab->name_fr . "\n";
        echo "Username: " . ($etab->username ?? 'No Username') . "\n";
        echo "Password Hash: " . ($etab->password_hash ?? 'No Password') . "\n";
        echo "----------------------\n";
    }

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
