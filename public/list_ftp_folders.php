<?php
header('Content-Type: text/plain; charset=utf-8');

$ftpDir = '/www/wwwroot/hamzaftp';
if (is_dir($ftpDir)) {
    echo "=== DIRECTORIES IN hamzaftp ===\n";
    $files = scandir($ftpDir);
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            $fullPath = $ftpDir . '/' . $file;
            if (is_dir($fullPath)) {
                echo "[DIR]  " . $file . "\n";
                // List files inside
                $subFiles = scandir($fullPath);
                $count = 0;
                foreach ($subFiles as $sf) {
                    if ($sf !== '.' && $sf !== '..') {
                        $count++;
                    }
                }
                echo "       Contains: {$count} items\n";
            } else {
                echo "[FILE] " . $file . "\n";
            }
        }
    }
} else {
    echo "Directory not found: $ftpDir\n";
}
