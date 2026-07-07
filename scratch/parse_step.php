<?php
$jsonFile = 'c:\\xampp\\htdocs\\sig\\scratch\\step_1973.json';
if (!file_exists($jsonFile)) {
    die("JSON file not found.\n");
}

$data = json_decode(file_get_contents($jsonFile), true);
echo "Type: " . $data['type'] . "\n";
echo "Keys: " . implode(', ', array_keys($data)) . "\n";

if (isset($data['tool_calls'])) {
    echo "Tool Calls Count: " . count($data['tool_calls']) . "\n";
    foreach ($data['tool_calls'] as $i => $tc) {
        echo "  [$i] Name: " . $tc['function']['name'] . "\n";
        echo "  [$i] Keys: " . implode(', ', array_keys($tc['function'])) . "\n";
        // If it's a file write or edit, show the target file and some content
        $args = json_decode($tc['function']['arguments'], true);
        echo "  [$i] Args keys: " . implode(', ', array_keys($args)) . "\n";
        if (isset($args['TargetFile'])) {
            echo "  [$i] TargetFile: " . $args['TargetFile'] . "\n";
        }
        if (isset($args['ReplacementChunks'])) {
            echo "  [$i] ReplacementChunks count: " . count($args['ReplacementChunks']) . "\n";
        }
    }
}

if (isset($data['content'])) {
    echo "Content Length: " . strlen($data['content']) . "\n";
    echo "Content Preview: " . substr($data['content'], 0, 500) . "\n";
}
