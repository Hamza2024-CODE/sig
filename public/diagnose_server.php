<?php
header('Content-Type: text/plain; charset=utf-8');

echo "=== DIAGNOSTIC FOR ACTIVE WEBSITE ===\n";
$baseDir = realpath(__DIR__ . '/..');
echo "1. Real Base Directory on Server: " . $baseDir . "\n\n";

$viewPath = $baseDir . '/resources/views/admin/offres/index.blade.php';
echo "2. View File Path: " . $viewPath . "\n";
if (file_exists($viewPath)) {
    $content = file_get_contents($viewPath);
    $hasBreakdown = strpos($content, 'Breakdown by Year') !== false;
    $hasDashed = strpos($content, 'dashed rgba(255,255,255,0.2)') !== false;
    echo "   - File Exists: Yes\n";
    echo "   - Contains 'Breakdown by Year' text: " . ($hasBreakdown ? 'Yes' : 'No') . "\n";
    echo "   - Contains new HTML styling: " . ($hasDashed ? 'Yes' : 'No') . "\n";
    echo "   - Last Modified: " . date("Y-m-d H:i:s", filemtime($viewPath)) . "\n\n";
} else {
    echo "   - File Exists: No\n\n";
}

$repoPath = $baseDir . '/app/Domains/Academic/Repositories/OffresRepository.php';
echo "3. Repository File Path: " . $repoPath . "\n";
if (file_exists($repoPath)) {
    $content = file_get_contents($repoPath);
    $hasByYear = strpos($content, 'by_year') !== false;
    $hasSYear = strpos($content, 'sYear =') !== false;
    echo "   - File Exists: Yes\n";
    echo "   - Contains 'by_year' mapping: " . ($hasByYear ? 'Yes' : 'No') . "\n";
    echo "   - Contains sYear query: " . ($hasSYear ? 'Yes' : 'No') . "\n";
    echo "   - Last Modified: " . date("Y-m-d H:i:s", filemtime($repoPath)) . "\n\n";
} else {
    echo "   - File Exists: No\n\n";
}

echo "4. Cleared Laravel views cache...\n";
try {
    // Try to clear compiled view files in storage
    $files = glob($baseDir . '/storage/framework/views/*.php');
    foreach ($files as $file) {
        if (is_file($file)) {
            unlink($file);
        }
    }
    echo "   - View files unlinked successfully.\n";
} catch (\Exception $e) {
    echo "   - Error unlinking views: " . $e->getMessage() . "\n";
}
