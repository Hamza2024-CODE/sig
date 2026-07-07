<?php

namespace App\Core;

use Illuminate\Support\Facades\DB;
use PDO;

class LaravelDbAdapter extends PDO
{
    public function __construct()
    {
        // Bypass native PDO connection setup
    }

    public function prepare($query, $options = [])
    {
        return new LaravelDbStatement($query);
    }

    public function query($query, $mode = null, ...$args)
    {
        $stmt = new LaravelDbStatement($query);
        $stmt->execute();
        return $stmt;
    }

    public function exec($statement)
    {
        return DB::statement($statement);
    }

    public function lastInsertId($name = null)
    {
        return DB::getPdo()->lastInsertId();
    }

    public function beginTransaction()
    {
        DB::beginTransaction();
        return true;
    }

    public function commit()
    {
        DB::commit();
        return true;
    }

    public function rollBack()
    {
        DB::rollBack();
        return true;
    }

    public function inTransaction()
    {
        return DB::transactionLevel() > 0;
    }

    public function quote(string $string, int $type = PDO::PARAM_STR): string|false
    {
        return "'" . str_replace(["\\", "'", "\r", "\n", "\x1a"], ["\\\\", "\\'", "\\r", "\\n", "\\Z"], $string) . "'";
    }

    public function setAttribute(int $attribute, mixed $value): bool
    {
        return true;
    }

    public function getAttribute(int $attribute): mixed
    {
        return null;
    }

    public function errorCode(): ?string
    {
        return '00000';
    }

    public function errorInfo(): array
    {
        return ['00000', null, null];
    }
}

class LaravelDbStatement
{
    private string $query;
    private array $bindings = [];
    private ?array $results = null;
    private int $position = 0;
    private int $rowCount = 0;

    public function __construct(string $query)
    {
        $this->query = $query;
    }

    public function bindValue($parameter, $value, $type = PDO::PARAM_STR)
    {
        if (is_int($parameter)) {
            $this->bindings[$parameter - 1] = $value;
        } else {
            $this->bindings[$parameter] = $value;
        }
        return true;
    }

    public function execute(?array $params = null)
    {
        if ($params !== null) {
            $this->bindings = $params;
        }

        // Sort bindings by key if they are numeric indexes to ensure correct position order
        ksort($this->bindings);

        $trimmedQuery = ltrim(strtolower($this->query));
        
        if (str_starts_with($trimmedQuery, 'select') || 
            str_starts_with($trimmedQuery, 'show') || 
            str_starts_with($trimmedQuery, 'describe') || 
            str_starts_with($trimmedQuery, 'explain')) {
            $rawResults = DB::select($this->query, $this->bindings);
            $this->results = array_map(fn($item) => (array)$item, $rawResults);
            $this->rowCount = count($this->results);
        } else {
            $this->rowCount = DB::affectingStatement($this->query, $this->bindings);
            $this->results = [];
        }

        $this->position = 0;
        return true;
    }

    public function fetch($mode = PDO::FETCH_ASSOC)
    {
        if ($this->results === null) {
            $this->execute();
        }

        if ($this->position < count($this->results)) {
            return $this->results[$this->position++];
        }

        return false;
    }

    public function fetchAll($mode = PDO::FETCH_ASSOC, ...$args)
    {
        if ($this->results === null) {
            $this->execute();
        }

        $remaining = array_slice($this->results, $this->position);
        $this->position = count($this->results);

        if ($mode === PDO::FETCH_COLUMN) {
            $columnIndex = isset($args[0]) ? (int)$args[0] : 0;
            return array_map(function($row) use ($columnIndex) {
                return is_array($row) ? (array_values($row)[$columnIndex] ?? null) : null;
            }, $remaining);
        }

        return $remaining;
    }

    public function fetchColumn($columnIndex = 0)
    {
        $row = $this->fetch();
        if ($row !== false) {
            return array_values($row)[$columnIndex] ?? null;
        }
        return false;
    }

    public function rowCount()
    {
        return $this->rowCount;
    }
}
