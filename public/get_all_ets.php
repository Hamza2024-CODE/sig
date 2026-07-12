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
    <title>مؤسسات ولاية تيزي وزو - حالة النشاط والحسابات</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background-color: #f8fafc; color: #1e293b; }
        h1 { color: #482b8f; border-bottom: 2px solid #e2e8f0; padding-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; background: white; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-radius: 8px; overflow: hidden; }
        th, td { padding: 12px 15px; text-align: right; border-bottom: 1px solid #e2e8f0; }
        th { background-color: #482b8f; color: white; }
        tr:hover { background-color: #f1f5f9; }
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 0.85em; font-weight: bold; }
        .bg-success-light { background-color: #d1fae5; color: #065f46; }
        .bg-danger-light { background-color: #fee2e2; color: #991b1b; }
        .bg-info-light { background-color: #e0f2fe; color: #075985; }
        .bg-warning-light { background-color: #fef3c7; color: #92400e; }
        .code-badge { background: #e2e8f0; color: #475569; }
    </style>
</head>
<body>

    <h1>📋 جميع مؤسسات ولاية تيزي وزو وحالة الحسابات والنشاط</h1>";

try {
    // 1. Fetch active ets IDs
    $activeEtsIds = DB::table('apprenant as a')
        ->join('section as s', 'a.IDsection', '=', 's.IDSection')
        ->join('session as sess', 's.IDSession', '=', 'sess.IDSession')
        ->join('offre as o', 's.IDOffre', '=', 'o.IDOffre')
        ->join('specialite as sp', 'o.IDSpecialite', '=', 'sp.IDSpecialite')
        ->leftJoin('apprenant_fin as af', 'af.IDapprenant', '=', 'a.IDapprenant')
        ->where('a.statut', 'actif')
        ->whereNull('af.IDapprenant')
        ->whereRaw("DATE_ADD(sess.DateD, INTERVAL COALESCE(NULLIF(sp.dureeM, 0), sp.NbrSem * 6, 24) MONTH) >= CURRENT_DATE()")
        ->distinct()
        ->pluck('o.IDEts_Form')
        ->toArray();

    // 2. Fetch all ets in Tizi Ouzou
    $etablissements = DB::table('etablissement')
        ->where('IDDFEP', 15)
        ->orderBy('Nom', 'ASC')
        ->get();

    echo "<table>
            <thead>
                <tr>
                    <th>المعرف ID</th>
                    <th>الرمز Code</th>
                    <th>اسم المؤسسة (Ar)</th>
                    <th>Nom Etablissement (Fr)</th>
                    <th>حالة الخدمة (في الخدمة / خارج الخدمة)</th>
                    <th>حالة الحساب (nomUser)</th>
                    <th>حالة النشاط (متربصين نشطين)</th>
                </tr>
            </thead>
            <tbody>";

    if ($etablissements->isEmpty()) {
        echo "<tr><td colspan='7' style='text-align:center;'>لا توجد مؤسسات في هذه الولاية</td></tr>";
    } else {
        foreach ($etablissements as $row) {
            // Check Account
            $hasAccount = !empty($row->nomUser);
            $accountBadge = $hasAccount 
                ? "<span class='badge bg-success-light'>لديه حساب ({$row->nomUser})</span>" 
                : "<span class='badge bg-danger-light'>ليس لديه حساب</span>";

            // Check Active Status (Trainees)
            $isActive = in_array($row->IDetablissement, $activeEtsIds);
            $activeBadge = $isActive 
                ? "<span class='badge bg-info-light'>نشط (يوجد متربصين)</span>" 
                : "<span class='badge bg-warning-light'>غير نشط (لا يوجد متربصين)</span>";

            // Check Service Status (activee = 0 -> in service, activee = 1 -> suspended/out of service)
            $isSuspended = ((int)($row->activee ?? 0) === 1);
            $serviceBadge = $isSuspended
                ? "<span class='badge bg-danger-light'>❌ خارج الخدمة (موقف/مجمد)</span>"
                : "<span class='badge bg-success-light'>✅ قيد الخدمة (مستمر)</span>";

            echo "<tr>
                    <td>{$row->IDetablissement}</td>
                    <td><span class='badge code-badge'>{$row->Code}</span></td>
                    <td><b>{$row->Nom}</b></td>
                    <td>{$row->NomFr}</td>
                    <td>{$serviceBadge}</td>
                    <td>{$accountBadge}</td>
                    <td>{$activeBadge}</td>
                  </tr>";
        }
    }
    echo "</tbody></table>";

} catch (\Exception $e) {
    echo "<p style='color:red;'>حدث خطأ أثناء جلب البيانات: " . $e->getMessage() . "</p>";
}

echo "</body></html>";
