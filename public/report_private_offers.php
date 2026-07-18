<?php
// report_private_offers.php - تقرير كامل لعروض ومتربصي المؤسسات الخاصة
header('Content-Type: text/html; charset=utf-8');

try {
    $envFile = __DIR__ . '/../.env';
    $env = [];
    if (file_exists($envFile)) {
        foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            if (strpos(trim($line), '#') === 0 || strpos($line, '=') === false) continue;
            [$k, $v] = explode('=', $line, 2);
            $env[trim($k)] = trim($v, " \t\n\r\"'");
        }
    }

    $dbHost = $env['DB_HOST'] ?? '127.0.0.1';
    $dbPort = $env['DB_PORT'] ?? '3306';
    $dbName = $env['DB_DATABASE'] ?? 'sgfep_windev';
    $dbUser = $env['DB_USERNAME'] ?? 'root';
    $dbPass = $env['DB_PASSWORD'] ?? '';

    $db = new PDO(
        "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4",
        $dbUser, $dbPass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );

    // Query for all private institutions (PublPrive = 1)
    $sql = "
        SELECT 
            parent.IDetablissement AS Parent_ID, 
            parent.Nom AS Parent_Name, 
            p.IDetablissement AS Private_ID, 
            p.Nom AS Private_Name, 
            p.nomUser AS Private_User, 
            o.IDOffre, 
            s.Nom AS Specialite, 
            COUNT(DISTINCT sec.IDSection) AS Nb_Sections, 
            COUNT(DISTINCT a.IDapprenant) AS Nb_Apprenants 
        FROM etablissement p 
        LEFT JOIN etablissement parent ON parent.IDetablissement = IF( IFNULL(p.DeIDetablissementRatacheInsfp,0) > 0, p.DeIDetablissementRatacheInsfp, p.DeIDetablissementRatache ) 
        LEFT JOIN offre o ON o.IDEts_Form = p.IDetablissement 
        LEFT JOIN specialite s ON s.IDSpecialite = o.IDSpecialite 
        LEFT JOIN section sec ON sec.IDOffre = o.IDOffre 
        LEFT JOIN apprenant a ON a.IDSection = sec.IDSection 
        WHERE p.PublPrive = 1
        GROUP BY parent.IDetablissement, parent.Nom, p.IDetablissement, p.Nom, p.nomUser, o.IDOffre, s.Nom
        ORDER BY parent.Nom ASC, p.Nom ASC, o.IDOffre DESC
    ";

    $stmt = $db->query($sql);
    $rows = $stmt->fetchAll();

} catch (Throwable $e) {
    die("خطأ في الاتصال بقاعدة البيانات: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تقرير عروض ومتربصي المؤسسات الخاصة</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Cairo', sans-serif;
            background-color: #f1f5f9;
            margin: 20px;
            color: #334155;
        }
        h2 {
            text-align: center;
            color: #1e293b;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
        }
        .meta-info {
            text-align: center;
            margin-bottom: 20px;
            font-size: 0.95rem;
            color: #64748b;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            font-size: 0.88rem;
        }
        th, td {
            border: 1px solid #e2e8f0;
            padding: 10px;
            text-align: right;
        }
        th {
            background-color: #f8fafc;
            color: #475569;
            font-weight: 700;
        }
        tr:nth-child(even) {
            background-color: #f8fafc;
        }
        tr:hover {
            background-color: #f1f5f9;
        }
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: bold;
        }
        .badge-parent {
            background-color: #e0f2fe;
            color: #0369a1;
        }
        .badge-private {
            background-color: #f0fdf4;
            color: #166534;
        }
        .badge-none {
            background-color: #fee2e2;
            color: #991b1b;
        }
        .export-btn {
            display: inline-block;
            background: #2563eb;
            color: #fff;
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: bold;
            margin-bottom: 15px;
        }
        .export-btn:hover {
            background: #1d4ed8;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>تقرير عروض ومتربصي المؤسسات الخاصة</h2>
    <div class="meta-info">
        إجمالي الأسطر المستخرجة: <strong><?= count($rows) ?></strong> سجل.
    </div>

    <a href="?export=csv" class="export-btn">تحميل كملف CSV</a>

    <table>
        <thead>
            <tr>
                <th>المؤسسة الأم (المشرفة)</th>
                <th>المؤسسة الخاصة (المنفذة)</th>
                <th>اسم المستخدم (الخاصة)</th>
                <th>رمز العرض (IDOffre)</th>
                <th>التخصص</th>
                <th>الأقسام (Sections)</th>
                <th>المتربصين (Apprenants)</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($rows)): ?>
                <tr>
                    <td colspan="7" style="text-align: center; color: #94a3b8;">لا توجد بيانات متاحة حالياً.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td>
                            <?php if ($r['Parent_Name']): ?>
                                <span class="badge badge-parent"><?= htmlspecialchars($r['Parent_Name']) ?> (ID: <?= $r['Parent_ID'] ?>)</span>
                            <?php else: ?>
                                <span class="badge badge-none">غير مربوط بمؤسسة أم ⚠️</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge badge-private"><?= htmlspecialchars($r['Private_Name']) ?> (ID: <?= $r['Private_ID'] ?>)</span>
                        </td>
                        <td><code><?= htmlspecialchars($r['Private_User']) ?></code></td>
                        <td><?= $r['IDOffre'] ? htmlspecialchars($r['IDOffre']) : '<span style="color:#94a3b8;">بدون عروض</span>' ?></td>
                        <td><?= $r['Specialite'] ? htmlspecialchars($r['Specialite']) : '—' ?></td>
                        <td style="text-align: center; font-weight: bold;"><?= htmlspecialchars($r['Nb_Sections']) ?></td>
                        <td style="text-align: center; font-weight: bold; color: #2563eb;"><?= htmlspecialchars($r['Nb_Apprenants']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>
<?php
// Handle CSV export request
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    ob_end_clean();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="rapport_offres_privees.csv"');
    
    $output = fopen('php://output', 'w');
    // Add UTF-8 BOM for proper excel reading
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Header
    fputcsv($output, [
        'Parent_ID', 'Parent_Name', 
        'Private_ID', 'Private_Name', 'Private_User', 
        'IDOffre', 'Specialite', 
        'Nb_Sections', 'Nb_Apprenants'
    ]);
    
    foreach ($rows as $r) {
        fputcsv($output, [
            $r['Parent_ID'], $r['Parent_Name'],
            $r['Private_ID'], $r['Private_Name'], $r['Private_User'],
            $r['IDOffre'], $r['Specialite'],
            $r['Nb_Sections'], $r['Nb_Apprenants']
        ]);
    }
    fclose($output);
    exit;
}
?>
