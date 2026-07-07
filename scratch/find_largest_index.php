<?php
$logFile = 'C:\\Users\\Bouba\\.gemini\\antigravity-ide\\brain\\6915b0f3-b4f2-4395-ba74-40ee5d7f3602\\.system_generated\\logs\\transcript_full.jsonl';
if (!file_exists($logFile)) {
    die("Log file not found.\n");
}

echo "Searching for view_file calls on index.blade.php...\n";
$handle = fopen($logFile, 'r');
while (($line = fgets($handle)) !== false) {
    $data = json_decode($line, true);
    
    // Check if this is a tool response (usually type is VIEW_FILE or similar, or has content/output)
    if (isset($data['type']) && ($data['type'] === 'VIEW_FILE' || $data['type'] === 'PLANNER_RESPONSE' || $data['type'] === 'CODE_ACTION')) {
        // Look for index.blade.php content indicators
        $hasIndicator = false;
        $content = '';
        if (isset($data['content'])) {
            $content = $data['content'];
        }
        
        // If it's a tool call response, it might have the file content
        if (strpos($content, 'bento-3 glass-panel') !== false || strpos($content, 'activeMeal') !== false || strpos($content, 'IDMode_formation') !== false) {
            echo "Step: " . $data['step_index'] . " | Type: " . $data['type'] . " | Content Length: " . strlen($content) . "\n";
            // Print first 200 chars
            echo "  Preview: " . substr(strip_tags($content), 0, 150) . "\n";
        }
    }
}
fclose($handle);
