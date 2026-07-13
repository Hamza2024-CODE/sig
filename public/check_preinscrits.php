<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    echo "<h1>Checking NULL Year Records:</h1>";
    $rows = DB::table('preinscrit')
        ->select('DatePreinscrit', 'dateInscr', 'IDOffre', DB::raw('COUNT(*) as count'))
        ->whereNotExists(function($query) {
            $query->select(DB::raw(1))
                ->from('offre as o')
                ->join('session as sess', 'o.IDSession', '=', 'sess.IDSession')
                ->join('semestre_formation as sf', 'sess.IDSemestre_formation', '=', 'sf.IDSemestre_formation')
                ->whereColumn('o.IDOffre', 'preinscrit.IDOffre');
        })
        ->groupBy('DatePreinscrit', 'dateInscr', 'IDOffre')
        ->limit(20)
        ->get();

    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>DatePreinscrit</th><th>dateInscr</th><th>IDOffre</th><th>Count</th></tr>";
    foreach ($rows as $row) {
        echo "<tr>";
        echo "<td>" . ($row->DatePreinscrit ?? 'NULL') . "</td>";
        echo "<td>" . ($row->dateInscr ?? 'NULL') . "</td>";
        echo "<td>" . ($row->IDOffre ?? 'NULL') . "</td>";
        echo "<td>" . $row->count . "</td>";
        echo "</tr>";
    }
    echo "</table>";

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
}
