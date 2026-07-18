<?php
$envFile = dirname(__DIR__) . '/.env';
$env = [];
foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) continue;
    [$k, $v] = explode('=', $line, 2);
    $env[trim($k)] = trim($v, " \t\n\r\"'");
}
$pdo = new PDO(
    "mysql:host={$env['DB_HOST']};port={$env['DB_PORT']};dbname={$env['DB_DATABASE']};charset=utf8mb4",
    $env['DB_USERNAME'], $env['DB_PASSWORD'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

// ── التاج الأزرق ─────────────────────────────────────────────────────
$e = $pdo->query("
    SELECT e.IDEts_Form, e.IDetablissement, e.Nom, e.nomUser, e.MotDePass,
           e.IDNature_etsF, e.activee,
           n.IDNature, n.Nom as nature_nom
    FROM etablissement e
    LEFT JOIN nature_etsf n ON e.IDNature_etsF = n.IDNature_etsF
    WHERE e.IDEts_Form = 1300
    LIMIT 1
")->fetch(PDO::FETCH_ASSOC);

echo "=== مؤسسة التاج الأزرق ===\n";
echo "اسم المؤسسة : {$e['Nom']}\n";
echo "nomUser     : {$e['nomUser']}\n";
echo "MotDePass   : {$e['MotDePass']}\n";
echo "IDNature_etsF: {$e['IDNature_etsF']}\n";
echo "IDNature    : {$e['IDNature']}\n";
echo "Nature      : {$e['nature_nom']}\n";
echo "activee     : " . ($e['activee'] == 0 ? 'مفعّل' : 'معطّل') . "\n";

// ── الرموز السرية المتاحة لهذا النوع ─────────────────────────────────
echo "\n=== الرموز السرية (utilisateur لـ IDNature={$e['IDNature']}) ===\n";
$users = $pdo->query("SELECT NomUser, MotPass FROM utilisateur WHERE IDNature = {$e['IDNature']}")->fetchAll(PDO::FETCH_ASSOC);
if ($users) {
    foreach ($users as $u) {
        echo "NomUser={$u['NomUser']} | MotPass={$u['MotPass']}\n";
    }
} else {
    echo "لا توجد سجلات — سيتم البحث في IDNature=5,6,7,8 ...\n";
    // جرب كل IDNature
    for ($i = 1; $i <= 10; $i++) {
        $cnt = $pdo->query("SELECT COUNT(*) FROM utilisateur WHERE IDNature = $i")->fetchColumn();
        if ($cnt > 0) echo "  IDNature=$i → $cnt مستخدم\n";
    }
}

// ── اختبار كلمات المرور الشائعة ──────────────────────────────────────
echo "\n=== اختبار كلمة المرور ===\n";
$commonPasswords = ['dev106', '123456', 'admin', $e['nomUser'], '1300', '1301', 'changeme', 'password'];
$stored = $e['MotDePass'];
echo "Hash مخزّن: $stored\n";
foreach ($commonPasswords as $p) {
    if (password_verify($p, $stored) || $p === $stored) {
        echo "✅ كلمة المرور هي: $p\n";
        break;
    }
}

// ── عروض التاج الأزرق والمؤسسة المشرفة ──────────────────────────────
echo "\n=== المؤسسة العمومية المشرفة (IDEts_FormM) ===\n";
$o = $pdo->query("SELECT o.IDEts_FormM, e2.Nom as sup_nom, e2.NomFr as sup_fr FROM offre o LEFT JOIN etablissement e2 ON o.IDEts_FormM = e2.IDEts_Form WHERE o.IDEts_Form = 1300 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
echo "IDEts_FormM={$o['IDEts_FormM']} | {$o['sup_nom']}\n";
