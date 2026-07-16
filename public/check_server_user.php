<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$username = 'rUQ1300';
try {
    $e = DB::table('etablissement')->where('nomUser', $username)->first();
    if ($e) {
        echo "FOUND ON SERVER!\n";
        echo "IDetablissement: {$e->IDetablissement} | Nom: {$e->Nom} | activee: {$e->activee} | nomUser: {$e->nomUser}\n";
        
        // Let's check nature and users
        $n = DB::table('nature_etsf')->where('IDNature_etsF', $e->IDNature_etsF)->first();
        echo "Nature IDNature: " . ($n->IDNature ?? 'NULL') . "\n";
        
        $u = DB::table('utilisateur')->where('IDNature', $n->IDNature)->get();
        echo "Utilisateurs count: " . count($u) . "\n";
        foreach ($u as $user) {
            echo "  - NomUser: {$user->NomUser}\n";
        }
    } else {
        echo "NOT FOUND ON SERVER for username: '{$username}'\n";
        // Let's search for similar usernames
        $sim = DB::table('etablissement')->where('nomUser', 'LIKE', '%1300%')->get(['IDetablissement', 'Nom', 'nomUser']);
        echo "Similar usernames containing 1300:\n";
        print_r($sim->toArray());
    }
} catch (\Throwable $ex) {
    echo "Error: " . $ex->getMessage() . "\n";
}
