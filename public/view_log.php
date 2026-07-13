<?php
$logFile = __DIR__ . '/../storage/logs/laravel.log';
if (file_exists($logFile)) {
    $lines = file($logFile);
    // Reverse the lines so newest is at the top
    $lines = array_reverse($lines);
    $lastLines = array_slice($lines, 0, 150);
    echo "<h1>Laravel Log (Newest First):</h1>";
    echo "<pre>" . htmlspecialchars(implode('', $lastLines)) . "</pre>";
} else {
    echo "Log file not found at: " . $logFile;
}
