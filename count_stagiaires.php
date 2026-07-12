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
    echo "=== فحص بيانات العرض #245807 بالكامل ===\n";
    echo "==================================================\n";

    $offre = DB::selectOne("SELECT * FROM offre WHERE IDOffre = 245807");
    if ($offre) {
        foreach ((array)$offre as $key => $value) {
            echo "$key: " . ($value ?? 'NULL') . "\n";
        }
    } else {
        echo "العرض غير موجود.\n";
    }

    echo "\n==================================================\n";
    echo "=== فحص بيانات القسم #191517 بالكامل ===\n";
    echo "==================================================\n";

    $section = DB::selectOne("SELECT * FROM section WHERE IDSection = 191517");
    if ($section) {
        foreach ((array)$section as $key => $value) {
            echo "$key: " . ($value ?? 'NULL') . "\n";
        }
    } else {
        echo "القسم غير موجود.\n";
    }




} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

