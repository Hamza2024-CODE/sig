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
    // Trainee IDs to inspect
    $ids = [5231796, 5231797, 5231798, 5231799, 5231800, 5231801, 5231802, 5231803, 5231804, 5231806, 5231808, 5231809, 5231812, 5231813];

    echo "\n==================================================\n";
    echo "=== تفاصيل المتربصين الـ 14 وتحديد مركزهم الفعلي ===\n";
    echo "==================================================\n";

    $results = DB::select("
        SELECT 
            a.IDapprenant, 
            c.Nom, 
            c.Prenom, 
            c.LieuNais,
            o.IDOffre, 
            o.IDEts_Form, 
            e.Nom as EtabNom, 
            e.NomFr as EtabNomFr,
            w.Nom as WilayaNom,
            sp.Nom as SpecialiteNom
        FROM apprenant a
        JOIN section s ON a.IDSection = s.IDSection
        JOIN offre o ON s.IDOffre = o.IDOffre
        JOIN etablissement e ON o.IDEts_Form = e.IDetablissement
        LEFT JOIN wilaya w ON e.IDDFEP = w.IDWilayaa
        LEFT JOIN candidat c ON a.IDCandidat = c.IDCandidat
        LEFT JOIN specialite sp ON o.IDSpecialite = sp.IDSpecialite
        WHERE a.IDapprenant IN (" . implode(',', $ids) . ")
    ");

    if (empty($results)) {
        echo "لم يتم العثور على هؤلاء المتربصين في قاعدة البيانات.\n";
    } else {
        foreach ($results as $r) {
            echo "متربص: #" . $r->IDapprenant . " | " . $r->Nom . " " . $r->Prenom . "\n";
            echo "  - مكان الميلاد: " . $r->LieuNais . "\n";
            echo "  - التخصص: " . $r->SpecialiteNom . "\n";
            echo "  - معرف المركز: " . $r->IDEts_Form . "\n";
            echo "  - اسم المركز: " . $r->EtabNom . " (" . $r->EtabNomFr . ")\n";
            echo "  - الولاية: " . $r->WilayaNom . "\n";
            echo "--------------------------------------------------\n";
        }
    }

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

