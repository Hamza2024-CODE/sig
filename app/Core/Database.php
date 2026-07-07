<?php

namespace App\Core;

use Illuminate\Support\Facades\DB;
use PDO;

class Database
{
    private static ?Database $instance = null;

    private function __construct()
    {
    }

    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new Database();
        }

        return self::$instance;
    }

    /**
     * Get the database connection dynamically from Laravel's DB facade.
     * Returning it dynamically prevents legacy scripts from accidentally closing or nullifying
     * the global database connection.
     */
    public function getConnection(): PDO
    {
        return new LaravelDbAdapter();
    }

    /**
     * Wrap execution inside a Laravel transaction with exception safety.
     */
    public function transaction(callable $callback)
    {
        return DB::transaction(function () use ($callback) {
            return $callback($this->getConnection());
        });
    }
}
