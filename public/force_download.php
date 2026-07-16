<?php
header('Content-Type: text/plain; charset=utf-8');

$files = [
    'app/Domains/Academic/Repositories/ApprenantRepository.php' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/app/Domains/Academic/Repositories/ApprenantRepository.php',
    'public/pull.php' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/public/pull.php',
    'public/debug_absences.php' => 'https://raw.githubusercontent.com/Hamza2024-CODE/sig/main/public/debug_absences.php'
];

foreach ($files as $localPath => $remoteUrl) {
    $fullPath = __DIR__ . '/../' . $localPath;
    echo "Downloading $localPath...\n";
    $content = file_get_contents($remoteUrl . '?v=' . time());
    if ($content !== false) {
        file_put_contents($fullPath, $content);
        echo "SUCCESS: Saved " . strlen($content) . " bytes to $localPath\n";
    } else {
        echo "ERROR: Failed to download $localPath\n";
    }
}
