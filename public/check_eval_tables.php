<?php
define('LARAVEL_START', microtime(true));
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

header('Content-Type: text/plain; charset=utf-8');

try {
    echo "--- DESCRIBE apprenant_fin ---\n";
    $cols = DB::select("DESCRIBE apprenant_fin");
    foreach ($cols as $c) {
        echo "{$c->Field} | {$c->Type} | {$c->Null} | {$c->Key}\n";
    }

    echo "\n--- DESCRIBE membrepvfinal ---\n";
    $cols = DB::select("DESCRIBE membrepvfinal");
    foreach ($cols as $c) {
        echo "{$c->Field} | {$c->Type} | {$c->Null} | {$c->Key}\n";
    }

    echo "\n--- DESCRIBE encadrement ---\n";
    $cols = DB::select("DESCRIBE encadrement");
    foreach ($cols as $c) {
        echo "{$c->Field} | {$c->Type} | {$c->Null} | {$c->Key}\n";
    }

} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage();
}
