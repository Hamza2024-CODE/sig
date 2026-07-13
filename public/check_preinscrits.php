<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    $total = DB::table('preinscrit')->count();
    echo "<h1>Total Pre-registrations in DB: $total</h1>";

    if ($total > 0) {
        $stats = DB::table('preinscrit as p')
            ->leftJoin('offre as o', 'p.IDOffre', '=', 'o.IDOffre')
            ->leftJoin('session as sess', 'o.IDSession', '=', 'sess.IDSession')
            ->leftJoin('semestre_formation as sf', 'sess.IDSemestre_formation', '=', 'sf.IDSemestre_formation')
            ->leftJoin('annee_formation as af', 'sf.IDAnnee_Formation', '=', 'af.IDAnnee_Formation')
            ->select('sf.IDAnnee_Formation', 'af.Nom', DB::raw('COUNT(*) as count'))
            ->groupBy('sf.IDAnnee_Formation', 'af.Nom')
            ->orderBy('sf.IDAnnee_Formation', 'desc')
            ->get();

        echo "<h2>Distribution by Training Year:</h2>";
        echo "<table border='1' cellpadding='10'>";
        echo "<tr><th>Year ID</th><th>Year Name</th><th>Count</th></tr>";
        foreach ($stats as $row) {
            echo "<tr>";
            echo "<td>" . ($row->IDAnnee_Formation ?? 'NULL') . "</td>";
            echo "<td>" . ($row->Nom ?? 'NULL') . "</td>";
            echo "<td>" . $row->count . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
}
