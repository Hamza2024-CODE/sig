<?php
$diffFile = 'c:\\xampp\\htdocs\\sig\\scratch\\diff.txt';
$lines = explode("\n", file_get_contents($diffFile));
$count = count($lines);
echo "Total lines in diff.txt: $count\n";
echo "Last 30 lines:\n";
for ($i = max(0, $count - 30); $i < $count; $i++) {
    echo "$i: " . $lines[$i] . "\n";
}
