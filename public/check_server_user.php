<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$username = 'rUQ1300';
$padded = 'rUQ1300';

echo "=== DIAGNOSTICS FOR USER {$username} ===\n";

try {
    // 1. Direct check on Etablissement table
    $etab = DB::table('etablissement')
        ->where(function($q) use ($username, $padded) {
            $q->where(DB::raw('LOWER(nomUser)'), strtolower($username))
              ->orWhere(DB::raw('LOWER(nomUser)'), strtolower($padded));
        })
        ->first();

    if ($etab) {
        echo "1. Found in etablissement table! ID: {$etab->IDetablissement}, Nom: {$etab->Nom}, nomUser: {$etab->nomUser}, activee: {$etab->activee}, IDNature_etsF: {$etab->IDNature_etsF}\n";
        
        // 2. Check Nature_etsF
        $nature = DB::table('nature_etsf')->where('IDNature_etsF', $etab->IDNature_etsF)->first();
        if ($nature) {
            echo "2. Found in nature_etsf! IDNature_etsF: {$nature->IDNature_etsF}, IDNature: {$nature->IDNature}, Nom: {$nature->Nom}\n";
            
            // 3. Check NatureDirection
            $nd = DB::table('naturedirection')->where('IDNature', $nature->IDNature)->first();
            if ($nd) {
                echo "3. Found in naturedirection! IDNature: {$nd->IDNature}, Nom: {$nd->Nom}\n";
                
                // 4. Check Utilisateur
                $users = DB::table('utilisateur')->where('IDNature', $nature->IDNature)->get();
                if ($users->count() > 0) {
                    echo "4. Found Utilisateurs matching IDNature {$nature->IDNature}:\n";
                    foreach ($users as $u) {
                        echo "   - NomUser: '{$u->NomUser}' | Nom: '{$u->Nom}'\n";
                    }
                } else {
                    echo "4. ERROR: No users found in 'utilisateur' table with IDNature = {$nature->IDNature}\n";
                }
            } else {
                echo "3. ERROR: No row in 'naturedirection' table with IDNature = {$nature->IDNature}\n";
            }
        } else {
            echo "2. ERROR: No row in 'nature_etsf' table with IDNature_etsF = {$etab->IDNature_etsF}\n";
        }
    } else {
        echo "1. ERROR: No row in 'etablissement' table matches nomUser = '{$username}' (case-insensitive)\n";
    }

} catch (\Throwable $e) {
    echo "Query Error: " . $e->getMessage() . "\n";
}
