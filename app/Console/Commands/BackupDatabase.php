<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BackupDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'database:backup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backup the database, compress to gzip, and send to Telegram';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info("Starting database backup process...");

        $botToken = env('TELEGRAM_BOT_TOKEN');
        $chatId   = env('TELEGRAM_CHAT_ID');

        if (!$botToken || !$chatId) {
            $this->error("Telegram credentials are not set in .env!");
            return 1;
        }

        $dbConnection = config('database.default');
        if ($dbConnection !== 'mysql') {
            $this->error("Backup currently only supports mysql database connection!");
            return 1;
        }

        $dbConfig = config("database.connections.{$dbConnection}");
        $host     = $dbConfig['host'] ?? '127.0.0.1';
        $port     = $dbConfig['port'] ?? '3306';
        $database = $dbConfig['database'] ?? '';
        $username = $dbConfig['username'] ?? '';
        $password = $dbConfig['password'] ?? '';

        if (empty($database) || empty($username)) {
            $this->error("Database configuration is incomplete!");
            return 1;
        }

        $dateStr = date('Y_m_d_H_i_s');
        $backupDir = storage_path('app/backups');
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $sqlFile = "{$backupDir}/backup_{$database}_{$dateStr}.sql";
        $gzFile = "{$sqlFile}.gz";

        $backupSuccess = false;

        // Try using mysqldump if available
        $mysqldumpPath = $this->findMysqldump();
        if ($mysqldumpPath) {
            $this->info("Found mysqldump at: {$mysqldumpPath}. Attempting dump...");
            
            // Build command
            $cmd = sprintf(
                '"%s" --host=%s --port=%s --user=%s --password=%s %s > "%s"',
                $mysqldumpPath,
                escapeshellarg($host),
                escapeshellarg($port),
                escapeshellarg($username),
                escapeshellarg($password),
                escapeshellarg($database),
                $sqlFile
            );

            // Execute command
            exec($cmd, $output, $resultCode);

            if ($resultCode === 0 && file_exists($sqlFile) && filesize($sqlFile) > 0) {
                $this->info("mysqldump finished successfully.");
                $backupSuccess = true;
            } else {
                $this->warn("mysqldump failed or returned empty file. falling back to native PHP dumper...");
            }
        } else {
            $this->info("mysqldump utility not found. Using native PHP dumper...");
        }

        // Native PHP dumper fallback
        if (!$backupSuccess) {
            try {
                $this->exportDatabaseNative($host, $port, $database, $username, $password, $sqlFile);
                $backupSuccess = true;
            } catch (\Exception $ex) {
                $this->error("Native PHP export failed: " . $ex->getMessage());
                Log::error("Database Backup Failure (Native): " . $ex->getMessage());
                return 1;
            }
        }

        // Compress sql using streaming gzip (chunk-by-chunk to avoid memory exhaustion)
        if ($backupSuccess && file_exists($sqlFile)) {
            $this->info("Compressing backup file to gzip (streaming mode)...");

            $inputHandle = fopen($sqlFile, 'rb');
            $gzHandle    = gzopen($gzFile, 'wb9'); // wb9 = write binary, compression level 9

            if (!$inputHandle || !$gzHandle) {
                $this->error("Failed to open file handles for compression.");
                return 1;
            }

            $chunkSize = 1024 * 1024; // 1 MB per chunk
            while (!feof($inputHandle)) {
                $chunk = fread($inputHandle, $chunkSize);
                gzwrite($gzHandle, $chunk);
            }

            fclose($inputHandle);
            gzclose($gzHandle);

            // Delete temporary uncompressed .sql file
            unlink($sqlFile);
            $this->info("Compression done. GZ size: " . round(filesize($gzFile) / (1024 * 1024), 2) . " MB");
        } else {
            $this->error("Backup generation failed.");
            return 1;
        }

        // Send to Telegram (auto-split if file > 45 MB due to Bot API limit)
        if (file_exists($gzFile)) {
            $this->info("Sending backup to Telegram...");
            $fileSize   = filesize($gzFile);
            $fileSizeMb = round($fileSize / (1024 * 1024), 2);
            $maxPartBytes = 45 * 1024 * 1024; // 45 MB per part (Telegram Bot API limit: 50 MB)

            $baseName   = "sgfep_backup_{$database}_{$dateStr}";
            $allSuccess = true;

            if ($fileSize <= $maxPartBytes) {
                // ── Single file send ──────────────────────────────────────────
                $caption  = "💾 <b>نسخة احتياطية لقاعدة البيانات</b>\n\n";
                $caption .= "• <b>المنصة:</b> SGFEP\n";
                $caption .= "• <b>قاعدة البيانات:</b> <code>{$database}</code>\n";
                $caption .= "• <b>الحجم:</b> {$fileSizeMb} MB\n";
                $caption .= "• <b>الخادم:</b> " . gethostname() . "\n";
                $caption .= "• <b>التاريخ:</b> " . date('Y-m-d H:i:s') . "\n";
                $caption .= "• <b>الحالة:</b> ناجح ✅";

                $allSuccess = $this->sendDocumentToTelegram($botToken, $chatId, $gzFile, "{$baseName}.sql.gz", $caption);
                unlink($gzFile);

            } else {
                // ── Split into 45 MB parts and send each ─────────────────────
                $totalParts = (int) ceil($fileSize / $maxPartBytes);
                $this->info("File is {$fileSizeMb} MB — splitting into {$totalParts} parts of 45 MB each...");

                $inputHandle = fopen($gzFile, 'rb');
                $partFiles   = [];

                for ($part = 1; $part <= $totalParts; $part++) {
                    $partFile = "{$backupDir}/{$baseName}.part{$part}of{$totalParts}.bin";
                    $partFiles[] = $partFile;

                    $partHandle = fopen($partFile, 'wb');
                    $written    = 0;

                    while ($written < $maxPartBytes && !feof($inputHandle)) {
                        $toRead  = min(1024 * 1024, $maxPartBytes - $written); // read 1 MB at a time
                        $chunk   = fread($inputHandle, $toRead);
                        fwrite($partHandle, $chunk);
                        $written += strlen($chunk);
                    }
                    fclose($partHandle);

                    $partSizeMb = round(filesize($partFile) / (1024 * 1024), 2);
                    $this->info("Sending part {$part}/{$totalParts} ({$partSizeMb} MB)...");

                    $caption  = "💾 <b>نسخة احتياطية - الجزء {$part} من {$totalParts}</b>\n\n";
                    $caption .= "• <b>المنصة:</b> SGFEP\n";
                    $caption .= "• <b>قاعدة البيانات:</b> <code>{$database}</code>\n";
                    $caption .= "• <b>الحجم الكلي:</b> {$fileSizeMb} MB\n";
                    $caption .= "• <b>هذا الجزء:</b> {$partSizeMb} MB\n";
                    $caption .= "• <b>الخادم:</b> " . gethostname() . "\n";
                    $caption .= "• <b>التاريخ:</b> " . date('Y-m-d H:i:s') . "\n";
                    $caption .= "• <b>الحالة:</b> " . ($part === $totalParts ? "اكتمل ✅" : "جارٍ... 📦");

                    $fileName   = "{$baseName}.part{$part}of{$totalParts}.sql.gz";
                    $partResult = $this->sendDocumentToTelegram($botToken, $chatId, $partFile, $fileName, $caption);

                    if (!$partResult) {
                        $this->error("Failed to send part {$part}/{$totalParts}.");
                        $allSuccess = false;
                    }

                    // 2-second pause between parts to avoid Telegram rate-limiting
                    if ($part < $totalParts) {
                        sleep(2);
                    }
                }

                fclose($inputHandle);

                // Cleanup all part files and original gz
                foreach ($partFiles as $pf) {
                    if (file_exists($pf)) {
                        unlink($pf);
                    }
                }
                unlink($gzFile);
            }

            if ($allSuccess) {
                $this->info("✅ Backup sent to Telegram successfully!");
                return 0;
            } else {
                $this->error("⚠️ Some parts failed. Check storage/logs/database_backup_daily.log");
                return 1;
            }
        }

        return 1;
    }

    /**
     * Export database using native PHP PDO calls (No external process dependency).
     */
    protected function exportDatabaseNative($host, $port, $database, $username, $password, $sqlFile)
    {
        $this->info("Running native SQL dumper...");
        
        $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";
        $options = [
            \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
        ];
        
        $pdo = new \PDO($dsn, $username, $password, $options);
        
        $handle = fopen($sqlFile, 'w');
        if (!$handle) {
            throw new \Exception("Cannot create sql file: {$sqlFile}");
        }

        // Header info
        fwrite($handle, "-- SGFEP Database Backup\n");
        fwrite($handle, "-- Generated: " . date('Y-m-d H:i:s') . "\n");
        fwrite($handle, "-- Host: {$host}:{$port}\n");
        fwrite($handle, "-- Database: {$database}\n");
        fwrite($handle, "SET FOREIGN_KEY_CHECKS=0;\n\n");

        // Fetch tables
        $tables = [];
        $stmt = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'");
        while ($row = $stmt->fetch(\PDO::FETCH_NUM)) {
            $tables[] = $row[0];
        }

        foreach ($tables as $table) {
            $this->info("Exporting table schema: {$table}...");
            fwrite($handle, "-- --------------------------------------------------------\n");
            fwrite($handle, "-- Table structure for table `{$table}`\n");
            fwrite($handle, "-- --------------------------------------------------------\n");
            fwrite($handle, "DROP TABLE IF EXISTS `{$table}`;\n");
            
            $createStmt = $pdo->query("SHOW CREATE TABLE `{$table}`");
            $createRow = $createStmt->fetch();
            fwrite($handle, $createRow['Create Table'] . ";\n\n");

            // Dump data
            $this->info("Exporting table data: {$table}...");
            fwrite($handle, "-- Dump data for table `{$table}`\n");
            
            $dataStmt = $pdo->query("SELECT * FROM `{$table}`");
            $rowsCount = 0;
            
            while ($dataRow = $dataStmt->fetch(\PDO::FETCH_ASSOC)) {
                $keys = array_keys($dataRow);
                $escapedKeys = array_map(function($key) { return "`{$key}`"; }, $keys);
                
                $values = array_values($dataRow);
                $escapedValues = array_map(function($value) use ($pdo) {
                    if ($value === null) {
                        return 'NULL';
                    }
                    return $pdo->quote($value);
                }, $values);

                $insertSql = "INSERT INTO `{$table}` (" . implode(', ', $escapedKeys) . ") VALUES (" . implode(', ', $escapedValues) . ");\n";
                fwrite($handle, $insertSql);
                $rowsCount++;
            }
            fwrite($handle, "\n\n");
            $this->info("Table `{$table}` exported. Rows: {$rowsCount}");
        }

        fwrite($handle, "SET FOREIGN_KEY_CHECKS=1;\n");
        fclose($handle);
    }

    /**
     * Find mysqldump path in common server locations or environment path.
     */
    protected function findMysqldump()
    {
        // Check if mysqldump is directly available in the system PATH
        $command = DIRECTORY_SEPARATOR === '\\' ? 'where mysqldump' : 'which mysqldump';
        @exec($command, $output, $returnVar);
        if ($returnVar === 0 && !empty($output[0])) {
            return trim($output[0]);
        }

        // Check common local development paths (XAMPP Windows)
        $commonWindowsPaths = [
            'C:\\xampp\\mysql\\bin\\mysqldump.exe',
            'D:\\xampp\\mysql\\bin\\mysqldump.exe',
        ];

        foreach ($commonWindowsPaths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        // Common Linux binary locations
        $commonLinuxPaths = [
            '/usr/bin/mysqldump',
            '/usr/local/bin/mysqldump',
            '/bin/mysqldump',
        ];

        foreach ($commonLinuxPaths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * Send backup file using curl multipart/form-data to Telegram Bot API.
     */
    protected function sendDocumentToTelegram($botToken, $chatId, $filePath, $fileName, $caption)
    {
        if (!function_exists('curl_init')) {
            Log::error("Curl is not enabled or installed!");
            return false;
        }

        // In PHP 5.5+ we use CURLFile class for file upload
        $curlFile = new \CURLFile($filePath, 'application/x-gzip', $fileName);

        $postData = [
            'chat_id' => $chatId,
            'document' => $curlFile,
            'caption' => $caption,
            'parse_mode' => 'HTML'
        ];

        $url = "https://api.telegram.org/bot{$botToken}/sendDocument";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 120); // Generous timeout for large files

        $response = curl_exec($ch);
        $err = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($err) {
            Log::error("Telegram sendDocument Curl Error: " . $err);
            return false;
        }

        $resDecoded = json_decode($response, true);
        if ($httpCode === 200 && ($resDecoded['ok'] ?? false)) {
            return true;
        }

        Log::error("Telegram sendDocument API Error: " . $response);
        return false;
    }
}
