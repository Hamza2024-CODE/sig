<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Core\HFSQLConnection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;

/**
 * HfsqlExportController
 * ─────────────────────────────────────────────────────────────────
 * يصدّر جداول HFSQL إلى ملفات CSV أو يزامنها مع MySQL
 * التحسينات:
 *   - تخطي الجداول الفارغة تلقائياً
 *   - إنشاء الجدول في MySQL تلقائياً إذا لم يكن موجوداً
 *   - تحويل الترميز Windows-1256 → UTF-8
 *   - تصحيح التواريخ الفاسدة والقيم NULL
 *   - دعم أوضاع insert | upsert | replace
 * ─────────────────────────────────────────────────────────────────
 */
class HfsqlExportController extends Controller
{
    /**
     * HfsqlExportController Constructor.
     * Enforces the 'admin' role check on all actions.
     */
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (strtolower(session('user')['role_code'] ?? '') !== 'admin') {
                if ($request->expectsJson()) {
                    return response()->json(['success' => false, 'message' => 'غير مصرح لك بالوصول'], 403);
                }
                abort(403, 'غير مصرح لك بالوصول');
            }
            return $next($request);
        });
    }

    /** الجداول المستثناة دائماً */
    private const EXCLUDED = [
        'accesuser', 'memo_candidat', 'memo_apprenant',
        'memo_ets_form', 'memo_encadrement',
    ];

    /** بادئات أسماء الجداول المستثناة (memo/صور) */
    private const EXCLUDED_PREFIXES = ['memo', 'mmo'];

    /** حجم الدفعة الافتراضي للمزامنة */
    private const DEFAULT_BATCH = 500;

    /** أقصى حجم دفعة مسموح */
    private const MAX_BATCH = 2000;

    // ──────────────────────────────────────────────────────────────
    // دالة مساعدة: هل الجدول مستثنى؟
    // ──────────────────────────────────────────────────────────────
    private function isExcluded(string $table): bool
    {
        $t = strtolower($table);
        if (in_array($t, array_map('strtolower', self::EXCLUDED))) {
            return true;
        }
        foreach (self::EXCLUDED_PREFIXES as $p) {
            if (str_starts_with($t, $p)) {
                return true;
            }
        }
        return false;
    }

    // ──────────────────────────────────────────────────────────────
    // دالة مساعدة: التحقق من صحة اسم الجدول (whitelist pattern)
    // ──────────────────────────────────────────────────────────────
    private function validateTable(string $table): bool
    {
        return !empty($table)
            && !$this->isExcluded($table)
            && preg_match('/^[a-zA-Z0-9_]+$/', $table);
    }

    // ──────────────────────────────────────────────────────────────
    // دالة مساعدة: تنظيف وتحويل قيمة واحدة من Windows-1256 إلى UTF-8
    // ──────────────────────────────────────────────────────────────
    private function cleanValue(mixed $val): mixed
    {
        if (!is_string($val)) {
            return $val;
        }
        $val       = trim(str_replace("\0", '', $val));
        $converted = @iconv('Windows-1256', 'UTF-8//IGNORE', $val);
        return ($converted !== false) ? $converted : $val;
    }

    // ──────────────────────────────────────────────────────────────
    // دالة مساعدة: تنظيف اسم عمود
    // ──────────────────────────────────────────────────────────────
    private function cleanKey(string $key): string
    {
        return trim(str_replace("\0", '', $key));
    }

    // ──────────────────────────────────────────────────────────────
    // دالة مساعدة: قائمة الجداول (Hardcoded)
    // سبب: ODBC driver الخاص بـ HFSQL لا يدعم Metadata Queries
    // المصدر: مستخرجة يدوياً من HFSQL Control Center
    // ──────────────────────────────────────────────────────────────
    private function fetchTableList(\PDO $hfsql): array
    {
        return [
            'accesuser',
            'action_order',
            'action_order_detail',
            'active_annee',
            'active_annee_dpt',
            'active_periode',
            'activite',
            'activitehoraire',
            'administrateur',
            'agrement',
            'agrement_type',
            'apprenant',
            'apprenant_annee_specialite',
            'apprenant_structure',
            'aptitude',
            'assiduite',
            'assiduite_details',
            'attestation',
            'branche',
            'budget',
            'budget_line',
            'calendrier',
            'candidat',
            'candidat_document',
            'candidat_encadrement',
            'candidat_free',
            'candidat_free_log',
            'categorie',
            'charge',
            'charges_details',
            'classe',
            'classe_formation',
            'classification',
            'codediscipline',
            'commune',
            'concept_enseignement',
            'concept_enseignement_environnement',
            'concours',
            'conge',
            'contrat',
            'contrat_detail',
            'convention',
            'convention_entite_professionnelle',
            'corps',
            'courriel',
            'courriel_type',
            'cv',
            'dashboard_info',
            'datagrid_config',
            'decision',
            'decision_end_null',
            'decision_end_seminaire',
            'department',
            'diplome',
            'discipline',
            'discipline_lot_forfaite0',
            'domaine_info',
            'dossier',
            'echelon',
            'employee_document_organik',
            'employee_info',
            'encadrement',
            'encadrement_campaign',
            'encadrement_dossier',
            'encadrement_structure',
            'encadrement_type',
            'encadrement_unitedecompetences',
            'encadrement_unitedecompetences_etape',
            'encadrement_unitedecompetences_module',
            'encadrement_unitedecompetences_prerequisite',
            'encadrement_unitedecompetences_suivi',
            'encadrement_grade',
            'encadrement_penalty_project',
            'encadrement_semestre',
            'engagement',
            'enregistrement',
            'etat',
            'evaluation_apprenant_v15',
            'evaluation_ref',
            'evaluation_semestre',
            'evaluation_semestre_module',
            'evaluation_semestre_results',
            'evaluation_semestre_suivi',
            'evaluation_type',
            'filiere',
            'filiereclasse',
            'financement',
            'financement_emploi_formateur',
            'formation',
            'formationdiplome',
            'fonctionnaire_ats_jug_gr1',
            'fonctionnaire_ats_jug_gr2',
            'formateur',
            'formateur_type',
            'formateur_dispense',
            'formateur_organisme',
            'formateur_periode_activite',
            'frais_expected',
            'fraisperiode',
            'frequence',
            'frequence_grp_regional_u_select',
            'genre',
            'grade',
            'grp_discipline',
            'historique',
            'historiqueapprenant',
            'indice',
            'jalon',
            'lieu',
            'liste_diploma',
            'liste_specialite',
            'localite',
            'log',
            'loge',
            'logement',
            'marque',
            'matiere',
            'memo_apprenant',
            'memo_candidat',
            'memo_encadrement',
            'memo_ets_form',
            'message',
            'module',
            'module_type',
            'motif',
            'nationalite',
            'niveau',
            'note',
            'notification',
            'offert',
            'offre',
            'offres',
            'offre_specialite',
            'operation',
            'operationdecision',
            'operationdecision_operations',
            'operationetal',
            'operationnature',
            'operationnature_operations',
            'operationoffre',
            'operations_etablissement',
            'operationtype',
            'order',
            'ordernature',
            'participant',
            'participantnature',
            'partie',
            'partie_tache',
            'pays',
            'periodeencour',
            'platform_settings',
            'portal_layouts',
            'portal_widgets',
            'portefeuille',
            'poste_budgetaire',
            'preinscrit',
            'preinscritbig',
            'preinscrit_archived',
            'privilege',
            'privilege_utilisateur',
            'procedure_disciplinaire',
            'profil',
            'programme',
            'propriete_location',
            'pvsemstriel',
            'qualification_dplm',
            'questionnaireperiode',
            'recommandations',
            'recommandationsgenerale',
            'region',
            'releve',
            'secteur',
            'secteurs',
            'secteur_mfc',
            'section',
            'sectionn',
            'section_pv',
            'section_semestre',
            'section_semestre_module',
            'section_semestre_results',
            'semestre',
            'semestre_formation',
            'service',
            'session',
            'sessions',
            'session_dlep',
            'session_mode_formation',
            'siege_annex',
            'sifamille',
            'situation',
            'situationadministrat',
            'situationadministratposts',
            'sousaction',
            'souscategorie',
            'sousdomaine_mfc',
            'sous_programme',
            'specialite',
            'specialites',
            'specialite_endroit',
            'specialite_modeformation',
            'specialite_module',
            'specialite_modulemodenseig',
            'specialite_moduletype',
            'specialite_module_objectifs',
            'specialite_module_semestre',
            'specialite_programmemode',
            'specialite_unitesdecompetences',
            'stageperfectionnement',
            'statusets',
            'students_menauth',
            'suivi',
            'sync_errors',
            'sync_jobs',
            'sync_logs',
            'sync_queue',
            'sync_reports',
            'sync_status',
            'syndicat',
            'tache',
            'takwin_settings',
            'tbudget',
            'titre',
            'trimestre',
            'typemodule',
            'type_interv',
            'unitemodulaire',
            'user_preferences',
            'utilisateur',
            'utilisateur_mode_formation',
            'validationdossier',
            'vehiculeat',
            'vehiculegenre',
            'vehiculesmarque',
            'vehiculestype',
            'vehicule_memo',
            'wilaya',
            'wilayas',
            'zone',
        ];
    }

    // ──────────────────────────────────────────────────────────────
    // ✅ جديد: إنشاء جدول MySQL تلقائياً من هيكل HFSQL
    // يُستدعى عندما يكون الجدول موجوداً في HFSQL لكن غير موجود في MySQL
    // ──────────────────────────────────────────────────────────────
    private function createMysqlTableFromHfsql(\PDO $hfsql, string $table): void
    {
        // جلب صف واحد لاستخراج الأعمدة
        $stmt = $hfsql->query("SELECT * FROM {$table} LIMIT 1");

        if (!$stmt) {
            throw new \RuntimeException("تعذّر الاستعلام عن {$table} في HFSQL");
        }

        $colCount = $stmt->columnCount();

        if ($colCount === 0) {
            throw new \RuntimeException(
                "لا يمكن استخراج هيكل {$table}: الجدول فارغ ولا توجد metadata في الـ driver"
            );
        }

        $columns = [];
        for ($i = 0; $i < $colCount; $i++) {
            $meta   = $stmt->getColumnMeta($i);
            $name   = $this->cleanKey($meta['name'] ?? "col_{$i}");
            $native = strtolower($meta['native_type'] ?? 'string');

            // تعيين أنواع MySQL بناءً على نوع HFSQL/ODBC
            $mysqlType = match (true) {
                in_array($native, ['long', 'integer', 'int', 'tiny', 'short']) => 'BIGINT',
                in_array($native, ['double', 'float', 'real', 'decimal'])      => 'DOUBLE',
                str_contains($native, 'date') && str_contains($native, 'time') => 'DATETIME',
                str_contains($native, 'date')                                  => 'DATE',
                str_contains($native, 'time')                                  => 'TIME',
                str_contains($native, 'blob') || str_contains($native, 'bin')  => 'LONGBLOB',
                default                                                        => 'TEXT',
            };

            $columns[] = "`{$name}` {$mysqlType} NULL";
        }

        if (empty($columns)) {
            throw new \RuntimeException("لم يتم استخراج أي عمود من {$table}");
        }

        $ddl = sprintf(
            "CREATE TABLE IF NOT EXISTS `%s` (\n  %s\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            $table,
            implode(",\n  ", $columns)
        );

        DB::statement($ddl);
    }

    // ──────────────────────────────────────────────────────────────
    // ✅ جديد: جلب metadata أعمدة MySQL مع إنشاء الجدول عند الحاجة
    // ──────────────────────────────────────────────────────────────
    private function getMysqlMetadata(\PDO $hfsql, string $table): array
    {
        $mysqlCols = DB::select("DESCRIBE `{$table}`");

        // الجدول غير موجود في MySQL → أنشئه
        if (empty($mysqlCols)) {
            $this->createMysqlTableFromHfsql($hfsql, $table);
            $mysqlCols = DB::select("DESCRIBE `{$table}`");
        }

        if (empty($mysqlCols)) {
            throw new \RuntimeException("فشل إنشاء الجدول {$table} في MySQL");
        }

        $metadata = [];
        foreach ($mysqlCols as $col) {
            $metadata[$col->Field] = [
                'nullable' => strtoupper($col->Null) === 'YES',
                'type'     => strtolower($col->Type),
                'default'  => $col->Default,
            ];
        }

        return $metadata;
    }

    // ──────────────────────────────────────────────────────────────
    // دالة مساعدة: تصحيح قيمة عمود واحدة قبل الإدخال في MySQL
    // ──────────────────────────────────────────────────────────────
    private function castForMysql(mixed $val, array $meta): mixed
    {
        $type = $meta['type'];
        $val  = $this->cleanValue($val);

        // تصحيح التواريخ الفاسدة / الفارغة
        if (
            str_contains($type, 'date') ||
            str_contains($type, 'time') ||
            str_contains($type, 'year')
        ) {
            $strVal = trim((string) ($val ?? ''));
            if (
                $val === null ||
                $strVal === '' ||
                in_array($strVal, [
                    '0000-00-00',
                    '0000-00-00 00:00:00',
                    '1970-01-01 00:00:00',
                    '1970-01-01',
                ], true)
            ) {
                return $meta['nullable'] ? null : '2000-01-01';
            }
        }

        // تصحيح NULL في الأعمدة NOT NULL
        if ($val === null && !$meta['nullable']) {
            return (
                str_contains($type, 'int')     ||
                str_contains($type, 'double')  ||
                str_contains($type, 'float')   ||
                str_contains($type, 'decimal')
            ) ? 0 : '';
        }

        return $val;
    }

    // ══════════════════════════════════════════════════════════════
    // ROUTES
    // ══════════════════════════════════════════════════════════════

    // ──────────────────────────────────────────────────────────────
    // GET /dashboard/hfsql-export
    // الصفحة الرئيسية
    // ──────────────────────────────────────────────────────────────
    public function index()
    {
        return view('admin.hfsql_export.index');
    }

    // ──────────────────────────────────────────────────────────────
    // GET /dashboard/hfsql-export/tables
    // JSON: قائمة الجداول المتاحة في HFSQL
    // ──────────────────────────────────────────────────────────────
    public function tables()
    {
        // Release session lock to prevent blocking other requests
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        try {
            request()->session()->save();
        } catch (\Throwable $e) {}

        set_time_limit(0);
        ini_set('memory_limit', '256M');

        try {
            $hfsql  = HFSQLConnection::getInstance()->getConnection();
            $rows   = $this->fetchTableList($hfsql);

            $tables = array_values(
                array_filter($rows, fn($t) => !$this->isExcluded($t))
            );

            return response()->json([
                'success' => true,
                'tables'  => $tables,
                'count'   => count($tables),
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // ──────────────────────────────────────────────────────────────
    // GET /dashboard/hfsql-export/count?table=xxx
    // إجمالي عدد الصفوف في جدول معين
    // ──────────────────────────────────────────────────────────────
    public function count()
    {
        // Release session lock to prevent blocking other requests
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        try {
            request()->session()->save();
        } catch (\Throwable $e) {}

        set_time_limit(0);
        $table = request('table', '');

        if (!$this->validateTable($table)) {
            return response()->json(['success' => false, 'message' => 'جدول غير صالح'], 400);
        }

        try {
            $cacheKey = "hfsql_count_{$table}";
            $total = \Illuminate\Support\Facades\Cache::remember($cacheKey, 1800, function () use ($table) {
                $hfsql = HFSQLConnection::getInstance()->getConnection();
                return (int) $hfsql->query("SELECT COUNT(*) FROM {$table}")->fetchColumn();
            });

            return response()->json([
                'success' => true,
                'table'   => $table,
                'total'   => $total,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ──────────────────────────────────────────────────────────────
    // POST /dashboard/hfsql-export/bulk-counts
    // JSON: جلب عدد الصفوف لمجموعة من الجداول دفعة واحدة مع كاش
    // ──────────────────────────────────────────────────────────────
    public function bulkCounts()
    {
        // Release session lock to prevent blocking other requests
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        try {
            request()->session()->save();
        } catch (\Throwable $e) {}

        set_time_limit(0);
        $tables = request('tables', []);

        if (!is_array($tables)) {
            return response()->json(['success' => false, 'message' => 'بيانات غير صالحة'], 400);
        }

        // Validate table names
        $validTables = [];
        foreach ($tables as $t) {
            if ($this->validateTable($t)) {
                $validTables[] = $t;
            }
        }

        if (empty($validTables)) {
            return response()->json(['success' => true, 'counts' => []]);
        }

        try {
            $counts = [];
            $hfsql = null;

            foreach ($validTables as $table) {
                $cacheKey = "hfsql_count_{$table}";
                $counts[$table] = \Illuminate\Support\Facades\Cache::remember($cacheKey, 1800, function () use (&$hfsql, $table) {
                    if ($hfsql === null) {
                        $hfsql = HFSQLConnection::getInstance()->getConnection();
                    }
                    return (int) $hfsql->query("SELECT COUNT(*) FROM {$table}")->fetchColumn();
                });
            }

            return response()->json([
                'success' => true,
                'counts'  => $counts,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // ──────────────────────────────────────────────────────────────
    // GET /dashboard/hfsql-export/stream?table=xxx&offset=0&limit=500
    // يُعيد جزءاً من بيانات الجدول بترميز UTF-8 صحيح (JSON)
    // ──────────────────────────────────────────────────────────────
    public function stream()
    {
        // Release session lock to prevent blocking other requests
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        try {
            request()->session()->save();
        } catch (\Throwable $e) {}

        set_time_limit(0);
        $table  = request('table', '');
        $offset = max(0, (int) request('offset', 0));
        $limit  = min(1000, max(1, (int) request('limit', 500)));

        if (!$this->validateTable($table)) {
            return response()->json([
                'success' => false,
                'message' => 'اسم الجدول غير صالح أو مستثنى',
            ], 400);
        }

        try {
            $hfsql = HFSQLConnection::getInstance()->getConnection();

            // عدد الصفوف الكلي (للطلب الأول فقط)
            $total = 0;
            if ($offset === 0) {
                try {
                    $total = (int) $hfsql->query("SELECT COUNT(*) FROM {$table}")->fetchColumn();
                } catch (\Throwable) {
                    $total = -1; // غير معروف
                }
            }

            // ✅ تخطي الجداول الفارغة فوراً
            if ($offset === 0 && $total === 0) {
                return response()->json([
                    'success'  => true,
                    'table'    => $table,
                    'offset'   => 0,
                    'limit'    => $limit,
                    'count'    => 0,
                    'total'    => 0,
                    'headers'  => [],
                    'rows'     => [],
                    'skipped'  => true,
                    'message'  => 'الجدول فارغ — تم التخطي',
                ]);
            }

            // جلب الصفوف
            $stmt = $hfsql->prepare("SELECT * FROM {$table} LIMIT :lim OFFSET :off");
            $stmt->bindValue(':lim', $limit, \PDO::PARAM_INT);
            $stmt->bindValue(':off', $offset, \PDO::PARAM_INT);
            $stmt->execute();

            $rows    = [];
            $headers = null;

            while ($raw = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                if ($headers === null) {
                    $headers = array_keys($raw);
                }
                $cleaned = [];
                foreach ($raw as $key => $val) {
                    $cleaned[$this->cleanKey($key)] = $this->cleanValue($val);
                }
                $rows[] = $cleaned;
            }

            return response()->json([
                'success' => true,
                'table'   => $table,
                'offset'  => $offset,
                'limit'   => $limit,
                'count'   => count($rows),
                'total'   => $total,
                'headers' => $headers ?? [],
                'rows'    => $rows,
            ]);

        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ──────────────────────────────────────────────────────────────
    // GET /dashboard/hfsql-export/download?table=xxx
    // تصدير CSV مباشر (streaming) مع BOM للتوافق مع Excel
    // ──────────────────────────────────────────────────────────────
    public function download()
    {
        // Release session lock to prevent blocking other requests
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        try {
            request()->session()->save();
        } catch (\Throwable $e) {}

        set_time_limit(0);
        ini_set('memory_limit', '256M');
        $table = request('table', '');

        if (!$this->validateTable($table)) {
            abort(400, 'اسم الجدول غير صالح أو غير آمن');
        }

        $filename = $table . '_' . date('Y-m-d') . '.csv';

        return Response::stream(function () use ($table) {
            $out = fopen('php://output', 'w');

            // UTF-8 BOM لتوافق Excel
            fputs($out, "\xEF\xBB\xBF");

            try {
                $hfsql         = HFSQLConnection::getInstance()->getConnection();
                $batchSize     = self::DEFAULT_BATCH;
                $offset        = 0;
                $headerWritten = false;
                $fetched       = 0;

                // ✅ تحقق من وجود بيانات أولاً
                $total = (int) $hfsql->query("SELECT COUNT(*) FROM {$table}")->fetchColumn();
                if ($total === 0) {
                    fputcsv($out, ['# الجدول فارغ — لا توجد بيانات للتصدير']);
                    fclose($out);
                    return;
                }

                do {
                    $stmt = $hfsql->prepare("SELECT * FROM {$table} LIMIT :lim OFFSET :off");
                    $stmt->bindValue(':lim', $batchSize, \PDO::PARAM_INT);
                    $stmt->bindValue(':off', $offset, \PDO::PARAM_INT);
                    $stmt->execute();
                    $batch = $stmt->fetchAll(\PDO::FETCH_ASSOC);

                    if (!$headerWritten && !empty($batch)) {
                        fputcsv($out, array_keys($batch[0]));
                        $headerWritten = true;
                    }

                    $fetched = 0;
                    foreach ($batch as $raw) {
                        $cleaned = [];
                        foreach ($raw as $key => $val) {
                            $cleaned[$this->cleanKey($key)] = $this->cleanValue($val) ?? '';
                        }
                        fputcsv($out, $cleaned);
                        $fetched++;
                    }

                    $offset += $batchSize;
                    ob_flush();
                    flush();

                } while ($fetched === $batchSize);

            } catch (\Throwable $e) {
                fputcsv($out, ['ERROR: ' . $e->getMessage()]);
            }

            fclose($out);

        }, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control'       => 'no-store, no-cache',
            'X-Accel-Buffering'   => 'no',
        ]);
    }

    // ──────────────────────────────────────────────────────────────
    // POST /dashboard/hfsql-export/sync-to-mysql
    // يجلب جدولاً كاملاً من HFSQL ويكتبه في MySQL
    // الأوضاع: insert | upsert | replace
    //
    // ✅ التحسينات:
    //   - تخطي الجدول إذا كان فارغاً في HFSQL
    //   - إنشاء الجدول في MySQL تلقائياً إذا لم يكن موجوداً
    //   - تصحيح التواريخ والقيم NULL
    // ──────────────────────────────────────────────────────────────
    public function syncToMysql()
    {
        // Release session lock to prevent blocking other requests
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        try {
            request()->session()->save();
        } catch (\Throwable $e) {}

        // Invalidate cache for this table to ensure fresh count next time
        try {
            $table  = request('table', '');
            \Illuminate\Support\Facades\Cache::forget("hfsql_count_{$table}");
        } catch (\Throwable $e) {}

        set_time_limit(0);
        ini_set('memory_limit', '256M');

        $table  = request('table', '');
        $mode   = request('mode', 'upsert'); // insert | upsert | replace
        $offset = max(0, (int) request('offset', 0));
        $limit  = min(self::MAX_BATCH, max(1, (int) request('limit', self::DEFAULT_BATCH)));

        if (!$this->validateTable($table)) {
            return response()->json(['success' => false, 'message' => 'جدول غير صالح'], 400);
        }

        if (!in_array($mode, ['insert', 'upsert', 'replace'], true)) {
            return response()->json(['success' => false, 'message' => 'وضع غير مدعوم'], 400);
        }

        try {
            $hfsql = HFSQLConnection::getInstance()->getConnection();

            // ✅ (1) تحقق من عدد الصفوف في HFSQL أولاً (في الدفعة الأولى فقط لتجنب تكرار الاستعلام البطئ)
            $total = 0;
            if ($offset === 0) {
                try {
                    $total = (int) $hfsql->query("SELECT COUNT(*) FROM {$table}")->fetchColumn();
                } catch (\Throwable $e) {
                    return response()->json([
                        'success' => false,
                        'message' => "تعذّر الاستعلام عن {$table} في HFSQL: " . $e->getMessage(),
                    ], 500);
                }
            } else {
                $total = (int) request('total', 0);
            }

            // ✅ (2) تخطي الجداول الفارغة
            if ($total === 0) {
                return response()->json([
                    'success'         => true,
                    'processed_count' => 0,
                    'error_count'     => 0,
                    'total'           => 0,
                    'skipped'         => true,
                    'message'         => "الجدول {$table} فارغ في HFSQL — تم التخطي",
                ]);
            }

            // ✅ (3) جلب metadata من MySQL (مع إنشاء الجدول تلقائياً إذا لم يكن موجوداً)
            $metadata = $this->getMysqlMetadata($hfsql, $table);

            // (4) جلب الصفوف من HFSQL
            $stmt = $hfsql->prepare("SELECT * FROM {$table} LIMIT :lim OFFSET :off");
            $stmt->bindValue(':lim', $limit, \PDO::PARAM_INT);
            $stmt->bindValue(':off', $offset, \PDO::PARAM_INT);
            $stmt->execute();

            $batch = [];
            while ($raw = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $row = [];
                foreach ($raw as $key => $val) {
                    $cleanKey = $this->cleanKey($key);

                    // تجاهل الأعمدة غير الموجودة في MySQL
                    if (!isset($metadata[$cleanKey])) {
                        continue;
                    }

                    $row[$cleanKey] = $this->castForMysql($val, $metadata[$cleanKey]);
                }

                if (!empty($row)) {
                    $batch[] = $row;
                }
            }

            if (empty($batch)) {
                return response()->json([
                    'success'         => true,
                    'processed_count' => 0,
                    'error_count'     => 0,
                    'total'           => $total,
                    'message'         => 'لا توجد صفوف في هذا النطاق',
                ]);
            }

            // (5) كتابة إلى MySQL
            DB::statement("SET FOREIGN_KEY_CHECKS = 0");
            DB::statement("SET SESSION sql_mode = ''");

            $cols    = array_keys($batch[0]);
            $colsStr = implode(', ', array_map(fn($c) => "`{$c}`", $cols));
            $rowPh   = '(' . implode(', ', array_fill(0, count($cols), '?')) . ')';
            $allPh   = implode(', ', array_fill(0, count($batch), $rowPh));

            $sql = match ($mode) {
                'replace' => "REPLACE INTO `{$table}` ({$colsStr}) VALUES {$allPh}",
                'upsert'  => "INSERT INTO `{$table}` ({$colsStr}) VALUES {$allPh}"
                             . " ON DUPLICATE KEY UPDATE "
                             . implode(', ', array_map(fn($c) => "`{$c}` = VALUES(`{$c}`)", $cols)),
                default   => "INSERT IGNORE INTO `{$table}` ({$colsStr}) VALUES {$allPh}",
            };

            $flat = [];
            foreach ($batch as $r) {
                foreach (array_values($r) as $v) {
                    $flat[] = $v;
                }
            }

            DB::getPdo()->prepare($sql)->execute($flat);
            DB::statement("SET FOREIGN_KEY_CHECKS = 1");

            return response()->json([
                'success'         => true,
                'processed_count' => count($batch),
                'error_count'     => 0,
                'total'           => $total,
                'offset'          => $offset,
                'limit'           => $limit,
            ]);

        } catch (\Throwable $e) {
            // إعادة تفعيل foreign key checks حتى عند الخطأ
            try { DB::statement("SET FOREIGN_KEY_CHECKS = 1"); } catch (\Throwable) {}

            return response()->json([
                'success'         => false,
                'processed_count' => 0,
                'error_count'     => 1,
                'message'         => $e->getMessage(),
            ], 500);
        }
    }
}