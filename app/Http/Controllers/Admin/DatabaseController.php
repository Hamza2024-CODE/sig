<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use App\Core\AuditLogger;
use PDO;

class DatabaseController extends Controller
{
    protected $db;

    public function __construct()
    {
        if (app()->runningInConsole()) { return; }
        $this->db = new \App\Core\LaravelDbAdapter();
    }

    public function index()
    {
        $role_code = session('user')['role_code'] ?? '';
        if ($role_code !== 'admin') {
            session(['flash_error' => 'غير مسموح لك بالوصول إلى هذه الصفحة / Accès refusé.']);
            return $this->redirect('/dashboard');
        }

        $tables = [];
        try {
            // Get all tables and estimated counts in a single query from information_schema to prevent timeouts
            $stmt = $this->db->query("
                SELECT TABLE_NAME, TABLE_ROWS 
                FROM INFORMATION_SCHEMA.TABLES 
                WHERE TABLE_SCHEMA = DATABASE()
                ORDER BY TABLE_NAME ASC
            ");
            $rawTables = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($rawTables as $row) {
                $tables[] = [
                    'name' => $row['TABLE_NAME'],
                    'rows' => (int)($row['TABLE_ROWS'] ?? 0)
                ];
            }
        } catch (\Exception $e) {
            session(['flash_error' => 'خطأ في استعلام الجداول: ' . $e->getMessage()]);
        }

        return $this->render('admin/database/index', [
            'title' => 'مدير قاعدة البيانات والمخططات / Database & Tables Manager',
            'tables' => $tables
        ]);
    }

    public function describeTable()
    {
        $role_code = session('user')['role_code'] ?? '';
        if ($role_code !== 'admin') {
            return $this->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $table = request()->all()['table'] ?? '';
        if (empty($table)) {
            return $this->json(['success' => false, 'message' => 'Table name is required'], 400);
        }

        try {
            $stmt = $this->db->prepare("DESCRIBE `$table`");
            $stmt->execute();
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $this->json(['success' => true, 'columns' => $columns]);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function executeQuery()
    {
        $role_code = session('user')['role_code'] ?? '';
        if ($role_code !== 'admin') {
            return $this->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        if (request()->isMethod('post')) {
            $sql = trim(request()->all()['sql'] ?? '');
            if (empty($sql)) {
                session(['flash_error' => 'لا يمكن تنفيذ استعلام فارغ.']);
                return $this->redirect('/dashboard/database');
            }

            AuditLogger::logWarning("[DBADMIN] Admin executed SQL query: " . substr($sql, 0, 500));

            try {
                $stmt = $this->db->prepare($sql);
                $stmt->execute();

                $isSelect = preg_match('/^\s*(SELECT|SHOW|DESCRIBE|EXPLAIN)/i', $sql);
                
                if ($isSelect) {
                    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    session(['query_results' => $results]);
                    session(['query_sql' => $sql]);
                    session(['flash_success' => 'تم تنفيذ الاستعلام بنجاح! تم استرجاع ' . count($results) . ' صف.']);
                } else {
                    $affectedRows = $stmt->rowCount();
                    session(['flash_success' => "تم تنفيذ الاستعلام بنجاح! عدد الصفوف المتأثرة: $affectedRows"]);
                }
            } catch (\Exception $e) {
                session(['flash_error' => 'خطأ أثناء تنفيذ الاستعلام: ' . $e->getMessage()]);
                session(['query_sql' => $sql]);
            }
        }

        return $this->redirect('/dashboard/database');
    }

    /**
     * DYNAMIC CRUD: Fetch Paginated Table Data
     */
    public function getTableData()
    {
        $role_code = session('user')['role_code'] ?? '';
        if ($role_code !== 'admin') {
            return $this->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $table = request()->all()['table'] ?? '';
        $page  = max(1, (int)(request()->all()['page'] ?? 1));
        $limit = 15;
        $offset = ($page - 1) * $limit;
        $search = trim(request()->all()['q'] ?? '');

        if (empty($table)) {
            return $this->json(['success' => false, 'message' => 'Table name is required'], 400);
        }

        try {
            // Get columns to build search query dynamically
            $descStmt = $this->db->prepare("DESCRIBE `$table`");
            $descStmt->execute();
            $columns = $descStmt->fetchAll(PDO::FETCH_ASSOC);

            $searchCond = '';
            $params = [];
            
            if (!empty($search)) {
                $conds = [];
                foreach ($columns as $col) {
                    $colName = $col['Field'];
                    $conds[] = "`$colName` LIKE :search";
                }
                if (!empty($conds)) {
                    $searchCond = "WHERE " . implode(" OR ", $conds);
                    $params['search'] = "%$search%";
                }
            }

            // Count total
            $countQuery = "SELECT COUNT(*) FROM `$table` $searchCond";
            $countStmt = $this->db->prepare($countQuery);
            $countStmt->execute($params);
            $totalRows = (int)$countStmt->fetchColumn();
            $totalPages = ceil($totalRows / $limit);

            // Fetch data
            $dataQuery = "SELECT * FROM `$table` $searchCond LIMIT $limit OFFSET $offset";
            $dataStmt = $this->db->prepare($dataQuery);
            $dataStmt->execute($params);
            $rows = $dataStmt->fetchAll(PDO::FETCH_ASSOC);

            return $this->json([
                'success' => true,
                'columns' => $columns,
                'rows' => $rows,
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => $totalPages,
                    'total_rows' => $totalRows
                ]
            ]);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * DYNAMIC CRUD: Insert Row
     */
    public function insertRow()
    {
        $role_code = session('user')['role_code'] ?? '';
        if ($role_code !== 'admin') {
            return $this->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        if (request()->isMethod('post')) {
            $table = request()->all()['table'] ?? '';
            $data = request()->all()['fields'] ?? [];

            if (empty($table) || empty($data)) {
                return $this->json(['success' => false, 'message' => 'Table and fields data are required'], 400);
            }

            try {
                $cols = [];
                $placeholders = [];
                $params = [];

                foreach ($data as $col => $val) {
                    $cols[] = "`$col`";
                    $placeholders[] = ":$col";
                    // Handle empty values as NULL or default
                    $params[$col] = ($val === '') ? null : $val;
                }

                $query = "INSERT INTO `$table` (" . implode(', ', $cols) . ") VALUES (" . implode(', ', $placeholders) . ")";
                $stmt = $this->db->prepare($query);
                $stmt->execute($params);

                AuditLogger::logWarning("[DBADMIN] Admin inserted a row into `$table`");
                return $this->json(['success' => true, 'message' => 'تم إضافة الصف بنجاح!']);
            } catch (\Exception $e) {
                return $this->json(['success' => false, 'message' => $e->getMessage()], 500);
            }
        }
    }

    /**
     * DYNAMIC CRUD: Update Row
     */
    public function updateRow()
    {
        $role_code = session('user')['role_code'] ?? '';
        if ($role_code !== 'admin') {
            return $this->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        if (request()->isMethod('post')) {
            $table = request()->all()['table'] ?? '';
            $data = request()->all()['fields'] ?? [];
            $pkCol = request()->all()['pk_column'] ?? '';
            $pkVal = request()->all()['pk_val'] ?? '';

            if (empty($table) || empty($data) || empty($pkCol) || empty($pkVal)) {
                return $this->json(['success' => false, 'message' => 'Missing required data parameters'], 400);
            }

            try {
                $sets = [];
                $params = [];

                foreach ($data as $col => $val) {
                    // Do not update the primary key column itself in sets
                    if ($col === $pkCol) continue;
                    
                    $sets[] = "`$col` = :$col";
                    $params[$col] = ($val === '') ? null : $val;
                }

                $params['pkVal'] = $pkVal;
                $query = "UPDATE `$table` SET " . implode(', ', $sets) . " WHERE `$pkCol` = :pkVal";
                
                $stmt = $this->db->prepare($query);
                $stmt->execute($params);

                AuditLogger::logWarning("[DBADMIN] Admin updated row where `$pkCol` = `$pkVal` in `$table`");
                return $this->json(['success' => true, 'message' => 'تم تحديث الصف بنجاح!']);
            } catch (\Exception $e) {
                return $this->json(['success' => false, 'message' => $e->getMessage()], 500);
            }
        }
    }

    /**
     * DYNAMIC CRUD: Delete Row
     */
    public function deleteRow()
    {
        $role_code = session('user')['role_code'] ?? '';
        if ($role_code !== 'admin') {
            return $this->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        if (request()->isMethod('post')) {
            $table = request()->all()['table'] ?? '';
            $pkCol = request()->all()['pk_column'] ?? '';
            $pkVal = request()->all()['pk_val'] ?? '';

            if (empty($table) || empty($pkCol) || empty($pkVal)) {
                return $this->json(['success' => false, 'message' => 'Missing delete parameters'], 400);
            }

            try {
                $query = "DELETE FROM `$table` WHERE `$pkCol` = :pkVal";
                $stmt = $this->db->prepare($query);
                $stmt->execute(['pkVal' => $pkVal]);

                AuditLogger::logWarning("[DBADMIN] Admin deleted row where `$pkCol` = `$pkVal` in `$table`");
                return $this->json(['success' => true, 'message' => 'تم حذف الصف بنجاح!']);
            } catch (\Exception $e) {
                return $this->json(['success' => false, 'message' => $e->getMessage()], 500);
            }
        }
    }

    /**
     * DYNAMIC ANALYTICS: Show Database Analytics Dashboard
     */
    public function analytics()
    {
        $role_code = session('user')['role_code'] ?? '';
        if ($role_code !== 'admin') {
            session(['flash_error' => 'غير مسموح لك بالوصول إلى هذه الصفحة / Accès refusé.']);
            return $this->redirect('/dashboard');
        }

        // Retrieve or generate analytics data
        $data = cache()->remember('db_analytics_report', 86400, function () {
            return $this->generateAnalyticsReport();
        });

        return $this->render('admin/database/analytics', array_merge([
            'title' => 'تحليلات وجودة البيانات / Database Analytics & Quality',
        ], $data));
    }

    /**
     * DYNAMIC ANALYTICS: Refresh Cached Stats
     */
    public function refreshAnalytics()
    {
        $role_code = session('user')['role_code'] ?? '';
        if ($role_code !== 'admin') {
            session(['flash_error' => 'غير مسموح لك بالوصول إلى هذه الصفحة / Accès refusé.']);
            return $this->redirect('/dashboard');
        }

        cache()->forget('db_analytics_report');
        session(['flash_success' => 'تم إعادة تحليل قاعدة البيانات وتحديث الإحصائيات بنجاح!']);
        return $this->redirect('/dashboard/database/analytics');
    }

    /**
     * DYNAMIC ANALYTICS: Explain Query Plan & Table Diagnostics
     */
    public function explainTable()
    {
        $role_code = session('user')['role_code'] ?? '';
        if ($role_code !== 'admin') {
            return $this->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $table = request()->all()['table'] ?? '';
        if (empty($table)) {
            return $this->json(['success' => false, 'message' => 'Table name is required'], 400);
        }

        $result = cache()->remember("db_explain_{$table}", 86400, function () use ($table) {
            try {
                // DESCRIBE Table Structure
                $descStmt = $this->db->prepare("DESCRIBE `$table`");
                $descStmt->execute();
                $columns = $descStmt->fetchAll(PDO::FETCH_ASSOC);

                // EXPLAIN Query Plan
                $explainStmt = $this->db->prepare("EXPLAIN SELECT * FROM `$table` WHERE 1");
                $explainStmt->execute();
                $explain = $explainStmt->fetchAll(PDO::FETCH_ASSOC);

                // Formulate intelligent recommendations based on indexing
                $hasPk = false;
                $indexedCols = [];
                $foreignKeys = [];
                $recommendations = [];

                foreach ($columns as $col) {
                    if ($col['Key'] === 'PRI') {
                        $hasPk = true;
                    }
                    if (in_array($col['Key'], ['PRI', 'UNI', 'MUL'])) {
                        $indexedCols[] = $col['Field'];
                    }
                    // Guess foreign keys based on name signature (starts with ID or ends with _id)
                    if (str_starts_with($col['Field'], 'ID') || str_ends_with(strtolower($col['Field']), '_id') || str_ends_with(strtolower($col['Field']), 'id')) {
                        if ($col['Key'] !== 'PRI') {
                            $foreignKeys[] = $col['Field'];
                        }
                    }
                }

                if (!$hasPk) {
                    $recommendations[] = "تنبيه: الجدول لا يحتوي على مفتاح أساسي (Primary Key). ينصح بشدة بإضافة مفتاح أساسي لضمان سلامة البيانات وتسريع الاستعلامات.";
                }

                // Check if any guessed foreign keys are not indexed
                foreach ($foreignKeys as $fk) {
                    if (!in_array($fk, $indexedCols)) {
                        $recommendations[] = "تحذير: العمود `$fk` يبدو كمعرف خارجي (Foreign Key) ولكنه غير مفهرس. ينصح بإنشاء فهرس (Index) عليه لتحسين سرعة الربط (JOINs).";
                    }
                }

                // Check index vs data ratio in explain or estimates
                foreach ($explain as $exp) {
                    if (isset($exp['rows']) && (int)$exp['rows'] > 100000 && empty($exp['key'])) {
                        $recommendations[] = "تنبيه أداء: الاستعلام الافتراضي يقرأ أكثر من " . number_format($exp['rows']) . " صف دون استخدام أي فهارس. تأكد من استخدام فهارس في استعلامات التصفية.";
                    }
                }

                if (empty($recommendations)) {
                    $recommendations[] = "بنية الجدول ممتازة ومفهرسة بشكل جيد! لا توجد مشاكل واضحة في الأداء.";
                }

                return [
                    'success' => true,
                    'columns' => $columns,
                    'explain' => $explain,
                    'recommendations' => $recommendations
                ];

            } catch (\Exception $e) {
                return [
                    'success' => false,
                    'message' => $e->getMessage()
                ];
            }
        });

        return $this->json($result);
    }

    /**
     * DYNAMIC ANALYTICS: Generate Stats Report
     */
    private function generateAnalyticsReport()
    {
        $report = [
            'tables' => [],
            'total_size_mb' => 0,
            'total_rows' => 0,
            'total_tables' => 0,
            'engines' => [],
            'collations' => [],
            'top_by_size' => [],
            'top_by_rows' => [],
            'index_to_data_ratio' => 0,
            
            // Health issues
            'missing_pks' => [],
            'empty_tables' => 0,
            'high_index_write_warnings' => [], // Index vs Data ratio > 1:1 on tables with rows > 50k
            
            // Encoding health (Mojibake sample check)
            'scanned_columns_count' => 0,
            'mojibake_health_score' => 100, // percentage
            'mojibake_warnings' => [], // tables with suspected mojibake
            
            // Sync status metrics
            'sync_metrics' => [
                'completed' => 0,
                'failed' => 0,
                'running' => 0,
                'pending' => 0,
                'paused' => 0,
                'total_synced_rows' => 0,
                'success_rate' => 100
            ],
            'generated_at' => date('Y-m-d H:i:s')
        ];

        try {
            // 1. Structural Metadata Layer
            $stmt = $this->db->query("
                SELECT 
                    TABLE_NAME, 
                    ENGINE, 
                    TABLE_COLLATION, 
                    DATA_LENGTH, 
                    INDEX_LENGTH, 
                    TABLE_ROWS 
                FROM INFORMATION_SCHEMA.TABLES 
                WHERE TABLE_SCHEMA = DATABASE()
            ");
            $rawTables = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $report['total_tables'] = count($rawTables);

            $totalDataBytes = 0;
            $totalIndexBytes = 0;

            foreach ($rawTables as $row) {
                $name = $row['TABLE_NAME'];
                $rows = (int)($row['TABLE_ROWS'] ?? 0);
                $dataLen = (int)($row['DATA_LENGTH'] ?? 0);
                $indexLen = (int)($row['INDEX_LENGTH'] ?? 0);
                $totalBytes = $dataLen + $indexLen;
                $sizeMb = round($totalBytes / 1024 / 1024, 2);
                $engine = $row['ENGINE'] ?? 'UNKNOWN';
                $collation = $row['TABLE_COLLATION'] ?? 'UNKNOWN';

                $report['total_rows'] += $rows;
                $totalDataBytes += $dataLen;
                $totalIndexBytes += $indexLen;

                $report['tables'][$name] = [
                    'name' => $name,
                    'rows' => $rows,
                    'size_mb' => $sizeMb,
                    'engine' => $engine,
                    'collation' => $collation,
                    'data_mb' => round($dataLen / 1024 / 1024, 2),
                    'index_mb' => round($indexLen / 1024 / 1024, 2),
                ];

                // Engine counts
                $report['engines'][$engine] = ($report['engines'][$engine] ?? 0) + 1;
                // Collation counts
                $report['collations'][$collation] = ($report['collations'][$collation] ?? 0) + 1;

                if ($rows === 0) {
                    $report['empty_tables']++;
                }

                // Check index vs data ratio > 1:1 on large tables (> 50k rows)
                if ($rows > 50000 && $dataLen > 0) {
                    $ratio = $indexLen / $dataLen;
                    if ($ratio > 1.0) {
                        $report['high_index_write_warnings'][] = [
                            'table' => $name,
                            'rows' => $rows,
                            'ratio' => round($ratio, 2),
                            'index_mb' => round($indexLen / 1024 / 1024, 2),
                            'data_mb' => round($dataLen / 1024 / 1024, 2)
                        ];
                    }
                }
            }

            $report['total_size_mb'] = round(($totalDataBytes + $totalIndexBytes) / 1024 / 1024, 2);
            if ($totalDataBytes > 0) {
                $report['index_to_data_ratio'] = round($totalIndexBytes / $totalDataBytes, 2);
            }

            // Top Tables by size
            $sizeSorted = $report['tables'];
            uasort($sizeSorted, function ($a, $b) {
                return $b['size_mb'] <=> $a['size_mb'];
            });
            $report['top_by_size'] = array_slice($sizeSorted, 0, 10, true);

            // Top Tables by rows
            $rowsSorted = $report['tables'];
            uasort($rowsSorted, function ($a, $b) {
                return $b['rows'] <=> $a['rows'];
            });
            $report['top_by_rows'] = array_slice($rowsSorted, 0, 10, true);

            // 2. Find tables missing primary keys
            $pkStmt = $this->db->query("
                SELECT t.TABLE_NAME
                FROM INFORMATION_SCHEMA.TABLES t
                LEFT JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE k
                  ON t.TABLE_SCHEMA = k.TABLE_SCHEMA
                  AND t.TABLE_NAME = k.TABLE_NAME
                  AND k.CONSTRAINT_NAME = 'PRIMARY'
                WHERE t.TABLE_SCHEMA = DATABASE()
                  AND k.COLUMN_NAME IS NULL
            ");
            $report['missing_pks'] = $pkStmt->fetchAll(PDO::FETCH_COLUMN);

            // 3. Sync & Queue Audits
            try {
                $syncStmt = $this->db->query("
                    SELECT status, COUNT(*) as cnt, SUM(synced_rows) as total_synced
                    FROM sync_jobs
                    GROUP BY status
                ");
                $syncRows = $syncStmt->fetchAll(PDO::FETCH_ASSOC);
                $done = 0;
                $failed = 0;
                foreach ($syncRows as $srow) {
                    $status = strtolower($srow['status']);
                    if (in_array($status, ['done', 'completed', 'success'])) {
                        $report['sync_metrics']['completed'] += $srow['cnt'];
                        $done += $srow['cnt'];
                    } elseif (in_array($status, ['failed', 'error'])) {
                        $report['sync_metrics']['failed'] += $srow['cnt'];
                        $failed += $srow['cnt'];
                    } elseif ($status === 'running') {
                        $report['sync_metrics']['running'] += $srow['cnt'];
                    } elseif ($status === 'pending') {
                        $report['sync_metrics']['pending'] += $srow['cnt'];
                    } elseif ($status === 'paused') {
                        $report['sync_metrics']['paused'] += $srow['cnt'];
                    }
                    $report['sync_metrics']['total_synced_rows'] += (int)$srow['total_synced'];
                }
                if ($done + $failed > 0) {
                    $report['sync_metrics']['success_rate'] = round(($done / ($done + $failed)) * 100, 1);
                }
            } catch (\Exception $e) {
                // sync_jobs table might not exist
            }

            // 4. Deep Content Sampling (Mojibake Check)
            // Select the top 20 most populated tables to inspect
            $tablesToScan = array_slice($rowsSorted, 0, 20, true);
            $totalCheckedFields = 0;
            $mojibakeFieldsDetected = 0;

            foreach ($tablesToScan as $tName => $tMeta) {
                if ($tMeta['rows'] === 0) continue;

                // Find text columns
                $colStmt = $this->db->prepare("
                    SELECT COLUMN_NAME 
                    FROM INFORMATION_SCHEMA.COLUMNS 
                    WHERE TABLE_SCHEMA = DATABASE() 
                      AND TABLE_NAME = ? 
                      AND DATA_TYPE IN ('char', 'varchar', 'text', 'mediumtext', 'longtext')
                ");
                $colStmt->execute([$tName]);
                $textCols = $colStmt->fetchAll(PDO::FETCH_COLUMN);

                if (empty($textCols)) continue;

                $colsEscaped = array_map(fn($c) => "`$c`", $textCols);
                $colsSql = implode(', ', $colsEscaped);
                
                $rowsCount = $tMeta['rows'];
                $sampleSize = min(500, $rowsCount);
                
                try {
                    // Check if there is any Primary Key column for index-based sorting
                    $pkCheckStmt = $this->db->prepare("
                        SELECT COLUMN_NAME
                        FROM INFORMATION_SCHEMA.COLUMNS
                        WHERE TABLE_SCHEMA = DATABASE()
                          AND TABLE_NAME = ?
                          AND COLUMN_KEY = 'PRI'
                        LIMIT 1
                    ");
                    $pkCheckStmt->execute([$tName]);
                    $pkColRow = $pkCheckStmt->fetch(PDO::FETCH_ASSOC);

                    $samples = [];
                    if ($pkColRow) {
                        $pkColName = $pkColRow['COLUMN_NAME'];
                        // Fetch 50 rows from the top and 50 rows from the bottom (extremely fast due to index scans)
                        $topStmt = $this->db->query("SELECT $colsSql FROM `$tName` ORDER BY `$pkColName` ASC LIMIT 50");
                        $topRows = $topStmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        $bottomStmt = $this->db->query("SELECT $colsSql FROM `$tName` ORDER BY `$pkColName` DESC LIMIT 50");
                        $bottomRows = $bottomStmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        $samples = array_merge($topRows, $bottomRows);
                    } else {
                        // Direct fallback scan of first 100 rows
                        $sampleStmt = $this->db->query("SELECT $colsSql FROM `$tName` LIMIT 100");
                        $samples = $sampleStmt->fetchAll(PDO::FETCH_ASSOC);
                    }

                    $tableMojibakeCount = 0;
                    foreach ($samples as $sampleRow) {
                        foreach ($textCols as $col) {
                            $val = $sampleRow[$col] ?? '';
                            if (empty($val)) continue;
                            
                            $totalCheckedFields++;
                            
                            // Check for double-encoding Mojibake signatures (e.g., 'Ï', '┘', '┬', 'Ô')
                            if (strpos($val, 'Ï') !== false || strpos($val, '┘') !== false || strpos($val, '┬') !== false || strpos($val, 'Ô') !== false) {
                                $mojibakeFieldsDetected++;
                                $tableMojibakeCount++;
                            }
                        }
                    }

                    if ($tableMojibakeCount > 0) {
                        $report['mojibake_warnings'][] = [
                            'table' => $tName,
                            'sample_size' => count($samples),
                            'columns' => $textCols,
                            'bad_fields_ratio' => round(($tableMojibakeCount / max(1, count($samples) * count($textCols))) * 100, 1)
                        ];
                    }

                } catch (\Exception $ex) {
                    // Fail-safe
                }
            }

            $report['scanned_columns_count'] = $totalCheckedFields;
            if ($totalCheckedFields > 0) {
                $healthScore = (($totalCheckedFields - $mojibakeFieldsDetected) / $totalCheckedFields) * 100;
                $report['mojibake_health_score'] = round($healthScore, 1);
            }

        } catch (\Exception $e) {
            // Main block fail-safe
        }

        return $report;
    }
}
