<?php
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Core\Database;

class QueryValidator
{
    private PDO $db;
    private array $results = [];

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function run()
    {
        echo "=== SQL QUERY VALIDATOR ===\n";
        echo "Scanning codebase for SQL queries...\n\n";

        $directory = new RecursiveDirectoryIterator(__DIR__ . '/../app');
        $iterator = new RecursiveIteratorIterator($directory);
        $regex = '/\.(php)$/i';
        $fileCount = 0;
        $queryCount = 0;

        foreach ($iterator as $file) {
            if (preg_match($regex, $file->getPathname())) {
                $fileCount++;
                $this->scanFile($file->getPathname(), $queryCount);
            }
        }

        echo "\nScan complete. Scanned {$fileCount} files, found {$queryCount} queries.\n\n";
        $this->report();
    }

    private function scanFile(string $filePath, int &$queryCount)
    {
        $content = file_get_contents($filePath);
        // Match multiline double/single quoted strings starting with SELECT, INSERT, UPDATE, DELETE
        $pattern = '/(["\'])\s*(SELECT|INSERT\s+INTO|UPDATE|DELETE\s+FROM)\b.*?(\1)/is';
        
        if (preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[0] as $matchInfo) {
                $rawSql = $matchInfo[0];
                $offset = $matchInfo[1];
                
                // Determine line number
                $lineNumber = substr_count(substr($content, 0, $offset), "\n") + 1;
                
                // Clean the SQL string
                $cleanedSql = $this->cleanSql($rawSql);
                if (empty($cleanedSql)) continue;

                $queryCount++;
                $this->validateQuery($filePath, $lineNumber, $cleanedSql);
            }
        }
    }

    private function cleanSql(string $rawSql): string
    {
        // Remove only the matching outer quotes
        $firstChar = substr($rawSql, 0, 1);
        $lastChar = substr($rawSql, -1);
        if (($firstChar === '"' && $lastChar === '"') || ($firstChar === "'" && $lastChar === "'")) {
            $rawSql = substr($rawSql, 1, -1);
        }
        $sql = trim($rawSql);
        
        // Replace PHP concatenation / variables like {$whereClause} or " . $table . "
        $sql = preg_replace('/\{\$[a-zA-Z0-9_]+\}/', '1=1', $sql);
        $sql = preg_replace('/(\'\s*\.\s*\$[a-zA-Z0-9_]+\s*\.\s*\'|"\s*\.\s*\$[a-zA-Z0-9_]+\s*\.\s*")/', 'test', $sql);
        $sql = preg_replace('/\bWHERE\s+1=1\s+AND\s+test\b/i', 'WHERE 1=1', $sql);
        $sql = preg_replace('/\bWHERE\s+test\b/i', 'WHERE 1=1', $sql);

        // Replace any remaining $variable
        $sql = preg_replace('/\$[a-zA-Z0-9_]+/', '1', $sql);

        return $sql;
    }

    private function validateQuery(string $filePath, int $lineNumber, string $sql)
    {
        // Dry-run by preparing EXPLAIN statement (safe for SELECT, INSERT, UPDATE, DELETE)
        // Convert to EXPLAIN SELECT ... or check directly.
        // For non-selects, we can transform UPDATE/DELETE/INSERT to SELECT if needed, or wrap in a transaction and rollback.
        $isSelect = preg_match('/^\s*SELECT/i', $sql);
        
        try {
            $explainSql = $sql;
            if ($isSelect) {
                // Remove LIMIT clause if it has variable placeholders that make EXPLAIN fail
                $explainSql = preg_replace('/LIMIT\s+:\w+/i', '', $explainSql);
                $explainSql = preg_replace('/LIMIT\s+\?/i', '', $explainSql);
                $explainSql = "EXPLAIN " . $explainSql;
            } else {
                // Non-select: we wrap in a transaction, prepare and execute, then rollback.
                $this->db->beginTransaction();
                $stmt = $this->db->prepare($sql);
                // Bind dummy parameters to satisfy prepare/execute
                $params = $this->getParamPlaceholders($sql);
                $stmt->execute($params);
                $this->db->rollBack();
                
                $this->results[] = [
                    'file' => basename($filePath),
                    'path' => $filePath,
                    'line' => $lineNumber,
                    'sql' => $sql,
                    'status' => 'SUCCESS',
                    'message' => 'Query prepared & executed in transactional rollback successfully.'
                ];
                return;
            }

            $stmt = $this->db->prepare($explainSql);
            $params = $this->getParamPlaceholders($explainSql);
            $stmt->execute($params);
            
            $this->results[] = [
                'file' => basename($filePath),
                'path' => $filePath,
                'line' => $lineNumber,
                'sql' => $sql,
                'status' => 'SUCCESS',
                'message' => 'EXPLAIN execution succeeded.'
            ];

        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            $this->results[] = [
                'file' => basename($filePath),
                'path' => $filePath,
                'line' => $lineNumber,
                'sql' => $sql,
                'status' => 'FAILURE',
                'message' => $e->getMessage()
            ];
        }
    }

    private function getParamPlaceholders(string $sql): array
    {
        $params = [];
        // Match :name placeholders
        if (preg_match_all('/:([a-zA-Z0-9_]+)/', $sql, $matches)) {
            foreach ($matches[1] as $p) {
                $params[':' . $p] = 1;
            }
        }
        // Match ? placeholders (indexed array of 1s)
        $qCount = substr_count($sql, '?');
        if ($qCount > 0) {
            return array_fill(0, $qCount, 1);
        }
        return $params;
    }

    private function report()
    {
        $failures = array_filter($this->results, fn($r) => $r['status'] === 'FAILURE');
        $successes = array_filter($this->results, fn($r) => $r['status'] === 'SUCCESS');

        echo "=== VALIDATION REPORT ===\n";
        echo "Passed: " . count($successes) . "\n";
        echo "Failed: " . count($failures) . "\n\n";

        if (count($failures) > 0) {
            echo "--- DETAILED FAILURES ---\n";
            foreach ($failures as $f) {
                echo "File: " . $f['file'] . " (Line " . $f['line'] . ")\n";
                echo "Error: " . $f['message'] . "\n";
                echo "SQL: " . substr(preg_replace('/\s+/', ' ', $f['sql']), 0, 150) . "...\n";
                echo "--------------------------------------------------\n";
            }
        } else {
            echo "✨ All scanned SQL queries are 100% compatible with the database schema!\n";
        }
    }
}

$validator = new QueryValidator();
$validator->run();
