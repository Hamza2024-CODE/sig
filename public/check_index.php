<?php
header('Content-Type: text/plain');
$file = __DIR__ . '/../resources/views/admin/sections/index.blade.php';
if (file_exists($file)) {
    $lines = file($file);
    $last_lines = array_slice($lines, -50);
    echo "Total lines: " . count($lines) . "\n";
    echo implode("", $last_lines);
} else {
    echo "File not found!";
}
