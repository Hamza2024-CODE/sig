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
    echo "\n==================================================\n";
    echo "=== البحث عن جميع المؤسسات التي تحتوي على 'سلطان' ===\n";
    echo "==================================================\n";

    $etabs = DB::select("
        SELECT e.IDetablissement, e.Nom, e.NomFr, e.IDEts_Form, w.Nom as WilayaNom
        FROM etablissement e
        LEFT JOIN wilaya w ON e.IDDFEP = w.IDWilayaa
        WHERE e.Nom LIKE '%سلطان%'
    ");

    foreach ($etabs as $et) {
        echo "معرف: " . $et->IDetablissement . " | الاسم: " . $et->Nom . " | تابع لـ: " . ($et->IDEts_Form ?: 'لا يوجد') . " | الولاية: " . $et->WilayaNom . "\n";
    }

    echo "\n==================================================\n";
    echo "\n==================================================\n";
    echo "=== إحصائيات مركز عين طاية (معرف 45) ومركز برج الكيفان (معرف 48) ===\n";
    echo "==================================================\n";

    // Count trainees for Ain Taya (45)
    $total_45 = DB::selectOne("
        SELECT COUNT(a.IDapprenant) as total
        FROM apprenant a
        JOIN section s ON a.IDSection = s.IDSection
        JOIN offre o ON s.IDOffre = o.IDOffre
        LEFT JOIN apprenant_fin af ON af.IDapprenant = a.IDapprenant
        WHERE o.IDEts_Form = 45 AND af.IDapprenant IS NULL
    ");
    echo "إجمالي المتربصين النشطين في مركز عين طاية (45): " . ($total_45->total ?? 0) . "\n";

    // Count trainees for Bordj El Kiffan (48)
    $total_48 = DB::selectOne("
        SELECT COUNT(a.IDapprenant) as total
        FROM apprenant a
        JOIN section s ON a.IDSection = s.IDSection
        JOIN offre o ON s.IDOffre = o.IDOffre
        LEFT JOIN apprenant_fin af ON af.IDapprenant = a.IDapprenant
        WHERE o.IDEts_Form = 48 AND af.IDapprenant IS NULL
    ");
    echo "إجمالي المتربصين النشطين في مركز برج الكيفان (48): " . ($total_48->total ?? 0) . "\n";

    echo "\n==================================================\n";
    echo "=== عروض تخصص 'أمين مخزن' المسجلة لمركز عين طاية (45) ===\n";
    echo "==================================================\n";

    $offres_45 = DB::select("
        SELECT o.IDOffre, o.IDSpecialite, sp.Nom as SpecialiteNom, o.IDEts_Form, o.SessionNum, s.IDSession
        FROM offre o
        JOIN specialite sp ON o.IDSpecialite = sp.IDSpecialite
        LEFT JOIN section s ON s.IDOffre = o.IDOffre
        WHERE o.IDEts_Form = 45 AND sp.Nom LIKE '%مخزن%'
    ");

    if (empty($offres_45)) {
        echo "لا توجد عروض تخصص 'أمين مخزن' تحت مركز عين طاية (45).\n";
    } else {
        foreach ($offres_45 as $of) {
            echo "عرض: " . $of->IDOffre . " | التخصص: " . $of->SpecialiteNom . " (معرف القسم: " . ($of->IDSession ?: 'لا يوجد') . ")\n";
        }
    }






} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

