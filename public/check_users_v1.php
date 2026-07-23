<?php
header('Content-Type: text/plain; charset=utf-8');
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "1. Users containing 'Ansim' or 'أنسيم' in username, NomUser, or description:\n";
try {
    $users = DB::table('utilisateur')
        ->where('username', 'LIKE', '%ansim%')
        ->orWhere('NomUser', 'LIKE', '%أنسيم%')
        ->orWhere('NomUser', 'LIKE', '%ansim%')
        ->get();
    foreach ($users as $u) {
        echo " - User: {$u->username} | NomUser: {$u->NomUser} | EtabID: {$u->IDEts_Form} | Role: {$u->IDRole} | Active: {$u->valide}\n";
    }
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

echo "\n2. Private establishments in Wilaya 35 (Boumerdes):\n";
try {
    $etabs = DB::table('etablissement')
        ->where('IDDFEP', 35)
        ->where('PublPrive', 1)
        ->get();
    foreach ($etabs as $et) {
        echo " - ID: {$et->IDetablissement} | Nom: {$et->Nom} | PublPrive: {$et->PublPrive} | Attached Insfp: {$et->DeIDetablissementRatacheInsfp} | Attached Etab: {$et->DeIDetablissementRatache}\n";
    }
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

echo "\n3. Checking if there are other logins or candidates linked to 'Ansim' in Boumerdes:\n";
// Let's count offers by IDEts_Form for any private etabs in Wilaya 35
try {
    $etabIds35 = DB::table('etablissement')
        ->where('IDDFEP', 35)
        ->where('PublPrive', 1)
        ->pluck('IDetablissement')
        ->toArray();

    if (!empty($etabIds35)) {
        $offersCount = DB::table('offre')
            ->whereIn('IDEts_Form', $etabIds35)
            ->select('IDEts_Form', DB::raw('count(*) as count'))
            ->groupBy('IDEts_Form')
            ->get();
        echo "Offers count for private etabs in Wilaya 35:\n";
        foreach ($offersCount as $oc) {
            echo " - EtabID: {$oc->IDEts_Form} | Count: {$oc->count}\n";
        }
    } else {
        echo "No private etabs in Wilaya 35 found.\n";
    }
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
