<?php
$logPath = __DIR__ . '/../storage/logs/laravel.log';
header('Content-Type: text/plain; charset=utf-8');
if (file_exists($logPath)) {
    $content = file_get_contents($logPath);
    $lines = explode("\n", $content);
    $lastLines = array_slice($lines, -100);
    echo implode("\n", $lastLines);
} else {
    echo "Log file not found at: $logPath";
}
