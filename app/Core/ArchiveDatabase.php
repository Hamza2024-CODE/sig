<?php

namespace App\Core;

use PDO;
use PDOException;

class ArchiveDatabase
{
    private static ?ArchiveDatabase $instance = null;
    private PDO $connection;

    private function __construct()
    {
        if (!extension_loaded('pdo_odbc')) {
            throw new PDOException("The PHP extension 'pdo_odbc' is required to connect to HFSQL but is not loaded. Please enable it in php.ini.");
        }

        $dsn = env('ARCHIVE_DSN', 'odbc:hamza');
        $user = env('ARCHIVE_USERNAME', 'sig');
        $pass = env('ARCHIVE_PASSWORD', 'Sig@2023#2025');

        if (strpos(strtolower($dsn), 'odbc:') !== 0) {
            $dsn = 'odbc:' . $dsn;
        }

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ];

        try {
            $this->connection = new PDO($dsn, $user, $pass, $options);
        } catch (PDOException $e) {
            throw new PDOException("فشل الاتصال بقاعدة بيانات الأرشيف HFSQL: " . $e->getMessage(), (int)$e->getCode());
        }
    }

    public static function getInstance(): ArchiveDatabase
    {
        if (self::$instance === null) {
            self::$instance = new ArchiveDatabase();
        }

        return self::$instance;
    }

    public function getConnection(): PDO
    {
        return $this->connection;
    }
}
