<?php
header('Content-Type: text/html; charset=utf-8');

define('LARAVEL_START', microtime(true));
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "<!DOCTYPE html>
<html lang='ar' dir='rtl'>
<head>
    <meta charset='UTF-8'>
    <title>المؤسسات غير الناشطة - تيزي وزو</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background-color: #f8fafc; color: #1e293b; }
        h2 { color: #482b8f; border-bottom: 2px solid #e2e8f0; padding-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 40px; background: white; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-radius: 8px; overflow: hidden; }
        th, td { padding: 12px 15px; text-align: right; border-bottom: 1px solid #e2e8f0; }
        th { background-color: #482b8f; color: white; }
        tr:hover { background-color: #f1f5f9; }
        .badge { background: #e2e8f0; padding: 4px 8px; border-radius: 4px; font-size: 0.85em; }
    </style>
</head>
<body>

    <h1>📋 تقرير المؤسسات غير الناشطة لولاية تيزي وزو</h1>";

try {
    // 1. No Active Trainees Query
    $sql1 = "
        SELECT e.IDetablissement, e.Code, e.Nom, e.NomFr
        FROM etablissement e
        WHERE e.IDDFEP = 15
          AND e.IDetablissement NOT IN (
              SELECT DISTINCT o.IDEts_Form
              FROM apprenant a
              INNER JOIN section s ON a.IDsection = s.IDSection
              INNER JOIN session sess ON s.IDSession = sess.IDSession
              INNER JOIN offre o ON s.IDOffre = o.IDOffre
              INNER JOIN specialite sp ON o.IDSpecialite = sp.IDSpecialite
              LEFT JOIN apprenant_fin af ON af.IDapprenant = a.IDapprenant
              WHERE a.statut = 'actif'
                AND af.IDapprenant IS NULL
                AND DATE_ADD(sess.DateD, INTERVAL COALESCE(NULLIF(sp.dureeM, 0), sp.NbrSem * 6, 24) MONTH) >= CURRENT_DATE()
          )
        ORDER BY e.Nom ASC
    ";
    $res1 = DB::select($sql1);

    echo "<h2>1. مؤسسات ليس لديها متربصين نشطين حالياً (" . count($res1) . " مؤسسة)</h2>";
    echo "<table>
            <thead>
                <tr>
                    <th>المعرف ID</th>
                    <th>الرمز Code</th>
                    <th>اسم المؤسسة (Ar)</th>
                    <th>Nom Etablissement (Fr)</th>
                </tr>
            </thead>
            <tbody>";
    if (empty($res1)) {
        echo "<tr><td colspan='4' style='text-align:center;'>لا توجد مؤسسات</td></tr>";
    } else {
        foreach ($res1 as $row) {
            echo "<tr>
                    <td>{$row->IDetablissement}</td>
                    <td><span class='badge'>{$row->Code}</span></td>
                    <td><b>{$row->Nom}</b></td>
                    <td>{$row->NomFr}</td>
                  </tr>";
        }
    }
    echo "</tbody></table>";

    // 2. No Programmed Offers Query
    $sql2 = "
        SELECT e.IDetablissement, e.Code, e.Nom, e.NomFr
        FROM etablissement e
        WHERE e.IDDFEP = 15
          AND e.IDetablissement NOT IN (
              SELECT DISTINCT o.IDEts_Form
              FROM offre o
          )
        ORDER BY e.Nom ASC
    ";
    $res2 = DB::select($sql2);

    echo "<h2>2. مؤسسات ليس لديها أي عروض تكوين مبرمجة في النظام (" . count($res2) . " مؤسسة)</h2>";
    echo "<table>
            <thead>
                <tr>
                    <th>المعرف ID</th>
                    <th>الرمز Code</th>
                    <th>اسم المؤسسة (Ar)</th>
                    <th>Nom Etablissement (Fr)</th>
                </tr>
            </thead>
            <tbody>";
    if (empty($res2)) {
        echo "<tr><td colspan='4' style='text-align:center;'>لا توجد مؤسسات</td></tr>";
    } else {
        foreach ($res2 as $row) {
            echo "<tr>
                    <td>{$row->IDetablissement}</td>
                    <td><span class='badge'>{$row->Code}</span></td>
                    <td><b>{$row->Nom}</b></td>
                    <td>{$row->NomFr}</td>
                  </tr>";
        }
    }
    echo "</tbody></table>";

} catch (\Exception $e) {
    echo "<p style='color:red;'>حدث خطأ أثناء جلب البيانات: " . $e->getMessage() . "</p>";
}

echo "</body></html>";
