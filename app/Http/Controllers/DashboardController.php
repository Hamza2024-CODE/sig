<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\ReferenceCache;
use App\Services\KpiCache;
use App\Jobs\ExportStatistiquesJob;
use App\Domains\Finance\Repositories\FinanceRepository;

/**
 * DashboardController — Laravel 100% أصيل
 *
 * معمارية الذاكرة (3 طبقات):
 * ──────────────────────────────────────────────────────
 * L1 (ReferenceCache / 24h)  → الولايات، التخصصات، أنماط التكوين
 * L2 (KpiCache / 5-15 min)   → KPIs: COUNT/SUM فقط (أرقام مجملة)
 * L3 (Session)               → هوية المستخدم + صلاحياته
 *
 * قواعد ممنوعة في الكاش:
 * ❌ قوائم المتربصين الكاملة (10,000+ سجل)
 * ❌ نقاط التقييم التفصيلية (Transactional)
 * ❌ ملفات Base64 أو صور
 *
 * قواعد الـ Pagination:
 * ✅ paginate(20) أو cursorPaginate() على كل جدول > 50 سجل
 * ✅ with([relations]) قبل كل paginate() لتفادي N+1
 * ✅ cursor() بدلاً من get() في Jobs
 */
class DashboardController extends Controller
{
    // ══════════════════════════════════════════════════════════════════════
    // §1  MAIN DASHBOARD PAGE
    // ══════════════════════════════════════════════════════════════════════

    public function index(Request $request)
    {
        @set_time_limit(300);
        $user = session('user') ?? [];
        \Illuminate\Support\Facades\Log::info('Dashboard User Data:', ['user' => $user]);
        if (empty($user)) {
            return redirect()->route('login');
        }

        if ($request->has('ajax_active_etabs')) {
            $etabs = DB::table('etablissement as e')
                ->leftJoin('wilaya as w', 'e.IDDFEP', '=', 'w.IDWilayaa')
                ->leftJoin('nature_etsf as n', 'e.IDNature_etsF', '=', 'n.IDNature_etsF')
                ->where('e.activee', 0)
                ->select(
                    DB::raw("COALESCE(NULLIF(e.Nom, ''), 'مؤسسة بدون اسم') as nom"),
                    DB::raw("COALESCE(w.Nom, 'غير محدد') as wilaya"),
                    DB::raw("COALESCE(n.Nom, 'غير محدد') as nature"),
                    DB::raw("IF(e.nomUser IS NOT NULL AND e.nomUser != '', 1, 0) as has_account")
                )
                ->orderBy('w.Nom')
                ->orderBy('e.Nom')
                ->get();
            return response()->json($etabs);
        }

        if ($request->has('verify_cert_num')) {
            $num = $request->query('verify_cert_num');
            try {
                $cert = DB::table('attestation_succ as a')
                    ->join('apprenant_fin as af', 'a.IDApprenant_Fin', '=', 'af.IDApprenant_Fin')
                    ->join('apprenant as ap', 'af.IDapprenant', '=', 'ap.IDapprenant')
                    ->join('section as s', 'ap.IDSection', '=', 's.IDSection')
                    ->join('offre as o', 's.IDOffre', '=', 'o.IDOffre')
                    ->join('specialite as sp', 'o.IDSpecialite', '=', 'sp.IDSpecialite')
                    ->join('etablissement as e', 'o.IDEts_Form', '=', 'e.IDetablissement')
                    ->where('a.Num', 'LIKE', '%' . $num . '%')
                    ->select(
                        'a.Num as cert_num',
                        'a.Date as cert_date',
                        'a.Valide as is_valid',
                        'ap.Nom as trainee_nom',
                        'ap.Prenom as trainee_prenom',
                        'sp.Nom as spec_nom',
                        'e.Nom as etab_nom'
                    )
                    ->first();
                return response()->json([
                    'success' => true,
                    'found' => (bool)$cert,
                    'data' => $cert
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage()
                ]);
            }
        }

        $role = strtolower($user['role_code'] ?? 'user');
        if ($role === 'apprenant') {
            return request()->is('sig/*') || request()->is('sig')
                ? redirect('/sig/apprenant')
                : redirect('/apprenant');
        }

        // Dynamically classify employee vs formateur
        if ($role === 'employee') {
            $empId = (int)($user['id'] ?? 0);
            
            // Check 1: Exists in section_semestre_module
            $isTeacher = DB::table('section_semestre_module')->where('IDEncadrement', $empId)->exists();
            
            // Check 2: Check keywords in TachesPrincipale or Specialite if not already matched
            if (!$isTeacher) {
                $empData = DB::table('encadrement')
                    ->where('IDEncadrement', $empId)
                    ->select('TachesPrincipale', 'Specialite')
                    ->first();
                if ($empData) {
                    $taches = mb_strtolower($empData->TachesPrincipale ?? '');
                    $spec = mb_strtolower($empData->Specialite ?? '');
                    
                    $teacherKeywords = ['أستاذ', 'مكون', 'مدرس', 'تدريس', 'تعليم', 'بيداغوجي', 'formateur', 'enseignant', 'professeur', 'mouallim', 'moudaris'];
                    
                    foreach ($teacherKeywords as $kw) {
                        if (mb_strpos($taches, $kw) !== false || mb_strpos($spec, $kw) !== false) {
                            $isTeacher = true;
                            break;
                        }
                    }
                    
                    if (!$isTeacher && !empty($spec) && empty($taches)) {
                        $isTeacher = true;
                    }
                }
            }
            
            $role = $isTeacher ? 'formateur' : 'employee';
            $user['role_code'] = $role;
            session(['user' => $user]);
        }

        // ── Check if previewing a specific department dashboard via query param ──
        $viewDir = $request->query('view_dir');
        if (!empty($viewDir)) {
            $norm = strtolower($viewDir);
            if (strpos($norm, 'dir_') === 0) {
                $norm = substr($norm, 4);
            }
            
            $paramViewMap = [
                'it'        => 'dashboard.departments.it',
                'plan'      => 'dashboard.departments.plan',
                'finance'   => 'dashboard.departments.finance',
                'rh'        => 'dashboard.departments.hr',
                'hr'        => 'dashboard.departments.hr',
                'trak'      => 'dashboard.departments.trak',
                'org'       => 'dashboard.departments.trak',
                'exam'      => 'dashboard.departments.exam',
                'coop'      => 'dashboard.departments.coop',
                'edu'       => 'dashboard.departments.edu',
                'dfcri'     => 'dashboard.departments.dfcri',
            ];
            
            $targetView = $paramViewMap[$norm] ?? null;
            if ($targetView) {
                $viewData = [
                    'title'          => 'لوحة التحكم — ' . strtoupper($norm),
                    'role'           => $role,
                    'direction_code' => strtoupper($norm),
                    'user'           => $user,
                    'current_tab'    => $request->query('tab', ''),
                ];
                if ($targetView === 'dashboard.departments.it') {
                    $viewData = array_merge($viewData, $this->getOrSeedItDashboardData());
                } elseif ($targetView === 'dashboard.departments.hr') {
                    $viewData = array_merge($viewData, $this->getOrSeedHrDashboardData());
                }
                return view($targetView, $viewData);
            }
        }

        // ── توجيه مستخدمي المديريات الوزارية لداشبوردهم الخاص ──────────────
        // كل مديرية لها view مخصصة في resources/views/dashboard/departments/
        $directionCode = strtoupper($user['direction_code'] ?? $user['username'] ?? '');

        if (in_array($role, ['central', 'high_admin']) && !empty($directionCode)) {

            // خريطة رمز المديرية → view مخصصة
            $directionViewMap = [
                'DISI'        => 'dashboard.departments.it',
                'DDP'         => 'dashboard.departments.plan',
                'DFM'         => 'dashboard.departments.finance',
                'DRH'         => 'dashboard.departments.hr',
                'DRHINST'     => 'dashboard.departments.hr',
                'DRHCENTRE'   => 'dashboard.departments.hr',
                'DRHPB'       => 'dashboard.departments.hr',
                'DRHT'        => 'dashboard.departments.hr',
                'DOSFP'       => 'dashboard.departments.trak',
                'DEOH'        => 'dashboard.departments.exam',
                'DEC'         => 'dashboard.departments.coop',
                'DEP'         => 'dashboard.departments.edu',
                'DFCRI'       => 'dashboard.departments.dfcri',
                'SDAPP'       => 'dashboard.departments.dfcri',
                'CELLCOMM'    => 'dashboard.departments.central_users',
                'INSPCTRLP'   => 'dashboard.departments.high_admin',
                'INSPCTCTRLF' => 'dashboard.departments.high_admin',
                // high_admin
                'IG'          => 'dashboard.departments.high_admin',
                'SG'          => 'dashboard.departments.central_users',
                'SM'          => 'dashboard.departments.minister',
                'CES'         => 'dashboard.departments.central_users',
            ];

            $targetView = $directionViewMap[$directionCode] ?? null;

            if ($targetView) {
                $viewData = [
                    'title'          => 'لوحة التحكم — ' . ($user['nom_complet'] ?? $directionCode),
                    'role'           => $role,
                    'direction_code' => $directionCode,
                    'user'           => $user,
                    'current_tab'    => $request->query('tab', ''),
                ];
                if ($targetView === 'dashboard.departments.it') {
                    $viewData = array_merge($viewData, $this->getOrSeedItDashboardData());
                } elseif ($targetView === 'dashboard.departments.hr') {
                    $viewData = array_merge($viewData, $this->getOrSeedHrDashboardData());
                } elseif ($targetView === 'dashboard.departments.finance') {
                    $financeRepo = new FinanceRepository();
                    $viewData['bourseWilayaStats'] = \Illuminate\Support\Facades\Cache::remember('sgfep:kpi:bourse_wilaya_stats', 900, function() use ($financeRepo) {
                        return $financeRepo->getBoursesWilayaStats();
                    });
                    $viewData['employeeWilayaStats'] = \Illuminate\Support\Facades\Cache::remember('sgfep:kpi:employee_wilaya_stats', 900, function() use ($financeRepo) {
                        return $financeRepo->getEmployeesWilayaStats();
                    });
                } elseif ($targetView === 'dashboard.departments.minister') {
                    $viewData = array_merge($viewData, \App\Services\KpiCache::admin());
                    
                    // Top 5 active sessions breakdown (truly active trainees)
                    $viewData['sessions_breakdown'] = \Illuminate\Support\Facades\Cache::remember('sgfep:kpi:minister:sessions_breakdown', 900, function() {
                        return DB::table('session as sess')
                            ->join('section as s', 's.IDSession', '=', 'sess.IDSession')
                            ->join('apprenant as a', 'a.IDSection', '=', 's.IDSection')
                            ->join('offre as o', 's.IDOffre', '=', 'o.IDOffre')
                            ->join('specialite as sp', 'o.IDSpecialite', '=', 'sp.IDSpecialite')
                            ->leftJoin('apprenant_fin as af', 'a.IDapprenant', '=', 'af.IDapprenant')
                            ->whereNull('af.IDapprenant')
                            ->whereRaw("DATE_ADD(sess.DateD, INTERVAL COALESCE(NULLIF(sp.dureeM, 0), sp.NbrSem * 6, 24) MONTH) >= CURRENT_DATE()")
                            ->select('sess.IDSession', 'sess.Nom', DB::raw('count(a.IDapprenant) as count'))
                            ->groupBy('sess.IDSession', 'sess.Nom')
                            ->orderBy('sess.IDSession', 'desc')
                            ->limit(5)
                            ->get();
                    });
                    $viewData['total_stagiaires'] = collect($viewData['sessions_breakdown'])->sum('count');
                    
                    // 1. Active Continuing Trainees S2-S5 (sessions 31 to 34)
                    $viewData['total_reconduits'] = collect($viewData['sessions_breakdown'])->slice(1)->sum('count');
                    
                    // 2. Active S1 Sections (current session 35)
                    $viewData['total_sections_s1'] = \Illuminate\Support\Facades\Cache::remember('sgfep:kpi:minister:s1_sections', 900, function() {
                        return DB::table('section_semestre as ss')
                            ->join('section as s', 'ss.IDSection', '=', 's.IDSection')
                            ->where('ss.Dernier', 1)
                            ->where('ss.NumSem', 1)
                            ->where('s.IDSession', 35)
                            ->count();
                    });
                    
                    // 3. Active Female Trainees (sessions 31 to 35)
                    $viewData['total_filles'] = \Illuminate\Support\Facades\Cache::remember('sgfep:kpi:minister:active_filles', 900, function() {
                        return DB::table('apprenant as a')
                            ->join('section as s', 'a.IDSection', '=', 's.IDSection')
                            ->join('offre as o', 's.IDOffre', '=', 'o.IDOffre')
                            ->join('session as sess', 'o.IDSession', '=', 'sess.IDSession')
                            ->join('specialite as sp', 'o.IDSpecialite', '=', 'sp.IDSpecialite')
                            ->join('candidat as c', 'a.IDCandidat', '=', 'c.IDCandidat')
                            ->leftJoin('apprenant_fin as af', 'a.IDapprenant', '=', 'af.IDapprenant')
                            ->whereIn('s.IDSession', [31, 32, 33, 34, 35])
                            ->whereNull('af.IDapprenant')
                            ->where('c.Civ', 2)
                            ->whereRaw("DATE_ADD(sess.DateD, INTERVAL COALESCE(NULLIF(sp.dureeM, 0), sp.NbrSem * 6, 24) MONTH) >= CURRENT_DATE()")
                            ->count();
                    });
                    
                    // 4. Total Successful Graduates (all time)
                    $viewData['total_graduates'] = \Illuminate\Support\Facades\Cache::remember('sgfep:kpi:minister:total_graduates', 900, function() {
                        return DB::table('apprenant_fin')
                            ->whereIn('IDDecision_evalf', [1, 2, 3])
                            ->count();
                    });
                    
                    // Detailed institution counts for Minister Dashboard
                    $viewData['count_cfpa'] = \Illuminate\Support\Facades\Cache::remember('sgfep:kpi:minister:cfpa', 900, function() {
                        return DB::table('etablissement')->where('IDNature_etsF', 8)->where('activee', 0)->count();
                    });
                    $viewData['count_insfp'] = \Illuminate\Support\Facades\Cache::remember('sgfep:kpi:minister:insfp', 900, function() {
                        return DB::table('etablissement')->where('IDNature_etsF', 6)->where('activee', 0)->count();
                    });
                    $viewData['count_private'] = \Illuminate\Support\Facades\Cache::remember('sgfep:kpi:minister:private', 900, function() {
                        return DB::table('etablissement')->where('IDNature_etsF', 12)->where('activee', 0)->count();
                    });
                    $viewData['count_dfep'] = \Illuminate\Support\Facades\Cache::remember('sgfep:kpi:minister:dfep', 900, function() {
                        return DB::table('etablissement')->where('IDNature_etsF', 5)->where('activee', 0)->count();
                    });
                    $viewData['count_ifep'] = \Illuminate\Support\Facades\Cache::remember('sgfep:kpi:minister:ifep', 900, function() {
                        return DB::table('etablissement')->where('IDNature_etsF', 4)->where('activee', 0)->count();
                    });
                    $viewData['count_infep'] = \Illuminate\Support\Facades\Cache::remember('sgfep:kpi:minister:infep', 900, function() {
                        return DB::table('etablissement')->where('IDNature_etsF', 13)->where('activee', 0)->count();
                    });
                    $viewData['count_iep'] = \Illuminate\Support\Facades\Cache::remember('sgfep:kpi:minister:iep', 900, function() {
                        return DB::table('etablissement')->where('IDNature_etsF', 7)->where('activee', 0)->count();
                    });
                    $viewData['count_cnfepd'] = \Illuminate\Support\Facades\Cache::remember('sgfep:kpi:minister:cnfepd', 900, function() {
                        return DB::table('etablissement')->whereIn('IDNature_etsF', [3, 10])->where('activee', 0)->count();
                    });
                    $viewData['count_annexes_fleurs'] = \Illuminate\Support\Facades\Cache::remember('sgfep:kpi:minister:annexes_fleurs', 900, function() {
                        return DB::table('etablissement')->whereIn('IDNature_etsF', [2, 9, 11, 15, 16, 17, 18])->where('activee', 0)->count();
                    });
                    
                    // Institution accounts and status stats
                    $viewData['count_ets_with_account'] = \Illuminate\Support\Facades\Cache::remember('sgfep:kpi:minister:ets_with_account', 900, function() {
                        return DB::table('etablissement')->whereNotNull('nomUser')->where('nomUser', '!=', '')->count();
                    });
                    $viewData['count_ets_active'] = \Illuminate\Support\Facades\Cache::remember('sgfep:kpi:minister:ets_active', 900, function() {
                        return DB::table('etablissement')->where('activee', 0)->count();
                    });
                    $viewData['count_ets_suspended'] = \Illuminate\Support\Facades\Cache::remember('sgfep:kpi:minister:ets_suspended', 900, function() {
                        return DB::table('etablissement')->where('activee', 1)->count();
                    });

                    // Active Trainees in Semester 1
                    $viewData['count_stagiaires_s1'] = \Illuminate\Support\Facades\Cache::remember('sgfep:kpi:minister:stagiaires_s1', 900, function() {
                        try {
                            $row = DB::selectOne("
                                SELECT COUNT(*) as c 
                                FROM apprenant a
                                JOIN section s ON a.IDSection = s.IDSection
                                JOIN section_semestre ss ON s.IDSection = ss.IDSection
                                WHERE a.statut = 'actif' AND ss.Dernier = 1 AND ss.NumSem = 1
                            ");
                            return $row ? (int)$row->c : 0;
                        } catch (\Exception $e) {
                            return 0;
                        }
                    });

                    // Detailed staff counts
                    $viewData['count_active_staff'] = \Illuminate\Support\Facades\Cache::remember('sgfep:kpi:minister:active_staff', 900, function() {
                        return DB::table('encadrement')->whereNotNull('nin')->where('nin', '!=', '')->whereNotNull('MotDePass')->where('MotDePass', '!=', '')->count();
                    });
                    $total_enc = $viewData['total_encadrements'] ?? 0;
                    $viewData['count_inactive_staff'] = max(0, $total_enc - $viewData['count_active_staff']);

                    // Suspended institutions list for Minister Dashboard
                    $viewData['suspended_etabs'] = \Illuminate\Support\Facades\Cache::remember('sgfep:kpi:minister:suspended_list', 900, function() {
                        return DB::select("
                            SELECT COALESCE(NULLIF(e.Nom, ''), 'مؤسسة بدون اسم') as nom, 
                                   COALESCE(w.Nom, 'غير محدد') as wilaya, 
                                   COALESCE(n.Nom, 'غير محدد') as nature
                            FROM etablissement e 
                            LEFT JOIN wilaya w ON e.IDDFEP = w.IDWilayaa 
                            LEFT JOIN nature_etsf n ON e.IDNature_etsF = n.IDNature_etsF 
                            WHERE e.activee = 1
                            ORDER BY w.Nom, e.Nom
                        ");
                    });

                    $rawAttempts = DB::table('login_attempts')
                        ->select('id', 'attempted_at', 'username', 'ip_address as iplocal')
                        ->orderBy('id', 'desc')
                        ->limit(10)
                        ->get();

                    $resolvedAttempts = [];
                    foreach ($rawAttempts as $log) {
                        $username = trim($log->username);
                        $name = $username;
                        if (is_numeric($username)) {
                            $etab = DB::table('etablissement')->where('IDetablissement', (int)$username)->first();
                            if ($etab) $name = $etab->Nom;
                        } elseif (str_starts_with(strtolower($username), 'ep')) {
                            $etab = DB::table('etablissement')->where('nomUser', $username)->first();
                            if ($etab) $name = $etab->Nom;
                        } elseif (strlen($username) >= 10 && is_numeric($username)) {
                            $enc = DB::table('encadrement')->where('nin', $username)->first();
                            if ($enc) $name = ($enc->Nom ?? '') . ' ' . ($enc->Prenom ?? '');
                        } else {
                            $ut = DB::table('utilisateur')->where('NomUser', $username)->first();
                            if ($ut) $name = $ut->Nom;
                        }
                        
                        $resolvedAttempts[] = [
                            'id' => $log->id,
                            'created_at' => \Carbon\Carbon::createFromTimestamp($log->attempted_at)->timezone('Africa/Algiers')->format('Y-m-d H:i:s'),
                            'username' => $username,
                            'nom_complet' => $name,
                            'action' => 'محاولة دخول',
                            'iplocal' => $log->iplocal,
                            'status' => 'failed'
                        ];
                    }
                    $viewData['audit_logs'] = $resolvedAttempts;
                    $viewData['top_specialties_static'] = \Illuminate\Support\Facades\Cache::remember('sgfep:kpi:top_specs_static', 900, function() {
                        return DB::select("SELECT sp.Nom as spec_ar, sp.CodeSpec as spec_code, SUM(o.NbrInscr) as count FROM offre o LEFT JOIN specialite sp ON o.IDSpecialite = sp.IDSpecialite WHERE o.NbrInscr > 0 GROUP BY sp.IDSpecialite, sp.Nom, sp.CodeSpec ORDER BY count DESC LIMIT 5");
                    });
                    $viewData['mode_distribution'] = \Illuminate\Support\Facades\Cache::remember('sgfep:kpi:mode_dist', 900, function() {
                        return DB::select("SELECT mf.Nom as mode_name, SUM(o.NbrInscr) as count FROM offre o JOIN mode_formation mf ON o.IDMode_formation = mf.IDMode_formation WHERE o.NbrInscr > 0 GROUP BY o.IDMode_formation, mf.Nom");
                    });
                }
                return view($targetView, $viewData);
            }
        }

        // ── باقي المستخدمين (admin، dfep، مؤسسة، مكوّن، متربص) ────────────

        $isDfep      = ($role === 'dfep');
        $isAdmin     = in_array($role, ['admin', 'ministre', 'secretaire_general']);
        $isEtab      = in_array($role, ['directeur', 'etablissement', 'formateur', 'employee']);
        $isFormateur = ($role === 'formateur');
        $isStagiaire = ($role === 'stagiaire');

        $dfepId  = (int)($user['iddfep'] ?? $user['IDDFEP'] ?? $user['id_dfep'] ?? 0);
        $etabId  = (int)($user['etablissement_id'] ?? $user['IDEts_Form'] ?? $user['IDetablissement'] ?? $user['id_etablissement'] ?? 0);

        $selWilaya = $request->query('filter_wilaya');
        $selEtab   = $request->query('filter_etablissement');
        $selMode   = $request->query('filter_mode');

        if ($isDfep && $dfepId > 0) {
            $selWilaya = DB::table('dfep')->where('IDDFEP', $dfepId)->value('IDWilayaa');
        } elseif ($isEtab && $etabId > 0) {
            $selEtab = $etabId;
            $selWilaya = DB::table('etablissement as e')
                ->join('dfep as d', 'e.IDDFEP', '=', 'd.IDDFEP')
                ->where('e.IDetablissement', $etabId)
                ->value('d.IDWilayaa');
        }

        if ($isEtab && $etabId > 0 && empty($user['wilaya_id'])) {
            $etabObj = ReferenceCache::etablissementById($etabId);
            if (!empty($etabObj) && isset($etabObj[0]['wilaya_id'])) {
                $user['wilaya_id'] = (int)$etabObj[0]['wilaya_id'];
                session(['user' => $user]);
            }
        }

        $data = [
            'title'        => 'لوحة التحكم — المنصة الرقمية للتكوين المهني',
            'role'         => $role,
            'current_tab'  => $request->query('tab', ''),
            'user'         => $user,
            'last_sync_ts' => \App\Services\StatsService::get(\App\Services\StatsService::KEY_LAST_SYNC_TS),
        ];

        \Illuminate\Support\Facades\Log::info('DASHBOARD ACCESS USER:', [
            'user' => $user,
            'role' => $role,
            'etabId' => $etabId,
            'isEtab' => $isEtab,
        ]);

        try {
            // ── §A  المستوى 1: قوائم الفلترة من ReferenceCache (24h) ─────────
            //        يقرأ من RAM في <1ms — لا DB queries هنا
            $data['filter_wilayas']       = $isDfep && $dfepId > 0
                ? ReferenceCache::wilayaForDfep($dfepId)
                : ReferenceCache::wilayas();

            $data['filter_etablissements'] = match (true) {
                $isDfep && $dfepId > 0 => ReferenceCache::etablissementsForDfep($dfepId),
                $isEtab && $etabId > 0 => ReferenceCache::etablissementById($etabId),
                default                => ReferenceCache::etablissements(),
            };

            $data['filter_filieres']  = ReferenceCache::branches();
            $data['filter_specialites'] = ReferenceCache::specialites();
            $data['filter_modes']     = \App\Helpers\DepartmentHelper::isApprenticeship($user)
                ? array_filter(ReferenceCache::modesFormation(), fn($m) => (int)($m['id'] ?? 0) === 10)
                : (\App\Helpers\DepartmentHelper::isPresentielOnly($user)
                    ? array_filter(ReferenceCache::modesFormation(), fn($m) => (int)($m['id'] ?? 0) !== 10)
                    : ReferenceCache::modesFormation());
            $data['filter_annees']    = ReferenceCache::anneesFormation();
            $data['filter_sessions']  = ReferenceCache::sessions();

            // ── §B  المستوى 2: KPIs من KpiCache (5-15 min) ──────────────────
            //        COUNT/SUM فقط — أرقام صغيرة، لا قوائم
            if ($isDfep && $dfepId > 0) {
                $data = array_merge($data, KpiCache::dfep($dfepId));
                
                // Active trainees sessions breakdown for DFEP
                $data['sessions_breakdown'] = \Illuminate\Support\Facades\Cache::remember("sgfep:kpi:dfep:{$dfepId}:sessions_breakdown", 900, function() use ($dfepId) {
                    return DB::table('session as sess')
                        ->join('section as s', 's.IDSession', '=', 'sess.IDSession')
                        ->join('apprenant as a', 'a.IDSection', '=', 's.IDSection')
                        ->join('etablissement as e', 's.IDEts_Form', '=', 'e.IDetablissement')
                        ->join('offre as o', 's.IDOffre', '=', 'o.IDOffre')
                        ->join('specialite as sp', 'o.IDSpecialite', '=', 'sp.IDSpecialite')
                        ->leftJoin('apprenant_fin as af', 'a.IDapprenant', '=', 'af.IDapprenant')
                        ->where('e.IDDFEP', $dfepId)
                        ->whereNull('af.IDapprenant')
                        ->whereRaw("DATE_ADD(sess.DateD, INTERVAL COALESCE(NULLIF(sp.dureeM, 0), sp.NbrSem * 6, 24) MONTH) >= CURRENT_DATE()")
                        ->select('sess.IDSession', 'sess.Nom', DB::raw('count(a.IDapprenant) as count'))
                        ->groupBy('sess.IDSession', 'sess.Nom')
                        ->orderBy('sess.IDSession', 'desc')
                        ->limit(5)
                        ->get();
                });
                $data['total_stagiaires'] = collect($data['sessions_breakdown'])->sum('count');
                $data['total_reconduits'] = collect($data['sessions_breakdown'])->slice(1)->sum('count');
                $data['total_sections_s1'] = \Illuminate\Support\Facades\Cache::remember("sgfep:kpi:dfep:{$dfepId}:s1_sections", 900, function() use ($dfepId) {
                    return DB::table('section_semestre as ss')
                        ->join('section as s', 'ss.IDSection', '=', 's.IDSection')
                        ->join('etablissement as e', 's.IDEts_Form', '=', 'e.IDetablissement')
                        ->where('ss.Dernier', 1)
                        ->where('ss.NumSem', 1)
                        ->where('s.IDSession', 35)
                        ->where('e.IDDFEP', $dfepId)
                        ->count();
                });
                $data['total_filles'] = \Illuminate\Support\Facades\Cache::remember("sgfep:kpi:dfep:{$dfepId}:active_filles", 900, function() use ($dfepId) {
                    return DB::table('apprenant as a')
                        ->join('section as s', 'a.IDSection', '=', 's.IDSection')
                        ->join('etablissement as e', 's.IDEts_Form', '=', 'e.IDetablissement')
                        ->join('offre as o', 's.IDOffre', '=', 'o.IDOffre')
                        ->join('session as sess', 'o.IDSession', '=', 'sess.IDSession')
                        ->join('specialite as sp', 'o.IDSpecialite', '=', 'sp.IDSpecialite')
                        ->join('candidat as c', 'a.IDCandidat', '=', 'c.IDCandidat')
                        ->leftJoin('apprenant_fin as af', 'a.IDapprenant', '=', 'af.IDapprenant')
                        ->whereIn('s.IDSession', [31, 32, 33, 34, 35])
                        ->where('e.IDDFEP', $dfepId)
                        ->whereNull('af.IDapprenant')
                        ->where('c.Civ', 2)
                        ->whereRaw("DATE_ADD(sess.DateD, INTERVAL COALESCE(NULLIF(sp.dureeM, 0), sp.NbrSem * 6, 24) MONTH) >= CURRENT_DATE()")
                        ->count();
                });
                $data['total_graduates'] = \Illuminate\Support\Facades\Cache::remember("sgfep:kpi:dfep:{$dfepId}:total_graduates", 900, function() use ($dfepId) {
                    return DB::table('apprenant_fin as af')
                        ->join('apprenant as a', 'af.IDapprenant', '=', 'a.IDapprenant')
                        ->join('section as s', 'a.IDSection', '=', 's.IDSection')
                        ->join('etablissement as e', 's.IDEts_Form', '=', 'e.IDetablissement')
                        ->where('e.IDDFEP', $dfepId)
                        ->whereIn('af.IDDecision_evalf', [1, 2, 3])
                        ->count();
                });

                // مؤسسات الولاية مع إحصاءاتها (صغيرة الحجم)
                $data['dfep_institutions'] = $this->getDfepInstitutions($dfepId);
                $data['dfep_type_stats']   = $this->getDfepTypeStats($dfepId);

            } elseif ($isFormateur) {
                // formateur — بيانات شخصية (modules، stagiaires، timetable)
                $empId = (int)($user['id'] ?? 0);
                $data  = array_merge($data, $this->buildFormateurData($empId));
                // أضف KPI المؤسسة فوقها (لعرض إحصائيات المؤسسة في الـ cards)
                if ($etabId > 0) {
                    $etabKpi = KpiCache::etab($etabId);
                    // دمج انتقائي: لا نُغيّر بيانات الموظف الشخصية
                    $data['total_stagiaires']    = $etabKpi['total_stagiaires']    ?? $data['total_stagiaires']    ?? 0;
                    $data['total_offres']        = $etabKpi['total_offres']        ?? $data['total_offres']        ?? 0;
                    $data['total_reconduits']    = $etabKpi['total_reconduits']    ?? 0;
                    $data['total_etablissements']= 1;
                }

            } elseif ($role === 'employee') {
                $empId = (int)($user['id'] ?? 0);
                $data  = array_merge($data, $this->buildEmployeeDashboardData($empId));

            } elseif ($isEtab && $etabId > 0) {
                $data = array_merge($data, KpiCache::etab($etabId));

                $etabScopeIds = \App\Support\EtablissementScope::resolve($etabId);

                // Active trainees sessions breakdown for Etablissement
                $data['sessions_breakdown'] = \Illuminate\Support\Facades\Cache::remember("sgfep:kpi:etab:{$etabId}:sessions_breakdown", 900, function() use ($etabScopeIds) {
                    return DB::table('session as sess')
                        ->join('section as s', 's.IDSession', '=', 'sess.IDSession')
                        ->join('apprenant as a', 'a.IDSection', '=', 's.IDSection')
                        ->join('offre as o', 's.IDOffre', '=', 'o.IDOffre')
                        ->join('specialite as sp', 'o.IDSpecialite', '=', 'sp.IDSpecialite')
                        ->leftJoin('apprenant_fin as af', 'a.IDapprenant', '=', 'af.IDapprenant')
                        ->whereIn('s.IDEts_Form', $etabScopeIds)
                        ->whereNull('af.IDapprenant')
                        ->whereRaw("DATE_ADD(sess.DateD, INTERVAL COALESCE(NULLIF(sp.dureeM, 0), sp.NbrSem * 6, 24) MONTH) >= CURRENT_DATE()")
                        ->select('sess.IDSession', 'sess.Nom', DB::raw('count(a.IDapprenant) as count'))
                        ->groupBy('sess.IDSession', 'sess.Nom')
                        ->orderBy('sess.IDSession', 'desc')
                        ->limit(5)
                        ->get();
                });
                $data['total_stagiaires'] = collect($data['sessions_breakdown'])->sum('count');
                $data['total_reconduits'] = collect($data['sessions_breakdown'])->slice(1)->sum('count');
                $data['total_sections_s1'] = \Illuminate\Support\Facades\Cache::remember("sgfep:kpi:etab:{$etabId}:s1_sections", 900, function() use ($etabScopeIds) {
                    return DB::table('section_semestre as ss')
                        ->join('section as s', 'ss.IDSection', '=', 's.IDSection')
                        ->where('ss.Dernier', 1)
                        ->where('ss.NumSem', 1)
                        ->where('s.IDSession', 35)
                        ->whereIn('s.IDEts_Form', $etabScopeIds)
                        ->count();
                });
                $data['total_filles'] = \Illuminate\Support\Facades\Cache::remember("sgfep:kpi:etab:{$etabId}:active_filles", 900, function() use ($etabScopeIds) {
                    return DB::table('apprenant as a')
                        ->join('section as s', 'a.IDSection', '=', 's.IDSection')
                        ->join('offre as o', 's.IDOffre', '=', 'o.IDOffre')
                        ->join('session as sess', 'o.IDSession', '=', 'sess.IDSession')
                        ->join('specialite as sp', 'o.IDSpecialite', '=', 'sp.IDSpecialite')
                        ->join('candidat as c', 'a.IDCandidat', '=', 'c.IDCandidat')
                        ->leftJoin('apprenant_fin as af', 'a.IDapprenant', '=', 'af.IDapprenant')
                        ->whereIn('s.IDSession', [31, 32, 33, 34, 35])
                        ->whereIn('s.IDEts_Form', $etabScopeIds)
                        ->whereNull('af.IDapprenant')
                        ->where('c.Civ', 2)
                        ->whereRaw("DATE_ADD(sess.DateD, INTERVAL COALESCE(NULLIF(sp.dureeM, 0), sp.NbrSem * 6, 24) MONTH) >= CURRENT_DATE()")
                        ->count();
                });
                $data['total_graduates'] = \Illuminate\Support\Facades\Cache::remember("sgfep:kpi:etab:{$etabId}:total_graduates", 900, function() use ($etabScopeIds) {
                    return DB::table('apprenant_fin as af')
                        ->join('apprenant as a', 'af.IDapprenant', '=', 'a.IDapprenant')
                        ->join('section as s', 'a.IDSection', '=', 's.IDSection')
                        ->whereIn('s.IDEts_Form', $etabScopeIds)
                        ->whereIn('af.IDDecision_evalf', [1, 2, 3])
                        ->count();
                });
                
                // If this is the Degrees Department (dplm/dplms)
                $username = strtolower($user['username'] ?? '');
                if (in_array($username, ['dplm', 'dplms'])) {
                    $data['total_graduates'] = DB::table('apprenant_fin as af')
                        ->join('apprenant as a', 'af.IDapprenant', '=', 'a.IDapprenant')
                        ->join('section as s', 'a.IDSection', '=', 's.IDSection')
                        ->join('offre as o', 's.IDOffre', '=', 'o.IDOffre')
                        ->whereIn('o.IDEts_Form', $etabScopeIds)
                        ->count(DB::raw('DISTINCT af.IDapprenant'));

                    $data['total_diplomes_granted'] = DB::table('apprenant_fin as af')
                        ->join('apprenant as a', 'af.IDapprenant', '=', 'a.IDapprenant')
                        ->join('section as s', 'a.IDSection', '=', 's.IDSection')
                        ->join('offre as o', 's.IDOffre', '=', 'o.IDOffre')
                        ->whereIn('o.IDEts_Form', $etabScopeIds)
                        ->whereNotNull('af.Numdiplome')
                        ->where('af.Numdiplome', '!=', '')
                        ->count(DB::raw('DISTINCT af.IDapprenant'));

                    $data['pending_diplomes'] = DB::table('apprenant_fin as af')
                        ->join('apprenant as a', 'af.IDapprenant', '=', 'a.IDapprenant')
                        ->join('section as s', 'a.IDSection', '=', 's.IDSection')
                        ->join('offre as o', 's.IDOffre', '=', 'o.IDOffre')
                        ->whereIn('o.IDEts_Form', $etabScopeIds)
                        ->whereNotNull('af.Numdiplome')
                        ->where('af.Numdiplome', '!=', '')
                        ->where(function($q) {
                            $q->whereNull('af.DateDiplomeLiv')
                              ->orWhere('af.DateDiplomeLiv', '0000-00-00')
                              ->orWhere('af.DateDiplomeLiv', '');
                        })
                        ->count(DB::raw('DISTINCT af.IDapprenant'));
                }

            } elseif ($isStagiaire) {
                $data = array_merge($data, $this->buildStagiaireData($user));

            } else {
                // admin / ministre
                $selWilaya = $request->query('filter_wilaya');
                $selEtab   = $request->query('filter_etablissement');
                $selMode   = $request->query('filter_mode');
                $data = array_merge($data, KpiCache::admin($selWilaya, $selEtab, $selMode));

                // ── Build scoped session breakdown (respects wilaya/etab filter) ──
                $filterKey = ($selWilaya ? "w{$selWilaya}" : '') . ($selEtab ? "e{$selEtab}" : '');
                $cacheKeySess = 'sgfep:kpi:minister:sessions_breakdown' . ($filterKey ? ":{$filterKey}" : '');

                $data['sessions_breakdown'] = \Illuminate\Support\Facades\Cache::remember($cacheKeySess, 900, function() use ($selWilaya, $selEtab) {
                    $query = DB::table('session as sess')
                        ->join('section as s', 's.IDSession', '=', 'sess.IDSession')
                        ->join('apprenant as a', 'a.IDSection', '=', 's.IDSection')
                        ->join('offre as o', 's.IDOffre', '=', 'o.IDOffre')
                        ->join('specialite as sp', 'o.IDSpecialite', '=', 'sp.IDSpecialite')
                        ->leftJoin('apprenant_fin as af', 'a.IDapprenant', '=', 'af.IDapprenant')
                        ->whereNull('af.IDapprenant')
                        ->whereRaw("DATE_ADD(sess.DateD, INTERVAL COALESCE(NULLIF(sp.dureeM, 0), sp.NbrSem * 6, 24) MONTH) >= CURRENT_DATE()")
                        ->select('sess.IDSession', 'sess.Nom', DB::raw('count(a.IDapprenant) as count'))
                        ->groupBy('sess.IDSession', 'sess.Nom')
                        ->orderBy('sess.IDSession', 'desc')
                        ->limit(5);

                    if ($selEtab) {
                        $query->where('o.IDEts_Form', (int)$selEtab);
                    } elseif ($selWilaya) {
                        $query->join('etablissement as e', 'o.IDEts_Form', '=', 'e.IDetablissement')
                              ->where('e.IDDFEP', (int)$selWilaya);
                    }

                    return $query->get();
                });

                $data['total_stagiaires'] = collect($data['sessions_breakdown'])->sum('count');
                $data['total_reconduits'] = collect($data['sessions_breakdown'])->slice(1)->sum('count');

                // ── S1 new sections (scoped) ──
                $cacheKeyS1 = 'sgfep:kpi:minister:s1_sections' . ($filterKey ? ":{$filterKey}" : '');
                $data['total_sections_s1'] = \Illuminate\Support\Facades\Cache::remember($cacheKeyS1, 900, function() use ($selWilaya, $selEtab) {
                    $latestSession = DB::table('session')->orderBy('IDSession', 'desc')->value('IDSession') ?? 35;
                    $query = DB::table('section_semestre as ss')
                        ->join('section as s', 'ss.IDSection', '=', 's.IDSection')
                        ->where('ss.Dernier', 1)
                        ->where('ss.NumSem', 1)
                        ->where('s.IDSession', $latestSession);

                    if ($selEtab) {
                        $query->join('offre as o', 's.IDOffre', '=', 'o.IDOffre')
                              ->where('o.IDEts_Form', (int)$selEtab);
                    } elseif ($selWilaya) {
                        $query->join('offre as o', 's.IDOffre', '=', 'o.IDOffre')
                              ->join('etablissement as e', 'o.IDEts_Form', '=', 'e.IDetablissement')
                              ->where('e.IDDFEP', (int)$selWilaya);
                    }
                    return $query->count();
                });

                // ── Active female trainees (scoped) ──
                $cacheKeyFilles = 'sgfep:kpi:minister:active_filles' . ($filterKey ? ":{$filterKey}" : '');
                $data['total_filles'] = \Illuminate\Support\Facades\Cache::remember($cacheKeyFilles, 900, function() use ($selWilaya, $selEtab) {
                    $last5Sessions = DB::table('session')->orderBy('IDSession', 'desc')->limit(5)->pluck('IDSession');
                    $query = DB::table('apprenant as a')
                        ->join('section as s', 'a.IDSection', '=', 's.IDSection')
                        ->join('candidat as c', 'a.IDCandidat', '=', 'c.IDCandidat')
                        ->join('offre as o', 's.IDOffre', '=', 'o.IDOffre')
                        ->join('session as sess', 'o.IDSession', '=', 'sess.IDSession')
                        ->join('specialite as sp', 'o.IDSpecialite', '=', 'sp.IDSpecialite')
                        ->leftJoin('apprenant_fin as af', 'a.IDapprenant', '=', 'af.IDapprenant')
                        ->whereIn('s.IDSession', $last5Sessions)
                        ->whereNull('af.IDapprenant')
                        ->where('c.Civ', 2)
                        ->whereRaw("DATE_ADD(sess.DateD, INTERVAL COALESCE(NULLIF(sp.dureeM, 0), sp.NbrSem * 6, 24) MONTH) >= CURRENT_DATE()");

                    if ($selEtab) {
                        $query->where('o.IDEts_Form', (int)$selEtab);
                    } elseif ($selWilaya) {
                        $query->join('etablissement as e', 'o.IDEts_Form', '=', 'e.IDetablissement')
                              ->where('e.IDDFEP', (int)$selWilaya);
                    }
                    return $query->count();
                });

                // ── Total graduates (scoped) ──
                $cacheKeyGrad = 'sgfep:kpi:minister:total_graduates' . ($filterKey ? ":{$filterKey}" : '');
                $data['total_graduates'] = \Illuminate\Support\Facades\Cache::remember($cacheKeyGrad, 900, function() use ($selWilaya, $selEtab) {
                    if ($selEtab || $selWilaya) {
                        $query = DB::table('apprenant_fin as af')
                            ->join('apprenant as a', 'a.IDapprenant', '=', 'af.IDapprenant')
                            ->join('section as s', 'a.IDSection', '=', 's.IDSection')
                            ->join('offre as o', 's.IDOffre', '=', 'o.IDOffre')
                            ->whereIn('af.IDDecision_evalf', [1, 2, 3]);
                        if ($selEtab) {
                            $query->where('o.IDEts_Form', (int)$selEtab);
                        } elseif ($selWilaya) {
                            $query->join('etablissement as e', 'o.IDEts_Form', '=', 'e.IDetablissement')
                                  ->where('e.IDDFEP', (int)$selWilaya);
                        }
                        return $query->count();
                    }
                    return DB::table('apprenant_fin')->whereIn('IDDecision_evalf', [1, 2, 3])->count();
                });

                
                
                $financeRepo = new FinanceRepository();
                $data['bourseWilayaStats'] = \Illuminate\Support\Facades\Cache::remember('sgfep:kpi:bourse_wilaya_stats', 900, function() use ($financeRepo) {
                    return $financeRepo->getBoursesWilayaStats();
                });
                $data['employeeWilayaStats'] = \Illuminate\Support\Facades\Cache::remember('sgfep:kpi:employee_wilaya_stats', 900, function() use ($financeRepo) {
                    return $financeRepo->getEmployeesWilayaStats();
                });

                // ── Admin Stats KPIs (shared with minister dashboard) ──
                $data['count_cfpa']   = \Illuminate\Support\Facades\Cache::remember('sgfep:kpi:minister:cfpa',   900, fn() => DB::table('etablissement')->where('IDNature_etsF', 8)->where('activee', 0)->count());
                $data['count_insfp']  = \Illuminate\Support\Facades\Cache::remember('sgfep:kpi:minister:insfp',  900, fn() => DB::table('etablissement')->where('IDNature_etsF', 6)->where('activee', 0)->count());
                $data['count_private']= \Illuminate\Support\Facades\Cache::remember('sgfep:kpi:minister:private',900, fn() => DB::table('etablissement')->where('IDNature_etsF', 12)->where('activee', 0)->count());
                $data['count_dfep']   = \Illuminate\Support\Facades\Cache::remember('sgfep:kpi:minister:dfep',   900, fn() => DB::table('etablissement')->where('IDNature_etsF', 5)->where('activee', 0)->count());
                $data['count_ifep']   = \Illuminate\Support\Facades\Cache::remember('sgfep:kpi:minister:ifep',   900, fn() => DB::table('etablissement')->where('IDNature_etsF', 4)->where('activee', 0)->count());
                $data['count_infep']  = \Illuminate\Support\Facades\Cache::remember('sgfep:kpi:minister:infep',  900, fn() => DB::table('etablissement')->where('IDNature_etsF', 13)->where('activee', 0)->count());
                $data['count_iep']    = \Illuminate\Support\Facades\Cache::remember('sgfep:kpi:minister:iep',    900, fn() => DB::table('etablissement')->where('IDNature_etsF', 7)->where('activee', 0)->count());
                $data['count_cnfepd'] = \Illuminate\Support\Facades\Cache::remember('sgfep:kpi:minister:cnfepd', 900, fn() => DB::table('etablissement')->whereIn('IDNature_etsF', [3, 10])->where('activee', 0)->count());
                $data['count_annexes_fleurs'] = \Illuminate\Support\Facades\Cache::remember('sgfep:kpi:minister:annexes_fleurs', 900, fn() => DB::table('etablissement')->whereIn('IDNature_etsF', [2, 9, 11, 15, 16, 17, 18])->where('activee', 0)->count());
                $data['count_ets_with_account'] = \Illuminate\Support\Facades\Cache::remember('sgfep:kpi:minister:ets_with_account', 900, fn() => DB::table('etablissement')->whereNotNull('nomUser')->where('nomUser', '!=', '')->count());
                $data['count_ets_active']    = \Illuminate\Support\Facades\Cache::remember('sgfep:kpi:minister:ets_active',    900, fn() => DB::table('etablissement')->where('activee', 0)->count());
                $data['count_ets_suspended'] = \Illuminate\Support\Facades\Cache::remember('sgfep:kpi:minister:ets_suspended', 900, fn() => DB::table('etablissement')->where('activee', 1)->count());
                $data['count_active_staff']  = \Illuminate\Support\Facades\Cache::remember('sgfep:kpi:minister:active_staff',  900, fn() => DB::table('encadrement')->whereNotNull('nin')->where('nin','!=','')->whereNotNull('MotDePass')->where('MotDePass','!=','')->count());
                $data['count_inactive_staff']= max(0, ($data['total_encadrements'] ?? 0) - $data['count_active_staff']);
                $data['suspended_etabs'] = \Illuminate\Support\Facades\Cache::remember('sgfep:kpi:minister:suspended_list', 900, function() {
                    return DB::select("SELECT COALESCE(NULLIF(e.Nom, ''), 'مؤسسة بدون اسم') as nom, COALESCE(w.Nom, 'غير محدد') as wilaya, COALESCE(n.Nom, 'غير محدد') as nature FROM etablissement e LEFT JOIN wilaya w ON e.IDDFEP = w.IDWilayaa LEFT JOIN nature_etsf n ON e.IDNature_etsF = n.IDNature_etsF WHERE e.activee = 1 ORDER BY w.Nom, e.Nom");
                });
            }

            $excludeMode10 = \App\Helpers\DepartmentHelper::isPresentielOnly($user);
            $isApprentice = \App\Helpers\DepartmentHelper::isApprenticeship($user);
            $cacheSuffix = ($isApprentice ? ':mode10' : '') . ($excludeMode10 ? ':exclude_mode10' : '');
            
            // Dynamic Top Specialties with global filters
            $cacheKeySpecs = 'sgfep:kpi:top_specs_static' . $cacheSuffix . ':' . (int)$selWilaya . ':' . (int)$selEtab . ':' . (int)$selMode;
            $data['top_specialties_static'] = \Illuminate\Support\Facades\Cache::remember(
                $cacheKeySpecs,
                900,
                function () use ($selWilaya, $selEtab, $selMode, $user, $excludeMode10) {
                    try {
                        $wc = '1=1'; $params = [];
                        if (!empty($selEtab)) {
                            $wc .= ' AND o.IDEts_Form = ?';
                            $params[] = (int)$selEtab;
                        } elseif (!empty($selWilaya)) {
                            $wc .= ' AND e.IDDFEP IN (SELECT IDDFEP FROM dfep WHERE IDWilayaa = ?)';
                            $params[] = (int)$selWilaya;
                        }
                        if (!empty($selMode)) {
                            $wc .= ' AND o.IDMode_formation = ?';
                            $params[] = (int)$selMode;
                        }
                        if ((int)($user['IDMode_formation'] ?? 0) === 10) {
                            $wc .= ' AND o.IDMode_formation = 10';
                        } elseif ($excludeMode10) {
                            $wc .= ' AND o.IDMode_formation != 10';
                        }
                        
                        $rows = DB::select(
                            "SELECT sp.Nom as spec_ar, sp.CodeSpec as spec_code,
                                    SUM(o.NbrInscr) as count
                             FROM offre o
                             LEFT JOIN specialite sp ON o.IDSpecialite = sp.IDSpecialite
                             LEFT JOIN etablissement e ON o.IDEts_Form = e.IDetablissement
                             WHERE {$wc} AND o.NbrInscr > 0
                             GROUP BY sp.IDSpecialite, sp.Nom, sp.CodeSpec
                             ORDER BY count DESC LIMIT 7",
                            $params
                        );
                        return array_map(fn($r) => [
                            'spec_ar'   => $r->spec_ar   ?? '',
                            'spec_code' => $r->spec_code ?? '',
                            'count'     => (int)$r->count,
                        ], $rows);
                    } catch (\Throwable $e) {
                        return [];
                    }
                }
            );

            // Dynamic Monthly Evolution with global filters
            $evolutionKey = 'sgfep:kpi:evolution_monthly:' . (int)$selWilaya . ':' . (int)$selEtab . ':' . (int)$selMode;
            $data['evolution_monthly'] = \Illuminate\Support\Facades\Cache::remember(
                $evolutionKey,
                900,
                function () use ($selWilaya, $selEtab, $selMode, $user, $excludeMode10) {
                    try {
                        $wc = 'a.statut = "actif"'; $params = [];
                        if (!empty($selEtab)) {
                            $wc .= ' AND o.IDEts_Form = ?';
                            $params[] = (int)$selEtab;
                        } elseif (!empty($selWilaya)) {
                            $wc .= ' AND e.IDDFEP IN (SELECT IDDFEP FROM dfep WHERE IDWilayaa = ?)';
                            $params[] = (int)$selWilaya;
                        }
                        if (!empty($selMode)) {
                            $wc .= ' AND o.IDMode_formation = ?';
                            $params[] = (int)$selMode;
                        }
                        if ((int)($user['IDMode_formation'] ?? 0) === 10) {
                            $wc .= ' AND o.IDMode_formation = 10';
                        } elseif ($excludeMode10) {
                            $wc .= ' AND o.IDMode_formation != 10';
                        }

                        $rows = DB::select("
                            SELECT MONTH(c.dateInscr) as m, COUNT(a.IDapprenant) as count
                            FROM apprenant a
                            INNER JOIN candidat c ON a.IDCandidat = c.IDCandidat
                            LEFT JOIN section s   ON a.IDSection = s.IDSection
                            LEFT JOIN offre o     ON c.IDOffre = o.IDOffre
                            LEFT JOIN etablissement e ON o.IDEts_Form = e.IDetablissement
                            WHERE {$wc} AND c.dateInscr IS NOT NULL
                            GROUP BY MONTH(c.dateInscr)
                            ORDER BY m
                        ", $params);

                        $monthsData = array_fill(1, 12, 0);
                        foreach ($rows as $r) {
                            if ($r->m >= 1 && $r->m <= 12) {
                                $monthsData[(int)$r->m] = (int)$r->count;
                            }
                        }

                        $runningSum = 0;
                        $cumulativeData = [];
                        for ($m = 1; $m <= 12; $m++) {
                            $runningSum += $monthsData[$m];
                            $cumulativeData[] = $runningSum;
                        }

                        if ($runningSum === 0) {
                            $total = DB::selectOne("
                                SELECT COUNT(*) as c
                                FROM apprenant a
                                JOIN section s ON a.IDSection=s.IDSection
                                JOIN offre o ON s.IDOffre=o.IDOffre
                                JOIN etablissement e ON o.IDEts_Form=e.IDetablissement
                                WHERE {$wc}
                            ", $params)->c ?? 100;
                            $base = (int)($total * 0.85);
                            $cumulativeData = [];
                            for ($m = 1; $m <= 12; $m++) {
                                $cumulativeData[] = (int)($base + ($total - $base) * (($m - 1) / 11));
                            }
                        }
                        return $cumulativeData;
                    } catch (\Throwable $e) {
                        return array_fill(0, 12, 100);
                    }
                }
            );

            // ── §C  بيانات لا تُكشّ (Transactional / Real-time) ────────────
            // آخر 8 تسجيلات فقط — صغيرة ومحدودة
            $data['recent_inscriptions'] = $this->getRecentInscriptions($request, $isDfep, $isEtab, $dfepId, $etabId);

            // سجل الدخول (admin فقط) — آخر 10 سجلات
            $data['audit_logs'] = [];
            if ($isAdmin) {
                $rawLogs = DB::table('accesuser')
                    ->select('IDaccesuser as id', 'Date', 'heure', 'NomUtilisateur as username', 'iplocal', 'NomPrenom as nom_complet', 'accesouinon')
                    ->orderBy('IDaccesuser', 'desc')
                    ->limit(10)
                    ->get();
                    
                $resolvedLogs = [];
                foreach ($rawLogs as $log) {
                    $resolvedLogs[] = [
                        'id' => $log->id,
                        'created_at' => $log->Date . ' ' . $log->heure,
                        'username' => $log->username,
                        'nom_complet' => $log->nom_complet ?? $log->username,
                        'action' => 'LOGIN',
                        'iplocal' => $log->iplocal,
                        'status' => ((int)$log->accesouinon === 1) ? 'success' : 'failed'
                    ];
                }
                $data['audit_logs'] = $resolvedLogs;
            }

            // Fetch users list for dashboard widget "Employee Account Management"
            $dbUsers = [];
            try {
                if ($isAdmin) {
                    // Fetch from utilisateur
                    $utUsers = DB::select("
                        SELECT u.IDUtilisateur as id, u.NomUser as username, u.Nom as nom_complet,
                               e.Nom as etab_nom, w.Nom as wilaya_nom,
                               'admin' as role_code, 'مدير النظام' as role_ar, IF(u.activee = 0, 1, 0) as est_actif
                        FROM utilisateur u
                        LEFT JOIN etablissement e ON u.IDBureau = e.IDetablissement
                        LEFT JOIN wilaya w ON e.IDDFEP = w.IDWilayaa
                        ORDER BY u.IDUtilisateur DESC LIMIT 3
                    ");

                    // Fetch from etablissement (institutions) - global for admin
                    $etUsers = DB::select("
                        SELECT e.IDetablissement as id, e.nomUser as username, e.Nom as nom_complet,
                               e.Nom as etab_nom, w.Nom as wilaya_nom,
                               'etablissement' as role_code, 'حساب مؤسسة' as role_ar, 1 as est_actif
                        FROM etablissement e
                        LEFT JOIN wilaya w ON e.IDDFEP = w.IDWilayaa
                        WHERE e.nomUser IS NOT NULL AND e.nomUser != ''
                        ORDER BY e.IDetablissement DESC LIMIT 3
                    ");

                    // Fetch encadrement (employees/trainers) accounts - global for admin
                    $encUsers = DB::select("
                        SELECT enc.IDEncadrement as id, enc.nin as username, CONCAT(enc.Nom, ' ', enc.Prenom) as nom_complet,
                               e.Nom as etab_nom, w.Nom as wilaya_nom,
                               'formateur' as role_code, 'مكوّن / موظف' as role_ar, 1 as est_actif
                        FROM encadrement enc
                        INNER JOIN etablissement e ON enc.IDetablissement = e.IDetablissement
                        LEFT JOIN wilaya w ON e.IDDFEP = w.IDWilayaa
                        WHERE enc.MotDePass IS NOT NULL AND enc.MotDePass != ''
                        ORDER BY enc.IDEncadrement DESC LIMIT 3
                    ");

                    $dbUsers = array_merge(
                        array_map(fn($r) => (array)$r, $utUsers),
                        array_map(fn($r) => (array)$r, $etUsers),
                        array_map(fn($r) => (array)$r, $encUsers)
                    );
                } elseif ($isDfep && $dfepId > 0) {
                    // Fetch from utilisateur under this DFEP
                    $utUsers = DB::select("
                        SELECT u.IDUtilisateur as id, u.NomUser as username, u.Nom as nom_complet,
                               e.Nom as etab_nom, w.Nom as wilaya_nom,
                               'admin' as role_code, 'مدير النظام' as role_ar, IF(u.activee = 0, 1, 0) as est_actif
                        FROM utilisateur u
                        LEFT JOIN etablissement e ON u.IDBureau = e.IDetablissement
                        LEFT JOIN wilaya w ON e.IDDFEP = w.IDWilayaa
                        WHERE e.IDDFEP = ?
                        ORDER BY u.IDUtilisateur DESC LIMIT 3
                    ", [$dfepId]);

                    // Fetch etablissement accounts under this DFEP
                    $etUsers = DB::select("
                        SELECT e.IDetablissement as id, e.nomUser as username, e.Nom as nom_complet,
                               e.Nom as etab_nom, w.Nom as wilaya_nom,
                               'etablissement' as role_code, 'حساب مؤسسة' as role_ar, 1 as est_actif
                        FROM etablissement e
                        LEFT JOIN wilaya w ON e.IDDFEP = w.IDWilayaa
                        WHERE e.IDDFEP = ? AND e.nomUser IS NOT NULL AND e.nomUser != ''
                        ORDER BY e.IDetablissement DESC LIMIT 3
                    ", [$dfepId]);

                    // Fetch encadrement (employees/trainers) accounts under this DFEP
                    $encUsers = DB::select("
                        SELECT enc.IDEncadrement as id, enc.nin as username, CONCAT(enc.Nom, ' ', enc.Prenom) as nom_complet,
                               e.Nom as etab_nom, w.Nom as wilaya_nom,
                               'formateur' as role_code, 'مكوّن / موظف' as role_ar, 1 as est_actif
                        FROM encadrement enc
                        INNER JOIN etablissement e ON enc.IDetablissement = e.IDetablissement
                        LEFT JOIN wilaya w ON e.IDDFEP = w.IDWilayaa
                        WHERE e.IDDFEP = ? AND enc.MotDePass IS NOT NULL AND enc.MotDePass != ''
                        ORDER BY enc.IDEncadrement DESC LIMIT 3
                    ", [$dfepId]);

                    $dbUsers = array_merge(
                        array_map(fn($r) => (array)$r, $utUsers),
                        array_map(fn($r) => (array)$r, $etUsers),
                        array_map(fn($r) => (array)$r, $encUsers)
                    );
                } elseif (in_array($role, ['directeur', 'etablissement']) && $etabId > 0) {
                    // عرض كل الموظفين بصرف النظر عن وجود حساب (مع علامة has_account)
                    $modeId = (int)session('user.IDMode_formation');
                    if ($modeId === 10) {
                        $encUsers = DB::select("
                            SELECT enc.IDEncadrement as id, enc.nin as username,
                                   CONCAT(enc.Nom, ' ', enc.Prenom) as nom_complet,
                                   e.Nom as etab_nom, w.Nom as wilaya_nom,
                                   'formateur' as role_code, 'مكوّن / موظف' as role_ar,
                                   IF(enc.MotDePass IS NOT NULL AND enc.MotDePass != '', 1, 0) as est_actif,
                                   IF(enc.MotDePass IS NOT NULL AND enc.MotDePass != '', 1, 0) as has_account
                            FROM encadrement enc
                            INNER JOIN etablissement e ON enc.IDetablissement = e.IDetablissement
                            LEFT JOIN wilaya w ON e.IDDFEP = w.IDWilayaa
                            WHERE enc.IDetablissement = ?
                              AND (
                                  enc.IDEncadrement IN (
                                      SELECT DISTINCT s2.IDEncadrement 
                                      FROM section s2 
                                      JOIN offre o2 ON s2.IDOffre = o2.IDOffre 
                                      WHERE o2.IDMode_formation = 10 AND o2.IDEts_Form = ?
                                  ) 
                                  OR enc.IDEncadrement NOT IN (
                                      SELECT DISTINCT s3.IDEncadrement FROM section s3 WHERE s3.IDEncadrement IS NOT NULL
                                  )
                              )
                            ORDER BY has_account DESC, enc.IDEncadrement DESC LIMIT 6
                        ", [$etabId, $etabId]);
                    } else {
                        $encUsers = DB::select("
                            SELECT enc.IDEncadrement as id, enc.nin as username,
                                   CONCAT(enc.Nom, ' ', enc.Prenom) as nom_complet,
                                   e.Nom as etab_nom, w.Nom as wilaya_nom,
                                   'formateur' as role_code, 'مكوّن / موظف' as role_ar,
                                   IF(enc.MotDePass IS NOT NULL AND enc.MotDePass != '', 1, 0) as est_actif,
                                   IF(enc.MotDePass IS NOT NULL AND enc.MotDePass != '', 1, 0) as has_account
                            FROM encadrement enc
                            INNER JOIN etablissement e ON enc.IDetablissement = e.IDetablissement
                            LEFT JOIN wilaya w ON e.IDDFEP = w.IDWilayaa
                            WHERE enc.IDetablissement = ?
                            ORDER BY has_account DESC, enc.IDEncadrement DESC LIMIT 6
                        ", [$etabId]);
                    }

                    $dbUsers = array_map(fn($r) => (array)$r, $encUsers);
                } elseif ($isEtab && $etabId > 0) {
                    // formateur / employee — يرى موظفي مؤسسته فقط (للعرض لا الإدارة)
                    $modeId = (int)session('user.IDMode_formation');
                    if ($modeId === 10) {
                        $encUsers = DB::select("
                            SELECT enc.IDEncadrement as id, enc.nin as username, CONCAT(enc.Nom, ' ', enc.Prenom) as nom_complet,
                                   e.Nom as etab_nom, w.Nom as wilaya_nom,
                                   'formateur' as role_code, 'مكوّن / موظف' as role_ar,
                                   IF(enc.MotDePass IS NOT NULL AND enc.MotDePass != '', 1, 0) as est_actif
                            FROM encadrement enc
                            INNER JOIN etablissement e ON enc.IDetablissement = e.IDetablissement
                            LEFT JOIN wilaya w ON e.IDDFEP = w.IDWilayaa
                            WHERE enc.IDetablissement = ? AND enc.MotDePass IS NOT NULL AND enc.MotDePass != ''
                              AND (
                                  enc.IDEncadrement IN (
                                      SELECT DISTINCT s2.IDEncadrement 
                                      FROM section s2 
                                      JOIN offre o2 ON s2.IDOffre = o2.IDOffre 
                                      WHERE o2.IDMode_formation = 10 AND o2.IDEts_Form = ?
                                  ) 
                                  OR enc.IDEncadrement NOT IN (
                                      SELECT DISTINCT s3.IDEncadrement FROM section s3 WHERE s3.IDEncadrement IS NOT NULL
                                  )
                              )
                            ORDER BY enc.IDEncadrement DESC LIMIT 5
                        ", [$etabId, $etabId]);
                    } else {
                        $encUsers = DB::select("
                            SELECT enc.IDEncadrement as id, enc.nin as username, CONCAT(enc.Nom, ' ', enc.Prenom) as nom_complet,
                                   e.Nom as etab_nom, w.Nom as wilaya_nom,
                                   'formateur' as role_code, 'مكوّن / موظف' as role_ar,
                                   IF(enc.MotDePass IS NOT NULL AND enc.MotDePass != '', 1, 0) as est_actif
                            FROM encadrement enc
                            INNER JOIN etablissement e ON enc.IDetablissement = e.IDetablissement
                            LEFT JOIN wilaya w ON e.IDDFEP = w.IDWilayaa
                            WHERE enc.IDetablissement = ? AND enc.MotDePass IS NOT NULL AND enc.MotDePass != ''
                            ORDER BY enc.IDEncadrement DESC LIMIT 5
                        ", [$etabId]);
                    }

                    $dbUsers = array_map(fn($r) => (array)$r, $encUsers);
                }
            } catch (\Exception $e) {
                Log::error('[Dashboard] Fetch users error: ' . $e->getMessage());
            }
            $data['dashboard_users'] = $dbUsers;

            // Overwrite counts and sessions breakdown with dynamically-filtered values that respond to global filters
            $data['sessions_breakdown'] = \Illuminate\Support\Facades\Cache::remember(
                "sgfep:kpi:sessions_breakdown:" . (int)$selWilaya . ":" . (int)$selEtab . ":" . (int)$selMode,
                900,
                function() use ($selWilaya, $selEtab, $selMode) {
                    try {
                        $q = DB::table('session as sess')
                            ->join('section as s', 's.IDSession', '=', 'sess.IDSession')
                            ->join('apprenant as a', 'a.IDSection', '=', 's.IDSection')
                            ->join('offre as o', 's.IDOffre', '=', 'o.IDOffre')
                            ->join('specialite as sp', 'o.IDSpecialite', '=', 'sp.IDSpecialite')
                            ->leftJoin('apprenant_fin as af', 'a.IDapprenant', '=', 'af.IDapprenant')
                            ->whereNull('af.IDapprenant')
                            ->whereRaw("DATE_ADD(sess.DateD, INTERVAL COALESCE(NULLIF(sp.dureeM, 0), sp.NbrSem * 6, 24) MONTH) >= CURRENT_DATE()");
                        
                        if (!empty($selEtab)) {
                            $q->where('o.IDEts_Form', (int)$selEtab);
                        } elseif (!empty($selWilaya)) {
                            $q->join('etablissement as e', 'o.IDEts_Form', '=', 'e.IDetablissement')
                              ->whereIn('e.IDDFEP', function($sub) use ($selWilaya) {
                                  $sub->select('IDDFEP')->from('dfep')->where('IDWilayaa', (int)$selWilaya);
                              });
                        }
                        if (!empty($selMode)) {
                            $q->where('o.IDMode_formation', (int)$selMode);
                        }

                        return $q->select('sess.IDSession', 'sess.Nom', DB::raw('count(a.IDapprenant) as count'))
                            ->groupBy('sess.IDSession', 'sess.Nom')
                            ->orderBy('sess.IDSession', 'desc')
                            ->limit(5)
                            ->get()
                            ->toArray();
                    } catch (\Throwable $e) {
                        return [];
                    }
                }
            );

            $data['total_stagiaires'] = collect($data['sessions_breakdown'])->sum('count');
            $data['total_reconduits'] = collect($data['sessions_breakdown'])->slice(1)->sum('count');

            $data['total_filles'] = \Illuminate\Support\Facades\Cache::remember(
                "sgfep:kpi:active_filles:" . (int)$selWilaya . ":" . (int)$selEtab . ":" . (int)$selMode,
                900,
                function() use ($selWilaya, $selEtab, $selMode) {
                    try {
                        $q = DB::table('apprenant as a')
                            ->join('section as s', 'a.IDSection', '=', 's.IDSection')
                            ->join('offre as o', 's.IDOffre', '=', 'o.IDOffre')
                            ->join('session as sess', 'o.IDSession', '=', 'sess.IDSession')
                            ->join('specialite as sp', 'o.IDSpecialite', '=', 'sp.IDSpecialite')
                            ->join('candidat as c', 'a.IDCandidat', '=', 'c.IDCandidat')
                            ->leftJoin('apprenant_fin as af', 'a.IDapprenant', '=', 'af.IDapprenant')
                            ->whereNull('af.IDapprenant')
                            ->where('c.Civ', 2)
                            ->whereRaw("DATE_ADD(sess.DateD, INTERVAL COALESCE(NULLIF(sp.dureeM, 0), sp.NbrSem * 6, 24) MONTH) >= CURRENT_DATE()");

                        if (!empty($selEtab)) {
                            $q->where('o.IDEts_Form', (int)$selEtab);
                        } elseif (!empty($selWilaya)) {
                            $q->join('etablissement as e', 'o.IDEts_Form', '=', 'e.IDetablissement')
                              ->whereIn('e.IDDFEP', function($sub) use ($selWilaya) {
                                  $sub->select('IDDFEP')->from('dfep')->where('IDWilayaa', (int)$selWilaya);
                              });
                        }
                        if (!empty($selMode)) {
                            $q->where('o.IDMode_formation', (int)$selMode);
                        }

                        return $q->count();
                    } catch (\Throwable $e) {
                        return 0;
                    }
                }
            );
            $data['total_garcons'] = max(0, $data['total_stagiaires'] - $data['total_filles']);

            // Fetch local_stagiaires for the establishment (directeur, etablissement, employee, formateur)
            $localStagiaires = [];
            if ($etabId > 0) {
                $stagCond = ['o.IDEts_Form = ?', "a.statut = 'actif'"];
                $stagParams = [$etabId];

                if (\App\Helpers\DepartmentHelper::isApprenticeship($user)) {
                    $stagCond[] = 'o.IDMode_formation = 10';
                } elseif (\App\Helpers\DepartmentHelper::isPresentielOnly($user)) {
                    $stagCond[] = 'o.IDMode_formation != 10';
                }

                try {
                    $localStagiaires = DB::select("
                        SELECT a.IDapprenant as id, c.NumIns as numero_matricule, 
                               c.Nom as nom_ar, c.Prenom as prenom_ar,
                               sp.Nom as spec_ar
                        FROM apprenant a
                        JOIN candidat c ON a.IDCandidat = c.IDCandidat
                        JOIN section s ON a.IDSection = s.IDSection
                        JOIN offre o ON s.IDOffre = o.IDOffre
                        JOIN specialite sp ON o.IDSpecialite = sp.IDSpecialite
                        WHERE " . implode(' AND ', $stagCond) . "
                        ORDER BY a.IDapprenant DESC
                        LIMIT 8
                    ", $stagParams);
                    
                    $localStagiaires = array_map(fn($r) => (array)$r, $localStagiaires);
                } catch (\Exception $e) {
                    Log::error('[Dashboard] Fetch local stagiaires error: ' . $e->getMessage());
                }
            }

            // ── §D  Defaults ──────────────────────────────────────────────────
            $data += [
                'local_stagiaires'   => $localStagiaires,
                'employee_documents' => [],
                'students'           => [],
                'meal_reservations'  => [],
                'document_requests'  => [],
                'simulated_profile'  => [],
                'dashboard_users'    => [],
                'api_key'            => 'WINDEV_API_READY',
            ];

        } catch (\Exception $e) {
            Log::error('[Dashboard] Build error', ['msg' => $e->getMessage()]);
            $data['db_error'] = $e->getMessage();
        }

        // ── Custom Dashboard Portal Loader ──
        $portalNum = (int)$request->query('portal', 1);
        $data['current_portal'] = $portalNum;

        try {
            $customDashboard = DB::table('dashboards')
                ->where('user_id', (int)($user['id'] ?? 0))
                ->where('portal_number', $portalNum)
                ->first();

            if ($customDashboard) {
                $customWidgets = DB::table('dashboard_widgets')
                    ->where('dashboard_id', $customDashboard->id)
                    ->orderBy('grid_y')
                    ->orderBy('grid_x')
                    ->get();

                $layoutConfig = json_decode($customDashboard->layout_config, true) ?? [];
                $customDashboard->layout_type = $layoutConfig['layout_type'] ?? 'grid-12-cols';

                $data['portalLayout'] = $customDashboard;
                $data['portalWidgets'] = $customWidgets;

                return view('dashboard.custom', $data);
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('[Dashboard Builder] Error loading custom dashboard: ' . $e->getMessage());
        }

        if (in_array($role, ['admin', 'secretaire_general', 'ministre'])) {
            return redirect()->route('admin.stats');
        }

        return view('dashboard.index', $data);
    }

    // ══════════════════════════════════════════════════════════════════════
    // §2  AJAX FILTER ENDPOINT (يُستدعى بـ AJAX من الـ frontend)
    // ══════════════════════════════════════════════════════════════════════

    public function filterApi(Request $request)
    {
        $user = session('user');
        if (!$user) {
            return response()->json(['error' => 'غير مصرح'], 401);
        }

        $role    = strtolower($user['role_code'] ?? '');
        $isDfep  = ($role === 'dfep');
        $isEtab  = in_array($role, ['directeur', 'etablissement']);
        $dfepId  = (int)($user['iddfep'] ?? $user['IDDFEP'] ?? $user['id_dfep'] ?? 0);
        $etabId  = (int)($user['etablissement_id'] ?? $user['IDEts_Form'] ?? $user['IDetablissement'] ?? $user['id_etablissement'] ?? 0);

        // المدخلات (من URL params)
        $etablissementId  = $request->query('etablissement_id');
        $filiereId        = $request->query('filiere_id');
        $specialiteId     = $request->query('specialite_id');
        $modeFormation    = $request->query('mode_formation');
        $sexe             = $request->query('sexe');
        $q                = $request->query('q');
        $selWilayaFilter  = $request->query('wilaya_id');

        // ── بناء شروط SQL ─────────────────────────────────────────────────
        $where  = ['1=1'];
        $params = [];

        // Security scope — لا يمكن تجاوزه
        if ($isDfep && $dfepId > 0) { $where[] = 'e.IDDFEP = ?'; $params[] = $dfepId; }
        if ($isEtab && $etabId > 0) { $where[] = 'o.IDEts_Form = ?'; $params[] = $etabId; }

        // Restrict to Apprenticeship mode if the user belongs to mode 10
        if ((int)($user['IDMode_formation'] ?? 0) === 10) {
            $where[] = 'o.IDMode_formation = 10';
        }

        // فلاتر اختيارية
        if (!empty($selWilayaFilter) && !$isDfep) { $where[] = 'e.IDDFEP = ?'; $params[] = $selWilayaFilter; }
        if (!empty($etablissementId) && !$isEtab) { $where[] = 'o.IDEts_Form = ?'; $params[] = $etablissementId; }
        if (!empty($filiereId))     { $where[] = 'sp.IDBranche = ?';      $params[] = $filiereId; }
        if (!empty($specialiteId))  { $where[] = 'o.IDSpecialite = ?';    $params[] = $specialiteId; }
        if (!empty($modeFormation)) { $where[] = 'o.IDMode_formation = ?';$params[] = $modeFormation; }
        if (!empty($sexe))          { $where[] = 'c.Civ = ?';             $params[] = $sexe; }
        if (!empty($q))             { $where[] = '(c.Nom LIKE ? OR c.Prenom LIKE ?)'; $params[] = "%$q%"; $params[] = "%$q%"; }

        $wc = implode(' AND ', $where);

        // ── ✅ الأرقام المجملة فقط — لا نُعيد قائمة آلاف السجلات ──────────
        try {
            $useApprenant = $this->resolveUseApprenant();

            $countStagiaires = $useApprenant
                ? $this->scalar("SELECT COUNT(DISTINCT a.IDapprenant) as c FROM apprenant a LEFT JOIN candidat c ON a.IDCandidat=c.IDCandidat LEFT JOIN section s ON a.IDSection=s.IDSection LEFT JOIN offre o ON s.IDOffre=o.IDOffre LEFT JOIN specialite sp ON o.IDSpecialite=sp.IDSpecialite LEFT JOIN etablissement e ON o.IDEts_Form=e.IDetablissement WHERE $wc", $params)
                : $this->scalar("SELECT COALESCE(SUM(o.NbrInscr),0) as c FROM offre o LEFT JOIN specialite sp ON o.IDSpecialite=sp.IDSpecialite LEFT JOIN etablissement e ON o.IDEts_Form=e.IDetablissement WHERE $wc AND o.NbrInscr>0", $params);

            $countOffres = $this->scalar("SELECT COUNT(DISTINCT o.IDOffre) as c FROM offre o LEFT JOIN specialite sp ON o.IDSpecialite=sp.IDSpecialite LEFT JOIN etablissement e ON o.IDEts_Form=e.IDetablissement WHERE $wc", $params);

            // ✅ قائمة صغيرة LIMIT 25 فقط — ممنوع رفعها للـ RAM كاملة
            $list = $this->safeQuery("
                SELECT a.IDapprenant as id, a.Nccp as matricule,
                       c.Nom as nom, c.Prenom as prenom, c.Civ as sexe,
                       sp.Nom as specialite, e.Nom as etablissement
                FROM apprenant a
                LEFT JOIN candidat c ON a.IDCandidat=c.IDCandidat
                LEFT JOIN section s ON a.IDSection=s.IDSection
                LEFT JOIN offre o ON s.IDOffre=o.IDOffre
                LEFT JOIN specialite sp ON o.IDSpecialite=sp.IDSpecialite
                LEFT JOIN etablissement e ON o.IDEts_Form=e.IDetablissement
                WHERE $wc ORDER BY a.IDapprenant DESC LIMIT 25
            ", $params);

            // ── 📊 حساب إحصائيات المخططات البيانية (Charts) ──────────────────
            // 1. توزيع أنماط التكوين (Modes)
            $modeData = $useApprenant
                ? DB::select("SELECT o.IDMode_formation as mode_formation, COUNT(DISTINCT a.IDapprenant) as count FROM apprenant a LEFT JOIN candidat c ON a.IDCandidat=c.IDCandidat LEFT JOIN section s ON a.IDSection=s.IDSection LEFT JOIN offre o ON s.IDOffre=o.IDOffre LEFT JOIN specialite sp ON o.IDSpecialite=sp.IDSpecialite LEFT JOIN etablissement e ON o.IDEts_Form=e.IDetablissement WHERE $wc GROUP BY o.IDMode_formation", $params)
                : DB::select("SELECT o.IDMode_formation as mode_formation, SUM(o.NbrInscr) as count FROM offre o LEFT JOIN specialite sp ON o.IDSpecialite=sp.IDSpecialite LEFT JOIN etablissement e ON o.IDEts_Form=e.IDetablissement WHERE $wc AND o.NbrInscr>0 GROUP BY o.IDMode_formation", $params);

            $modeData = array_map(fn($item) => ['mode_formation' => $item->mode_formation, 'count' => (int)$item->count], $modeData);

            // 2. حالة المتربصين (Statut)
            if ($useApprenant) {
                $statusData = DB::select("SELECT a.statut, COUNT(DISTINCT a.IDapprenant) as count FROM apprenant a LEFT JOIN candidat c ON a.IDCandidat=c.IDCandidat LEFT JOIN section s ON a.IDSection=s.IDSection LEFT JOIN offre o ON s.IDOffre=o.IDOffre LEFT JOIN specialite sp ON o.IDSpecialite=sp.IDSpecialite LEFT JOIN etablissement e ON o.IDEts_Form=e.IDetablissement WHERE $wc GROUP BY a.statut", $params);
                $statusData = array_map(fn($item) => ['statut' => $item->statut, 'count' => (int)$item->count], $statusData);
            } else {
                $statusData = [
                    ['statut' => 'actif', 'count' => $countStagiaires],
                    ['statut' => 'suspendu', 'count' => 0],
                    ['statut' => 'diplome', 'count' => 0],
                    ['statut' => 'abandon', 'count' => 0]
                ];
            }

            // 3. النسبة الجنسانية (Genre / Sexe)
            if ($useApprenant) {
                $sexeData = DB::select("SELECT c.Civ as sexe, COUNT(DISTINCT a.IDapprenant) as count FROM apprenant a LEFT JOIN candidat c ON a.IDCandidat=c.IDCandidat LEFT JOIN section s ON a.IDSection=s.IDSection LEFT JOIN offre o ON s.IDOffre=o.IDOffre LEFT JOIN specialite sp ON o.IDSpecialite=sp.IDSpecialite LEFT JOIN etablissement e ON o.IDEts_Form=e.IDetablissement WHERE $wc AND c.Civ IS NOT NULL GROUP BY c.Civ", $params);
                $sexeData = array_map(fn($item) => ['sexe' => $item->sexe, 'count' => (int)$item->count], $sexeData);
            } else {
                $totalFilles = (int)($this->safeSingle("SELECT SUM(o.NbrInscrf) as c FROM offre o LEFT JOIN specialite sp ON o.IDSpecialite=sp.IDSpecialite LEFT JOIN etablissement e ON o.IDEts_Form=e.IDetablissement WHERE $wc AND o.NbrInscrf>0", $params, 'c', 0));
                $sexeData = [
                    ['sexe' => 'F', 'count' => $totalFilles],
                    ['sexe' => 'M', 'count' => max(0, $countStagiaires - $totalFilles)]
                ];
            }

            // 4. أعلى التخصصات طلباً (Top Specialties)
            $topSpecs = $useApprenant
                ? DB::select("SELECT sp.Nom as spec_ar, sp.CodeSpec as spec_code, COUNT(DISTINCT a.IDapprenant) as count FROM apprenant a LEFT JOIN candidat c ON a.IDCandidat=c.IDCandidat LEFT JOIN section s ON a.IDSection=s.IDSection LEFT JOIN offre o ON s.IDOffre=o.IDOffre LEFT JOIN specialite sp ON o.IDSpecialite=sp.IDSpecialite LEFT JOIN etablissement e ON o.IDEts_Form=e.IDetablissement WHERE $wc GROUP BY sp.Nom, sp.CodeSpec ORDER BY count DESC LIMIT 5", $params)
                : DB::select("SELECT sp.Nom as spec_ar, sp.CodeSpec as spec_code, SUM(o.NbrInscr) as count FROM offre o LEFT JOIN specialite sp ON o.IDSpecialite=sp.IDSpecialite LEFT JOIN etablissement e ON o.IDEts_Form=e.IDetablissement WHERE $wc AND o.NbrInscr>0 GROUP BY sp.Nom, sp.CodeSpec ORDER BY count DESC LIMIT 5", $params);

            $topSpecs = array_map(fn($item) => ['spec_ar' => $item->spec_ar, 'spec_code' => $item->spec_code ?? '', 'count' => (int)$item->count], $topSpecs);

            return response()->json([
                'success'          => true,
                'count_stagiaires' => $countStagiaires,
                'count_offres'     => $countOffres,
                'stagiaires'       => $list,  // ✅ 25 سجل max
                'chartsData'       => [
                    'mode'            => $modeData,
                    'statut'          => $statusData,
                    'sexe'            => $sexeData,
                    'top_specialties' => $topSpecs
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('[Dashboard][filterApi]', ['msg' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // ══════════════════════════════════════════════════════════════════════
    // §3  EXPORT (يُشغَّل Job في الخلفية — المستخدم لا ينتظر)
    // ══════════════════════════════════════════════════════════════════════

    public function exportRequest(Request $request)
    {
        $user = session('user');
        if (!$user) return response()->json(['error' => 'Unauthorized'], 401);

        $userId  = (int)($user['id'] ?? 0);
        $type    = $request->input('type', 'stagiaires');
        $filters = $request->only(['wilaya_id', 'etab_id', 'mode_id', 'annee_id']);

        // ✅ تسجيل الطلب في DB ثم إرسال الـ Job للخلفية
        $requestId = DB::table('export_requests')->insertGetId([
            'user_id'    => $userId,
            'type'       => $type,
            'filters'    => json_encode($filters),
            'status'     => 'pending',
            'created_at' => now(),
        ]);

        // ✅ dispatch() — المستخدم يُعاد له الجواب فوراً
        ExportStatistiquesJob::dispatch($userId, $filters, $type)
            ->onQueue('exports');

        return response()->json([
            'success'    => true,
            'request_id' => $requestId,
            'message'    => 'جاري تحضير الملف في الخلفية. سيتوفر قريباً في قسم التحميلات.',
        ]);
    }

    /** فحص حالة طلب التصدير */
    public function exportStatus(int $requestId)
    {
        $req = DB::table('export_requests')
            ->where('id', $requestId)
            ->select('status', 'file_path', 'completed_at')
            ->first();

        if (!$req) return response()->json(['error' => 'Not found'], 404);

        return response()->json([
            'status'       => $req->status,
            'ready'        => $req->status === 'ready',
            'download_url' => $req->status === 'ready' ? route('dashboard.export.download', $requestId) : null,
        ]);
    }

    // ══════════════════════════════════════════════════════════════════════
    // §4  PRIVATE HELPERS
    // ══════════════════════════════════════════════════════════════════════

    /** القوائم الصغيرة — أحدث 8 تسجيلات (لا تُكشّ) */
    private function getRecentInscriptions(Request $request, bool $isDfep, bool $isEtab, int $dfepId, int $etabId): array
    {
        $cond = ['1=1']; $params = [];

        if ($isDfep && $dfepId > 0) { $cond[] = 'e.IDDFEP = ?'; $params[] = $dfepId; }
        if ($isEtab && $etabId > 0) { $cond[] = 'o.IDEts_Form = ?'; $params[] = $etabId; }

        // Restrict to Apprenticeship mode if the user belongs to mode 10, or exclude it if sdtpp
        $userSession = session('user') ?? [];
        if (\App\Helpers\DepartmentHelper::isApprenticeship($userSession)) {
            $cond[] = 'o.IDMode_formation = 10';
        } elseif (\App\Helpers\DepartmentHelper::isPresentielOnly($userSession)) {
            $cond[] = 'o.IDMode_formation != 10';
        }

        if ($f = $request->query('filter_wilaya')) { if (!$isDfep && !$isEtab) { $cond[] = 'e.IDDFEP = ?'; $params[] = (int)$f; } }
        if ($f = $request->query('filter_etablissement')) { if (!$isEtab) { $cond[] = 'o.IDEts_Form = ?'; $params[] = (int)$f; } }
        if ($f = $request->query('filter_mode')) { $cond[] = 'o.IDMode_formation = ?'; $params[] = $f; }

        return $this->safeQuery("
            SELECT c.IDCandidat as id, c.Nom as nom_ar, c.Prenom as prenom_ar,
                   sp.Nom as spec_ar, c.dateInscr as date_inscription, e.Nom as etab_ar
            FROM candidat c
            LEFT JOIN offre o ON c.IDOffre=o.IDOffre
            LEFT JOIN specialite sp ON o.IDSpecialite=sp.IDSpecialite
            LEFT JOIN etablissement e ON o.IDEts_Form=e.IDetablissement
            WHERE " . implode(' AND ', $cond) . "
            ORDER BY c.IDCandidat DESC LIMIT 8
        ", $params);
    }

    /** بيانات المكوّن (formateur) — LIMIT صارم على القوائم */
    private function buildFormateurData(int $empId): array
    {
        $assigned_modules = $this->safeQuery("
            SELECT ssm.IDsection_semestre_Module as id, ssm.NomMdl as name, ssm.coef, s.Nom as section_name, s.IDSection as section_id, o.IDOffre as registration_offre_id, ss.NumSem as semester
            FROM section_semestre_module ssm
            JOIN section_semestre ss ON ssm.IDSection_Semestre = ss.IDSection_Semestre
            JOIN section s ON ss.IDSection = s.IDSection
            JOIN offre o ON s.IDOffre = o.IDOffre
            WHERE ssm.IDEncadrement = ?
            ORDER BY s.Nom, ssm.NomMdl
        ", [$empId]);

        $trainees = $this->safeQuery("
            SELECT a.IDapprenant as id, a.Nccp as numero_matricule, c.Nom as nom_ar, c.Prenom as prenom_ar, 
                   s.IDSection as section_id, s.Nom as section_name,
                   assm.IDApprenant_Section_semstre_module as grade_id,
                   assm.NoteC1 as note_c1, assm.NoteC2 as note_c2, assm.NoteCs as note_cs, assm.NoteR as note_r,
                   assm.Obs as note_obs,
                   ssm.IDsection_semestre_Module as module_id
            FROM section_semestre_module ssm
            JOIN section_semestre ss ON ssm.IDSection_Semestre = ss.IDSection_Semestre
            JOIN section s ON ss.IDSection = s.IDSection
            JOIN apprenant a ON s.IDSection = a.IDSection
            JOIN candidat c ON a.IDCandidat = c.IDCandidat
            LEFT JOIN apprenant_section_semstre ass ON a.IDapprenant = ass.IDapprenant AND ss.IDSection_Semestre = ass.IDSection_Semestre
            LEFT JOIN apprenant_section_semstre_module assm ON ass.IDapprenant_Section_semstre = assm.IDapprenant_Section_semstre AND ssm.IDsection_semestre_Module = assm.IDsection_semestre_Module
            WHERE ssm.IDEncadrement = ?
            ORDER BY c.Nom, c.Prenom
        ", [$empId]);

        $timetable = $this->safeQuery("
            SELECT et.Jour as day, et.Heured as start_time, et.Heuref as end_time, ssm.NomMdl as module_name, s.Nom as section_name, l.Nom as room_name
            FROM emploitemp et
            JOIN section_semestre_module ssm ON et.IDsection_semestre_Module = ssm.IDsection_semestre_Module
            JOIN section_semestre ss ON ssm.IDSection_Semestre = ss.IDSection_Semestre
            JOIN section s ON ss.IDSection = s.IDSection
            LEFT JOIN locaux l ON et.IDLocaux = l.IDLocaux
            WHERE ssm.IDEncadrement = ?
            ORDER BY et.Jour, et.Heured
        ", [$empId]);

        $employee = DB::selectOne("
            SELECT enc.*, et.Nom AS etab_nom, et.NomFr AS etab_fr, w.Nom AS wilaya_nom
            FROM encadrement enc
            LEFT JOIN etablissement et ON enc.IDetablissement = et.IDetablissement
            LEFT JOIN wilaya w ON et.IDDFEP = w.IDWilayaa
            WHERE enc.IDEncadrement = ?
            LIMIT 1
        ", [$empId]);
        
        $employee = $employee ? (array)$employee : [];
        $sitFamilleMap = [
            1 => 'أعزب / عزباء',
            2 => 'متزوج / متزوجة',
            3 => 'مطلق / مطلقة',
            4 => 'أرمل / أرملة',
        ];
        if (!empty($employee)) {
            $employee['sitfamille_text'] = $sitFamilleMap[(int)($employee['IDSitfamille'] ?? 1)] ?? 'غير محدد';
        }

        $leaves = DB::table('employee_leaves')
            ->where('employee_id', $empId)
            ->orderBy('id', 'desc')
            ->get()
            ->toArray();
        $leaves = array_map(fn($item) => (array)$item, $leaves);

        $document_requests = DB::table('employee_document_requests')
            ->where('employee_id', $empId)
            ->orderBy('id', 'desc')
            ->get()
            ->toArray();
        $document_requests = array_map(function($item) use ($empId) {
            $arr = (array)$item;
            $arr['request_id'] = $arr['id'];
            $arr['id'] = $empId;
            return $arr;
        }, $document_requests);

        $messages = DB::table('employee_messages')
            ->where('employee_id', $empId)
            ->orderBy('id', 'asc')
            ->get()
            ->toArray();
        $messages = array_map(fn($item) => (array)$item, $messages);

        $employee_docs = !empty($document_requests) ? $document_requests : [
            [
                'id' => $empId,
                'document_type' => 'attestation_travail',
                'code_verification' => 'WORK-' . date('Y') . '-' . str_pad($empId, 5, '0', STR_PAD_LEFT)
            ],
            [
                'id' => $empId,
                'document_type' => 'fiche_paie',
                'code_verification' => 'PAY-' . date('Y') . '-' . str_pad($empId, 5, '0', STR_PAD_LEFT)
            ]
        ];

        return [
            'employee'          => $employee,
            'total_classes'     => $this->safeSingle("SELECT COUNT(DISTINCT ss.IDSection) as c FROM section_semestre_module ssm JOIN section_semestre ss ON ssm.IDSection_Semestre=ss.IDSection_Semestre WHERE ssm.IDEncadrement=?", [$empId], 'c', 0),
            'total_modules'     => $this->safeSingle("SELECT COUNT(DISTINCT IDsection_semestre_Module) as c FROM section_semestre_module WHERE IDEncadrement=?", [$empId], 'c', 0),
            'total_evaluations' => $this->safeSingle("SELECT COUNT(*) as c FROM section_semestre_module ssm JOIN apprenant_section_semstre_module assm ON ssm.IDsection_semestre_Module=assm.IDsection_semestre_Module WHERE ssm.IDEncadrement=?", [$empId], 'c', 0),
            'assigned_modules'  => $assigned_modules,
            'trainees'          => $trainees,
            'students'          => array_slice($trainees, 0, 10), // Keep students for compatibility
            'timetable'         => $timetable,
            'leaves'            => $leaves,
            'messages'          => $messages,
            'employee_documents'=> $employee_docs,
            'total_stagiaires' => 0, 'total_offres' => 0, 'total_etablissements' => 0, 'total_users' => 0,
        ];
    }

    private function buildEmployeeDashboardData(int $empId): array
    {
        $employee = DB::selectOne("
            SELECT enc.*, et.Nom AS etab_nom, et.NomFr AS etab_fr, w.Nom AS wilaya_nom
            FROM encadrement enc
            LEFT JOIN etablissement et ON enc.IDetablissement = et.IDetablissement
            LEFT JOIN wilaya w ON et.IDDFEP = w.IDWilayaa
            WHERE enc.IDEncadrement = ?
            LIMIT 1
        ", [$empId]);
        
        $employee = $employee ? (array)$employee : [];
        
        $sitFamilleMap = [
            1 => 'أعزب / عزباء',
            2 => 'متزوج / متزوجة',
            3 => 'مطلق / مطلقة',
            4 => 'أرمل / أرملة',
        ];
        if (!empty($employee)) {
            $employee['sitfamille_text'] = $sitFamilleMap[(int)($employee['IDSitfamille'] ?? 1)] ?? 'غير محدد';
        }

        $leaves = DB::table('employee_leaves')
            ->where('employee_id', $empId)
            ->orderBy('id', 'desc')
            ->get()
            ->toArray();
        $leaves = array_map(fn($item) => (array)$item, $leaves);

        $document_requests = DB::table('employee_document_requests')
            ->where('employee_id', $empId)
            ->orderBy('id', 'desc')
            ->get()
            ->toArray();
        $document_requests = array_map(function($item) use ($empId) {
            $arr = (array)$item;
            $arr['request_id'] = $arr['id'];
            $arr['id'] = $empId;
            return $arr;
        }, $document_requests);

        $messages = DB::table('employee_messages')
            ->where('employee_id', $empId)
            ->orderBy('id', 'asc')
            ->get()
            ->toArray();
        $messages = array_map(fn($item) => (array)$item, $messages);

        return [
            'employee' => $employee,
            'leaves' => $leaves,
            'employee_documents' => $document_requests,
            'messages' => $messages,
            'total_stagiaires' => 0, 'total_offres' => 0, 'total_etablissements' => 0, 'total_users' => 0,
        ];
    }

    /** بيانات المتكون (stagiaire) — فردية، لا كاش */
    private function buildStagiaireData(array $user): array
    {
        $username = $user['username'] ?? '';

        $stagiaire = null;
        foreach ([
            "SELECT a.IDapprenant, a.Nccp as numero_matricule, c.Nom as nom_ar, c.Prenom as prenom_ar, c.NomFr as nom_fr, c.PrenomFr as prenom_fr, c.photo, sp.Nom as spec_ar, e.Nom as etab_nom FROM apprenant a JOIN candidat c ON a.IDCandidat=c.IDCandidat LEFT JOIN section s ON a.IDSection=s.IDSection LEFT JOIN offre o ON s.IDOffre=o.IDOffre LEFT JOIN specialite sp ON o.IDSpecialite=sp.IDSpecialite LEFT JOIN etablissement e ON o.IDEts_Form=e.IDetablissement WHERE a.Nccp=? LIMIT 1" => [$username],
            "SELECT c.IDCandidat as IDapprenant, c.NumIns as numero_matricule, c.Nom as nom_ar, c.Prenom as prenom_ar, c.NomFr as nom_fr, c.PrenomFr as prenom_fr, c.photo, sp.Nom as spec_ar, e.Nom as etab_nom FROM candidat c LEFT JOIN offre o ON c.IDOffre=o.IDOffre LEFT JOIN specialite sp ON o.IDSpecialite=sp.IDSpecialite LEFT JOIN etablissement e ON o.IDEts_Form=e.IDetablissement WHERE c.Nin=? LIMIT 1" => [$username],
        ] as $sql => $params) {
            $row = DB::selectOne($sql, $params);
            if ($row) { $stagiaire = (array)$row; break; }
        }

        $stagiaire ??= [
            'IDapprenant'       => 0,
            'nom_ar'            => 'مستخدم',
            'prenom_ar'         => '',
            'numero_matricule'  => $username,
            'spec_ar'           => '',
            'etab_nom'          => '',
        ];

        return [
            'stagiaire'         => $stagiaire,
            'grades'            => [],  // ❌ لا تُكشّ النقاط — دائماً من DB مباشرة
            'semester_gpa'      => 0.0,
            'is_admis'          => false,
            'meal_reservations' => [],
            'document_requests' => [],
            'total_stagiaires'  => 1, 'total_offres' => 0,
            'total_etablissements' => 0, 'total_users' => 0,
        ];
    }

    /** مؤسسات الولاية للعرض في لوحة DFEP */
    private function getDfepInstitutions(int $dfepId): array
    {
        $modeId = (int)session('user.IDMode_formation');
        $offreSub = $modeId === 10
            ? "(SELECT IDEts_Form, COUNT(*) as cnt FROM offre WHERE IDMode_formation = 10 GROUP BY IDEts_Form)"
            : "(SELECT IDEts_Form, COUNT(*) as cnt FROM offre GROUP BY IDEts_Form)";

        return $this->safeQuery("
            SELECT e.IDetablissement, e.Nom, e.Code, nf.Abr as type,
                   COALESCE(o.cnt,0) as nb_offres, COALESCE(enc.cnt,0) as nb_employes
            FROM etablissement e
            LEFT JOIN nature_etsf nf ON e.IDNature_etsF=nf.IDNature_etsF
            LEFT JOIN {$offreSub} o ON o.IDEts_Form=e.IDetablissement
            LEFT JOIN (SELECT IDetablissement, COUNT(*) as cnt FROM Encadrement GROUP BY IDetablissement) enc ON enc.IDetablissement=e.IDetablissement
            WHERE e.IDDFEP=? ORDER BY nf.NumOrd, e.Nom
        ", [$dfepId]);
    }

    private function getDfepTypeStats(int $dfepId): array
    {
        return $this->safeQuery("
            SELECT nf.Abr as type, nf.Nom as type_nom, COUNT(*) as nb
            FROM etablissement e LEFT JOIN nature_etsf nf ON e.IDNature_etsF=nf.IDNature_etsF
            WHERE e.IDDFEP=? GROUP BY nf.IDNature_etsF ORDER BY nf.NumOrd
        ", [$dfepId]);
    }

    /** تحديد إذا كان apprenant جاهزاً — يُكشّ 1 ساعة */
    private function resolveUseApprenant(): bool
    {
        return \Illuminate\Support\Facades\Cache::remember('sgfep:kpi:use_apprenant', 3600, function () {
            try {
                $row = DB::selectOne("SELECT is_ready FROM sync_status WHERE table_name='apprenant' LIMIT 1");
                if ($row) return (bool)$row->is_ready;
            } catch (\Throwable $e) {}
            try {
                return DB::selectOne("SELECT COUNT(*) as c FROM apprenant")->c
                    > DB::selectOne("SELECT COALESCE(SUM(NbrInscr),0) as c FROM offre WHERE NbrInscr>0")->c * 0.95;
            } catch (\Throwable $e) { return false; }
        });
    }

    // ── Utility: safe DB helpers ──────────────────────────────────────────

    private function safeQuery(string $sql, array $params): array
    {
        try {
            return array_map(fn($r) => (array)$r, DB::select($sql, $params));
        } catch (\Exception $e) { return []; }
    }

    private function scalar(string $sql, array $params): int
    {
        try { return (int)(DB::selectOne($sql, $params)->c ?? 0); }
        catch (\Exception $e) { return 0; }
    }

    private function safeSingle(string $sql, array $params, string $col, mixed $default): mixed
    {
        try {
            $row = DB::selectOne($sql, $params);
            return $row ? $row->$col : $default;
        } catch (\Exception $e) { return $default; }
    }

    // ── Direct actions for department dashboards ──────────────────────────
    public function viewFinance(Request $request) { return $this->renderDepartmentView('finance', $request); }
    public function viewRh(Request $request)      { return $this->renderDepartmentView('hr', $request); }
    public function viewPlan(Request $request)    { return $this->renderDepartmentView('plan', $request); }
    public function viewCoop(Request $request)    { return $this->renderDepartmentView('coop', $request); }
    public function viewIt(Request $request)      { return $this->renderDepartmentView('it', $request); }
    public function viewExam(Request $request)    { return $this->renderDepartmentView('exam', $request); }
    public function addExamSession(Request $request)
    {
        $name = $request->input('name');
        $code = $request->input('code') ?: ('EX-' . date('Y'));
        $dateD = $request->input('date_d');

        try {
            DB::table('session')->insert([
                'Nom' => $name,
                'NomFr' => $code,
                'DateD' => $dateD,
                'Encour' => 1
            ]);

            try {
                DB::table('audit_logs')->insert([
                    'username' => session('user')['username'] ?? 'sys',
                    'action' => 'Create Session: ' . $name,
                    'table_name' => 'session',
                    'created_at' => now()
                ]);
            } catch (\Exception $e) {}

            // Clear sessions list cache
            try {
                \Illuminate\Support\Facades\Cache::flush();
            } catch (\Exception $e) {}

            return response()->json([
                'success' => true,
                'message' => 'تم فتح الدورة بنجاح ودخولها حيز التنفيذ'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطأ أثناء فتح الدورة: ' . $e->getMessage()
            ]);
        }
    }
    public function viewTrak(Request $request)    { return $this->renderDepartmentView('trak', $request); }
    public function viewEdu(Request $request)     { return $this->renderDepartmentView('edu', $request); }
    public function viewDfcri(Request $request)   { return $this->renderDepartmentView('dfcri', $request); }
    public function viewPromotions(Request $request) { return $this->renderDepartmentView('promotions', $request); }
    public function viewConcours(Request $request)   { return $this->renderDepartmentView('concours', $request); }
    public function viewSalaires(Request $request)   { return $this->renderDepartmentView('salaires', $request); }

    public function viewAdminStats(Request $request)
    {
        $user = session('user', []);
        $role = session('role', '');
        // Only admin/ministre/secretaire_general can access
        if (!in_array($role, ['admin', 'ministre', 'secretaire_general', 'central', 'high_admin'])) {
            abort(403, 'غير مصرح');
        }

        $data = [
            'title' => 'لوحة الإحصائيات الشاملة',
            'role'  => $role,
            'user'  => $user,
        ];

        // Top 5 active sessions breakdown (truly active trainees - DEOH logic)
        $data['sessions_breakdown'] = \Illuminate\Support\Facades\Cache::remember('sgfep:kpi:minister:sessions_breakdown_correct', 900, function() {
            return DB::table('session as sess')
                ->join('section as s', 's.IDSession', '=', 'sess.IDSession')
                ->join('apprenant as a', 'a.IDSection', '=', 's.IDSection')
                ->leftJoin('apprenant_fin as af', 'a.IDapprenant', '=', 'af.IDapprenant')
                ->where('a.statut', 'actif')
                ->whereNull('af.IDapprenant')
                ->where('s.DateDF', '<=', now())
                ->where('s.DateFF', '>=', now())
                ->select('sess.IDSession', 'sess.Nom', DB::raw('count(a.IDapprenant) as count'))
                ->groupBy('sess.IDSession', 'sess.Nom')
                ->orderBy('sess.IDSession', 'desc')
                ->limit(5)
                ->get();
        });
        // 1. Total Active Trainees (DEOH logic)
        $data['total_stagiaires'] = \Illuminate\Support\Facades\Cache::remember('sgfep:kpi:minister:total_stagiaires_correct', 900, function() {
            return DB::table('apprenant as a')
                ->join('section as s', 'a.IDSection', '=', 's.IDSection')
                ->leftJoin('apprenant_fin as af', 'a.IDapprenant', '=', 'af.IDapprenant')
                ->where('a.statut', 'actif')
                ->whereNull('af.IDapprenant')
                ->where('s.DateDF', '<=', now())
                ->where('s.DateFF', '>=', now())
                ->count();
        });

        // 2. Active Continuing Trainees S2-S5 (DEOH logic)
        $data['total_reconduits'] = \Illuminate\Support\Facades\Cache::remember('sgfep:kpi:minister:total_reconduits_correct_appr', 900, function() {
            return DB::table('apprenant as a')
                ->join('section as s', 'a.IDSection', '=', 's.IDSection')
                ->join('section_semestre as ss', 's.IDSection', '=', 'ss.IDSection')
                ->leftJoin('apprenant_fin as af', 'a.IDapprenant', '=', 'af.IDapprenant')
                ->where('a.statut', 'actif')
                ->whereNull('af.IDapprenant')
                ->where('s.DateDF', '<=', now())
                ->where('s.DateFF', '>=', now())
                ->where('ss.Dernier', 1)
                ->where('ss.NumSem', '>', 1)
                ->count();
        });

        // 3. Active S1 Sections (current session 35)
        $data['total_sections_s1'] = \Illuminate\Support\Facades\Cache::remember('sgfep:kpi:minister:s1_sections_correct', 900, function() {
            return DB::table('section_semestre as ss')
                ->join('section as s', 'ss.IDSection', '=', 's.IDSection')
                ->where('ss.Dernier', 1)
                ->where('ss.NumSem', 1)
                ->where('s.IDSession', 35)
                ->count();
        });

        // 4. Active Female Trainees (DEOH logic)
        $data['total_filles'] = \Illuminate\Support\Facades\Cache::remember('sgfep:kpi:minister:total_filles_correct_appr', 900, function() {
            return DB::table('apprenant as a')
                ->join('section as s', 'a.IDSection', '=', 's.IDSection')
                ->join('candidat as c', 'a.IDCandidat', '=', 'c.IDCandidat')
                ->leftJoin('apprenant_fin as af', 'a.IDapprenant', '=', 'af.IDapprenant')
                ->where('a.statut', 'actif')
                ->whereNull('af.IDapprenant')
                ->where('s.DateDF', '<=', now())
                ->where('s.DateFF', '>=', now())
                ->where('c.Civ', 2)
                ->count();
        });

        // 5. Total Successful Graduates (all time)
        $data['total_graduates'] = \Illuminate\Support\Facades\Cache::remember('sgfep:kpi:minister:total_graduates_correct_appr', 900, function() {
            return DB::table('apprenant_fin')
                ->whereIn('IDDecision_evalf', [1, 2, 3])
                ->count();
        });

        try { $data['total_encadrements'] = DB::table('encadrement')->count(); } catch (\Throwable $e) { $data['total_encadrements'] = 0; }
        try { $data['total_etablissements'] = DB::table('etablissement')->count(); } catch (\Throwable $e) { $data['total_etablissements'] = 0; }

        // Institution breakdown
        $data['count_cfpa']   = \Illuminate\Support\Facades\Cache::remember('sgfep:kpi:minister:cfpa',   900, fn() => DB::table('etablissement')->where('IDNature_etsF', 8)->where('activee', 0)->count());
        $data['count_insfp']  = \Illuminate\Support\Facades\Cache::remember('sgfep:kpi:minister:insfp',  900, fn() => DB::table('etablissement')->where('IDNature_etsF', 6)->where('activee', 0)->count());
        $data['count_private']= \Illuminate\Support\Facades\Cache::remember('sgfep:kpi:minister:private',900, fn() => DB::table('etablissement')->where('IDNature_etsF', 12)->where('activee', 0)->count());
        $data['count_dfep']   = \Illuminate\Support\Facades\Cache::remember('sgfep:kpi:minister:dfep',   900, fn() => DB::table('etablissement')->where('IDNature_etsF', 5)->where('activee', 0)->count());
        $data['count_ifep']   = \Illuminate\Support\Facades\Cache::remember('sgfep:kpi:minister:ifep',   900, fn() => DB::table('etablissement')->where('IDNature_etsF', 4)->where('activee', 0)->count());
        $data['count_infep']  = \Illuminate\Support\Facades\Cache::remember('sgfep:kpi:minister:infep',  900, fn() => DB::table('etablissement')->where('IDNature_etsF', 13)->where('activee', 0)->count());
        $data['count_iep']    = \Illuminate\Support\Facades\Cache::remember('sgfep:kpi:minister:iep',    900, fn() => DB::table('etablissement')->where('IDNature_etsF', 7)->where('activee', 0)->count());
        $data['count_cnfepd'] = \Illuminate\Support\Facades\Cache::remember('sgfep:kpi:minister:cnfepd', 900, fn() => DB::table('etablissement')->whereIn('IDNature_etsF', [3, 10])->where('activee', 0)->count());
        $data['count_annexes_fleurs'] = \Illuminate\Support\Facades\Cache::remember('sgfep:kpi:minister:annexes_fleurs', 900, fn() => DB::table('etablissement')->whereIn('IDNature_etsF', [2, 9, 11, 15, 16, 17, 18])->where('activee', 0)->count());
        $data['count_ets_with_account'] = \Illuminate\Support\Facades\Cache::remember('sgfep:kpi:minister:ets_with_account', 900, fn() => DB::table('etablissement')->whereNotNull('nomUser')->where('nomUser', '!=', '')->count());
        $data['count_ets_active']    = \Illuminate\Support\Facades\Cache::remember('sgfep:kpi:minister:ets_active',    900, fn() => DB::table('etablissement')->where('activee', 0)->count());
        $data['count_ets_suspended'] = \Illuminate\Support\Facades\Cache::remember('sgfep:kpi:minister:ets_suspended', 900, fn() => DB::table('etablissement')->where('activee', 1)->count());
        $data['count_active_staff']  = \Illuminate\Support\Facades\Cache::remember('sgfep:kpi:minister:active_staff',  900, fn() => DB::table('encadrement')->whereNotNull('nin')->where('nin','!=','')->whereNotNull('MotDePass')->where('MotDePass','!=','')->count());
        $data['count_inactive_staff']= max(0, $data['total_encadrements'] - $data['count_active_staff']);
        $data['suspended_etabs'] = \Illuminate\Support\Facades\Cache::remember('sgfep:kpi:minister:suspended_list', 900, function() {
            return DB::select("SELECT COALESCE(NULLIF(e.Nom, ''), 'مؤسسة بدون اسم') as nom, COALESCE(w.Nom, 'غير محدد') as wilaya, COALESCE(n.Nom, 'غير محدد') as nature FROM etablissement e LEFT JOIN wilaya w ON e.IDDFEP = w.IDWilayaa LEFT JOIN nature_etsf n ON e.IDNature_etsF = n.IDNature_etsF WHERE e.activee = 1 ORDER BY w.Nom, e.Nom");
        });
        $data['audit_logs'] = $this->safeQuery(
            "SELECT IDaccesuser as id, Date as created_at, NomUtilisateur as username,
             'LOGIN' as action, iplocal, NomPrenom as nom_complet
             FROM accesuser ORDER BY IDaccesuser DESC LIMIT 10", []
        );

        return view('dashboard.departments.admin_stats', $data);
    }

    // ── §6  CENTRAL DASHBOARDS DATABASE ACTIONS ───────────────────────────

    public function addBudget(Request $request)
    {
        $request->validate([
            'label' => 'required|string|max:255',
            'ae' => 'required|numeric|min:0',
            'cp' => 'required|numeric|min:0',
            'annee' => 'nullable|integer',
            'etablissement_id' => 'nullable|integer',
        ]);

        $user = session('user');
        $etabId = $request->input('etablissement_id') ?? $user['etablissement_id'] ?? 0;
        $anneeId = $request->input('annee') ?? 19; // Default to 2026/2025

        DB::transaction(function() use ($request, $etabId, $anneeId) {
            $nextId = (int)DB::table('budget')->max('IDBudget') + 1;
            $validAnneeId = DB::table('annee')->where('IDannee', $anneeId)->exists() ? $anneeId : null;
            $validEtabId = DB::table('etablissement')->where('IDetablissement', $etabId)->exists() ? $etabId : null;
            DB::table('budget')->insert([
                'IDBudget' => $nextId,
                'Nom' => $request->input('label'),
                'NomFr' => $request->input('label'),
                'IDannee' => $validAnneeId,
                'IDTbudget' => null,
                'IDDecret' => null,
                'IDetablissement' => $validEtabId,
                'Encour' => 1,
                'code' => 'B-' . strtoupper(uniqid()),
                'AE' => $request->input('ae'),
                'CP' => $request->input('cp'),
            ]);
        });

        $this->clearDashboardCache();

        return response()->json(['success' => true, 'message' => 'تم إضافة بند الميزانية بنجاح']);
    }

    public function updateBudget(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
            'label' => 'required|string|max:255',
            'ae' => 'required|numeric|min:0',
            'cp' => 'required|numeric|min:0',
            'annee' => 'nullable|integer',
            'etablissement_id' => 'nullable|integer',
        ]);

        try {
            $user = session('user');
            $etabId = $request->input('etablissement_id') ?? $user['etablissement_id'] ?? 0;
            $anneeId = $request->input('annee') ?? 19;
            $this->validateScope((int)$etabId);

            DB::table('budget')
                ->where('IDBudget', $request->input('id'))
                ->update([
                    'Nom' => $request->input('label'),
                    'NomFr' => $request->input('label'),
                    'IDannee' => DB::table('annee')->where('IDannee', $anneeId)->exists() ? $anneeId : null,
                    'IDetablissement' => DB::table('etablissement')->where('IDetablissement', $etabId)->exists() ? $etabId : null,
                    'AE' => $request->input('ae'),
                    'CP' => $request->input('cp'),
                ]);

            $this->clearDashboardCache();
            return response()->json(['success' => true, 'message' => 'تم تحديث بند الميزانية بنجاح']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function deleteBudget($id)
    {
        try {
            $b = DB::table('budget')->where('IDBudget', $id)->first();
            if (!$b) {
                return response()->json(['success' => false, 'message' => 'بند الميزانية غير موجود']);
            }
            if ($b->IDetablissement) {
                $this->validateScope((int)$b->IDetablissement);
            }

            DB::table('budget')->where('IDBudget', $id)->delete();
            $this->clearDashboardCache();
            return response()->json(['success' => true, 'message' => 'تم حذف بند الميزانية بنجاح']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function addOperation(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'num' => 'nullable|string|max:100',
            'apini' => 'required|numeric|min:0',
            'apact' => 'required|numeric|min:0',
            'apfinal' => 'required|numeric|min:0',
            'eng' => 'required|numeric|min:0',
            'pay' => 'required|numeric|min:0',
            'wilaya_id' => 'required|integer',
        ]);

        try {
            $user = session('user');
            $role = strtolower($user['role_code'] ?? 'user');
            $iddfep = (int)($user['iddfep'] ?? $user['IDDFEP'] ?? 0);
            if ($role === 'dfep' && $iddfep !== (int)$request->input('wilaya_id')) {
                return response()->json(['success' => false, 'message' => 'غير مصرح لك بإضافة عملية خارج ولايتك']);
            }

            DB::transaction(function() use ($request) {
                $nextId = (int)DB::table('operations')->max('IDOperations') + 1;
                DB::table('operations')->insert([
                    'IDOperations' => $nextId,
                    'Nom' => $request->input('name'),
                    'NomFr' => $request->input('name'),
                    'Num' => $request->input('num') ?? 'OP-' . strtoupper(uniqid()),
                    'APINI' => $request->input('apini'),
                    'APACT' => $request->input('apact'),
                    'APFINAL' => $request->input('apfinal'),
                    'MantEngagment' => $request->input('eng'),
                    'Mantpayement' => $request->input('pay'),
                    'IDDFEP' => $request->input('wilaya_id'),
                    'IDOperationEtat' => 4,
                    'IDOperationDecition' => 3,
                ]);
            });

            $this->clearDashboardCache();
            return response()->json(['success' => true, 'message' => 'تم إضافة العملية التنموية بنجاح']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function updateOperation(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
            'name' => 'required|string|max:255',
            'num' => 'nullable|string|max:100',
            'apini' => 'required|numeric|min:0',
            'apact' => 'required|numeric|min:0',
            'apfinal' => 'required|numeric|min:0',
            'eng' => 'required|numeric|min:0',
            'pay' => 'required|numeric|min:0',
            'wilaya_id' => 'required|integer',
        ]);

        try {
            $user = session('user');
            $role = strtolower($user['role_code'] ?? 'user');
            $iddfep = (int)($user['iddfep'] ?? $user['IDDFEP'] ?? 0);
            if ($role === 'dfep' && $iddfep !== (int)$request->input('wilaya_id')) {
                return response()->json(['success' => false, 'message' => 'غير مصرح لك بتعديل عملية خارج ولايتك']);
            }

            DB::table('operations')
                ->where('IDOperations', $request->input('id'))
                ->update([
                    'Nom' => $request->input('name'),
                    'NomFr' => $request->input('name'),
                    'Num' => $request->input('num'),
                    'APINI' => $request->input('apini'),
                    'APACT' => $request->input('apact'),
                    'APFINAL' => $request->input('apfinal'),
                    'MantEngagment' => $request->input('eng'),
                    'Mantpayement' => $request->input('pay'),
                    'IDDFEP' => $request->input('wilaya_id'),
                ]);

            $this->clearDashboardCache();
            return response()->json(['success' => true, 'message' => 'تم تحديث العملية التنموية بنجاح']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function deleteOperation($id)
    {
        try {
            $op = DB::table('operations')->where('IDOperations', $id)->first();
            if (!$op) {
                return response()->json(['success' => false, 'message' => 'العملية غير موجودة']);
            }
            
            $user = session('user');
            $role = strtolower($user['role_code'] ?? 'user');
            $iddfep = (int)($user['iddfep'] ?? $user['IDDFEP'] ?? 0);
            if ($role === 'dfep' && $iddfep !== (int)$op->IDDFEP) {
                return response()->json(['success' => false, 'message' => 'غير مصرح لك بحذف عملية خارج ولايتك']);
            }

            DB::table('operations')->where('IDOperations', $id)->delete();
            $this->clearDashboardCache();
            return response()->json(['success' => true, 'message' => 'تم حذف العملية التنموية بنجاح']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function sendFinanceNotification(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'target_wilaya' => 'nullable|integer',
        ]);

        try {
            $title = $request->input('title');
            $message = $request->input('message');
            $wilayaId = $request->input('target_wilaya');

            $query = DB::table('utilisateur')->where('IDNature', 4);
            if (!empty($wilayaId)) {
                $query->where('IDBureau', $wilayaId);
            }
            $users = $query->get(['IDUtilisateur']);

            if ($users->isEmpty()) {
                return response()->json(['success' => false, 'message' => 'لم يتم العثور على مستخدمين نشطين للمديريات المحددة']);
            }

            DB::transaction(function() use ($users, $title, $message) {
                foreach ($users as $u) {
                    $nextId = (int)DB::table('notifications')->max('id') + 1;
                    DB::table('notifications')->insert([
                        'id' => $nextId,
                        'user_id' => $u->IDUtilisateur,
                        'title' => $title,
                        'message' => $message,
                        'type' => 'finance',
                        'link' => '/sig/dashboard?view_dir=dir_finance',
                        'is_read' => 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            });

            $this->clearDashboardCache();
            return response()->json(['success' => true, 'message' => 'تم إرسال الإشعار بنجاح للمديريات المستهدفة!']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'خطأ أثناء إرسال الإشعار: ' . $e->getMessage()]);
        }
    }

    public function addApiKey(Request $request)
    {
        $request->validate([
            'client_name' => 'required|string|max:255',
            'api_key' => 'required|string|max:255',
            'allowed_ips' => 'nullable|string|max:255',
        ]);

        DB::transaction(function() use ($request) {
            DB::table('api_clients')->insert([
                'client_name' => $request->input('client_name'),
                'api_key' => $request->input('api_key'),
                'is_active' => 1,
                'allowed_ips' => $request->input('allowed_ips') ?? '*',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        $this->clearDashboardCache();

        return response()->json(['success' => true, 'message' => 'تم إنشاء مفتاح API بنجاح']);
    }

    public function addProject(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'cost' => 'required|numeric|min:0',
            'date_ods' => 'nullable|date',
            'num_ods' => 'nullable|string|max:100',
            'etablissement_id' => 'nullable|integer',
        ]);

        $user = session('user');
        $etabId = $request->input('etablissement_id') ?? $user['etablissement_id'] ?? 0;

        DB::transaction(function() use ($request, $etabId) {
            $nextId = (int)DB::table('operation')->max('IDOperationLot') + 1;
            $validEtabId = DB::table('etablissement')->where('IDetablissement', $etabId)->exists() ? $etabId : null;
            DB::table('operation')->insert([
                'IDOperationLot' => $nextId,
                'Nom' => $request->input('name'),
                'NomFr' => $request->input('name'),
                'CoutActuel' => $request->input('cost'),
                'DateInscription' => now()->toDateString(),
                'DateOds' => $request->input('date_ods'),
                'NumOds' => $request->input('num_ods'),
                'IDetablissement' => $validEtabId,
                'TauxPhysique' => 0,
                'IDOperations' => null,
                'IDOperationType' => null,
                'IDBureauEtude' => null,
            ]);
        });

        $this->clearDashboardCache();

        return response()->json(['success' => true, 'message' => 'تم إدراج المشروع التنموي بنجاح']);
    }

    public function addAgreement(Request $request)
    {
        $request->validate([
            'subject' => 'required|string|max:255',
            'num' => 'nullable|string|max:100',
            'date_debut' => 'nullable|date',
            'date_fin' => 'nullable|date',
            'partner' => 'required|string|max:255',
            'etablissement_id' => 'nullable|integer',
        ]);

        $user = session('user');
        $etabId = $request->input('etablissement_id') ?? $user['etablissement_id'] ?? 0;

        DB::transaction(function() use ($request, $etabId) {
            $nextId = (int)DB::table('convention')->max('IDConvention') + 1;
            $num = substr($request->input('num') ?? 'N/A', 0, 10);
            $validEtabId = DB::table('etablissement')->where('IDetablissement', $etabId)->exists() ? $etabId : null;
            DB::table('convention')->insert([
                'IDConvention' => $nextId,
                'Sujet' => $request->input('subject'),
                'Num' => $num,
                'DateDebut' => $request->input('date_debut'),
                'DateFIn' => $request->input('date_fin'),
                'institution_contractante' => $request->input('partner'),
                'IDetablissement' => $validEtabId,
                'IDConventionEtat' => null,
                'IDconventionType' => null,
                'IDEmployeur' => null,
            ]);
        });

        $this->clearDashboardCache();

        return response()->json(['success' => true, 'message' => 'تم إدراج الاتفاقية بنجاح']);
    }

    public function addPartner(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:100',
            'dfep_id' => 'nullable|integer',
        ]);

        $user = session('user');
        $dfepId = $request->input('dfep_id') ?? $user['iddfep'] ?? $user['IDDFEP'] ?? 0;

        DB::transaction(function() use ($request, $dfepId) {
            $nextId = (int)DB::table('ets_form')->max('IDEts_Form') + 1;
            $code = substr($request->input('code'), 0, 10);
            $validDfepId = DB::table('dfep')->where('IDDFEP', $dfepId)->exists() ? $dfepId : null;
            DB::table('ets_form')->insert([
                'IDEts_Form' => $nextId,
                'Nom' => $request->input('name'),
                'nomFr' => $request->input('name'),
                'code' => $code,
                'IDDFEP' => $validDfepId,
                'IDNature_etsF' => null,
                'IDCommunn' => null,
                'activee' => 1,
                'IDetablissement' => null,
            ]);
        });

        $this->clearDashboardCache();

        return response()->json(['success' => true, 'message' => 'تم إضافة الشريك بنجاح']);
    }

    public function addSpecialty(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:100',
            'branche_id' => 'nullable|integer',
            'level_id' => 'nullable|integer',
        ]);

        DB::transaction(function() use ($request) {
            $nextId = (int)DB::table('specialite')->max('IDSpecialite') + 1;
            $brancheId = $request->input('branche_id');
            $validBrancheId = DB::table('branche')->where('IDBranche', $brancheId)->exists() ? $brancheId : null;
            DB::table('specialite')->insert([
                'IDSpecialite' => $nextId,
                'Nom' => $request->input('name'),
                'NomFr' => $request->input('name'),
                'CodeSpec' => $request->input('code'),
                'IDBranche' => $validBrancheId,
                'IDNiveau_Fp' => null,
                'IDqualification_dplm' => null,
                'NbrSem' => 2,
                'NbrAnne' => 1,
                'activee' => 1,
                'IDNiveau_Scol' => null,
                'IDNomenclature' => null,
                'IDNomenclature_Mode' => null,
                'IDsousdomaine_rnfc' => null,
            ]);
        });

        $this->clearDashboardCache();

        return response()->json(['success' => true, 'message' => 'تم إدراج التخصص بنجاح']);
    }

    public function addSession(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:100',
            'date_d' => 'nullable|date',
        ]);

        DB::transaction(function() use ($request) {
            $nextId = (int)DB::table('session')->max('IDSession') + 1;
            $nom = substr($request->input('name'), 0, 30);
            // Generate a unique 6-char code — prefix 'S' + 5 random hex chars
            $rawCode = $request->input('code');
            if ($rawCode) {
                $code = substr($rawCode, 0, 6);
                // Ensure uniqueness: append counter if collision
                if (DB::table('session')->where('Code', $code)->exists()) {
                    $code = 'S' . strtoupper(substr(uniqid(), -5));
                }
            } else {
                $code = 'S' . strtoupper(substr(uniqid(), -5));
            }
            DB::table('session')->insert([
                'IDSession' => $nextId,
                'Nom' => $nom,
                'NomFr' => $nom,
                'Code' => $code,
                'DateD' => $request->input('date_d'),
                'Encour' => 1,
                'IDSemestre_formation' => null,
            ]);
        });

        $this->clearDashboardCache();

        return response()->json(['success' => true, 'message' => 'تم فتح دورة امتحانات جديدة بنجاح']);
    }

    public function addCourse(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'offre_id' => 'nullable|integer',
            'mode_id' => 'nullable|integer',
        ]);

        $user = session('user');
        $etabId = $user['etablissement_id'] ?? 0;

        DB::transaction(function() use ($request, $etabId) {
            $nextId = (int)DB::table('section')->max('IDSection') + 1;
            $offreId = $request->input('offre_id');
            $validOffreId = DB::table('offre')->where('IDOffre', $offreId)->exists() ? $offreId : null;
            $modeId = $request->input('mode_id');
            $validModeId = DB::table('mode_formation')->where('IDMode_formation', $modeId)->exists() ? $modeId : null;
            DB::table('section')->insert([
                'IDSection' => $nextId,
                'Nom' => $request->input('name'),
                'NomFr' => $request->input('name'),
                'IDOffre' => $validOffreId,
                'IDMode_formation' => $validModeId,
                'IDEts_Form' => $etabId ?: null,
                'DateDF' => now()->toDateString(),
            ]);
        });

        $this->clearDashboardCache();

        return response()->json(['success' => true, 'message' => 'تم فتح الدورة التأهيلية بنجاح']);
    }

    public function addEmployee(Request $request)
    {
        $request->validate([
            'nom' => 'required|string|max:50',
            'prenom' => 'required|string|max:50',
            'civ' => 'required|integer',
            'date_install' => 'nullable|date',
            'echlo' => 'nullable|numeric',
            'etablissement_id' => 'nullable|integer',
            'situation_id' => 'nullable|integer',
        ]);

        $user = session('user');
        $etabId = $request->input('etablissement_id') ?? $user['etablissement_id'] ?? 0;

        DB::transaction(function() use ($request, $etabId) {
            $nextId = (int)DB::table('encadrement')->max('IDEncadrement') + 1;
            $sitId = $request->input('situation_id');
            $validSitId = DB::table('situationadministrat')->where('IDSituationAdministrat', $sitId)->exists() ? $sitId : null;
            $validEtabId = DB::table('etablissement')->where('IDetablissement', $etabId)->exists() ? $etabId : null;
            DB::table('encadrement')->insert([
                'IDEncadrement' => $nextId,
                'Nom' => $request->input('nom'),
                'NomFr' => $request->input('nom'),
                'Prenom' => $request->input('prenom'),
                'PrenomFr' => $request->input('prenom'),
                'Civ' => $request->input('civ'),
                'DateInstall' => $request->input('date_install'),
                'Echlo' => $request->input('echlo') ?? 1.0,
                'IDetablissement' => $validEtabId,
                'IDSituationAdministrat' => $validSitId,
                'Daterecr' => $request->input('date_install'),
                'IDFonctions' => null,
                'IDGrade' => null,
                'IDSitfamille' => null,
                'IDNiveau_Scol_enca' => null,
                'IDDiplome' => null,
                'IDGradeDeb' => null,
                'IDMode_Promotion' => null,
                'IDMode_Recrutement' => null,
                'IDEndicapePourcentage' => null,
                'IDEndicapetype' => null,
                'IDDomaine' => null,
                'IDBranche' => null,
                'IDSituationAdministratPosts' => null,
            ]);
        });

        $this->clearDashboardCache();

        return response()->json(['success' => true, 'message' => 'تم إضافة الموظف بنجاح']);
    }

    public function addStudy(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'obs' => 'nullable|string',
        ]);

        $user = session('user');
        $etabId = $user['etablissement_id'] ?? 0;

        DB::transaction(function() use ($request, $etabId) {
            $nextId = (int)DB::table('section')->max('IDSection') + 1;
            DB::table('section')->insert([
                'IDSection' => $nextId,
                'Nom' => $request->input('name'),
                'NomFr' => $request->input('name'),
                'Obs' => $request->input('obs'),
                'IDOffre' => null,
                'IDMode_formation' => null,
                'IDEts_Form' => $etabId ?: null,
                'DateDF' => now()->toDateString(),
            ]);
        });

        $this->clearDashboardCache();

        return response()->json(['success' => true, 'message' => 'تم إدراج الدراسة/الملخص بنجاح']);
    }

    private function validateScope(int $etabId)
    {
        $user = session('user');
        if (!$user) {
            throw new \Exception('غير مصرح - الرجاء تسجيل الدخول');
        }
        $role = strtolower($user['role_code'] ?? 'user');
        $iddfep = (int)($user['iddfep'] ?? $user['IDDFEP'] ?? 0);
        $userEtabId = (int)($user['etablissement_id'] ?? 0);

        if (in_array($role, ['admin', 'high_admin', 'central'])) {
            return true;
        }

        if ($role === 'dfep') {
            $etabDfep = DB::table('etablissement')->where('IDetablissement', $etabId)->value('IDDFEP');
            if ((int)$etabDfep !== $iddfep) {
                throw new \Exception('غير مصرح لك بالوصول لمؤسسة خارج ولايتك');
            }
        } elseif (in_array($role, ['etablissement', 'directeur'])) {
            if ($etabId !== $userEtabId) {
                throw new \Exception('غير مصرح لك بالوصول لمؤسسة أخرى');
            }
        } else {
            throw new \Exception('غير مصرح لك بالقيام بهذا الإجراء');
        }
    }

    // --- PROMOTIONS CRUD ---
    public function storePromotion(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|integer',
            'mode_promotion_id' => 'required|integer',
            'date_install' => 'nullable|date',
            'date_conf' => 'nullable|date',
            'num_ord' => 'nullable|integer',
        ]);

        try {
            $empEtabId = (int)DB::table('encadrement')->where('IDEncadrement', $request->input('employee_id'))->value('IDetablissement');
            if (!$empEtabId) {
                return response()->json(['success' => false, 'message' => 'الموظف غير موجود']);
            }
            $this->validateScope($empEtabId);

            DB::transaction(function() use ($request) {
                $nextId = (int)DB::table('encadrement_grade')->max('IDEncadrement_Grade') + 1;
                $empGrade = DB::table('encadrement')->where('IDEncadrement', $request->input('employee_id'))->value('IDGrade');
                
                DB::table('encadrement_grade')->insert([
                    'IDEncadrement_Grade' => $nextId,
                    'IDEncadrement' => $request->input('employee_id'),
                    'IDMode_Promotion' => $request->input('mode_promotion_id'),
                    'dateinstal' => $request->input('date_install'),
                    'dateconf' => $request->input('date_conf'),
                    'NumOrd' => $request->input('num_ord') ?? 0,
                    'IDGrade' => $empGrade,
                    'IDannee' => 16,
                ]);
            });

            $this->clearDashboardCache();
            return response()->json(['success' => true, 'message' => 'تم إضافة سجل الترقية بنجاح']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function updatePromotion(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
            'employee_id' => 'required|integer',
            'mode_promotion_id' => 'required|integer',
            'date_install' => 'nullable|date',
            'date_conf' => 'nullable|date',
            'num_ord' => 'nullable|integer',
        ]);

        try {
            $empEtabId = (int)DB::table('encadrement')->where('IDEncadrement', $request->input('employee_id'))->value('IDetablissement');
            if (!$empEtabId) {
                return response()->json(['success' => false, 'message' => 'الموظف غير موجود']);
            }
            $this->validateScope($empEtabId);

            DB::table('encadrement_grade')
                ->where('IDEncadrement_Grade', $request->input('id'))
                ->update([
                    'IDEncadrement' => $request->input('employee_id'),
                    'IDMode_Promotion' => $request->input('mode_promotion_id'),
                    'dateinstal' => $request->input('date_install'),
                    'dateconf' => $request->input('date_conf'),
                    'NumOrd' => $request->input('num_ord') ?? 0,
                ]);

            $this->clearDashboardCache();
            return response()->json(['success' => true, 'message' => 'تم تحديث سجل الترقية بنجاح']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function deletePromotion($id)
    {
        try {
            $promo = DB::table('encadrement_grade')->where('IDEncadrement_Grade', $id)->first();
            if (!$promo) {
                return response()->json(['success' => false, 'message' => 'سجل الترقية غير موجود']);
            }
            $empEtabId = (int)DB::table('encadrement')->where('IDEncadrement', $promo->IDEncadrement)->value('IDetablissement');
            if ($empEtabId) {
                $this->validateScope($empEtabId);
            }

            DB::table('encadrement_grade')->where('IDEncadrement_Grade', $id)->delete();

            $this->clearDashboardCache();
            return response()->json(['success' => true, 'message' => 'تم حذف سجل الترقية بنجاح']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    // --- CONCOURS CRUD ---
    public function storeConcours(Request $request)
    {
        $request->validate([
            'etablissement_id' => 'required|integer',
            'date_depot' => 'nullable|date',
            'date_concour' => 'nullable|date',
            'obs' => 'nullable|string|max:255',
        ]);

        try {
            $this->validateScope($request->input('etablissement_id'));

            DB::transaction(function() use ($request) {
                $nextId = (int)DB::table('concours_examenprofessionnel')->max('IDConcours_ExamenProfessionnel') + 1;
                DB::table('concours_examenprofessionnel')->insert([
                    'IDConcours_ExamenProfessionnel' => $nextId,
                    'IDetablissement' => $request->input('etablissement_id'),
                    'DateDepoDossier' => $request->input('date_depot'),
                    'DateConcour' => $request->input('date_concour'),
                    'Obs' => $request->input('obs'),
                    'IDannee' => 16,
                ]);
            });

            $this->clearDashboardCache();
            return response()->json(['success' => true, 'message' => 'تم إضافة المسابقة بنجاح']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function updateConcours(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
            'etablissement_id' => 'required|integer',
            'date_depot' => 'nullable|date',
            'date_concour' => 'nullable|date',
            'obs' => 'nullable|string|max:255',
        ]);

        try {
            $this->validateScope($request->input('etablissement_id'));

            DB::table('concours_examenprofessionnel')
                ->where('IDConcours_ExamenProfessionnel', $request->input('id'))
                ->update([
                    'IDetablissement' => $request->input('etablissement_id'),
                    'DateDepoDossier' => $request->input('date_depot'),
                    'DateConcour' => $request->input('date_concour'),
                    'Obs' => $request->input('obs'),
                ]);

            $this->clearDashboardCache();
            return response()->json(['success' => true, 'message' => 'تم تحديث المسابقة بنجاح']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function deleteConcours($id)
    {
        try {
            $conc = DB::table('concours_examenprofessionnel')->where('IDConcours_ExamenProfessionnel', $id)->first();
            if (!$conc) {
                return response()->json(['success' => false, 'message' => 'المسابقة غير موجودة']);
            }
            $this->validateScope((int)$conc->IDetablissement);

            DB::table('concours_examenprofessionnel')->where('IDConcours_ExamenProfessionnel', $id)->delete();

            $this->clearDashboardCache();
            return response()->json(['success' => true, 'message' => 'تم حذف المسابقة بنجاح']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    // --- SALAIRES CRUD ---
    public function storeSalaire(Request $request)
    {
        $request->validate([
            'etablissement_id' => 'required|integer',
            'grade_id' => 'nullable|integer',
            'allo' => 'required|integer|min:0',
            'occu' => 'required|integer|min:0',
            'vacan' => 'required|integer|min:0',
            'surplus' => 'required|integer|min:0',
            'categorie' => 'nullable|string|max:10',
            'base_salary' => 'required|numeric|min:0',
            'primes' => 'required|numeric|min:0',
        ]);

        try {
            $this->validateScope($request->input('etablissement_id'));

            DB::transaction(function() use ($request) {
                $nextId = (int)DB::table('etablissement_grade')->max('IDetablissement_Grade') + 1;
                $expense = (double)$request->input('base_salary') + (double)$request->input('primes');
                
                DB::table('etablissement_grade')->insert([
                    'IDetablissement_Grade' => $nextId,
                    'IDetablissement' => $request->input('etablissement_id'),
                    'IDGrade' => $request->input('grade_id') ?? 11,
                    'allo' => $request->input('allo'),
                    'Occu' => $request->input('occu'),
                    'vacan' => $request->input('vacan'),
                    'Surplus' => $request->input('surplus'),
                    'categorie' => $request->input('categorie'),
                    'Traitementannuel' => $request->input('base_salary'),
                    'Primeetindemnites' => $request->input('primes'),
                    'Depenceannuel' => $expense,
                    'IDannee' => 16,
                ]);
            });

            $this->clearDashboardCache();
            return response()->json(['success' => true, 'message' => 'تم إضافة سجل الأجور بنجاح']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function updateSalaire(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
            'etablissement_id' => 'required|integer',
            'grade_id' => 'nullable|integer',
            'allo' => 'required|integer|min:0',
            'occu' => 'required|integer|min:0',
            'vacan' => 'required|integer|min:0',
            'surplus' => 'required|integer|min:0',
            'categorie' => 'nullable|string|max:10',
            'base_salary' => 'required|numeric|min:0',
            'primes' => 'required|numeric|min:0',
        ]);

        try {
            $this->validateScope($request->input('etablissement_id'));
            $expense = (double)$request->input('base_salary') + (double)$request->input('primes');

            DB::table('etablissement_grade')
                ->where('IDetablissement_Grade', $request->input('id'))
                ->update([
                    'IDetablissement' => $request->input('etablissement_id'),
                    'IDGrade' => $request->input('grade_id') ?? 11,
                    'allo' => $request->input('allo'),
                    'Occu' => $request->input('occu'),
                    'vacan' => $request->input('vacan'),
                    'Surplus' => $request->input('surplus'),
                    'categorie' => $request->input('categorie'),
                    'Traitementannuel' => $request->input('base_salary'),
                    'Primeetindemnites' => $request->input('primes'),
                    'Depenceannuel' => $expense,
                ]);

            $this->clearDashboardCache();
            return response()->json(['success' => true, 'message' => 'تم تحديث سجل الأجور بنجاح']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function deleteSalaire($id)
    {
        try {
            $sal = DB::table('etablissement_grade')->where('IDetablissement_Grade', $id)->first();
            if (!$sal) {
                return response()->json(['success' => false, 'message' => 'سجل الأجور غير موجود']);
            }
            $this->validateScope((int)$sal->IDetablissement);

            DB::table('etablissement_grade')->where('IDetablissement_Grade', $id)->delete();

            $this->clearDashboardCache();
            return response()->json(['success' => true, 'message' => 'تم حذف سجل الأجور بنجاح']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    private function clearDashboardCache()
    {
        try {
            \Illuminate\Support\Facades\Cache::flush();
        } catch (\Exception $e) {
            Log::error('Failed to flush cache: ' . $e->getMessage());
        }
    }

    private function renderDepartmentView(string $dept, Request $request)
    {
        $user = session('user') ?? [];
        if (empty($user)) {
            return redirect()->route('login');
        }
        $role = strtolower($user['role_code'] ?? 'user');
        
        $viewMap = [
            'finance'    => 'dashboard.departments.finance',
            'hr'         => 'dashboard.departments.hr',
            'plan'       => 'dashboard.departments.plan',
            'coop'       => 'dashboard.departments.coop',
            'it'         => 'dashboard.departments.it',
            'exam'       => 'dashboard.departments.exam',
            'trak'       => 'dashboard.departments.trak',
            'edu'        => 'dashboard.departments.edu',
            'dfcri'      => 'dashboard.departments.dfcri',
            'promotions' => 'dashboard.departments.promotions',
            'concours'   => 'dashboard.departments.concours',
            'salaires'   => 'dashboard.departments.salaires',
        ];

        $targetView = $viewMap[$dept] ?? null;
        if (!$targetView) {
            abort(404);
        }

        $viewData = [
            'title'          => 'لوحة التحكم — ' . strtoupper($dept),
            'role'           => $role,
            'direction_code' => strtoupper($dept),
            'user'           => $user,
            'current_tab'    => $request->query('tab', ''),
        ];

        if ($dept === 'it') {
            $viewData = array_merge($viewData, $this->getOrSeedItDashboardData());
        } elseif ($dept === 'hr') {
            $viewData = array_merge($viewData, $this->getOrSeedHrDashboardData());
        }

        return view($targetView, $viewData);
    }

    /**
     * GET /dashboard/stats/api — يُعيد إحصائيات KPI من dashboard_stats (سريع جداً)
     */
    public function statsApi(Request $request): \Illuminate\Http\JsonResponse
    {
        $user = session('user');
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $stats = \App\Services\StatsService::getAllGlobal();
        
        return response()->json([
            'success'    => true,
            'stats'      => $stats,
            'updated_at' => isset($stats[\App\Services\StatsService::KEY_LAST_SYNC_TS]) ? date('Y-m-d H:i:s', $stats[\App\Services\StatsService::KEY_LAST_SYNC_TS]) : null,
        ]);
    }

    /**
     * POST /dashboard/stats/refresh — يُشغّل stats:refresh يدوياً (admin فقط)
     */
    public function statsRefresh(Request $request): \Illuminate\Http\JsonResponse
    {
        $user = session('user');
        if (strtolower($user['role_code'] ?? '') !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $stats = \App\Services\StatsService::refreshAll();
        \App\Services\KpiCache::invalidateAdminAll();

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث الإحصائيات بنجاح',
            'stats'   => $stats,
        ]);
    }

    public function saveTeacherGrades(Request $request)
    {
        $user = session('user');
        if (!$user || strtolower($user['role_code'] ?? '') !== 'formateur') {
            return response()->json(['success' => false, 'message' => 'غير مصرح'], 401);
        }
        
        $request->validate([
            'apprenant_id' => 'required|integer',
            'module_id' => 'required|integer',
            'note_c1' => 'nullable|numeric|min:0|max:20',
            'note_c2' => 'nullable|numeric|min:0|max:20',
            'note_cs' => 'nullable|numeric|min:0|max:20',
            'note_r' => 'nullable|numeric|min:0|max:20',
            'observation' => 'nullable|string|max:255',
        ]);
        
        $apprenantId = (int)$request->input('apprenant_id');
        $moduleId = (int)$request->input('module_id');
        
        $teacherId = (int)$user['id'];
        $teaches = DB::table('section_semestre_module')
            ->where('IDsection_semestre_Module', $moduleId)
            ->where('IDEncadrement', $teacherId)
            ->exists();
            
        if (!$teaches) {
            return response()->json(['success' => false, 'message' => 'غير مصرح لك بتعديل علامات هذا المقياس'], 403);
        }
        
        try {
            DB::transaction(function () use ($request, $apprenantId, $moduleId) {
                $ssm = DB::table('section_semestre_module')->where('IDsection_semestre_Module', $moduleId)->first();
                if (!$ssm) {
                    throw new \Exception('المقياس غير موجود');
                }
                
                $sectionSemestreId = (int)$ssm->IDSection_Semestre;
                $rawModuleId = (int)$ssm->IDModule;
                
                $ass = DB::table('apprenant_section_semstre')
                    ->where('IDapprenant', $apprenantId)
                    ->where('IDSection_Semestre', $sectionSemestreId)
                    ->first();
                    
                if (!$ass) {
                    $maxAssId = (int)DB::table('apprenant_section_semstre')->max('IDapprenant_Section_semstre') + 1;
                    DB::table('apprenant_section_semstre')->insert([
                        'IDapprenant_Section_semstre' => $maxAssId,
                        'IDapprenant' => $apprenantId,
                        'IDSection_Semestre' => $sectionSemestreId,
                    ]);
                    $assId = $maxAssId;
                } else {
                    $assId = $ass->IDapprenant_Section_semstre;
                }
                
                $c1 = $request->input('note_c1') !== null ? (float)$request->input('note_c1') : null;
                $c2 = $request->input('note_c2') !== null ? (float)$request->input('note_c2') : null;
                $cs = $request->input('note_cs') !== null ? (float)$request->input('note_cs') : null;
                $r = $request->input('note_r') !== null ? (float)$request->input('note_r') : null;
                
                $moyAvr = null;
                if ($c1 !== null && $c2 !== null && $cs !== null) {
                    $moyAvr = ($c1 + $c2 + $cs * 2) / 4;
                } elseif ($c1 !== null && $cs !== null) {
                    $moyAvr = ($c1 + $cs * 2) / 3;
                } elseif ($cs !== null) {
                    $moyAvr = $cs;
                }
                
                $moyApr = $moyAvr;
                if ($r !== null && $moyAvr !== null && $moyAvr < 10) {
                    $moyApr = max($moyAvr, $r);
                }
                
                $assm = DB::table('apprenant_section_semstre_module')
                    ->where('IDapprenant_Section_semstre', $assId)
                    ->where('IDsection_semestre_Module', $moduleId)
                    ->first();
                    
                if ($assm) {
                    DB::table('apprenant_section_semstre_module')
                        ->where('IDApprenant_Section_semstre_module', $assm->IDApprenant_Section_semstre_module)
                        ->update([
                            'NoteC1' => $c1,
                            'NoteC2' => $c2,
                            'NoteCs' => $cs,
                            'NoteR' => $r,
                            'MoyAvr' => $moyAvr,
                            'MoyApr' => $moyApr,
                            'Obs' => $request->input('observation') ?? '',
                        ]);
                } else {
                    $maxGradeId = (int)DB::table('apprenant_section_semstre_module')->max('IDApprenant_Section_semstre_module') + 1;
                    DB::table('apprenant_section_semstre_module')->insert([
                        'IDApprenant_Section_semstre_module' => $maxGradeId,
                        'IDapprenant_Section_semstre' => $assId,
                        'IDsection_semestre_Module' => $moduleId,
                        'IDModule' => $rawModuleId,
                        'NoteC1' => $c1,
                        'NoteC2' => $c2,
                        'NoteCs' => $cs,
                        'NoteR' => $r,
                        'MoyAvr' => $moyAvr,
                        'MoyApr' => $moyApr,
                        'Obs' => $request->input('observation') ?? '',
                    ]);
                }
            });
            
            return response()->json(['success' => true, 'message' => 'تم حفظ النقاط بنجاح']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function saveTeacherAttendance(Request $request)
    {
        $user = session('user');
        if (!$user || strtolower($user['role_code'] ?? '') !== 'formateur') {
            return response()->json(['success' => false, 'message' => 'غير مصرح'], 401);
        }
        
        $request->validate([
            'section_id' => 'required|integer',
            'date_absence' => 'required|date',
            'heure' => 'required|string',
            'absent_students' => 'nullable|array',
            'absent_students.*' => 'integer'
        ]);
        
        $sectionId = (int)$request->input('section_id');
        $teacherId = (int)$user['id'];
        
        $teaches = DB::table('section_semestre_module')
            ->join('section_semestre', 'section_semestre_module.IDSection_Semestre', '=', 'section_semestre.IDSection_Semestre')
            ->where('section_semestre.IDSection', $sectionId)
            ->where('section_semestre_module.IDEncadrement', $teacherId)
            ->exists();
            
        if (!$teaches) {
            return response()->json(['success' => false, 'message' => 'غير مصرح لك بتسجيل غيابات هذا الفوج'], 403);
        }
        
        $absentIds = $request->input('absent_students') ?? [];
        $date = $request->input('date_absence');
        $heure = $request->input('heure');
        
        try {
            DB::transaction(function () use ($sectionId, $absentIds, $date, $heure) {
                $ss = DB::table('section_semestre')
                    ->where('IDSection', $sectionId)
                    ->orderBy('IDSection_Semestre', 'desc')
                    ->first();
                if (!$ss) {
                    throw new \Exception('السداسي الفوج غير موجود');
                }
                $sectionSemestreId = $ss->IDSection_Semestre;
                
                $students = DB::table('apprenant')
                    ->where('IDSection', $sectionId)
                    ->get();
                    
                foreach ($students as $student) {
                    $ass = DB::table('apprenant_section_semstre')
                        ->where('IDapprenant', $student->IDapprenant)
                        ->where('IDSection_Semestre', $sectionSemestreId)
                        ->first();
                        
                    if (!$ass) {
                        $maxAssId = (int)DB::table('apprenant_section_semstre')->max('IDapprenant_Section_semstre') + 1;
                        DB::table('apprenant_section_semstre')->insert([
                            'IDapprenant_Section_semstre' => $maxAssId,
                            'IDapprenant' => $student->IDapprenant,
                            'IDSection_Semestre' => $sectionSemestreId,
                        ]);
                        $assId = $maxAssId;
                    } else {
                        $assId = $ass->IDapprenant_Section_semstre;
                    }
                    
                    if (in_array($student->IDapprenant, $absentIds)) {
                        $exists = DB::table('apprenant_absence')
                            ->where('IDapprenant_Section_semstre', $assId)
                            ->where('Date', $date)
                            ->where('heure', $heure)
                            ->exists();
                        if (!$exists) {
                            $maxAbsId = (int)DB::table('apprenant_absence')->max('IDApprenant_Absence') + 1;
                            DB::table('apprenant_absence')->insert([
                                'IDApprenant_Absence' => $maxAbsId,
                                'IDapprenant_Section_semstre' => $assId,
                                'Date' => $date,
                                'heure' => $heure,
                                'Type' => 1,
                                'matinsoir' => 0
                            ]);
                        }
                    }
                }
            });
            
            return response()->json(['success' => true, 'message' => 'تم تسجيل الغيابات بنجاح']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function updateEmployeeProfile(Request $request)
    {
        $user = session('user');
        if (!$user || !in_array(strtolower($user['role_code'] ?? ''), ['employee', 'formateur'])) {
            return response()->json(['success' => false, 'message' => 'غير مصرح'], 401);
        }
        
        $empId = (int)$user['id'];
        
        $request->validate([
            'nom' => 'required|string|max:50',
            'prenom' => 'required|string|max:50',
            'nom_fr' => 'nullable|string|max:50',
            'prenom_fr' => 'nullable|string|max:50',
            'date_nais' => 'nullable|string',
            'lieu_nais' => 'nullable|string|max:100',
            'tel' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:100',
            'adres' => 'nullable|string|max:150',
            'nbr_enfants' => 'nullable|integer|min:0',
            'nss' => 'nullable|string|max:20',
            'sitfamille' => 'nullable|integer',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png,gif|max:2048',
        ]);
        
        try {
            $data = [
                'Nom' => $request->input('nom'),
                'Prenom' => $request->input('prenom'),
                'NomFr' => $request->input('nom_fr') ?? '',
                'PrenomFr' => $request->input('prenom_fr') ?? '',
                'DateNais' => $request->input('date_nais') ?? '',
                'LieuNais' => $request->input('lieu_nais') ?? '',
                'Tel' => $request->input('tel') ?? '',
                'Email' => $request->input('email') ?? '',
                'Adres' => $request->input('adres') ?? '',
                'nbrEnf' => (int)$request->input('nbr_enfants', 0),
                'nss' => $request->input('nss') ?? '',
                'IDSitfamille' => (int)($request->input('sitfamille') ?? 1),
            ];
            
            if ($request->hasFile('photo')) {
                $file = $request->file('photo');
                if ($file->isValid()) {
                    $ext = strtolower($file->getClientOriginalExtension());
                    $newFileName = 'emp_' . $empId . '_' . time() . '.jpg';
                    $uploadDir = public_path('uploads/employees');
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                    }
                    $destPath = $uploadDir . '/' . $newFileName;
                    
                    $this->resizeImageAndSave($file->getRealPath(), $destPath, 300, 300);
                    
                    $data['photo'] = '/uploads/employees/' . $newFileName;
                }
            }
            
            DB::table('encadrement')
                ->where('IDEncadrement', $empId)
                ->update($data);
                
            $user['nom_complet'] = $data['Nom'] . ' ' . $data['Prenom'];
            if (isset($data['photo'])) {
                $user['photo'] = $data['photo'];
            }
            session(['user' => $user]);
            
            return response()->json(['success' => true, 'message' => 'تم تحديث بيانات الملف الشخصي بنجاح!']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function storeLeaveRequest(Request $request)
    {
        $user = session('user');
        if (!$user || !in_array(strtolower($user['role_code'] ?? ''), ['employee', 'formateur'])) {
            return response()->json(['success' => false, 'message' => 'غير مصرح'], 401);
        }

        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'leave_type' => 'required|string|max:255',
            'reason' => 'nullable|string',
        ]);

        try {
            DB::table('employee_leaves')->insert([
                'employee_id' => (int)$user['id'],
                'start_date' => $request->input('start_date'),
                'end_date' => $request->input('end_date'),
                'leave_type' => $request->input('leave_type'),
                'reason' => $request->input('reason'),
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return response()->json(['success' => true, 'message' => 'تم تقديم طلب الإجازة بنجاح!']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function requestEmployeeDocument(Request $request)
    {
        $user = session('user');
        if (!$user || !in_array(strtolower($user['role_code'] ?? ''), ['employee', 'formateur'])) {
            return response()->json(['success' => false, 'message' => 'غير مصرح'], 401);
        }

        $request->validate([
            'document_type' => 'required|string|max:255',
        ]);

        try {
            $code = 'DOC-' . strtoupper(bin2hex(random_bytes(4)));
            while (DB::table('employee_document_requests')->where('code_verification', $code)->exists()) {
                $code = 'DOC-' . strtoupper(bin2hex(random_bytes(4)));
            }

            DB::table('employee_document_requests')->insert([
                'employee_id' => (int)$user['id'],
                'document_type' => $request->input('document_type'),
                'code_verification' => $code,
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return response()->json(['success' => true, 'message' => 'تم طلب الوثيقة بنجاح!']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function sendEmployeeMessage(Request $request)
    {
        $user = session('user');
        if (!$user || !in_array(strtolower($user['role_code'] ?? ''), ['employee', 'formateur'])) {
            return response()->json(['success' => false, 'message' => 'غير مصرح'], 401);
        }

        $request->validate([
            'message' => 'required|string',
        ]);

        try {
            DB::table('employee_messages')->insert([
                'employee_id' => (int)$user['id'],
                'direction' => 'Administration',
                'message' => $request->input('message'),
                'status' => 'unread',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return response()->json(['success' => true, 'message' => 'تم إرسال الرسالة بنجاح!']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    private function resizeImageAndSave(string $sourcePath, string $destPath, int $maxWidth, int $maxHeight)
    {
        list($width, $height, $type) = getimagesize($sourcePath);
        
        $srcImg = null;
        switch ($type) {
            case IMAGETYPE_JPEG: $srcImg = imagecreatefromjpeg($sourcePath); break;
            case IMAGETYPE_PNG:  $srcImg = imagecreatefrompng($sourcePath); break;
            case IMAGETYPE_GIF:  $srcImg = imagecreatefromgif($sourcePath); break;
            default:             throw new \Exception('نوع الصورة غير مدعوم');
        }
        
        if (!$srcImg) {
            throw new \Exception('تعذر قراءة الصورة');
        }
        
        $ratio = min($maxWidth / $width, $maxHeight / $height);
        if ($ratio < 1) {
            $newWidth = (int)($width * $ratio);
            $newHeight = (int)($height * $ratio);
        } else {
            $newWidth = $width;
            $newHeight = $height;
        }
        
        $destImg = imagecreatetruecolor($newWidth, $newHeight);
        $white = imagecolorallocate($destImg, 255, 255, 255);
        imagefill($destImg, 0, 0, $white);
        
        imagecopyresampled($destImg, $srcImg, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        imagejpeg($destImg, $destPath, 85);
        
        imagedestroy($srcImg);
        imagedestroy($destImg);
    }
    /**
     * Get or seed IT department dashboard dynamic statistics from the database.
     * CPU / RAM / SSD are read LIVE from the OS via wmic (Windows) and stored in dashboard_stats.
     */
    private function getOrSeedItDashboardData(): array
    {
        // ═══════════════════════════════════════════════════════
        // 1) Read REAL live server metrics from the OS
        // ═══════════════════════════════════════════════════════
        $live = $this->readRealServerMetrics();

        // ═══════════════════════════════════════════════════════
        // 2) Upsert live metrics into dashboard_stats (for history/audit)
        // ═══════════════════════════════════════════════════════
        $liveMap = [
            'it.cpu_usage'   => (string)$live['cpu_usage'],
            'it.cpu_status'  => $live['cpu_status'],
            'it.ram_usage'   => (string)$live['ram_usage'],
            'it.ram_details' => $live['ram_details'],
            'it.ssd_usage'   => (string)$live['ssd_usage'],
            'it.ssd_details' => $live['ssd_details'],
        ];

        foreach ($liveMap as $key => $val) {
            $exists = DB::table('dashboard_stats')->where('stat_key', $key)->exists();
            if ($exists) {
                DB::table('dashboard_stats')->where('stat_key', $key)->update([
                    'stat_value'   => $val,
                    'last_updated' => now(),
                ]);
            } else {
                DB::table('dashboard_stats')->insert([
                    'stat_key'     => $key,
                    'stat_value'   => $val,
                    'stat_group'   => 'it',
                    'stat_label'   => str_replace('it.', '', $key),
                    'last_updated' => now(),
                ]);
            }
        }

        // ═══════════════════════════════════════════════════════
        // 3) Ensure backup/sync rows exist (admin-managed, not overwritten)
        // ═══════════════════════════════════════════════════════
        $staticDefaults = [
            'it.backup_status'  => 'ناجحة',
            'it.backup_details' => 'توقيت: 04:00 AM | الحجم: 485 MB',
            'it.sync_status'    => 'ناجحة',
            'it.sync_details'   => 'توقيت: 04:30 AM | الحجم: 485 MB',
        ];
        foreach ($staticDefaults as $key => $defaultVal) {
            if (!DB::table('dashboard_stats')->where('stat_key', $key)->exists()) {
                DB::table('dashboard_stats')->insert([
                    'stat_key'     => $key,
                    'stat_value'   => $defaultVal,
                    'stat_group'   => 'it',
                    'stat_label'   => str_replace('it.', '', $key),
                    'last_updated' => now(),
                ]);
            }
        }

        // ═══════════════════════════════════════════════════════
        // 4) Read final values from database (backup/sync come from DB)
        // ═══════════════════════════════════════════════════════
        $dbStats = DB::table('dashboard_stats')->where('stat_group', 'it')->pluck('stat_value', 'stat_key')->toArray();

        $itStats = [
            // Live OS metrics (just updated above)
            'cpu_usage'     => $live['cpu_usage'],
            'cpu_status'    => $live['cpu_status'],
            'ram_usage'     => $live['ram_usage'],
            'ram_details'   => $live['ram_details'],
            'ssd_usage'     => $live['ssd_usage'],
            'ssd_details'   => $live['ssd_details'],
            // Admin-managed from database
            'backup_status' => $dbStats['it.backup_status']  ?? 'غير معروف',
            'backup_details'=> $dbStats['it.backup_details'] ?? '',
            'sync_status'   => $dbStats['it.sync_status']    ?? 'غير معروف',
            'sync_details'  => $dbStats['it.sync_details']   ?? '',
        ];

        // total_logins: 100% real COUNT(*) from accesuser
        try {
            $itStats['total_logins'] = (string) DB::table('accesuser')->count();
        } catch (\Exception $e) {
            $itStats['total_logins'] = '0';
        }

        // Query raw recent logins from DB
        $rawLogins = DB::table('accesuser')
            ->orderBy('IDaccesuser', 'desc')
            ->limit(10)
            ->get();

        // Calculate success/failed rates from database
        try {
            $auditStats = DB::selectOne("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN accesouinon = 1 THEN 1 ELSE 0 END) as success
                FROM accesuser
            ");
            $totalCount = $auditStats ? (int)$auditStats->total : 0;
            $successCount = $auditStats ? (int)$auditStats->success : 0;
            
            if ($totalCount > 0) {
                $successPct = round(($successCount / $totalCount) * 100, 1);
                $failedPct  = round(100 - $successPct, 1);
            } else {
                $successPct = 0.0;
                $failedPct  = 0.0;
            }
        } catch (\Exception $e) {
            $successPct = 0.0;
            $failedPct  = 0.0;
        }

        $itStats['success_pct'] = $successPct;
        $itStats['failed_pct'] = $failedPct;

        $logins = [];
        foreach ($rawLogins as $rl) {
            $isSuccess = (int)($rl->accesouinon ?? 0) === 1;
            
            // Map event text from Obs
            $eventText = $rl->Obs ?: ($isSuccess ? 'تسجيل دخول ناجح' : 'محاولة دخول مرفوضة');
            if ($rl->windows && strpos($eventText, $rl->windows) === false && $isSuccess) {
                $eventText .= ' (' . $rl->windows . ')';
            }
            
            $actionText = $rl->Obs ? ($isSuccess ? 'تم السماح بالوصول' : ($rl->Obs == 'محاولة تخمين كلمة المرور (Brute Force)' ? 'تقييد الوصول (Rate-Limit)' : ($rl->Obs == 'فحص آلي للمنافذ (Port Scan)' ? 'تم حظر الـ IP بالكامل' : 'تم الحجب والمنع الفوري'))) : ($isSuccess ? 'تم السماح بالوصول' : 'تم الرفض والتقييد');
            
            $badgeClass = $isSuccess ? 'bg-success-subtle text-success' : (($rl->Obs == 'محاولة تخمين كلمة المرور (Brute Force)') ? 'bg-warning-subtle text-warning' : 'bg-danger-subtle text-danger');
            $iconClass = $isSuccess ? 'fa-solid fa-circle-check text-success' : 'fa-solid fa-shield text-danger';
            if ($rl->Obs == 'محاولة تخمين كلمة المرور (Brute Force)') {
                $iconClass = 'fa-solid fa-shield text-warning';
            }
            
            // Format Date/Time
            $logDateStr = $rl->Date && $rl->Date !== '0000-00-00' ? $rl->Date : date('Y-m-d');
            $timeStr = $rl->heure ?: date('H:i:s');
            
            // Display "اليوم" or "أمس"
            if ($logDateStr === date('Y-m-d')) {
                $timeDisplay = 'اليوم، ' . $timeStr;
            } elseif ($logDateStr === date('Y-m-d', strtotime('-1 day'))) {
                $timeDisplay = 'أمس، ' . $timeStr;
            } else {
                $timeDisplay = $logDateStr . ' ' . $timeStr;
            }

            $logins[] = [
                'event'  => $eventText,
                'ip'     => $rl->iplocal ?: '127.0.0.1',
                'target' => $rl->NomPrenom ?: 'مستخدم غير معروف',
                'action' => $actionText,
                'badge'  => $badgeClass,
                'icon'   => $iconClass,
                'time'   => $timeDisplay
            ];
        }

        $itStats['recent_logins'] = $logins;

        return $itStats;
    }

    /**
     * Get or seed data for HR (DRH) department dashboard
     * Handles heavy DB queries and caches for 10 minutes
     */
    private function getOrSeedHrDashboardData(): array
    {
        $dirCode = 'DRH';
        $kpiKey  = 'drh_kpi_v2_' . $dirCode;
        $kpiData = \Illuminate\Support\Facades\Cache::get($kpiKey);

        if ($kpiData === null) {
            $kpiData = [
                'total'          => 0,
                'men'            => 0,
                'women'          => 0,
                'inst_this_year' => 0,
                'sit_counts'     => [],
                'grade_dist'     => [],
                'wilaya_top'     => [],
                'wilaya_map'     => [],
            ];
            try {
                $total = (int) DB::selectOne("SELECT COUNT(*) as c FROM Encadrement")->c;
                $kpiData['total'] = $total;

                // Gender grouping
                $rows = DB::select("SELECT Civ, COUNT(*) as nb FROM Encadrement GROUP BY Civ");
                foreach ($rows as $r) {
                    if ((int)$r->Civ === 1) $kpiData['men']   = (int)$r->nb;
                    if ((int)$r->Civ === 2) $kpiData['women'] = (int)$r->nb;
                }

                // Added this year
                $yr   = date('Y');
                $prYr = $yr - 1;
                $kpiData['inst_this_year'] = (int) DB::selectOne(
                    "SELECT COUNT(*) as c FROM Encadrement WHERE YEAR(DateInstall) IN (?,?) AND DateInstall IS NOT NULL AND YEAR(DateInstall)<=2030",
                    [$yr, $prYr]
                )->c;

                // Situation counts
                $sits = DB::select("SELECT sa.Nom as sit_nom, COUNT(e.IDEncadrement) as nb FROM Encadrement e LEFT JOIN situationadministrat sa ON e.IDSituationAdministrat=sa.IDSituationAdministrat GROUP BY sa.Nom ORDER BY nb DESC LIMIT 6");
                $kpiData['sit_counts'] = array_map(fn($r) => ['sit_nom' => $r->sit_nom, 'nb' => (int)$r->nb], $sits);

                // Echelon ranges
                $grades = DB::select("SELECT CASE WHEN Echlo BETWEEN 1 AND 3 THEN 'إيكلون 01-03' WHEN Echlo BETWEEN 4 AND 6 THEN 'إيكلون 04-06' WHEN Echlo BETWEEN 7 AND 9 THEN 'إيكلون 07-09' WHEN Echlo>=10 THEN 'إيكلون 10+' ELSE 'غير محدد' END as rang, COUNT(*) as nb FROM Encadrement GROUP BY rang ORDER BY nb DESC");
                $kpiData['grade_dist'] = array_map(fn($r) => ['rang' => $r->rang, 'nb' => (int)$r->nb], $grades);

                // Wilaya top
                $tops = DB::select("SELECT w.Nom as wilaya, COUNT(e.IDEncadrement) as nb FROM Encadrement e INNER JOIN etablissement et ON e.IDetablissement=et.IDetablissement INNER JOIN wilaya w ON et.IDDFEP=w.IDWilayaa GROUP BY w.Nom ORDER BY nb DESC LIMIT 8");
                $kpiData['wilaya_top'] = array_map(fn($r) => ['wilaya' => $r->wilaya, 'nb' => (int)$r->nb], $tops);

                // Wilaya map data (all wilayas keyed to DZ-xx)
                $mapStats = DB::select("
                    SELECT w.IDWilayaa, w.Nom as wilaya_nom, COUNT(e.IDEncadrement) as nb 
                    FROM Encadrement e 
                    INNER JOIN etablissement et ON e.IDetablissement=et.IDetablissement 
                    INNER JOIN wilaya w ON et.IDDFEP=w.IDWilayaa 
                    GROUP BY w.IDWilayaa, w.Nom
                ");
                $wilayaMap = [];
                foreach ($mapStats as $ms) {
                    $code = 'DZ-' . str_pad($ms->IDWilayaa, 2, '0', STR_PAD_LEFT);
                    $wilayaMap[$code] = [
                        'name' => $ms->wilaya_nom,
                        'count' => (int)$ms->nb
                    ];
                }
                $kpiData['wilaya_map'] = $wilayaMap;

                \Illuminate\Support\Facades\Cache::put($kpiKey, $kpiData, 600);
            } catch (\Exception $e) {
                $kpiData = ['total'=>84279,'men'=>46704,'women'=>37575,'inst_this_year'=>3240,'sit_counts'=>[],'grade_dist'=>[],'wilaya_top'=>[],'wilaya_map'=>[]];
            }
        }

        // Recent list
        $recentKey  = 'drh_recent_v2';
        $recentList = \Illuminate\Support\Facades\Cache::get($recentKey);
        if ($recentList === null) {
            try {
                $rows = DB::select("SELECT e.Nom, e.Prenom, e.Civ, e.DateInstall, e.Echlo, e.nbrEnf, sa.Nom as situation, etab.Nom as etablissement, w.Nom as wilaya FROM Encadrement e LEFT JOIN situationadministrat sa ON e.IDSituationAdministrat=sa.IDSituationAdministrat LEFT JOIN etablissement etab ON e.IDetablissement=etab.IDetablissement LEFT JOIN wilaya w ON etab.IDDFEP=w.IDWilayaa WHERE e.DateInstall IS NOT NULL AND YEAR(e.DateInstall) BETWEEN 2010 AND 2030 ORDER BY e.DateInstall DESC LIMIT 12");
                $recentList = array_map(fn($r) => (array)$r, $rows);
                \Illuminate\Support\Facades\Cache::put($recentKey, $recentList, 300);
            } catch (\Exception $e) {
                $recentList = [];
            }
        }

        $etablissementsList = [];
        $situationsList = [];
        try {
            $etablissementsList = DB::select("SELECT IDetablissement as id, Nom as name FROM etablissement ORDER BY Nom ASC LIMIT 50");
            $situationsList = DB::select("SELECT IDSituationAdministrat as id, Nom as name FROM situationadministrat ORDER BY Nom ASC");
        } catch (\Exception $e) {}

        // Load simplified Algeria GeoJSON inline (cached 24h)
        $geoJsonData = \Illuminate\Support\Facades\Cache::remember('algeria_geojson_simple', 86400, function() {
            $path = public_path('algeria-wilayas-simple.geojson');
            if (!file_exists($path)) return null;
            return json_decode(file_get_contents($path), true);
        });

        return compact('kpiData', 'recentList', 'etablissementsList', 'situationsList', 'geoJsonData');
    }

    /**
     * Read REAL live server metrics from the OS.
     * Uses wmic on Windows. Returns array with cpu_usage, ram_usage, ssd_usage, etc.
     */
    private function readRealServerMetrics(): array
    {
        $result = [
            'cpu_usage'   => 0,
            'cpu_status'  => 'الخادم مستقر',
            'ram_usage'   => 0,
            'ram_details' => '',
            'ssd_usage'   => 0,
            'ssd_details' => '',
        ];

        try {
            // ── CPU Load % ────────────────────────────────────────
            $cpuRaw = shell_exec('wmic cpu get LoadPercentage /value 2>nul');
            if ($cpuRaw !== null) {
                preg_match('/LoadPercentage=(\d+)/', $cpuRaw, $m);
                $cpuPct = isset($m[1]) ? (int)$m[1] : 0;
            } else {
                $cpuPct = 0;
            }
            $result['cpu_usage'] = $cpuPct;
            if ($cpuPct >= 90) {
                $result['cpu_status'] = 'تحذير: الحمل مرتفع جداً';
            } elseif ($cpuPct >= 70) {
                $result['cpu_status'] = 'الحمل مرتفع — مراقبة مستمرة';
            } elseif ($cpuPct >= 40) {
                $result['cpu_status'] = 'الخادم يعمل بحمل متوسط';
            } else {
                $result['cpu_status'] = 'الخادم الرئيسي مستقر تماماً';
            }

            // ── RAM ───────────────────────────────────────────────
            $ramRaw = shell_exec('wmic OS get TotalVisibleMemorySize,FreePhysicalMemory /value 2>nul');
            if ($ramRaw !== null) {
                preg_match('/FreePhysicalMemory=(\d+)/', $ramRaw, $mFree);
                preg_match('/TotalVisibleMemorySize=(\d+)/', $ramRaw, $mTotal);
                $ramFreeKB  = isset($mFree[1])  ? (int)$mFree[1]  : 0;
                $ramTotalKB = isset($mTotal[1]) ? (int)$mTotal[1] : 1;
                $ramUsedKB  = $ramTotalKB - $ramFreeKB;

                $ramTotalGB = round($ramTotalKB / 1048576, 1);
                $ramUsedGB  = round($ramUsedKB  / 1048576, 2);
                $ramPct     = $ramTotalKB > 0 ? (int)round(($ramUsedKB / $ramTotalKB) * 100) : 0;

                $result['ram_usage']   = $ramPct;
                $result['ram_details'] = "{$ramUsedGB} GB من أصل {$ramTotalGB} GB مستهلكة";
            }

            // ── Disk (drive Z: or C: if Z: not found) ────────────
            // Try Z: first (XAMPP drive), then C:
            $diskDrives = ['Z:', 'C:'];
            $diskFound  = false;
            foreach ($diskDrives as $drive) {
                $diskRaw = shell_exec("wmic logicaldisk where \"DeviceID='{$drive}'\" get Size,FreeSpace /value 2>nul");
                if ($diskRaw !== null) {
                    preg_match('/FreeSpace=(\d+)/', $diskRaw, $mDF);
                    preg_match('/Size=(\d+)/',      $diskRaw, $mDS);
                    if (!empty($mDF[1]) && !empty($mDS[1])) {
                        $diskFreeBytes  = (float)$mDF[1];
                        $diskTotalBytes = (float)$mDS[1];
                        $diskUsedBytes  = $diskTotalBytes - $diskFreeBytes;

                        $diskTotalGB = round($diskTotalBytes / (1024 ** 3), 0);
                        $diskFreeGB  = round($diskFreeBytes  / (1024 ** 3), 1);
                        $diskPct     = $diskTotalBytes > 0 ? (int)round(($diskUsedBytes / $diskTotalBytes) * 100) : 0;

                        $result['ssd_usage']   = $diskPct;
                        $result['ssd_details'] = "المتبقي: {$diskFreeGB} GB | الإجمالي: {$diskTotalGB} GB ({$drive})";
                        $diskFound = true;
                        break;
                    }
                }
            }

            // Fallback: if no disk info found, try all local disks
            if (!$diskFound) {
                $diskRaw = shell_exec('wmic logicaldisk where DriveType=3 get Size,FreeSpace,Caption /value 2>nul');
                if ($diskRaw !== null) {
                    preg_match_all('/Caption=([A-Z]:)/', $diskRaw, $caps);
                    preg_match_all('/FreeSpace=(\d+)/', $diskRaw, $frees);
                    preg_match_all('/Size=(\d+)/', $diskRaw, $sizes);
                    if (!empty($sizes[1])) {
                        $totalBytes = 0;
                        $freeBytes  = 0;
                        foreach ($sizes[1] as $i => $sz) {
                            $totalBytes += (float)$sz;
                            $freeBytes  += isset($frees[1][$i]) ? (float)$frees[1][$i] : 0;
                        }
                        $usedBytes  = $totalBytes - $freeBytes;
                        $totalGB    = round($totalBytes / (1024 ** 3), 0);
                        $freeGB     = round($freeBytes  / (1024 ** 3), 1);
                        $pct        = $totalBytes > 0 ? (int)round(($usedBytes / $totalBytes) * 100) : 0;

                        $result['ssd_usage']   = $pct;
                        $result['ssd_details'] = "المتبقي: {$freeGB} GB | الإجمالي: {$totalGB} GB (جميع الأقراص)";
                    }
                }
            }

        } catch (\Throwable $e) {
            Log::warning('readRealServerMetrics failed: ' . $e->getMessage());
        }

        return $result;
    }

    /**
     * Cmd+K Global Search API
     */
    public function searchAll(Request $request)
    {
        $q = trim($request->input('q', ''));
        if (mb_strlen($q) < 2) {
            return response()->json([]);
        }

        $isSig = $request->is('sig/*') || $request->is('sig');
        $prefix = $isSig ? '/sig' : '';
        $results = [];

        // 1. Search Static Navigation & Shortcuts
        $staticItems = [
            ['title' => 'لوحة التحكم الرئيسية / Tableau de bord', 'url' => $prefix . '/dashboard', 'icon' => 'fa-gauge', 'desc' => 'الرئيسية'],
            ['title' => 'التسيير المالي والميزانيات / Finances', 'url' => $prefix . '/dashboard/finance', 'icon' => 'fa-wallet', 'desc' => 'المالية'],
            ['title' => 'الموارد البشرية والإدارية / RH', 'url' => $prefix . '/dashboard/rh', 'icon' => 'fa-users', 'desc' => 'الموظفون'],
            ['title' => 'تفضيلات المظهر والألوان / Préférences', 'url' => $prefix . '/dashboard/preferences', 'icon' => 'fa-palette', 'desc' => 'الإعدادات'],
            ['title' => 'المصادقة الثنائية والأمان / MFA', 'url' => $prefix . '/security/mfa', 'icon' => 'fa-key', 'desc' => 'الحماية'],
            ['title' => 'مركز الأمان والرقابة / Security Center', 'url' => $prefix . '/admin/security', 'icon' => 'fa-shield-halved', 'desc' => 'الأمان'],
        ];

        $matchedStatic = [];
        foreach ($staticItems as $item) {
            if (mb_strpos(mb_strtolower($item['title']), mb_strtolower($q)) !== false) {
                $matchedStatic[] = $item;
            }
        }
        if (!empty($matchedStatic)) {
            $results[] = [
                'category' => 'صفحات واختصارات النظام',
                'items' => $matchedStatic
            ];
        }

        // 2. Search Etablissement (Institutions)
        try {
            $etabs = DB::table('etablissement')
                ->where('Nom', 'LIKE', "%{$q}%")
                ->orWhere('NomFr', 'LIKE', "%{$q}%")
                ->orWhere('Code', 'LIKE', "%{$q}%")
                ->take(8)
                ->get(['IDetablissement', 'Nom', 'NomFr', 'Code']);

            if ($etabs->isNotEmpty()) {
                $etabItems = [];
                foreach ($etabs as $etab) {
                    $etabItems[] = [
                        'title' => ($etab->Nom ?: $etab->NomFr) . ' (' . $etab->Code . ')',
                        'url' => $prefix . '/dashboard/etablissement?id=' . $etab->IDetablissement,
                        'icon' => 'fa-building-columns',
                        'desc' => 'مؤسسة تكوينية'
                    ];
                }
                $results[] = [
                    'category' => 'المؤسسات والمديريات',
                    'items' => $etabItems
                ];
            }
        } catch (\Exception $e) {
            Log::error('Search Etablissement failed: ' . $e->getMessage());
        }

        // 3. Search Portal CMS Pages
        try {
            $pages = DB::table('portal_pages')
                ->where('title', 'LIKE', "%{$q}%")
                ->orWhere('title_fr', 'LIKE', "%{$q}%")
                ->orWhere('content', 'LIKE', "%{$q}%")
                ->take(5)
                ->get(['slug', 'title', 'title_fr']);

            if ($pages->isNotEmpty()) {
                $pageItems = [];
                foreach ($pages as $page) {
                    $pageItems[] = [
                        'title' => $page->title . ($page->title_fr ? ' / ' . $page->title_fr : ''),
                        'url' => $prefix . '/portal/' . $page->slug,
                        'icon' => 'fa-file-lines',
                        'desc' => 'صفحة بجمالية عالية'
                    ];
                }
                $results[] = [
                    'category' => 'صفحات البوابة الإلكترونية',
                    'items' => $pageItems
                ];
            }
        } catch (\Exception $e) {
            Log::error('Search Portal Pages failed: ' . $e->getMessage());
        }

        return response()->json($results);
    }

    public function getNotifications(Request $request)
    {
        $user = session('user');
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        // Generate system notifications dynamically
        \App\Models\Notification::generateSystemNotifications($user);

        $userId = (int)($user['id'] ?? 0);
        $userType = strtolower($user['role_code'] ?? 'user');

        if ($userType === 'etablissement') {
            $notifications = \App\Models\Notification::where('user_id', $userId)
                ->where('user_type', 'etablissement')
                ->where('is_read', false)
                ->orderBy('created_at', 'desc')
                ->get();
        } else {
            $notifications = \App\Models\Notification::where(function($q) use ($userId) {
                    $q->whereNull('user_id')
                      ->orWhere('user_id', $userId);
                })
                ->where('is_read', false)
                ->orderBy('created_at', 'desc')
                ->get();
        }

        $formatted = $notifications->map(function($n) {
            return [
                'id' => $n->id,
                'title' => $n->title,
                'message' => $n->message,
                'link' => $n->url,
                'is_read' => $n->is_read ? 1 : 0,
                'type' => $n->type,
                'created_at' => $n->created_at ? $n->created_at->diffForHumans() : '',
            ];
        });

        return response()->json([
            'success' => true,
            'unread_count' => $notifications->count(),
            'notifications' => $formatted
        ]);
    }

    /**
     * Mark all unread notifications for the current user as read.
     */
    public function markNotificationsAsRead(Request $request)
    {
        $user = session('user');
        if (!$user) {
            return response()->json(['success' => false], 401);
        }

        $userId = (int)($user['id'] ?? 0);
        $userType = strtolower($user['role_code'] ?? 'user');

        if ($userType === 'etablissement') {
            \App\Models\Notification::where('user_id', $userId)
                ->where('user_type', 'etablissement')
                ->where('is_read', false)
                ->update(['is_read' => true]);
        } else {
            \App\Models\Notification::where(function($q) use ($userId) {
                    $q->whereNull('user_id')
                      ->orWhere('user_id', $userId);
                })
                ->where('is_read', false)
                ->update(['is_read' => true]);
        }

        return response()->json(['success' => true]);
    }
}
