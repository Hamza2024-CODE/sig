<?php

namespace App\Core;

use PDO;

class AuditLogger
{
    private static ?PDO $db = null;
    private static bool $enabled = true;
    private static ?array $columns = null;

    private static function getDb(): ?PDO
    {
        if (self::$db === null) {
            try {
                self::$db = Database::getInstance()->getConnection();
            } catch (\Exception $e) {
                self::$enabled = false;
                self::logError("Database connection for AuditLogger failed: " . $e->getMessage());
                return null;
            }
        }

        return self::$db;
    }

    private static function getColumns(PDO $db): array
    {
        if (self::$columns !== null) {
            return self::$columns;
        }

        try {
            $columns = [];
            foreach ($db->query("DESCRIBE audit_logs") as $row) {
                $columns[$row['Field']] = true;
            }
            self::$columns = $columns;
        } catch (\Exception $e) {
            self::$enabled = false;
            self::$columns = [];
            self::logError("Describe audit_logs table failed: " . $e->getMessage());
        }

        return self::$columns;
    }

    public static function log(
        string $action,
        string $table_name = '',
        ?int $record_id = null,
        ?array $old_data = null,
        ?array $new_data = null
    ): void {
        if (!self::$enabled) {
            return;
        }

        $db = self::getDb();
        if (!$db) {
            self::logError("Audit logging database connection failed", [
                'action' => $action,
                'table_name' => $table_name,
                'record_id' => $record_id
            ]);
            return;
        }

        $userId = null;
        if (function_exists('session') && function_exists('app') && app()->bound('session.store')) {
            try {
                $laravelUser = session('user');
                if (is_array($laravelUser) && isset($laravelUser['id'])) {
                    $userId = $laravelUser['id'];
                }
            } catch (\Throwable $t) {}
        }
        if ($userId === null && isset($_SESSION['user']['id'])) {
            $userId = $_SESSION['user']['id'];
        }
        if ($userId === null) {
            return;
        }

        $columns = self::getColumns($db);
        if (empty($columns)) {
            return;
        }

        $userRole = null;
        if (function_exists('session') && function_exists('app') && app()->bound('session.store')) {
            try { $userRole = session('user')['role_code'] ?? null; } catch (\Throwable $t) {}
        }
        if ($userRole === null) {
            $userRole = $_SESSION['user']['role_code'] ?? 'unknown';
        }
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $userAgent = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);

        $sanitize = function (?array $data): ?array {
            if ($data === null) {
                return null;
            }

            $safe = $data;
            foreach (['password', 'password_hash', 'api_key', 'token_reset'] as $key) {
                if (isset($safe[$key])) {
                    $safe[$key] = '***REDACTED***';
                }
            }

            return $safe;
        };

        $details = [
            'table_name' => $table_name,
            'record_id' => $record_id,
            'user_role' => $userRole,
            'old_data' => $sanitize($old_data),
            'new_data' => $sanitize($new_data),
            'user_agent' => $userAgent,
        ];

        try {
            if (isset($columns['details'])) {
                $stmt = $db->prepare("
                    INSERT INTO audit_logs (user_id, action, details, ip_address)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([
                    (int)$userId,
                    strtoupper($action),
                    json_encode($details, JSON_UNESCAPED_UNICODE),
                    $ip
                ]);
                return;
            }

            $insert = [
                'user_id' => (int)$userId,
                'action' => strtoupper($action),
                'ip_address' => $ip,
            ];

            foreach ([
                'user_role' => $userRole,
                'table_name' => $table_name,
                'record_id' => $record_id,
                'old_data' => json_encode($sanitize($old_data), JSON_UNESCAPED_UNICODE),
                'new_data' => json_encode($sanitize($new_data), JSON_UNESCAPED_UNICODE),
                'user_agent' => $userAgent,
            ] as $column => $value) {
                if (isset($columns[$column])) {
                    $insert[$column] = $value;
                }
            }

            $columnNames = array_keys($insert);
            $placeholders = array_fill(0, count($columnNames), '?');
            $sql = "INSERT INTO audit_logs (" . implode(', ', $columnNames) . ") VALUES (" . implode(', ', $placeholders) . ")";
            $stmt = $db->prepare($sql);
            $stmt->execute(array_values($insert));
        } catch (\Exception $e) {
            self::logError("Audit logging to database failed: " . $e->getMessage(), [
                'action' => $action,
                'table_name' => $table_name,
                'record_id' => $record_id
            ]);
        }
    }

    /**
     * Log a sensitive READ/VIEW operation.
     */
    public static function logRead(string $table_name, ?int $record_id, ?array $accessed_fields = null): void
    {
        self::log(
            'READ_ACCESS',
            $table_name,
            $record_id,
            null,
            $accessed_fields
        );
    }

    public static function recent(int $limit = 50, ?int $user_id = null): array
    {
        $db = self::getDb();
        if (!$db) {
            return [];
        }

        $columns = self::getColumns($db);

        try {
            if (isset($columns['details'])) {
                $sql = "
                    SELECT al.*, u.Nom as nom_complet, u.NomUser as username
                    FROM audit_logs al
                    LEFT JOIN utilisateur u ON al.user_id = u.IDUtilisateur
                ";

                $params = [];
                if ($user_id) {
                    $sql .= " WHERE al.user_id = ?";
                    $params[] = $user_id;
                }

                $sql .= " ORDER BY al.created_at DESC LIMIT " . (int)$limit;
                $stmt = $db->prepare($sql);
                $stmt->execute($params);
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            }

            if ($user_id) {
                $stmt = $db->prepare("
                    SELECT al.*, u.Nom as nom_complet, u.NomUser as username
                    FROM audit_logs al
                    LEFT JOIN utilisateur u ON al.user_id = u.IDUtilisateur
                    WHERE al.user_id = ?
                    ORDER BY al.created_at DESC
                    LIMIT " . (int)$limit
                );
                $stmt->execute([$user_id]);
            } else {
                $stmt = $db->prepare("
                    SELECT al.*, u.Nom as nom_complet, u.NomUser as username
                    FROM audit_logs al
                    LEFT JOIN utilisateur u ON al.user_id = u.IDUtilisateur
                    ORDER BY al.created_at DESC
                    LIMIT " . (int)$limit
                );
                $stmt->execute();
            }

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            self::logError("Retrieve recent audit logs failed: " . $e->getMessage());
            return [];
        }
    }

    public static function logToFile(string $level, string $message, array $context = []): void
    {
        $logDir = base_path('storage/logs');
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
        $logFile = $logDir . '/app.log';
        $timestamp = date('Y-m-d H:i:s');
        $contextString = empty($context) ? '' : ' ' . json_encode($context, JSON_UNESCAPED_UNICODE);
        $logMessage = "[$timestamp] [$level] $message$contextString\n";
        @error_log($logMessage, 3, $logFile);
    }

    public static function logWarning(string $message, array $context = []): void
    {
        self::logToFile('WARNING', $message, $context);
    }

    public static function logError(string $message, array $context = []): void
    {
        self::logToFile('ERROR', $message, $context);
    }
}
