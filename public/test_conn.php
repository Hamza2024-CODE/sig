<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$dsn = "Driver={HFSQL};Server Name=197.112.101.166;Server Port=4900;Database=sig;IntegrityCheck=1";
$user = "sig";
$pass = "Sig@2023#2025";
$fullDsn = "odbc:" . $dsn . ";UID=" . $user . ";PWD=" . $pass;

echo "Attempting web connection to: " . $fullDsn . "\n";
try {
    $start = microtime(true);
    $pdo = new PDO($fullDsn, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 5
    ]);
    $time = microtime(true) - $start;
    echo "Connection SUCCEEDED in " . round($time, 3) . " seconds!\n";
    
    $stmt = $pdo->query("SELECT 1");
    echo "Query SELECT 1 succeeded!\n";
} catch (\Throwable $e) {
    echo "Connection FAILED: " . $e->getMessage() . "\n";
}
