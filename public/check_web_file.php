<?php
header('Content-Type: text/plain; charset=utf-8');
$file = __DIR__ . '/../routes/web.php';
if (file_exists($file)) {
    echo "Size: " . filesize($file) . " bytes\n";
    $content = file_get_contents($file);
    if (strpos($content, 'evaluation.jury') !== false) {
        echo "SUCCESS: evaluation.jury is present in routes/web.php!\n";
    } else {
        echo "ERROR: evaluation.jury is NOT present in routes/web.php!\n";
    }
} else {
    echo "File not found!\n";
}
