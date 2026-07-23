<?php
header('Content-Type: text/plain; charset=utf-8');
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== OFFERS FOR IDEts_Form = 1316 ===\n";
try {
    $offers = DB::table('offre as o')
        ->join('session as s', 'o.IDSession', '=', 's.IDSession')
        ->where('o.IDEts_Form', 1316)
        ->orWhere('o.IDEts_FormM', 1316)
        ->select('o.IDOffre', 'o.IDEts_Form', 'o.IDEts_FormM', 's.Nom as session_name')
        ->get();
    echo "Total offers: " . count($offers) . "\n";
    foreach ($offers as $o) {
        echo " - Offre ID: {$o->IDOffre} | IDEts_Form: {$o->IDEts_Form} | IDEts_FormM: {$o->IDEts_FormM} | Session: {$o->session_name}\n";
    }
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

echo "\n=== SECTIONS FOR IDEts_Form = 1316 ===\n";
try {
    $sections = DB::table('section as sec')
        ->join('offre as o', 'sec.IDOffre', '=', 'o.IDOffre')
        ->join('session as s', 'o.IDSession', '=', 's.IDSession')
        ->where('sec.IDEts_Form', 1316)
        ->orWhere('sec.IDEts_FormM', 1316)
        ->select('sec.IDSection', 'sec.Nom as sec_name', 's.Nom as session_name')
        ->get();
    echo "Total sections: " . count($sections) . "\n";
    foreach ($sections as $s) {
        echo " - Sec ID: {$s->IDSection} | Name: {$s->sec_name} | Session: {$s->session_name}\n";
    }
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

echo "\n=== TRAINEES FOR IDEts_Form = 1316 ===\n";
try {
    $traineesCount = DB::table('apprenant as a')
        ->join('section as sec', 'a.IDSection', '=', 'sec.IDSection')
        ->join('offre as o', 'sec.IDOffre', '=', 'o.IDOffre')
        ->join('session as s', 'o.IDSession', '=', 's.IDSession')
        ->where('sec.IDEts_Form', 1316)
        ->orWhere('sec.IDEts_FormM', 1316)
        ->count();
    echo "Total trainees: $traineesCount\n";
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
