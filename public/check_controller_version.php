<?php
header('Content-Type: text/plain; charset=utf-8');

$file = __DIR__ . '/../app/Http/Controllers/Admin/EspaceEmployeController.php';
echo "=== CONTROLLER FILE STATUS ===\n";
echo "Path: $file\n";

if (file_exists($file)) {
    echo "Exists: YES\n";
    echo "Size: " . filesize($file) . " bytes\n";
    echo "Modified: " . date('Y-m-d H:i:s', filemtime($file)) . "\n";
    
    $content = file_get_contents($file);
    if (strpos($content, 'FORCE INDEX (PRIMARY)') !== false) {
        echo "Contains FORCE INDEX: YES\n";
    } else {
        echo "Contains FORCE INDEX: NO (Old version is active!)\n";
    }
    
    if (strpos($content, 'IDSession IN (31') !== false) {
        echo "Contains IDSession filter: YES\n";
    } else {
        echo "Contains IDSession filter: NO (Old version is active!)\n";
    }
} else {
    echo "Exists: NO\n";
}
