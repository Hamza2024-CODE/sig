<?php
/**
 * fix_https_env.php
 * يُصلح APP_URL في ملف .env ليستخدم https للنطاق الجديد
 * شغّله مرة واحدة على السيرفر ثم احذفه
 */

$envFile = __DIR__ . '/../.env';

if (!file_exists($envFile)) {
    die("❌ ملف .env غير موجود!");
}

$content = file_get_contents($envFile);
$currentUrl = '';

// استخراج APP_URL الحالي
if (preg_match('/^APP_URL=(.+)$/m', $content, $matches)) {
    $currentUrl = trim($matches[1]);
}

echo "<pre style='font-family:monospace;direction:ltr;'>";
echo "APP_URL الحالي: <b>{$currentUrl}</b>\n\n";

// التحقق من البروتوكول الجديد
$newUrl = 'https://test.tassyir.dz';

if (strpos($currentUrl, $newUrl) !== false) {
    echo "✅ APP_URL صحيح بالفعل: {$currentUrl}\n";
} else {
    // استبدال APP_URL
    $updated = preg_replace('/^APP_URL=.+$/m', 'APP_URL=' . $newUrl, $content);
    if ($updated !== $content) {
        file_put_contents($envFile, $updated);
        echo "✅ تم تحديث APP_URL من:\n   {$currentUrl}\n   إلى:\n   {$newUrl}\n\n";
    } else {
        // إضافته إذا لم يكن موجوداً
        file_put_contents($envFile, $content . "\nAPP_URL={$newUrl}\n");
        echo "✅ تمت إضافة APP_URL={$newUrl}\n";
    }
}

// التحقق من ASSET_URL
if (preg_match('/^ASSET_URL=(.+)$/m', $content, $matches2)) {
    $currentAsset = trim($matches2[1]);
    echo "ASSET_URL الحالي: {$currentAsset}\n";
    if ($currentAsset !== $newUrl) {
        $content2 = file_get_contents($envFile);
        $updated2 = preg_replace('/^ASSET_URL=.+$/m', 'ASSET_URL=' . $newUrl, $content2);
        file_put_contents($envFile, $updated2);
        echo "✅ تم تحديث ASSET_URL إلى: {$newUrl}\n";
    }
} else {
    // إضافة ASSET_URL
    $content2 = file_get_contents($envFile);
    file_put_contents($envFile, $content2 . "\nASSET_URL={$newUrl}\n");
    echo "✅ تمت إضافة ASSET_URL={$newUrl}\n";
}

// مسح الكاش بعد التعديل
echo "\n🔄 جاري مسح كاش التطبيق...\n";
$commands = [
    'cd ' . escapeshellarg(dirname(__DIR__)) . ' && php artisan config:clear 2>&1',
    'cd ' . escapeshellarg(dirname(__DIR__)) . ' && php artisan cache:clear 2>&1',
    'cd ' . escapeshellarg(dirname(__DIR__)) . ' && php artisan view:clear 2>&1',
];
foreach ($commands as $cmd) {
    $out = shell_exec($cmd);
    echo htmlspecialchars($out ?? '(no output)') . "\n";
}

echo "\n✅ الإصلاح اكتمل — لا تنسَ حذف هذا الملف من السيرفر بعد الانتهاء!";
echo "</pre>";
