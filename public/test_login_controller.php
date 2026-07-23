<?php
header('Content-Type: text/plain');
$pullPath = __DIR__ . '/pull.php';
echo "Pull path: {$pullPath}\n";
if (file_exists($pullPath)) {
    $lines = file($pullPath);
    echo "Total lines: " . count($lines) . "\n";
    echo "=== LINES 28 to 85 ===\n";
    for ($i = 27; $i < min(85, count($lines)); $i++) {
        echo ($i + 1) . ": " . $lines[$i];
    }
} else {
    echo "pull.php does not exist!\n";
}
