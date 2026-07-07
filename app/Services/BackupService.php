<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use ZipArchive;

class BackupService
{
    private string $backupDir;

    public function __construct()
    {
        $this->backupDir = storage_path('app/backups');
        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0755, true);
        }
    }

    /**
     * Get list of existing backup files.
     */
    public function getBackupsList(): array
    {
        $backups = [];
        if (!is_dir($this->backupDir)) return [];

        $files = scandir($this->backupDir);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..' || $file === '.htaccess') continue;
            $path = $this->backupDir . '/' . $file;
            if (is_file($path)) {
                $backups[] = [
                    'filename'   => $file,
                    'size_mb'    => round(filesize($path) / 1024 / 1024, 2),
                    'created_at' => date('Y-m-d H:i:s', filemtime($path)),
                ];
            }
        }

        // Sort backups by date descending
        usort($backups, fn($a, $b) => strcmp($b['created_at'], $a['created_at']));

        return $backups;
    }

    /**
     * Delete backup file.
     */
    public function deleteBackup(string $filename): bool
    {
        // Prevent directory traversal attacks
        $filename = basename($filename);
        $path = $this->backupDir . '/' . $filename;
        if (file_exists($path)) {
            return unlink($path);
        }
        return false;
    }

    /**
     * Clean up old backups based on a rotation policy (e.g. older than 30 days).
     */
    public function cleanOldBackups(int $days = 30): int
    {
        $deletedCount = 0;
        if (!is_dir($this->backupDir)) return 0;

        $files = scandir($this->backupDir);
        $thresholdTime = time() - ($days * 24 * 60 * 60);

        foreach ($files as $file) {
            if ($file === '.' || $file === '..' || $file === '.htaccess') continue;
            
            // Target only files starting with backup_ and ending with .zip
            if (str_starts_with($file, 'backup_') && str_ends_with($file, '.zip')) {
                $path = $this->backupDir . '/' . $file;
                if (is_file($path) && filemtime($path) < $thresholdTime) {
                    if (@unlink($path)) {
                        $deletedCount++;
                    }
                }
            }
        }

        return $deletedCount;
    }

    /**
     * Create database backup and compress it.
     */
    public function createBackup(): array
    {
        // Prevent timeout and memory exhaustion during backup of large databases
        @set_time_limit(0);
        @ini_set('memory_limit', '1024M');

        $dbName = config('database.connections.mysql.database');
        $dbHost = config('database.connections.mysql.host', '127.0.0.1');
        $dbUser = config('database.connections.mysql.username');
        $dbPass = config('database.connections.mysql.password');
        $timestamp = date('Y-m-d_H-i-s');
        $sqlFile = "{$this->backupDir}/backup_{$dbName}_{$timestamp}.sql";
        $zipFile = "{$sqlFile}.zip";

        // Try to use mysqldump command first (fast and memory-efficient)
        $mysqldumpPath = $this->findMysqldumpPath();
        
        $success = false;
        $error = '';

        if ($mysqldumpPath) {
            // Build mysqldump command
            // Note: password flag must be attached immediately to -p option if it is not empty
            // We use --single-transaction and --quick to prevent locking tables and reduce memory footprint.
            // We redirect stderr to stdout (2>&1) and stdout to file (> file) to capture errors in PHP while keeping sql file clean.
            $passFlag = $dbPass !== '' ? "-p\"{$dbPass}\"" : "";
            $cmd = "\"{$mysqldumpPath}\" -h \"{$dbHost}\" -u \"{$dbUser}\" {$passFlag} --single-transaction --quick \"{$dbName}\" 2>&1 > \"{$sqlFile}\"";
            
            $output = [];
            $resultCode = null;
            exec($cmd, $output, $resultCode);

            if ($resultCode === 0 && file_exists($sqlFile) && filesize($sqlFile) > 0) {
                $success = true;
            } else {
                $error = "Command failed with code {$resultCode}. Output: " . implode(' ', $output);
                if (file_exists($sqlFile)) {
                    @unlink($sqlFile);
                }
            }
        }

        // Fallback to PHP-based chunked exporter if mysqldump is not available or failed
        if (!$success) {
            try {
                $this->phpFallbackDump($sqlFile);
                $success = true;
            } catch (\Exception $e) {
                $error = "PHP Dump failed: " . $e->getMessage();
                if (file_exists($sqlFile)) {
                    @unlink($sqlFile);
                }
            }
        }

        if ($success && file_exists($sqlFile)) {
            // Compress the SQL file into a ZIP file
            $zip = new ZipArchive();
            if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
                $zip->addFile($sqlFile, basename($sqlFile));
                $zip->close();

                // Zip Integrity Check
                $integrityCheck = false;
                $zipRead = new ZipArchive();
                if ($zipRead->open($zipFile) === true) {
                    $index = $zipRead->locateName(basename($sqlFile));
                    if ($index !== false) {
                        $stat = $zipRead->statIndex($index);
                        if ($stat !== false && $stat['size'] == filesize($sqlFile)) {
                            $integrityCheck = true;
                        }
                    }
                    $zipRead->close();
                }

                if ($integrityCheck) {
                    @unlink($sqlFile); // delete uncompressed SQL file
                    
                    // Run rotation policy (clean up backups older than 30 days)
                    $this->cleanOldBackups(30);

                    return [
                        'success'  => true,
                        'filename' => basename($zipFile),
                        'size_mb'  => round(filesize($zipFile) / 1024 / 1024, 2),
                    ];
                } else {
                    @unlink($zipFile); // delete corrupted zip file
                    return [
                        'success' => false,
                        'error'   => 'Zip integrity check failed: compressed file size mismatch or entry missing.',
                    ];
                }
            } else {
                return [
                    'success' => false,
                    'error'   => 'Failed to create zip archive.',
                ];
            }
        }

        return [
            'success' => false,
            'error'   => $error ?: 'Unknown backup error occurred.',
        ];
    }

    /**
     * Locate mysqldump executable dynamically.
     */
    private function findMysqldumpPath(): ?string
    {
        // 1. Check if mysqldump command is in PATH
        $command = 'where mysqldump';
        if (DIRECTORY_SEPARATOR === '/') {
            $command = 'which mysqldump';
        }
        $output = [];
        $resultCode = null;
        exec($command, $output, $resultCode);
        if ($resultCode === 0 && !empty($output)) {
            return trim($output[0]);
        }

        // 2. Check common paths on XAMPP Windows
        $commonPaths = [
            'c:\\xampp\\mysql\\bin\\mysqldump.exe',
            'd:\\xampp\\mysql\\bin\\mysqldump.exe',
            'c:\\Program Files\\MySQL\\MySQL Server 8.0\\bin\\mysqldump.exe',
        ];

        foreach ($commonPaths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * Chunked SQL Exporter Fallback (Memory-safe).
     */
    private function phpFallbackDump(string $sqlFile): void
    {
        $handle = fopen($sqlFile, 'w');
        if (!$handle) {
            throw new \Exception("Cannot open backup file for writing: $sqlFile");
        }

        // Write header
        fwrite($handle, "-- SGFEP Database Backup Fallback\n");
        fwrite($handle, "-- Date: " . date('Y-m-d H:i:s') . "\n");
        fwrite($handle, "SET FOREIGN_KEY_CHECKS=0;\n\n");

        // Set encoding to UTF-8
        fwrite($handle, "SET NAMES utf8mb4;\n\n");

        $tables = DB::select("SHOW TABLES");
        $keyName = 'Tables_in_' . config('database.connections.mysql.database');

        foreach ($tables as $tableObj) {
            $table = $tableObj->$keyName;

            // Get Create Table structure
            $createTableObj = DB::selectOne("SHOW CREATE TABLE `$table`");
            $createKey = 'Create Table';
            $createSql = $createTableObj->$createKey;
            
            fwrite($handle, "DROP TABLE IF EXISTS `$table`;\n");
            fwrite($handle, $createSql . ";\n\n");

            // Dump data in chunks of 2000 rows to prevent PHP memory limits
            $offset = 0;
            $chunkSize = 2000;
            
            while (true) {
                // Fetch data using query builder offset-limit
                $rows = DB::table($table)->offset($offset)->limit($chunkSize)->get()->toArray();
                if (empty($rows)) {
                    break;
                }

                $inserts = [];
                foreach ($rows as $row) {
                    $rowArray = (array)$row;
                    $escapedValues = array_map(function($val) {
                        if ($val === null) return 'NULL';
                        if (is_numeric($val) && (string)(float)$val === (string)$val) return $val;
                        return "'" . str_replace(["\\", "'", "\r", "\n", "\x1a"], ["\\\\", "\\'", "\\r", "\\n", "\\Z"], $val) . "'";
                    }, $rowArray);

                    $inserts[] = "(" . implode(', ', $escapedValues) . ")";
                }

                if (!empty($inserts)) {
                    $cols = array_keys((array)$rows[0]);
                    $colsString = implode(', ', array_map(fn($c) => "`$c`", $cols));
                    fwrite($handle, "INSERT INTO `$table` ($colsString) VALUES \n" . implode(",\n", $inserts) . ";\n\n");
                }

                $offset += count($rows);
                if (count($rows) < $chunkSize) {
                    break;
                }
            }
        }

        fwrite($handle, "SET FOREIGN_KEY_CHECKS=1;\n");
        fclose($handle);
    }
}
