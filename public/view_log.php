<?php
$logFile = __DIR__ . '/../storage/logs/laravel.log';
if (file_exists($logFile)) {
    $lines = file($logFile);
    $lastLines = array_slice($lines, -100);
    echo "<h1>Last 100 Lines of Laravel Log:</h1>";
    echo "<pre>" . htmlspecialchars(implode('', $lastLines)) . "</pre>";
} else {
    echo "Log file not found at: " . $logFile;
}
