<?php
header('Content-Type: text/plain; charset=utf-8');
$file = $_GET['file'] ?? 'resources/views/admin/grades/index.blade.php';
$start = isset($_GET['start']) ? (int)$_GET['start'] : 1;
$end = isset($_GET['end']) ? (int)$_GET['end'] : 100;

$filePath = __DIR__ . '/../' . $file;
if (!file_exists($filePath)) {
    echo "File does not exist: $filePath\n";
    exit;
}

$lines = file($filePath);
echo "=== VIEW FILE $file (Lines $start-$end) ===\n";
for ($i = $start; $i <= $end; $i++) {
    if (isset($lines[$i - 1])) {
        echo $i . ": " . $lines[$i - 1];
    }
}
