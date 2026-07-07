<?php
$indexFile = 'c:\\xampp\\htdocs\\sig\\resources\\views\\dashboard\\index.blade.php';
$diffFile = 'c:\\xampp\\htdocs\\sig\\scratch\\diff.txt';

if (!file_exists($indexFile) || !file_exists($diffFile)) {
    die("Required files not found.\n");
}

$indexLines = explode("\n", file_get_contents($indexFile));
$diffContent = file_get_contents($diffFile);

// Parse diff.txt to find Hunk 2 deleted lines
$diffLines = explode("\n", $diffContent);
$deletedLines = [];
$inHunk2 = false;

foreach ($diffLines as $line) {
    if (strpos($line, '@@ -1863,1418') !== false) {
        $inHunk2 = true;
        continue;
    }
    if ($inHunk2) {
        if (strpos($line, '@@') === 0) {
            // End of Hunk 2 if another hunk starts
            $inHunk2 = false;
            continue;
        }
        // If it starts with '-', it's a deleted line. We keep it (without the '-')
        if (strpos($line, '-') === 0) {
            $deletedLines[] = substr($line, 1);
        }
    }
}

echo "Extracted " . count($deletedLines) . " deleted lines from Hunk 2.\n";

// Now, let's reconstruct the file.
// Line 1865 in 1-based index is index 1864 in 0-based array.
// Let's verify line 1865:
echo "Line 1864 (0-based): " . $indexLines[1864] . "\n";

$part1 = array_slice($indexLines, 0, 1865);
$part2 = array_slice($indexLines, 1865);

$reconstructedLines = array_merge($part1, $deletedLines, $part2);
$reconstructedContent = implode("\n", $reconstructedLines);

file_put_contents('c:\\xampp\\htdocs\\sig\\scratch\\reconstructed_index.blade.php', $reconstructedContent);
echo "Reconstructed file saved to scratch/reconstructed_index.blade.php. Total lines: " . count($reconstructedLines) . "\n";
