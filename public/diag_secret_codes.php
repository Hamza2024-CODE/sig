<?php
/**
 * diag_secret_codes.php — تشخيص رموز المصالح السرية
 */
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

// الرموز المتوقعة
$expectedCodes = [
    'SA'          => 'wonque',
    'SSFEP'       => 'driduv',
    'SFACI'       => 'devteW',
    'SAMF'        => 'wAn7Wa',
    'SAMRH'       => 'furwAn',
    'SSIP'        => 'keweLA',
    'AdmFin#Dfep' => 'LifabE',
    'DFEPS'       => 'driduv',
    'pedago#dfep' => null, // نتحقق من هاشه
];

echo "=== 1. رموز utilisateur في قاعدة البيانات ===\n";
$users = $pdo->query("SELECT IDNature, NomUser, MotPass FROM utilisateur ORDER BY IDNature, NomUser")->fetchAll(PDO::FETCH_ASSOC);
foreach ($users as $u) {
    $nomUser = $u['NomUser'];
    $hash = $u['MotPass'];
    $expected = $expectedCodes[$nomUser] ?? null;
    
    if ($expected !== null) {
        $valid = password_verify($expected, $hash);
        $status = $valid ? "✅ صحيح ({$expected})" : "❌ خاطئ — expected [{$expected}]";
    } else {
        $status = "ℹ️ (لا مقارنة)";
    }
    echo "  IDNature={$u['IDNature']} | NomUser={$nomUser} | {$status}\n";
}

// ── 2. تتبع السلسلة للدخول بـ 3100 (وهران) ────────────────────────────
echo "\n=== 2. سلسلة IDNature لـ DFEP وهران (nomUser=3100) ===\n";
$etab = $pdo->query("
    SELECT e.IDEts_Form, e.IDetablissement, e.nomUser, e.IDNature_etsF,
           n.IDNature, n.Nom as nature_nom
    FROM etablissement e
    LEFT JOIN nature_etsf n ON e.IDNature_etsF = n.IDNature_etsF
    WHERE LOWER(e.nomUser) = '3100'
    LIMIT 1
")->fetch(PDO::FETCH_ASSOC);

if ($etab) {
    echo "nomUser={$etab['nomUser']} | IDNature_etsF={$etab['IDNature_etsF']} → IDNature={$etab['IDNature']} ({$etab['nature_nom']})\n";
    $idNature = $etab['IDNature'];
    
    echo "\nمستخدمو utilisateur لـ IDNature={$idNature}:\n";
    $nu = $pdo->query("SELECT NomUser, MotPass FROM utilisateur WHERE IDNature = {$idNature}")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($nu as $u) {
        $expected = $expectedCodes[$u['NomUser']] ?? null;
        if ($expected) {
            $ok = password_verify($expected, $u['MotPass']);
            echo "  {$u['NomUser']}: " . ($ok ? "✅ {$expected}" : "❌ hash مخزّن لا يطابق '{$expected}'") . "\n";
        } else {
            echo "  {$u['NomUser']}: hash=" . substr($u['MotPass'], 0, 20) . "...\n";
        }
    }
} else {
    echo "❌ لم يتم العثور على المؤسسة بـ nomUser=3100\n";
    // ابحث بطريقة أخرى
    $dfeps = $pdo->query("SELECT IDEts_Form, nomUser, IDNature_etsF FROM etablissement WHERE IDNature_etsF IN (SELECT IDNature_etsF FROM nature_etsf WHERE IDNature=4) LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    echo "مؤسسات بـ IDNature=4:\n";
    foreach ($dfeps as $d) echo "  {$d['nomUser']} | IDNature_etsF={$d['IDNature_etsF']}\n";
}

// ── 3. اختبار رمز wonque مباشرةً ─────────────────────────────────────
echo "\n=== 3. اختبار مباشر للرمز wonque ===\n";
$saUser = $pdo->query("SELECT MotPass FROM utilisateur WHERE NomUser = 'SA' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
if ($saUser) {
    echo "MotPass(SA): " . $saUser['MotPass'] . "\n";
    echo "password_verify('wonque', hash): " . (password_verify('wonque', $saUser['MotPass']) ? "TRUE ✅" : "FALSE ❌") . "\n";
    // جرب بدون حالة
    echo "password_verify('WONQUE', hash): " . (password_verify('WONQUE', $saUser['MotPass']) ? "TRUE" : "FALSE") . "\n";
} else {
    echo "❌ لم يتم العثور على NomUser=SA في utilisateur\n";
}
