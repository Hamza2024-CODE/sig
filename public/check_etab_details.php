<?php
define('LARAVEL_START', microtime(true));
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

header('Content-Type: text/plain; charset=utf-8');

$etab1301 = DB::table('etablissement')->where('IDetablissement', 1301)->first();
$etabEtsForm1301 = DB::table('etablissement')->where('IDEts_Form', 1301)->first();

echo "1. Querying IDetablissement = 1301:\n";
if ($etab1301) {
    echo "Nom: " . $etab1301->Nom . "\n";
    echo "IDetablissement: " . $etab1301->IDetablissement . "\n";
    echo "IDEts_Form: " . $etab1301->IDEts_Form . "\n";
    echo "PublPrive: " . $etab1301->PublPrive . "\n";
} else {
    echo "Not found!\n";
}

echo "\n2. Querying IDEts_Form = 1301:\n";
if ($etabEtsForm1301) {
    echo "Nom: " . $etabEtsForm1301->Nom . "\n";
    echo "IDetablissement: " . $etabEtsForm1301->IDetablissement . "\n";
    echo "IDEts_Form: " . $etabEtsForm1301->IDEts_Form . "\n";
    echo "PublPrive: " . $etabEtsForm1301->PublPrive . "\n";
} else {
    echo "Not found!\n";
}
