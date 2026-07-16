<?php
header('Content-Type: text/plain; charset=utf-8');
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    $e = DB::table('etablissement')->where('IDetablissement', 1301)->first();
    if ($e) {
        echo "Etablissement: {$e->Nom}\n";
        echo "Username on Server: '{$e->nomUser}'\n";
        
        $pw1301 = 'jyc:@1301';
        $pw1300 = 'jyc:@1300';
        
        $match1301 = (password_verify($pw1301, $e->MotDePass) || $e->MotDePass === $pw1301);
        $match1300 = (password_verify($pw1300, $e->MotDePass) || $e->MotDePass === $pw1300);
        
        echo "Password 'jyc:@1301' matches? " . ($match1301 ? 'YES' : 'NO') . "\n";
        echo "Password 'jyc:@1300' matches? " . ($match1300 ? 'YES' : 'NO') . "\n";
        
        if (!$match1301 && !$match1300) {
            echo "Current password hash/value in DB: '{$e->MotDePass}'\n";
        }
    } else {
        echo "Establishment 1301 not found.\n";
    }
} catch (\Throwable $ex) {
    echo "Error: " . $ex->getMessage() . "\n";
}
