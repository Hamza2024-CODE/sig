<?php
// Secure view logs utility
header('Content-Type: text/plain; charset=utf-8');

$logFile = __DIR__ . '/../storage/logs/laravel.log';
if (file_exists($logFile)) {
    $lines = file($logFile);
    $lastLines = array_slice($lines, -100);
    echo implode("", $lastLines);
} else {
    echo "Laravel log file not found at " . $logFile;
}
