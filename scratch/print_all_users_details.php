<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

$users = DB::select("SELECT * FROM utilisateur");
foreach ($users as $u) {
    $decryptedUsername = '';
    if (strpos($u->NomUser, '@') === 0) {
        try {
            $decryptedUsername = Crypt::decryptString(substr($u->NomUser, 1));
        } catch (\Exception $e) {}
    } else {
        $decryptedUsername = $u->NomUser;
    }
    
    // Check if NomUser or Nom matches Jedaoui or Mokhtaria (case-insensitive)
    $nom = mb_strtolower($u->Nom);
    $username = mb_strtolower($decryptedUsername);
    if (strpos($nom, 'مخت') !== false || strpos($nom, 'جدا') !== false || strpos($username, 'mokh') !== false || strpos($username, 'jed') !== false) {
        echo "ID: " . $u->IDUtilisateur . " | Username: " . $decryptedUsername . " | Name: " . $u->Nom . " | IDBureau: " . $u->IDBureau . "\n";
    }
}
