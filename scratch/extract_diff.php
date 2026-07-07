<?php
$jsonFile = 'c:\\xampp\\htdocs\\sig\\scratch\\step_1973.json';
$data = json_decode(file_get_contents($jsonFile), true);
$content = $data['content'];

// Find the diff block
$pos = strpos($content, '[diff_block_start]');
if ($pos === false) {
    die("No diff block found.\n");
}
$diff = substr($content, $pos + strlen('[diff_block_start]'));
$endPos = strpos($diff, '[diff_block_end]');
if ($endPos !== false) {
    $diff = substr($diff, 0, $endPos);
}

// Write the diff to a file
file_put_contents('c:\\xampp\\htdocs\\sig\\scratch\\diff.txt', $diff);
echo "Saved diff to diff.txt. Length: " . strlen($diff) . "\n";

// Let's see the first 20 lines of the diff
$lines = explode("\n", $diff);
for ($i = 0; $i < min(50, count($lines)); $i++) {
    echo "$i: " . $lines[$i] . "\n";
}
