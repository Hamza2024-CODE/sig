<?php
/**
 * reset_passwords_from_csv.php
 *
 * يعيد تعيين كلمات مرور المستخدمين والمؤسسات من ملف CSV
 * بدون تغيير قيم كلمات المرور — يعتمد على القيم الموجودة في الملف كما هي
 *
 * الجدول: utilisateur  → عمود MotPass  (كود سري)
 * الجدول: etablissement → عمود MotDePass (كلمة مرور المؤسسة)
 *
 * الاستخدام:
 *   php public/reset_passwords_from_csv.php            ← يعرض فقط (dry-run)
 *   php public/reset_passwords_from_csv.php --apply    ← يطبق التغييرات
 *   URL: /reset_passwords_from_csv.php?apply=1         ← يطبق عبر المتصفح
 *   URL: /reset_passwords_from_csv.php                 ← dry-run عبر المتصفح
 */

// ─── Security ───────────────────────────────────────────────────────────────
// Block direct web access in production — comment out if running via CLI only
$isCli = PHP_SAPI === 'cli';
if (!$isCli) {
    header('Content-Type: text/plain; charset=utf-8');
    // Optionally add an IP whitelist:
    // $allowedIps = ['127.0.0.1', '::1'];
    // if (!in_array($_SERVER['REMOTE_ADDR'] ?? '', $allowedIps)) {
    //     http_response_code(403); exit('Access denied');
    // }
}

// ─── Config ──────────────────────────────────────────────────────────────────
$csvFile = __DIR__ . '/../all_application_users - all_application_users.csv.csv';
$applyChanges = $isCli
    ? in_array('--apply', $argv ?? [])
    : (($_GET['apply'] ?? '0') === '1');

// ─── DB Connection ───────────────────────────────────────────────────────────
$envFile = __DIR__ . '/../.env';
$env = [];
foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) continue;
    [$k, $v] = explode('=', $line, 2);
    $env[trim($k)] = trim($v, " \t\n\r\"'");
}

try {
    $db = new PDO(
        "mysql:host={$env['DB_HOST']};port={$env['DB_PORT']};dbname={$env['DB_DATABASE']};charset=utf8mb4",
        $env['DB_USERNAME'],
        $env['DB_PASSWORD'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (Throwable $e) {
    die("❌ DB connection failed: " . $e->getMessage() . "\n");
}

// ─── CSV Parsing ─────────────────────────────────────────────────────────────
if (!file_exists($csvFile)) {
    die("❌ CSV file not found: $csvFile\n");
}

$handle = fopen($csvFile, 'r');
if (!$handle) {
    die("❌ Cannot open CSV file.\n");
}

$utilisateurRows = [];  // rows for table: utilisateur  (MotPass)
$etablissementRows = []; // rows for table: etablissement (MotDePass)
$skipped = 0;
$lineNum = 0;

while (($row = fgetcsv($handle, 4096, ',')) !== false) {
    $lineNum++;
    if ($lineNum === 1) continue; // skip header

    // Trim all values
    $row = array_map('trim', $row);

    $type     = $row[0] ?? '';
    $id       = $row[1] ?? '';
    $username = $row[2] ?? '';
    // $name  = $row[3] ?? '';  // not used for password reset
    $password = $row[4] ?? '';
    // $role  = $row[5] ?? '';  // not used
    // $active= $row[6] ?? '';  // not used

    if ($password === '' || $id === '') {
        $skipped++;
        continue;
    }

    if (str_starts_with($type, 'Utilisateur')) {
        $utilisateurRows[] = ['id' => (int)$id, 'username' => $username, 'password' => $password];
    } elseif (str_starts_with($type, 'Etablissement')) {
        $etablissementRows[] = ['id' => (int)$id, 'username' => $username, 'password' => $password];
    }
}
fclose($handle);

echo "══════════════════════════════════════════════════\n";
echo "   إعادة تعيين كلمات المرور من ملف CSV\n";
echo "══════════════════════════════════════════════════\n";
echo "الوضع: " . ($applyChanges ? "✅ التطبيق الفعلي (--apply)" : "🔍 معاينة فقط (dry-run) — أضف --apply لتطبيق") . "\n";
echo "عدد مستخدمي utilisateur: " . count($utilisateurRows) . "\n";
echo "عدد مؤسسات etablissement: " . count($etablissementRows) . "\n";
echo "صفوف متخطاة (بدون كلمة مرور أو ID): $skipped\n";
echo "──────────────────────────────────────────────────\n\n";

// ─── Reset UTILISATEUR passwords (MotPass) ───────────────────────────────────
echo "【1】 جدول utilisateur → عمود MotPass (الكود السري)\n";
echo "────────────────────────────────────────────────\n";

$utilisateurUpdated = 0;
$utilisateurNotFound = 0;
$utilisateurAlreadyOk = 0;
$utilisateurErrors = 0;

$stmtFetch = $db->prepare("SELECT IDUtilisateur, NomUser, MotPass FROM utilisateur WHERE IDUtilisateur = ?");
$stmtUpdate = $db->prepare("UPDATE utilisateur SET MotPass = ? WHERE IDUtilisateur = ?");

foreach ($utilisateurRows as $r) {
    try {
        $stmtFetch->execute([$r['id']]);
        $existing = $stmtFetch->fetch(PDO::FETCH_ASSOC);

        if (!$existing) {
            echo "  ⚠️  ID={$r['id']} ({$r['username']}) → غير موجود في قاعدة البيانات\n";
            $utilisateurNotFound++;
            continue;
        }

        $currentPass = $existing['MotPass'];
        $newPass = $r['password'];

        // Check if already correct (plain text match)
        if ($currentPass === $newPass) {
            $utilisateurAlreadyOk++;
            // echo "  ✓  ID={$r['id']} ({$r['username']}) → كلمة المرور صحيحة بالفعل\n";
            continue;
        }

        // Check if it's already bcrypt-hashed version of the same password
        if (str_starts_with($currentPass, '$2y$') && password_verify($newPass, $currentPass)) {
            $utilisateurAlreadyOk++;
            // echo "  ✓  ID={$r['id']} ({$r['username']}) → مُشفرة بـ bcrypt وصحيحة\n";
            continue;
        }

        // Need update
        $hashedNewPass = password_hash($newPass, PASSWORD_BCRYPT, ['cost' => 12]);
        if ($applyChanges) {
            $stmtUpdate->execute([$hashedNewPass, $r['id']]);
            echo "  ✅ ID={$r['id']} ({$r['username']}) → تم تحديث MotPass (مُشفر بـ bcrypt)\n";
        } else {
            echo "  🔄 ID={$r['id']} ({$r['username']}) → سيتم تحديث MotPass\n";
            echo "       الحالي: " . (strlen($currentPass) > 30 ? '[HASHED]' : $currentPass) . "\n";
            echo "       الجديد: {$newPass} (سيتم تشفيره)\n";
        }
        $utilisateurUpdated++;

    } catch (Throwable $e) {
        echo "  ❌ ID={$r['id']} ({$r['username']}) → خطأ: " . $e->getMessage() . "\n";
        $utilisateurErrors++;
    }
}

echo "\n  📊 الملخص - utilisateur:\n";
echo "     يحتاج تحديث: $utilisateurUpdated\n";
echo "     صحيح بالفعل: $utilisateurAlreadyOk\n";
echo "     غير موجود:   $utilisateurNotFound\n";
echo "     أخطاء:       $utilisateurErrors\n";

// ─── Reset Private Counterparts (ID 86 to 90) ────────────────────────────────
echo "\n【1.5】 تحديث الحسابات الخاصة (المؤسسات الخاصة) من نظيرتها العمومية\n";
echo "────────────────────────────────────────────────\n";

$privateUpdated = 0;
$privateAlreadyOk = 0;
$privateCounterparts = [
    86 => 24, // DIRETSp => DIRETS
    87 => 25, // BIAOp => BIAO
    88 => 26, // SDTPPp => SDTPP
    89 => 27, // SDTPAp => SDTPA
    90 => 28  // SDTPCp => SDTPC
];

$stmtFetchUserPass = $db->prepare("SELECT MotPass FROM utilisateur WHERE IDUtilisateur = ?");
$stmtUpdateUserPass = $db->prepare("UPDATE utilisateur SET MotPass = ? WHERE IDUtilisateur = ?");

foreach ($privateCounterparts as $privId => $pubId) {
    try {
        // Get the password that was set/updated for the public counterpart
        $stmtFetchUserPass->execute([$pubId]);
        $pubRow = $stmtFetchUserPass->fetch(PDO::FETCH_ASSOC);
        if (!$pubRow) continue;
        
        $pubPassHash = $pubRow['MotPass'];
        
        // Get the current password of the private counterpart
        $stmtFetchUserPass->execute([$privId]);
        $privRow = $stmtFetchUserPass->fetch(PDO::FETCH_ASSOC);
        if (!$privRow) continue;
        
        $privPassHash = $privRow['MotPass'];
        
        if ($privPassHash === $pubPassHash) {
            $privateAlreadyOk++;
            continue;
        }
        
        if ($applyChanges) {
            $stmtUpdateUserPass->execute([$pubPassHash, $privId]);
            echo "  ✅ تم تحديث الرمز السري للحساب الخاص ID={$privId} لينسخ نظيره العمومي ID={$pubId}\n";
        } else {
            echo "  🔄 سيتم تحديث الرمز السري للحساب الخاص ID={$privId} لينسخ نظيره العمومي ID={$pubId}\n";
        }
        $privateUpdated++;
    } catch (Throwable $e) {
        echo "  ❌ خطأ في تحديث الحساب الخاص ID={$privId}: " . $e->getMessage() . "\n";
    }
}

echo "\n  📊 الملخص - الحسابات الخاصة:\n";
echo "     يحتاج تحديث: $privateUpdated\n";
echo "     صحيح بالفعل: $privateAlreadyOk\n";


// ─── Reset ETABLISSEMENT passwords (MotDePass) ───────────────────────────────
echo "\n【2】 جدول etablissement → عمود MotDePass (كلمة مرور المؤسسة)\n";
echo "────────────────────────────────────────────────\n";

$etabUpdated = 0;
$etabNotFound = 0;
$etabAlreadyOk = 0;
$etabErrors = 0;

$stmtEtabFetch = $db->prepare("SELECT IDetablissement, nomUser, MotDePass FROM etablissement WHERE IDetablissement = ?");
$stmtEtabUpdate = $db->prepare("UPDATE etablissement SET MotDePass = ? WHERE IDetablissement = ?");

foreach ($etablissementRows as $r) {
    try {
        $stmtEtabFetch->execute([$r['id']]);
        $existing = $stmtEtabFetch->fetch(PDO::FETCH_ASSOC);

        if (!$existing) {
            echo "  ⚠️  ID={$r['id']} ({$r['username']}) → غير موجود في قاعدة البيانات\n";
            $etabNotFound++;
            continue;
        }

        $currentPass = $existing['MotDePass'];
        $newPass = $r['password'];

        // Already correct (plain text)
        if ($currentPass === $newPass) {
            $etabAlreadyOk++;
            continue;
        }

        // Already bcrypt-hashed and valid
        if (str_starts_with($currentPass, '$2y$') && password_verify($newPass, $currentPass)) {
            $etabAlreadyOk++;
            continue;
        }

        // Needs update
        $hashedNewPass = password_hash($newPass, PASSWORD_BCRYPT, ['cost' => 12]);
        if ($applyChanges) {
            $stmtEtabUpdate->execute([$hashedNewPass, $r['id']]);
            echo "  ✅ ID={$r['id']} ({$r['username']}) → تم تحديث MotDePass (مُشفر بـ bcrypt)\n";
        } else {
            echo "  🔄 ID={$r['id']} ({$r['username']}) → سيتم تحديث MotDePass\n";
            echo "       الحالي: " . (strlen($currentPass) > 30 ? '[HASHED]' : $currentPass) . "\n";
            echo "       الجديد: {$newPass} (سيتم تشفيره)\n";
        }
        $etabUpdated++;

    } catch (Throwable $e) {
        echo "  ❌ ID={$r['id']} ({$r['username']}) → خطأ: " . $e->getMessage() . "\n";
        $etabErrors++;
    }
}

echo "\n  📊 الملخص - etablissement:\n";
echo "     يحتاج تحديث: $etabUpdated\n";
echo "     صحيح بالفعل: $etabAlreadyOk\n";
echo "     غير موجود:   $etabNotFound\n";
echo "     أخطاء:       $etabErrors\n";

// ─── Final Summary ───────────────────────────────────────────────────────────
$totalUpdated = $utilisateurUpdated + $privateUpdated + $etabUpdated;
echo "\n══════════════════════════════════════════════════\n";
if ($applyChanges) {
    if ($totalUpdated > 0) {
        echo "✅ تم التحديث بنجاح: $totalUpdated سجل تم تعديل كلمة مرور\n";
    } else {
        echo "✅ لا توجد تغييرات مطلوبة — جميع كلمات المرور صحيحة بالفعل\n";
    }
} else {
    echo "🔍 dry-run انتهى — إجمالي السجلات التي ستتغير: $totalUpdated\n";
    echo "   لتطبيق التغييرات نفّذ:\n";
    echo "   php public/reset_passwords_from_csv.php --apply\n";
    echo "   أو عبر المتصفح: ?apply=1\n";
}
echo "══════════════════════════════════════════════════\n";
