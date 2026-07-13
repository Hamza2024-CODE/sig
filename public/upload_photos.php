<?php
/**
 * Secure Server-side Zip Receiver & Extractor
 */

// Define a secure security key
define('SECRET_KEY', 'HFSQL_SYNC_PHOTO_TRANSFER_2026');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die("Method Not Allowed");
}

$token = $_POST['token'] ?? '';
if ($token !== SECRET_KEY) {
    http_response_code(403);
    die("Unauthorized Access");
}

if (!isset($_FILES['zipfile'])) {
    http_response_code(400);
    die("No file uploaded");
}

$file = $_FILES['zipfile'];
if ($file['error'] !== UPLOAD_ERR_OK) {
    http_response_code(500);
    die("Upload error: " . $file['error']);
}

$targetDir = __DIR__ . '/uploads/hfsql_sync/candidat_memo/photo';
if (!is_dir($targetDir)) {
    mkdir($targetDir, 0755, true);
}

$tempFilePath = $targetDir . '/upload_temp';

if (move_uploaded_file($file['tmp_name'], $tempFilePath)) {
    $clientFileName = strtolower($file['name']);
    if (str_ends_with($clientFileName, '.tar')) {
        try {
            $phar = new PharData($tempFilePath);
            $phar->extractTo($targetDir, null, true);
            unlink($tempFilePath);
            echo "SUCCESS: Extracted TAR successfully.";
        } catch (\Exception $e) {
            unlink($tempFilePath);
            http_response_code(500);
            die("ERROR: Failed to extract TAR archive: " . $e->getMessage());
        }
    } else {
        // Extract zip
        $zip = new ZipArchive;
        if ($zip->open($tempFilePath) === TRUE) {
            $zip->extractTo($targetDir);
            $zip->close();
            unlink($tempFilePath);
            echo "SUCCESS: Extracted ZIP successfully.";
        } else {
            unlink($tempFilePath);
            http_response_code(500);
            die("ERROR: Failed to open ZIP archive.");
        }
    }
} else {
    http_response_code(500);
    die("ERROR: Failed to save uploaded file.");
}
