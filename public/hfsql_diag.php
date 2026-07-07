<?php
// Quick diagnostic — checks pdo_odbc, HFSQL driver, and connection
header('Content-Type: text/plain; charset=UTF-8');

echo "=== PHP ODBC Diagnostic ===\n\n";

// 1. Check pdo_odbc
echo "pdo_odbc loaded: " . (extension_loaded('pdo_odbc') ? "YES ✓" : "NO ✗ — enable in php.ini") . "\n";
echo "odbc loaded:     " . (extension_loaded('odbc') ? "YES ✓" : "NO ✗") . "\n\n";

// 2. List available ODBC drivers
if (function_exists('odbc_data_source')) {
    echo "ODBC Drivers available:\n";
    // Try to list drivers via odbcad32 or registry
} else {
    echo "odbc_data_source() not available\n";
}

// 3. Check registry for HFSQL driver
echo "\n=== Checking Windows ODBC Drivers (registry) ===\n";
$regOutput = shell_exec('reg query "HKEY_LOCAL_MACHINE\SOFTWARE\ODBC\ODBCINST.INI\ODBC Drivers" 2>&1');
if ($regOutput) {
    echo $regOutput;
} else {
    echo "(could not read registry)\n";
}

// 4. Try connection
echo "\n=== Connection Test ===\n";
if (!extension_loaded('pdo_odbc')) {
    echo "SKIP — pdo_odbc not loaded\n";
} else {
    $dsn = 'odbc:Driver={HFSQL};Server Name=197.112.101.166;Server Port=4900;Database=sig;IntegrityCheck=1';
    echo "DSN: $dsn\n";
    echo "Connecting (timeout 5s)...\n";
    try {
        $pdo = new PDO($dsn, 'sig', 'Sig@2023#2025', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        echo "CONNECTED ✓\n";
        $tables = $pdo->query("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE='TABLE' ORDER BY TABLE_NAME")->fetchAll(PDO::FETCH_COLUMN);
        echo "Tables found: " . count($tables) . "\n";
        echo implode(', ', array_slice($tables, 0, 10)) . " ...\n";
    } catch (Throwable $e) {
        echo "FAILED ✗: " . $e->getMessage() . "\n";
    }
}
echo "\nDone.\n";
