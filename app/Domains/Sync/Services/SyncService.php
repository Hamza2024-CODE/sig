<?php

namespace App\Domains\Sync\Services;

use App\Core\Database;
use App\Core\HFSQLConnection;
use PDO;
use Exception;

class SyncService
{
    /** @var PDO|\App\Core\LaravelDbAdapter|mixed */
    private mixed $mysql;
    /** @var PDO|HFSQLConnection|mixed */
    private mixed $hfsql;
    private ?string $currentJobId = null;
    private int $lastConnectionCheckTime = 0;

    // Tables that should not be filtered by wilaya/etab
    private const GLOBAL_TABLES = [
        'specialite', 'wilaya', 'dfep', 'roles', 'filiere',
        'branche', 'module', 'zone', 'commune', 'daira', 'session'
    ];

    // Possible wilaya column names in HFSQL tables
    private const WILAYA_COLS = ['IDWilayaa', 'IDWilaya', 'wilaya_id', 'CodeWilaya'];

    // Possible etablissement column names
    private const ETAB_COLS   = ['IDetablissement', 'CodeEtab', 'etab_id', 'IDEts_Form'];

    // Batch size for upsert operations
    private const BATCH_SIZE = 500;

    public function __construct()
    {
        ini_set('memory_limit', '1G');

        $this->mysql = Database::getInstance()->getConnection();

        // Disable strict mode so '0000-00-00', '1970-01-01', and out-of-range
        // numeric values from HFSQL don't abort the entire sync batch.
        // Also set NO_AUTO_VALUE_ON_ZERO so 0 is accepted as an integer PK value.
        try {
            $this->mysql->exec("SET SESSION sql_mode = 'NO_ENGINE_SUBSTITUTION'");
            $this->mysql->exec("SET SESSION time_zone = '+00:00'");
        } catch (\Throwable $e) {
            // Ignore — connection may not support these
        }

        try {
            $this->hfsql = HFSQLConnection::getInstance()->getConnection();
        } catch (Exception $e) {
            throw new Exception("HFSQL Connection Failed: " . $e->getMessage());
        }
    }

    /**
     * Get counts for MySQL and HFSQL.
     * Caches the counts in the sync_reports table.
     */
    public function getTableCounts(string $table, bool $forceRefresh = false): array
    {
        // 1. Check database cache first if not forceRefresh
        if (!$forceRefresh) {
            try {
                $stmt = $this->mysql->prepare("SELECT * FROM sync_reports WHERE table_name = ?");
                $stmt->execute([$table]);
                $cached = $stmt->fetch();
                if ($cached) {
                    $age = time() - strtotime($cached['updated_at']);
                    if ($age < 300) { // 5 minutes cache
                        return [
                            'mysql'  => (int)$cached['mysql_count'],
                            'hfsql'  => (int)$cached['hfsql_count'],
                            'status' => $cached['status']
                        ];
                    }
                }
            } catch (\Throwable $e) {
                // Fail silently and proceed to query
            }
        }

        // 2. Query MySQL
        $mysqlCount = 0;
        try {
            $stmt = $this->mysql->query("SELECT COUNT(*) FROM `$table`");
            $mysqlCount = (int)$stmt->fetchColumn();
        } catch (\Throwable $e) {
            return [
                'mysql'  => 0,
                'hfsql'  => 0,
                'status' => 'LOCAL_ERROR'
            ];
        }

        // 3. Query HFSQL
        $hfsqlTable = $this->resolveHfsqlTableName($table);
        $hfsqlCount = 0;
        $hfsqlExists = true;
        try {
            $stmt = $this->hfsql->query("SELECT COUNT(*) FROM $hfsqlTable");
            $hfsqlCount = (int)$stmt->fetchColumn();
        } catch (\Throwable $e) {
            $hfsqlExists = false;
        }

        // 4. Compare status
        $status = 'UNKNOWN';
        if (!$hfsqlExists) {
            $status = 'LOCAL_ONLY';
        } else {
            if ($mysqlCount === $hfsqlCount) {
                $status = 'SYNCED';
            } elseif ($mysqlCount === 0 && $hfsqlCount > 0) {
                $status = 'EMPTY_LOCALLY';
            } else {
                $status = 'OUTDATED';
            }
        }

        // 5. Save to database cache
        try {
            $this->mysql->prepare(
                "INSERT INTO sync_reports (table_name, mysql_count, hfsql_count, status, updated_at)
                 VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)
                 ON DUPLICATE KEY UPDATE mysql_count = VALUES(mysql_count), hfsql_count = VALUES(hfsql_count), status = VALUES(status), updated_at = CURRENT_TIMESTAMP"
            )->execute([$table, $mysqlCount, $hfsqlCount, $status]);
        } catch (\Throwable $e) {
            // Fail silently
        }

        return [
            'mysql'  => $mysqlCount,
            'hfsql'  => $hfsqlCount,
            'status' => $status
        ];
    }

    // =========================================================
    // PUBLIC: Sync with full Resume, Logging, Progress tracking
    // =========================================================

    /**
     * Full sync with Resume support, Progress tracking, and Logging.
     *
     * @param string      $jobId     UUID of the sync job
     * @param string      $table     MySQL target table name
     * @param string|null $syncType  'wilaya' | 'etab' | null
     * @param int|null    $filterId  Wilaya ID or Etab ID
     * @param string|null $resumeId  Last synced primary key (for resume)
     * @return array
     */
    public function syncTableWithResume(
        string $jobId,
        string $table,
        ?string $syncType = null,
        ?int $filterId = null,
        ?string $resumeId = null
    ): array {
        $this->currentJobId = $jobId;
        $logger = new SyncLogger($jobId);
        $logger->info("Starting sync for table: $table" . ($resumeId ? " (resuming from ID: $resumeId)" : ""));

        // Sanitize empty strings or '0' for range-based PK tables
        $useOffsetPagination = false; // Bypassed: Range pagination is fully supported and avoids offset limits
        if (trim((string)$resumeId) === '' || (!$useOffsetPagination && (string)$resumeId === '0')) {
            $resumeId = null;
        }

        // HFSQL sometimes uses a different table name
        $hfsqlTable = $this->resolveHfsqlTableName($table);

        // Determine WHERE clause
        [$whereClause, $params, $resolvedFilter] = $this->resolveWhereClause(
            $hfsqlTable, $table, $syncType, $filterId, $logger
        );

        // Get local MySQL columns metadata (to avoid "Unknown column" and nullability constraint errors)
        $metadata = $this->getTableMetadata($table);
        if (empty($metadata)) {
            $logger->error("Table '$table' does not exist in MySQL.");
            return ['success' => false, 'message' => "Table '$table' does not exist in MySQL."];
        }

        // Detect primary key for Keyset Pagination (Resume support)
        $pkCol = $this->getPrimaryKey($table);
        $logger->debug("Primary key for $table: " . ($pkCol ?? 'none'));

        // Determine if primary key is numeric to avoid slow quoted comparisons in HFSQL
        $pkType = ($pkCol && isset($metadata[$pkCol])) ? $metadata[$pkCol]['type'] : '';
        $isNumericPk = (str_contains($pkType, 'int') || str_contains($pkType, 'decimal') || str_contains($pkType, 'float') || str_contains($pkType, 'double'));

        // Build resume clause if we have a PK and a resumeId (fallback/keyset only)
        $resumeClause = '';
        if ($pkCol && $resumeId !== null) {
            $isNumericVal = is_numeric($resumeId);
            $valStr = ($isNumericPk && $isNumericVal) ? (string)(float)$resumeId : $this->mysql->quote($resumeId);
            $resumeClause = $whereClause
                ? " AND $pkCol > " . $valStr
                : " WHERE $pkCol > " . $valStr;
        }

        $finalWhere = $whereClause . $resumeClause;

        try {
            // Fetch total row count using COUNT(*) (without resume condition to get fixed total)
            $countSql = "SELECT COUNT(*) FROM $hfsqlTable $whereClause";
            $totalRows = $this->safeCount($countSql, $params, $logger);
            $logger->info("Total rows to sync: $totalRows");

            // Update job with total_rows
            $this->updateJob($jobId, ['total_rows' => $totalRows, 'status' => 'running', 'started_at' => date('Y-m-d H:i:s')]);

            // FIX: start at 0 for fresh runs. If resuming (resumeId is set),
            // seed the count from the previously recorded synced_rows in DB.
            $syncedCount = 0;
            if ($resumeId !== null) {
                try {
                    $prevSynced = (int)$this->mysql->query("SELECT synced_rows FROM sync_jobs WHERE job_id = " . $this->mysql->quote($jobId))->fetchColumn();
                    if ($prevSynced > 0) {
                        $syncedCount = $prevSynced;
                    }
                } catch (\Throwable $e) {}
            }

            $lastId      = $resumeId;
            
            // Set dynamic chunk size to optimize network roundtrips based on total rows
            $chunkSize   = 5000;
            if ($totalRows > 1000000) {
                $chunkSize = 25000;
            } elseif ($totalRows > 200000) {
                $chunkSize = 10000;
            }

            $useRangePagination = ($pkCol && $isNumericPk && !$useOffsetPagination);

            if ($useRangePagination) {
                // ── Range-Based Pagination (fast and bypasses index corruption by not using ORDER BY) ──
                $maxId = null;
                if (strtolower($table) !== 'candidat') {
                    try {
                        $maxStmt = $this->hfsql->query("SELECT MAX($pkCol) FROM $hfsqlTable");
                        $maxVal = $maxStmt->fetchColumn();
                        $maxId = $maxVal !== null && $maxVal !== false ? (int)$maxVal : 0;
                    } catch (\Throwable $e) {
                        $logger->warning("Could not fetch MAX($pkCol): " . $e->getMessage() . ". Using fallback limit.");
                    }
                }

                // If MAX failed or returned 0, set a very high fallback based on the absolute row count to prevent filtered sync truncation
                if ($maxId === null || $maxId === 0) {
                    $absoluteCount = 0;
                    try {
                        $absoluteCount = (int)$this->hfsql->query("SELECT COUNT(*) FROM $hfsqlTable")->fetchColumn();
                    } catch (\Throwable $e) {
                        $logger->warning("Could not fetch absolute COUNT(*) for fallback: " . $e->getMessage());
                    }

                    if ($absoluteCount > 0) {
                        $maxId = (int)($absoluteCount * 2 + 100000);
                    } else {
                        $maxId = $totalRows > 0 ? (int)($totalRows * 10 + 100000) : 10000000;
                    }
                }

                $startId = ($resumeId !== null && is_numeric($resumeId)) ? (int)$resumeId : 0;
                
                $localMaxId = 0;
                try {
                    $localMaxId = (int)$this->mysql->query("SELECT MAX($pkCol) FROM `$table`")->fetchColumn();
                    $localCount = (int)$this->mysql->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
                    if ($totalRows > 0 && $localCount >= $totalRows) {
                        $logger->info("Table $table is already fully synced ($localCount / $totalRows rows locally).");
                        $startId = $maxId + 1; // Bypass the loop
                    }
                } catch (\Throwable $e) {
                    $logger->warning("Could not fetch local MAX/COUNT: " . $e->getMessage());
                }

                if ($totalRows === 0) {
                    $logger->info("Table $table is empty in HFSQL. Skipping sync loop.");
                    $startId = $maxId + 1; // Bypass loop
                }

                $logger->info("Using RANGE pagination for $table. Range: $startId to $maxId. Chunk size: $chunkSize");

                // Prepare statement outside the loop to optimize query compilation on the remote database
                $rangeCondition = "$pkCol >= ? AND $pkCol < ?";
                $chunkWhere = $whereClause
                    ? $whereClause . " AND " . $rangeCondition
                    : " WHERE " . $rangeCondition;

                $sql = "SELECT * FROM $hfsqlTable $chunkWhere";
                $stmt = $this->hfsql->prepare($sql);

                while ($startId <= $maxId) {
                    $prevConnection = $this->hfsql;
                    $this->ensureConnections();
                    
                    // Re-prepare the statement if connection was re-established
                    if ($this->hfsql !== $prevConnection) {
                        $stmt = $this->hfsql->prepare($sql);
                    }

                    $endId = $startId + $chunkSize;
                    
                    $logger->debug("Executing range chunk: $startId to $endId");
                    $execParams = array_merge($params, [$startId, $endId]);
                    $stmt->execute($execParams);

                    $currentBatch = [];
                    $rowsFetched  = 0;

                    while ($rawRow = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        $rowsFetched++;
                        $row = $this->cleanRow($rawRow, $metadata);
                        if (!empty($row)) {
                            $currentBatch[] = $row;
                        }
                    }

                    if ($rowsFetched > 0) {
                        $syncedCount += $rowsFetched;

                        $this->mysql->exec('SET FOREIGN_KEY_CHECKS = 0;');
                        $this->mysql->exec('SET UNIQUE_CHECKS = 0;');
                        $this->mysql->beginTransaction();
                        $this->upsertBatch($table, $currentBatch);
                        $this->mysql->commit();
                        $this->mysql->exec('SET FOREIGN_KEY_CHECKS = 1;');
                        $this->mysql->exec('SET UNIQUE_CHECKS = 1;');

                        $this->updateJob($jobId, [
                            'synced_rows'    => $syncedCount,
                            'last_synced_id' => (string)$endId,
                        ]);
                        $this->updateSyncStatus($table, $syncedCount, $totalRows);

                        $logger->debug("Batch done. Synced so far: $syncedCount / $totalRows");

                        // Check if MySQL row count has reached or exceeded HFSQL total count
                        try {
                            $localCount = (int)$this->mysql->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
                            if ($localCount >= $totalRows) {
                                $logger->info("Breaking range loop: local MySQL row count ($localCount) reached HFSQL total rows ($totalRows).");
                                break;
                            }
                        } catch (\Throwable $e) {
                            // ignore
                        }

                        // Track the maximum ID we have inserted
                        $batchMaxId = 0;
                        foreach ($currentBatch as $r) {
                            if (isset($r[$pkCol])) {
                                $batchMaxId = max($batchMaxId, (int)$r[$pkCol]);
                            }
                        }
                        $localMaxId = max($localMaxId, $batchMaxId);
                    } else {
                        // Empty range (gap). Just update job last_synced_id so we can resume if paused
                        $this->updateJob($jobId, [
                            'last_synced_id' => (string)$endId,
                        ]);

                        // Break early if we have scanned far beyond the maximum ID present in MySQL
                        if ($localMaxId > 0 && $startId > $localMaxId + 100000) {
                            $logger->info("Breaking range loop: scanned past local maximum ID ($localMaxId) by more than 100,000.");
                            break;
                        }
                    }

                    $startId = $endId;

                    $chk = $this->mysql->prepare("SELECT status FROM sync_jobs WHERE job_id = ?");
                    $chk->execute([$jobId]);
                    if ($chk->fetchColumn() === 'paused') {
                        $logger->info("Sync paused at range ID $startId.");
                        return ['success' => true, 'paused' => true, 'synced' => $syncedCount,
                                'total' => $totalRows, 'last_id' => (string)$startId,
                                'message' => "تم إيقاف المزامنة مؤقتاً عند المعرف: $startId"];
                    }

                    if (ob_get_level() > 0) { ob_flush(); }
                    flush();
                }

                $lastId = (string)$maxId;

            } elseif ($pkCol && !$useOffsetPagination) {
                // ── Keyset Pagination fallback (non-numeric PKs) ──
                while (true) {
                    $this->ensureConnections();
                    // Build chunk WHERE clause with the current keyset pointer
                    $chunkWhere = $whereClause;
                    if ($lastId !== null) {
                        $isNumericVal = is_numeric($lastId);
                        $valStr = ($isNumericPk && $isNumericVal) ? (string)(float)$lastId : $this->mysql->quote($lastId);
                        $chunkWhere = $whereClause
                            ? $whereClause . " AND $pkCol > " . $valStr
                            : " WHERE $pkCol > " . $valStr;
                    }

                    $sql = "SELECT * FROM $hfsqlTable $chunkWhere ORDER BY $pkCol ASC LIMIT $chunkSize";

                    $logger->debug("Executing chunk: $sql");
                    $stmt = $this->hfsql->prepare($sql);
                    $stmt->execute($params);

                    $currentBatch = [];
                    $rowsFetched  = 0;

                    while ($rawRow = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        $rowsFetched++;
                        $row = $this->cleanRow($rawRow, $metadata);

                        if (!empty($row)) {
                            $currentBatch[] = $row;
                            if (isset($row[$pkCol])) {
                                $lastId = (string)$row[$pkCol];
                            }
                        }
                    }

                    if ($rowsFetched === 0) {
                        break; // No more records
                    }

                    $syncedCount += $rowsFetched;

                    $this->mysql->exec('SET FOREIGN_KEY_CHECKS = 0;');
                    $this->mysql->exec('SET UNIQUE_CHECKS = 0;');
                    $this->mysql->beginTransaction();
                    $this->upsertBatch($table, $currentBatch);
                    $this->mysql->commit();
                    $this->mysql->exec('SET FOREIGN_KEY_CHECKS = 1;');
                    $this->mysql->exec('SET UNIQUE_CHECKS = 1;');

                    $this->updateJob($jobId, [
                        'synced_rows'    => $syncedCount,
                        'last_synced_id' => $lastId,
                    ]);
                    $this->updateSyncStatus($table, $syncedCount, $totalRows);

                    $chk = $this->mysql->prepare("SELECT status FROM sync_jobs WHERE job_id = ?");
                    $chk->execute([$jobId]);
                    if ($chk->fetchColumn() === 'paused') {
                        $logger->info("Sync paused. last_synced_id: $lastId");
                        return ['success' => true, 'paused' => true, 'synced' => $syncedCount,
                                'total' => $totalRows, 'last_id' => $lastId,
                                'message' => "تم إيقاف المزامنة مؤقتاً عند السجل: $lastId"];
                    }

                    $logger->debug("Batch done. Synced so far: $syncedCount / $totalRows");
                    if (ob_get_level() > 0) { ob_flush(); }
                    flush();
                }

            } elseif ($useOffsetPagination) {
                // ── OFFSET Pagination for candidat (HFSQL corrupted index workaround) ──
                // last_synced_id is reused to store the current OFFSET (not a row ID).
                $offset = ($lastId !== null && is_numeric($lastId)) ? (int)$lastId : 0;
                $logger->info("Using OFFSET pagination for $table. Starting at offset: $offset");

                while (true) {
                    $this->ensureConnections();
                    // No ORDER BY — HFSQL returns rows in natural (insertion) order
                    $sql = "SELECT * FROM $hfsqlTable $whereClause LIMIT $chunkSize OFFSET $offset";

                    $logger->debug("Executing chunk: $sql (params=" . json_encode($params) . ")");
                    $stmt = $this->hfsql->prepare($sql);
                    $stmt->execute($params);

                    $currentBatch = [];
                    $rowsFetched  = 0;

                    while ($rawRow = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        $rowsFetched++;
                        $row = $this->cleanRow($rawRow, $metadata);
                        if (!empty($row)) {
                            $currentBatch[] = $row;
                            if (isset($row[$pkCol])) {
                                $lastId = (string)$row[$pkCol];
                            }
                        }
                    }

                    if ($rowsFetched === 0) {
                        $logger->info("OFFSET $offset returned 0 rows — sync complete.");
                        break;
                    }

                    $offset      += $rowsFetched;
                    $syncedCount += $rowsFetched;

                    $this->mysql->exec('SET FOREIGN_KEY_CHECKS = 0;');
                    $this->mysql->exec('SET UNIQUE_CHECKS = 0;');
                    $this->mysql->beginTransaction();
                    $this->upsertBatch($table, $currentBatch);
                    $this->mysql->commit();
                    $this->mysql->exec('SET FOREIGN_KEY_CHECKS = 1;');
                    $this->mysql->exec('SET UNIQUE_CHECKS = 1;');

                    // Store offset (not row ID) so we can resume correctly
                    $this->updateJob($jobId, [
                        'synced_rows'    => $syncedCount,
                        'last_synced_id' => (string)$offset,
                    ]);
                    $this->updateSyncStatus($table, $syncedCount, $totalRows);

                    $chk = $this->mysql->prepare("SELECT status FROM sync_jobs WHERE job_id = ?");
                    $chk->execute([$jobId]);
                    if ($chk->fetchColumn() === 'paused') {
                        $logger->info("Sync paused at offset $offset.");
                        return ['success' => true, 'paused' => true, 'synced' => $syncedCount,
                                'total' => $totalRows, 'last_id' => (string)$offset,
                                'message' => "تم إيقاف المزامنة مؤقتاً عند الإزاحة: $offset"];
                    }

                    $logger->debug("Batch done. Offset=$offset, Synced so far: $syncedCount / $totalRows");
                    if (ob_get_level() > 0) { ob_flush(); }
                    flush();
                }

            } else {
                $this->ensureConnections();
                // Fallback for tables without primary key (fully buffer/stream)
                $sql = "SELECT * FROM $hfsqlTable $whereClause";

                $logger->debug("Executing fallback: $sql");
                $stmt = $this->hfsql->prepare($sql);
                $stmt->execute($params);

                $currentBatch = [];

                while ($rawRow = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $row = $this->cleanRow($rawRow, $metadata);
                    if (!empty($row)) {
                        $currentBatch[] = $row;
                    }
                    $syncedCount++;

                    if (count($currentBatch) >= self::BATCH_SIZE) {
                        $this->ensureConnections();
                        $this->mysql->exec('SET FOREIGN_KEY_CHECKS = 0;');
                        $this->mysql->exec('SET UNIQUE_CHECKS = 0;');
                        $this->mysql->beginTransaction();

                        $this->upsertBatch($table, $currentBatch);
                        $currentBatch = [];

                        $this->mysql->commit();
                        $this->mysql->exec('SET FOREIGN_KEY_CHECKS = 1;');
                        $this->mysql->exec('SET UNIQUE_CHECKS = 1;');

                        $this->updateJob($jobId, [
                            'synced_rows'    => $syncedCount,
                            'last_synced_id' => $lastId,
                        ]);
                        $this->updateSyncStatus($table, $syncedCount, $totalRows);

                        if (ob_get_level() > 0) { ob_flush(); }
                        flush();
                    }
                }

                if (!empty($currentBatch)) {
                    $this->mysql->exec('SET FOREIGN_KEY_CHECKS = 0;');
                    $this->mysql->exec('SET UNIQUE_CHECKS = 0;');
                    $this->mysql->beginTransaction();

                    $this->upsertBatch($table, $currentBatch);

                    $this->mysql->commit();
                    $this->mysql->exec('SET FOREIGN_KEY_CHECKS = 1;');
                    $this->mysql->exec('SET UNIQUE_CHECKS = 1;');
                }
            }

            // Mark job as done
            $this->updateJob($jobId, [
                'status'         => 'done',
                'synced_rows'    => $totalRows, // Set to totalRows on completion to show 100% in UI
                'total_rows'     => $totalRows, // Keep original total rows count
                'last_synced_id' => $lastId,
                'finished_at'    => date('Y-m-d H:i:s'),
            ]);
            $this->updateSyncStatus($table, $totalRows, $totalRows); // Pass totalRows to show synced status

            $logger->info("Sync completed. Total synced: $syncedCount records.");

            return [
                'success'     => true,
                'synced'      => $syncedCount,
                'total'       => $totalRows,
                'last_id'     => $lastId,
                'message'     => "تمت المزامنة بنجاح. $syncedCount سجل.",
            ];

        } catch (\Throwable $e) {
            if ($this->mysql->inTransaction()) {
                $this->mysql->rollBack();
            }
            $this->mysql->exec('SET FOREIGN_KEY_CHECKS = 1;');

            $errMsg = "Error syncing $table: " . $e->getMessage();
            $logger->error($errMsg);

            $this->updateJob($jobId, [
                'status'        => 'failed',
                'error_message' => $errMsg,
                'finished_at'   => date('Y-m-d H:i:s'),
            ]);

            return ['success' => false, 'message' => $errMsg];
        }
    }

    // =========================================================
    // HELPERS
    // =========================================================

    private function ensureConnections(): void
    {
        $now = time();
        if ($now - $this->lastConnectionCheckTime < 60) {
            return;
        }
        $this->lastConnectionCheckTime = $now;

        // Check mysql connection
        try {
            $this->mysql->query("SELECT 1");
        } catch (\Throwable $e) {
            try {
                \Illuminate\Support\Facades\DB::disconnect();
                $this->mysql = Database::getInstance()->getConnection();
                $this->mysql->exec("SET SESSION sql_mode = 'NO_ENGINE_SUBSTITUTION'");
                $this->mysql->exec("SET SESSION time_zone = '+00:00'");
            } catch (\Throwable $err) {}
        }

        // Check hfsql connection
        try {
            $this->hfsql->query("SELECT 1");
        } catch (\Throwable $e) {
            try {
                $this->hfsql = HFSQLConnection::getInstance()->getConnection();
            } catch (\Throwable $err) {}
        }
    }

    private function resolveHfsqlTableName(string $table): string
    {
        // ✅ خريطة موسّعة: MySQL table name => HFSQL table name
        $map = [
            'evaluations'   => 'evaluation',
            'wilayas'       => 'wilaya',
            'offres'        => 'offre',
            'specialites'   => 'specialite',
            'sections'      => 'section',
            'sessions'      => 'session',
            'candidats'     => 'candidat',
            'apprenants'    => 'apprenant',
            'encadrements'  => 'encadrement',
            'formations'    => 'formation',
            'formateurs'    => 'formateur',
            'modules'       => 'module',
            'diplomes'      => 'diplome',
            'utilisateurs'  => 'utilisateur',
        ];
        return $map[strtolower($table)] ?? $table;
    }

    /**
     * Tries to find the correct WHERE clause by testing candidate column names.
     * Returns [$whereClause, $params, $usedColumn]
     */
    private function resolveWhereClause(
        string $hfsqlTable,
        string $mysqlTable,
        ?string $syncType,
        ?int $filterId,
        SyncLogger $logger
    ): array {
        if (in_array(strtolower($mysqlTable), self::GLOBAL_TABLES) || !$filterId || !$syncType) {
            return ['', [], null];
        }

        // Special case routing for apprenant related tables without direct Wilaya/Etab columns
        if ($syncType === 'wilaya') {
            if (strtolower($mysqlTable) === 'apprenant') {
                $logger->info("Resolved custom subquery for apprenant filtering by Wilaya: $filterId");
                return ["WHERE IDCandidat IN (SELECT IDCandidat FROM candidat WHERE IDWilayaa = ?)", [$filterId], 'IDCandidat'];
            }
            if (in_array(strtolower($mysqlTable), ['apprenant_absence', 'apprenant_fin', 'apprenant_regime', 'apprenant_section_semstre'])) {
                $logger->info("Resolved custom subquery for $mysqlTable filtering by Wilaya: $filterId");
                return ["WHERE IDapprenant IN (SELECT IDapprenant FROM apprenant WHERE IDCandidat IN (SELECT IDCandidat FROM candidat WHERE IDWilayaa = ?))", [$filterId], 'IDapprenant'];
            }
            if (strtolower($mysqlTable) === 'apprenant_section_semstre_module') {
                $logger->info("Resolved custom subquery for $mysqlTable filtering by Wilaya: $filterId");
                return ["WHERE IDapprenant_Section_semstre IN (SELECT IDapprenant_Section_semstre FROM apprenant_section_semstre WHERE IDapprenant IN (SELECT IDapprenant FROM apprenant WHERE IDCandidat IN (SELECT IDCandidat FROM candidat WHERE IDWilayaa = ?)))", [$filterId], 'IDapprenant_Section_semstre'];
            }
        }
        
        if ($syncType === 'etab') {
            if (strtolower($mysqlTable) === 'candidat') {
                $logger->info("Resolved custom subquery for candidat filtering by Etab: $filterId");
                return ["WHERE IDOffre IN (SELECT IDOffre FROM offre WHERE IDEts_Form = ?)", [$filterId], 'IDOffre'];
            }
            if (strtolower($mysqlTable) === 'apprenant') {
                $logger->info("Resolved custom subquery for apprenant filtering by Etab: $filterId");
                return ["WHERE IDCandidat IN (SELECT IDCandidat FROM candidat WHERE IDOffre IN (SELECT IDOffre FROM offre WHERE IDEts_Form = ?))", [$filterId], 'IDCandidat'];
            }
            if (in_array(strtolower($mysqlTable), ['apprenant_absence', 'apprenant_fin', 'apprenant_regime', 'apprenant_section_semstre'])) {
                $logger->info("Resolved custom subquery for $mysqlTable filtering by Etab: $filterId");
                return ["WHERE IDapprenant IN (SELECT IDapprenant FROM apprenant WHERE IDCandidat IN (SELECT IDCandidat FROM candidat WHERE IDOffre IN (SELECT IDOffre FROM offre WHERE IDEts_Form = ?)))", [$filterId], 'IDapprenant'];
            }
            if (strtolower($mysqlTable) === 'apprenant_section_semstre_module') {
                $logger->info("Resolved custom subquery for $mysqlTable filtering by Etab: $filterId");
                return ["WHERE IDapprenant_Section_semstre IN (SELECT IDapprenant_Section_semstre FROM apprenant_section_semstre WHERE IDapprenant IN (SELECT IDapprenant FROM apprenant WHERE IDCandidat IN (SELECT IDCandidat FROM candidat WHERE IDOffre IN (SELECT IDOffre FROM offre WHERE IDEts_Form = ?))))", [$filterId], 'IDapprenant_Section_semstre'];
            }
        }

        $candidates = ($syncType === 'etab') ? self::ETAB_COLS : self::WILAYA_COLS;

        foreach ($candidates as $col) {
            try {
                // ✅ إصلاح: SELECT TOP 1 غير مدعوم في بعض HFSQL ODBC drivers — نستخدم LIMIT 1
                $testSql = "SELECT * FROM $hfsqlTable WHERE $col = ? LIMIT 1";
                $stmt    = $this->hfsql->prepare($testSql);
                $stmt->execute([$filterId]);
                $logger->info("Resolved WHERE column: $col for table $hfsqlTable");
                return ["WHERE $col = ?", [$filterId], $col];
            } catch (\Throwable $e) {
                // Column doesn't exist — try next
                $logger->debug("Column $col not found in $hfsqlTable: " . $e->getMessage());
            }
        }

        // Fallback: no filter (global sync)
        $logger->warning("No filter column found for $hfsqlTable. Falling back to global sync.");
        return ['', [], null];
    }

    private function getTableMetadata(string $table): array
    {
        try {
            $stmt = $this->mysql->query("DESCRIBE `$table`");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $metadata = [];
            foreach ($columns as $col) {
                $metadata[$col['Field']] = [
                    'nullable' => strtoupper($col['Null']) === 'YES',
                    'type'     => strtolower($col['Type']),
                    'default'  => $col['Default'],
                ];
            }
            return $metadata;
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function getPrimaryKey(string $table): ?string
    {
        try {
            $stmt = $this->mysql->prepare(
                "SELECT COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = ?
                   AND CONSTRAINT_NAME = 'PRIMARY'
                 LIMIT 1"
            );
            $stmt->execute([$table]);
            return $stmt->fetchColumn() ?: null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function safeCount(string $sql, array $params, SyncLogger $logger): int
    {
        try {
            $stmt = $this->hfsql->prepare($sql);
            $stmt->execute($params);
            return (int) $stmt->fetchColumn();
        } catch (\Throwable $e) {
            $logger->warning("COUNT failed: " . $e->getMessage() . ". Using 0 as default.");
            return 0;
        }
    }

    private function cleanRow(array $rawRow, array $metadata): array
    {
        $row = [];
        $metaLower = [];
        foreach ($metadata as $key => $meta) {
            $metaLower[strtolower($key)] = $key;
        }

        foreach ($rawRow as $key => $val) {
            // HFSQL pads column names with trailing null bytes (fixed-width char buffers).
            // strip_tags won't help; use str_replace to remove embedded/trailing nulls,
            // then trim whitespace.
            $cleanKey = trim(str_replace("\0", '', $key));
            $cleanKeyLower = strtolower($cleanKey);

            if (isset($metaLower[$cleanKeyLower])) {
                $origKey = $metaLower[$cleanKeyLower];
                $meta = $metadata[$origKey];

                // Decode / Clean string values
                if (is_string($val)) {
                    // Remove HFSQL null-byte padding from values
                    $val = str_replace("\0", '', $val);
                    $val = trim($val);
                    // Robust self-healing conversion for Arabic encodings (UTF-8, Windows-1256, and CP850 double-encoding)
                    $origArabicCount = preg_match_all('/\p{Arabic}/u', $val);
                    $bytes1 = @iconv('UTF-8', 'CP850//IGNORE', $val);
                    if ($bytes1 !== false && $bytes1 !== '') {
                        $fixedByStep1 = false;
                        // 1. Check if one-step conversion works (Type A)
                        if (mb_check_encoding($bytes1, 'UTF-8')) {
                            $fixedArabicCount = preg_match_all('/\p{Arabic}/u', $bytes1);
                            if ($fixedArabicCount > $origArabicCount) {
                                $val = $bytes1;
                                $fixedByStep1 = true;
                            }
                        }
                        // 2. Check if two-step conversion works (Type B)
                        if (!$fixedByStep1) {
                            $fixed2 = @iconv('Windows-1256', 'UTF-8//IGNORE', $bytes1);
                            if ($fixed2 !== false && $fixed2 !== '' && mb_check_encoding($fixed2, 'UTF-8')) {
                                $fixedArabicCount2 = preg_match_all('/\p{Arabic}/u', $fixed2);
                                if ($fixedArabicCount2 > $origArabicCount) {
                                    $val = $fixed2;
                                } else {
                                    // Try standard Windows-1256 to UTF-8 conversion if no Arabic exists
                                    if ($origArabicCount === 0) {
                                        $converted = @iconv('Windows-1256', 'UTF-8//IGNORE', $val);
                                        $val = $converted !== false ? $converted : $val;
                                    }
                                }
                            }
                        }
                    }
                }

                // Handle date/time conversion and empty values
                $type = $meta['type'];
                $isDateType = (str_contains($type, 'date') || str_contains($type, 'time') || str_contains($type, 'year') || str_contains($type, 'timestamp'));

                if ($isDateType) {
                    if ($val === null || (is_string($val) && trim($val) === '')) {
                        $val = $meta['nullable'] ? null : '2000-01-01';
                    } elseif (is_string($val) && in_array(trim($val), ['0000-00-00', '0000-00-00 00:00:00', '1970-01-01 00:00:00', '1970-01-01'], true)) {
                        // HFSQL epoch values / zero-dates that MySQL strict mode rejects
                        $val = $meta['nullable'] ? null : '2000-01-01';
                    }
                }

                // Clamp integer values to avoid Numeric value out of range (1264) SQL errors
                if (str_contains($type, 'int')) {
                    if ($val !== null && $val !== '') {
                        $valFloat = (float)$val;
                        $isUnsigned = str_contains($type, 'unsigned');
                        
                        if (str_contains($type, 'tinyint')) {
                            $minVal = $isUnsigned ? 0 : -128;
                            $maxVal = $isUnsigned ? 255 : 127;
                        } elseif (str_contains($type, 'smallint')) {
                            $minVal = $isUnsigned ? 0 : -32768;
                            $maxVal = $isUnsigned ? 65535 : 32767;
                        } elseif (str_contains($type, 'mediumint')) {
                            $minVal = $isUnsigned ? 0 : -8388608;
                            $maxVal = $isUnsigned ? 16777215 : 8388607;
                        } elseif (str_contains($type, 'bigint')) {
                            $minVal = $isUnsigned ? 0 : PHP_INT_MIN;
                            $maxVal = $isUnsigned ? (float)'18446744073709551615' : PHP_INT_MAX;
                        } else { // standard int
                            $minVal = $isUnsigned ? 0 : -2147483648;
                            $maxVal = $isUnsigned ? 4294967295 : 2147483647;
                        }
                        
                        if ($valFloat < $minVal) {
                            $val = $minVal;
                        } elseif ($valFloat > $maxVal) {
                            $val = $maxVal;
                        } else {
                            $val = (int)$val;
                        }
                    }
                }

                // Handle NULL value for NOT NULL columns of other types
                if ($val === null && !$meta['nullable']) {
                    if (str_contains($type, 'int') || str_contains($type, 'double') || str_contains($type, 'float') || str_contains($type, 'decimal')) {
                        $val = 0;
                    } else {
                        $val = '';
                    }
                }

                $row[$origKey] = $val;
            }
        }
        return $row;
    }

    /**
     * Update fields in sync_jobs for a given job_id.
     */
    public function updateJob(string $jobId, array $fields): void
    {
        if (empty($fields)) return;
        
        // Ensure updated_at is touched to prevent auto-pause sweep from flagging this active job
        $fields['updated_at'] = date('Y-m-d H:i:s');
        
        $setParts = [];
        $values   = [];
        foreach ($fields as $col => $val) {
            $setParts[] = "`$col` = ?";
            $values[]   = $val;
        }
        $values[] = $jobId;
        $sql = "UPDATE sync_jobs SET " . implode(', ', $setParts) . " WHERE job_id = ?";
        try {
            $this->mysql->prepare($sql)->execute($values);
        } catch (\Throwable $e) {
            @file_put_contents(dirname(__DIR__, 4) . '/storage/logs/sync_error.log', "[" . date('Y-m-d H:i:s') . "] updateJob Failed: " . $e->getMessage() . " | SQL: $sql | Values: " . json_encode($values) . "\n", FILE_APPEND);
        }
    }

    private function upsertBatch(string $table, array $rows): void
    {
        if (empty($rows)) return;

        $columns          = array_keys($rows[0]);
        $numCols          = count($columns);
        
        // Calculate a safe batch size to avoid MySQL prepared statement placeholder limit (65,535)
        $maxRowsPerInsert = $numCols > 0 ? floor(60000 / $numCols) : 1000;
        if ($maxRowsPerInsert > 500) {
            $maxRowsPerInsert = 500; // Cap at 500 to prevent exceeding MySQL max_allowed_packet (1MB)
        }
        if ($maxRowsPerInsert < 1) $maxRowsPerInsert = 1;
        
        $rowChunks = array_chunk($rows, $maxRowsPerInsert);
        
        foreach ($rowChunks as $chunk) {
            $colsString       = implode(', ', array_map(fn($c) => "`$c`", $columns));
            $rowPlaceholder   = '(' . implode(', ', array_fill(0, $numCols, '?')) . ')';
            $allPlaceholders  = implode(', ', array_fill(0, count($chunk), $rowPlaceholder));
            $updateParts      = array_map(fn($c) => "`$c` = VALUES(`$c`)", $columns);
            $updateString     = implode(', ', $updateParts);

            $sql = "INSERT INTO `$table` ($colsString) VALUES $allPlaceholders ON DUPLICATE KEY UPDATE $updateString";

            try {
                $stmt       = $this->mysql->prepare($sql);
                $flatValues = [];
                foreach ($chunk as $row) {
                    foreach ($columns as $col) {
                        $flatValues[] = $row[$col] ?? null;
                    }
                }
                $stmt->execute($flatValues);
            } catch (\PDOException $e) {
                // Self-Healing Fallback: Isolate bad rows and insert the rest one by one
                if ($this->mysql->inTransaction()) {
                    try {
                        $this->mysql->rollBack();
                    } catch (\Throwable $rollbackEx) {}
                }
                
                try {
                    $this->mysql->beginTransaction();
                } catch (\Throwable $beginEx) {}

                $pkCol = $this->getPrimaryKey($table);

                foreach ($chunk as $row) {
                    $rowSql = "INSERT INTO `$table` ($colsString) VALUES (" . implode(', ', array_fill(0, $numCols, '?')) . ") ON DUPLICATE KEY UPDATE $updateString";
                    try {
                        $rowStmt = $this->mysql->prepare($rowSql);
                        $rowValues = [];
                        foreach ($columns as $col) {
                            $rowValues[] = $row[$col] ?? null;
                        }
                        $rowStmt->execute($rowValues);
                    } catch (\PDOException $rowException) {
                        $recordId = ($pkCol && isset($row[$pkCol])) ? (string)$row[$pkCol] : null;
                        $payload = json_encode($row, JSON_UNESCAPED_UNICODE);
                        $errMsg = $rowException->getMessage();

                        if ($this->currentJobId) {
                            $logger = new SyncLogger($this->currentJobId);
                            $logger->warning("Isolated row error in table $table (ID: " . ($recordId ?? 'unknown') . "): " . $errMsg);
                        }

                        // Save row to sync_errors for self-healing/retry mechanism
                        try {
                            $errStmt = $this->mysql->prepare("
                                INSERT INTO sync_errors (job_id, table_name, record_id, error_message, payload, status, retry_count, created_at, updated_at)
                                VALUES (?, ?, ?, ?, ?, 'pending_retry', 0, NOW(), NOW())
                            ");
                            $errStmt->execute([$this->currentJobId, $table, $recordId, $errMsg, $payload]);
                        } catch (\Throwable $errDbEx) {
                            // Fail silently for logger error
                        }
                    }
                }
            }
        }
    }

    /**
     * Calculate and update sync readiness in sync_status table.
     */
    private function updateSyncStatus(string $table, int $synced, int $total): void
    {
        // Query MySQL dynamically for the true count of rows
        $realCount = 0;
        try {
            $realCount = (int)$this->mysql->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
        } catch (\Throwable $e) {
            $realCount = $synced; // fallback
        }

        // Get absolute HFSQL count to prevent filtered sync stats from corrupting the reports/readiness
        $absoluteTotal = $total;
        try {
            $hfsqlTable = $this->resolveHfsqlTableName($table);
            $absoluteTotal = (int)$this->hfsql->query("SELECT COUNT(*) FROM $hfsqlTable")->fetchColumn();
        } catch (\Throwable $e) {
            // fallback to $total
        }

        if ($absoluteTotal > 0) {
            $isReady = (($realCount / $absoluteTotal) >= 0.95) ? 1 : 0;
            try {
                $stmt = $this->mysql->prepare("
                    INSERT INTO sync_status (table_name, is_ready)
                    VALUES (?, ?)
                    ON DUPLICATE KEY UPDATE is_ready = ?, updated_at = NOW()
                ");
                $stmt->execute([$table, $isReady, $isReady]);
            } catch (\Throwable $e) {
                // Silently ignore DB errors so it doesn't interrupt the worker
            }
        }

        // Live cache sync status in sync_reports for Compare dashboard (using absolute count)
        try {
            $status = 'UNKNOWN';
            if ($realCount === $absoluteTotal) {
                $status = 'SYNCED';
            } elseif ($realCount === 0 && $absoluteTotal > 0) {
                $status = 'EMPTY_LOCALLY';
            } else {
                $status = 'OUTDATED';
            }

            $stmt = $this->mysql->prepare("
                INSERT INTO sync_reports (table_name, mysql_count, hfsql_count, status, updated_at)
                VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)
                ON DUPLICATE KEY UPDATE mysql_count = VALUES(mysql_count), hfsql_count = VALUES(hfsql_count), status = VALUES(status), updated_at = CURRENT_TIMESTAMP
            ");
            $stmt->execute([$table, $realCount, $absoluteTotal, $status]);
        } catch (\Throwable $e) {
            // Silently ignore DB errors so it doesn't interrupt the worker
        }
    }

    // =========================================================
    // STATIC: Queue management helpers
    // =========================================================

    /**
     * Create a new sync_jobs entry and return the job_id.
     */
    public static function createJob(string $jobId, string $table): void
    {
        $db = Database::getInstance()->getConnection();
        $db->prepare(
            "INSERT IGNORE INTO sync_jobs (job_id, table_name, status) VALUES (?, ?, 'pending')"
        )->execute([$jobId, $table]);
    }

    /**
     * Get all tables from MySQL dynamically.
     */
    public static function getAllMysqlTables(): array
    {
        $db   = Database::getInstance()->getConnection();
        $stmt = $db->query('SHOW TABLES');
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}
