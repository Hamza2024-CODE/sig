<?php
header('Content-Type: text/plain; charset=utf-8');

echo "=== TELEGRAM BOT DIAGNOSTIC ===\n";

if (!function_exists('curl_init')) {
    die("✗ Error: cURL PHP extension is NOT enabled/installed on this server for PHP 8.3!\n");
}
echo "✓ cURL PHP extension is enabled.\n";

define('LARAVEL_START', microtime(true));
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$botToken = env('TELEGRAM_BOT_TOKEN');
$chatId   = env('TELEGRAM_CHAT_ID');

echo "Token configured: " . ($botToken ? "Yes" : "No") . "\n";
echo "Chat ID configured: " . ($chatId ? "Yes" : "No") . "\n";

if (!$botToken || !$chatId) {
    die("✗ Error: TELEGRAM_BOT_TOKEN or TELEGRAM_CHAT_ID is missing in .env!\n");
}

$url = "https://api.telegram.org/bot{$botToken}/getMe";
echo "Calling Telegram getMe API: $url\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$response = curl_exec($ch);
$err = curl_error($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($response === false) {
    echo "✗ cURL Error: " . $err . "\n";
} else {
    echo "✓ HTTP Code: " . $httpCode . "\n";
    echo "✓ Response: " . $response . "\n";
}
