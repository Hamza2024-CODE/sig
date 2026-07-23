<?php
header('Content-Type: text/plain; charset=utf-8');
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "1. Describing utilisateur table columns:\n";
try {
    $cols = DB::select("DESCRIBE utilisateur");
    foreach ($cols as $c) {
        echo " - Column: {$c->Field} | Type: {$c->Type}\n";
    }
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

echo "\n2. Searching for users related to etab 1317 or with name 'Ansim' / 'أنسيم':\n";
try {
    // We will select all users since it is a small table
    $users = DB::table('utilisateur')->get();
    foreach ($users as $u) {
        // Let's check any fields that contain ansim or 1317
        $isMatch = false;
        foreach ((array)$u as $k => $v) {
            if (strpos(strtolower((string)$v), 'ansim') !== false || strpos((string)$v, 'أنسيم') !== false || (string)$v == '1317') {
                $isMatch = true;
                break;
            }
        }
        if ($isMatch) {
            // Find which fields exist in user
            $etabId = $u->IDetablissement ?? $u->IDEts_Form ?? $u->etablissement_id ?? 'null';
            $login = $u->login ?? $u->Login ?? $u->username ?? $u->NomUser ?? 'null';
            echo " - User Match: " . json_encode($u, JSON_UNESCAPED_UNICODE) . "\n";
        }
    }
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
