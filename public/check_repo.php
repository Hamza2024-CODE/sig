<?php
header('Content-Type: text/plain; charset=utf-8');
$file = __DIR__ . '/../app/Domains/Academic/Repositories/ApprenantRepository.php';
if (file_exists($file)) {
    echo "Size: " . filesize($file) . " bytes\n";
    $content = file_get_contents($file);
    if (strpos($content, 'COALESCE(c1.Nom, c2.Nom)') !== false) {
        echo "ERROR: Correlated subquery is still present in ApprenantRepository.php!\n";
    } else {
        echo "SUCCESS: Correlated subquery was optimized out of ApprenantRepository.php!\n";
    }
} else {
    echo "File not found!\n";
}
