<?php
$logFile = 'C:\\Users\\Bouba\\.gemini\\antigravity-ide\\brain\\6915b0f3-b4f2-4395-ba74-40ee5d7f3602\\.system_generated\\logs\\transcript_full.jsonl';
if (!file_exists($logFile)) {
    die("Log file not found.\n");
}

echo "Scanning transcript_full.jsonl...\n";
$handle = fopen($logFile, 'r');
$count = 0;
while (($line = fgets($handle)) !== false) {
    if (strpos($line, 'index.blade.php') !== false) {
        $data = json_decode($line, true);
        echo "Line: " . $data['step_index'] . " | Type: " . $data['type'] . " | Source: " . $data['source'] . "\n";
        
        // Let's see if this line contains tool_calls or content
        if (isset($data['tool_calls'])) {
            foreach ($data['tool_calls'] as $tc) {
                if (isset($tc['function']['name'])) {
                    echo "  Tool Call: " . $tc['function']['name'] . "\n";
                }
            }
        }
        if (isset($data['content'])) {
            echo "  Content length: " . strlen($data['content']) . "\n";
            if (strpos($data['content'], 'ViewException') !== false) {
                echo "  Contains ViewException\n";
            }
        }
        $count++;
    }
}
fclose($handle);
echo "Total occurrences: $count\n";
