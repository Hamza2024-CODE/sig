<?php
/**
 * set_credentials_pdo.php
 * يُحدِّث رموز المصالح السرية مباشرةً عبر PDO — لا يحتاج Laravel
 */

// ── قراءة .env مباشرةً ──────────────────────────────────────────────
$envFile = dirname(__DIR__) . '/.env';
$env = [];
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) continue;
        [$key, $val] = explode('=', $line, 2);
        $env[trim($key)] = trim($val, " \t\n\r\0\x0B\"'");
    }
}

$host = $env['DB_HOST']     ?? '127.0.0.1';
$port = $env['DB_PORT']     ?? '3306';
$db   = $env['DB_DATABASE'] ?? 'sgfep_windev';
$user = $env['DB_USERNAME'] ?? 'root';
$pass = $env['DB_PASSWORD'] ?? '';

// ── الاتصال بقاعدة البيانات ──────────────────────────────────────────
try {
    $pdo = new PDO(
        "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4",
        $user, $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die("❌ خطأ في الاتصال: " . $e->getMessage() . "\n");
}

// ── قائمة المصالح والرموز السرية (CSV standard) ──────────────────────
$updates = [
    // مستوى المديرية الولائية
    'SA'          => 'wonque',
    'SSFEP'       => 'driduv',
    'SFACI'       => 'devteW',
    'SAMF'        => 'wAn7Wa',
    'SAMRH'       => 'furwAn',
    'SSIP'        => 'keweLA',
    'AdmFin#Dfep' => 'LifabE',
    'DFEPS'       => 'driduv',
    // مديرو المؤسسات
    'DIRETS'      => 'phimEt',
    'DIRETSs'     => 'phimEt',
    'DIRETSp'     => 'phimEt',
    // التمهين
    'SDTPA'       => 'kYbyfu',
    'SDTPAs'      => 'kYbyfu',
    'SDTPAp'      => 'kYbyfu',
    // الحضوري
    'SDTPP'       => 'mEtflY',
    'SDTPPs'      => 'mEtflY',
    'SDTPPp'      => 'mEtflY',
    // التكوين المتواصل
    'SDTPC'       => 'SixikA',
    'SDTPCs'      => 'SixikA',
    'SDTPCp'      => 'SixikA',
    // التوجيه
    'BIAO'        => 'nAMUto',
    'BIAOs'       => 'nAMUto',
    'BIAOp'       => 'nAMUto',
    // الإدارة والمالية
    'SDARH'       => 'DEvibi',
    'SDAFM'       => 'qYPeze',
    'AdmFinEp'    => 'LifabE',
    // الشهادات
    'Dplm'        => 'quyCA6',
    'Dplms'       => 'quyCA6',
    'Dplmp'       => 'quyCA6',
    'dplmDir'     => 'quyCA6',
];

echo "Updating all department secret codes...\n";
echo str_repeat("─", 60) . "\n";

$updated = 0;
$notFound = 0;

$stmt = $pdo->prepare(
    "UPDATE utilisateur SET MotPass = ? WHERE NomUser = ?"
);

foreach ($updates as $username => $plainPassword) {
    $hash = password_hash($plainPassword, PASSWORD_BCRYPT, ['cost' => 12]);
    $stmt->execute([$hash, $username]);
    $affected = $stmt->rowCount();

    if ($affected > 0) {
        echo "✓ Updated '{$username}' → '{$plainPassword}'\n";
        $updated++;
    } else {
        // تحقق إذا كان المستخدم موجوداً
        $check = $pdo->prepare("SELECT COUNT(*) FROM utilisateur WHERE NomUser = ?");
        $check->execute([$username]);
        $count = (int)$check->fetchColumn();
        if ($count === 0) {
            echo "· Not found: '{$username}' (skip)\n";
            $notFound++;
        } else {
            echo "✓ Already up-to-date: '{$username}'\n";
            $updated++;
        }
    }
}

echo str_repeat("─", 60) . "\n";
echo "✅ Done! Updated: {$updated} | Not found: {$notFound}\n";
