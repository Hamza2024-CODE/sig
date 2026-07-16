<?php
define('LARAVEL_START', microtime(true));
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

header('Content-Type: text/plain; charset=utf-8');

try {
    echo "--- Public/Private institutions comparison ---\n";
    
    // PublPrive column value: let's inspect PublPrive for some institutions
    $diffs = DB::select("
        SELECT IDetablissement, IDEts_Form, Nom, PublPrive 
        FROM etablissement 
        WHERE IDEts_Form IS NOT NULL AND IDEts_Form != IDetablissement 
        LIMIT 10
    ");
    foreach ($diffs as $d) {
        echo "IDetablissement: {$d->IDetablissement} | IDEts_Form: {$d->IDEts_Form} | Nom: {$d->Nom} | PublPrive: {$d->PublPrive}\n";
    }

    echo "\n--- Count of etabs where IDEts_Form != IDetablissement ---\n";
    $cnt = DB::select("SELECT COUNT(*) as count FROM etablissement WHERE IDEts_Form IS NOT NULL AND IDEts_Form != IDetablissement");
    echo "Total different: {$cnt[0]->count}\n";

} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
