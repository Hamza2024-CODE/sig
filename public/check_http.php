<?php
header('Content-Type: text/plain; charset=utf-8');

ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "1. Testing file_get_contents with raw.githubusercontent.com:\n";
$url = 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/public/pull.php';
$content = file_get_contents($url);
if ($content === false) {
    echo "✗ file_get_contents failed.\n";
} else {
    echo "✓ file_get_contents succeeded! (length: " . strlen($content) . ")\n";
}

echo "\n2. Testing curl with raw.githubusercontent.com:\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
$res = curl_exec($ch);
if ($res === false) {
    echo "✗ curl failed: " . curl_error($ch) . "\n";
} else {
    echo "✓ curl succeeded! (length: " . strlen($res) . ")\n";
}
curl_close($ch);
