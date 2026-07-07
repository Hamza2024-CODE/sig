<?php

namespace App\Core;

use PDO;
use PDOException;

class HFSQLConnection
{
    private static ?HFSQLConnection $instance = null;
    private PDO $connection;

    private function __construct()
    {
        if (!extension_loaded('pdo_odbc')) {
            throw new PDOException("The PHP extension 'pdo_odbc' is required to connect to HFSQL but is not loaded. Please enable it in php.ini.");
        }

        $this->connection = $this->createConnection();
    }

    /**
     * Build DSN with UID/PWD embedded — required by HFSQL ODBC driver.
     * Passing credentials as separate PDO params causes "access denied" with this driver.
     */
    private function createConnection(): PDO
    {
        $dsn  = config('security.hfsql.dsn', 'Driver={HFSQL};Server Name=127.0.0.1;Server Port=4900;Database=sig;IntegrityCheck=1');
        $user = config('security.hfsql.username', 'sig');
        $pass = config('security.hfsql.password');

        // Strip the leading 'odbc:' prefix if present — we'll re-add it cleanly
        $rawDsn = preg_replace('/^odbc:/i', '', $dsn);

        // Remove any trailing semicolons for clean injection
        $rawDsn = rtrim(trim($rawDsn), ';');

        // Inject credentials directly into the DSN string (HFSQL ODBC requirement)
        $fullDsn = 'odbc:' . $rawDsn . ';UID=' . $user . ';PWD=' . $pass;

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ];

        try {
            return new PDO($fullDsn, null, null, $options);
        } catch (PDOException $e) {
            throw new PDOException("Could not connect to HFSQL Remote Database: " . $e->getMessage(), (int)$e->getCode());
        }
    }

    public static function getInstance(): HFSQLConnection
    {
        if (self::$instance === null) {
            self::$instance = new HFSQLConnection();
        }

        return self::$instance;
    }

    public function getConnection(): PDO
    {
        try {
            // SELECT 1 لا يعمل في HFSQL — نستخدم استعلاماً أبسط
            $this->connection->query("SELECT COUNT(*) AS n FROM wilaya");
        } catch (\Throwable $e) {
            // Connection lost — reconnect automatically
            try {
                $this->connection = $this->createConnection();
            } catch (\Throwable $err) {
                // Return existing (possibly broken) connection — caller will handle the error
            }
        }

        return $this->connection;
    }

}
