<?php

/**
 * Script to extract usernames and password hashes for Private Institutions only.
 * This script boots Laravel to use the active database connection configuration automatically.
 */

// Boot Laravel
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    // Select all private institutions (PublPrive = 1) with active logins
    $users = DB::select("
        SELECT 
            IDetablissement as id, 
            Nom as name_ar, 
            NomFr as name_fr, 
            nomUser as username, 
            MotDePass as password_hash 
        FROM etablissement 
        WHERE PublPrive = 1 
          AND nomUser IS NOT NULL 
          AND nomUser != ''
        ORDER BY Nom ASC
    ");

    if (empty($users)) {
        echo "No private institutions found with active usernames.\n";
        exit;
    }

    $csvFile = __DIR__ . '/private_institutions_users.csv';
    $fp = fopen($csvFile, 'w');
    
    // Add UTF-8 BOM for Excel to display Arabic characters correctly
    fprintf($fp, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Write headers
    fputcsv($fp, [
        'ID', 
        'Nom (العربية)', 
        'Nom (Français)', 
        'Username (اسم المستخدم)', 
        'Password Hash (كلمة المرور المشفرة)'
    ]);
    
    // Write rows
    foreach ($users as $user) {
        fputcsv($fp, [
            $user->id,
            $user->name_ar,
            $user->name_fr,
            $user->username,
            $user->password_hash
        ]);
    }
    
    fclose($fp);
    echo "\n=== Extraction Success ===\n";
    echo "Extracted " . count($users) . " private institution accounts.\n";
    echo "Saved to CSV file: " . $csvFile . "\n";
    echo "==========================\n";

} catch (\Exception $e) {
    echo "Error during extraction: " . $e->getMessage() . "\n";
}
