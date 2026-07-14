<?php
/**
 * Secure Server-side SQL Zip Receiver & Extractor
 */
define('SECRET_KEY', 'HFSQL_SYNC_SQL_TRANSFER_2026');

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

$targetDir = __DIR__; // Extract directly in public/ folder
$tempFilePath = $targetDir . '/sql_upload_temp.zip';

if (move_uploaded_file($file['tmp_name'], $tempFilePath)) {
    $zip = new ZipArchive;
    if ($zip->open($tempFilePath) === TRUE) {
        $zip->extractTo($targetDir);
        $zip->close();
        unlink($tempFilePath);
        echo "SUCCESS: Extracted SQL ZIP successfully.";
    } else {
        unlink($tempFilePath);
        http_response_code(500);
        die("ERROR: Failed to open ZIP archive.");
    }
} else {
    http_response_code(500);
    die("ERROR: Failed to save uploaded file.");
}
