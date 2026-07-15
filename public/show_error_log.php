<?php
header('Content-Type: text/plain; charset=utf-8');
$logFile = __DIR__ . '/../storage/logs/laravel.log';
if (file_exists($logFile)) {
    $content = file_get_contents($logFile);
    // Find last log entry (Laravel log entries start with [YYYY-MM-DD)
    $pos = strrpos($content, '[2026-');
    if ($pos === false) {
        $pos = strrpos($content, '[2025-');
    }
    if ($pos !== false) {
        echo substr($content, $pos);
    } else {
        // Fallback to last 150 lines
        $lines = file($logFile);
        echo implode("", array_slice($lines, -150));
    }
} else {
    echo "Log file not found at: " . $logFile;
}
