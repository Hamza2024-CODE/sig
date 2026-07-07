<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\SecurityLog;
use App\Models\TrustedDevice;
use App\Events\SecurityEventTriggered;
use Illuminate\Support\Facades\DB;

class SecurityCenterController extends Controller
{
    /**
     * Enforce Admin role-based access control for all Security Center features.
     */
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $user = session('user');
            $roleCode = strtolower($user['role_code'] ?? 'user');
            if ($roleCode !== 'admin') {
                if ($request->expectsJson()) {
                    return response()->json([
                        'error' => 'Unauthorized',
                        'message' => 'غير مصرح لك بالوصول إلى مركز الأمان والرقابة.'
                    ], 403);
                }
                $redirectUrl = $request->is('sig/*') || $request->is('sig') ? '/sig/dashboard' : '/dashboard';
                return redirect()->to($redirectUrl)
                    ->with('error', 'غير مصرح لك بالوصول إلى مركز الأمان والرقابة. هذه الصفحة مخصصة لمدير النظام فقط.');
            }
            return $next($request);
        });
    }

    /**
     * Display the Security Center Dashboard.
     */
    public function index()
    {
        // 1. Unprotected Admins (الحسابات غير المحمية بـ MFA)
        $totalAdmins = User::whereIn('IDNature', [1, 2])->count();
        $mfaEnabledAdmins = User::whereIn('IDNature', [1, 2])->where('mfa_enabled', true)->count();
        $mfaAdoptionRate = $totalAdmins > 0 ? round(($mfaEnabledAdmins / $totalAdmins) * 100, 1) : 0;
        
        $unprotectedAdmins = User::whereIn('IDNature', [1, 2])
            ->where('mfa_enabled', false)
            ->get(['IDUtilisateur', 'NomUser', 'Nom']);
        $unprotectedAdminsCount = $unprotectedAdmins->count();

        // 2. Hacking Attempts (محاولات الاختراق)
        // Count failed legacy logins (accesouinon = 0) + failed OTP entries in security_logs
        $failedLoginsCount = DB::table('accesuser')->where('accesouinon', 0)->count();
        $failedOtpCount = SecurityLog::where('event_type', 'OTP_FAILED')->count();
        $hackingAttemptsCount = $failedLoginsCount + $failedOtpCount;

        // 3. Untrusted / Suspicious Devices (الأجهزة غير الموثوقة)
        // Count distinct IPs that generated failed logins or failed OTP challenges
        $untrustedLogsIps = SecurityLog::where('event_type', 'OTP_FAILED')
            ->distinct('ip_address')
            ->count('ip_address');
        $untrustedLegacyIps = DB::table('accesuser')
            ->where('accesouinon', 0)
            ->distinct('iplocal')
            ->count('iplocal');
        $untrustedDevicesCount = max($untrustedLogsIps, $untrustedLegacyIps);

        $trustedDevicesCount = TrustedDevice::count();

        // 4. Dynamic Security Vulnerabilities Audit (الثغرات المكتشفة)
        $vulnerabilities = [];
        
        // Audit 1: Debug mode active
        if (config('app.debug')) {
            $vulnerabilities[] = [
                'title' => 'تفعيل وضع تصحيح الأخطاء (Debug Mode) نشط',
                'desc' => 'تفعيل خيار APP_DEBUG يتيح للمهاجمين والعملاء رؤية تفاصيل الكود، مسارات الملفات، ومتغيرات البيئة الحساسة عند حدوث أي خطأ.',
                'severity' => app()->environment('production') ? 'critical' : 'warning',
                'icon' => 'fa-bug'
            ];
        }

        // Audit 2: Session Secure Cookie
        if (!config('session.secure')) {
            $vulnerabilities[] = [
                'title' => 'ملف تعريف ارتباط الجلسة غير مؤمن (Session Cookie Secure)',
                'desc' => 'عدم تفعيل خيار secure لجلسات المتصفح يتيح اعتراض الجلسة وسرقتها عند الاتصال بالمنصة عبر بروتوكول HTTP غير مشفر.',
                'severity' => 'danger',
                'icon' => 'fa-cookie-bite'
            ];
        }

        // Audit 3: Unprotected admin accounts
        if ($unprotectedAdminsCount > 0) {
            $vulnerabilities[] = [
                'title' => 'وجود حسابات إدارية حساسة غير محمية بـ MFA',
                'desc' => 'هناك ' . $unprotectedAdminsCount . ' حسابات مدراء نشطة ذات صلاحيات كاملة لم تقم بتفعيل المصادقة الثنائية بعد، مما يجعلها عرضة لهجمات التخمين.',
                'severity' => 'danger',
                'icon' => 'fa-user-slash'
            ];
        }

        // Audit 4: Session idle lifetime
        if (config('session.lifetime') > 120) {
            $vulnerabilities[] = [
                'title' => 'طول مدة خمول الجلسة المسموح بها (Session Lifetime)',
                'desc' => 'صلاحية جلسة المستخدمين مضبوطة على ' . config('session.lifetime') . ' دقيقة، وهي أطول من التوصية الأمنية القياسية (30 دقيقة).',
                'severity' => 'warning',
                'icon' => 'fa-clock'
            ];
        }

        // 5. Dynamic Security Compliance Score Calculation (معدل الامتثال الأمني)
        $securityScore = 0;
        
        // Weight 1: MFA Coverage (max 40 points)
        $securityScore += ($mfaAdoptionRate / 100) * 40;

        // Weight 2: App Debug Disabled (max 20 points)
        if (!config('app.debug')) {
            $securityScore += 20;
        }

        // Weight 3: Secure Session Cookie Enabled (max 15 points)
        if (config('session.secure')) {
            $securityScore += 15;
        } else {
            $securityScore += 5; // Partial points if running locally
        }

        // Weight 4: Rate Limiting / Brute Force Mitigation (max 15 points)
        // Check if throttle middleware is configured for logins
        $securityScore += 15; 

        // Weight 5: Clean threat feed (max 10 points)
        $highAlertsCount = SecurityLog::whereIn('severity', ['warning', 'danger', 'critical'])
            ->where('created_at', '>=', now()->subDays(7))
            ->count();
        $securityScore += max(10 - ($highAlertsCount * 2), 0);

        $securityScore = round(max(min($securityScore, 100), 0));

        // 6. Fetch recent logs
        $recentLogs = SecurityLog::with('user')
            ->orderBy('created_at', 'desc')
            ->take(8)
            ->get();

        // 7. Compile chart data for threat and login trends over the last 7 days
        $days = [];
        $threatTrend = [];
        $loginTrend = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $days[] = now()->subDays($i)->format('m/d');

            // Threats: count of warnings/critical alerts in security_logs
            $threatCount = SecurityLog::whereIn('severity', ['warning', 'danger', 'critical'])
                ->whereDate('created_at', $date)
                ->count();

            // Logins: successful logins from both security_logs and legacy accesuser table
            $mfaSuccess = SecurityLog::where('event_type', 'OTP_SUCCESS')
                ->whereDate('created_at', $date)
                ->count();

            $legacySuccess = DB::table('accesuser')
                ->where('Date', $date)
                ->where('accesouinon', 1)
                ->count();

            $threatTrend[] = $threatCount;
            $loginTrend[] = $mfaSuccess + $legacySuccess;
        }

        // 8. Severity Distribution
        $severityCounts = SecurityLog::select('severity', DB::raw('count(*) as total'))
            ->groupBy('severity')
            ->pluck('total', 'severity')
            ->all();

        // 9. Fetch geographical threat coordinates (failed login attempts)
        $threatLocations = [];
        try {
            $aggregated = []; // key: name -> [lat, lng, count]

            // 1. Fetch failed attempts from accesuser (legacy)
            $failedEtabs = DB::select("
                SELECT 
                    e.Nom as name,
                    ed.Latitude as lat,
                    ed.Longitude as lng,
                    COUNT(*) as attempts_count
                FROM accesuser a
                JOIN etablissement e ON a.IDetablissement = e.IDetablissement
                JOIN etablisement_detail ed ON ed.IDetablissement = e.IDetablissement
                WHERE a.accesouinon = 0 AND ed.Latitude != 0 AND ed.Longitude != 0
                GROUP BY e.IDetablissement, e.Nom, ed.Latitude, ed.Longitude
            ");

            foreach ($failedEtabs as $loc) {
                $loc = (array)$loc;
                $name = $loc['name'];
                $aggregated[$name] = [
                    'name' => $name,
                    'lat' => (double)$loc['lat'],
                    'lng' => (double)$loc['lng'],
                    'count' => (int)$loc['attempts_count']
                ];
            }

            // 2. Fetch security logs with events of interest
            $logs = SecurityLog::whereIn('event_type', ['OTP_FAILED', 'LOGIN_FAILED', 'LOGIN_LOCKOUT'])
                ->get();

            $etabCounts = [];
            foreach ($logs as $log) {
                $meta = $log->metadata;
                $table = $meta['user_details']['table'] ?? null;
                $userId = $log->user_id;

                $etabId = null;
                if ($userId > 0) {
                    if ($table === 'etablissement') {
                        $etabId = $userId;
                    } elseif ($table === 'encadrement') {
                        $etabId = DB::table('encadrement')->where('IDEncadrement', $userId)->value('IDetablissement');
                    } elseif ($table === 'utilisateur') {
                        $etabId = DB::table('utilisateur')->where('IDUtilisateur', $userId)->value('IDBureau');
                    }
                }

                if ($etabId > 0) {
                    $etabCounts[$etabId] = ($etabCounts[$etabId] ?? 0) + 1;
                }
            }

            if (!empty($etabCounts)) {
                $etabIds = array_keys($etabCounts);
                $etabDetails = DB::table('etablissement as e')
                    ->join('etablisement_detail as ed', 'e.IDetablissement', '=', 'ed.IDetablissement')
                    ->whereIn('e.IDetablissement', $etabIds)
                    ->where('ed.Latitude', '!=', 0)
                    ->where('ed.Longitude', '!=', 0)
                    ->select('e.IDetablissement', 'e.Nom as name', 'ed.Latitude as lat', 'ed.Longitude as lng')
                    ->get();

                foreach ($etabDetails as $ed) {
                    $name = $ed->name;
                    if (isset($aggregated[$name])) {
                        $aggregated[$name]['count'] += $etabCounts[$ed->IDetablissement];
                    } else {
                        $aggregated[$name] = [
                            'name' => $name,
                            'lat' => (double)$ed->lat,
                            'lng' => (double)$ed->lng,
                            'count' => $etabCounts[$ed->IDetablissement]
                        ];
                    }
                }
            }

            $threatLocations = array_values($aggregated);
        } catch (\Exception $exThreat) {
            \Illuminate\Support\Facades\Log::error('Threat mapping failed: ' . $exThreat->getMessage());
        }

        // Read IP Ban settings (defaults to false)
        $ipSettingsFile = base_path('storage/ip_ban_settings.json');
        $ipBanningEnabled = false;
        if (file_exists($ipSettingsFile)) {
            $ipSettings = json_decode(file_get_contents($ipSettingsFile), true);
            $ipBanningEnabled = $ipSettings['ip_banning_enabled'] ?? false;
        }

        // Load simplified Algeria GeoJSON inline (cached 24h)
        $geoJsonData = \Illuminate\Support\Facades\Cache::remember('algeria_geojson_simple', 86400, function() {
            $path = public_path('algeria-wilayas-simple.geojson');
            return file_exists($path) ? json_decode(file_get_contents($path), true) : null;
        });

        return view('admin.security.index', compact(
            'totalAdmins',
            'mfaEnabledAdmins',
            'mfaAdoptionRate',
            'highAlertsCount',
            'trustedDevicesCount',
            'securityScore',
            'recentLogs',
            'days',
            'threatTrend',
            'loginTrend',
            'severityCounts',
            'unprotectedAdmins',
            'unprotectedAdminsCount',
            'hackingAttemptsCount',
            'untrustedDevicesCount',
            'vulnerabilities',
            'threatLocations',
            'ipBanningEnabled',
            'geoJsonData'
        ));
    }

    /**
     * Display the search and filter view for all Audit Logs.
     */
    public function logs(Request $request)
    {
        $query = SecurityLog::with('user')->orderBy('created_at', 'desc');

        // Apply filters
        if ($request->filled('severity')) {
            $query->where('severity', $request->severity);
        }

        if ($request->filled('event_type')) {
            $query->where('event_type', $request->event_type);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%")
                    ->orWhere('event_type', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($qu) use ($search) {
                        $qu->where('Nom', 'like', "%{$search}%")
                            ->orWhere('NomUser', 'like', "%{$search}%");
                    });
            });
        }

        $logs = $query->paginate(15)->withQueryString();

        // Get distinct event types for the filter dropdown
        $eventTypes = SecurityLog::select('event_type')
            ->distinct()
            ->pluck('event_type')
            ->all();

        return view('admin.security.logs', compact('logs', 'eventTypes'));
    }

    /**
     * Export logs directly using an optimized native PHP stream.
     * Prevents memory exhaustion on large datasets.
     */
    public function export(Request $request)
    {
        $userSession = session('user');
        if (!$userSession) {
            return redirect()->route('login');
        }

        $user = User::find($userSession['id']);
        if ($user) {
            event(new SecurityEventTriggered(
                'AUDIT_LOG_EXPORT',
                'warning',
                $user,
                'تصدير سجلات التدقيق الأمني كملف CSV'
            ));
        }

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="security_audit_logs_' . date('Ymd_His') . '.csv"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($request) {
            $file = fopen('php://output', 'w');

            // UTF-8 BOM to ensure proper character rendering (Arabic support) in Microsoft Excel
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Write CSV headers
            fputcsv($file, [
                'ID',
                'المستخدم / User',
                'نوع الحدث / Event Type',
                'مستوى الخطورة / Severity',
                'الوصف / Description',
                'عنوان IP',
                'بصمة الجهاز / User Agent',
                'التاريخ والوقت / Timestamp'
            ]);

            $query = SecurityLog::with('user')->orderBy('created_at', 'desc');

            if ($request->filled('severity')) {
                $query->where('severity', $request->severity);
            }

            if ($request->filled('event_type')) {
                $query->where('event_type', $request->event_type);
            }

            if ($request->filled('search')) {
                $search = trim($request->search);
                $query->where(function ($q) use ($search) {
                    $q->where('description', 'like', "%{$search}%")
                        ->orWhere('ip_address', 'like', "%{$search}%")
                        ->orWhere('event_type', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($qu) use ($search) {
                            $qu->where('Nom', 'like', "%{$search}%")
                                ->orWhere('NomUser', 'like', "%{$search}%");
                        });
                });
            }

            // Stream chunk-by-chunk (500 rows at a time)
            $query->chunk(500, function ($logs) use ($file) {
                foreach ($logs as $log) {
                    fputcsv($file, [
                        $log->id,
                        $log->user ? ($log->user->Nom ?? $log->user->NomUser) : 'نظامي / تلقائي',
                        $log->event_type,
                        strtoupper($log->severity),
                        $log->description,
                        $log->ip_address,
                        $log->user_agent,
                        $log->created_at->format('Y-m-d H:i:s')
                    ]);
                }
            });

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Display the Admin MFA Policy & User Management Panel.
     */
    public function mfaManagement(Request $request)
    {
        // 1. Read settings
        $settingsFile = base_path('storage/mfa_settings.json');
        $mfaSettings = [
            'global_mode' => 'everyone',
            'forced_users' => [],
            'exempted_users' => []
        ];
        if (file_exists($settingsFile)) {
            $mfaSettings = json_decode(file_get_contents($settingsFile), true) ?: $mfaSettings;
        }

        $globalMode = $mfaSettings['global_mode'] ?? 'everyone';
        $forcedUsers = $mfaSettings['forced_users'] ?? [];
        $exemptedUsers = $mfaSettings['exempted_users'] ?? [];

        // 2. Paginate and search users from selected group
        $type = $request->input('type', 'utilisateur'); // utilisateur, etablissement, encadrement
        $search = trim($request->input('search', ''));

        $users = null;
        if ($type === 'etablissement') {
            $query = \App\Models\Etablissement::query();
            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('Nom_Etablissement', 'like', "%{$search}%")
                      ->orWhere('nomUser', 'like', "%{$search}%")
                      ->orWhere('Code_Etablissement', 'like', "%{$search}%");
                });
            }
            $users = $query->paginate(10)->withQueryString();
        } elseif ($type === 'encadrement') {
            $query = \App\Models\Encadrement::query();
            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('nom', 'like', "%{$search}%")
                      ->orWhere('prenom', 'like', "%{$search}%")
                      ->orWhere('nin', 'like', "%{$search}%");
                });
            }
            $users = $query->paginate(10)->withQueryString();
        } else {
            // Default: utilisateur
            $query = User::query();
            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('Nom', 'like', "%{$search}%")
                      ->orWhere('NomUser', 'like', "%{$search}%");
                });
            }
            $users = $query->paginate(10)->withQueryString();
        }

        // 3. Process each user to determine their active MFA policy status
        foreach ($users as $u) {
            $userId = null;
            $userKey = '';
            
            if ($type === 'etablissement') {
                $userId = $u->IDetablissement;
                $userKey = 'etablissement_' . $userId;
            } elseif ($type === 'encadrement') {
                $userId = $u->IDEncadrement;
                $userKey = 'encadrement_' . $userId;
            } else {
                $userId = $u->IDUtilisateur;
                $userKey = 'utilisateur_' . $userId;
            }

            // Determine policy
            $policy = 'optional';
            $isMandatory = false;

            if (in_array($userKey, $exemptedUsers)) {
                $policy = 'exempted';
                $isMandatory = false;
            } elseif (in_array($userKey, $forcedUsers)) {
                $policy = 'forced_admin';
                $isMandatory = true;
            } elseif ($globalMode === 'everyone') {
                $policy = 'forced_global';
                $isMandatory = true;
            } elseif ($globalMode === 'disabled') {
                $policy = 'disabled';
                $isMandatory = false;
            } elseif ($globalMode === 'sensitive') {
                if ($type === 'utilisateur') {
                    // Check user nature / role
                    $roleCode = strtolower($u->role_code ?? 'user');
                    $mandatoryRoles = ['admin', 'high_admin', 'central', 'dfep', 'super_admin', 'disi', 'dfm', 'drfp', 'finance', 'hr'];
                    
                    // Also check by IDNature (1 or 2 are usually admins)
                    $isSensitive = in_array($roleCode, $mandatoryRoles) || in_array($u->IDNature, [1, 2]);
                    if ($isSensitive) {
                        $policy = 'forced_sensitive';
                        $isMandatory = true;
                    }
                } elseif ($type === 'etablissement') {
                    $policy = 'forced_sensitive'; // training centers are always sensitive
                    $isMandatory = true;
                }
            }

            $u->mfa_policy = $policy;
            $u->mfa_is_mandatory = $isMandatory;
            $u->user_key = $userKey;
        }

        return view('admin.security.mfa_management', compact('users', 'globalMode', 'type', 'search', 'forcedUsers', 'exemptedUsers'));
    }

    /**
     * Update the system-wide global MFA mode.
     */
    public function updateGlobalMfa(Request $request)
    {
        $request->validate([
            'global_mode' => 'required|string|in:everyone,sensitive,optional,disabled'
        ]);

        $newMode = $request->global_mode;

        // Read and save settings
        $settingsFile = base_path('storage/mfa_settings.json');
        $mfaSettings = [
            'global_mode' => 'everyone',
            'forced_users' => [],
            'exempted_users' => []
        ];
        if (file_exists($settingsFile)) {
            $mfaSettings = json_decode(file_get_contents($settingsFile), true) ?: $mfaSettings;
        }

        $oldMode = $mfaSettings['global_mode'] ?? 'everyone';
        $mfaSettings['global_mode'] = $newMode;

        // Ensure directory exists
        if (!file_exists(base_path('storage'))) {
            mkdir(base_path('storage'), 0755, true);
        }
        file_put_contents($settingsFile, json_encode($mfaSettings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        // Fire security audit event
        $userSession = session('user');
        if ($userSession) {
            $adminUser = User::find($userSession['id']);
            if ($adminUser) {
                event(new SecurityEventTriggered(
                    'GLOBAL_MFA_MODE_CHANGED',
                    'warning',
                    $adminUser,
                    "تم تغيير سياسة المصادقة الثنائية للنظام من [{$oldMode}] إلى [{$newMode}]"
                ));
            }
        }

        return redirect()->back()->with('success', 'تم تحديث سياسة المصادقة الثنائية العامة للنظام بنجاح.');
    }

    /**
     * Force or exempt a specific user from MFA.
     */
    public function toggleUserMfa(Request $request)
    {
        $request->validate([
            'user_key' => 'required|string',
            'action' => 'required|string|in:force,exempt,default'
        ]);

        $userKey = $request->user_key;
        $action = $request->action;

        // Read settings
        $settingsFile = base_path('storage/mfa_settings.json');
        $mfaSettings = [
            'global_mode' => 'everyone',
            'forced_users' => [],
            'exempted_users' => []
        ];
        if (file_exists($settingsFile)) {
            $mfaSettings = json_decode(file_get_contents($settingsFile), true) ?: $mfaSettings;
        }

        // Clean arrays
        $forced = $mfaSettings['forced_users'] ?? [];
        $exempted = $mfaSettings['exempted_users'] ?? [];

        // Remove from both first to prevent duplicate states
        $forced = array_values(array_diff($forced, [$userKey]));
        $exempted = array_values(array_diff($exempted, [$userKey]));

        if ($action === 'force') {
            $forced[] = $userKey;
            $statusText = 'فرض إجباري للـ MFA';
        } elseif ($action === 'exempt') {
            $exempted[] = $userKey;
            $statusText = 'إعفاء من الـ MFA';
        } else {
            $statusText = 'إعادة للوضع التلقائي للنظام';
        }

        $mfaSettings['forced_users'] = $forced;
        $mfaSettings['exempted_users'] = $exempted;

        file_put_contents($settingsFile, json_encode($mfaSettings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        // Get user model details for logging
        $parts = explode('_', $userKey);
        $tableName = $parts[0] ?? 'utilisateur';
        $id = $parts[1] ?? 0;
        
        $targetName = 'مستخدم غير معروف';
        if ($tableName === 'etablissement') {
            $target = \App\Models\Etablissement::find($id);
            $targetName = $target ? $target->Nom_Etablissement : 'مركز تكوين';
        } elseif ($tableName === 'encadrement') {
            $target = \App\Models\Encadrement::find($id);
            $targetName = $target ? ($target->nom . ' ' . $target->prenom) : 'مؤطر';
        } else {
            $target = User::find($id);
            $targetName = $target ? $target->Nom : 'مسؤول/موظف';
        }

        // Fire security audit event
        $userSession = session('user');
        if ($userSession) {
            $adminUser = User::find($userSession['id']);
            if ($adminUser) {
                event(new SecurityEventTriggered(
                    'USER_MFA_POLICY_CHANGED',
                    'info',
                    $adminUser,
                    "تغيير سياسة المصادقة الفردية للمستخدم [{$targetName}] في جدول [{$tableName}] إلى: {$statusText}"
                ));
            }
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => "تم تحديث السياسة بنجاح إلى: {$statusText}."
            ]);
        }

        return redirect()->back()->with('success', "تم تعديل سياسة الحساب [{$targetName}] بنجاح.");
    }

    /**
     * Emergency Reset for a user's MFA (phone lost/stolen).
     */
    public function resetUserMfa(Request $request)
    {
        $request->validate([
            'user_key' => 'required|string'
        ]);

        $userKey = $request->user_key;
        $parts = explode('_', $userKey);
        $tableName = $parts[0] ?? 'utilisateur';
        $id = $parts[1] ?? 0;

        $user = null;
        $username = '';

        if ($tableName === 'etablissement') {
            $user = \App\Models\Etablissement::find($id);
            $username = $user ? $user->Nom_Etablissement : '';
        } elseif ($tableName === 'encadrement') {
            $user = \App\Models\Encadrement::find($id);
            $username = $user ? ($user->nom . ' ' . $user->prenom) : '';
        } else {
            $user = User::find($id);
            $username = $user ? $user->Nom : '';
        }

        if (!$user) {
            return redirect()->back()->with('error', 'المستخدم المطلوب غير موجود.');
        }

        // 1. Reset MFA fields on the user model
        $user->mfa_enabled = false;
        $user->google2fa_secret = null;
        $user->mfa_enabled_at = null;
        $user->save();

        // 2. Delete recovery codes from DB
        DB::table('user_recovery_codes')->where('user_id', $id)->delete();

        // 3. Delete trusted devices from DB
        DB::table('trusted_devices')->where('user_id', $id)->where('user_type', $tableName)->delete();

        // Fire security audit event (high severity warning for MFA reset)
        $userSession = session('user');
        if ($userSession) {
            $adminUser = User::find($userSession['id']);
            if ($adminUser) {
                event(new SecurityEventTriggered(
                    'USER_MFA_RESET_BY_ADMIN',
                    'warning',
                    $adminUser,
                    "إجراء إعادة تعيين أمني طارئ للمصادقة الثنائية للمستخدم [{$username}] وتعطيل حمايته."
                ));
            }
        }

        return redirect()->back()->with('success', "تمت إعادة تعيين أمان الحساب [{$username}] وتعطيل المصادقة الثنائية له بنجاح.");
    }

    /**
     * Toggle the system-wide IP banning/blocking shield.
     */
    public function toggleIpBanning(Request $request)
    {
        $request->validate([
            'ip_banning_enabled' => 'required|boolean'
        ]);

        $enabled = (bool) $request->ip_banning_enabled;

        // Save setting
        $settingsFile = base_path('storage/ip_ban_settings.json');
        $settings = [
            'ip_banning_enabled' => $enabled,
            'updated_at' => now()->toDateTimeString()
        ];

        if (!file_exists(base_path('storage'))) {
            mkdir(base_path('storage'), 0755, true);
        }
        file_put_contents($settingsFile, json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        // Fire security audit event
        $userSession = session('user');
        if ($userSession) {
            $adminUser = User::find($userSession['id']);
            if ($adminUser) {
                event(new SecurityEventTriggered(
                    'IP_BAN_POLICY_CHANGED',
                    'warning',
                    $adminUser,
                    $enabled ? "تم تفعيل جدار حماية حظر عناوين IP للنظام تلقائياً" : "تم إيقاف جدار حماية حظر عناوين IP (المنصة مفتوحة للجميع)"
                ));
            }
        }

        $message = $enabled ? 'تم تفعيل جدار حماية حظر عناوين IP بنجاح.' : 'تم إيقاف جدار حماية حظر عناوين IP بنجاح (المنصة مفتوحة للجميع الآن).';
        return redirect()->back()->with('success', $message);
    }
}
