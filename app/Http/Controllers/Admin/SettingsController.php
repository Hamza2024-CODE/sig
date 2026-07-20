<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Helpers\TakwinHelper;
use App\Core\AuditLogger;
use App\Services\ReferenceCache;
use Illuminate\Support\Facades\DB;

/**
 * SettingsController — صفحة الإعدادات الموحدة للمنصة
 *
 * تجمع كل الإعدادات في مكان واحد:
 * - General: معلومات المؤسسة والمنصة
 * - API: Takwin API
 * - Database: إعدادات قواعد البيانات
 * - Security: الأدوار والصلاحيات (redirect)
 * - Certificates: إعدادات الشهادات
 * - Performance: الكاش والأداء
 */
class SettingsController extends Controller
{
    public function __construct()
    {
        if (app()->runningInConsole()) { return; }
        $this->middleware(function ($request, $next) {
            $roleCode = strtolower(session('user')['role_code'] ?? '');
            // Allow full admin + high-level supervisors (ministerial level) + central directors
            if (!in_array($roleCode, ['admin', 'high_admin', 'central'])) {
                session(['flash_error' => 'هذه الصفحة للمديرين فقط / Accès réservé aux administrateurs.']);
                return redirect('/dashboard');
            }
            return $next($request);
        });
    }

    /**
     * صفحة الإعدادات الرئيسية
     */
    public function index()
    {
        $tab = request()->query('tab', 'general');

        // معلومات المنصة من .env وقاعدة البيانات
        $platformInfo = $this->getPlatformInfo();
        $dbStats      = $this->getDatabaseStats();
        $cacheStats   = $this->getCacheStats();
        $takwinSettings = TakwinHelper::getSettings();

        $portalPages = [];
        try {
            \App\Helpers\PortalCMSHelper::ensureTableExists();
            $portalPages = \App\Helpers\PortalCMSHelper::getPages();
        } catch (\Throwable $e) {}

        // إحصائيات الوثائق والملفات مع الفلاتر
        $docService = new \App\Services\DocumentSyncService();
        $selectedTable = request()->query('doc_table', 'candidat_document');
        $selectedWilaya = request()->query('wilaya_id') ? (int)request()->query('wilaya_id') : null;
        $selectedEtab = request()->query('etab_id') ? (int)request()->query('etab_id') : null;

        $docStats = $docService->checkSyncStatus($selectedTable, $selectedWilaya, $selectedEtab);
        $previewDocs = $docService->getPreviewDocuments($selectedTable, 10, $selectedWilaya, $selectedEtab);

        // قائمة الولايات والمؤسسات
        $wilayas = [];
        try {
            $wilayas = DB::table('wilaya')->select('IDWilayaa', 'Nom')->orderBy('Nom')->get()->map(function($w) {
                $newItem = new \stdClass();
                foreach ($w as $k => $v) {
                    $lk = strtolower($k);
                    if ($lk === 'idwilayaa') {
                        $newItem->IDWilayaa = $v;
                    } elseif ($lk === 'nom') {
                        $newItem->Nom = $v;
                    } else {
                        $newItem->$k = $v;
                    }
                }
                if (!property_exists($newItem, 'IDWilayaa')) $newItem->IDWilayaa = null;
                if (!property_exists($newItem, 'Nom')) $newItem->Nom = null;
                return $newItem;
            });
        } catch (\Throwable $e) {}

        $etablissements = [];
        try {
            $etablissements = DB::table('etablissement')->select('IDetablissement', 'IDEts_Form', 'IDDFEP', 'Nom')->orderBy('Nom')->get()->map(function($e) {
                $newItem = new \stdClass();
                foreach ($e as $k => $v) {
                    $lk = strtolower($k);
                    if ($lk === 'idetablissement') {
                        $newItem->IDetablissement = $v;
                    } elseif ($lk === 'idets_form') {
                        $newItem->IDEts_Form = $v;
                    } elseif ($lk === 'iddfep') {
                        $newItem->IDDFEP = $v;
                    } elseif ($lk === 'nom') {
                        $newItem->Nom = $v;
                    } else {
                        $newItem->$k = $v;
                    }
                }
                if (!property_exists($newItem, 'IDetablissement')) $newItem->IDetablissement = null;
                if (!property_exists($newItem, 'IDEts_Form')) $newItem->IDEts_Form = null;
                if (!property_exists($newItem, 'IDDFEP')) $newItem->IDDFEP = null;
                if (!property_exists($newItem, 'Nom')) $newItem->Nom = null;
                return $newItem;
            });
        } catch (\Throwable $e) {}

        // إعدادات الترخيص السيادي والأمان المتقدم
        $isActivationRequired = \App\Helpers\SovereignLicensingHelper::isActivationRequired();
        $isShieldActive = \App\Helpers\SovereignLicensingHelper::getSetting('content_protection_shield_active', '1') === '1';
        $isCaptchaActive = \App\Helpers\SovereignLicensingHelper::getSetting('login_captcha_active', '0') === '1';
        
        $isSsoEnabled = \App\Helpers\SovereignLicensingHelper::getSetting('sso_enabled', '0') === '1';
        $isPushEnabled = \App\Helpers\SovereignLicensingHelper::getSetting('push_notifications_enabled', '0') === '1';
        $isSseEnabled = \App\Helpers\SovereignLicensingHelper::getSetting('sse_realtime_kpis_enabled', '0') === '1';
        $isRateLimitingEnabled = \App\Helpers\SovereignLicensingHelper::getSetting('rate_limiting_enabled', '0') === '1';

        $masterKey = \App\Helpers\SovereignLicensingHelper::getMasterKey();
        $licenseKeys = [];
        try {
            $licenseKeys = DB::table('license_keys')
                ->leftJoin('utilisateur', 'license_keys.user_id', '=', 'utilisateur.IDUtilisateur')
                ->select('license_keys.*', 'utilisateur.NomUser as username', 'utilisateur.Nom as user_nom')
                ->orderBy('license_keys.created_at', 'desc')
                ->get();
            
            $etsIds = [];
            foreach ($licenseKeys as $lk) {
                if ($lk->ets_id) {
                    $etsIds[] = (int)$lk->ets_id;
                }
            }
            $etsIds = array_unique($etsIds);
            
            $etabMap = [];
            if (!empty($etsIds)) {
                try {
                    $etabs = DB::table('etablissement')
                        ->whereIn('IDetablissement', $etsIds)
                        ->orWhereIn('IDEts_Form', $etsIds)
                        ->select('IDetablissement', 'IDEts_Form', 'Nom as name')
                        ->get();
                    foreach ($etabs as $etab) {
                        $dbId = isset($etab->IDetablissement) ? $etab->IDetablissement : (isset($etab->idetablissement) ? $etab->idetablissement : null);
                        $dbForm = isset($etab->IDEts_Form) ? $etab->IDEts_Form : (isset($etab->idets_form) ? $etab->idets_form : null);
                        if ($dbId !== null) {
                            $etabMap[$dbId] = $etab->name;
                        }
                        if ($dbForm !== null) {
                            $etabMap[$dbForm] = $etab->name;
                        }
                    }
                } catch (\Throwable $e) {}
            }

            foreach ($licenseKeys as $lk) {
                if ($lk->ets_id) {
                    $lk->etablissement_name = $etabMap[$lk->ets_id] ?? 'مؤسسة #' . $lk->ets_id;
                } else {
                    $lk->etablissement_name = '—';
                }
            }
        } catch (\Throwable $e) {}

        // قائمة طلبات الإجازات والوثائق للموظفين
        $employeeLeaves = [];
        $employeeDocs = [];
        try {
            $employeeLeaves = DB::table('employee_leaves')
                ->leftJoin('encadrement', 'employee_leaves.employee_id', '=', 'encadrement.IDEncadrement')
                ->leftJoin('etablissement', 'encadrement.IDetablissement', '=', 'etablissement.IDetablissement')
                ->select(
                    'employee_leaves.*',
                    'encadrement.Nom as emp_nom',
                    'encadrement.Prenom as emp_prenom',
                    'etablissement.Nom as etab_nom'
                )
                ->orderBy('employee_leaves.created_at', 'desc')
                ->get();

            $employeeDocs = DB::table('employee_document_requests')
                ->leftJoin('encadrement', 'employee_document_requests.employee_id', '=', 'encadrement.IDEncadrement')
                ->leftJoin('etablissement', 'encadrement.IDetablissement', '=', 'etablissement.IDetablissement')
                ->select(
                    'employee_document_requests.*',
                    'encadrement.Nom as emp_nom',
                    'encadrement.Prenom as emp_prenom',
                    'etablissement.Nom as etab_nom'
                )
                ->orderBy('employee_document_requests.created_at', 'desc')
                ->get();
        } catch (\Throwable $e) {}

        // قائمة النسخ الاحتياطية
        $backups = [];
        try {
            $backupService = new \App\Services\BackupService();
            $backups = $backupService->getBackupsList();
        } catch (\Throwable $e) {}

        $ministry = null;
        $allSemesters = [];
        $currentSemesterId = 1;
        try {
            $ministry = DB::table('minister')->where('IDMinister', 1)->first();
        } catch (\Throwable $e) {}

        try {
            $allSemesters = DB::table('periodeencour')->get();
        } catch (\Throwable $e) {}

        try {
            $currentSemesterId = DB::table('takwin_settings')->value('active_semester_id') ?? 1;
        } catch (\Throwable $e) {}

        $apiClients = [];
        try {
            $apiClients = \App\Models\ApiClient::orderBy('id', 'DESC')->get();
        } catch (\Throwable $e) {}

        $userApiKey = 'sgfep_live_' . substr(hash('sha256', session('user')['username']), 0, 32);

        return $this->render('admin/settings/index', [
            'title'                  => 'إعدادات المنصة الشاملة / Paramètres Généraux',
            'active_tab'             => $tab,
            'platform_info'          => $platformInfo,
            'db_stats'               => $dbStats,
            'cache_stats'            => $cacheStats,
            'takwin_settings'        => $takwinSettings,
            'doc_stats'              => $docStats,
            'preview_docs'           => $previewDocs,
            'wilayas'                => $wilayas,
            'etablissements'         => $etablissements,
            'selected_table'         => $selectedTable,
            'selected_wilaya'        => $selectedWilaya,
            'selected_etab'          => $selectedEtab,
            'backups'                => $backups,
            'is_activation_required' => $isActivationRequired,
            'is_shield_active'       => $isShieldActive,
            'is_captcha_active'      => $isCaptchaActive,
            'is_sso_enabled'         => $isSsoEnabled,
            'is_push_enabled'        => $isPushEnabled,
            'is_sse_enabled'         => $isSseEnabled,
            'is_rate_limiting_enabled' => $isRateLimitingEnabled,
            'license_keys'           => $licenseKeys,
            'master_key'             => $masterKey,
            'employee_leaves'        => $employeeLeaves,
            'employee_docs'          => $employeeDocs,
            'ministry'               => $ministry,
            'allSemesters'           => $allSemesters,
            'currentSemesterId'      => $currentSemesterId,
            'api_clients'            => $apiClients,
            'user_api_key'           => $userApiKey,
            'portal_pages'           => $portalPages,
        ]);
    }

    /**
     * حفظ الإعدادات العامة
     */
    public function update()
    {
        if (!request()->isMethod('post')) {
            return $this->redirect('/dashboard/settings');
        }

        $csrfToken = request()->all()['csrf_token'] ?? '';
        if (empty($csrfToken) || $csrfToken !== csrf_token()) {
            session(['flash_error' => 'رمز CSRF غير صالح.']);
            return $this->redirect('/dashboard/settings');
        }

        $section = request()->all()['section'] ?? 'general';

        try {
            match ($section) {
                'cache'             => $this->handleCacheClear(),
                'takwin'            => $this->handleTakwinUpdate(),
                'diploma'           => $this->handleDiplomaUpdate(),
                'documents_sync'    => $this->handleDocumentsSync(),
                'documents_cleanup' => $this->handleDocumentsCleanup(),
                'documents_secure'  => $this->handleDocumentsSecure(),
                'backup_create'     => $this->handleBackupCreate(),
                'backup_delete'     => $this->handleBackupDelete(),
                'sovereign_toggle'   => $this->handleSovereignToggle(),
                'shield_toggle'      => $this->handleShieldToggle(),
                'captcha_toggle'     => $this->handleCaptchaToggle(),
                'hide_other_logins_toggle' => $this->handleHideOtherLoginsToggle(),
                'sso_toggle'         => $this->handleSsoToggle(),
                'push_toggle'        => $this->handlePushToggle(),
                'sse_toggle'         => $this->handleSseToggle(),
                'rate_limiting_toggle' => $this->handleRateLimitingToggle(),
                'sovereign_generate' => $this->handleSovereignGenerate(),
                'patrimoine_media_toggle'    => $this->handlePatrimoineMediaToggle(),
                'feature_print_toggle'       => $this->handleFeatureToggle('feature_print_actions_enabled', 'أزرار الطباعة والتنزيل'),
                'feature_stats_toggle'       => $this->handleFeatureToggle('feature_complex_stats_enabled', 'الإحصائيات المتقدمة'),
                'feature_photo_sort_toggle'  => $this->handleFeatureToggle('feature_photo_sorting_enabled', 'الفرز حسب وجود الصور'),
                'feature_sync_toggle'        => $this->handleFeatureToggle('feature_background_sync_enabled', 'المزامنة الخلفية مع HFSQL'),
                'feature_memos_toggle'       => $this->handleFeatureToggle('feature_large_memos_query_enabled', 'استعلامات المذكرات الضخمة'),
                'sovereign_delete'   => $this->handleSovereignDelete(),
                'sovereign_extend'   => $this->handleSovereignExtend(),
                'leave_action'       => $this->handleLeaveAction(),
                'doc_action'         => $this->handleDocAction(),
                'ministry_sync'     => $this->handleMinistrySyncUpdate(),
                'landing_settings'  => $this->handleLandingSettingsUpdate(),
                'portal_cms_update' => $this->handlePortalCMSUpdate(),
                'portal_cms_add'    => $this->handlePortalCMSAdd(),
                'portal_cms_delete' => $this->handlePortalCMSDelete(),
                'enrollment_permissions' => $this->handleEnrollmentPermissionsUpdate(),
                default             => session(['flash_error' => 'قسم غير معروف: ' . $section]),
            };
        } catch (\Exception $e) {
            session(['flash_error' => 'خطأ: ' . $e->getMessage()]);
        }

        $tab = request()->all()['redirect_tab'] ?? 'general';
        $params = ['tab' => $tab];
        if ($tab === 'documents') {
            $params['doc_table'] = request()->input('doc_table', 'candidat_document');
            if (request()->input('wilaya_id')) {
                $params['wilaya_id'] = request()->input('wilaya_id');
            }
            if (request()->input('etab_id')) {
                $params['etab_id'] = request()->input('etab_id');
            }
        }
        return $this->redirect('/dashboard/settings?' . http_build_query($params));
    }

    // ── Private Handlers ──────────────────────────────────────────────────

    private function handleDocumentsSync(): void
    {
        $docService = new \App\Services\DocumentSyncService();
        $limit = (int)request()->input('sync_limit', 500);
        if ($limit <= 0) $limit = 500;

        $table = request()->input('doc_table', 'candidat_document');
        $wilayaId = request()->input('wilaya_id') ? (int)request()->input('wilaya_id') : null;
        $etabId = request()->input('etab_id') ? (int)request()->input('etab_id') : null;

        $res = $docService->syncBlobsToFiles($table, $limit, $wilayaId, $etabId);
        if ($res['success']) {
            if ($res['synced'] > 0) {
                session(['flash_success' => "تمت مزامنة {$res['synced']} سجل بنجاح من قاعدة البيانات إلى خادم الملفات! / Synchronisation réussie."]);
            } else {
                session(['flash_success' => 'جميع الملفات مزامنة بالفعل! / Tous les fichiers sont déjà synchronisés.']);
            }
        } else {
            $errStr = !empty($res['errors']) ? implode(', ', array_slice($res['errors'], 0, 3)) : 'خطأ غير معروف';
            session(['flash_error' => 'حدث خطأ أثناء المزامنة: ' . $errStr]);
        }
    }

    private function handleDocumentsCleanup(): void
    {
        $docService = new \App\Services\DocumentSyncService();
        $res = $docService->cleanupOrphans();
        if ($res['success']) {
            session(['flash_success' => "تم تنظيف وتطهير {$res['deleted']} من الملفات اليتيمة والمكررة بنجاح!"]);
        } else {
            session(['flash_error' => 'فشل تنظيف الملفات: ' . ($res['error'] ?? '')]);
        }
    }

    private function handleDocumentsSecure(): void
    {
        $docService = new \App\Services\DocumentSyncService();
        $ok = $docService->secureStorageDirectory();
        if ($ok) {
            session(['flash_success' => 'تم إنشاء وتأمين ملف .htaccess بنجاح داخل مجلد التخزين لمنع أي ثغرات!']);
        } else {
            session(['flash_error' => 'فشل كتابة ملف الحماية. تأكد من صلاحيات المجلد.']);
        }
    }

    private function handleBackupCreate(): void
    {
        $backupService = new \App\Services\BackupService();
        $res = $backupService->createBackup();
        if ($res['success']) {
            session(['flash_success' => "تم إنشاء نسخة احتياطية جديدة بنجاح! ({$res['filename']})"]);
        } else {
            session(['flash_error' => 'فشل إنشاء النسخة الاحتياطية: ' . ($res['error'] ?? '')]);
        }
    }

    private function handleBackupDelete(): void
    {
        $filename = request()->input('filename');
        if (empty($filename)) {
            session(['flash_error' => 'اسم الملف غير صالح.']);
            return;
        }
        $backupService = new \App\Services\BackupService();
        $ok = $backupService->deleteBackup($filename);
        if ($ok) {
            session(['flash_success' => 'تم حذف ملف النسخة الاحتياطية بنجاح!']);
        } else {
            session(['flash_error' => 'فشل حذف الملف.']);
        }
    }

    public function downloadBackup(string $filename)
    {
        $filename = basename($filename);
        $path = storage_path('app/backups/' . $filename);
        if (!file_exists($path)) {
            abort(404, 'الملف غير موجود / Fichier introuvable');
        }

        // Stream download to support large files without memory limits
        return response()->streamDownload(function () use ($path) {
            $stream = fopen($path, 'r');
            if ($stream) {
                while (!feof($stream)) {
                    echo fread($stream, 1024 * 64); // read 64kb chunks
                    flush();
                }
                fclose($stream);
            }
        }, $filename, [
            'Content-Type' => 'application/zip',
            'Content-Length' => filesize($path),
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    private function handleCacheClear(): void
    {
        ReferenceCache::flushAll();
        \Illuminate\Support\Facades\Cache::flush();
        AuditLogger::logWarning('[SETTINGS] Admin cleared all application cache');
        session(['flash_success' => 'تم مسح الكاش بالكامل بنجاح! / Cache vidé avec succès.']);
    }

    private function handleTakwinUpdate(): void
    {
        $apiUrl       = trim(request()->all()['api_url'] ?? '');
        $apiToken     = trim(request()->all()['api_token'] ?? '');
        $syncEnabled  = isset(request()->all()['sync_enabled']) ? 1 : 0;

        if (empty($apiUrl)) {
            session(['flash_error' => 'يرجى إدخال رابط API صالح.']);
            return;
        }

        $old = TakwinHelper::getSettings();
        if (empty($apiToken) || str_contains($apiToken, '...') || $apiToken === '—') {
            $apiToken = $old['api_token'];
        }

        $ok = TakwinHelper::saveSettings($apiUrl, $apiToken, $syncEnabled);
        if ($ok) {
            AuditLogger::log('UPDATE', 'takwin_settings', $old['id'] ?? 1,
                ['api_url' => $old['api_url']], ['api_url' => $apiUrl]);
            session(['flash_success' => 'تم حفظ إعدادات Takwin API بنجاح!']);
        } else {
            session(['flash_error' => 'فشل حفظ إعدادات Takwin API.']);
        }
    }

    private function handleDiplomaUpdate(): void
    {
        $bgUrl        = trim(request()->all()['diploma_bg_url'] ?? '');
        $borderColor  = trim(request()->all()['diploma_border_color'] ?? '#1e3a8a');
        $watermarkUrl = trim(request()->all()['diploma_watermark_url'] ?? '');
        $primaryColor = trim(request()->all()['diploma_primary_color'] ?? '#1e3a8a');

        $ok = TakwinHelper::saveDiplomaSettings($bgUrl, $borderColor, $watermarkUrl, $primaryColor);
        if ($ok) {
            session(['flash_success' => 'تم حفظ إعدادات الشهادة بنجاح!']);
        } else {
            session(['flash_error' => 'فشل حفظ إعدادات الشهادة.']);
        }
    }

    // ── Helpers ────────────────────────────────────────────────────────────

    private function getPlatformInfo(): array
    {
        return [
            'app_name'     => config('app.name', 'منصة تسيير'),
            'app_env'      => config('app.env', 'production'),
            'app_debug'    => config('app.debug', false),
            'php_version'  => PHP_VERSION,
            'laravel_ver'  => app()->version(),
            'db_name'      => config('database.connections.mysql.database', '—'),
            'db_host'      => config('database.connections.mysql.host', '127.0.0.1'),
            'cache_driver' => config('cache.default', 'file'),
            'session_drv'  => config('session.driver', 'file'),
            'sentry_dsn'   => !empty(config('sentry.dsn')) ? 'مُفعَّل ✅' : 'معطَّل ❌ (وضع التطوير)',
        ];
    }

    private function getDatabaseStats(): array
    {
        $stats = [
            'total_tables'     => 0,
            'total_rows'       => 0,
            'db_size_mb'       => 0,
            'top_tables'       => [],
        ];

        try {
            $dbName = config('database.connections.mysql.database');

            // حجم قاعدة البيانات
            $sizeRow = DB::selectOne("
                SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb
                FROM information_schema.TABLES
                WHERE table_schema = ?
            ", [$dbName]);
            $stats['db_size_mb'] = $sizeRow->size_mb ?? 0;

            // أكبر 10 جداول
            $tables = DB::select("
                SELECT table_name AS name,
                       table_rows AS rows,
                       ROUND((data_length + index_length) / 1024 / 1024, 2) AS size_mb
                FROM information_schema.TABLES
                WHERE table_schema = ?
                ORDER BY (data_length + index_length) DESC
                LIMIT 10
            ", [$dbName]);

            $stats['top_tables']   = array_map(fn($t) => (array)$t, $tables);
            $stats['total_tables'] = count($tables);
            $stats['total_rows']   = array_sum(array_column($stats['top_tables'], 'rows'));

        } catch (\Throwable $e) {
            // graceful
        }

        return $stats;
    }

    private function getCacheStats(): array
    {
        try {
            $driver = config('cache.default', 'file');
            $cacheDir = storage_path('framework/cache/data');
            $fileCount = 0;
            $cacheSize = 0;

            if ($driver === 'file' && is_dir($cacheDir)) {
                $iter = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($cacheDir));
                foreach ($iter as $file) {
                    if ($file->isFile()) {
                        $fileCount++;
                        $cacheSize += $file->getSize();
                    }
                }
            }

            return [
                'driver'        => $driver,
                'file_count'    => $fileCount,
                'size_kb'       => round($cacheSize / 1024, 1),
                'ref_warmed'    => \Illuminate\Support\Facades\Cache::has('sgfep:ref:wilayas'),
                'kpi_warmed'    => \Illuminate\Support\Facades\Cache::has('sgfep:kpi:admin'),
            ];
        } catch (\Throwable $e) {
            return ['driver' => 'unknown', 'file_count' => 0, 'size_kb' => 0, 'ref_warmed' => false, 'kpi_warmed' => false];
        }
    }

    public function searchSovereignTargets()
    {
        $wilayaId = request()->query('wilaya_id') ? (int)request()->query('wilaya_id') : null;
        $etabId = request()->query('etab_id') ? (int)request()->query('etab_id') : null;
        $search = request()->query('search');

        // 1. Fetch filtered Establishments
        $etabsQuery = DB::table('etablissement')
            ->select('IDetablissement as id', 'Nom as name', 'IDDFEP as wilaya_id')
            ->orderBy('Nom');
        
        if ($wilayaId) {
            $etabsQuery->where('IDDFEP', $wilayaId);
        }
        $etabs = $etabsQuery->get();

        // 2. Fetch filtered Users
        $usersQuery = DB::table('utilisateur')
            ->select('IDUtilisateur as id', 'NomUser as username', 'Nom as name', 'IDBureau as etab_id', 'Code as wilaya_id', 'IDNature as nature_id')
            ->where(function($q) {
                $q->where('NomUser', '!=', 'admin')
                  ->where('NomUser', '!=', 'DISI');
            });

        if ($wilayaId) {
            $usersQuery->where(function($q) use ($wilayaId) {
                // If Code matches wilaya (for DFEP)
                $q->where('Code', $wilayaId)
                  // Or if user's establishment belongs to the wilaya
                  ->orWhereIn('IDBureau', function($sub) use ($wilayaId) {
                      $sub->select('IDEts_Form')
                          ->from('etablissement')
                          ->where('IDDFEP', $wilayaId);
                  });
            });
        }

        if ($etabId) {
            $usersQuery->where('IDBureau', $etabId);
        }

        if (!empty($search)) {
            $usersQuery->where(function($q) use ($search) {
                $q->where('NomUser', 'like', "%{$search}%")
                  ->orWhere('Nom', 'like', "%{$search}%");
            });
        }

        // Return a list of users with a descriptive name, e.g. "Ahmed (ahmed12) [DFEP]"
        $users = $usersQuery->limit(150)->get();

        $natureNames = [
            1 => 'وزارة',
            2 => 'مديرية مركزية',
            3 => 'مؤسسة / مكون',
            4 => 'مديرية الولائية (DFEP)',
            5 => 'متربص',
        ];

        $formattedUsers = array_map(function($u) use ($natureNames) {
            $roleText = $natureNames[$u->nature_id] ?? 'مستخدم';
            return [
                'id' => $u->id,
                'username' => $u->username,
                'display_name' => ($u->name ?: $u->username) . " ({$u->username}) [" . $roleText . "]"
            ];
        }, $users->toArray());

        return response()->json([
            'establishments' => $etabs,
            'users' => $formattedUsers
        ]);
    }

    // ── Sovereign Licensing Handlers ─────────────────────────────────────

    private function handleSovereignToggle(): void
    {
        $enabled = isset(request()->all()['is_activation_required']) ? '1' : '0';
        \App\Helpers\SovereignLicensingHelper::setSetting('is_activation_required', $enabled);
        \App\Core\AuditLogger::logWarning('[SOVEREIGN] Admin changed platform activation requirement to: ' . ($enabled === '1' ? 'ENABLED' : 'DISABLED'));
        session(['flash_success' => 'تم تعديل حالة تفعيل الترخيص بنجاح! / Statut de licence modifié.']);
    }

    private function handleShieldToggle(): void
    {
        $enabled = isset(request()->all()['is_shield_active']) ? '1' : '0';
        \App\Helpers\SovereignLicensingHelper::setSetting('content_protection_shield_active', $enabled);
        \App\Core\AuditLogger::logWarning('[SHIELD] Admin changed content protection shield to: ' . ($enabled === '1' ? 'ENABLED' : 'DISABLED'));
        session(['flash_success' => 'تم تعديل حالة درع الحماية بنجاح! / Statut du bouclier de protection modifié.']);
    }

    private function handleCaptchaToggle(): void
    {
        $enabled = isset(request()->all()['login_captcha_active']) ? '1' : '0';
        \App\Helpers\SovereignLicensingHelper::setSetting('login_captcha_active', $enabled);
        \App\Core\AuditLogger::logWarning('[CAPTCHA] Admin changed login captcha requirement to: ' . ($enabled === '1' ? 'ENABLED' : 'DISABLED'));
        session(['flash_success' => 'تم تعديل حالة التحقق البشري (CAPTCHA) بنجاح! / Statut CAPTCHA modifié.']);
    }

    private function handleHideOtherLoginsToggle(): void
    {
        $enabled = isset(request()->all()['hide_other_login_portals']) ? '1' : '0';
        \App\Helpers\SovereignLicensingHelper::setSetting('hide_other_login_portals', $enabled);
        \App\Core\AuditLogger::logWarning('[LOGIN_SECURITY] Admin changed hide other login portals setting to: ' . ($enabled === '1' ? 'ENABLED' : 'DISABLED'));
        session(['flash_success' => 'تم تعديل إعدادات بوابات الدخول بنجاح! / Paramètres de connexion modifiés.']);
    }

    private function handleSsoToggle(): void
    {
        $enabled = isset(request()->all()['sso_enabled']) ? '1' : '0';
        \App\Helpers\SovereignLicensingHelper::setSetting('sso_enabled', $enabled);
        \App\Core\AuditLogger::logWarning('[SSO] Admin changed SSO status to: ' . ($enabled === '1' ? 'ENABLED' : 'DISABLED'));
        session(['flash_success' => 'تم تعديل حالة تسجيل الدخول الموحد (SSO) بنجاح!']);
    }

    private function handlePushToggle(): void
    {
        $enabled = isset(request()->all()['push_notifications_enabled']) ? '1' : '0';
        \App\Helpers\SovereignLicensingHelper::setSetting('push_notifications_enabled', $enabled);
        \App\Core\AuditLogger::logWarning('[PUSH] Admin changed Push Notifications status to: ' . ($enabled === '1' ? 'ENABLED' : 'DISABLED'));
        session(['flash_success' => 'تم تعديل حالة إشعارات المتصفح (Push Notifications) بنجاح!']);
    }

    private function handleSseToggle(): void
    {
        $enabled = isset(request()->all()['sse_realtime_kpis_enabled']) ? '1' : '0';
        \App\Helpers\SovereignLicensingHelper::setSetting('sse_realtime_kpis_enabled', $enabled);
        \App\Core\AuditLogger::logWarning('[SSE] Admin changed SSE Real-time KPIs status to: ' . ($enabled === '1' ? 'ENABLED' : 'DISABLED'));
        session(['flash_success' => 'تم تعديل حالة البث الفوري للمؤشرات (SSE) بنجاح!']);
    }

    private function handleRateLimitingToggle(): void
    {
        $enabled = isset(request()->all()['rate_limiting_enabled']) ? '1' : '0';
        \App\Helpers\SovereignLicensingHelper::setSetting('rate_limiting_enabled', $enabled);
        \App\Core\AuditLogger::logWarning('[RATELIMIT] Admin changed Rate Limiting status to: ' . ($enabled === '1' ? 'ENABLED' : 'DISABLED'));
        session(['flash_success' => 'تم تعديل حالة محدد معدل الطلبات المتقدم بنجاح!']);
    }

    private function handleSovereignGenerate(): void
    {
        $count = (int)request()->input('generate_count', 1);
        $etsId = request()->input('generate_ets_id') ? (int)request()->input('generate_ets_id') : null;
        $userId = request()->input('generate_user_id') ? (int)request()->input('generate_user_id') : null;
        
        if ($count <= 0) $count = 1;
        if ($count > 100) $count = 100;
        
        for ($i = 0; $i < $count; $i++) {
            $key = \App\Helpers\SovereignLicensingHelper::generateLicenseKey();
            DB::table('license_keys')->insert([
                'license_key' => $key,
                'ets_id'      => $etsId,
                'user_id'     => $userId, // Pre-assign the license key to a specific user
                'created_at'  => now(),
                'updated_at'  => now()
            ]);
        }
        
        \App\Core\AuditLogger::logWarning("[SOVEREIGN] Admin generated {$count} new license keys.");
        session(['flash_success' => "تم توليد {$count} مفتاح ترخيص بنجاح! / Clés générées avec succès."]);
    }

    private function handleSovereignDelete(): void
    {
        $id = (int)request()->input('key_id');
        $key = DB::table('license_keys')->where('id', $id)->first();
        if ($key) {
            DB::table('license_keys')->where('id', $id)->delete();
            if ($key->user_id) {
                \Illuminate\Support\Facades\Cache::forget("sovereign:user_active:{$key->user_id}");
            }
            \App\Core\AuditLogger::logError("[SOVEREIGN] Admin deleted/revoked license key: {$key->license_key}");
            session(['flash_success' => 'تم حذف/إلغاء مفتاح الترخيص بنجاح! / Clé supprimée.']);
        } else {
            session(['flash_error' => 'مفتاح الترخيص غير موجود.']);
        }
    }

    private function handleSovereignExtend(): void
    {
        $id = (int)request()->input('key_id');
        $days = (int)request()->input('extend_days', 365);
        if ($days <= 0) $days = 365;

        $key = DB::table('license_keys')->where('id', $id)->first();
        if ($key) {
            $newExpiry = now()->addDays($days);
            DB::table('license_keys')->where('id', $id)->update([
                'expires_at' => $newExpiry,
                'updated_at' => now()
            ]);
            if ($key->user_id) {
                \Illuminate\Support\Facades\Cache::forget("sovereign:user_active:{$key->user_id}");
            }
            \App\Core\AuditLogger::logWarning("[SOVEREIGN] Admin extended license key {$key->license_key} by {$days} days.");
            session(['flash_success' => "تم تمديد صلاحية المفتاح بنجاح حتى " . $newExpiry->format('Y-m-d')]);
        } else {
            session(['flash_error' => 'مفتاح الترخيص غير موجود.']);
        }
    }

    private function handleLeaveAction(): void
    {
        $id = (int)request()->input('id');
        $status = request()->input('status'); // 'approved' or 'rejected'
        
        if (!in_array($status, ['approved', 'rejected'])) {
            session(['flash_error' => 'حالة غير صالحة.']);
            return;
        }

        $leave = DB::table('employee_leaves')->where('id', $id)->first();
        if ($leave) {
            DB::table('employee_leaves')->where('id', $id)->update([
                'status' => $status,
                'updated_at' => now()
            ]);
            
            $msg = $status === 'approved' ? 'تم قبول طلب الإجازة بنجاح!' : 'تم رفض طلب الإجازة.';
            \App\Core\AuditLogger::logWarning("[EMPLOYEE_PORTAL] Admin updated leave request #{$id} status to: {$status}");
            session(['flash_success' => $msg]);
        } else {
            session(['flash_error' => 'طلب الإجازة غير موجود.']);
        }
    }

    private function handleDocAction(): void
    {
        $id = (int)request()->input('id');
        $status = request()->input('status'); // 'approved' or 'rejected'
        
        if (!in_array($status, ['approved', 'rejected'])) {
            session(['flash_error' => 'حالة غير صالحة.']);
            return;
        }

        $doc = DB::table('employee_document_requests')->where('id', $id)->first();
        if ($doc) {
            DB::table('employee_document_requests')->where('id', $id)->update([
                'status' => $status,
                'updated_at' => now()
            ]);
            
            $msg = $status === 'approved' ? 'تم قبول/تجهيز طلب الوثيقة بنجاح!' : 'تم رفض طلب الوثيقة.';
            \App\Core\AuditLogger::logWarning("[EMPLOYEE_PORTAL] Admin updated document request #{$id} status to: {$status}");
            session(['flash_success' => $msg]);
        } else {
            session(['flash_error' => 'طلب الوثيقة غير موجود.']);
        }
    }

    private function handleMinistrySyncUpdate(): void
    {
        $nom = request()->input('ministry_name');
        $nomFr = request()->input('ministry_name_fr');
        $ipsrvhfsql = request()->input('ipsrvhfsql');
        $portsrvhfsql = (int)request()->input('portsrvhfsql', 4900);
        $ipsrvmysql = request()->input('ipsrvmysql');
        $portsrvmusql = (int)request()->input('portsrvmusql', 3306);
        $ipsrvftp = request()->input('ipsrvftp');
        $portsrvftp = (int)request()->input('portsrvftp', 21);
        $ipsrvhttp = request()->input('ipsrvhttp');
        $dnssrvhttp = request()->input('dnssrvhttp');
        $nomUser = request()->input('nom_user');
        $motDePass = request()->input('mot_de_pass');
        $activeSemesterId = (int)request()->input('active_semester_id', 1);

        // Update minister table
        $updateData = [
            'Nom' => $nom,
            'NomFr' => $nomFr,
            'ipsrvhfsql' => $ipsrvhfsql,
            'portsrvhfsql' => $portsrvhfsql,
            'ipsrvmysql' => $ipsrvmysql,
            'portsrvmusql' => $portsrvmusql,
            'ipsrvftp' => $ipsrvftp,
            'portsrvftp' => $portsrvftp,
            'ipsrvhttp' => $ipsrvhttp,
            'dnssrvhttp' => $dnssrvhttp,
            'NomUser' => $nomUser,
        ];
        if (!empty($motDePass)) {
            $updateData['Motdepass'] = $motDePass;
        }

        DB::table('minister')->where('IDMinister', 1)->update($updateData);
        DB::table('ministere')->where('IDMinister', 1)->update($updateData);

        // Update active_semester_id in takwin_settings
        $exists = DB::table('takwin_settings')->first();
        if ($exists) {
            DB::table('takwin_settings')->where('id', $exists->id)->update([
                'active_semester_id' => $activeSemesterId,
                'updated_at' => now()
            ]);
        } else {
            DB::table('takwin_settings')->insert([
                'api_url' => 'https://takwin.dz/api',
                'api_token' => '',
                'sync_enabled' => 0,
                'active_semester_id' => $activeSemesterId,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        session(['flash_success' => 'تم حفظ إعدادات خوادم ومزامنة الوزارة بنجاح!']);
    }

    private function handleLandingSettingsUpdate(): void
    {
        $keys = [
            'landing_primary_color',
            'landing_secondary_color',
            'landing_bg_color',
            'landing_hero_title',
            'landing_hero_title_gradient',
            'landing_hero_desc',
            'landing_hero_btn1_text',
            'landing_hero_btn2_text',
            'landing_features_eyebrow',
            'landing_features_title',
            'landing_features_title_accent',
            'landing_features_desc',
            'landing_features_btn_text',
        ];

        foreach ($keys as $key) {
            if (request()->has($key)) {
                \App\Helpers\SovereignLicensingHelper::setSetting($key, request()->input($key));
            }
        }

        // Create uploads folder if it doesn't exist
        $uploadsPath = public_path('uploads');
        if (!is_dir($uploadsPath)) {
            mkdir($uploadsPath, 0755, true);
        }

        // Handle Image Uploads (with secure real-MIME validation)
        if (request()->hasFile('landing_logo_file')) {
            $file = request()->file('landing_logo_file');
            $validation = \App\Services\FileValidator::validate($file, 'logo');
            if (!$validation['ok']) {
                session(['flash_error' => $validation['error']]);
                return;
            }
            if ($file->isValid()) {
                $filename = 'logo_' . time() . '.' . strtolower($file->getClientOriginalExtension());
                $file->move($uploadsPath, $filename);
                \App\Helpers\SovereignLicensingHelper::setSetting('landing_logo', 'uploads/' . $filename);
            }
        } elseif (request()->input('landing_logo_url')) {
            \App\Helpers\SovereignLicensingHelper::setSetting('landing_logo', request()->input('landing_logo_url'));
        }

        if (request()->hasFile('landing_hero_image_file')) {
            $file = request()->file('landing_hero_image_file');
            $validation = \App\Services\FileValidator::validate($file, 'logo');
            if (!$validation['ok']) {
                session(['flash_error' => $validation['error']]);
                return;
            }
            if ($file->isValid()) {
                $filename = 'hero_' . time() . '.' . strtolower($file->getClientOriginalExtension());
                $file->move($uploadsPath, $filename);
                \App\Helpers\SovereignLicensingHelper::setSetting('landing_hero_image', 'uploads/' . $filename);
            }
        } elseif (request()->input('landing_hero_image_url')) {
            \App\Helpers\SovereignLicensingHelper::setSetting('landing_hero_image', request()->input('landing_hero_image_url'));
        }

        session(['flash_success' => 'تم حفظ إعدادات المظهر والواجهة بنجاح!']);
    }

    private function handlePortalCMSUpdate(): void
    {
        $slug = request()->input('slug');
        if (empty($slug)) {
            session(['flash_error' => 'المعرف التعريفي للصفحة غير صالح.']);
            return;
        }

        $title = request()->input('title');
        $titleFr = request()->input('title_fr');
        $icon = request()->input('icon', 'fa-file-lines');
        $content = request()->input('content');
        $contentFr = request()->input('content_fr');
        $sortOrder = (int)request()->input('sort_order', 0);

        $data = [
            'title' => $title,
            'title_fr' => $titleFr,
            'icon' => $icon,
            'content' => $content,
            'content_fr' => $contentFr,
            'sort_order' => $sortOrder
        ];

        \App\Helpers\PortalCMSHelper::updatePage($slug, $data);
        session(['flash_success' => 'تم تحديث محتوى الصفحة بنجاح!']);
    }

    private function handlePortalCMSAdd(): void
    {
        $slug = request()->input('slug');
        $title = request()->input('title');
        $titleFr = request()->input('title_fr');
        $icon = request()->input('icon', 'fa-file-lines');
        $content = request()->input('content', '');
        $contentFr = request()->input('content_fr', '');

        if (empty($slug) || empty($title)) {
            session(['flash_error' => 'يرجى ملء جميع الحقول الإلزامية (المعرف والعنوان).']);
            return;
        }

        try {
            \App\Helpers\PortalCMSHelper::addPage([
                'slug' => $slug,
                'title' => $title,
                'title_fr' => $titleFr,
                'icon' => $icon,
                'content' => $content,
                'content_fr' => $contentFr
            ]);
            session(['flash_success' => 'تمت إضافة الصفحة الجديدة للبوابة بنجاح!']);
        } catch (\Exception $e) {
            session(['flash_error' => 'فشل إضافة الصفحة: ' . $e->getMessage()]);
        }
    }

    private function handlePortalCMSDelete(): void
    {
        $slug = request()->input('slug');
        if (empty($slug)) {
            session(['flash_error' => 'المعرف التعريفي غير صالح.']);
            return;
        }

        $ok = \App\Helpers\PortalCMSHelper::deletePage($slug);
        if ($ok) {
            session(['flash_success' => 'تم حذف الصفحة بالكامل بنجاح من البوابة!']);
        } else {
            session(['flash_error' => 'فشل حذف الصفحة المطلوبة.']);
        }
    }

    private function handlePatrimoineMediaToggle(): void
    {
        $enabled = isset(request()->all()['patrimoine_media_actions_enabled']) ? '1' : '0';
        \App\Helpers\SovereignLicensingHelper::setSetting('patrimoine_media_actions_enabled', $enabled);
        \App\Core\AuditLogger::logWarning('[SETTINGS] Admin changed patrimoine media actions enabled to: ' . ($enabled === '1' ? 'ENABLED' : 'DISABLED'));
        session(['flash_success' => 'تم تعديل إعدادات صلاحية إدارة صور الممتلكات بنجاح!']);
    }

    private function handleFeatureToggle(string $key, string $label): void
    {
        $enabled = isset(request()->all()[$key]) ? '1' : '0';
        \App\Helpers\SovereignLicensingHelper::setSetting($key, $enabled);
        \App\Core\AuditLogger::logWarning('[SETTINGS] Admin changed feature flag [' . $key . '] to: ' . ($enabled === '1' ? 'ENABLED' : 'DISABLED'));
        session(['flash_success' => 'تم تعديل إعداد «' . $label . '» بنجاح! / Paramètre «' . $label . '» modifié avec succès.']);
    }

    private function handleEnrollmentPermissionsUpdate(): void
    {
        $actions = ['add', 'edit', 'delete', 'export'];
        $wilayas = DB::table('wilaya')->select('IDWilayaa')->get();
        
        foreach ($actions as $action) {
            $globalKey = "enrollment_{$action}_enabled";
            $globalVal = isset(request()->all()[$globalKey]) ? '1' : '0';
            \App\Helpers\SovereignLicensingHelper::setSetting($globalKey, $globalVal);
            
            $disabledIds = [];
            foreach ($wilayas as $w) {
                $inputName = "wilaya_{$w->IDWilayaa}_{$action}";
                if (!isset(request()->all()[$inputName])) {
                    $disabledIds[] = $w->IDWilayaa;
                }
            }
            
            $restrictedKey = "enrollment_restricted_wilayas_{$action}";
            \App\Helpers\SovereignLicensingHelper::setSetting($restrictedKey, implode(',', $disabledIds));
        }
        
        \App\Core\AuditLogger::logWarning('[SETTINGS] Admin updated Enrollment Board permissions.');
        session(['flash_success' => 'تم حفظ صلاحيات وإعدادات لوحة الالتحاق بنجاح! / Les permissions d\'inscription ont été enregistrées avec succès.']);
    }
}
