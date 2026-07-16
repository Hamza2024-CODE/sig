<?php
header('Content-Type: text/plain; charset=utf-8');
$filePath = __DIR__ . '/../resources/views/admin/grades/index.blade.php';
if (!file_exists($filePath)) {
    echo "File does not exist!\n";
    exit;
}

$lines = file($filePath);
echo "=== VIEW FILE INDEX.BLADE.PHP (Lines 260-290) ===\n";
for ($i = 260; $i <= 290; $i++) {
    if (isset($lines[$i - 1])) {
        echo $i . ": " . $lines[$i - 1];
    }
}
