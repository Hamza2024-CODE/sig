<?php
header('Content-Type: text/plain; charset=utf-8');
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "1. Describing 'offre' table columns:\n";
try {
    $cols = DB::select("DESCRIBE offre");
    foreach ($cols as $c) {
        echo " - Column: {$c->Field} | Type: {$c->Type}\n";
    }
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

echo "\n2. Searching for offers of public INSFP 491 where IDEts_Form = 491, printing some samples to see if there is any field linking it to a private school:\n";
try {
    $offers491 = DB::table('offre')
        ->where('IDEts_Form', 491)
        ->limit(10)
        ->get();
    foreach ($offers491 as $o) {
        echo " - Offre ID: {$o->IDOffre} | IDEts_Form: {$o->IDEts_Form} | IDEts_FormM: " . ($o->IDEts_FormM ?? 'null') . " | IDSession: {$o->IDSession} | Valide: {$o->Valide} | ValidDfp: {$o->ValidDfp}\n";
    }
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
