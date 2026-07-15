<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

header('Content-Type: text/plain; charset=utf-8');

echo "=== DIRECT QUERY TEST ===\n";

$cfpa = DB::table('etablissement')->where('IDNature_etsF', 8)->where('activee', 0)->count();
echo "CFPA count in DB: {$cfpa}\n";

$insfp = DB::table('etablissement')->where('IDNature_etsF', 6)->where('activee', 0)->count();
echo "INSFP count in DB: {$insfp}\n";

$private = DB::table('etablissement')->where('IDNature_etsF', 12)->where('activee', 0)->count();
echo "Private count in DB: {$private}\n";

$staff = DB::table('encadrement')->whereNotNull('nin')->where('nin','!=','')->whereNotNull('MotDePass')->where('MotDePass','!=','')->count();
echo "Active staff count in DB: {$staff}\n";

$total_staff = DB::table('encadrement')->count();
echo "Total staff in DB: {$total_staff}\n";
