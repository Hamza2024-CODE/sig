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
    echo "=== البحث عن مؤسسات في ولاية الجزائر (16) ذات صلة ===\n";
    echo "==================================================\n";

    $etabs = DB::select("
        SELECT IDetablissement, Nom, NomFr, IDDFEP
        FROM etablissement
        WHERE IDDFEP IN (
            SELECT IDDFEP FROM dfep WHERE IDWilayaa = 16
        ) AND (Nom LIKE '%رويبة%' OR Nom LIKE '%طاية%' OR Nom LIKE '%كيفان%' OR Nom LIKE '%برج%')
    ");

    foreach ($etabs as $et) {
        echo "معرف: " . $et->IDetablissement . " | الاسم: " . $et->Nom . " (Wilaya 16)\n";
    }

    echo "\n==================================================\n";
    echo "=== هل هناك أي متربصين آخرين في نفس القسم 191517؟ ===\n";
    echo "==================================================\n";

    $all_in_sec = DB::select("
        SELECT a.IDapprenant, c.Nom, c.Prenom, c.LieuNais, c.IDWilayaa
        FROM apprenant a
        LEFT JOIN candidat c ON a.IDCandidat = c.IDCandidat
        WHERE a.IDSection = 191517
    ");

    echo "إجمالي المسجلين في القسم 191517: " . count($all_in_sec) . "\n";
    foreach ($all_in_sec as $idx => $st) {
        echo ($idx + 1) . ". #" . $st->IDapprenant . " | " . $st->Nom . " " . $st->Prenom . " (مكان الميلاد: " . $st->LieuNais . " | ولاية الإقامة: " . $st->IDWilayaa . ")\n";
    }





} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

