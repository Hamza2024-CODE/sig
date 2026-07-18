<?php
/**
 * fix_https_env.php
 * يُصلح APP_URL و ASSET_URL في ملف .env للنطاق الجديد test.tassyir.dz
 * شغّله مرة واحدة عبر المتصفح ثم احذفه للأمان
 */

$envFile = dirname(__DIR__) . '/.env';

if (!file_exists($envFile)) {
    die("<p style='color:red'>❌ ملف .env غير موجود في: " . htmlspecialchars($envFile) . "</p>");
}

$content = file_get_contents($envFile);

echo "<!DOCTYPE html><html dir='rtl' lang='ar'><head><meta charset='UTF-8'>";
echo "<title>إصلاح HTTPS</title>";
echo "<style>body{font-family:monospace;padding:20px;direction:rtl;} .ok{color:#16a34a} .err{color:#dc2626} .warn{color:#d97706} .box{background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:16px;margin:8px 0;}</style></head><body>";
echo "<h2>🔧 إصلاح إعدادات HTTPS — النطاق: test.tassyir.dz</h2><hr>";

$newAppUrl   = 'https://test.tassyir.dz';
$newAssetUrl = 'https://test.tassyir.dz';
$changed = false;

// ── 1. قراءة القيم الحالية ──────────────────────────────────────────
preg_match('/^APP_URL=(.*)$/m', $content, $mApp);
preg_match('/^ASSET_URL=(.*)$/m', $content, $mAsset);
$currentApp   = trim($mApp[1]   ?? '(غير موجود)');
$currentAsset = trim($mAsset[1] ?? '(غير موجود)');

echo "<div class='box'>";
echo "<b>القيم الحالية:</b><br>";
echo "APP_URL   = <span>" . htmlspecialchars($currentApp)   . "</span><br>";
echo "ASSET_URL = <span>" . htmlspecialchars($currentAsset) . "</span>";
echo "</div>";

// ── 2. إصلاح APP_URL (إزالة trailing slash) ─────────────────────────
$targetApp = rtrim($newAppUrl, '/');
$cleanedApp = rtrim($currentApp, '/');
if ($cleanedApp !== $targetApp) {
    if (preg_match('/^APP_URL=/m', $content)) {
        $content = preg_replace('/^APP_URL=.*$/m', 'APP_URL=' . $targetApp, $content);
    } else {
        $content .= "\nAPP_URL={$targetApp}";
    }
    echo "<p class='ok'>✅ APP_URL: <b>{$currentApp}</b> → <b>{$targetApp}</b></p>";
    $changed = true;
} else {
    echo "<p class='ok'>✅ APP_URL صحيح بالفعل: {$currentApp}</p>";
}

// ── 3. إصلاح ASSET_URL — المشكلة الرئيسية ───────────────────────────
// ASSET_URL=/ يجعل asset() يستخدم البروتوكول الداخلي (http://)
// يجب تعيينه صراحةً لـ https://test.tassyir.dz
$targetAsset = $newAssetUrl;
if (rtrim($currentAsset, '/') !== $targetAsset) {
    if (preg_match('/^ASSET_URL=/m', $content)) {
        $content = preg_replace('/^ASSET_URL=.*$/m', 'ASSET_URL=' . $targetAsset, $content);
    } else {
        $content .= "\nASSET_URL={$targetAsset}";
    }
    echo "<p class='ok'>✅ ASSET_URL: <b>" . htmlspecialchars($currentAsset) . "</b> → <b>{$targetAsset}</b></p>";
    echo "<p class='warn'>⚠️ هذا كان سبب Mixed Content — ASSET_URL=/ يجعل روابط الـ assets تستخدم HTTP بدل HTTPS</p>";
    $changed = true;
} else {
    echo "<p class='ok'>✅ ASSET_URL صحيح بالفعل: {$currentAsset}</p>";
}

// ── 4. التحقق من SESSION_SECURE_COOKIE ──────────────────────────────
if (strpos($content, 'SESSION_SECURE_COOKIE=true') !== false) {
    echo "<p class='ok'>✅ SESSION_SECURE_COOKIE=true — صحيح لـ HTTPS</p>";
}

// ── 5. كتابة الملف ──────────────────────────────────────────────────
if ($changed) {
    $backup = $envFile . '.bak.' . date('YmdHis');
    copy($envFile, $backup);
    echo "<p class='ok'>📄 تم إنشاء نسخة احتياطية: " . htmlspecialchars(basename($backup)) . "</p>";

    if (file_put_contents($envFile, $content) !== false) {
        echo "<p class='ok'>✅ تم حفظ ملف .env بنجاح</p>";
    } else {
        echo "<p class='err'>❌ فشل في كتابة ملف .env — تحقق من الصلاحيات</p>";
    }
} else {
    echo "<p>لا توجد تغييرات ضرورية في .env</p>";
}

// ── 6. مسح كاش Laravel ──────────────────────────────────────────────
echo "<hr><h3>🔄 مسح كاش التطبيق...</h3>";
$artisan = dirname(__DIR__) . '/artisan';
$commands = [
    'config:clear' => 'مسح كاش الإعدادات',
    'cache:clear'  => 'مسح كاش التطبيق',
    'view:clear'   => 'مسح كاش الـ Views',
    'route:clear'  => 'مسح كاش الـ Routes',
];
foreach ($commands as $cmd => $label) {
    $out = shell_exec("php " . escapeshellarg($artisan) . " {$cmd} 2>&1");
    $ok = strpos($out ?? '', 'Successfully') !== false || strpos($out ?? '', 'cleared') !== false;
    echo "<p class='" . ($ok ? 'ok' : 'warn') . "'>• {$label}: " . htmlspecialchars(trim($out ?? '(لا مخرجات)')) . "</p>";
}

// ── 7. التحقق النهائي ───────────────────────────────────────────────
echo "<hr>";
$finalContent = file_get_contents($envFile);
preg_match('/^APP_URL=(.*)$/m', $finalContent, $fApp);
preg_match('/^ASSET_URL=(.*)$/m', $finalContent, $fAsset);
echo "<div class='box'>";
echo "<b>القيم بعد الإصلاح:</b><br>";
echo "APP_URL   = <b class='ok'>" . htmlspecialchars(trim($fApp[1]   ?? '?')) . "</b><br>";
echo "ASSET_URL = <b class='ok'>" . htmlspecialchars(trim($fAsset[1] ?? '?')) . "</b>";
echo "</div>";

echo "<p class='err'><b>⚠️ مهم: احذف هذا الملف بعد الانتهاء!</b><br>";
echo "<code>rm public/fix_https_env.php</code></p>";

echo "</body></html>";
