<?php
header('Content-Type: text/plain; charset=utf-8');

define('LARAVEL_START', microtime(true));
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== HFSQL CONFIG DIAGNOSTIC ===\n";
$dsn = config('security.hfsql.dsn');
$user = config('security.hfsql.username');
$pass = config('security.hfsql.password');

echo "DSN: " . $dsn . "\n";
echo "Username: " . $user . "\n";
echo "Password Configured (Length): " . strlen($pass) . "\n";

echo "\n--- 1. Testing Raw odbc_connect() with separate credentials ---\n";
if (function_exists('odbc_connect')) {
    try {
        $rawDsn = preg_replace('/^odbc:/i', '', $dsn);
        $conn = @odbc_connect($rawDsn, $user, $pass);
        if ($conn) {
            echo "✓ Success!\n";
            @odbc_close($conn);
        } else {
            echo "✗ Failed: " . odbc_errormsg() . "\n";
        }
    } catch (\Exception $e) {
        echo "✗ Exception: " . $e->getMessage() . "\n";
    }
}

echo "\n--- 2. Testing Raw odbc_connect() with embedded credentials ---\n";
if (function_exists('odbc_connect')) {
    try {
        $rawDsn = preg_replace('/^odbc:/i', '', $dsn);
        $connString = $rawDsn . ';UID=' . $user . ';PWD=' . $pass;
        echo "DSN String: $connString\n";
        $conn = @odbc_connect($connString, '', '');
        if ($conn) {
            echo "✓ Success!\n";
            $result = @odbc_exec($conn, "SELECT COUNT(*) FROM wilaya");
            if ($result) {
                $row = @odbc_fetch_array($result);
                echo "✓ Query Success! Wilaya count: " . json_encode($row) . "\n";
            } else {
                echo "✗ Query Failed: " . odbc_errormsg($conn) . "\n";
            }
            @odbc_close($conn);
        } else {
            echo "✗ Failed: " . odbc_errormsg() . "\n";
        }
    } catch (\Exception $e) {
        echo "✗ Exception: " . $e->getMessage() . "\n";
    }
}

echo "\n--- 3. Testing Laravel HFSQLConnection (PDO) ---\n";
try {
    $conn = \App\Core\HFSQLConnection::getInstance()->getConnection();
    echo "✓ PDO Connection Success!\n";
    $q = $conn->query("SELECT COUNT(*) AS n FROM wilaya")->fetch();
    echo "✓ PDO Query Success! Wilaya count: " . json_encode($q) . "\n";
} catch (\Exception $e) {
    echo "✗ PDO Connection Failed: " . $e->getMessage() . "\n";
}
