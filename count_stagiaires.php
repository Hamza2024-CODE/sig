<?php

/**
 * Script to count active trainees in specific establishment (Ain Soltan, Saida)
 */

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    // Search for Ain Soltan in Saida (Saida Wilaya ID is 20)
    $etab = DB::selectOne("
        SELECT IDetablissement, Nom 
        FROM etablissement 
        WHERE Nom LIKE '%سلطان%' 
          AND (Nom LIKE '%سعيدة%' OR IDDFEP IN (SELECT IDDFEP FROM dfep WHERE IDWilayaa = 20))
    ");

    if (!$etab) {
        $etab = DB::selectOne("SELECT IDetablissement, Nom FROM etablissement WHERE Nom LIKE '%سلطان%'");
    }

    if (!$etab) {
        echo "لم يتم العثور على المؤسسة في قاعدة البيانات\n";
        exit;
    }

    echo "\n==================================================\n";
    echo "المؤسسة: " . $etab->Nom . " (معرف: " . $etab->IDetablissement . ")\n";
    echo "==================================================\n";

    // Count total active
    $total = DB::selectOne("
        SELECT COUNT(a.IDapprenant) as total
        FROM apprenant a
        JOIN section s ON a.IDSection = s.IDSection
        JOIN offre o ON s.IDOffre = o.IDOffre
        LEFT JOIN apprenant_fin af ON af.IDapprenant = a.IDapprenant
        WHERE o.IDEts_Form = ? AND af.IDapprenant IS NULL
    ", [$etab->IDetablissement]);

    echo "إجمالي المتربصين النشطين حالياً: " . ($total->total ?? 0) . " متربص\n\n";

    // Breakdown by session
    $breakdown = DB::select("
        SELECT sess.Nom as session_nom, COUNT(a.IDapprenant) as count
        FROM session sess
        JOIN section s ON s.IDSession = sess.IDSession
        JOIN offre o ON s.IDOffre = o.IDOffre
        JOIN apprenant a ON a.IDSection = s.IDSection
        LEFT JOIN apprenant_fin af ON af.IDapprenant = a.IDapprenant
        WHERE o.IDEts_Form = ? AND af.IDapprenant IS NULL
        GROUP BY sess.IDSession, sess.Nom, sess.DateD
        ORDER BY sess.DateD DESC
    ", [$etab->IDetablissement]);

    echo "التفاصيل حسب الدورة:\n";
    foreach ($breakdown as $row) {
        echo "- " . $row->session_nom . ": " . $row->count . " متربص نشط\n";
    }
    echo "==================================================\n\n";

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
