<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Core\HFSQLConnection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

/**
 * FileSyncController
 * ══════════════════════════════════════════════════════════════════
 * أداة مزامنة الصور والملفات من HFSQL القديم → MySQL + Storage
 *
 * الجداول المدعومة (التي تحتوي على ملفات ثنائية في HFSQL):
 *   - candidat          → photo
 *   - encadrement       → photo
 *   - candidat_memo     → photo
 *   - encadremen_memo   → photo
 *   - etablissement_memo → photo
 *   - equipement_memo   → photo
 *   - logement_memo     → photo
 *   - vehicule_memo     → photo
 *   - dercrte_memo      → Pdf (ملفات PDF)
 *   - dpca              → Document, Document1 (وثائق)
 *
 * الطريقة: PDO_ODBC مع HFSQLConnection (نفس البنية الموجودة في المنصة)
 * ══════════════════════════════════════════════════════════════════
 */
class FileSyncController extends Controller
{
    /**
     * جدول التعريف: الجداول المدعومة مع أعمدة الملفات
     * key   = اسم الجدول في HFSQL
     * value = معلومات الجدول
     */
    private const TABLES_CONFIG = [
        'candidat' => [
            'label'       => 'صور المتربصين',
            'icon'        => 'fa-users',
            'primary_key' => 'IDCandidat',
            'columns'     => [
                'photo' => ['label' => 'الصورة الشخصية', 'type' => 'image', 'ext' => 'jpg'],
            ],
        ],
        'encadrement' => [
            'label'       => 'صور الموظفين والتأطير',
            'icon'        => 'fa-user-tie',
            'primary_key' => 'IDEncadrement',
            'columns'     => [
                'photo' => ['label' => 'الصورة الشخصية', 'type' => 'image', 'ext' => 'jpg'],
            ],
        ],
        'candidat_memo' => [
            'label'       => 'ملفات المتربصين (Memo)',
            'icon'        => 'fa-file-image',
            'primary_key' => 'IDCandidat_memo',
            'columns'     => [
                'photo' => ['label' => 'الصورة', 'type' => 'image', 'ext' => 'jpg'],
            ],
        ],
        'encadremen_memo' => [
            'label'       => 'ملفات التأطير (Memo)',
            'icon'        => 'fa-file-image',
            'primary_key' => 'IDEncadremen_memo',
            'columns'     => [
                'photo' => ['label' => 'الصورة', 'type' => 'image', 'ext' => 'jpg'],
            ],
        ],
        'etablissement_memo' => [
            'label'       => 'صور المؤسسات',
            'icon'        => 'fa-building',
            'primary_key' => 'IDEtablissement_memo',
            'columns'     => [
                'photo' => ['label' => 'صورة المؤسسة', 'type' => 'image', 'ext' => 'jpg'],
            ],
        ],
        'equipement_memo' => [
            'label'       => 'صور التجهيزات',
            'icon'        => 'fa-tools',
            'primary_key' => 'IDEquipement_memo',
            'columns'     => [
                'photo' => ['label' => 'صورة التجهيز', 'type' => 'image', 'ext' => 'jpg'],
            ],
        ],
        'logement_memo' => [
            'label'       => 'صور المساكن',
            'icon'        => 'fa-home',
            'primary_key' => 'IDLogement_memo',
            'columns'     => [
                'photo' => ['label' => 'صورة المسكن', 'type' => 'image', 'ext' => 'jpg'],
            ],
        ],
        'vehicule_memo' => [
            'label'       => 'صور العجلات',
            'icon'        => 'fa-car',
            'primary_key' => 'IDVehicule_memo',
            'columns'     => [
                'photo' => ['label' => 'صورة المركبة', 'type' => 'image', 'ext' => 'jpg'],
            ],
        ],
        'dercrte_memo' => [
            'label'       => 'ملفات المراسيم (PDF)',
            'icon'        => 'fa-file-pdf',
            'primary_key' => 'IDDercrte_memo',
            'columns'     => [
                'Pdf' => ['label' => 'ملف PDF', 'type' => 'pdf', 'ext' => 'pdf'],
            ],
        ],
        'dpca' => [
            'label'       => 'وثائق DPCA',
            'icon'        => 'fa-file-lines',
            'primary_key' => 'IDDpca',
            'columns'     => [
                'Document'  => ['label' => 'وثيقة 1', 'type' => 'pdf', 'ext' => 'pdf'],
                'Document1' => ['label' => 'وثيقة 2', 'type' => 'pdf', 'ext' => 'pdf'],
            ],
        ],
        'candidat_document' => [
            'label'       => 'وثائق المتربصين (كشف النقاط، شهادة الميلاد...)',
            'icon'        => 'fa-folder-open',
            'primary_key' => 'IDcandidat_document',
            'columns'     => [
                'relevedenotes_doc'  => ['label' => 'كشف النقاط', 'type' => 'pdf', 'ext' => 'pdf', 'url_col' => 'relevedenotes_url'],
                'enneexperience_doc' => ['label' => 'شهادة الخبرة', 'type' => 'pdf', 'ext' => 'pdf', 'url_col' => 'enneexperience_url'],
                'exdiplome_doc'      => ['label' => 'نسخة الشهادة', 'type' => 'pdf', 'ext' => 'pdf', 'url_col' => 'exdiplome_url'],
                'actn_doc'           => ['label' => 'شهادة الميلاد', 'type' => 'pdf', 'ext' => 'pdf', 'url_col' => 'actn_url'],
            ],
        ],
        'candidat_certifscol' => [
            'label'       => 'الشهادات المدرسية للمتربصين',
            'icon'        => 'fa-graduation-cap',
            'primary_key' => 'IDCandidat_certifscol',
            'columns'     => [
                'photo' => ['label' => 'الشهادة المدرسية', 'type' => 'pdf', 'ext' => 'pdf', 'url_col' => 'photo'],
            ],
        ],
        'candidat_contratapp' => [
            'label'       => 'عقود التمهين للمتربصين',
            'icon'        => 'fa-file-signature',
            'primary_key' => 'IDCandidat_contratapp',
            'columns'     => [
                'photo' => ['label' => 'عقد التمهين', 'type' => 'pdf', 'ext' => 'pdf', 'url_col' => 'photo'],
            ],
        ],
    ];

    /**
     * عرض لوحة التحكم — لا يُنفّذ أي COUNT queries هنا لضمان السرعة
     * الإحصائيات تُجلب عبر AJAX (statsApi) فقط عند فتح الصفحة
     */
    public function index()
    {
        $user = session('user') ?? [];
        $role = strtolower($user['role_code'] ?? 'user');
        if (empty($user) || !in_array($role, ['high_admin', 'admin'])) {
            return redirect('/');
        }

        // فقط هيكل الجداول بدون أي COUNT — سريع جداً
        $tableStats = [];
        foreach (self::TABLES_CONFIG as $table => $config) {
            $tableStats[$table] = [
                'label'   => $config['label'],
                'icon'    => $config['icon'],
                'columns' => $config['columns'],
            ];
        }

        $wilayas = DB::table('wilaya')->orderBy('IDWilayaa')->get();

        return view('admin.sync-files.index', compact('tableStats', 'wilayas'));
    }

    /**
     * AJAX: إحصائيات الجداول (تُستدعى فقط عند فتح الصفحة مباشرة)
     * لا تُستدعى في كل طلب للموقع
     */
    public function statsApi()
    {
        $user = session('user') ?? [];
        if (empty($user) || !in_array(strtolower($user['role_code'] ?? ''), ['high_admin', 'admin'])) {
            return response()->json([], 403);
        }

        $stats = [];
        foreach (self::TABLES_CONFIG as $table => $config) {
            try {
                $total = DB::table($table)->count();
                
                // حساب عدد السجلات التي بها ملفات (إما عمود الـ Blob أو عمود الـ URL)
                $withFiles = DB::table($table)
                    ->where(function($q) use ($config) {
                        foreach ($config['columns'] as $col => $colInfo) {
                            $urlCol = $colInfo['url_col'] ?? $col;
                            $q->orWhereNotNull($col)->where($col, '!=', '');
                            if ($urlCol !== $col) {
                                $q->orWhereNotNull($urlCol)->where($urlCol, '!=', '');
                            }
                        }
                    })
                    ->count();
            } catch (\Exception $e) {
                $total = $withFiles = 0;
            }
            $stats[$table] = [
                'total'      => $total,
                'with_files' => $withFiles,
                'without'    => max(0, $total - $withFiles),
            ];
        }

        return response()->json($stats);
    }

    /**
     * جلب المؤسسات حسب الولاية (AJAX — MySQL فقط)
     */
    public function getEtablissements(Request $request)
    {
        $user = session('user') ?? [];
        if (empty($user) || !in_array(strtolower($user['role_code'] ?? ''), ['high_admin', 'admin'])) {
            return response()->json([], 403);
        }

        $wilayaId = (int) $request->input('wilaya_id');
        if (!$wilayaId) return response()->json([]);

        return response()->json(
            DB::table('etablissement')
                ->where('IDDFEP', $wilayaId)
                ->select('IDetablissement', 'Nom', 'Abr')
                ->orderBy('Nom')
                ->get()
        );
    }

    /**
     * استخراج الملفات على دفعات من HFSQL (AJAX)
     */
    public function process(Request $request)
    {
        $user = session('user') ?? [];
        if (empty($user) || !in_array(strtolower($user['role_code'] ?? ''), ['high_admin', 'admin'])) {
            return response()->json(['error' => 'غير مصرح'], 403);
        }

        $allowedTables = array_keys(self::TABLES_CONFIG);

        $request->validate([
            'table'  => 'required|in:' . implode(',', $allowedTables),
            'column' => 'required|string',
            'offset' => 'required|integer|min:0',
            'limit'  => 'required|integer|min:1|max:30',
        ]);

        $table      = $request->input('table');
        $column     = $request->input('column');
        $offset     = (int) $request->input('offset');
        $limit      = (int) $request->input('limit');
        $etabId     = (int) $request->input('etab_id', 0);

        // التحقق من أن العمود مسموح به لهذا الجدول
        $config = self::TABLES_CONFIG[$table] ?? null;
        if (!$config || !isset($config['columns'][$column])) {
            return response()->json(['error' => "العمود '$column' غير مسموح لجدول '$table'"], 422);
        }

        $primaryKey = $config['primary_key'];
        $colConfig  = $config['columns'][$column];
        $ext        = $colConfig['ext'];

        $processedCount = 0;
        $extractedCount = 0;
        $extractedIds   = [];

        try {
            $pdo = HFSQLConnection::getInstance()->getConnection();
            $hfsqlTable = $this->resolveHfsqlTableName($table);

            $isMainTable = in_array($table, ['candidat', 'encadrement']);
            $fields = $isMainTable ? "{$primaryKey}, {$column}" : "*";

            // Get total count from HFSQL if offset is 0
            $totalHfsqlCount = 0;
            if ($offset === 0) {
                try {
                    $countSql = "SELECT COUNT(*) AS cnt FROM {$hfsqlTable}";
                    $countStmt = $pdo->query($countSql);
                    $countRow = $countStmt->fetch(\PDO::FETCH_ASSOC);
                    $totalHfsqlCount = (int)($countRow['cnt'] ?? 0);
                } catch (\Exception $e) {}
            }

            // بناء استعلام HFSQL مع دعم الفلترة
            if ($table === 'candidat' && $etabId > 0) {
                $sql  = "SELECT c.{$primaryKey}, c.{$column}
                         FROM candidat c
                         INNER JOIN offre o ON o.IDOffre = c.IDOffre
                         WHERE o.IDEts_Form = :etab_id AND c.{$column} IS NOT NULL
                         ORDER BY c.{$primaryKey}
                         LIMIT :limit OFFSET :offset";
                $stmt = $pdo->prepare($sql);
                $stmt->bindValue(':etab_id', $etabId, \PDO::PARAM_INT);
            } elseif ($table === 'encadrement' && $etabId > 0) {
                $sql  = "SELECT {$primaryKey}, {$column}
                         FROM encadrement
                         WHERE IDetablissement = :etab_id AND {$column} IS NOT NULL
                         ORDER BY {$primaryKey}
                         LIMIT :limit OFFSET :offset";
                $stmt = $pdo->prepare($sql);
                $stmt->bindValue(':etab_id', $etabId, \PDO::PARAM_INT);
            } elseif (in_array($table, ['candidat_document', 'candidat_certifscol', 'candidat_contratapp']) && $etabId > 0) {
                $sql  = "SELECT t.*
                         FROM {$hfsqlTable} t
                         INNER JOIN candidat c ON c.IDCandidat = t.IDCandidat
                         INNER JOIN offre o ON o.IDOffre = c.IDOffre
                         WHERE o.IDEts_Form = :etab_id AND t.{$column} IS NOT NULL
                         ORDER BY t.{$primaryKey}
                         LIMIT :limit OFFSET :offset";
                $stmt = $pdo->prepare($sql);
                $stmt->bindValue(':etab_id', $etabId, \PDO::PARAM_INT);
            } else {
                $sql  = "SELECT {$fields}
                         FROM {$hfsqlTable}
                         ORDER BY {$primaryKey}
                         LIMIT :limit OFFSET :offset";
                $stmt = $pdo->prepare($sql);
            }

            $stmt->bindValue(':limit',  $limit,  \PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
            $stmt->execute();

            $saveDir = "uploads/hfsql_sync/{$table}/{$column}";
            if (!Storage::disk('public')->exists($saveDir)) {
                Storage::disk('public')->makeDirectory($saveDir);
            }

            $mysqlColumns = \Illuminate\Support\Facades\Schema::getColumnListing($table);

            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $id         = $row[$primaryKey];
                $binaryData = $row[$column];
                $processedCount++;

                if (!empty($binaryData) && strlen($binaryData) > 100) {
                    // كشف الامتداد ديناميكياً
                    $detectedExt = $this->getExtensionFromBinary($binaryData, $ext);
                    $fileName    = "{$id}.{$detectedExt}";
                    $storagePath = "{$saveDir}/{$fileName}";
                    $publicPath  = "/uploads/hfsql_sync/{$table}/{$column}/{$fileName}";

                    Storage::disk('public')->put($storagePath, $binaryData);

                    // تحديث المسار في MySQL (نأخذ بعين الاعتبار عمود الـ URL المنفصل إن وُجد)
                    $updateData = [];
                    $urlCol = $colConfig['url_col'] ?? $column;
                    if ($urlCol !== $column) {
                        $updateData[$urlCol] = $publicPath;
                        $updateData[$column] = null; // تفريغ الـ blob لتخفيف قاعدة البيانات
                    } else {
                        $updateData[$column] = $publicPath;
                    }

                    // For relation/memo tables, copy other fields from HFSQL row
                    if (!$isMainTable) {
                        foreach ($row as $k => $v) {
                            if ($k !== $column && $k !== $primaryKey && in_array($k, $mysqlColumns)) {
                                // Convert encoding for text fields to prevent double-encoding
                                if (is_string($v)) {
                                    $v = trim(str_replace("\0", '', $v));
                                    $converted = @iconv('Windows-1256', 'UTF-8//IGNORE', $v);
                                    $v = ($converted !== false) ? $converted : $v;
                                }
                                $updateData[$k] = $v;
                            }
                        }
                    }

                    DB::table($table)->updateOrInsert([$primaryKey => $id], $updateData);

                    $extractedIds[] = $id;
                    $extractedCount++;
                }
            }

            return response()->json([
                'success'       => true,
                'message'       => "معالجة {$processedCount} سجل | استخراج {$extractedCount} ملف",
                'extracted_ids' => $extractedIds,
                'processed'     => $processedCount,
                'extracted'     => $extractedCount,
                'total_count'   => $totalHfsqlCount,
            ]);

        } catch (\Exception $e) {
            Log::error("HFSQL Sync [{$table}.{$column}]: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * بدء المزامنة في الخلفية (Background) — اتصال HFSQL واحد يعالج كل السجلات
     * يطلق عملية PHP منفصلة تكتب تقدمها في ملف JSON
     */
    public function startSync(Request $request)
    {
        $user = session('user') ?? [];
        if (empty($user) || !in_array(strtolower($user['role_code'] ?? ''), ['high_admin', 'admin'])) {
            return response()->json(['error' => 'غير مصرح'], 403);
        }

        $allowedTables = array_keys(self::TABLES_CONFIG);
        $request->validate([
            'table'  => 'required|in:' . implode(',', $allowedTables),
            'column' => 'required|string',
            'etab_id' => 'nullable|integer',
        ]);

        $table  = $request->input('table');
        $column = $request->input('column');
        $etabId = (int) $request->input('etab_id', 0);

        $config = self::TABLES_CONFIG[$table] ?? null;
        if (!$config || !isset($config['columns'][$column])) {
            return response()->json(['error' => "العمود '$column' غير مسموح لجدول '$table'"], 422);
        }

        $progressFile = storage_path("app/hfsql_sync_{$table}_{$column}.json");
        $pidFile      = storage_path("app/hfsql_sync_{$table}_{$column}_pid.txt");

        // إنهاء أي عملية سابقة
        if (file_exists($pidFile)) {
            $oldPid = (int) file_get_contents($pidFile);
            if ($oldPid > 0) {
                @shell_exec("taskkill /F /PID {$oldPid} 2>NUL");
            }
            @unlink($pidFile);
        }

        // إعادة تعيين ملف التقدم
        file_put_contents($progressFile, json_encode([
            'status'     => 'starting',
            'processed'  => 0,
            'extracted'  => 0,
            'total'      => 0,
            'started_at' => now()->toISOString(),
        ], JSON_UNESCAPED_UNICODE));

        $phpBin  = 'c:\\xampp\\php\\php.exe';
        $artisan = base_path('artisan');
        $etabOpt = $etabId > 0 ? " --etab-id={$etabId}" : '';

        // تشغيل الأمر في الخلفية عبر Windows Start
        $cmd = "\"$phpBin\" \"$artisan\" hfsql:sync {$table} {$column}{$etabOpt}";
        $fullCmd = "start /B {$cmd} > NUL 2>&1";

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['file', 'NUL', 'w'],
            2 => ['file', 'NUL', 'w'],
        ];

        $process = proc_open("cmd /C {$fullCmd}", $descriptors, $pipes);
        if (is_resource($process)) {
            fclose($pipes[0]);
            $status = proc_get_status($process);
            if ($status['pid'] > 0) {
                file_put_contents($pidFile, $status['pid']);
            }
            proc_close($process);
        }

        Log::info("HFSQL Sync Background Started: {$table}.{$column}");

        return response()->json([
            'started' => true,
            'message' => "بدء مزامنة {$table}.{$column} في الخلفية — الاتصال يستغرق ~65 ثانية",
        ]);
    }

    /**
     * قراءة حالة المزامنة الجارية في الخلفية
     */
    public function syncStatus(Request $request)
    {
        $user = session('user') ?? [];
        if (empty($user) || !in_array(strtolower($user['role_code'] ?? ''), ['high_admin', 'admin'])) {
            return response()->json([], 403);
        }

        $table  = $request->input('table', '');
        $column = $request->input('column', '');

        if (!$table || !$column) {
            return response()->json(['status' => 'idle']);
        }

        $progressFile = storage_path("app/hfsql_sync_{$table}_{$column}.json");

        if (!file_exists($progressFile)) {
            return response()->json(['status' => 'idle']);
        }

        $data = json_decode(file_get_contents($progressFile), true);
        return response()->json($data ?? ['status' => 'idle']);
    }


    /**
     * مطابقة اسم الجدول في MySQL مع اسمه الفعلي في HFSQL
     */
    private function resolveHfsqlTableName(string $table): string
    {
        $map = [
            'candidat_memo'      => 'memo_candidat',
            'encadremen_memo'    => 'memo_encadrement',
            'etablissement_memo' => 'memo_ets_form',
            'equipement_memo'    => 'memo_equipement',
            'logement_memo'      => 'memo_logement',
        ];
        return $map[strtolower($table)] ?? $table;
    }

    /**
     * كشف نوع الملف وامتداده من البايتات الأولى (Magic Bytes)
     */
    private function getExtensionFromBinary(string $data, string $default = 'jpg'): string
    {
        if (str_starts_with($data, '%PDF')) {
            return 'pdf';
        }
        if (str_starts_with($data, "\xff\xd8\xff")) {
            return 'jpg';
        }
        if (str_starts_with($data, "\x89PNG")) {
            return 'png';
        }
        if (str_starts_with($data, "GIF8")) {
            return 'gif';
        }
        if (str_starts_with($data, "PK\x03\x04")) {
            return 'docx';
        }
        return $default;
    }
}
