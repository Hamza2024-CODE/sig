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
    echo "=== فحص جدول ets_form للمعرف 2033 ===\n";
    echo "==================================================\n";

    $ets_by_id = DB::selectOne("SELECT * FROM ets_form WHERE IDEts_Form = 2033");
    if ($ets_by_id) {
        echo "البحث بـ IDEts_Form = 2033:\n";
        foreach ((array)$ets_by_id as $k => $v) {
            echo "  $k: " . ($v ?? 'NULL') . "\n";
        }
    } else {
        echo "لا يوجد صف في ets_form بالمعرف IDEts_Form = 2033\n";
    }

    $ets_by_etab = DB::selectOne("SELECT * FROM ets_form WHERE IDetablissement = 2033");
    if ($ets_by_etab) {
        echo "\nالبحث بـ IDetablissement = 2033:\n";
        foreach ((array)$ets_by_etab as $k => $v) {
            echo "  $k: " . ($v ?? 'NULL') . "\n";
        }
    } else {
        echo "لا يوجد صف في ets_form بالمعرف IDetablissement = 2033\n";
    }

    echo "\n==================================================\n";
    echo "=== فحص جدول ets_form للمراكز 45 و 48 ===\n";
    echo "==================================================\n";

    $ets_45 = DB::select("SELECT * FROM ets_form WHERE IDEts_Form = 45 OR IDetablissement = 45");
    foreach ($ets_45 as $e) {
        echo "IDEts_Form: " . $e->IDEts_Form . " | IDetablissement: " . $e->IDetablissement . " | Nom: " . $e->Nom . "\n";
    }

    $ets_48 = DB::select("SELECT * FROM ets_form WHERE IDEts_Form = 48 OR IDetablissement = 48");
    foreach ($ets_48 as $e) {
        echo "IDEts_Form: " . $e->IDEts_Form . " | IDetablissement: " . $e->IDetablissement . " | Nom: " . $e->Nom . "\n";
    }







} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

