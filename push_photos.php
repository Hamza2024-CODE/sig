<?php
/**
 * Local Script: Zip & Push Photos to Remote Server
 */

$sourceDir = 'C:/xampp/htdocs/sig/public/uploads/hfsql_sync/candidat_memo/photo';
$tempZipPath = __DIR__ . '/photos_temp.zip';
$remoteUrl = 'https://test-tdfp.rnfc.dz/upload_photos.php';
$secretToken = 'HFSQL_SYNC_PHOTO_TRANSFER_2026';

echo "1. Checking source directory...\n";
if (!is_dir($sourceDir)) {
    die("ERROR: Source directory does not exist: $sourceDir\n");
}

$files = scandir($sourceDir);
$fileCount = count($files) - 2; // Subtract . and ..
echo "Found $fileCount files to transfer.\n";

if ($fileCount <= 0) {
    die("No files to transfer.\n");
}

echo "2. Zipping files...\n";
$zip = new ZipArchive();
if ($zip->open($tempZipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
    die("ERROR: Could not create temporary ZIP archive: $tempZipPath\n");
}

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($sourceDir, RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::LEAVES_ONLY
);

foreach ($iterator as $name => $file) {
    if (!$file->isDir()) {
        $filePath = $file->getRealPath();
        $relativePath = substr($filePath, strlen($sourceDir) + 1);
        $zip->addFile($filePath, $relativePath);
    }
}

$zip->close();
echo "ZIP created successfully: " . round(filesize($tempZipPath) / 1024 / 1024, 2) . " MB\n";

echo "3. Sending ZIP file to remote server...\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $remoteUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, [
    'token' => $secretToken,
    'zipfile' => new CURLFile($tempZipPath, 'application/zip', 'photos.zip')
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Bypass SSL checks

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if (curl_errno($ch)) {
    echo "CURL Error: " . curl_error($ch) . "\n";
}

curl_close($ch);

// Clean up temporary ZIP
unlink($tempZipPath);

echo "HTTP Status Code: $httpCode\n";
echo "Server Response: $response\n";

if ($httpCode === 200 && strpos($response, 'SUCCESS') !== false) {
    echo "\nSUCCESS! All photos synced and extracted successfully on the server!\n";
} else {
    echo "\nFAILURE! Please check server errors or file permissions.\n";
}
