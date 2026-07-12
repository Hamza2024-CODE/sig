<?php
header('Content-Type: text/plain; charset=utf-8');

// Enable error reporting to catch direct bootstrap errors
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "=== DIAGNOSING 500 SERVER ERROR ===\n";

$logFile = __DIR__ . '/../storage/logs/laravel.log';
if (file_exists($logFile)) {
    $lines = file($logFile);
    $lastLines = array_slice($lines, -40);
    echo "--- Last 40 Lines of laravel.log ---\n";
    echo implode("", $lastLines);
} else {
    echo "laravel.log not found at: $logFile\n";
}

echo "\n--- Testing BlockBannedIPs Middleware Syntax ---\n";
$middlewarePath = __DIR__ . '/../app/Http/Middleware/BlockBannedIPs.php';
if (file_exists($middlewarePath)) {
    $output = [];
    $returnVar = 0;
    exec("php -l " . escapeshellarg($middlewarePath), $output, $returnVar);
    echo "Syntax Check: " . implode("\n", $output) . " (Code: $returnVar)\n";
} else {
    echo "BlockBannedIPs.php not found at: $middlewarePath\n";
}
