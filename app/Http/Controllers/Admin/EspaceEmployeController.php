<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Employee\EmployeeCardService;
use App\Services\Employee\EmployeePhotoService;
use App\Services\Employee\EmployeeQueryService;
use App\Services\Employee\EmployeeScopeService;
use App\Services\ReferenceCache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Exception;

/**
 * EspaceEmployeController
 *
 * Thin orchestration layer — delegates ALL business logic to dedicated services:
 *  - EmployeeScopeService  → role-based auth, sanitization, filter building
 *  - EmployeeQueryService  → all SQL / database access
 *  - EmployeeCardService   → classification + career data building
 *  - EmployeePhotoService  → image upload, validation, resize
 *
 * This controller only: validates HTTP input, orchestrates the services,
 * and returns the appropriate HTTP response. No SQL, no role logic, no HTML.
 */
class EspaceEmployeController extends Controller
{
    // ─────────────────────────────────────────────────────────────────────────
    //  Services factory — no constructor DI, works on any PHP/Laravel version
    // ─────────────────────────────────────────────────────────────────────────

    private function scopeSvc(): EmployeeScopeService { return new EmployeeScopeService(); }
    private function querySvc(): EmployeeQueryService  { return new EmployeeQueryService();  }
    private function cardSvc():  EmployeeCardService   { return new EmployeeCardService();   }
    private function photoSvc(): EmployeePhotoService  { return new EmployeePhotoService();  }

    // ─────────────────────────────────────────────────────────────────────────
    //  ALLOWED ROLES
    // ─────────────────────────────────────────────────────────────────────────

    private const ALLOWED_ROLES = [
        'admin', 'dfep', 'central', 'etablissement', 'directeur',
        'high_admin', 'secretaire_general', 'ministre',
    ];

    /**
     * Backward-compatibility shim for digitalCards() and getTrainee()
     */
    private function getScope(): array
    {
        return $this->scopeSvc()->getScope();
    }


    // ─────────────────────────────────────────────────────────────────────────
    //  index() — Employee list page
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Display the employee space listing page.
     */
    public function index(Request $request)
    {
        @set_time_limit(300);

        $user = session('user') ?? [];
        if (empty($user)) {
            return redirect()->route('login');
        }

        $this->authorizeRole(self::ALLOWED_ROLES);
        $scopeData = $this->scopeSvc()->getScope();

        // Build WHERE from role scope + user-supplied filters
        $scopeResult  = $this->scopeSvc()->buildScopeClauses($scopeData);
        $clauses      = $scopeResult['clauses'];
        $params       = $scopeResult['params'];

        $filterResult = $this->scopeSvc()->applyRequestFilters($request, $clauses, $params);
        $clauses      = $filterResult['clauses'];
        $params       = $filterResult['params'];
        $where = implode(' AND ', $clauses);

        $page   = max(1, (int)$request->query('page', 1));
        $limit  = 12;
        $offset = ($page - 1) * $limit;

        $totalCount = $this->querySvc()->count($where, $params);
        $employees  = $this->querySvc()->paginate($where, $params, $limit, $offset);
        $totalPages = max(1, (int)ceil($totalCount / $limit));

        // Reference data for filters
        $filter_wilayas = ReferenceCache::wilayas();
        $filter_etablissements = ($scopeData['role'] === 'dfep' && $scopeData['iddfep'])
            ? ReferenceCache::etablissementsForDfep($scopeData['iddfep'])
            : ReferenceCache::etablissements();

        // ✅ Secure API key — random, unpredictable
        $apiKey = $user['api_key'] ?? null;
        if (empty($apiKey)) {
            $apiKey          = $this->scopeSvc()->generateApiKey();
            $user['api_key'] = $apiKey;
            session(['user' => $user]);
        }

        $search = $request->query('filter_search');
        $wilaya = $request->query('filter_wilaya');
        $type   = $request->query('filter_type');
        $etab   = $request->query('filter_etab');

        return $this->render('admin/espace-employe/index', [
            'employees'             => $employees,
            'filter_wilayas'        => $filter_wilayas,
            'filter_etablissements' => $filter_etablissements,
            'page'                  => $page,
            'totalPages'            => $totalPages,
            'totalCount'            => $totalCount,
            'api_key'               => $apiKey,
            'scope'                 => $scopeData,
            'selected_filters'      => compact('search', 'wilaya', 'type', 'etab'),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  getEmployee() — AJAX detail endpoint
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * AJAX: return full employee profile (role-filtered).
     * Returns only 25 selected columns + derived data — never 80+ raw columns.
     */
    public function getEmployee($id)
    {
        $this->authorizeRole(self::ALLOWED_ROLES);
        $scopeData = $this->scopeSvc()->getScope();
        $role      = $scopeData['role'];

        // employee can only view their own record
        $id = ($role === 'employee')
            ? (int)$this->scopeSvc()->getAuthenticatedEmployeeId()
            : (int)$id;

        try {
            $employee = $this->querySvc()->findById($id);

            if (!$employee) {
                return response()->json(['success' => false, 'message' => 'الموظف غير موجود'], 404);
            }

            // ✅ Scope-based access check (throws 403 if denied)
            try {
                $this->scopeSvc()->authorizeRead($employee, $scopeData);
            } catch (AccessDeniedHttpException $e) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 403);
            }

            // ✅ Audit log
            \App\Core\AuditLogger::logRead('encadrement', $id, [
                'name'    => ($employee['Nom'] ?? '') . ' ' . ($employee['Prenom'] ?? ''),
                'nin'     => '***PROTECTED***',
                'subject' => 'معاينة الملف الشخصي للموظف',
            ]);

            // Enrich with computed/derived fields (classification, milestones, doc codes)
            $employee = $this->cardSvc()->enrich($employee);

            // ✅ Strip sensitive fields based on role — ALWAYS the last step
            $employee = $this->scopeSvc()->sanitize($employee, $role);

            // ✅ Add secure_id for QR code generation on digital card
            $encId = (int)($employee['IDEncadrement'] ?? $employee['id'] ?? 0);
            $employee['secure_id'] = $encId > 0 ? \App\Helpers\SecureIdHelper::encrypt($encId) : null;

            return response()->json(['success' => true, 'employee' => $employee]);


        } catch (Exception $e) {
            Log::error('EspaceEmployeController::getEmployee', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'حدث خطأ أثناء تحميل بيانات الموظف'], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  updateEmployee() — AJAX save endpoint
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * AJAX: update editable employee fields.
     * NIN is intentionally excluded from accepted input.
     */
    public function updateEmployee(Request $request, $id)
    {
        $this->authorizeRole(self::ALLOWED_ROLES);
        $scopeData = $this->scopeSvc()->getScope();

        $id = ($scopeData['role'] === 'employee')
            ? (int)$this->scopeSvc()->getAuthenticatedEmployeeId()
            : (int)$id;

        try {
            // Find minimal record for scope authorisation
            $existing = $this->querySvc()->findForUpdate($id);
            if (!$existing) {
                return response()->json(['success' => false, 'message' => 'الموظف غير موجود'], 404);
            }

            // ✅ Scope-based write authorisation
            try {
                $this->scopeSvc()->authorizeWrite($existing, $id, $scopeData);
            } catch (AccessDeniedHttpException $e) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 403);
            }

            // ✅ Photo upload via dedicated service (MimeType + 2MB + 0755)
            $photoPath = null;
            if ($request->hasFile('photo') && $request->file('photo')->isValid()) {
                try {
                    $photoPath = $this->photoSvc()->upload($request->file('photo'), $id);
                } catch (\InvalidArgumentException $e) {
                    return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
                } catch (\RuntimeException $e) {
                    Log::error('Photo upload failed', ['id' => $id, 'error' => $e->getMessage()]);
                    return response()->json(['success' => false, 'message' => 'فشل رفع الصورة — يرجى المحاولة مجدداً'], 500);
                }
            }

            // ✅ Server-side validation
            $tel   = trim($request->input('tel') ?? '');
            $email = trim($request->input('email') ?? '');
            if ($tel !== '' && !preg_match('/^[0-9\s\-\+]{7,20}$/', $tel)) {
                return response()->json(['success' => false, 'message' => 'رقم الهاتف غير صالح'], 422);
            }
            if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return response()->json(['success' => false, 'message' => 'البريد الإلكتروني غير صالح'], 422);
            }

            // Normalize date format: YYYY/MM/DD → YYYY-MM-DD
            $dateNais = str_replace('/', '-', $request->input('date_nais') ?? '');

            $data = [
                'Nom'              => trim($request->input('nom') ?? ''),
                'Prenom'           => trim($request->input('prenom') ?? ''),
                'NomFr'            => trim($request->input('nom_fr') ?? ''),
                'PrenomFr'         => trim($request->input('prenom_fr') ?? ''),
                'DateNais'         => $dateNais,
                'LieuNais'         => trim($request->input('lieu_nais') ?? ''),
                'Tel'              => $tel,
                'Email'            => $email,
                'Adres'            => trim($request->input('adres') ?? ''),
                'nbrEnf'           => max(0, (int)$request->input('nbr_enfants', 0)),
                'nbrenfscol'       => max(0, (int)$request->input('nbr_enfants_scol', 0)),
                'Echlo'            => max(0, (int)$request->input('echelon', 0)),
                'nss'              => trim($request->input('nss') ?? ''),
                'Civ'              => (int)($request->input('civ') ?? 1),
                'IDSitfamille'     => (int)($request->input('sitfamille') ?? 1),
                'Specialite'       => trim($request->input('specialite') ?? ''),
                'TachesPrincipale' => trim($request->input('taches_principale') ?? ''),
                'Daterecr'         => $request->input('daterecr') ?: null,
                // nin intentionally OMITTED — cannot be modified via this endpoint
            ];

            if ($photoPath) {
                $data['photo'] = $photoPath;
            }

            $ok = $this->querySvc()->update($id, $data);

            if (!$ok) {
                return response()->json(['success' => false, 'message' => 'فشل تحديث البيانات'], 500);
            }

            return response()->json(['success' => true, 'message' => 'تم تحديث بيانات الموظف بنجاح!']);

        } catch (Exception $e) {
            Log::error('EspaceEmployeController::updateEmployee', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'حدث خطأ أثناء الحفظ'], 500);
        }
    }


    public function digitalCards(Request $request)
    {
        @set_time_limit(300);
        $user = session('user') ?? [];
        if (empty($user)) {
            return redirect()->route('login');
        }
        $db = new \App\Core\LaravelDbAdapter();
        $this->authorizeRole(['admin', 'dfep', 'central', 'etablissement', 'directeur', 'high_admin', 'secretaire_general', 'ministre']);

        $scope = $this->getScope();
        $type = $request->query('type', 'employee'); // 'employee' or 'trainee'
        $search = $request->query('filter_search');
        $wilaya = $request->query('filter_wilaya');
        $etab = $request->query('filter_etab');
        $mode = $request->query('filter_mode');
        $branche = $request->query('filter_branche');
        $grade = $request->query('filter_grade');
        $fonction = $request->query('filter_fonction');
        $page = (int)$request->query('page', 1);
        if ($page < 1) $page = 1;
        $limit = 10;
        $offset = ($page - 1) * $limit;

        $clauses = ['1=1'];
        $params = [];

        // Role scoping
        if ($scope['role'] === 'dfep' && $scope['iddfep']) {
            $clauses[] = $type === 'employee' ? "et.IDDFEP = ?" : "e.IDDFEP = ?";
            $params[] = $scope['iddfep'];
        } elseif (in_array($scope['role'], ['etablissement', 'directeur']) && $scope['etabId']) {
            $clauses[] = $type === 'employee' ? "enc.IDetablissement = ?" : "COALESCE(NULLIF(s.IDEts_Form, 0), NULLIF(o.IDEts_Form, 0)) = ?";
            $params[] = $scope['etabId'];
        }

        if ($type === 'employee') {
            if (!empty($search)) {
                $clauses[] = "(enc.Nom LIKE ? OR enc.Prenom LIKE ? OR enc.NomFr LIKE ? OR enc.PrenomFr LIKE ? OR enc.IDEncadrement = ? OR enc.nin = ? OR enc.Specialite LIKE ? OR g.Nom LIKE ? OR f.Nom LIKE ?)";
                $searchTerm = "%$search%";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = (int)$search;
                $params[] = $search;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            if (!empty($wilaya)) {
                $clauses[] = "et.IDDFEP = ?";
                $params[] = (int)$wilaya;
            }
            if (!empty($etab)) {
                $clauses[] = "enc.IDetablissement = ?";
                $params[] = (int)$etab;
            }
            if (!empty($grade)) {
                $clauses[] = "enc.IDGrade = ?";
                $params[] = (int)$grade;
            }
            if (!empty($fonction)) {
                $clauses[] = "enc.IDFonctions = ?";
                $params[] = (int)$fonction;
            }

            $whereClause = implode(' AND ', $clauses);

            // Fetch Total Count
            $totalCount = 0;
            try {
                $countSql = "SELECT COUNT(*) FROM encadrement enc LEFT JOIN etablissement et ON enc.IDetablissement = et.IDetablissement LEFT JOIN grade g ON enc.IDGrade = g.IDGrade LEFT JOIN fonctions f ON enc.IDFonctions = f.IDFonctions WHERE $whereClause";
                $stmtCount = $db->prepare($countSql);
                $stmtCount->execute($params);
                $totalCount = (int)$stmtCount->fetchColumn();
            } catch (Exception $e) {}

            // Fetch Employees
            $records = [];
            try {
                $selectSql = "
                    SELECT enc.IDEncadrement as id, enc.Nom as nom, enc.Prenom as prenom, 
                           enc.NomFr as nom_fr, enc.PrenomFr as prenom_fr, enc.nin, enc.nss,
                           enc.Specialite as spec_ar, et.Nom AS etab_nom,
                           g.Nom as grade_nom, f.Nom as fonction_nom, enc.TachesPrincipale
                    FROM encadrement enc
                    LEFT JOIN etablissement et ON enc.IDetablissement = et.IDetablissement
                    LEFT JOIN grade g ON enc.IDGrade = g.IDGrade
                    LEFT JOIN fonctions f ON enc.IDFonctions = f.IDFonctions
                    WHERE $whereClause
                    ORDER BY enc.Nom ASC, enc.Prenom ASC
                    LIMIT ? OFFSET ?
                ";
                $stmtSelect = $db->prepare($selectSql);
                $i = 1;
                foreach ($params as $paramVal) {
                    $stmtSelect->bindValue($i++, $paramVal);
                }
                $stmtSelect->bindValue($i++, $limit, \PDO::PARAM_INT);
                $stmtSelect->bindValue($i, $offset, \PDO::PARAM_INT);
                $stmtSelect->execute();
                $records = $stmtSelect->fetchAll(\PDO::FETCH_ASSOC);
            } catch (Exception $e) {}

        } else {
            $clauses1 = ['1=1'];
            $clauses2 = ['s.IDSection IS NULL'];
            $params1 = [];
            $params2 = [];

            // Apply 2024-2026 session constraint globally to prevent timeouts and match requirements
            $clauses1[] = "s.DateDF >= '2024-02-01'";
            $clauses2[] = "(o_cand.Session_rentree LIKE '2024%' OR o_cand.Session_rentree LIKE '2025%' OR o_cand.Session_rentree LIKE '2026%')";

            // Role scoping
            if ($scope['role'] === 'dfep' && $scope['iddfep']) {
                $clauses1[] = "w.IDWilayaa = ?";
                $params1[] = $scope['iddfep'];
                
                $clauses2[] = "w.IDWilayaa = ?";
                $params2[] = $scope['iddfep'];
            } elseif (in_array($scope['role'], ['etablissement', 'directeur']) && $scope['etabId']) {
                $clauses1[] = "s.IDEts_Form = ?";
                $params1[] = $scope['etabId'];
                
                $clauses2[] = "o_cand.IDEts_Form = ?";
                $params2[] = $scope['etabId'];
            }

            // Search text
            if (!empty($search)) {
                $searchTerm = "%$search%";
                $clauses1[] = "(c.Nom LIKE ? OR c.Prenom LIKE ? OR c.NomFr LIKE ? OR c.PrenomFr LIKE ? OR a.IDapprenant = ? OR c.nin = ? OR a.Nccp = ? OR sp.Nom LIKE ? OR e.Nom LIKE ?)";
                $params1[] = $searchTerm;
                $params1[] = $searchTerm;
                $params1[] = $searchTerm;
                $params1[] = $searchTerm;
                $params1[] = (int)$search;
                $params1[] = $search;
                $params1[] = $search;
                $params1[] = $searchTerm;
                $params1[] = $searchTerm;

                $clauses2[] = "(c.Nom LIKE ? OR c.Prenom LIKE ? OR c.NomFr LIKE ? OR c.PrenomFr LIKE ? OR a.IDapprenant = ? OR c.nin = ? OR a.Nccp = ? OR sp.Nom LIKE ? OR e.Nom LIKE ?)";
                $params2[] = $searchTerm;
                $params2[] = $searchTerm;
                $params2[] = $searchTerm;
                $params2[] = $searchTerm;
                $params2[] = (int)$search;
                $params2[] = $search;
                $params2[] = $search;
                $params2[] = $searchTerm;
                $params2[] = $searchTerm;
            }

            // Wilaya filter
            if (!empty($wilaya)) {
                $clauses1[] = "w.IDWilayaa = ?";
                $params1[] = (int)$wilaya;
                
                $clauses2[] = "w.IDWilayaa = ?";
                $params2[] = (int)$wilaya;
            }

            // Etab filter
            if (!empty($etab)) {
                $clauses1[] = "s.IDEts_Form = ?";
                $params1[] = (int)$etab;
                
                $clauses2[] = "o_cand.IDEts_Form = ?";
                $params2[] = (int)$etab;
            }

            // Mode filter
            if (!empty($mode)) {
                $clauses1[] = "s.IDMode_formation = ?";
                $params1[] = $mode;
                
                $clauses2[] = "o_cand.IDMode_formation = ?";
                $params2[] = $mode;
            }

            // Branche filter
            if (!empty($branche)) {
                $clauses1[] = "sp.IDBranche = ?";
                $params1[] = (int)$branche;
                
                $clauses2[] = "sp.IDBranche = ?";
                $params2[] = (int)$branche;
            }

            $whereClause1 = implode(' AND ', $clauses1);
            $whereClause2 = implode(' AND ', $clauses2);
            $allParams = array_merge($params1, $params2);
            
            $isUnfiltered = ($whereClause1 === '1=1' && $whereClause2 === 's.IDSection IS NULL');

            // Fetch Total Count
            $totalCount = 0;
            try {
                if ($isUnfiltered) {
                    $countSql = "SELECT COUNT(*) FROM apprenant";
                    $countCacheKey = 'trainees_count_fast_all';
                } else {
                    $countSql = "
                        SELECT SUM(cnt) FROM (
                            SELECT COUNT(*) as cnt
                            FROM apprenant a
                            JOIN candidat c ON a.IDCandidat = c.IDCandidat
                            JOIN section s ON a.IDSection = s.IDSection
                            LEFT JOIN specialite sp ON s.IDSpecialite = sp.IDSpecialite
                            LEFT JOIN etablissement e ON s.IDEts_Form = e.IDetablissement
                            LEFT JOIN wilaya w ON w.IDWilayaa = e.IDDFEP
                            WHERE $whereClause1
                            
                            UNION ALL
                            
                            SELECT COUNT(*) as cnt
                            FROM apprenant a
                            JOIN candidat c ON a.IDCandidat = c.IDCandidat
                            LEFT JOIN section s ON a.IDSection = s.IDSection
                            JOIN offre o_cand ON c.IDOffre = o_cand.IDOffre
                            LEFT JOIN specialite sp ON o_cand.IDSpecialite = sp.IDSpecialite
                            LEFT JOIN etablissement e ON o_cand.IDEts_Form = e.IDetablissement
                            LEFT JOIN wilaya w ON w.IDWilayaa = e.IDDFEP
                            WHERE $whereClause2
                        ) tmp
                    ";
                    $countCacheKey = 'trainees_count_' . md5($countSql . serialize($allParams));
                }

                $totalCount = cache()->remember($countCacheKey, 300, function() use ($db, $countSql, $allParams, $isUnfiltered) {
                    $stmtCount = $db->prepare($countSql);
                    if (!$isUnfiltered) {
                        $stmtCount->execute($allParams);
                    } else {
                        $stmtCount->execute();
                    }
                    return (int)$stmtCount->fetchColumn();
                });
            } catch (Exception $e) {}

            // Fetch Trainees
            $records = [];
            try {
                if ($isUnfiltered) {
                    $selectSql = "
                        SELECT a.IDapprenant as id, a.Nccp as numero_matricule,
                               c.Nom as nom, c.Prenom as prenom, 
                               c.NomFr as nom_fr, c.PrenomFr as prenom_fr, c.nin, c.nss,
                               sp.Nom as spec_ar, e.Nom AS etab_nom
                        FROM apprenant a
                        JOIN candidat c ON a.IDCandidat = c.IDCandidat
                        LEFT JOIN section s ON a.IDSection = s.IDSection
                        LEFT JOIN offre o_cand ON c.IDOffre = o_cand.IDOffre
                        LEFT JOIN specialite sp ON sp.IDSpecialite = COALESCE(NULLIF(s.IDSpecialite, 0), NULLIF(o_cand.IDSpecialite, 0))
                        LEFT JOIN etablissement e ON e.IDetablissement = COALESCE(NULLIF(s.IDEts_Form, 0), NULLIF(o_cand.IDEts_Form, 0))
                        ORDER BY a.IDapprenant DESC
                        LIMIT ? OFFSET ?
                    ";
                    $stmtSelect = $db->prepare($selectSql);
                    $stmtSelect->bindValue(1, $limit, \PDO::PARAM_INT);
                    $stmtSelect->bindValue(2, $offset, \PDO::PARAM_INT);
                    $stmtSelect->execute();
                } else {
                    $selectSql = "
                        SELECT id, numero_matricule, nom, prenom, nom_fr, prenom_fr, nin, nss, spec_ar, etab_nom
                        FROM (
                            SELECT a.IDapprenant as id, a.Nccp as numero_matricule,
                                   c.Nom as nom, c.Prenom as prenom, 
                                   c.NomFr as nom_fr, c.PrenomFr as prenom_fr, c.nin, c.nss,
                                   sp.Nom as spec_ar, e.Nom AS etab_nom
                            FROM apprenant a
                            JOIN candidat c ON a.IDCandidat = c.IDCandidat
                            JOIN section s ON a.IDSection = s.IDSection
                            LEFT JOIN specialite sp ON s.IDSpecialite = sp.IDSpecialite
                            LEFT JOIN etablissement e ON s.IDEts_Form = e.IDetablissement
                            LEFT JOIN wilaya w ON w.IDWilayaa = e.IDDFEP
                            WHERE $whereClause1
                            
                            UNION ALL
                            
                            SELECT a.IDapprenant as id, a.Nccp as numero_matricule,
                                   c.Nom as nom, c.Prenom as prenom, 
                                   c.NomFr as nom_fr, c.PrenomFr as prenom_fr, c.nin, c.nss,
                                   sp.Nom as spec_ar, e.Nom AS etab_nom
                            FROM apprenant a
                            JOIN candidat c ON a.IDCandidat = c.IDCandidat
                            LEFT JOIN section s ON a.IDSection = s.IDSection
                            JOIN offre o_cand ON c.IDOffre = o_cand.IDOffre
                            LEFT JOIN specialite sp ON o_cand.IDSpecialite = sp.IDSpecialite
                            LEFT JOIN etablissement e ON o_cand.IDEts_Form = e.IDetablissement
                            LEFT JOIN wilaya w ON w.IDWilayaa = e.IDDFEP
                            WHERE $whereClause2
                        ) tmp
                        ORDER BY id DESC
                        LIMIT ? OFFSET ?
                    ";
                    $stmtSelect = $db->prepare($selectSql);
                    $i = 1;
                    foreach ($allParams as $paramVal) {
                        $stmtSelect->bindValue($i++, $paramVal);
                    }
                    $stmtSelect->bindValue($i++, $limit, \PDO::PARAM_INT);
                    $stmtSelect->bindValue($i, $offset, \PDO::PARAM_INT);
                    $stmtSelect->execute();
                }
                
                $records = $stmtSelect->fetchAll(\PDO::FETCH_ASSOC);
            } catch (Exception $e) {}
        }

        foreach ($records as &$rec) {
            if (!empty($rec['nin'])) {
                try {
                    $dec = \Illuminate\Support\Facades\Crypt::decryptString($rec['nin']);
                    if ($dec !== false && $dec !== '') {
                        $rec['nin'] = $dec;
                    }
                } catch (\Exception $e) {}
            }
        }
        unset($rec);

        $totalPages = ceil($totalCount / $limit);
        if ($totalPages < 1) $totalPages = 1;

        $filter_wilayas = ReferenceCache::wilayas();
        if ($scope['role'] === 'dfep' && $scope['iddfep']) {
            $filter_etablissements = ReferenceCache::etablissementsForDfep($scope['iddfep']);
        } else {
            $filter_etablissements = ReferenceCache::etablissements();
        }
        $filter_branches = ReferenceCache::branches();
        $filter_modes = ReferenceCache::modesFormation();

        $filter_grades = DB::select("SELECT IDGrade as id, Nom as nom_ar FROM grade ORDER BY Nom ASC");
        $filter_fonctions = DB::select("SELECT IDFonctions as id, Nom as nom_ar FROM fonctions ORDER BY Nom ASC");
        $filter_grades = array_map(fn($r) => (array)$r, $filter_grades);
        $filter_fonctions = array_map(fn($r) => (array)$r, $filter_fonctions);

        return $this->render('admin/digital-cards/index', [
            'type' => $type,
            'records' => $records,
            'filter_wilayas' => $filter_wilayas,
            'filter_etablissements' => $filter_etablissements,
            'filter_branches' => $filter_branches,
            'filter_modes' => $filter_modes,
            'filter_grades' => $filter_grades,
            'filter_fonctions' => $filter_fonctions,
            'page' => $page,
            'totalPages' => $totalPages,
            'totalCount' => $totalCount,
            'scope' => $scope,
            'selected_filters' => compact('search', 'wilaya', 'etab', 'mode', 'branche', 'grade', 'fonction')
        ]);
    }

    /**
     * AJAX endpoint to retrieve full trainee details with role scoping checks.
     */
    public function getTrainee($id)
    {
        $this->authorizeRole(['admin', 'dfep', 'central', 'etablissement', 'directeur', 'high_admin', 'secretaire_general', 'ministre']);
        $id = (int)$id;
        try {
            $sql = "
                SELECT a.IDapprenant as id, a.Nccp as numero_matricule, 
                       c.Nom as nom_ar, c.Prenom as prenom_ar, c.NomFr as nom_fr, c.PrenomFr as prenom_fr,
                       c.nin, c.nss, c.photo, c.Civ, c.DateNais, c.LieuNais,
                       sp.Nom as spec_ar, e.Nom as etab_nom, w.Nom as wilaya_nom, w.IDWilayaa as id_wilaya,
                       mf.Nom as mode_nom, s.DateDF as date_deb, s.DateFF as date_fin,
                       ar.Nom as regime_nom
                FROM apprenant a
                JOIN candidat c ON a.IDCandidat = c.IDCandidat
                LEFT JOIN section s ON a.IDSection = s.IDSection
                LEFT JOIN offre o ON o.IDOffre = COALESCE(NULLIF(s.IDOffre, 0), NULLIF(c.IDOffre, 0))
                LEFT JOIN specialite sp ON sp.IDSpecialite = COALESCE(NULLIF(s.IDSpecialite, 0), NULLIF(o.IDSpecialite, 0))
                LEFT JOIN etablissement e ON e.IDetablissement = COALESCE(NULLIF(s.IDEts_Form, 0), NULLIF(o.IDEts_Form, 0))
                LEFT JOIN wilaya w ON e.IDDFEP = w.IDWilayaa
                LEFT JOIN mode_formation mf ON mf.IDMode_formation = COALESCE(NULLIF(s.IDMode_formation, 0), NULLIF(o.IDMode_formation, 0))
                LEFT JOIN apprenant_section_semstre ass ON a.IDapprenant = ass.IDapprenant
                LEFT JOIN apprenant_regime ar ON ass.IDapprenant_Regime = ar.IDapprenant_Regime
                WHERE a.IDapprenant = ?
                LIMIT 1
            ";
            $trainee = DB::selectOne($sql, [$id]);
            if (!$trainee) {
                return response()->json(['success' => false, 'message' => 'المتربص غير موجود'], 404);
            }
            
            $trainee = (array)$trainee;
            
            // Check scope permissions for trainees matching the user's role limits
            $scope = $this->getScope();
            if ($scope['role'] === 'dfep' && $scope['iddfep']) {
                if ((int)$trainee['id_wilaya'] !== $scope['iddfep']) {
                    return response()->json(['success' => false, 'message' => 'غير مصرح لك بالوصول لبيانات هذا المتربص'], 403);
                }
            } elseif (in_array($scope['role'], ['etablissement', 'directeur']) && $scope['etabId']) {
                $etabCheck = DB::table('apprenant as a')
                    ->join('candidat as c', 'a.IDCandidat', '=', 'c.IDCandidat')
                    ->leftJoin('section as s', 'a.IDSection', '=', 's.IDSection')
                    ->leftJoin('offre as o', 'o.IDOffre', '=', DB::raw('COALESCE(NULLIF(s.IDOffre, 0), NULLIF(c.IDOffre, 0))'))
                    ->where('a.IDapprenant', $id)
                    ->value(DB::raw('COALESCE(NULLIF(s.IDEts_Form, 0), NULLIF(o.IDEts_Form, 0))'));
                if ((int)$etabCheck !== $scope['etabId']) {
                    return response()->json(['success' => false, 'message' => 'غير مصرح لك بالوصول لبيانات هذا المتربص'], 403);
                }
            }
            
            // ✅ [SECURITY] Log sensitive READ/VIEW operation
            \App\Core\AuditLogger::logRead('apprenant', $id, [
                'name'    => ($trainee['nom_ar'] ?? '') . ' ' . ($trainee['prenom_ar'] ?? ''),
                'nin'     => '***PROTECTED***',
                'matricule' => $trainee['numero_matricule'] ?? '',
                'subject' => 'معاينة الملف الشخصي للمتربص'
            ]);

            if ($trainee) {
                if (!empty($trainee['nin'])) {
                    try {
                        $dec = \Illuminate\Support\Facades\Crypt::decryptString($trainee['nin']);
                        if ($dec !== false && $dec !== '') {
                            $trainee['nin'] = $dec;
                        }
                    } catch (\Exception $e) {}
                }
                if (!empty($trainee['DateNais'])) {
                    try {
                        $dec = \Illuminate\Support\Facades\Crypt::decryptString($trainee['DateNais']);
                        if ($dec !== false && $dec !== '') {
                            $trainee['DateNais'] = $dec;
                        }
                    } catch (\Exception $e) {}
                }
            }
            
            $trainee['secure_id'] = \App\Helpers\SecureIdHelper::encrypt((int)$trainee['id']);

            return response()->json([
                'success' => true,
                'trainee' => $trainee
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}

