<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Core\AuditLogger;
use Illuminate\Support\Facades\DB;
use PDO;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;

/**
 * ChunkReadFilter — limits PhpSpreadsheet memory to the current chunk only.
 */
class ChunkReadFilter implements IReadFilter
{
    private int $startRow;
    private int $endRow;

    public function __construct(int $startRow, int $chunkSize)
    {
        if (app()->runningInConsole()) { return; }
        $this->startRow = $startRow;
        $this->endRow   = $startRow + $chunkSize - 1;
    }

    public function readCell($columnAddress, $row, $worksheetName = ''): bool
    {
        return ($row === 1 || ($row >= $this->startRow && $row <= $this->endRow));
    }
}

class ImportController extends Controller
{
    protected \PDO $db;
    private string $storageDir;

    /** Tables always excluded regardless of user selection */
    private const EXCLUDED_TABLES = [
        'accesuser', 'sync_queue', 'sync_jobs', 'sync_logs',
        'migrations', 'sessions', 'sync_reports', 'cache',
        'failed_jobs', 'personal_access_tokens', 'audit_logs',
        'user_preferences', 'dashboard_widgets',
    ];

    public function __construct()
    {
        if (app()->runningInConsole()) { return; }
        $this->db = new \App\Core\LaravelDbAdapter();
        $this->storageDir = storage_path('temp/imports/');
    }

    // ─── Pages ────────────────────────────────────────────────────────────────

    /**
     * GET /dashboard/import
     * Render the Import Dashboard View.
     */
    public function index()
    {
        $tables = $this->getAvailableTables();
        return $this->render('admin/import/index', [
            'title'  => 'استيراد البيانات — لوحة رفع الملفات',
            'tables' => $tables,
        ]);
    }

    // ─── AJAX: Table Schema ───────────────────────────────────────────────────

    /**
     * GET /dashboard/import/tables
     * Returns JSON list of importable tables with column info.
     */
    public function tables()
    {
        $tables = $this->getAvailableTables();
        return $this->json(['success' => true, 'tables' => $tables]);
    }

    /**
     * GET /dashboard/import/schema?table=xxx
     * Returns JSON column list for a given table (for preview).
     */
    public function schema()
    {
        $table = trim(request()->get('table', ''));
        if (!$this->isTableAllowed($table)) {
            return $this->json(['success' => false, 'message' => 'جدول غير مسموح به.'], 403);
        }

        try {
            $columns = DB::select("DESCRIBE `{$table}`");
            $cols = array_map(fn($c) => [
                'field'   => $c->Field,
                'type'    => $c->Type,
                'null'    => $c->Null,
                'key'     => $c->Key,
                'default' => $c->Default,
            ], $columns);

            $count = DB::table($table)->count();

            return $this->json([
                'success'  => true,
                'table'    => $table,
                'columns'  => $cols,
                'row_count' => $count,
            ]);
        } catch (\Throwable $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ─── AJAX: Export ─────────────────────────────────────────────────────────

    /**
     * GET /dashboard/import/export?table=xxx
     * Streams a CSV export of the given MySQL table directly to browser.
     */
    public function export()
    {
        $table = trim(request()->get('table', ''));
        if (!$this->isTableAllowed($table)) {
            abort(403, 'جدول غير مسموح به.');
        }

        try {
            // Stream CSV — no memory accumulation
            $columns = DB::select("DESCRIBE `{$table}`");
            $headers = array_map(fn($c) => $c->Field, $columns);

            $filename = $table . '_' . date('Ymd_His') . '.csv';

            // Set streaming response headers
            header('Content-Type: text/csv; charset=UTF-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Cache-Control: no-cache, must-revalidate');
            header('Pragma: no-cache');
            header('Expires: 0');

            $handle = fopen('php://output', 'w');

            // UTF-8 BOM for Excel compatibility
            fputs($handle, "\xEF\xBB\xBF");

            // Header row
            fputcsv($handle, $headers);

            // Stream rows in batches to avoid OOM
            $batchSize = 500;
            $offset    = 0;
            do {
                $rows = DB::table($table)->offset($offset)->limit($batchSize)->get();
                foreach ($rows as $row) {
                    fputcsv($handle, (array) $row);
                }
                $offset += $batchSize;
            } while (count($rows) === $batchSize);

            fclose($handle);

            AuditLogger::log('EXPORT', $table, null, null, ['source' => 'import_dashboard_csv']);

            exit;
        } catch (\Throwable $e) {
            abort(500, 'فشل التصدير: ' . $e->getMessage());
        }
    }

    // ─── AJAX: Upload ─────────────────────────────────────────────────────────

    /**
     * POST /dashboard/import/upload
     * Saves uploaded CSV/Excel to temp storage, returns file_id + row count.
     */
    public function upload()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->json(['success' => false, 'message' => 'Invalid request method'], 400);
        }

        if (!isset($_FILES['import_file']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
            return $this->json(['success' => false, 'message' => 'يرجى اختيار ملف صالح للرفع.'], 400);
        }

        $tmpName = $_FILES['import_file']['tmp_name'];
        $ext = strtolower(pathinfo($_FILES['import_file']['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, ['csv', 'xlsx', 'xls'])) {
            return $this->json(['success' => false, 'message' => 'صيغة الملف غير مدعومة. يسمح بـ CSV و Excel فقط.'], 400);
        }

        if (!is_dir($this->storageDir)) {
            mkdir($this->storageDir, 0775, true);
        }

        $fileId   = uniqid('import_', true) . '.' . $ext;
        $destPath = $this->storageDir . $fileId;

        if (!move_uploaded_file($tmpName, $destPath)) {
            return $this->json(['success' => false, 'message' => 'فشل حفظ الملف المرفوع في الخادم.'], 500);
        }

        // Count rows for progress bar
        $totalRows = 0;
        try {
            if ($ext === 'csv') {
                $file = fopen($destPath, 'r');
                while (fgetcsv($file) !== false) { $totalRows++; }
                fclose($file);
            } else {
                $reader = IOFactory::createReaderForFile($destPath);
                $reader->setReadDataOnly(true);
                $info = $reader->listWorksheetInfo($destPath);
                $totalRows = $info[0]['totalRows'] ?? 0;
            }
        } catch (\Exception $e) {
            error_log('[ImportController::upload] Row counting failed: ' . $e->getMessage());
        }

        $dataRowsCount = max(0, $totalRows - 1);

        // Read file headers for preview
        $fileHeaders = $this->readFileHeaders($destPath, $ext);

        return $this->json([
            'success'      => true,
            'file_id'      => $fileId,
            'filename'     => $_FILES['import_file']['name'],
            'total_rows'   => $dataRowsCount,
            'file_headers' => $fileHeaders,
        ]);
    }

    // ─── AJAX: Process Chunk ─────────────────────────────────────────────────

    /**
     * POST /dashboard/import/process
     * Processes one chunk of rows from an uploaded file into a target table.
     * Fully generic — works for any allowed MySQL table.
     */
    public function process()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->json(['success' => false, 'message' => 'Invalid request method'], 400);
        }

        $fileId = request()->all()['file_id'] ?? '';
        $table  = trim(request()->all()['table'] ?? '');
        $mode   = request()->all()['mode'] ?? 'insert'; // insert | upsert | skip
        $offset = (int)(request()->all()['offset'] ?? 0);
        $limit  = (int)(request()->all()['limit'] ?? 200);

        // Legacy support for old 'type' param
        $type = request()->all()['type'] ?? '';
        if ($type === 'candidats') {
            return $this->processLegacyCandidats($fileId, $offset, $limit);
        }
        if ($type === 'specialites') {
            return $this->processLegacySpecialites($fileId, $offset, $limit);
        }

        $filePath = $this->storageDir . $fileId;
        if (empty($fileId) || !file_exists($filePath)) {
            return $this->json(['success' => false, 'message' => 'الملف غير موجود أو منتهي الصلاحية.'], 404);
        }

        if (!$this->isTableAllowed($table)) {
            return $this->json(['success' => false, 'message' => 'الجدول المطلوب غير مسموح باستيراده.'], 403);
        }

        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        try {
            $rows = ($ext === 'csv')
                ? $this->readCsvChunk($filePath, $offset, $limit)
                : $this->readExcelChunk($filePath, $offset, $limit);

            if (empty($rows)) {
                return $this->json(['success' => true, 'processed_count' => 0, 'error_count' => 0, 'errors' => []]);
            }

            $result = $this->importGeneric($table, $rows, $mode, $offset);

            return $this->json(array_merge(['success' => true], $result));

        } catch (\Exception $e) {
            error_log('[ImportController::process] Error: ' . $e->getMessage());
            return $this->json(['success' => false, 'message' => 'حدث خطأ أثناء معالجة البيانات: ' . $e->getMessage()], 500);
        }
    }

    // ─── AJAX: Cleanup ────────────────────────────────────────────────────────

    /**
     * POST /dashboard/import/cleanup
     */
    public function cleanup()
    {
        $fileId   = request()->all()['file_id'] ?? '';
        $filePath = $this->storageDir . $fileId;
        if (!empty($fileId) && file_exists($filePath) && strpos($fileId, '..') === false) {
            unlink($filePath);
            return $this->json(['success' => true]);
        }
        return $this->json(['success' => false, 'message' => 'Invalid file ID']);
    }

    // ─── Core Generic Import ─────────────────────────────────────────────────

    /**
     * Generic row-by-row import into any allowed table.
     * Modes: 'insert' (skip on duplicate PK), 'upsert' (INSERT … ON DUPLICATE KEY UPDATE), 'replace' (REPLACE INTO)
     */
    private function importGeneric(string $table, array $rows, string $mode, int $startOffset): array
    {
        $processedCount = 0;
        $errorCount     = 0;
        $errors         = [];

        // Discover table columns once
        $tableColumns = $this->getTableColumns($table);
        if (empty($tableColumns)) {
            return ['processed_count' => 0, 'error_count' => count($rows), 'errors' => ['لا يمكن قراءة أعمدة الجدول.']];
        }

        foreach ($rows as $index => $row) {
            $lineNum = $startOffset + $index + 2;
            try {
                // Filter row keys to only known DB columns (case-insensitive matching)
                $data = $this->mapRowToColumns($row, $tableColumns);

                if (empty($data)) {
                    $errors[] = "السطر {$lineNum}: لم يتطابق أي عمود مع أعمدة الجدول.";
                    $errorCount++;
                    continue;
                }

                // Convert empty strings to null for nullable columns
                foreach ($data as $k => $v) {
                    if ($v === '' || $v === 'NULL' || $v === 'null') {
                        $data[$k] = null;
                    }
                }

                $cols        = array_keys($data);
                $placeholders = implode(', ', array_fill(0, count($cols), '?'));
                $colList     = implode(', ', array_map(fn($c) => "`{$c}`", $cols));
                $values      = array_values($data);

                if ($mode === 'replace') {
                    $sql = "REPLACE INTO `{$table}` ({$colList}) VALUES ({$placeholders})";
                } elseif ($mode === 'upsert') {
                    $updateParts = implode(', ', array_map(fn($c) => "`{$c}` = VALUES(`{$c}`)", $cols));
                    $sql = "INSERT INTO `{$table}` ({$colList}) VALUES ({$placeholders}) ON DUPLICATE KEY UPDATE {$updateParts}";
                } else {
                    // insert — ignore duplicates silently
                    $sql = "INSERT IGNORE INTO `{$table}` ({$colList}) VALUES ({$placeholders})";
                }

                $stmt = DB::statement($sql, $values);
                $processedCount++;

            } catch (\Throwable $e) {
                $errorCount++;
                $errors[] = "السطر {$lineNum}: " . $e->getMessage();
            }
        }

        return [
            'processed_count' => $processedCount,
            'error_count'     => $errorCount,
            'errors'          => $errors,
        ];
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    /**
     * Returns all MySQL tables excluding system/excluded/memo tables.
     */
    private function getAvailableTables(): array
    {
        try {
            $dbName = config('database.connections.mysql.database');
            $rows   = DB::select("SELECT TABLE_NAME, TABLE_ROWS FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_TYPE = 'BASE TABLE' ORDER BY TABLE_NAME", [$dbName]);

            $tables = [];
            foreach ($rows as $row) {
                $name = $row->TABLE_NAME;

                // Exclude system tables
                if (in_array($name, self::EXCLUDED_TABLES)) continue;
                // Exclude memo/image tables
                if (str_starts_with(strtolower($name), 'memo')) continue;

                $tables[] = [
                    'name'      => $name,
                    'row_count' => (int)($row->TABLE_ROWS ?? 0),
                ];
            }
            return $tables;
        } catch (\Throwable $e) {
            error_log('[ImportController::getAvailableTables] ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Validate that a table name is allowed for import/export.
     */
    private function isTableAllowed(string $table): bool
    {
        if (empty($table) || !preg_match('/^[a-zA-Z0-9_]+$/', $table)) return false;
        if (in_array($table, self::EXCLUDED_TABLES)) return false;
        if (str_starts_with(strtolower($table), 'memo')) return false;
        // Verify table exists
        try {
            $dbName = config('database.connections.mysql.database');
            $exists = DB::select("SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? LIMIT 1", [$dbName, $table]);
            return !empty($exists);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Return list of column names for a table.
     */
    private function getTableColumns(string $table): array
    {
        try {
            $cols = DB::select("DESCRIBE `{$table}`");
            return array_map(fn($c) => $c->Field, $cols);
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Map a CSV/Excel row (keyed by header) to actual DB columns (case-insensitive).
     */
    private function mapRowToColumns(array $row, array $tableColumns): array
    {
        $columnMap = [];
        foreach ($tableColumns as $col) {
            $columnMap[strtolower($col)] = $col;
        }

        $result = [];
        foreach ($row as $key => $value) {
            $cleanKey = strtolower(trim($key));
            if (isset($columnMap[$cleanKey])) {
                $result[$columnMap[$cleanKey]] = $value;
            }
        }
        return $result;
    }

    /**
     * Read first row of file as headers (for column preview).
     */
    private function readFileHeaders(string $filePath, string $ext): array
    {
        try {
            if ($ext === 'csv') {
                $fh = fopen($filePath, 'r');
                $headers = fgetcsv($fh) ?: [];
                fclose($fh);
                return array_map('trim', $headers);
            } else {
                $reader = IOFactory::createReaderForFile($filePath);
                $reader->setReadDataOnly(true);
                $filter = new ChunkReadFilter(1, 1);
                $reader->setReadFilter($filter);
                $spreadsheet = $reader->load($filePath);
                $row = $spreadsheet->getActiveSheet()->toArray(null, true, true, false)[0] ?? [];
                $spreadsheet->disconnectWorksheets();
                unset($spreadsheet);
                return array_map('trim', array_filter($row));
            }
        } catch (\Throwable $e) {
            return [];
        }
    }

    // ─── Chunk Readers ───────────────────────────────────────────────────────

    private function readCsvChunk(string $filePath, int $offset, int $limit): array
    {
        $file    = fopen($filePath, 'r');
        $headers = fgetcsv($file);
        if (!$headers) { fclose($file); return []; }

        $headers    = array_map('trim', $headers);
        $currentRow = 0;
        $data       = [];

        while (($row = fgetcsv($file)) !== false) {
            if ($currentRow >= $offset) {
                $mapped = [];
                foreach ($headers as $i => $key) {
                    $mapped[$key] = $row[$i] ?? '';
                }
                $data[] = $mapped;
                if (count($data) >= $limit) break;
            }
            $currentRow++;
        }

        fclose($file);
        return $data;
    }

    private function readExcelChunk(string $filePath, int $offset, int $limit): array
    {
        $startRow = $offset + 2;

        $reader = IOFactory::createReaderForFile($filePath);
        $reader->setReadDataOnly(true);
        $reader->setReadFilter(new ChunkReadFilter($startRow, $limit));

        $spreadsheet = $reader->load($filePath);
        $sheet       = $spreadsheet->getActiveSheet();
        $sheetData   = $sheet->toArray(null, true, true, true);

        $headerRow = $sheetData[1] ?? [];
        $headers   = array_filter(array_map('trim', $headerRow));

        if (empty($headers)) { return []; }

        $data = [];
        for ($rowNum = $startRow; $rowNum < ($startRow + $limit); $rowNum++) {
            if (!isset($sheetData[$rowNum])) continue;
            $rowVals = array_filter($sheetData[$rowNum]);
            if (empty($rowVals)) continue;

            $mapped = [];
            foreach ($headers as $colLetter => $key) {
                $mapped[$key] = $sheetData[$rowNum][$colLetter] ?? '';
            }
            $data[] = $mapped;
        }

        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet, $sheet, $sheetData);
        gc_collect_cycles();

        return $data;
    }

    // ─── Legacy Handlers (backward compat) ───────────────────────────────────

    private function processLegacyCandidats(string $fileId, int $offset, int $limit): mixed
    {
        $filePath = $this->storageDir . $fileId;
        if (!file_exists($filePath)) {
            return $this->json(['success' => false, 'message' => 'الملف غير موجود.'], 404);
        }
        $ext  = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $rows = ($ext === 'csv') ? $this->readCsvChunk($filePath, $offset, $limit) : $this->readExcelChunk($filePath, $offset, $limit);

        if (empty($rows)) {
            return $this->json(['success' => true, 'processed_count' => 0, 'error_count' => 0, 'errors' => []]);
        }

        $processedCount = $errorCount = 0;
        $errors = [];
        foreach ($rows as $i => $row) {
            $res = $this->importSingleCandidat($row, $offset + $i + 2);
            $res['success'] ? $processedCount++ : ($errorCount++ && $errors[] = $res['message']);
        }
        return $this->json(['success' => true, 'processed_count' => $processedCount, 'error_count' => $errorCount, 'errors' => $errors]);
    }

    private function processLegacySpecialites(string $fileId, int $offset, int $limit): mixed
    {
        $filePath = $this->storageDir . $fileId;
        if (!file_exists($filePath)) {
            return $this->json(['success' => false, 'message' => 'الملف غير موجود.'], 404);
        }
        $ext  = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $rows = ($ext === 'csv') ? $this->readCsvChunk($filePath, $offset, $limit) : $this->readExcelChunk($filePath, $offset, $limit);

        if (empty($rows)) {
            return $this->json(['success' => true, 'processed_count' => 0, 'error_count' => 0, 'errors' => []]);
        }

        $processedCount = $errorCount = 0;
        $errors = [];
        foreach ($rows as $i => $row) {
            $res = $this->importSingleSpecialite($row, $offset + $i + 2);
            $res['success'] ? $processedCount++ : ($errorCount++ && $errors[] = $res['message']);
        }
        return $this->json(['success' => true, 'processed_count' => $processedCount, 'error_count' => $errorCount, 'errors' => $errors]);
    }

    /**
     * Validate and insert a single Candidate row.
     */
    private function importSingleCandidat(array $row, int $lineNum): array
    {
        $nin      = $this->getRowValue($row, ['Nin', 'nin', 'رقم التعريف الوطني']);
        $nomAr    = $this->getRowValue($row, ['Nom', 'nom_ar', 'اللقب بالعربية', 'اللقب']);
        $prenomAr = $this->getRowValue($row, ['Prenom', 'prenom_ar', 'الاسم بالعربية', 'الاسم']);
        $nomFr    = $this->getRowValue($row, ['NomFr', 'nom_fr', 'اللقب بالفرنسية']);
        $prenomFr = $this->getRowValue($row, ['PrenomFr', 'prenom_fr', 'الاسم بالفرنسية']);
        $dateNais = $this->getRowValue($row, ['DateNais', 'date_naissance', 'تاريخ الميلاد']);
        $sexe     = $this->getRowValue($row, ['Civ', 'sexe', 'الجنس']);
        $tel      = $this->getRowValue($row, ['Tel', 'telephone', 'رقم الهاتف']);
        $offreId  = (int)$this->getRowValue($row, ['IDOffre', 'offre_id', 'رمز العرض']);

        if (empty($nin) || empty($nomAr) || empty($prenomAr) || $offreId <= 0) {
            return ['success' => false, 'message' => "السطر {$lineNum}: الحقول الإلزامية غير متوفرة."];
        }

        try {
            $stmt = $this->db->prepare("SELECT IDOffre FROM offre WHERE IDOffre = ? LIMIT 1");
            $stmt->execute([$offreId]);
            if (!$stmt->fetchColumn()) {
                return ['success' => false, 'message' => "السطر {$lineNum}: رمز عرض التكوين ({$offreId}) غير موجود."];
            }

            $stmtCheck = $this->db->prepare("SELECT IDCandidat FROM candidat WHERE Nin = ? AND IDOffre = ? LIMIT 1");
            $stmtCheck->execute([$nin, $offreId]);
            if ($stmtCheck->fetchColumn()) {
                return ['success' => false, 'message' => "السطر {$lineNum}: المترشح ({$nin}) مسجل مسبقاً في هذا العرض."];
            }

            $civ = (strtolower($sexe) === 'f' || $sexe == 2 || $sexe === 'أنثى') ? 2 : 1;

            $this->db->beginTransaction();
            $this->db->prepare("INSERT INTO candidat (IDOffre,Nom,Prenom,NomFr,PrenomFr,DateNais,Civ,Tel,Nin,dateInscr,Validation,ValidationDfp) VALUES (?,?,?,?,?,?,?,?,?,?,0,0)")
                ->execute([$offreId, $nomAr, $prenomAr, $nomFr ?: '-', $prenomFr ?: '-', $dateNais ?: '2000-01-01', $civ, $tel ?: '-', $nin, date('Y-m-d')]);

            $candidateId = (int)$this->db->lastInsertId();
            $numIns      = 'WIN-' . date('Y') . '-' . str_pad($candidateId, 6, '0', STR_PAD_LEFT);
            $this->db->prepare("UPDATE candidat SET NumIns = ? WHERE IDCandidat = ?")->execute([$numIns, $candidateId]);
            $this->db->commit();

            AuditLogger::log('CREATE', 'candidat', $candidateId, null, ['source' => 'import', 'offre_id' => $offreId]);
            return ['success' => true];

        } catch (\Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            return ['success' => false, 'message' => "السطر {$lineNum}: " . $e->getMessage()];
        }
    }

    /**
     * Validate and upsert a single Specialty.
     */
    private function importSingleSpecialite(array $row, int $lineNum): array
    {
        $codeSpec = $this->getRowValue($row, ['CodeSpec', 'code', 'رمز التخصص']);
        $legacyId = (int)$this->getRowValue($row, ['IDSpecialite', 'id', 'المعرف']);
        $nomAr    = $this->getRowValue($row, ['Nom', 'libelle_ar', 'اسم التخصص بالعربية', 'الاسم']);
        $nomFr    = $this->getRowValue($row, ['NomFr', 'libelle_fr', 'اسم التخصص بالفرنسية']);
        $dureeMois = (int)$this->getRowValue($row, ['Durée en Mois', 'duree_mois', 'مدة التكوين بالأشهر']);
        $nbrSem   = (int)$this->getRowValue($row, ['NbrSem', 'duree_semestres', 'عدد السداسيات']);

        if (empty($codeSpec) || $legacyId <= 0 || empty($nomAr)) {
            return ['success' => false, 'message' => "السطر {$lineNum}: الحقول الإلزامية غير متوفرة."];
        }

        if ($nbrSem <= 0 && $dureeMois > 0) $nbrSem = max(1, (int)round($dureeMois / 6));
        if ($nbrSem <= 0) $nbrSem = 4;
        $nbrAnne = round($nbrSem / 2, 1);

        $brancheCode = strtoupper(substr($codeSpec, 0, 3));
        if (strpos($codeSpec, '/') !== false) {
            $parts = explode('/', $codeSpec);
            $brancheCode = strtoupper(substr(trim($parts[1] ?? ''), 0, 3));
        }

        try {
            $stmtB = $this->db->prepare("SELECT IDBranche FROM branche WHERE Code = ? LIMIT 1");
            $stmtB->execute([$brancheCode]);
            $brancheId = $stmtB->fetchColumn();

            if (!$brancheId) {
                $this->db->beginTransaction();
                $maxB = (int)$this->db->query("SELECT COALESCE(MAX(IDBranche),0) FROM branche")->fetchColumn();
                $brancheId = max(1, $maxB + 1);
                $this->db->prepare("INSERT INTO branche (IDBranche,Code,Nom,NomFr,activee) VALUES (?,?,?,?,1)")->execute([$brancheId, $brancheCode, $brancheCode, $brancheCode]);
                $this->db->commit();
            }

            $this->db->prepare("INSERT INTO specialite (IDSpecialite,CodeSpec,IDBranche,Nom,NomFr,NbrSem,NbrAnne,activee) VALUES (?,?,?,?,?,?,?,1) ON DUPLICATE KEY UPDATE CodeSpec=VALUES(CodeSpec),IDBranche=VALUES(IDBranche),Nom=VALUES(Nom),NomFr=VALUES(NomFr),NbrSem=VALUES(NbrSem),NbrAnne=VALUES(NbrAnne)")
                ->execute([$legacyId, $codeSpec, $brancheId, $nomAr, $nomFr ?: $nomAr, $nbrSem, $nbrAnne]);

            return ['success' => true];

        } catch (\Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            return ['success' => false, 'message' => "السطر {$lineNum}: " . $e->getMessage()];
        }
    }

    // ─── Utility ──────────────────────────────────────────────────────────────

    private function getRowValue(array $row, array $keys): ?string
    {
        foreach ($row as $k => $v) {
            $cleanedKey = strtolower(trim($k));
            foreach ($keys as $expected) {
                if ($cleanedKey === strtolower(trim($expected))) {
                    return $v !== null ? trim((string)$v) : null;
                }
            }
        }
        return null;
    }
}
