<?php
header('Content-Type: text/plain; charset=utf-8');
$file = __DIR__ . '/../app/Http/Controllers/Admin/ModulesController.php';
if (!file_exists($file)) {
    die("File not found at $file\n");
}
$lines = file($file);
echo "File has " . count($lines) . " lines.\n";
foreach ($lines as $num => $line) {
    if (stripos($line, 'reconduits') !== false) {
        echo "Line " . ($num + 1) . ": " . trim($line) . "\n";
    }
}
