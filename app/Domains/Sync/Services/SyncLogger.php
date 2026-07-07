<?php
/**
 * SyncLogger — يكتب في sync_logs (MySQL) وفي ملف على القرص
 */

namespace App\Domains\Sync\Services;

use App\Core\Database;
use PDO;

class SyncLogger
{
    private PDO $db;
    private string $jobId;
    private string $logFile;

    public function __construct(string $jobId)
    {
        $this->jobId   = $jobId;
        $this->db      = Database::getInstance()->getConnection();
        $logDir        = dirname(__DIR__, 4) . '/storage/logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        $this->logFile = $logDir . '/sync.log';
    }

    public function info(string $msg): void    { $this->write('info',    $msg); }
    public function warning(string $msg): void { $this->write('warning', $msg); }
    public function error(string $msg): void   { $this->write('error',   $msg); }
    public function debug(string $msg): void   { $this->write('debug',   $msg); }

    private function write(string $level, string $msg): void
    {
        // 1) Write to MySQL sync_logs
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO sync_logs (job_id, level, message) VALUES (?, ?, ?)"
            );
            $stmt->execute([$this->jobId, $level, $msg]);
        } catch (\Throwable $e) {
            // Silently ignore DB log failures
        }

        // 2) Write to flat file (always works, even if DB is down)
        $line = sprintf("[%s] [%s] [%s] %s\n",
            date('Y-m-d H:i:s'),
            strtoupper($level),
            $this->jobId,
            $msg
        );
        @file_put_contents($this->logFile, $line, FILE_APPEND | LOCK_EX);
    }

    /**
     * Returns last N log entries for a job (for API polling)
     */
    public static function getLogs(string $jobId, int $limit = 50): array
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare(
            "SELECT level, message, created_at FROM sync_logs
             WHERE job_id = ? ORDER BY id DESC LIMIT ?"
        );
        $stmt->execute([$jobId, $limit]);
        return array_reverse($stmt->fetchAll(PDO::FETCH_ASSOC));
    }
}
