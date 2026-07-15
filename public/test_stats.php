<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

header('Content-Type: text/plain; charset=utf-8');

echo "=== DIAGNOSTIC START ===\n";

try {
    $activeCount = DB::selectOne("SELECT COUNT(*) as c FROM apprenant")->c;
    echo "Total apprenants in DB: " . $activeCount . "\n";
} catch (\Throwable $e) {
    echo "Error querying apprenant: " . $e->getMessage() . "\n";
}

try {
    $activeCount = DB::selectOne("SELECT COUNT(*) as c FROM section")->c;
    echo "Total sections in DB: " . $activeCount . "\n";
} catch (\Throwable $e) {
    echo "Error querying section: " . $e->getMessage() . "\n";
}

try {
    echo "Running Apprenants Query...\n";
    $sql = "
        SELECT COUNT(a.IDapprenant) as c
        FROM apprenant a
        JOIN section s ON a.IDSection = s.IDSection
        LEFT JOIN apprenant_fin af ON a.IDapprenant = af.IDapprenant
        WHERE a.statut = 'actif'
          AND af.IDapprenant IS NULL
          AND s.DateDF <= CURRENT_DATE()
          AND s.DateFF >= CURRENT_DATE()
    ";
    $r = DB::selectOne($sql);
    echo "Result: " . ($r ? $r->c : 'NULL') . "\n";
} catch (\Throwable $e) {
    echo "Error running Apprenants Query: " . $e->getMessage() . "\n";
}

try {
    echo "Running simple count as backup check...\n";
    $sql = "SELECT COUNT(*) as c FROM apprenant WHERE statut = 'actif'";
    $r = DB::selectOne($sql);
    echo "Simple active count: " . $r->c . "\n";
} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

try {
    echo "Checking DateDF/DateFF range in section...\n";
    $row = DB::selectOne("SELECT MIN(DateDF) as mind, MAX(DateFF) as maxd, COUNT(*) as cnt FROM section");
    if ($row) {
        echo "Min DateDF: " . $row->mind . ", Max DateFF: " . $row->maxd . ", Total rows: " . $row->cnt . "\n";
    }
} catch (\Throwable $e) {
    echo "Error checking date range: " . $e->getMessage() . "\n";
}
