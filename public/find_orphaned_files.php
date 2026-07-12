<?php
header('Content-Type: text/plain; charset=utf-8');

define('LARAVEL_START', microtime(true));
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "=========================================================\n";
echo "       ORPHANED UPLOADS SCANNER (CLEANUP AUDIT)          \n";
echo "=========================================================\n\n";

function normalizePath($path) {
    $path = str_replace('\\', '/', $path);
    $path = trim($path, '/');
    return strtolower($path);
}

// 1. Collect all valid file paths from MySQL Database
$dbPaths = [];
$collectedCount = 0;

$collect = function($table, $column) use (&$dbPaths, &$collectedCount) {
    try {
        if (Schema::hasTable($table) && Schema::hasColumn($table, $column)) {
            $rows = DB::table($table)->whereNotNull($column)->where($column, '!=', '')->pluck($column);
            foreach ($rows as $row) {
                $norm = normalizePath($row);
                $dbPaths[$norm] = true;
                $collectedCount++;
            }
        }
    } catch (\Throwable $e) {
        echo "⚠️ Warning reading {$table}.{$column}: " . $e->getMessage() . "\n";
    }
};

echo "1. Scanning database tables for referenced files...\n";
$collect('candidat', 'photo');
$collect('encadrement', 'photo');
$collect('candidat_memo', 'photo');
$collect('encadremen_memo', 'photo');
$collect('etablissement_memo', 'photo');
$collect('equipement_memo', 'photo');
$collect('logement_memo', 'photo');
$collect('vehicule_memo', 'photo');
$collect('dercrte_memo', 'Pdf');
$collect('dpca', 'Document');
$collect('dpca', 'Document1');
$collect('candidat_document', 'relevedenotes_url');
$collect('candidat_document', 'enneexperience_url');
$collect('candidat_document', 'exdiplome_url');
$collect('candidat_document', 'actn_url');
$collect('candidat_certifscol', 'photo');
$collect('candidat_contratapp', 'photo');
$collect('utilisateur', 'avatar');

echo "✓ Collected {$collectedCount} file paths from the database (" . count($dbPaths) . " unique paths).\n\n";

// 2. Scan uploads directory on disk
echo "2. Scanning public/uploads/ directory on disk...\n";
$uploadsDir = public_path('uploads');
$diskFiles = [];
$totalDiskSize = 0;

if (is_dir($uploadsDir)) {
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($uploadsDir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    foreach ($iterator as $file) {
        if ($file->isFile()) {
            $filePath = $file->getPathname();
            $relPath = str_replace(public_path(), '', $filePath);
            $normPath = normalizePath($relPath);
            
            $size = $file->getSize();
            $diskFiles[$normPath] = [
                'full_path' => $filePath,
                'rel_path' => $relPath,
                'size' => $size
            ];
            $totalDiskSize += $size;
        }
    }
} else {
    die("❌ Error: Directory not found at: {$uploadsDir}\n");
}

$totalDiskFilesCount = count($diskFiles);
echo "✓ Found {$totalDiskFilesCount} files on disk (Total Size: " . number_format($totalDiskSize / (1024 * 1024), 2) . " MB).\n\n";

// 3. Find orphaned files (Files on disk but NOT in the database)
echo "3. Identifying orphaned files...\n";
$orphanedFiles = [];
$orphanedSize = 0;

foreach ($diskFiles as $normPath => $fileInfo) {
    if (!isset($dbPaths[$normPath])) {
        $orphanedFiles[] = $fileInfo;
        $orphanedSize += $fileInfo['size'];
    }
}

$orphanedCount = count($orphanedFiles);
echo "✓ Found {$orphanedCount} orphaned files (Total Size: " . number_format($orphanedSize / (1024 * 1024), 2) . " MB).\n";
echo "✓ Safe files (referenced in DB): " . ($totalDiskFilesCount - $orphanedCount) . "\n\n";

// 4. Handle Deletion if requested
$deleteRequested = isset($_GET['delete']) && $_GET['delete'] == '1';
$confirmKey = $_GET['confirm_key'] ?? '';
$expectedKey = 'sgfep_clean_2026';

if ($deleteRequested) {
    if ($confirmKey !== $expectedKey) {
        echo "❌ Deletion Aborted: Invalid confirm_key. Please use '?delete=1&confirm_key={$expectedKey}'\n";
    } else {
        echo "⚠️ Deletion Started...\n";
        $deletedCount = 0;
        $deletedSize = 0;
        foreach ($orphanedFiles as $file) {
            if (file_exists($file['full_path'])) {
                if (@unlink($file['full_path'])) {
                    $deletedCount++;
                    $deletedSize += $file['size'];
                }
            }
        }
        echo "✓ Successfully deleted {$deletedCount} orphaned files (" . number_format($deletedSize / (1024 * 1024), 2) . " MB freed).\n\n";
    }
} else {
    echo "ℹ️ Note: To delete these orphaned files and free up disk space, visit this URL with parameters:\n";
    echo "   ?delete=1&confirm_key={$expectedKey}\n\n";
}

// 5. Output Preview of Orphaned Files (Limit 50 for display)
if ($orphanedCount > 0) {
    echo "--- Preview of Orphaned Files (Showing first 50) ---\n";
    $i = 0;
    foreach ($orphanedFiles as $file) {
        if ($i++ >= 50) break;
        echo "- " . $file['rel_path'] . " (" . number_format($file['size'] / 1024, 1) . " KB)\n";
    }
} else {
    echo "✓ Congratulations! No orphaned files found. All files on disk are referenced in the database.\n";
}
