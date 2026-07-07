<?php
$logFile = 'C:\\Users\\Bouba\\.gemini\\antigravity-ide\\brain\\6915b0f3-b4f2-4395-ba74-40ee5d7f3602\\.system_generated\\logs\\transcript_full.jsonl';
$targetStep = 1973;

echo "Searching for step_index $targetStep...\n";
$handle = fopen($logFile, 'r');
while (($line = fgets($handle)) !== false) {
    $data = json_decode($line, true);
    if (isset($data['step_index']) && $data['step_index'] === $targetStep) {
        file_put_contents('c:\\xampp\\htdocs\\sig\\scratch\\step_1973.json', json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        echo "Found! Saved to step_1973.json. JSON Length: " . strlen($line) . "\n";
        break;
    }
}
fclose($handle);
