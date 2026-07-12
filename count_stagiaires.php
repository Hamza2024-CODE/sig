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
    echo "=== تفاصيل عروض التكوين لتخصص 'أمين مخزن' في مركز 2033 ===\n";
    echo "==================================================\n";

    echo "\n==================================================\n";
    echo "=== فحص بيانات المتربص #5231796 بالكامل في القاعدة ===\n";
    echo "==================================================\n";

    $apprenant = DB::selectOne("
        SELECT a.*, c.*
        FROM apprenant a
        LEFT JOIN candidat c ON a.IDCandidat = c.IDCandidat
        WHERE a.IDapprenant = 5231796
    ");

    if ($apprenant) {
        foreach ((array)$apprenant as $key => $value) {
            echo "$key: " . ($value ?? 'NULL') . "\n";
        }
    } else {
        echo "المتربص غير موجود.\n";
    }



} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

