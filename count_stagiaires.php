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
    // Search for all matching establishments containing 'سلطان'
    $etabs = DB::select("
        SELECT IDetablissement as id, Nom as name 
        FROM etablissement 
        WHERE Nom LIKE '%سلطان%'
    ");

    if (empty($etabs)) {
        echo "لم يتم العثور على أي مؤسسة تحتوي على اسم 'سلطان' في قاعدة البيانات\n";
        exit;
    }

    echo "\n==================================================\n";
    echo "=== قائمة المؤسسات المطابقة وإحصائيات المتربصين ===\n";
    echo "==================================================\n";

    foreach ($etabs as $etab) {
        // Count total active
        $total = DB::selectOne("
            SELECT COUNT(a.IDapprenant) as total
            FROM apprenant a
            JOIN section s ON a.IDSection = s.IDSection
            JOIN offre o ON s.IDOffre = o.IDOffre
            LEFT JOIN apprenant_fin af ON af.IDapprenant = a.IDapprenant
            WHERE o.IDEts_Form = ? AND af.IDapprenant IS NULL
        ", [$etab->id]);

        echo "\n[معرف: " . $etab->id . "] " . $etab->name . "\n";
        echo "إجمالي المتربصين النشطين حالياً: " . ($total->total ?? 0) . " متربص\n";

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
        ", [$etab->id]);

        if (!empty($breakdown)) {
            echo "التفاصيل حسب الدورة:\n";
            foreach ($breakdown as $row) {
                echo "  - " . $row->session_nom . ": " . $row->count . " متربص نشط\n";
            }
        } else {
            echo "  (لا توجد دورات أو متربصون نشطون لهذه المؤسسة حالياً)\n";
        }
        echo "--------------------------------------------------\n";
    }

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
