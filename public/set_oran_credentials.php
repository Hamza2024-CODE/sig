<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$updates = [
    // DFEP level
    'SA' => 'wonque',
    'SSFEP' => 'driduv',
    'SFACI' => 'devteW',
    'SAMF' => 'wAn7Wa',
    'SAMRH' => 'furwAn',
    'SSIP' => 'keweLA',
    'AdmFin#Dfep' => 'LifabE',
    'DFEPS' => 'driduv',
    
    // Establishment Directors
    'DIRETS' => 'phimEt',
    'DIRETSs' => 'phimEt',
    'DIRETSp' => 'phimEt',
    
    // Apprenticeship departments
    'SDTPA' => 'kYbyfu',
    'SDTPAs' => 'kYbyfu',
    'SDTPAp' => 'kYbyfu',
    
    // Presentiel departments
    'SDTPP' => 'mEtflY',
    'SDTPPs' => 'mEtflY',
    'SDTPPp' => 'mEtflY',
    
    // Continuous training departments
    'SDTPC' => 'SixikA',
    'SDTPCs' => 'SixikA',
    'SDTPCp' => 'SixikA',
    
    // Orientation departments
    'BIAO' => 'nAMUto',
    'BIAOs' => 'nAMUto',
    'BIAOp' => 'nAMUto',
    
    // Administrative and finance departments
    'SDARH' => 'DEvibi',
    'SDAFM' => 'qYPeze',
    'AdmFinEp' => 'LifabE',
    
    // Diplomas offices
    'Dplm' => 'quyCA6',
    'Dplms' => 'quyCA6',
    'Dplmp' => 'quyCA6',
    'dplmDir' => 'quyCA6'
];

echo "Updating all department secret codes in the database to match CSV standard...\n";

foreach ($updates as $username => $plainTextPassword) {
    $hash = password_hash($plainTextPassword, PASSWORD_BCRYPT, ['cost' => 12]);
    
    $affected = Illuminate\Support\Facades\DB::table('utilisateur')
        ->where('NomUser', $username)
        ->update(['MotPass' => $hash]);
        
    if ($affected > 0) {
        echo "✓ Updated '{$username}' to password: '{$plainTextPassword}'\n";
    } else {
        echo "· No changes made for '{$username}' (user not found or password already matches)\n";
    }
}

echo "All updates completed!\n";
