<?php
header('Content-Type: text/plain; charset=utf-8');
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    echo "=== OFFER/SECTION OWNER SEARCH FOR 1301 ===\n\n";

    // 1. Search offers by IDEts_Form or IDEts_FormM
    $oForm = DB::table('offre')->where('IDEts_Form', 1301)->count();
    $oFormM = DB::table('offre')->where('IDEts_FormM', 1301)->count();
    echo "Offers: IDEts_Form=1301: {$oForm} | IDEts_FormM=1301: {$oFormM}\n";

    // 2. Search sections by IDEts_Form or IDEts_FormM
    $sForm = DB::table('section')->where('IDEts_Form', 1301)->count();
    $sFormM = DB::table('section')->where('IDEts_FormM', 1301)->count();
    echo "Sections: IDEts_Form=1301: {$sForm} | IDEts_FormM=1301: {$sFormM}\n";

    // 3. Search in 'offre' for public center 352 (Setif) to see what private offers are linked to it
    echo "\n=== Offers linked to public center 352 ===\n";
    $offers352 = DB::table('offre')
        ->where('IDEts_Form', 352)
        ->orWhere('IDEts_FormM', 352)
        ->get(['IDOffre', 'IDEts_Form', 'IDEts_FormM', 'NbrInscr']);
    
    echo "Found " . $offers352->count() . " offers:\n";
    foreach ($offers352 as $o) {
        echo "   - Offer ID {$o->IDOffre}: Owner (IDEts_Form) = {$o->IDEts_Form} | Parent (IDEts_FormM) = {$o->IDEts_FormM} | Enrolled: {$o->NbrInscr}\n";
    }

} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
