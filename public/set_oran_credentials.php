<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$updates = [
    'SA' => 'wonque',
    'SSFEP' => 'driduv',
    'SFACI' => 'devteW',
    'SAMF' => 'wAn7Wa',
    'SAMRH' => 'furwAn',
    'SSIP' => 'keweLA',
    'AdmFin#Dfep' => 'LifabE'
];

echo "Updating secret codes in the database...\n";

foreach ($updates as $username => $plainTextPassword) {
    $hash = password_hash($plainTextPassword, PASSWORD_BCRYPT, ['cost' => 12]);
    
    $affected = Illuminate\Support\Facades\DB::table('utilisateur')
        ->where('NomUser', $username)
        ->update(['MotPass' => $hash]);
        
    if ($affected > 0) {
        echo "✓ Updated '{$username}' to password: '{$plainTextPassword}'\n";
    } else {
        echo "✗ No changes made for '{$username}' (user not found or password already matches)\n";
    }
}

echo "All updates completed!\n";
