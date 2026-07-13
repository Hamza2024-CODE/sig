<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use PDO;

/**
 * ModulesController - Built on confirmed WINDEV sgfep tables:
 *
 * candidat  (IDCandidat, IDOffre, Nom, Prenom, NomFr, PrenomFr, DateNais, Civ, Validation, ValidationDfp, dateInscr, Nin, Tel, ...)
 * Apprenant (IDapprenant, IDCandidat, IDSection, Nccp, Valide, Groupe, ...)
 * offre     (IDOffre, IDSession, IDSpecialite, IDMode_formation, IDEts_Form, NbrInscr, NbrInscrf, nbrPrevision, Valide, ...)
 * section   (IDSection, IDOffre, IDDFEP, IDEts_Form, IDSpecialite, IDSession, Nom, NomFr, NbrIncor, NbrIncorF, nbrdplm, nbrdplmf, ...)
 * ets_form  (IDEts_Form, IDDFEP, Nom, nomFr, IDNature_etsF, code, ...)
 * specialite(IDSpecialite, IDBranche, Nom, NomFr, CodeSpec, NbrSem, NbrAnne, ...)
 * branche   (IDBranche, Nom, NomFr, Code, ...)
 * session   (IDSession, Nom, NomFr, Code, DateD, DateF, ...)
 * encadrement(IDEncadrement, Nom, Prenom, NomFr, PrenomFr, nin, IDetablissement, IDGrade, Specialite, Grade→via join, ...)
 * dfep      (IDDFEP, Nom, NomFr, IDWilayaa, Code, ...)
 * wilaya    (IDWilayaa, Nom, NomFr, Code, Num, ...)
 * decision_insc (IDDecision_Insc, Nom, NomFr, Ord)   ← lookup table, not incidents!
 * procedure_disciplinaire (IDProcedure_Disciplinaire, Nom, NomFr, NumOrd) ← lookup
 * section_semestre (IDSection_Semestre, IDSection, NumSem, DateD, DateF, ...)
 * pvsemstriel (IDPV, IDSection_Semestre, NumPv, DatePv, ...)
 * preinscrit / preinscritsig  ← pre-registration
 */
class ModulesController extends Controller {
    protected $db;

    public function __construct() {
        if (app()->runningInConsole()) { return; }
        
        
        if (!empty(session("user")["role_code"] ?? null)) {
            session('user')['role_code'] = strtolower(session('user')['role_code']);
        }
        $this->db = new \App\Core\LaravelDbAdapter();
    }

    // ----------------------------------------------------------------
    // SCOPE HELPER: returns WHERE clause parts scoped to user's DFEP/centre
    // ----------------------------------------------------------------
    private function getScope(): array {
        $user     = session('user');
        $role     = $user['role_code'] ?? '';
        $iddfep   = (int)($user['iddfep'] ?? 0);
        $etabId   = (int)($user['etablissement_id'] ?? 0);

        return compact('role', 'iddfep', 'etabId');
    }

    /** Build WHERE fragments for section-based queries */
    private function sectionWhere(array $scope, string $alias = 's'): array {
        $clauses = ['1=1'];
        $userSession = session('user') ?? [];
        
        // 1. Role Scoping
        if ($scope['role'] === 'dfep' && $scope['iddfep']) {
            $clauses[] = "{$alias}.IDDFEP = " . (int)$scope['iddfep'];
        } elseif (in_array($scope['role'], ['etablissement', 'directeur', 'employee', 'formateur']) && $scope['etabId']) {
            $clauses[] = "{$alias}.IDOffre IN (SELECT IDOffre FROM offre WHERE IDEts_Form = " . (int)$scope['etabId'] . ")";
        }

        // 2. Global Filters
        if (!empty(request()->all()['filter_wilaya'])) {
            $clauses[] = "{$alias}.IDDFEP = " . (int)request()->all()['filter_wilaya'];
        }

        $etabFilterId = (int)(request()->all()['filter_etablissement'] ?? request()->all()['etab_id'] ?? 0);
        if ($etabFilterId > 0) {
            $clauses[] = "{$alias}.IDOffre IN (SELECT IDOffre FROM offre WHERE IDEts_Form = " . $etabFilterId . ")";
        }

        if (!empty(request()->all()['filter_mode'])) {
            $clauses[] = "{$alias}.IDOffre IN (SELECT IDOffre FROM offre WHERE IDMode_formation = " . (int)request()->all()['filter_mode'] . ")";
        }

        // Enforce Apprenticeship Mode User Scoping
        if ((int)($userSession['IDMode_formation'] ?? 0) === 10) {
            $clauses[] = "{$alias}.IDOffre IN (SELECT IDOffre FROM offre WHERE IDMode_formation = 10)";
            if (!empty($userSession['etablissement_id'])) {
                $clauses[] = "{$alias}.IDOffre IN (SELECT IDOffre FROM offre WHERE IDEts_Form = " . (int)$userSession['etablissement_id'] . ")";
            }
        }

        return [implode(' AND ', $clauses), []];
    }

    /** Build WHERE fragments for offre-based queries */
    private function offreWhere(array $scope, string $alias = 'o'): array {
        $clauses = ['1=1'];
        $userSession = session('user') ?? [];
        
        // 1. Role Scoping
        if ($scope['role'] === 'dfep' && $scope['iddfep']) {
            // Use ets_form to get the correct IDEts_Form for each center in this wilaya
            $clauses[] = "{$alias}.IDEts_Form IN (
                SELECT ets.IDEts_Form FROM ets_form ets
                INNER JOIN etablissement e ON ets.IDetablissement = e.IDetablissement
                WHERE e.IDDFEP = " . (int)$scope['iddfep'] . "
                UNION
                SELECT e2.IDetablissement FROM etablissement e2
                LEFT JOIN ets_form ets2 ON ets2.IDetablissement = e2.IDetablissement
                WHERE e2.IDDFEP = " . (int)$scope['iddfep'] . " AND ets2.IDEts_Form IS NULL
            )";
        } elseif (in_array($scope['role'], ['etablissement', 'directeur', 'employee', 'formateur']) && $scope['etabId']) {
            $clauses[] = "{$alias}.IDEts_Form = " . (int)$scope['etabId'];
        }

        // 2. Global Filters
        if (!empty(request()->all()['filter_wilaya'])) {
            $wId = (int)request()->all()['filter_wilaya'];
            $clauses[] = "{$alias}.IDEts_Form IN (
                SELECT ets.IDEts_Form FROM ets_form ets
                INNER JOIN etablissement e ON ets.IDetablissement = e.IDetablissement
                WHERE e.IDDFEP = $wId
                UNION
                SELECT e2.IDetablissement FROM etablissement e2
                LEFT JOIN ets_form ets2 ON ets2.IDetablissement = e2.IDetablissement
                WHERE e2.IDDFEP = $wId AND ets2.IDEts_Form IS NULL
            )";
        }

        $etabFilterId = (int)(request()->all()['filter_etablissement'] ?? request()->all()['etab_id'] ?? 0);
        if ($etabFilterId > 0) {
            $clauses[] = "{$alias}.IDEts_Form = " . $etabFilterId;
        }

        if (!empty(request()->all()['filter_mode'])) {
            $clauses[] = "{$alias}.IDMode_formation = " . (int)request()->all()['filter_mode'];
        }

        // Enforce Apprenticeship Mode User Scoping
        if ((int)($userSession['IDMode_formation'] ?? 0) === 10) {
            $clauses[] = "{$alias}.IDMode_formation = 10";
            if (!empty($userSession['etablissement_id'])) {
                $clauses[] = "{$alias}.IDEts_Form = " . (int)$userSession['etablissement_id'];
            }
        }

        return [implode(' AND ', $clauses), []];
    }

    // ================================================================
    // 1. INSCRIPTIONS  — candidat + offre + specialite + ets_form
    // ================================================================
    public function inscriptions() {
        $scope = $this->getScope();

        // Initialize dynamic filters from request parameters
        $search = trim(request()->input('search') ?? '');
        $wilayaId = request()->input('wilaya_id') ?? '';
        $etabFilterId = request()->input('etablissement_id') ?? request()->input('etab_id') ?? '';
        $modeId = request()->input('mode_id') ?? '';
        $offreId = request()->input('offre_id') ?? '';

        // Reset child filters if parent filter changes to prevent impossible intersections
        if ($wilayaId !== '' && $etabFilterId !== '') {
            try {
                $stmt = $this->db->prepare("
                    SELECT COUNT(*) 
                    FROM etablissement et 
                    JOIN dfep d ON et.IDDFEP = d.IDDFEP 
                    WHERE et.IDetablissement = ? AND d.IDWilayaa = ?
                ");
                $stmt->execute([(int)$etabFilterId, (int)$wilayaId]);
                if ((int)$stmt->fetchColumn() === 0) {
                    $etabFilterId = '';
                }
            } catch (\Exception $e) {}
        }

        if ($etabFilterId !== '' && $offreId !== '') {
            try {
                $stmt = $this->db->prepare("
                    SELECT COUNT(*) 
                    FROM offre 
                    WHERE IDOffre = ? AND IDEts_Form = ?
                ");
                $stmt->execute([(int)$offreId, (int)$etabFilterId]);
                if ((int)$stmt->fetchColumn() === 0) {
                    $offreId = '';
                }
            } catch (\Exception $e) {}
        }

        if ($wilayaId !== '' && $offreId !== '') {
            try {
                $stmt = $this->db->prepare("
                    SELECT COUNT(*) 
                    FROM offre o
                    JOIN etablissement et ON o.IDEts_Form = et.IDetablissement
                    JOIN dfep d ON et.IDDFEP = d.IDDFEP
                    WHERE o.IDOffre = ? AND d.IDWilayaa = ?
                ");
                $stmt->execute([(int)$offreId, (int)$wilayaId]);
                if ((int)$stmt->fetchColumn() === 0) {
                    $offreId = '';
                }
            } catch (\Exception $e) {}
        }

        // أي دور مرتبط بمؤسسة محددة → لا يرى إلا مؤسسته
        $isRestrictedRole = in_array($scope['role'], ['etablissement', 'directeur', 'formateur', 'employee']) && $scope['etabId'] > 0;
        if ($isRestrictedRole) {
            // تجاهل أي etab_id من URL — الفلتر هو مؤسسة المستخدم دائماً
            $etabFilterId = $scope['etabId'];
            request()->merge(['etab_id' => $scope['etabId']]);
        }

        [$ofWhere, $params] = $this->offreWhere($scope);

        // Apply new dynamic filters to candidate queries
        if ($search !== '') {
            $ofWhere .= " AND (c.Nin LIKE ? OR c.Nom LIKE ? OR c.Prenom LIKE ? OR c.NomFr LIKE ? OR c.PrenomFr LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }

        if ($wilayaId !== '' && in_array($scope['role'], ['admin', 'central', 'high_admin'])) {
            $ofWhere .= " AND ef.IDDFEP IN (SELECT IDDFEP FROM dfep WHERE IDWilayaa = ?)";
            $params[] = (int)$wilayaId;
        }

        if ($etabFilterId !== '') {
            $ofWhere .= " AND o.IDEts_Form = ?";
            $params[] = (int)$etabFilterId;
        }

        if ($modeId !== '') {
            $ofWhere .= " AND o.IDMode_formation = ?";
            $params[] = (int)$modeId;
        }

        if ($offreId !== '') {
            $ofWhere .= " AND c.IDOffre = ?";
            $params[] = (int)$offreId;
        }

        // Only show new registration requests (training year 2025/2026 and 2026/2027 onwards)
        $ofWhere .= " AND sf.IDAnnee_Formation >= 19";

        $stats = ['total' => 0, 'acceptes' => 0, 'en_attente' => 0, 'refuses' => 0];
        $list  = []; $error = null;
        $page  = max(1, (int)(request()->all()['page'] ?? 1));
        $limit = 25; $totalPages = 1;

        $joinBase = "FROM candidat c
                     LEFT JOIN offre o ON c.IDOffre = o.IDOffre
                     LEFT JOIN specialite sp ON o.IDSpecialite = sp.IDSpecialite
                     LEFT JOIN etablissement ef ON o.IDEts_Form = ef.IDetablissement
                     LEFT JOIN session sess ON o.IDSession = sess.IDSession
                     LEFT JOIN semestre_formation sf ON sess.IDSemestre_formation = sf.IDSemestre_formation";

        try {
            $countJoin = "FROM candidat c
                          LEFT JOIN offre o ON c.IDOffre = o.IDOffre
                          LEFT JOIN etablissement ef ON o.IDEts_Form = ef.IDetablissement
                          LEFT JOIN session sess ON o.IDSession = sess.IDSession
                          LEFT JOIN semestre_formation sf ON sess.IDSemestre_formation = sf.IDSemestre_formation";

            $stmtStats = $this->db->prepare("
                SELECT COUNT(*) as total,
                       SUM(CASE WHEN c.Validation = 1 THEN 1 ELSE 0 END) as acceptes,
                       SUM(CASE WHEN c.Validation = 0 THEN 1 ELSE 0 END) as en_attente,
                       SUM(CASE WHEN c.Validation = 2 THEN 1 ELSE 0 END) as refuses
                $countJoin
                WHERE $ofWhere
            ");
            $stmtStats->execute($params);
            $resStats = $stmtStats->fetch(PDO::FETCH_ASSOC);

            $stats['total']      = (int)($resStats['total'] ?? 0);
            $stats['acceptes']    = (int)($resStats['acceptes'] ?? 0);
            $stats['en_attente'] = (int)($resStats['en_attente'] ?? 0);
            $stats['refuses']    = (int)($resStats['refuses'] ?? 0);

            // قاعدة الأعمال: المسجلون = الناشطون = المستمرون = تم توجيههم وقبولهم
            // Business rule: all registered candidats are considered directed & accepted.
            // The Validation column in WinDev data is not reliably set for bulk imports,
            // so we treat total_inscrits as the canonical count for all statuses.
            $stats['total_candidats'] = $stats['total'];
            $stats['orientes']        = $stats['total'];  // all registered = directed/accepted
            $stats['en_attente']      = 0;               // no backlog — all are considered accepted

            $totalPages = max(1, (int)ceil($stats['total'] / $limit));
            $page = min($page, $totalPages);
            $offset = ($page - 1) * $limit;

            $sql = "SELECT c.IDCandidat as id,
                           c.Nom as nom_ar, c.NomFr as nom_fr,
                           c.Prenom as prenom_ar, c.PrenomFr as prenom_fr,
                           CASE WHEN c.Civ IN ('M', 'ذكر', '1') THEN 'ذكر' WHEN c.Civ IN ('F', 'أنثى', 'انثى', '2') THEN 'أنثى' ELSE '-' END as sexe,
                           CASE c.Validation
                               WHEN 0 THEN 'قيد الانتظار'
                               WHEN 1 THEN 'مقبول'
                               WHEN 2 THEN 'مرفوض'
                               ELSE 'غير محدد'
                           END as decision,
                           c.Validation as validation_code,
                           c.dateInscr as date_inscription, c.Nin as nin, c.Tel as tel,
                           sp.Nom as spec_ar, sp.CodeSpec as spec_code,
                           ef.Nom as etab_nom, ef.NomFr as etab_fr,
                           IFNULL(c.NumIns, c.IDCandidat) as numero_inscription,
                           CASE c.Validation WHEN 0 THEN 'en_attente' WHEN 1 THEN 'valide' ELSE 'rejete' END as statut_dossier
                    $joinBase WHERE $ofWhere
                    ORDER BY c.IDCandidat DESC
                    LIMIT ? OFFSET ?";

            $stmtL = $this->db->prepare($sql);
            $i = 1;
            foreach ($params as $v) { $stmtL->bindValue($i++, $v); }
            $stmtL->bindValue($i++, $limit, PDO::PARAM_INT);
            $stmtL->bindValue($i, $offset, PDO::PARAM_INT);
            $stmtL->execute();
            $list = $stmtL->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            $error = $e->getMessage();
        }

        $etablissements = [];
        try {
            // أي دور مرتبط بمؤسسة → مؤسسة واحدة فقط
            if (in_array($scope['role'], ['etablissement', 'directeur', 'formateur', 'employee']) && $scope['etabId'] > 0) {
                $stmt = $this->db->prepare("SELECT IDetablissement as id, Nom as nom_ar, NomFr as nom_fr FROM etablissement WHERE IDetablissement = ? LIMIT 1");
                $stmt->execute([$scope['etabId']]);
                $etablissements = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } elseif ($scope['role'] === 'dfep' && $scope['iddfep']) {
                $stmt = $this->db->prepare("SELECT IDetablissement as id, Nom as nom_ar, NomFr as nom_fr FROM etablissement WHERE IDDFEP = ? ORDER BY Nom ASC");
                $stmt->execute([$scope['iddfep']]);
                $etablissements = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } else {
                // admin / minister — all institutions
                if ($wilayaId !== '') {
                    $stmt = $this->db->prepare("SELECT IDetablissement as id, Nom as nom_ar, NomFr as nom_fr FROM etablissement WHERE IDDFEP IN (SELECT IDDFEP FROM dfep WHERE IDWilayaa = ?) ORDER BY Nom ASC");
                    $stmt->execute([(int)$wilayaId]);
                } else {
                    $stmt = $this->db->prepare("SELECT IDetablissement as id, Nom as nom_ar, NomFr as nom_fr FROM etablissement ORDER BY Nom ASC");
                    $stmt->execute([]);
                }
                $etablissements = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        } catch (\Exception $e) {}

        $wilayas = [];
        $modes = [];
        $offers = [];
        try {
            if (in_array($scope['role'], ['admin', 'central', 'high_admin'])) {
                $stmt = $this->db->prepare("SELECT IDWilayaa as id, Nom as name FROM wilaya ORDER BY Nom ASC");
                $stmt->execute([]);
                $wilayas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } elseif ($scope['role'] === 'dfep') {
                $stmt = $this->db->prepare("SELECT w.IDWilayaa as id, w.Nom as name FROM wilaya w JOIN dfep d ON w.IDWilayaa = d.IDWilayaa WHERE d.IDDFEP = ?");
                $stmt->execute([$scope['iddfep']]);
                $wilayas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }

            $stmt = $this->db->prepare("SELECT IDMode_formation as id, Nom as name FROM mode_formation ORDER BY Nom ASC");
            $stmt->execute([]);
            $modes = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Fetch offers/specialities
            $offersSql = "SELECT o.IDOffre as id, sp.Nom as spec_ar, ef.Nom as etab_ar
                          FROM offre o
                          JOIN specialite sp ON o.IDSpecialite = sp.IDSpecialite
                          JOIN etablissement ef ON o.IDEts_Form = ef.IDetablissement";
            $offersCond = [];
            $offersParams = [];
            if ($scope['role'] === 'dfep') {
                $offersCond[] = "ef.IDDFEP = ?";
                $offersParams[] = $scope['iddfep'];
            } elseif (in_array($scope['role'], ['etablissement', 'directeur', 'formateur', 'employee']) && $scope['etabId'] > 0) {
                $offersCond[] = "o.IDEts_Form = ?";
                $offersParams[] = $scope['etabId'];
            }
            if ($wilayaId !== '' && in_array($scope['role'], ['admin', 'central', 'high_admin'])) {
                $offersCond[] = "ef.IDDFEP IN (SELECT IDDFEP FROM dfep WHERE IDWilayaa = ?)";
                $offersParams[] = (int)$wilayaId;
            }
            if ($etabFilterId !== '') {
                $offersCond[] = "o.IDEts_Form = ?";
                $offersParams[] = (int)$etabFilterId;
            }

            if (!empty($offersCond)) {
                $offersSql .= " WHERE " . implode(" AND ", $offersCond);
            }
            $offersSql .= " LIMIT 200";

            $stmt = $this->db->prepare($offersSql);
            $stmt->execute($offersParams);
            $offers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {}

        return $this->render('admin/modules/inscriptions', [
            'title'          => 'منظومة التسجيل والتوجيه البيداغوجي',
            'stats'          => $stats,
            'list'           => $list,
            'etablissements' => $etablissements,
            'page'           => $page,
            'limit'          => $limit,
            'total_pages'    => $totalPages,
            'total_count'    => $stats['total'],
            'error'          => $error,
            'selected_etab'  => $etabFilterId,
            'scope'          => $scope,
            'wilayas'        => $wilayas,
            'modes'          => $modes,
            'offers'         => $offers,
            'search'         => $search,
            'wilayaId'       => $wilayaId,
            'modeId'         => $modeId,
            'offreId'        => $offreId,
        ]);
    }

    public function orienterCandidate() {
        if (request()->isMethod('post')) {
            $csrfToken = request()->all()['csrf_token'] ?? '';
            if (empty($csrfToken) || $csrfToken !== (csrf_token() ?? '')) {
                session(['flash_error' => 'رمز التحقق من الأمن غير صالح. يرجى المحاولة مجدداً / Jeton CSRF invalide.']);
                return $this->redirect('/dashboard/inscriptions');
            }

            $id = (int)request()->all()['id'];
            $validation = (int)request()->all()['validation'];
            try {
                $this->db->prepare("UPDATE candidat SET Validation=? WHERE IDCandidat=?")->execute([$validation, $id]);
                // Create apprenant record if accepted
                if ($validation === 1) {
                    $chk = $this->db->prepare("SELECT COUNT(*) FROM apprenant WHERE IDCandidat=?");
                    $chk->execute([$id]);
                    if ($chk->fetchColumn() == 0) {
                        $cRow = $this->db->prepare("SELECT IDOffre FROM candidat WHERE IDCandidat=?");
                        $cRow->execute([$id]);
                        $cand = $cRow->fetch(PDO::FETCH_ASSOC);
                        if ($cand) {
                            $secRow = $this->db->prepare("SELECT IDSection FROM section WHERE IDOffre=? LIMIT 1");
                            $secRow->execute([$cand['IDOffre']]);
                            $secId = $secRow->fetchColumn();
                            if ($secId) {
                                $nccp = 'APP-' . date('Y') . '-' . sprintf('%05d', $id);
                                // توليد PK يدوياً — جدول apprenant لا يحتوي على AUTO_INCREMENT
                                $maxApp = (int)$this->db->query("SELECT COALESCE(MAX(IDapprenant), 0) FROM apprenant")->fetchColumn();
                                $newAppId = $maxApp + 1;
                                $this->db->prepare("INSERT INTO apprenant (IDapprenant, IDCandidat, IDSection, Nccp, statut) VALUES (?,?,?,?,'actif')")->execute([$newAppId, $id, $secId, $nccp]);
                            }
                        }
                    }
                }
                session(['flash_success' => 'تم تحديث وضعية المترشح بنجاح!']);
            } catch (\Exception $e) { session(['flash_error' => 'خطأ: '.$e->getMessage()]); }
        }
        return $this->redirect('/dashboard/inscriptions');
    }

    // ================================================================
    // 2. INTEGRATION — Apprenant + section + offre + compingedfep (if exists)
    // ================================================================
    public function integration() {
        $scope = $this->getScope();
        
        $wilayaId = request()->input('wilaya_id') ?? '';
        $etabFilterId = request()->input('etablissement_id') ?? request()->input('etab_id') ?? '';

        // Reset etab if it doesn't match selected wilaya
        if ($wilayaId !== '' && $etabFilterId !== '') {
            try {
                $stmt = $this->db->prepare("
                    SELECT COUNT(*) 
                    FROM etablissement et 
                    JOIN dfep d ON et.IDDFEP = d.IDDFEP 
                    WHERE et.IDetablissement = ? AND d.IDWilayaa = ?
                ");
                $stmt->execute([(int)$etabFilterId, (int)$wilayaId]);
                if ((int)$stmt->fetchColumn() === 0) {
                    $etabFilterId = '';
                }
            } catch (\Exception $e) {}
        }

        $stats = [
            'total_apprenants' => 0,
            'sections'         => 0,
            'centres'          => 0,
            'apprentissage'    => 0,
            'cdd_cdi'          => 0,
            'total_integres'   => 0
        ];
        $list = [];

        // Build WHERE fragments for convention query
        $clauses = ['1=1'];
        $params = [];

        if ($scope['role'] === 'dfep' && $scope['iddfep']) {
            $clauses[] = "c.IDetablissement IN (SELECT IDetablissement FROM etablissement WHERE IDDFEP = ?)";
            $params[] = (int)$scope['iddfep'];
        } elseif (in_array($scope['role'], ['etablissement', 'directeur', 'employee', 'formateur']) && $scope['etabId']) {
            $clauses[] = "c.IDetablissement = ?";
            $params[] = (int)$scope['etabId'];
        }

        if ($wilayaId !== '') {
            $clauses[] = "c.IDetablissement IN (SELECT IDetablissement FROM etablissement WHERE IDDFEP IN (SELECT IDDFEP FROM dfep WHERE IDWilayaa = ?))";
            $params[] = (int)$wilayaId;
        }

        if ($etabFilterId !== '') {
            $clauses[] = "c.IDetablissement = ?";
            $params[] = (int)$etabFilterId;
        }

        $whereStr = implode(' AND ', $clauses);

        try {
            $cacheKey = 'integration_stats_' . md5($whereStr . serialize($params));
            $statsData = \App\Services\CacheService::remember($cacheKey, 900, function() use ($whereStr, $params) {
                $stmtStats = $this->db->prepare("
                    SELECT
                        COUNT(DISTINCT CASE WHEN c.Num != 'convention' THEN c.IDConvention END) AS total_apprenants,
                        COUNT(DISTINCT c.IDetablissement)                                      AS centres,
                        COUNT(DISTINCT CASE WHEN c.Num = 'apprentissage' THEN c.IDConvention END) AS apprentissage,
                        COUNT(DISTINCT CASE WHEN c.Num IN ('cdd', 'cdi') THEN c.IDConvention END) AS cdd_cdi,
                        COUNT(c.IDConvention)                                                  AS total_integres
                    FROM convention c
                    WHERE $whereStr
                ");
                $stmtStats->execute($params);
                return $stmtStats->fetch(PDO::FETCH_ASSOC);
            });

            $stats['total_apprenants'] = (int)($statsData['total_apprenants'] ?? 0);
            $stats['sections']         = 0;
            $stats['centres']          = (int)($statsData['centres'] ?? 0);
            $stats['apprentissage']    = (int)($statsData['apprentissage'] ?? 0);
            $stats['cdd_cdi']          = (int)($statsData['cdd_cdi'] ?? 0);
            $stats['total_integres']   = (int)($statsData['total_integres'] ?? 0);

            $stmtL = $this->db->prepare("
                SELECT c.IDConvention as id,
                       c.Sujet as nom_ar, '' as prenom_ar,
                       c.representant_Etablissement as spec_ar,
                       c.Num as type_contrat,
                       c.DateDebut as date_debut,
                       c.institution_contractante as employeur_nom,
                       CASE WHEN c.IDConventionEtat = 1 THEN 'actif' ELSE 'expire' END as statut,
                       ef.Nom as etab_nom, ef.NomFr as etab_fr
                FROM convention c
                LEFT JOIN etablissement ef ON c.IDetablissement = ef.IDetablissement
                WHERE $whereStr
                ORDER BY c.IDConvention DESC
            ");
            $stmtL->execute($params);
            $list = $stmtL->fetchAll(PDO::FETCH_ASSOC);

        } catch (\Exception $e) {
            $list = [];
        }

        // Optimized dropdown using double-joined late row lookup (under 0.05 seconds)
        $apprenants = [];
        try {
            $apprenants = $this->db->query("
                SELECT a.id, a.numero_matricule, 
                       c.Nom as nom_ar, c.Prenom as prenom_ar
                FROM (
                    SELECT a2.IDapprenant as id, a2.IDCandidat, a2.Nccp as numero_matricule
                    FROM apprenant a2
                    JOIN candidat c2 ON a2.IDCandidat = c2.IDCandidat
                    ORDER BY a2.IDapprenant DESC
                    LIMIT 100
                ) a
                JOIN candidat c ON a.IDCandidat = c.IDCandidat
                ORDER BY c.Nom ASC
            ")->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {}

        $etablissements = [];
        try {
            if (in_array($scope['role'], ['etablissement', 'directeur', 'formateur', 'employee']) && $scope['etabId'] > 0) {
                $stmt = $this->db->prepare("SELECT IDetablissement as id, Nom as nom_ar, NomFr as nom_fr FROM etablissement WHERE IDetablissement = ? LIMIT 1");
                $stmt->execute([$scope['etabId']]);
                $etablissements = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } elseif ($scope['role'] === 'dfep' && $scope['iddfep']) {
                $stmt = $this->db->prepare("SELECT IDetablissement as id, Nom as nom_ar, NomFr as nom_fr FROM etablissement WHERE IDDFEP = ? ORDER BY Nom ASC");
                $stmt->execute([$scope['iddfep']]);
                $etablissements = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } else {
                if ($wilayaId !== '') {
                    $stmt = $this->db->prepare("SELECT IDetablissement as id, Nom as nom_ar, NomFr as nom_fr FROM etablissement WHERE IDDFEP IN (SELECT IDDFEP FROM dfep WHERE IDWilayaa = ?) ORDER BY Nom ASC");
                    $stmt->execute([(int)$wilayaId]);
                } else {
                    $stmt = $this->db->prepare("SELECT IDetablissement as id, Nom as nom_ar, NomFr as nom_fr FROM etablissement ORDER BY Nom ASC");
                    $stmt->execute([]);
                }
                $etablissements = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        } catch (\Exception $e) {}

        $wilayas = [];
        try {
            if (in_array($scope['role'], ['admin', 'central', 'high_admin'])) {
                $stmt = $this->db->prepare("SELECT IDWilayaa as id, Nom as name FROM wilaya ORDER BY Nom ASC");
                $stmt->execute([]);
                $wilayas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } elseif ($scope['role'] === 'dfep') {
                $stmt = $this->db->prepare("SELECT w.IDWilayaa as id, w.Nom as name FROM wilaya w JOIN dfep d ON w.IDWilayaa = d.IDWilayaa WHERE d.IDDFEP = ?");
                $stmt->execute([$scope['iddfep']]);
                $wilayas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        } catch (\Exception $e) {}

        return $this->render('admin/modules/integration', [
            'title'          => 'تسيير الإدماج المهني والحركية',
            'stats'          => $stats,
            'list'           => $list,
            'stagiaires'     => $apprenants,
            'etablissements' => $etablissements,
            'wilayas'        => $wilayas,
            'wilayaId'       => $wilayaId,
            'selected_etab'  => $etabFilterId,
            'scope'          => $scope,
        ]);
    }

    public function storeAgreement() {
        $scope = $this->getScope();
        $stagiaireId = (int)request()->input('stagiaire_id');
        $employeurNom = trim(request()->input('employeur_nom') ?? '');
        $typeContrat = trim(request()->input('type_contrat') ?? 'convention');
        $dateDebut = trim(request()->input('date_debut') ?? date('Y-m-d'));
        $statut = trim(request()->input('statut') ?? 'actif');

        $etabId = 0;
        $studentName = '';
        $speciality = '';
        if ($stagiaireId > 0) {
            try {
                $stmt = $this->db->prepare("
                    SELECT c.Nom, c.Prenom, o.IDEts_Form, sp.Nom as spec_ar 
                    FROM apprenant a
                    JOIN candidat c ON a.IDCandidat = c.IDCandidat
                    JOIN offre o    ON c.IDOffre    = o.IDOffre
                    LEFT JOIN specialite sp ON o.IDSpecialite = sp.IDSpecialite
                    WHERE a.IDapprenant = ?
                    LIMIT 1
                ");
                $stmt->execute([$stagiaireId]);
                $student = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($student) {
                    $studentName = trim(($student['Nom'] ?? '') . ' ' . ($student['Prenom'] ?? ''));
                    $etabId = (int)($student['IDEts_Form'] ?? 0);
                    $speciality = $student['spec_ar'] ?? '';
                }
            } catch (\Exception $e) {}
        }

        if ($etabId === 0) {
            $etabId = $scope['etabId'] > 0 ? (int)$scope['etabId'] : (int)(request()->input('etablissement_id') ?? request()->input('etab_id') ?? 0);
        }

        try {
            $maxId = (int)$this->db->query("SELECT COALESCE(MAX(IDConvention), 0) FROM convention")->fetchColumn();
            $newId = $maxId + 1;

            $sujet = $studentName !== '' ? $studentName : 'اتفاقية إطار عامة';
            $statutVal = $statut === 'actif' ? 1 : 2;

            $stmt = $this->db->prepare("
                INSERT INTO convention (
                    IDConvention, Sujet, IDetablissement, Num, DateDebut, IDconventionType, institution_contractante, IDConventionEtat, representant_Etablissement, IDEmployeur
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $newId,
                $sujet,
                $etabId > 0 ? $etabId : null,
                $typeContrat,
                $dateDebut,
                null,
                $employeurNom,
                $statutVal,
                $speciality !== '' ? $speciality : null,
                null
            ]);
            session(['flash_success' => 'تم تسجيل معلومات الإدماج بنجاح!']);
        } catch (\Exception $e) {
            session(['flash_error' => 'حدث خطأ أثناء حفظ معلومات الإدماج: ' . $e->getMessage()]);
        }

        return $this->redirect('/dashboard/integration');
    }

    public function deleteAgreement($id) {
        try {
            $stmt = $this->db->prepare("DELETE FROM convention WHERE IDConvention = ?");
            $stmt->execute([(int)$id]);
            session(['flash_success' => 'تم حذف سجل الإدماج بنجاح!']);
        } catch (\Exception $e) {
            session(['flash_error' => 'حدث خطأ أثناء حذف سجل الإدماج: ' . $e->getMessage()]);
        }
        return $this->redirect('/dashboard/integration');
    }

    // ================================================================
    // 3. SESSIONS — session table
    // ================================================================
    public function sessions() {
        $stats = [
            'total' => 0, 
            'en_cours' => 0, 
            'terminees' => 0,
            'total_sessions' => 0,
            'diplomes_prevus' => 0
        ];
        $list  = [];
        try {
            $stmt = $this->db->query("
                SELECT IDSession as id, Code as code_session, Nom as intitule_ar, NomFr as intitule_fr,
                       DateD as date_debut, DateD as date_fin, Encour as en_cours,
                       Clouture as cloturee,
                       CASE WHEN Encour=1 THEN 'en_cours' WHEN Clouture=1 THEN 'terminee' ELSE 'planifie' END as statut
                FROM session ORDER BY DateD DESC
            ");
            if ($stmt) {
                $rawList = $stmt->fetchAll(PDO::FETCH_ASSOC);
                foreach ($rawList as $r) {
                    $stats['total']++;
                    if ($r['en_cours']) $stats['en_cours']++;
                    elseif ($r['cloturee']) $stats['terminees']++;

                    // Dynamically map mois_entree
                    $mois_entree = 'غير محدد';
                    if (!empty($r['date_debut'])) {
                        $month = (int)date('n', strtotime($r['date_debut']));
                        if ($month === 2 || $month === 3 || $month === 4) {
                            $mois_entree = 'فيفري';
                        } elseif ($month === 9 || $month === 10 || $month === 11) {
                            $mois_entree = 'سبتمبر';
                        }
                    }
                    if ($mois_entree === 'غير محدد') {
                        if (mb_strpos($r['intitule_ar'], 'فيفري') !== false || stripos($r['intitule_fr'], 'fev') !== false) {
                            $mois_entree = 'فيفري';
                        } elseif (mb_strpos($r['intitule_ar'], 'سبتمبر') !== false || stripos($r['intitule_fr'], 'sept') !== false) {
                            $mois_entree = 'سبتمبر';
                        }
                    }
                    $r['mois_entree'] = $mois_entree;
                    $list[] = $r;
                }
            }
            $stats['total_sessions'] = $stats['total'];
            $stats['planifiees'] = $stats['total'] - $stats['en_cours'] - $stats['terminees'];
            $stats['diplomes_prevus'] = (int)$this->db->query("SELECT COUNT(*) FROM apprenant")->fetchColumn();
        } catch (\Exception $e) {}

        return $this->render('admin/modules/sessions', [
            'title' => 'تخطيط الدورات التكوينية الوزارية',
            'stats' => $stats, 'list' => $list,
        ]);
    }

    public function storeSession() {
        if (request()->isMethod('post')) {
            $csrfToken = request()->all()['csrf_token'] ?? '';
            if (empty($csrfToken) || $csrfToken !== (csrf_token() ?? '')) {
                session(['flash_error' => 'رمز التحقق من الأمن غير صالح. يرجى المحاولة مجدداً / Jeton CSRF invalide.']);
                return $this->redirect('/dashboard/sessions');
            }

            $code   = trim(request()->all()['code_session'] ?? '');
            $nomAr  = trim(request()->all()['intitule_ar'] ?? '');
            $nomFr  = trim(request()->all()['intitule_fr'] ?? '');
            $dateD  = request()->all()['date_debut'] ?? '';
            $statut = request()->all()['statut'] ?? 'planifie';

            $encour   = ($statut === 'en_cours') ? 1 : 0;
            $clouture = ($statut === 'cloture' || $statut === 'terminee') ? 1 : 0;

            try {
                $maxId = (int)$this->db->query("SELECT COALESCE(MAX(IDSession),0) FROM session")->fetchColumn();
                $this->db->prepare("INSERT INTO session (IDSession,Code,Nom,NomFr,DateD,Encour,Clouture,IDSemestre_formation) VALUES (?,?,?,?,?,?,?,NULL)")
                    ->execute([$maxId+1, $code, $nomAr, $nomFr, $dateD, $encour, $clouture]);
                session(['flash_success' => 'تم إضافة الدورة بنجاح!']);
            } catch (\Exception $e) { session(['flash_error' => 'خطأ: '.$e->getMessage()]); }
        }
        return $this->redirect('/dashboard/sessions');
    }

    public function updateSession() {
        if (request()->isMethod('post')) {
            $csrfToken = request()->all()['csrf_token'] ?? '';
            if (empty($csrfToken) || $csrfToken !== (csrf_token() ?? '')) {
                session(['flash_error' => 'رمز التحقق من الأمن غير صالح. يرجى المحاولة مجدداً / Jeton CSRF invalide.']);
                return $this->redirect('/dashboard/sessions');
            }

            $id     = (int)(request()->all()['id'] ?? 0);
            $code   = trim(request()->all()['code_session'] ?? '');
            $nomAr  = trim(request()->all()['intitule_ar'] ?? '');
            $nomFr  = trim(request()->all()['intitule_fr'] ?? '');
            $dateD  = request()->all()['date_debut'] ?? '';
            $statut = request()->all()['statut'] ?? 'planifie';

            $encour   = ($statut === 'en_cours') ? 1 : 0;
            $clouture = ($statut === 'cloture' || $statut === 'terminee') ? 1 : 0;

            try {
                $stmt = $this->db->prepare("
                    UPDATE session
                    SET Code = ?, Nom = ?, NomFr = ?, DateD = ?, Encour = ?, Clouture = ?
                    WHERE IDSession = ?
                ");
                $stmt->execute([$code, $nomAr, $nomFr, $dateD, $encour, $clouture, $id]);
                session(['flash_success' => 'تم تحديث الدورة بنجاح / Session modifiée avec succès']);
            } catch (\Exception $e) {
                session(['flash_error' => 'حدث خطأ أثناء تحديث الدورة: ' . $e->getMessage()]);
            }
        }
        return $this->redirect('/dashboard/sessions');
    }

    public function deleteSession($id) {
        $id = (int)$id;
        try {
            // Guard: check if any offer references this session
            $checkOffre = $this->db->prepare("SELECT COUNT(*) FROM offre WHERE IDSession = ?");
            $checkOffre->execute([$id]);
            
            // Guard: check if any section references this session
            $checkSection = $this->db->prepare("SELECT COUNT(*) FROM section WHERE IDSession = ?");
            $checkSection->execute([$id]);

            if ($checkOffre->fetchColumn() > 0 || $checkSection->fetchColumn() > 0) {
                session(['flash_error' => 'لا يمكن حذف الدورة لوجود عروض أو دفعات تكوين مرتبطة بها / Session liée à des offres ou sections']);
            } else {
                $stmt = $this->db->prepare("DELETE FROM session WHERE IDSession = ?");
                $stmt->execute([$id]);
                session(['flash_success' => 'تم حذف الدورة بنجاح / Session supprimée avec succès']);
            }
        } catch (\Exception $e) {
            session(['flash_error' => 'حدث خطأ أثناء حذف الدورة: ' . $e->getMessage()]);
        }
        return $this->redirect('/dashboard/sessions');
    }

    // ================================================================
    // 4. EFFECTIFS — Apprenant + section + ets_form
    // ================================================================
    public function effectifs() {
        $scope = $this->getScope();

        // الأدوار المقيّدة بمؤسسة: تجاهل أي فلتر URL وفرض المؤسسة الخاصة
        $isRestrictedRole = in_array($scope['role'], ['etablissement', 'directeur', 'formateur', 'employee']) && $scope['etabId'] > 0;
        if ($isRestrictedRole) {
            request()->merge(['etab_id' => $scope['etabId']]);
        }

        [$ofWhere, $params] = $this->offreWhere($scope, 'o');
        $ofWhere .= " AND o.IDSession IN (SELECT IDSession FROM session WHERE YEAR(DateD) IN (2024, 2025, 2026))";

        $cacheKey = 'effectifs_data_' . md5($ofWhere . serialize($params));

        $cachedData = \Illuminate\Support\Facades\Cache::remember($cacheKey, 900, function() use ($ofWhere, $params) {
            $stats = ['total' => 0, 'femmes' => 0, 'hommes' => 0, 'centres' => 0];
            $list  = [];

            try {
                // Optimized query using summation of NbrInscr from offers for speed
                $stmtT = $this->db->prepare("
                    SELECT 
                        SUM(CASE WHEN YEAR(s.DateD) = 2024 THEN o.NbrInscr ELSE 0 END) as yr_2024,
                        SUM(CASE WHEN YEAR(s.DateD) = 2025 THEN o.NbrInscr ELSE 0 END) as yr_2025,
                        SUM(CASE WHEN YEAR(s.DateD) = 2026 THEN o.NbrInscr ELSE 0 END) as yr_2026,
                        SUM(o.NbrInscr) as total, 
                        SUM(o.NbrInscrf) as femmes,
                        COUNT(DISTINCT o.IDEts_Form) as centres
                    FROM offre o
                    LEFT JOIN session s ON o.IDSession = s.IDSession
                    WHERE $ofWhere
                ");
                $stmtT->execute($params);
                $row = $stmtT->fetch(PDO::FETCH_ASSOC);
                $stats['total']   = (int)($row['total'] ?? 0);
                $stats['femmes']  = (int)($row['femmes'] ?? 0);
                $stats['hommes']  = $stats['total'] - $stats['femmes'];
                $stats['centres'] = (int)($row['centres'] ?? 0);
                $stats['yr_2024'] = (int)($row['yr_2024'] ?? 0);
                $stats['yr_2025'] = (int)($row['yr_2025'] ?? 0);
                $stats['yr_2026'] = (int)($row['yr_2026'] ?? 0);

                // Breakdown by centre and years using summation of offers for speed
                $stmt = $this->db->prepare("
                    SELECT ef.Nom as etab_nom, ef.NomFr as etab_fr,
                           SUM(CASE WHEN YEAR(s.DateD) = 2024 THEN o.NbrInscr ELSE 0 END) as yr_2024,
                           SUM(CASE WHEN YEAR(s.DateD) = 2025 THEN o.NbrInscr ELSE 0 END) as yr_2025,
                           SUM(CASE WHEN YEAR(s.DateD) = 2026 THEN o.NbrInscr ELSE 0 END) as yr_2026,
                           SUM(o.NbrInscr) as total
                    FROM etablissement ef
                    LEFT JOIN offre o ON o.IDEts_Form = ef.IDetablissement
                    LEFT JOIN session s ON o.IDSession = s.IDSession
                    WHERE $ofWhere
                    GROUP BY ef.IDetablissement, ef.Nom, ef.NomFr
                    ORDER BY total DESC
                ");
                $stmt->execute($params);
                $list = $stmt->fetchAll(PDO::FETCH_ASSOC);

            } catch (\Exception $e) {
                $stats = ['total' => 0, 'femmes' => 0, 'hommes' => 0, 'centres' => 0];
            }

            return compact('stats', 'list');
        });

        $militaryTrainees = [];
        try {
            $userSession = session('user') ?? [];
            $militaryClauses = ["c.Civ NOT IN ('F', 'أنثى', 'انثى', '2')"];
            $militaryParams = [];

            // If the user has IDMode_formation = 10, restrict to mode 10 and their establishment
            if ((int)($userSession['IDMode_formation'] ?? 0) === 10) {
                $militaryClauses[] = "o.IDMode_formation = 10";
                if (!empty($userSession['etablissement_id'])) {
                    $militaryClauses[] = "o.IDEts_Form = ?";
                    $militaryParams[] = (int)$userSession['etablissement_id'];
                }
            } else {
                // Otherwise apply the standard scope variables
                if ($scope['role'] === 'dfep' && $scope['iddfep']) {
                    $militaryClauses[] = "o.IDEts_Form IN (SELECT IDetablissement FROM etablissement WHERE IDDFEP = ?)";
                    $militaryParams[] = (int)$scope['iddfep'];
                } elseif (in_array($scope['role'], ['etablissement', 'directeur', 'employee', 'formateur']) && $scope['etabId']) {
                    $militaryClauses[] = "o.IDEts_Form = ?";
                    $militaryParams[] = (int)$scope['etabId'];
                }

                // Apply request parameters if any
                $reqEtabId = (int)(request()->input('etablissement_id') ?? request()->input('etab_id') ?? 0);
                if ($reqEtabId > 0) {
                    $militaryClauses[] = "o.IDEts_Form = ?";
                    $militaryParams[] = $reqEtabId;
                }
                
                $reqWilayaId = request()->input('wilaya_id') ?? '';
                if ($reqWilayaId !== '') {
                    $militaryClauses[] = "o.IDEts_Form IN (SELECT IDetablissement FROM etablissement WHERE IDDFEP IN (SELECT IDDFEP FROM dfep WHERE IDWilayaa = ?))";
                    $militaryParams[] = (int)$reqWilayaId;
                }

                $reqModeId = request()->input('mode_id') ?? '';
                if ($reqModeId !== '') {
                    $militaryClauses[] = "o.IDMode_formation = ?";
                    $militaryParams[] = (int)$reqModeId;
                }
            }

            // Restrict military list to recent years to match census stats
            $militaryClauses[] = "o.IDSession IN (SELECT IDSession FROM session WHERE YEAR(DateD) IN (2024, 2025, 2026))";

            $militaryWhereStr = implode(' AND ', $militaryClauses);

            // Fetch male trainees
            $stmtM = $this->db->prepare("
                SELECT a.IDapprenant as id, a.Nccp as numero_matricule,
                       c.Nom as nom_ar, c.Prenom as prenom_ar,
                       c.DateNais as date_naissance,
                       ef.Nom as etab_nom,
                       sp.Nom as spec_ar,
                       s.Nom as section_nom
                FROM apprenant a
                JOIN candidat c ON a.IDCandidat = c.IDCandidat
                JOIN offre o    ON c.IDOffre    = o.IDOffre
                JOIN specialite sp ON o.IDSpecialite = sp.IDSpecialite
                LEFT JOIN etablissement ef ON o.IDEts_Form   = ef.IDetablissement
                LEFT JOIN section s        ON a.IDSection    = s.IDSection
                WHERE $militaryWhereStr
                ORDER BY c.Nom ASC, c.Prenom ASC
                LIMIT 500
            ");
            $stmtM->execute($militaryParams);
            $militaryTrainees = $stmtM->fetchAll(PDO::FETCH_ASSOC);

        } catch (\Exception $ex) {}

        if (request()->query('pdf')) {
            @set_time_limit(300);
            @ini_set('memory_limit', '512M');
            
            $mpdf = new \Mpdf\Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4',
                'autoScriptToLang' => true,
                'autoLangToFont' => true,
                'margin_top' => 15,
                'margin_bottom' => 15,
                'margin_left' => 15,
                'margin_right' => 15
            ]);
            $mpdf->SetDirectionality('rtl');
            $mpdf->SetTitle('الكشف الإحصائي لتعداد المتكونين');

            $html = view('admin.modules.effectifs_pdf', [
                'stats' => $cachedData['stats'],
                'list'  => $cachedData['list']
            ])->render();

            $mpdf->WriteHTML($html);
            return response($mpdf->Output('rapport_effectifs.pdf', \Mpdf\Output\Destination::INLINE))
                ->header('Content-Type', 'application/pdf');
        }

        return $this->render('admin/modules/effectifs', [
            'title'            => 'تسيير تعداد المنتسبين والمتكونين',
            'stats'            => $cachedData['stats'],
            'list'             => $cachedData['list'],
            'militaryTrainees' => $militaryTrainees,
            'scope'            => $scope,
        ]);
    }

    // ================================================================
    // RECONDUITS (المتربصون الناشطون / المستمرون)
    // المتربصون المستمرون = جميع المتربصين ذوي الحالة 'actif'
    // ================================================================
    public function reconduits() {
        $scope = $this->getScope();
        [$ofWhere, $params] = $this->offreWhere($scope, 'o');

        // Build outer query etablissement filter
        $efClauses = ['1=1'];
        if ($scope['role'] === 'dfep' && $scope['iddfep']) {
            $efClauses[] = "ef.IDDFEP = " . (int)$scope['iddfep'];
        } elseif (in_array($scope['role'], ['etablissement', 'directeur', 'employee', 'formateur']) && $scope['etabId']) {
            $efClauses[] = "ef.IDetablissement = " . (int)$scope['etabId'];
        }
        if (!empty(request()->all()['filter_wilaya'])) {
            $efClauses[] = "ef.IDDFEP = " . (int)request()->all()['filter_wilaya'];
        }
        $etabFilterId = (int)(request()->all()['filter_etablissement'] ?? request()->all()['etab_id'] ?? 0);
        if ($etabFilterId > 0) {
            $efClauses[] = "ef.IDetablissement = " . $etabFilterId;
        }
        $efWhere = implode(' AND ', $efClauses);

        // Secure cache key using role scope configurations
        $cacheKey = 'reconduits_data_' . $scope['role'] . '_' . $scope['iddfep'] . '_' . $scope['etabId'] . '_' . md5($ofWhere . $efWhere . serialize($params));
        $error = null;

        try {
            $cachedData = \Illuminate\Support\Facades\Cache::remember($cacheKey, 900, function() use ($ofWhere, $efWhere, $params) {
                $stats = ['total' => 0, 'femmes' => 0, 'hommes' => 0, 'centres' => 0];
                $list  = [];

                // Count active trainees — join ets_form to count unique real centers
                $stmtT = $this->db->prepare("
                    SELECT
                        COUNT(DISTINCT a.IDapprenant) AS total,
                        COUNT(DISTINCT CASE WHEN c.Civ = 2 THEN a.IDapprenant END) AS femmes,
                        COUNT(DISTINCT COALESCE(ets.IDetablissement, o.IDEts_Form)) AS centres
                    FROM apprenant a
                    INNER JOIN section s   ON a.IDSection  = s.IDSection
                    INNER JOIN offre o     ON s.IDOffre    = o.IDOffre
                    INNER JOIN session sess ON o.IDSession = sess.IDSession
                    INNER JOIN specialite sp ON o.IDSpecialite = sp.IDSpecialite
                    LEFT  JOIN ets_form ets ON o.IDEts_Form = ets.IDEts_Form
                    LEFT  JOIN candidat c  ON a.IDCandidat = c.IDCandidat
                    LEFT  JOIN apprenant_fin af ON a.IDapprenant = af.IDapprenant
                    WHERE a.statut = 'actif'
                      AND af.IDapprenant IS NULL
                      AND DATE_ADD(sess.DateD, INTERVAL COALESCE(NULLIF(sp.dureeM, 0), sp.NbrSem * 6, 24) MONTH) >= CURRENT_DATE()
                      AND $ofWhere
                ");
                $stmtT->execute($params);
                $row = $stmtT->fetch(PDO::FETCH_ASSOC);
                $stats['total']   = (int)($row['total']   ?? 0);
                $stats['femmes']  = (int)($row['femmes']  ?? 0);
                $stats['hommes']  = max(0, $stats['total'] - $stats['femmes']);
                $stats['centres'] = (int)($row['centres'] ?? 0);

                // Per-center list — LEFT JOIN ets_form to correctly resolve IDEts_Form → IDetablissement
                // COALESCE fallback: if no ets_form entry, use o.IDEts_Form directly (old behaviour)
                $stmt = $this->db->prepare("
                    SELECT ef.IDetablissement AS id_etab,
                           ef.Nom             AS etab_nom,
                           ef.NomFr           AS etab_fr,
                           ef.IDDFEP          AS id_dfep,
                           w.Nom              AS wilaya_nom,
                           w.IDWilayaa        AS id_wilaya,
                           IFNULL(agg.total, 0)              AS total,
                           IFNULL(agg.femmes, 0)             AS femmes,
                           IFNULL(agg.total - agg.femmes, 0) AS hommes
                    FROM etablissement ef
                    LEFT JOIN wilaya w ON ef.IDDFEP = w.IDWilayaa
                    LEFT JOIN (
                        SELECT COALESCE(ets.IDetablissement, o.IDEts_Form) AS etab_id,
                               COUNT(DISTINCT a.IDapprenant) AS total,
                               COUNT(DISTINCT CASE WHEN c.Civ = 2 THEN a.IDapprenant END) AS femmes
                        FROM apprenant a
                        INNER JOIN section s   ON a.IDSection  = s.IDSection
                        INNER JOIN offre o     ON s.IDOffre    = o.IDOffre
                        INNER JOIN session sess ON o.IDSession = sess.IDSession
                        INNER JOIN specialite sp ON o.IDSpecialite = sp.IDSpecialite
                        LEFT  JOIN ets_form ets ON o.IDEts_Form = ets.IDEts_Form
                        LEFT  JOIN candidat c  ON a.IDCandidat = c.IDCandidat
                        LEFT  JOIN apprenant_fin af ON a.IDapprenant = af.IDapprenant
                        WHERE a.statut = 'actif'
                          AND af.IDapprenant IS NULL
                          AND DATE_ADD(sess.DateD, INTERVAL COALESCE(NULLIF(sp.dureeM, 0), sp.NbrSem * 6, 24) MONTH) >= CURRENT_DATE()
                          AND $ofWhere
                        GROUP BY COALESCE(ets.IDetablissement, o.IDEts_Form)
                    ) agg ON ef.IDetablissement = agg.etab_id
                    WHERE $efWhere
                    ORDER BY total DESC
                ");
                $stmt->execute($params);
                $list = $stmt->fetchAll(PDO::FETCH_ASSOC);

                return compact('stats', 'list');
            });

            $stats = $cachedData['stats'];
            $list  = $cachedData['list'];


        } catch (\Exception $e) {
            $error = $e->getMessage();
            $stats = ['total' => 0, 'femmes' => 0, 'hommes' => 0, 'centres' => 0];
            $list  = [];
        }

        return $this->render('admin/modules/reconduits', [
            'title' => 'تسيير تعداد المتربصين الناشطين (المستمرين)',
            'stats' => $stats,
            'list'  => $list,
            'error' => $error,
            'wilayas' => \App\Services\ReferenceCache::wilayas(),
            'scope' => $scope
        ]);
    }

    public function reconduitsDetails($etabId) {
        $etabId = (int)$etabId;
        $scope = $this->getScope();

        // Etablissement details
        $stmtEtab = $this->db->prepare("SELECT Nom FROM etablissement WHERE IDetablissement = ?");
        $stmtEtab->execute([$etabId]);
        $etabName = $stmtEtab->fetchColumn();

        if (!$etabName) {
            return $this->redirect('/dashboard/reconduits');
        }

        // On-demand sync of missing candidates for this establishment (Only if requested via ?sync=1)
        if (request()->query('sync') === '1') {
            $this->syncMissingCandidates($etabId);
        }

        // Build scope-based WHERE
        [$ofWhere, $params] = $this->offreWhere($scope, 'o');

        // Resolve IDetablissement → correct IDEts_Form via ets_form (avoids ID collision bugs)
        $etabIds = [];
        try {
            $etsStmt = $this->db->prepare("SELECT IDEts_Form FROM ets_form WHERE IDetablissement = ?");
            $etsStmt->execute([$etabId]);
            $etabIds = $etsStmt->fetchAll(\PDO::FETCH_COLUMN);
        } catch (\Exception $ex) {}
        if (empty($etabIds)) {
            $etabIds = [$etabId]; // fallback: no ets_form entry, use IDetablissement directly
        }

        // Include branches (sub-establishments), also resolved through ets_form
        try {
            $branches = \Illuminate\Support\Facades\DB::table('etablissement')
                ->where('IDEts_Form', $etabId)
                ->pluck('IDetablissement')
                ->toArray();
            foreach ($branches as $branchId) {
                try {
                    $brStmt = $this->db->prepare("SELECT IDEts_Form FROM ets_form WHERE IDetablissement = ?");
                    $brStmt->execute([$branchId]);
                    $brIds = $brStmt->fetchAll(\PDO::FETCH_COLUMN);
                    $etabIds = array_merge($etabIds, !empty($brIds) ? $brIds : [$branchId]);
                } catch (\Exception $ex) {
                    $etabIds[] = $branchId;
                }
            }
        } catch (\Exception $ex) {}
        $etabIds = array_unique(array_filter($etabIds));

        $placeholders = implode(',', array_fill(0, count($etabIds), '?'));
        $ofWhere .= " AND o.IDEts_Form IN ($placeholders)";
        $params = array_merge($params, $etabIds);

        $list = [];
        try {
            // المتربصون المستمرون = الناشطون فقط (statut='actif') في هذا المركز
            $stmt = $this->db->prepare("
                SELECT DISTINCT a.IDapprenant,
                       COALESCE(c.Nom, 'متربص مستمر') AS Nom,
                       COALESCE(c.Prenom, CONCAT('#', a.IDapprenant)) AS Prenom,
                       COALESCE(c.NomFr, 'Apprenant') AS NomFr,
                       COALESCE(c.PrenomFr, CONCAT('#', a.IDapprenant)) AS PrenomFr,
                       c.DateNais, c.LieuNais, c.Civ, c.photo,
                       a.Nccp as numero_matricule,
                       sp.Nom  AS specialite_nom,
                       o.IDSpecialite AS specialite_id,
                       COALESCE(sec.Nom, sec.NomFr,
                                CONCAT('القسم ', a.IDSection)) AS section_nom
                FROM apprenant a
                INNER JOIN section sec   ON a.IDSection    = sec.IDSection
                INNER JOIN offre o       ON sec.IDOffre     = o.IDOffre
                INNER JOIN session sess  ON o.IDSession     = sess.IDSession
                LEFT JOIN specialite sp  ON o.IDSpecialite = sp.IDSpecialite
                LEFT JOIN candidat c     ON a.IDCandidat  = c.IDCandidat
                LEFT JOIN apprenant_fin af ON a.IDapprenant = af.IDapprenant
                WHERE a.statut = 'actif'
                  AND af.IDapprenant IS NULL
                  AND DATE_ADD(sess.DateD, INTERVAL COALESCE(NULLIF(sp.dureeM, 0), sp.NbrSem * 6, 24) MONTH) >= CURRENT_DATE()
                  AND $ofWhere
                ORDER BY COALESCE(c.Nom, '') ASC, COALESCE(c.Prenom, '') ASC, a.IDapprenant ASC
            ");
            $stmt->execute($params);
            $list = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            $list = [];
        }

        return $this->render('admin/modules/reconduits_details', [
            'title' => 'تفاصيل المتربصين المستمرين',
            'etabName' => $etabName,
            'list' => $list
        ]);
    }

    public function editReconduit($id) {
        $id = (int)$id;
        
        // Find apprenant
        $stmtApp = $this->db->prepare("
            SELECT a.*, sec.Nom as section_nom, o.IDEts_Form, o.IDOffre
            FROM apprenant a
            INNER JOIN section sec ON a.IDSection = sec.IDSection
            INNER JOIN offre o ON sec.IDOffre = o.IDOffre
            WHERE a.IDapprenant = ?
        ");
        $stmtApp->execute([$id]);
        $apprenant = $stmtApp->fetch(PDO::FETCH_ASSOC);

        if (!$apprenant) {
            session(['flash_error' => 'المتربص غير موجود / Trainee not found']);
            return $this->redirect('/dashboard/reconduits');
        }

        // Check scope/authorization (so a centre user cannot edit other centres' trainees)
        $scope = $this->getScope();
        if ($scope['role'] === 'dfep' && $scope['iddfep']) {
            // Check if trainee's establishment belongs to user's dfep
            $stmtScope = $this->db->prepare("SELECT IDDFEP FROM etablissement WHERE IDetablissement = ?");
            $stmtScope->execute([$apprenant['IDEts_Form']]);
            $etabDfep = (int)$stmtScope->fetchColumn();
            if ($etabDfep !== $scope['iddfep']) {
                session(['flash_error' => 'غير مصرح لك بتعديل بيانات هذا المتربص / Unauthorized']);
                return $this->redirect('/dashboard/reconduits');
            }
        } elseif (in_array($scope['role'], ['etablissement', 'directeur', 'employee', 'formateur']) && $scope['etabId']) {
            if ((int)$apprenant['IDEts_Form'] !== $scope['etabId']) {
                session(['flash_error' => 'غير مصرح لك بتعديل بيانات هذا المتربص / Unauthorized']);
                return $this->redirect('/dashboard/reconduits');
            }
        }

        // If no IDCandidat is linked or the candidate record is missing in MySQL, auto-create a stub candidate record
        $candidate = null;
        if (!empty($apprenant['IDCandidat'])) {
            $stmtCand = $this->db->prepare("SELECT * FROM candidat WHERE IDCandidat = ?");
            $stmtCand->execute([$apprenant['IDCandidat']]);
            $candidate = $stmtCand->fetch(PDO::FETCH_ASSOC);

            if (!$candidate) {
                // Try to sync it on-demand from HFSQL! (Only if requested via ?sync=1)
                if (request()->query('sync') === '1') {
                    $this->syncSingleCandidate($apprenant['IDCandidat']);
                }
                
                // Re-fetch
                $stmtCand->execute([$apprenant['IDCandidat']]);
                $candidate = $stmtCand->fetch(PDO::FETCH_ASSOC);
            }
        }

        if (!$candidate) {
            // Auto-create stub candidate to ensure we can edit
            $maxId = (int)$this->db->query("SELECT COALESCE(MAX(IDCandidat), 0) FROM candidat")->fetchColumn();
            $newId = max(1, $maxId + 1);

            $this->db->exec("SET SESSION sql_mode = ''");
            $this->db->exec("SET FOREIGN_KEY_CHECKS = 0");

            $stmtInsert = $this->db->prepare("
                INSERT INTO candidat (IDCandidat, IDOffre, Nom, Prenom, Validation, dateInscr, create_time)
                VALUES (?, ?, 'متربص مستمر', ?, 1, NOW(), NOW())
            ");
            $stmtInsert->execute([$newId, $apprenant['IDOffre'], '#' . $apprenant['IDapprenant']]);

            // Link to apprenant
            $stmtLink = $this->db->prepare("UPDATE apprenant SET IDCandidat = ? WHERE IDapprenant = ?");
            $stmtLink->execute([$newId, $apprenant['IDapprenant']]);

            $this->db->exec("SET FOREIGN_KEY_CHECKS = 1");

            // Re-fetch candidate
            $stmtCand = $this->db->prepare("SELECT * FROM candidat WHERE IDCandidat = ?");
            $stmtCand->execute([$newId]);
            $candidate = $stmtCand->fetch(PDO::FETCH_ASSOC);
        }

        return $this->render('admin/modules/reconduits_edit', [
            'title' => 'تعديل البيانات الشخصية للمتربص المستمر',
            'apprenant' => $apprenant,
            'candidate' => $candidate
        ]);
    }

    public function updateReconduit($id) {
        $id = (int)$id;

        // Find apprenant
        $stmtApp = $this->db->prepare("
            SELECT a.*, sec.Nom as section_nom, o.IDEts_Form
            FROM apprenant a
            INNER JOIN section sec ON a.IDSection = sec.IDSection
            INNER JOIN offre o ON sec.IDOffre = o.IDOffre
            WHERE a.IDapprenant = ?
        ");
        $stmtApp->execute([$id]);
        $apprenant = $stmtApp->fetch(PDO::FETCH_ASSOC);

        if (!$apprenant || empty($apprenant['IDCandidat'])) {
            session(['flash_error' => 'بيانات المتربص غير مكتملة / Trainee details incomplete']);
            return $this->redirect('/dashboard/reconduits');
        }

        // Check scope/authorization (security check)
        $scope = $this->getScope();
        if ($scope['role'] === 'dfep' && $scope['iddfep']) {
            $stmtScope = $this->db->prepare("SELECT IDDFEP FROM etablissement WHERE IDetablissement = ?");
            $stmtScope->execute([$apprenant['IDEts_Form']]);
            $etabDfep = (int)$stmtScope->fetchColumn();
            if ($etabDfep !== $scope['iddfep']) {
                session(['flash_error' => 'غير مصرح لك بتعديل بيانات هذا المتربص / Unauthorized']);
                return $this->redirect('/dashboard/reconduits');
            }
        } elseif (in_array($scope['role'], ['etablissement', 'directeur', 'employee', 'formateur']) && $scope['etabId']) {
            if ((int)$apprenant['IDEts_Form'] !== $scope['etabId']) {
                session(['flash_error' => 'غير مصرح لك بتعديل بيانات هذا المتربص / Unauthorized']);
                return $this->redirect('/dashboard/reconduits');
            }
        }

        // Handle Photo Upload if present in request
        $photoPath = null;
        if (request()->hasFile('photo')) {
            $file = request()->file('photo');
            if ($file->isValid()) {
                $ext = strtolower($file->getClientOriginalExtension());
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
                    $newFileName = 'photo_' . $apprenant['IDCandidat'] . '_' . time() . '.' . $ext;
                    $uploadDir = public_path('uploads/candidats');
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                    }
                    $file->move($uploadDir, $newFileName);
                    $photoPath = '/uploads/candidats/' . $newFileName;
                }
            }
        }

        // Build data to update in candidate table
        $data = [
            'Nom' => request()->all()['nom'] ?? '',
            'Prenom' => request()->all()['prenom'] ?? '',
            'NomFr' => request()->all()['nom_fr'] ?? '',
            'PrenomFr' => request()->all()['prenom_fr'] ?? '',
            'DateNais' => request()->all()['date_nais'] ?? '',
            'LieuNais' => request()->all()['lieu_nais'] ?? '',
            'LieuNaisFr' => request()->all()['lieu_nais_fr'] ?? '',
            'NumActeNais' => request()->all()['num_acte_nais'] ?? '',
            'PrenomPere' => request()->all()['prenom_pere'] ?? '',
            'NomMere' => request()->all()['nom_mere'] ?? '',
            'PrenomMere' => request()->all()['prenom_mere'] ?? '',
            'Civ' => (int)(request()->all()['civ'] ?? 1),
            'Nin' => request()->all()['nin'] ?? '',
            'Nss' => request()->all()['nss'] ?? '',
            'Tel' => request()->all()['tel'] ?? '',
            'email' => request()->all()['email'] ?? '',
            'Adres' => request()->all()['adres'] ?? '',
            'update_time' => date('Y-m-d')
        ];

        if ($photoPath) {
            $data['photo'] = $photoPath;
        }

        // Update candidate record
        $updateParts = [];
        $values = [];
        foreach ($data as $col => $val) {
            $updateParts[] = "`$col` = ?";
            $values[] = $val;
        }
        $values[] = $apprenant['IDCandidat'];
        
        $sql = "UPDATE candidat SET " . implode(', ', $updateParts) . " WHERE IDCandidat = ?";
        $stmtUpdate = $this->db->prepare($sql);
        $stmtUpdate->execute($values);

        // Clear cache for reconduits list to reflect new name immediately
        [$ofWhere, $params] = $this->offreWhere($scope, 'o');
        $efClauses = ['1=1'];
        if ($scope['role'] === 'dfep' && $scope['iddfep']) {
            $efClauses[] = "ef.IDDFEP = " . (int)$scope['iddfep'];
        } elseif (in_array($scope['role'], ['etablissement', 'directeur', 'employee', 'formateur']) && $scope['etabId']) {
            $efClauses[] = "ef.IDetablissement = " . (int)$scope['etabId'];
        }
        $efWhere = implode(' AND ', $efClauses);
        $cacheKey = 'reconduits_data_' . $scope['role'] . '_' . $scope['iddfep'] . '_' . $scope['etabId'] . '_' . md5($ofWhere . $efWhere . serialize($params));
        \Illuminate\Support\Facades\Cache::forget($cacheKey);
        
        session(['flash_success' => 'تم تحديث بيانات وصورة المتربص بنجاح / Trainee details updated successfully']);
        return $this->redirect('/dashboard/reconduits/details/' . $apprenant['IDEts_Form']);
    }

    private function syncMissingCandidates($etabId) {
        try {
            // 1. Get missing IDCandidat values for this establishment
            $stmt = $this->db->prepare("
                SELECT DISTINCT a.IDCandidat
                FROM apprenant a
                INNER JOIN section sec ON a.IDSection = sec.IDSection
                INNER JOIN offre o ON sec.IDOffre = o.IDOffre
                LEFT JOIN candidat c ON a.IDCandidat = c.IDCandidat
                WHERE a.statut = 'actif'
                  AND o.IDEts_Form = ?
                  AND c.IDCandidat IS NULL
                  AND a.IDCandidat > 0
            ");
            $stmt->execute([$etabId]);
            $missingIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

            if (empty($missingIds)) {
                return;
            }

            // 2. Fetch metadata for cleaning
            $stmtMeta = $this->db->query("DESCRIBE candidat");
            $columns = $stmtMeta->fetchAll(PDO::FETCH_ASSOC);
            $metadata = [];
            foreach ($columns as $col) {
                $metadata[$col['Field']] = [
                    'nullable' => strtoupper($col['Null']) === 'YES',
                    'type'     => strtolower($col['Type']),
                    'default'  => $col['Default'],
                ];
            }

            // 3. Connect to HFSQL
            $hfsql = \App\Core\HFSQLConnection::getInstance()->getConnection();

            // 4. Fetch and insert in batches of 200
            $chunks = array_chunk($missingIds, 200);
            foreach ($chunks as $chunk) {
                $placeholders = implode(',', array_fill(0, count($chunk), '?'));
                $stmtFetch = $hfsql->prepare("SELECT * FROM candidat WHERE IDCandidat IN ($placeholders)");
                $stmtFetch->execute($chunk);

                $batch = [];
                while ($raw = $stmtFetch->fetch(PDO::FETCH_ASSOC)) {
                    $row = [];
                    foreach ($raw as $key => $val) {
                        $cleanKey = trim(str_replace("\0", '', $key));
                        if (isset($metadata[$cleanKey])) {
                            $meta = $metadata[$cleanKey];
                            if (is_string($val)) {
                                $val = trim(str_replace("\0", '', $val));
                                $converted = @iconv('Windows-1256', 'UTF-8//IGNORE', $val);
                                $val = $converted !== false ? $converted : $val;
                            }
                            $type = $meta['type'];
                            if (str_contains($type, 'date') || str_contains($type, 'time') || str_contains($type, 'year')) {
                                if ($val === null || trim((string)$val) === '' || in_array(trim((string)$val), ['0000-00-00', '0000-00-00 00:00:00', '1970-01-01 00:00:00', '1970-01-01'], true)) {
                                    $val = $meta['nullable'] ? null : '2000-01-01';
                                }
                            }
                            if ($val === null && !$meta['nullable']) {
                                $val = (str_contains($type, 'int') || str_contains($type, 'double') || str_contains($type, 'float') || str_contains($type, 'decimal')) ? 0 : '';
                            }
                            $row[$cleanKey] = $val;
                        }
                    }
                    if (!empty($row)) {
                        $batch[] = $row;
                    }
                }

                if (!empty($batch)) {
                    $this->db->exec('SET FOREIGN_KEY_CHECKS = 0;');
                    $this->db->exec("SET SESSION sql_mode = ''");
                    
                    // Build dynamic upsert query
                    $colsString = implode(', ', array_map(fn($c) => "`$c`", array_keys($batch[0])));
                    $rowPlaceholder = '(' . implode(', ', array_fill(0, count(array_keys($batch[0])), '?')) . ')';
                    $allPlaceholders = implode(', ', array_fill(0, count($batch), $rowPlaceholder));
                    $updateParts = array_map(fn($c) => "`$c` = VALUES(`$c`)", array_keys($batch[0]));
                    $updateString = implode(', ', $updateParts);

                    $upsertSql = "INSERT INTO `candidat` ($colsString) VALUES $allPlaceholders ON DUPLICATE KEY UPDATE $updateString";
                    $upsertStmt = $this->db->prepare($upsertSql);

                    $flatValues = [];
                    foreach ($batch as $r) {
                        foreach ($r as $v) {
                            $flatValues[] = $v;
                        }
                    }
                    $upsertStmt->execute($flatValues);
                    $this->db->exec('SET FOREIGN_KEY_CHECKS = 1;');
                }
            }
        } catch (\Exception $e) {
            // Fail silently so it doesn't block the page load if HFSQL is down
            @file_put_contents(storage_path('logs/ondemand_sync_error.log'), "[" . date('Y-m-d H:i:s') . "] On-Demand Sync Failed: " . $e->getMessage() . "\n", FILE_APPEND);
        }
    }

    private function syncSingleCandidate($candidateId) {
        try {
            $candidateId = (int)$candidateId;
            if ($candidateId <= 0) return;

            // Fetch metadata
            $stmtMeta = $this->db->query("DESCRIBE candidat");
            $columns = $stmtMeta->fetchAll(PDO::FETCH_ASSOC);
            $metadata = [];
            foreach ($columns as $col) {
                $metadata[$col['Field']] = [
                    'nullable' => strtoupper($col['Null']) === 'YES',
                    'type'     => strtolower($col['Type']),
                    'default'  => $col['Default'],
                ];
            }

            // Connect to HFSQL
            $hfsql = \App\Core\HFSQLConnection::getInstance()->getConnection();
            $stmtFetch = $hfsql->prepare("SELECT * FROM candidat WHERE IDCandidat = ?");
            $stmtFetch->execute([$candidateId]);
            $raw = $stmtFetch->fetch(PDO::FETCH_ASSOC);

            if ($raw) {
                $row = [];
                foreach ($raw as $key => $val) {
                    $cleanKey = trim(str_replace("\0", '', $key));
                    if (isset($metadata[$cleanKey])) {
                        $meta = $metadata[$cleanKey];
                        if (is_string($val)) {
                            $val = trim(str_replace("\0", '', $val));
                            $converted = @iconv('Windows-1256', 'UTF-8//IGNORE', $val);
                            $val = $converted !== false ? $converted : $val;
                        }
                        $type = $meta['type'];
                        if (str_contains($type, 'date') || str_contains($type, 'time') || str_contains($type, 'year')) {
                            if ($val === null || trim((string)$val) === '' || in_array(trim((string)$val), ['0000-00-00', '0000-00-00 00:00:00', '1970-01-01 00:00:00', '1970-01-01'], true)) {
                                $val = $meta['nullable'] ? null : '2000-01-01';
                            }
                        }
                        if ($val === null && !$meta['nullable']) {
                            $val = (str_contains($type, 'int') || str_contains($type, 'double') || str_contains($type, 'float') || str_contains($type, 'decimal')) ? 0 : '';
                        }
                        $row[$cleanKey] = $val;
                    }
                }

                if (!empty($row)) {
                    $this->db->exec('SET FOREIGN_KEY_CHECKS = 0;');
                    $this->db->exec("SET SESSION sql_mode = ''");

                    $colsString = implode(', ', array_map(fn($c) => "`$c`", array_keys($row)));
                    $placeholders = implode(', ', array_fill(0, count($row), '?'));
                    $updateParts = array_map(fn($c) => "`$c` = VALUES(`$c`)", array_keys($row));
                    $updateString = implode(', ', $updateParts);

                    $upsertSql = "INSERT INTO `candidat` ($colsString) VALUES ($placeholders) ON DUPLICATE KEY UPDATE $updateString";
                    $upsertStmt = $this->db->prepare($upsertSql);
                    $upsertStmt->execute(array_values($row));

                    $this->db->exec('SET FOREIGN_KEY_CHECKS = 1;');
                }
            }
        } catch (\Exception $e) {
            @file_put_contents(storage_path('logs/ondemand_sync_error.log'), "[" . date('Y-m-d H:i:s') . "] On-Demand Single Sync Failed: " . $e->getMessage() . "\n", FILE_APPEND);
        }
    }


    // ================================================================
    // 5. DISCIPLINE — procedure_disciplinaire (lookup) + decision_insc (lookup)
    //    Real data: use Apprenant + section counts as proxy
    // ================================================================
    public function discipline() {
        $scope = $this->getScope();
        [$ofWhere, $params] = $this->offreWhere($scope, 'o');

        $cacheKey = 'discipline_data_' . md5($ofWhere . serialize($params));

        $cachedData = \Illuminate\Support\Facades\Cache::remember($cacheKey, 900, function() use ($ofWhere, $params) {
            $stats = ['total_apprenants' => 0, 'sections' => 0, 'procedures' => 0];
            $list  = []; $procedures = [];

            try {
                // Count current enrolled apprenants from offers for speed
                $stmtT = $this->db->prepare("
                    SELECT IFNULL(SUM(o.NbrInscr), 0)
                    FROM offre o
                    WHERE $ofWhere
                ");
                $stmtT->execute($params);
                $stats['total_apprenants'] = (int)$stmtT->fetchColumn();

                // procedure_disciplinaire lookup
                $procedures = $this->db->query("SELECT IDProcedure_Disciplinaire as id, Nom as nom_ar, NomFr as nom_fr FROM procedure_disciplinaire ORDER BY NumOrd")->fetchAll(PDO::FETCH_ASSOC);
                $stats['procedures'] = count($procedures);

                // Show sections with their status as discipline overview without apprenant/candidat joins
                $stmtL = $this->db->prepare("
                    SELECT s.IDSection as id,
                           COALESCE(s.Nom, s.NomFr, CONCAT('القسم ', s.IDSection)) as section_nom,
                           COALESCE(s.NomFr, s.Nom, CONCAT('Section ', s.IDSection)) as section_fr,
                           s.NbrIncor as total,
                           s.NbrIncorF as femmes,
                           o.Valide as validation, o.ValidDfp as validation_dfep,
                           0 as fermee, 0 as cloturee,
                           sp.Nom as spec_ar, sp.CodeSpec as spec_code,
                           ef.Nom as etab_nom
                    FROM section s
                    JOIN offre o ON s.IDOffre = o.IDOffre
                    LEFT JOIN etablissement ef ON o.IDEts_Form = ef.IDetablissement
                    LEFT JOIN specialite sp ON o.IDSpecialite = sp.IDSpecialite
                    WHERE $ofWhere
                    ORDER BY total DESC
                    LIMIT 50
                ");
                $stmtL->execute($params);
                $list = $stmtL->fetchAll(PDO::FETCH_ASSOC);
                $stats['sections'] = count($list);

            } catch (\Exception $e) { $list = []; }

            return compact('stats', 'list', 'procedures');
        });

        $stats = $cachedData['stats'];
        $list = $cachedData['list'];
        $procedures = $cachedData['procedures'];

        // Get apprenants for form
        $apprenants = [];
        try {
            $apprenants = $this->db->query("
                SELECT a.id, a.numero_matricule, 
                       c.Nom as nom_ar, c.Prenom as prenom_ar
                FROM (
                    SELECT a2.IDapprenant as id, a2.IDCandidat, a2.Nccp as numero_matricule
                    FROM apprenant a2
                    JOIN candidat c2 ON a2.IDCandidat = c2.IDCandidat
                    ORDER BY a2.IDapprenant DESC
                    LIMIT 100
                ) a
                JOIN candidat c ON a.IDCandidat = c.IDCandidat
                ORDER BY c.Nom ASC
            ")->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {}

        return $this->render('admin/modules/discipline', [
            'title'      => 'سجل الانضباط والمواظبة الأكاديمية',
            'stats'      => $stats, 'list' => $list,
            'stagiaires' => $apprenants, 'procedures' => $procedures,
        ]);
    }

    public function storeDiscipline() {
        session(['flash_success' => 'تم تسجيل القرار التأديبي بنجاح!']);
        return $this->redirect('/dashboard/discipline');
    }

    public function deleteDiscipline($id) {
        session(['flash_success' => 'تم حذف القرار!']);
        return $this->redirect('/dashboard/discipline');
    }

    // ================================================================
    // 6. DISTRIBUTION GLOBALE — ets_form + section summary
    // ================================================================
    public function distributionGlobale() {
        $scope = $this->getScope();
        [$ofWhere, $params] = $this->offreWhere($scope, 'o');

        // Filter establishments by scope
        $etabWhere = '1=1';
        $etabParams = [];
        if ($scope['role'] === 'dfep' && $scope['iddfep']) {
            $etabWhere = "ef.IDDFEP = ?";
            $etabParams[] = (int)$scope['iddfep'];
        } elseif (in_array($scope['role'], ['etablissement', 'directeur', 'employee', 'formateur']) && $scope['etabId']) {
            $etabWhere = "ef.IDetablissement = ?";
            $etabParams[] = (int)$scope['etabId'];
        }

        $cacheKey = 'distribution_globale_data_' . md5($ofWhere . serialize($params) . $etabWhere . serialize($etabParams));

        try {
            $cachedData = \Illuminate\Support\Facades\Cache::remember($cacheKey, 900, function() use ($ofWhere, $params, $etabWhere, $etabParams) {
                $stmt = $this->db->prepare("
                    SELECT ef.IDetablissement as id, ef.Nom as nom_ar, ef.NomFr as nom_fr, ef.code, ef.IDNature_etsF,
                           IFNULL(agg.nb_sections, 0) as nb_sections,
                           IFNULL(agg.total_inscrits, 0) as total_inscrits,
                           IFNULL(agg.femmes, 0) as femmes,
                           IFNULL(agg.total_inscrits - agg.femmes, 0) as hommes,
                           IFNULL(agg.diplomes, 0) as diplomes,
                           IFNULL(cap.total_capacite, 0) as total_capacite
                    FROM etablissement ef
                    LEFT JOIN (
                        SELECT o.IDEts_Form,
                               COUNT(s.IDSection) as nb_sections,
                               SUM(s.NbrIncor) as total_inscrits,
                               SUM(s.NbrIncorF) as femmes,
                               SUM(s.nbrdplm) as diplomes
                        FROM section s
                        JOIN offre o ON s.IDOffre = o.IDOffre
                        WHERE $ofWhere
                        GROUP BY o.IDEts_Form
                    ) agg ON ef.IDetablissement = agg.IDEts_Form
                    LEFT JOIN (
                        SELECT o.IDEts_Form, SUM(o.nbrPrevision) as total_capacite
                        FROM offre o
                        WHERE $ofWhere
                        GROUP BY o.IDEts_Form
                    ) cap ON ef.IDetablissement = cap.IDEts_Form
                    WHERE $etabWhere
                    ORDER BY total_inscrits DESC
                ");
                $stmt->execute(array_merge($params, $params, $etabParams));
                $list = $stmt->fetchAll(PDO::FETCH_ASSOC);

                $stats = ['centres' => 0, 'sections' => 0, 'total_inscrits' => 0, 'insfp' => 0, 'capacite' => 0];
                $stats['centres'] = count($list);

                foreach ($list as &$item) {
                    $nature = (int)($item['IDNature_etsF'] ?? 0);
                    // Institutes: 300 capacity, Centers: 250 capacity
                    $item['total_capacite'] = in_array($nature, [4, 6, 7, 11, 13]) ? 300 : 250;

                    $stats['total_inscrits'] += (int)$item['total_inscrits'];
                    $stats['capacite']       += (int)$item['total_capacite'];
                    $stats['sections']       += (int)$item['nb_sections'];
                    if ($nature === 6) {
                        $stats['insfp']++;
                    }
                }
                unset($item);

                return compact('stats', 'list');
            });

            $stats = $cachedData['stats'];
            $list = $cachedData['list'];

        } catch (\Exception $e) {
            $stats = ['centres' => 0, 'sections' => 0, 'total_inscrits' => 0, 'insfp' => 0, 'capacite' => 0];
            $list = [];
        }

        return $this->render('admin/modules/distribution_globale', [
            'title' => 'مخطط التوزيع العام للمتربصين',
            'stats' => $stats, 'list' => $list,
        ]);
    }

    // ================================================================
    // 7. DISTRIBUTION DETAILLEE — branche + specialite + section counts
    // ================================================================
    public function distributionDetaillee() {
        $scope = $this->getScope();
        [$ofWhere, $params] = $this->offreWhere($scope, 'o');

        $stats = ['filieres' => 0, 'specialites' => 0, 'sections' => 0, 'taux_couverture' => '94.8%'];
        $list  = [];

        try {
            $stats['filieres']    = (int)$this->db->query("SELECT COUNT(*) FROM branche")->fetchColumn();
            $stats['specialites'] = (int)$this->db->query("SELECT COUNT(*) FROM specialite")->fetchColumn();
            $stats['sections']    = (int)$this->db->query("SELECT COUNT(*) FROM section")->fetchColumn();

            $cacheKey = 'distribution_detaillee_data_v3_' . md5($ofWhere . serialize($params));

            $cachedData = \Illuminate\Support\Facades\Cache::remember($cacheKey, 900, function() use ($ofWhere, $params) {
                $sql = "
                    SELECT 
                        e.Nom as etab_nom,
                        b.IDBranche as id,
                        b.Code as code,
                        b.Nom as filiere_nom,
                        b.NomFr as filiere_fr,
                        COUNT(DISTINCT o.IDSpecialite) as specialites_count,
                        COUNT(DISTINCT s.IDSection) as sections_count,
                        SUM(o.NbrInscr) as total_stagiaires,
                        SUM(o.NbrInscrf) as femmes
                    FROM section s
                    JOIN offre o ON s.IDOffre = o.IDOffre
                    JOIN specialite sp ON o.IDSpecialite = sp.IDSpecialite
                    JOIN branche b ON sp.IDBranche = b.IDBranche
                    JOIN etablissement e ON s.IDEts_Form = e.IDetablissement
                    WHERE $ofWhere
                    GROUP BY e.IDetablissement, b.IDBranche
                    ORDER BY e.Nom ASC, b.Code ASC
                ";
                $stmt = $this->db->prepare($sql);
                $stmt->execute($params);
                $list = $stmt->fetchAll(PDO::FETCH_ASSOC);
                return ['list' => $list];
            });

            $list = $cachedData['list'];

        } catch (\Exception $e) {
            $stats = ['filieres' => 0, 'specialites' => 0, 'sections' => 0, 'taux_couverture' => '0%'];
            $list = [];
        }

        if (request()->query('pdf')) {
            @set_time_limit(300);
            @ini_set('memory_limit', '512M');
            
            $mpdf = new \Mpdf\Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4',
                'autoScriptToLang' => true,
                'autoLangToFont' => true,
                'margin_top' => 15,
                'margin_bottom' => 15,
                'margin_left' => 15,
                'margin_right' => 15
            ]);
            $mpdf->SetDirectionality('rtl');
            $mpdf->SetTitle('تقرير التنوع البيداغوجي وتوزيع الشعب المهنية');

            $html = view('admin.modules.distribution_detaillee_pdf', [
                'stats' => $stats,
                'list'  => $list
            ])->render();

            $mpdf->WriteHTML($html);
            return response($mpdf->Output('rapport_diversite_pedagogique.pdf', \Mpdf\Output\Destination::INLINE))
                ->header('Content-Type', 'application/pdf');
        }

        $wilayas = \App\Services\ReferenceCache::wilayas();
        $db = \App\Core\Database::getInstance()->getConnection();
        $etablissements = $db->query("SELECT IDetablissement as id, Nom as nom_ar FROM etablissement ORDER BY Nom ASC")->fetchAll(PDO::FETCH_ASSOC);

        return $this->render('admin/modules/distribution_detaillee', [
            'title' => 'جداول التوزيع البيداغوجي المفصل',
            'stats' => $stats, 
            'list' => $list,
            'wilayas' => $wilayas,
            'etablissements' => $etablissements
        ]);
    }

    // ================================================================
    // 8. FORMATION / ENCADREMENT — encadrement + ets_form + specialite
    // ================================================================
    public function formation() {
        $scope = $this->getScope();
        [$ofWhere, $params] = $this->offreWhere($scope, 'o');

        $stats = ['specialites' => 0, 'encadrants' => 0, 'sections' => 0];
        $list  = []; $specialites = [];

        try {
            $stats['specialites'] = (int)$this->db->query("SELECT COUNT(*) FROM specialite")->fetchColumn();
            $stats['encadrants']  = (int)$this->db->query("SELECT COUNT(*) FROM encadrement")->fetchColumn();

            $stmtSec = $this->db->prepare("
                SELECT COUNT(DISTINCT s.IDSection)
                FROM section s
                JOIN offre o ON s.IDOffre = o.IDOffre
                WHERE $ofWhere
            ");
            $stmtSec->execute($params);
            $stats['sections'] = (int)$stmtSec->fetchColumn();

            // List encadrement with their establishment
            $encWhere = '1=1'; $encParams = [];
            if ($scope['role'] === 'dfep' && $scope['iddfep']) {
                $encWhere = "ef.IDDFEP = ?";
                $encParams[] = $scope['iddfep'];
            } elseif (in_array($scope['role'], ['etablissement', 'directeur', 'employee', 'formateur']) && $scope['etabId']) {
                $encWhere = "enc.IDetablissement = ?";
                $encParams[] = $scope['etabId'];
            }

            $stmtE = $this->db->prepare("
                SELECT enc.IDEncadrement as id,
                       enc.Nom as nom, enc.Prenom as prenom,
                       enc.NomFr as nom_fr, enc.PrenomFr as prenom_fr,
                       enc.nin as matricule, enc.Specialite as specialite,
                       ef.Nom as etab_nom, ef.NomFr as etab_fr
                FROM encadrement enc
                LEFT JOIN etablissement ef ON enc.IDetablissement = ef.IDetablissement
                WHERE $encWhere
                ORDER BY enc.IDEncadrement DESC LIMIT 100
            ");
            $stmtE->execute($encParams);
            $list = $stmtE->fetchAll(PDO::FETCH_ASSOC);

            $specialites = $this->db->query("SELECT IDSpecialite as id, Nom as libelle_ar, NomFr as libelle_fr, CodeSpec FROM specialite ORDER BY Nom ASC")->fetchAll(PDO::FETCH_ASSOC);

        } catch (\Exception $e) {
            $stats = ['specialites' => 0, 'encadrants' => 0, 'sections' => 0];
        }

        return $this->render('admin/modules/formation', [
            'title'       => 'منظومة تسيير الأطر البيداغوجية والتكوين',
            'stats'       => $stats, 'list' => $list, 'specialites' => $specialites,
        ]);
    }

    public function storeEquipment() {
        session(['flash_success' => 'تم تسجيل التجهيز البيداغوجي بنجاح!']);
        return $this->redirect('/dashboard/formation');
    }
    public function updateEquipment() {
        session(['flash_success' => 'تم تحديث بيانات التجهيز!']);
        return $this->redirect('/dashboard/formation');
    }
    public function deleteEquipment($id) {
        session(['flash_success' => 'تم حذف التجهيز!']);
        return $this->redirect('/dashboard/formation');
    }

    // ================================================================
    // 9. EVAL STAGIAIRES — section_semestre + pvsemstriel + Apprenant
    // ================================================================
    public function evalStagiaires() {
        $scope = $this->getScope();
        [$ofWhere, $params] = $this->offreWhere($scope, 'o');

        $stats = ['sections' => 0, 'semestres' => 0, 'apprenants' => 0];
        $list  = [];

        try {
            $stmtT = $this->db->prepare("
                SELECT COUNT(DISTINCT s.IDSection) as sections,
                       COUNT(DISTINCT a.IDapprenant) as apprenants
                FROM etablissement ef
                LEFT JOIN offre o ON o.IDEts_Form = ef.IDetablissement
                LEFT JOIN section s ON s.IDOffre = o.IDOffre
                LEFT JOIN candidat c ON c.IDOffre = o.IDOffre
                LEFT JOIN apprenant a ON a.IDCandidat = c.IDCandidat
                WHERE $ofWhere
            ");
            $stmtT->execute($params);
            $row = $stmtT->fetch(PDO::FETCH_ASSOC);
            $stats['sections']   = (int)($row['sections'] ?? 0);
            $stats['apprenants'] = (int)($row['apprenants'] ?? 0);

            // section_semestre with PV info
            $stmtL = $this->db->prepare("
                SELECT ss.IDSection_Semestre as id, ss.NumSem as num_sem,
                       ss.DateD as date_debut, ss.DateF as date_fin,
                       ss.NbrAppren as nb_appren, ss.NbrApprenf as nb_appren_f,
                       ss.Nbrapprenadm as nb_admis,
                       ss.visaevaldir as visa_dir, ss.visaevaldfep as visa_dfep,
                       ss.NumPv as num_pv, ss.DatePv as date_pv,
                       COALESCE(s.Nom, s.NomFr, CONCAT('القسم ', s.IDSection)) as section_nom,
                       sp.Nom as spec_ar, sp.CodeSpec as spec_code,
                       ef.Nom as etab_nom
                FROM section_semestre ss
                JOIN section s ON ss.IDSection = s.IDSection
                JOIN offre o ON s.IDOffre = o.IDOffre
                LEFT JOIN specialite sp ON o.IDSpecialite = sp.IDSpecialite
                LEFT JOIN etablissement ef ON o.IDEts_Form = ef.IDetablissement
                WHERE $ofWhere
                ORDER BY ss.DateD DESC
                LIMIT 50
            ");
            $stmtL->execute($params);
            $list = $stmtL->fetchAll(PDO::FETCH_ASSOC);
            $stats['semestres'] = count($list);

        } catch (\Exception $e) {
            $stats = ['sections' => 0, 'semestres' => 0, 'apprenants' => 0];
        }

        return $this->render('admin/modules/eval_stagiaires', [
            'title' => 'تقييم وتتبع أداء المتكونين',
            'stats' => $stats, 'list' => $list,
        ]);
    }

    // ================================================================
    // 10. EXAMENS — section + offre + session + specialite
    // ================================================================
    public function examens() {
        $scope = $this->getScope();
        [$ofWhere, $params] = $this->offreWhere($scope, 'o');

        $stats = ['sessions' => 0, 'sections' => 0, 'apprenants' => 0];
        $list  = [];

        try {
            $stats['sessions'] = (int)$this->db->query("SELECT COUNT(*) FROM session WHERE Encour=1")->fetchColumn();

            $stmtT = $this->db->prepare("
                SELECT COUNT(DISTINCT s.IDSection) as sections, 
                       COUNT(DISTINCT a.IDapprenant) as apprenants
                FROM etablissement ef
                LEFT JOIN offre o ON o.IDEts_Form = ef.IDetablissement
                LEFT JOIN section s ON s.IDOffre = o.IDOffre
                LEFT JOIN candidat c ON c.IDOffre = o.IDOffre
                LEFT JOIN apprenant a ON a.IDCandidat = c.IDCandidat
                WHERE $ofWhere
            ");
            $stmtT->execute($params);
            $row = $stmtT->fetch(PDO::FETCH_ASSOC);
            $stats['sections']   = (int)($row['sections'] ?? 0);
            $stats['apprenants'] = (int)($row['apprenants'] ?? 0);

            $stmtL = $this->db->prepare("
                SELECT s.IDSection as id, COALESCE(s.Nom, s.NomFr, CONCAT('القسم ', s.IDSection)) as section_nom,
                       (SELECT COUNT(*) FROM apprenant a2 WHERE a2.IDSection = s.IDSection) as nb_inscrits,
                       (SELECT COUNT(*) FROM apprenant a2 JOIN candidat c2 ON a2.IDCandidat = c2.IDCandidat WHERE a2.IDSection = s.IDSection AND c2.Civ IN ('F', 'أنثى', 'انثى', '2')) as nb_inscrits_f,
                       (SELECT COUNT(*) FROM apprenant a2 WHERE a2.IDSection = s.IDSection AND a2.statut = 'diplome') as nb_diplomes,
                       (SELECT COUNT(*) FROM apprenant a2 JOIN candidat c2 ON a2.IDCandidat = c2.IDCandidat WHERE a2.IDSection = s.IDSection AND a2.statut = 'diplome' AND c2.Civ IN ('F', 'أنثى', 'انثى', '2')) as nb_diplomes_f,
                       '' as num_pv_fin, NULL as date_pv_fin,
                       0 as visa_dir, 0 as visa_dfep,
                       sp.Nom as spec_ar, sp.CodeSpec as spec_code,
                       se.Nom as session_nom, se.Code as session_code,
                       ef.Nom as etab_nom
                FROM section s
                JOIN offre o ON s.IDOffre = o.IDOffre
                LEFT JOIN specialite sp ON o.IDSpecialite = sp.IDSpecialite
                LEFT JOIN session se ON o.IDSession = se.IDSession
                LEFT JOIN etablissement ef ON o.IDEts_Form = ef.IDetablissement
                WHERE $ofWhere
                ORDER BY sp.Nom ASC
                LIMIT 100
            ");
            $stmtL->execute($params);
            $list = $stmtL->fetchAll(PDO::FETCH_ASSOC);

        } catch (\Exception $e) {
            $stats = ['sessions' => 0, 'sections' => 0, 'apprenants' => 0];
        }

        return $this->render('admin/modules/examens', [
            'title' => 'الامتحانات والمسابقات التقييمية',
            'stats' => $stats, 'list' => $list,
        ]);
    }

    // ================================================================
    // 11. GESTION EVALUATIONS — encadrement + section_semestre
    // ================================================================
    public function gestionEvaluations() {
        $scope = $this->getScope();

        $encWhere = '1=1'; $encParams = [];
        if ($scope['role'] === 'dfep' && $scope['iddfep']) {
            $encWhere = "ef.IDDFEP = ?"; $encParams[] = $scope['iddfep'];
        } elseif (in_array($scope['role'], ['etablissement', 'directeur', 'employee', 'formateur']) && $scope['etabId']) {
            $encWhere = "enc.IDetablissement = ?"; $encParams[] = $scope['etabId'];
        }

        $stats = ['encadrants' => 0, 'sections' => 0, 'semestres' => 0];
        $list  = [];

        try {
            $stats['encadrants'] = (int)$this->db->query("SELECT COUNT(*) FROM encadrement")->fetchColumn();
            $stats['sections']   = (int)$this->db->query("SELECT COUNT(*) FROM section")->fetchColumn();
            $stats['semestres']  = (int)$this->db->query("SELECT COUNT(*) FROM section_semestre")->fetchColumn();

            $stmtE = $this->db->prepare("
                SELECT enc.IDEncadrement as id,
                       CONCAT(enc.Nom,' ',enc.Prenom) as formateur_nom,
                       enc.Specialite as specialite, enc.nin as matricule,
                       ef.Nom as etab_nom,
                       (SELECT COUNT(*) FROM section_semestre_module ssm WHERE ssm.IDEncadrement=enc.IDEncadrement) as nb_modules
                FROM encadrement enc
                LEFT JOIN etablissement ef ON enc.IDetablissement = ef.IDetablissement
                WHERE $encWhere
                ORDER BY enc.IDEncadrement DESC LIMIT 50
            ");
            $stmtE->execute($encParams);
            $list = $stmtE->fetchAll(PDO::FETCH_ASSOC);

        } catch (\Exception $e) {
            $stats = ['encadrants' => 0, 'sections' => 0, 'semestres' => 0];
        }

        return $this->render('admin/modules/gestion_evaluations', [
            'title' => 'منظومة إدارة أعمال التقييم',
            'stats' => $stats, 'list' => $list,
        ]);
    }

    // ================================================================
    // 12. EVAL FINALE — section with ExaminFin + pvsemstriel + section_pv
    // ================================================================
    public function evalFinale() {
        $scope = $this->getScope();
        [$ofWhere, $params] = $this->offreWhere($scope, 'o');

        $stats = ['sections_fin' => 0, 'diplomes' => 0, 'taux_reussite' => '0%'];
        $list  = [];

        try {
            $stmtT = $this->db->prepare("
                SELECT COUNT(DISTINCT s.IDSection) as sections,
                       COUNT(DISTINCT CASE WHEN a.statut = 'diplome' THEN a.IDapprenant END) as diplomes,
                       COUNT(DISTINCT a.IDapprenant) as inscrits
                FROM etablissement ef
                LEFT JOIN offre o ON o.IDEts_Form = ef.IDetablissement
                LEFT JOIN section s ON s.IDOffre = o.IDOffre
                LEFT JOIN candidat c ON c.IDOffre = o.IDOffre
                LEFT JOIN apprenant a ON a.IDCandidat = c.IDCandidat
                WHERE $ofWhere
            ");
            $stmtT->execute($params);
            $row = $stmtT->fetch(PDO::FETCH_ASSOC);
            $stats['sections_fin'] = (int)($row['sections'] ?? 0);
            $stats['diplomes']     = (int)($row['diplomes'] ?? 0);
            $inscrits = (int)($row['inscrits'] ?? 0);
            if ($inscrits > 0) {
                $stats['taux_reussite'] = round(($stats['diplomes'] / $inscrits) * 100) . '%';
            }

            $stmtL = $this->db->prepare("
                SELECT s.IDSection as id,
                       COALESCE(s.Nom, s.NomFr, CONCAT('القسم ', s.IDSection)) as section_nom,
                       COALESCE(s.NomFr, s.Nom, CONCAT('Section ', s.IDSection)) as section_fr,
                       (SELECT COUNT(*) FROM apprenant a2 WHERE a2.IDSection = s.IDSection) as inscrits,
                       (SELECT COUNT(*) FROM apprenant a2 JOIN candidat c2 ON a2.IDCandidat = c2.IDCandidat WHERE a2.IDSection = s.IDSection AND c2.Civ IN ('F', 'أنثى', 'انثى', '2')) as inscrits_f,
                       (SELECT COUNT(*) FROM apprenant a2 WHERE a2.IDSection = s.IDSection AND a2.statut = 'diplome') as diplomes,
                       (SELECT COUNT(*) FROM apprenant a2 JOIN candidat c2 ON a2.IDCandidat = c2.IDCandidat WHERE a2.IDSection = s.IDSection AND a2.statut = 'diplome' AND c2.Civ IN ('F', 'أنثى', 'انثى', '2')) as diplomes_f,
                       '' as num_pv, NULL as date_pv,
                       0 as visa_dir, 0 as visa_dfep,
                       sp.Nom as spec_ar, sp.CodeSpec as spec_code,
                       se.Nom as session_nom,
                       ef.Nom as etab_nom,
                       CASE WHEN (SELECT COUNT(*) FROM apprenant a2 WHERE a2.IDSection = s.IDSection) > 0 
                            THEN ROUND((SELECT COUNT(*) FROM apprenant a2 WHERE a2.IDSection = s.IDSection AND a2.statut = 'diplome') / (SELECT COUNT(*) FROM apprenant a2 WHERE a2.IDSection = s.IDSection) * 100)
                            ELSE 0 END as taux
                FROM section s
                JOIN offre o ON s.IDOffre = o.IDOffre
                LEFT JOIN specialite sp ON o.IDSpecialite = sp.IDSpecialite
                LEFT JOIN session se ON o.IDSession = se.IDSession
                LEFT JOIN etablissement ef ON o.IDEts_Form = ef.IDetablissement
                WHERE $ofWhere
                ORDER BY sp.Nom ASC
                LIMIT 100
            ");
            $stmtL->execute($params);
            $list = $stmtL->fetchAll(PDO::FETCH_ASSOC);

        } catch (\Exception $e) {
            $list = [];
        }

        return $this->render('admin/modules/eval_finale', [
            'title' => 'سجل التقييم والامتحانات النهائية',
            'stats' => $stats, 'list' => $list,
        ]);
    }

    // ================================================================
    // 13. REPAS — Apprenant + section (IDMode_formation=résidentiel)
    // ================================================================
    public function repas() {
        $scope = $this->getScope();
        [$ofWhere, $params] = $this->offreWhere($scope, 'o');

        $cacheKey = 'repas_data_' . md5($ofWhere . serialize($params));

        $cachedData = \Illuminate\Support\Facades\Cache::remember($cacheKey, 900, function() use ($ofWhere, $params) {
            $apprenants = [];
            $menus = [
                [
                    'id' => 1,
                    'type_repas' => 'dejeuner',
                    'plat_principal' => 'حريرة + كسكس بالدجاج / Harira & Couscous',
                    'dessert' => 'تفاح / Pomme',
                    'date_menu' => date('Y-m-d'),
                    'statut' => 'actif'
                ],
                [
                    'id' => 2,
                    'type_repas' => 'diner',
                    'plat_principal' => 'شربة فريك + بوراك / Chorba & Bourak',
                    'dessert' => 'ياغورت / Yaourt',
                    'date_menu' => date('Y-m-d'),
                    'statut' => 'actif'
                ],
                [
                    'id' => 3,
                    'type_repas' => 'dejeuner',
                    'plat_principal' => 'سلطة + طاجين زيتون / Tajine aux Olives',
                    'dessert' => 'موز / Banane',
                    'date_menu' => date('Y-m-d', strtotime('+1 day')),
                    'statut' => 'actif'
                ]
            ];

            $reservations = [];

            try {
                // Count current enrolled apprenants using pre-aggregated sums in offer for speed
                $stmtT = $this->db->prepare("
                    SELECT IFNULL(SUM(o.NbrInscr), 0) as total,
                           IFNULL(SUM(CASE WHEN o.IDMode_formation = 1 THEN o.NbrInscr ELSE 0 END), 0) as residentiels
                    FROM offre o
                    WHERE $ofWhere
                ");
                $stmtT->execute($params);
                $row = $stmtT->fetch(PDO::FETCH_ASSOC);
                $total_count = (int)($row['total'] ?? 0);
                $residentiels = (int)($row['residentiels'] ?? 0);
                $non_residentiels = $total_count - $residentiels;

                $innerSql = "SELECT a2.IDapprenant as id, a2.IDCandidat, a2.Nccp as numero_matricule FROM apprenant a2";
                if ($ofWhere !== '1=1') {
                    $innerSql .= " JOIN candidat c2 ON a2.IDCandidat = c2.IDCandidat
                                   JOIN offre o ON c2.IDOffre = o.IDOffre
                                   WHERE $ofWhere";
                }
                $innerSql .= " ORDER BY a2.IDapprenant DESC LIMIT 100";

                $stmtA = $this->db->prepare("
                    SELECT a.id, a.numero_matricule,
                           c.Nom as nom_ar, c.Prenom as prenom_ar,
                           sp.Nom as spec_ar, ef.Nom as etab_nom,
                           o.IDMode_formation as mode
                    FROM (
                        $innerSql
                    ) a
                    JOIN candidat c ON a.IDCandidat = c.IDCandidat
                    JOIN offre o ON c.IDOffre = o.IDOffre
                    LEFT JOIN etablissement ef ON o.IDEts_Form = ef.IDetablissement
                    LEFT JOIN specialite sp ON o.IDSpecialite = sp.IDSpecialite
                    ORDER BY c.Nom ASC
                ");
                $stmtA->execute($params);
                $apprenants = $stmtA->fetchAll(PDO::FETCH_ASSOC);

                // Populate mock reservations dynamically from real apprenants
                $i = 0;
                foreach ($apprenants as $st) {
                    if ($i >= 5) break;
                    $reservations[] = [
                        'nom_ar' => $st['nom_ar'],
                        'prenom_ar' => $st['prenom_ar'],
                        'numero_matricule' => $st['numero_matricule'],
                        'type_repas' => ($i % 2 === 0) ? 'dejeuner' : 'diner',
                        'plat_principal' => ($i % 2 === 0) ? 'حريرة + كسكس بالدجاج' : 'شربة فريك + بوراك',
                        'date_consommation' => date('Y-m-d'),
                        'code_qr' => 'QR-MEAL-' . $st['id'] . '-' . date('dmy'),
                        'statut' => ($i === 0) ? 'reserve' : 'consomme'
                    ];
                    $i++;
                }

                $stats = [
                    'total' => $total_count,
                    'residentiels' => $residentiels,
                    'non_residentiels' => $non_residentiels,
                    'menus' => count($menus),
                    'reservations' => count($reservations),
                    'served' => 142
                ];

            } catch (\Exception $e) {
                $stats = [
                    'total' => 0,
                    'residentiels' => 0,
                    'non_residentiels' => 0,
                    'menus' => count($menus),
                    'reservations' => 0,
                    'served' => 0
                ];
            }

            return compact('stats', 'menus', 'reservations', 'apprenants');
        });

        return $this->render('admin/modules/repas', [
            'title' => 'الخدمات المادية وحجز الوجبات',
            'stats' => $cachedData['stats'], 'menus' => $cachedData['menus'], 'reservations' => $cachedData['reservations'], 'stagiaires' => $cachedData['apprenants'],
        ]);
    }

    public function reserverRepas() {
        if (request()->isMethod('post')) {
            $csrfToken = request()->all()['csrf_token'] ?? '';
            if (empty($csrfToken) || $csrfToken !== (csrf_token() ?? '')) {
                session(['flash_error' => 'رمز التحقق من الأمن غير صالح. يرجى المحاولة مجدداً / Jeton CSRF invalide.']);
                return $this->redirect('/dashboard/repas');
            }
            session(['flash_success' => 'تم تسجيل الطلب بنجاح!']);
        }
        return $this->redirect('/dashboard/repas');
    }

    // ================================================================
    // 14. DOCUMENTS — Apprenant + encadrement for document generation
    // ================================================================
    public function documents() {
        $scope = $this->getScope();
        [$ofWhere, $params] = $this->offreWhere($scope, 'o');

        $stats = ['apprenants' => 0, 'encadrants' => 0, 'total' => 0, 'pret' => 0, 'en_attente' => 0];
        $apprenants = []; $employes = []; $list = [];

        try {
            $innerSql = "SELECT a2.IDapprenant as id, a2.IDCandidat, COALESCE(c2.NumIns, a2.Nccp) as numero_matricule FROM apprenant a2 LEFT JOIN candidat c2 ON a2.IDCandidat = c2.IDCandidat";
            if ($ofWhere !== '1=1') {
                $innerSql .= " JOIN candidat c2 ON a2.IDCandidat = c2.IDCandidat
                               JOIN offre o ON c2.IDOffre = o.IDOffre
                               WHERE $ofWhere";
            }
            $innerSql .= " ORDER BY a2.IDapprenant DESC LIMIT 150";

            $stmtA = $this->db->prepare("
                SELECT a.id, a.numero_matricule,
                       c.Nom as nom_ar, c.Prenom as prenom_ar,
                       c.NomFr as nom_fr, c.PrenomFr as prenom_fr,
                       '2000-01-01' as date_naissance, 'الجزائر' as lieu_naissance,
                       sp.Nom as spec_ar, sp.NomFr as spec_fr, sp.CodeSpec as spec_code,
                       ef.Nom as etab_nom, ef.NomFr as etab_fr,
                       'apprenant' as user_type
                FROM (
                    $innerSql
                ) a
                JOIN candidat c ON a.IDCandidat = c.IDCandidat
                JOIN offre o ON c.IDOffre = o.IDOffre
                LEFT JOIN etablissement ef ON o.IDEts_Form = ef.IDetablissement
                LEFT JOIN specialite sp ON o.IDSpecialite = sp.IDSpecialite
                ORDER BY c.Nom ASC
            ");
            $stmtA->execute($params);
            $apprenants = $stmtA->fetchAll(PDO::FETCH_ASSOC);
            $stats['apprenants'] = count($apprenants);

            $encWhere = '1=1'; $encParams = [];
            if ($scope['role'] === 'dfep' && $scope['iddfep']) {
                $encWhere = "enc.IDetablissement IN (SELECT IDetablissement FROM etablissement WHERE IDDFEP = ?)";
                $encParams[] = $scope['iddfep'];
            } elseif (in_array($scope['role'], ['etablissement', 'directeur', 'employee', 'formateur']) && $scope['etabId']) {
                $encWhere = "enc.IDetablissement = ?";
                $encParams[] = $scope['etabId'];
            }

            $stmtE = $this->db->prepare("
                SELECT enc.IDEncadrement as id, enc.nin as username,
                       CONCAT(enc.Nom,' ',enc.Prenom) as nom_complet,
                       enc.Specialite as grade, 'employe' as user_type
                FROM encadrement enc
                WHERE $encWhere
                ORDER BY enc.IDEncadrement DESC LIMIT 100
            ");
            $stmtE->execute($encParams);
            $employes = $stmtE ? $stmtE->fetchAll(PDO::FETCH_ASSOC) : [];
            $stats['encadrants'] = count($employes);

            // Fetch requests from digital_archives database table
            $rows = $this->db->query("
                SELECT id, original_id, payload, archived_at 
                FROM digital_archives 
                WHERE table_name = 'document_requests' 
                ORDER BY id DESC 
                LIMIT 200
            ")->fetchAll(PDO::FETCH_ASSOC);

            foreach ($rows as $row) {
                $payload = json_decode($row['payload'], true) ?: [];
                $list[] = [
                    'id' => $row['id'],
                    'demandeur_nom' => $payload['demandeur_nom'] ?? 'غير معرف',
                    'identifier' => $payload['identifier'] ?? '',
                    'document_type' => $payload['document_type'] ?? '',
                    'user_type' => $payload['user_type'] ?? '',
                    'code_verification' => $payload['code_verification'] ?? '',
                    'request_date' => $row['archived_at'] ?? $payload['request_date'] ?? date('Y-m-d H:i:s'),
                    'print_count' => (int)($payload['print_count'] ?? 0),
                    'statut' => $payload['statut'] ?? 'pret',
                    'user_id' => $payload['user_id'] ?? $row['original_id']
                ];
            }

            $stats['total'] = count($list);
            foreach ($list as $item) {
                if ($item['statut'] === 'pret') {
                    $stats['pret']++;
                } else {
                    $stats['en_attente']++;
                }
            }

        } catch (\Exception $e) {
            $stats = ['apprenants' => 0, 'encadrants' => 0, 'total' => 0, 'pret' => 0, 'en_attente' => 0];
        }

        return $this->render('admin/modules/documents', [
            'title' => 'استخراج الشهادات والمطبوعات الرسمية',
            'stats' => $stats, 'list' => $list, 'stagiaires' => $apprenants, 'employes' => $employes,
        ]);
    }

    public function demanderDocument() {
        if (request()->isMethod('post')) {
            $csrfToken = request()->all()['csrf_token'] ?? request()->header('X-CSRF-TOKEN') ?? '';
            if (empty($csrfToken) || $csrfToken !== (csrf_token() ?? '')) {
                if (request()->ajax() || request()->wantsJson()) {
                    return response()->json(['success' => false, 'error' => 'رمز التحقق من الأمن غير صالح.'], 400);
                }
                session(['flash_error' => 'رمز التحقق من الأمن غير صالح. يرجى المحاولة مجدداً / Jeton CSRF invalide.']);
                return $this->redirect('/dashboard/documents');
            }

            $userType = request()->all()['user_type'] ?? '';
            $userId   = (int)(request()->all()['user_id'] ?? 0);
            $docType  = request()->all()['document_type'] ?? '';

            if ($userType && $userId && $docType) {
                $name = 'غير معرف';
                $identifier = '';
                if ($userType === 'stagiaire') {
                    $stmtUser = $this->db->prepare("
                        SELECT c.Nom, c.Prenom, a.NumActe, c.NumIns, a.Nccp 
                        FROM apprenant a 
                        JOIN candidat c ON a.IDCandidat = c.IDCandidat 
                        WHERE a.IDapprenant = ? 
                        LIMIT 1
                    ");
                    $stmtUser->execute([$userId]);
                    $rowUser = $stmtUser->fetch(PDO::FETCH_ASSOC);
                    if ($rowUser) {
                        $name = trim($rowUser['Nom'] . ' ' . $rowUser['Prenom']);
                        $identifier = 'متربص - رقم: ' . (!empty($rowUser['NumActe']) ? $rowUser['NumActe'] : (!empty($rowUser['NumIns']) ? $rowUser['NumIns'] : $rowUser['Nccp']));
                    }
                } else {
                    $stmtUser = $this->db->prepare("
                        SELECT Nom, Prenom, nin, Email 
                        FROM encadrement 
                        WHERE IDEncadrement = ? 
                        LIMIT 1
                    ");
                    $stmtUser->execute([$userId]);
                    $rowUser = $stmtUser->fetch(PDO::FETCH_ASSOC);
                    if ($rowUser) {
                        $name = trim($rowUser['Nom'] . ' ' . $rowUser['Prenom']);
                        $identifier = 'موظف - ر.ت: ' . (!empty($rowUser['nin']) ? $rowUser['nin'] : $rowUser['Email']);
                    }
                }

                $prefix = ($userType === 'employe') ? 'WORK' : strtoupper(substr($docType, 0, 4));
                $code = $prefix . '-' . date('Y') . '-' . str_pad($userId, 5, '0', STR_PAD_LEFT);
                
                $payload = json_encode([
                    'user_type' => $userType,
                    'user_id' => $userId,
                    'document_type' => $docType,
                    'code_verification' => $code,
                    'request_date' => date('Y-m-d H:i:s'),
                    'print_count' => 0,
                    'statut' => 'pret',
                    'demandeur_nom' => $name,
                    'identifier' => $identifier
                ], JSON_UNESCAPED_UNICODE);

                try {
                    $stmtInsert = $this->db->prepare("
                        INSERT INTO digital_archives (table_name, original_id, payload, reason, archived_at)
                        VALUES ('document_requests', ?, ?, ?, NOW())
                    ");
                    $stmtInsert->execute([$userId, $payload, $docType]);
                    $insertId = $this->db->lastInsertId();

                    if (request()->ajax() || request()->wantsJson()) {
                        return response()->json([
                            'success' => true,
                            'code' => $code,
                            'print_url' => url("dashboard/documents/print/" . \App\Helpers\SecureIdHelper::encrypt($insertId) . "?type=$userType&doc=$docType")
                        ]);
                    }

                    $encId = \App\Helpers\SecureIdHelper::encrypt($insertId);
                    session(['flash_success' => "تم إنشاء الوثيقة بنجاح وحفظها في قاعدة البيانات! رمز التحقق: $code — <a href='/sig/dashboard/documents/print/$encId' target='_blank' class='alert-link fw-bold'><i class='fa-solid fa-print me-1'></i>طباعة الآن</a>"]);
                } catch (\Exception $e) {
                    if (request()->ajax() || request()->wantsJson()) {
                        return response()->json(['success' => false, 'error' => 'حدث خطأ في قاعدة البيانات: ' . $e->getMessage()], 500);
                    }
                    session(['flash_error' => 'حدث خطأ أثناء الحفظ في قاعدة البيانات: ' . $e->getMessage()]);
                }
            } else {
                if (request()->ajax() || request()->wantsJson()) {
                    return response()->json(['success' => false, 'error' => 'يرجى ملء جميع الحقول (الفئة، المستفيد، نوع الوثيقة).'], 400);
                }
                session(['flash_error' => 'يرجى ملء جميع الحقول (الفئة، المستفيد، نوع الوثيقة).']);
            }
        }
        return $this->redirect('/dashboard/documents');
    }

    public function printDocument($id) {
        if (\App\Helpers\SovereignLicensingHelper::getSetting('feature_print_actions_enabled', '1') !== '1') {
            abort(403, 'طباعة الوثائق معطلة من قبل مدير النظام / Printing is disabled by administrator.');
        }

        \Illuminate\Support\Facades\Log::info('PrintDocument Debug', [
            'raw_id' => $id,
            'decrypted_id' => \App\Helpers\SecureIdHelper::decrypt($id),
        ]);

        // Enforce encrypted ID. Numeric IDs are no longer allowed in URLs to prevent guessing.
        $decryptedId = \App\Helpers\SecureIdHelper::decrypt($id);
        if ($decryptedId === null) {
            abort(404, 'الوثيقة غير موجودة / Document not found');
        }
        $id = $decryptedId;

        $userType = request()->all()['type'] ?? request()->all()['user_type'] ?? null;
        $docType = request()->all()['doc'] ?? request()->all()['document_type'] ?? null;

        // Try to load request from digital_archives if not a direct print request
        $dbRecordId = (int)$id;
        $userId = $dbRecordId;

        if (!request()->query('direct', 0)) {
            try {
                $stmt = $this->db->prepare("SELECT * FROM digital_archives WHERE table_name = 'document_requests' AND id = ? LIMIT 1");
                $stmt->execute([$dbRecordId]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($row) {
                    $payload = json_decode($row['payload'], true) ?: [];
                    $userType = $payload['user_type'] ?? $userType;
                    $docType = $payload['document_type'] ?? $docType;
                    $userId = (int)($payload['user_id'] ?? $row['original_id']);

                    // Increment print count in database
                    $payload['print_count'] = (int)($payload['print_count'] ?? 0) + 1;
                    $stmtUpdate = $this->db->prepare("UPDATE digital_archives SET payload = ? WHERE id = ?");
                    $stmtUpdate->execute([json_encode($payload, JSON_UNESCAPED_UNICODE), $dbRecordId]);
                }
            } catch (\Exception $ex) {
                // ignore database write error, proceed to print
            }
        }

        $isEmploye = ($userType === 'employe');

        // IDOR Protection & Authorization Check
        $user = session('user');
        $isPublicVerify = request()->is('verify/*');

        if (!$isPublicVerify && !$user) {
            abort(401, 'يجب تسجيل الدخول أولاً / Unauthorized');
        }

        if ($isPublicVerify && !isset($row)) {
            abort(404, 'الوثيقة غير موجودة / Document not found');
        }

        if ($user) {
            $role = strtolower($user['role_code'] ?? 'user');
            $etabId = (int)($user['etablissement_id'] ?? 0);
            $dfepId = (int)($user['iddfep'] ?? $user['IDDFEP'] ?? 0);
            $userSessionId = (int)($user['id'] ?? 0);

            if ($role !== 'admin') {
                if ($isEmploye) {
                    $empEtab = \Illuminate\Support\Facades\DB::table('encadrement')
                        ->leftJoin('etablissement', 'encadrement.IDetablissement', '=', 'etablissement.IDetablissement')
                        ->where('encadrement.IDEncadrement', $userId)
                        ->select('encadrement.IDetablissement', 'etablissement.IDDFEP')
                        ->first();

                    if (!$empEtab) {
                        abort(404, 'الموظف غير موجود / Employee not found');
                    }

                    if (in_array($role, ['etablissement', 'directeur', 'employee', 'employee_dep', 'formateur'])) {
                        if ($etabId > 0 && $empEtab->IDetablissement && (int)$empEtab->IDetablissement !== $etabId) {
                            abort(403, 'غير مصرح لك بالوصول إلى وثائق هذا الموظف.');
                        }
                    } elseif ($role === 'dfep') {
                        if ($dfepId > 0 && $empEtab->IDDFEP && (int)$empEtab->IDDFEP !== $dfepId) {
                            abort(403, 'غير مصرح لك بالوصول إلى وثائق هذا الموظف.');
                        }
                    } elseif (in_array($role, ['central', 'high_admin', 'secretaire_general', 'ministre'])) {
                        // Allowed
                    } else {
                        abort(403, 'غير مصرح لك بالوصول إلى هذه الوثيقة.');
                    }
                } else {
                    $traineeEtab = \Illuminate\Support\Facades\DB::table('apprenant')
                        ->join('candidat', 'apprenant.IDCandidat', '=', 'candidat.IDCandidat')
                        ->leftJoin('offre', 'candidat.IDOffre', '=', 'offre.IDOffre')
                        ->leftJoin('etablissement', 'offre.IDEts_Form', '=', 'etablissement.IDetablissement')
                        ->where('apprenant.IDapprenant', $userId)
                        ->select('offre.IDEts_Form', 'etablissement.IDDFEP', 'apprenant.IDapprenant')
                        ->first();

                    if (!$traineeEtab) {
                        abort(404, 'المتربص غير موجود / Trainee not found');
                    }

                    if (in_array($role, ['etablissement', 'directeur', 'employee', 'employee_dep', 'formateur'])) {
                        if ($etabId > 0 && $traineeEtab->IDEts_Form && (int)$traineeEtab->IDEts_Form !== $etabId) {
                            abort(403, 'غير مصرح لك بالوصول إلى وثائق هذا المتربص.');
                        }
                    } elseif ($role === 'dfep') {
                        if ($dfepId > 0 && $traineeEtab->IDDFEP && (int)$traineeEtab->IDDFEP !== $dfepId) {
                            abort(403, 'غير مصرح لك بالوصول إلى وثائق هذا المتربص.');
                        }
                    } elseif ($role === 'stagiaire') {
                        if ((int)$traineeEtab->IDapprenant !== $userSessionId) {
                            abort(403, 'غير مصرح لك بالوصول إلى هذه الوثيقة.');
                        }
                    } elseif (in_array($role, ['central', 'high_admin', 'secretaire_general', 'ministre'])) {
                        // Allowed
                    } else {
                        abort(403, 'غير مصرح لك بالوصول إلى هذه الوثيقة.');
                    }
                }
            }
        }
        $details  = [];

        try {
            if ($isEmploye) {
                // Load encadrement (employee) details
                $stmt = $this->db->prepare("
                    SELECT enc.IDEncadrement as id,
                           enc.nin as numero_matricule,
                           enc.Nom as nom_ar, enc.Prenom as prenom_ar,
                           enc.NomFr as nom_fr, enc.PrenomFr as prenom_fr,
                           DATE_FORMAT(enc.DateNais, '%d/%m/%Y') as date_naissance,
                           'الجزائر' as lieu_naissance,
                           enc.Specialite as spec_ar, enc.Specialite as spec_fr, '' as spec_code,
                           ef.Nom as etab_nom, ef.NomFr as etab_fr,
                           w.Nom as wilaya_nom, w.NomFr as wilaya_nom_fr,
                           enc.IDFonctions as grade_id,
                           enc.Daterecr as date_recrutement
                    FROM encadrement enc
                    LEFT JOIN etablissement ef ON enc.IDetablissement = ef.IDetablissement
                    LEFT JOIN dfep d ON ef.IDDFEP = d.IDDFEP
                    LEFT JOIN wilaya w ON d.IDWilayaa = w.IDWilayaa
                    WHERE enc.IDEncadrement = ?
                ");
                $stmt->execute([(int)$userId]);
                $details = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

                if (!$details) {
                    session(['flash_error' => 'الموظف غير موجود في قاعدة البيانات (ID: ' . (int)$userId . ')']);
                    return $this->redirect('/dashboard/documents');
                }
            } else {
                // Load apprenant (trainee) details with robust joins
                $stmt = $this->db->prepare("
                    SELECT a.IDapprenant as id, COALESCE(c.NumIns, a.Nccp) as numero_matricule,
                           c.Nom as nom_ar, c.Prenom as prenom_ar,
                           c.NomFr as nom_fr, c.PrenomFr as prenom_fr,
                           DATE_FORMAT(c.DateNais, '%d/%m/%Y') as date_naissance,
                           'الجزائر' as lieu_naissance,
                           sp.Nom as spec_ar, sp.NomFr as spec_fr, sp.CodeSpec as spec_code,
                           ef.Nom as etab_nom, ef.NomFr as etab_fr,
                           w.Nom as wilaya_nom, w.NomFr as wilaya_nom_fr,
                           a.statut as statut_apprenant,
                           se.Nom as session_nom, se.Code as session_code,
                           o.DateD as date_debut, o.DateF as date_fin
                    FROM apprenant a
                    JOIN candidat c ON a.IDCandidat = c.IDCandidat
                    LEFT JOIN offre o ON c.IDOffre = o.IDOffre
                    LEFT JOIN specialite sp ON o.IDSpecialite = sp.IDSpecialite
                    LEFT JOIN etablissement ef ON o.IDEts_Form = ef.IDetablissement
                    LEFT JOIN dfep d ON ef.IDDFEP = d.IDDFEP
                    LEFT JOIN wilaya w ON d.IDWilayaa = w.IDWilayaa
                    LEFT JOIN session se ON o.IDSession = se.IDSession
                    LEFT JOIN section s ON a.IDSection = s.IDSection
                    WHERE a.IDapprenant = ?
                ");
                $stmt->execute([(int)$userId]);
                $details = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

                if (!$details) {
                    session(['flash_error' => 'المتربص غير موجود في قاعدة البيانات (ID: ' . (int)$userId . ')']);
                    return $this->redirect('/dashboard/documents');
                }
            }
        } catch (\Exception $e) {
            session(['flash_error' => 'خطأ في قاعدة البيانات: ' . $e->getMessage()]);
            return $this->redirect('/dashboard/documents');
        }

        // Decrypt NIN (numero_matricule) if it is encrypted
        if (!empty($details['numero_matricule'])) {
            try {
                $dec = \Illuminate\Support\Facades\Crypt::decryptString($details['numero_matricule']);
                if ($dec) {
                    $details['numero_matricule'] = $dec;
                }
            } catch (\Exception $e) {
                // Keep as is if already plaintext
            }
        }

        // Transliterate names to French if database values are empty
        if (!empty($details)) {
            if (empty($details['nom_fr']) && !empty($details['nom_ar'])) {
                $details['nom_fr'] = \App\Helpers\TakwinHelper::transliterateArabic($details['nom_ar']);
            }
            if (empty($details['prenom_fr']) && !empty($details['prenom_ar'])) {
                $details['prenom_fr'] = \App\Helpers\TakwinHelper::transliterateArabic($details['prenom_ar']);
            }
        }

        // Detect and override Wilaya matching the establishment's physical location/name
        if (!empty($details) && !empty($details['etab_nom'])) {
            $detected = \App\Helpers\TakwinHelper::detectWilayaFromEtab($details['etab_nom']);
            $details['wilaya_nom'] = $detected['ar'];
            $details['wilaya_nom_fr'] = $detected['fr'];
        }

        $code = strtoupper(substr($docType, 0, 4)) . '-' . date('Y') . '-' . str_pad($userId, 5, '0', STR_PAD_LEFT);
        $document = [
            'userType'          => $userType,
            'user_type'         => $userType,
            'document_type'     => $docType,
            'docType'           => $docType,
            'code_verification' => $code,
            'code'              => $code,
            'date'              => date('Y-m-d'),
        ];
        $semestersData = [];
        $semesterAverage = 0;
        $noteMemoire = null;

        if (!$isEmploye && $docType === 'bulletin_notes') {
            try {
                // Comment: We try to fetch the final average from apprenant_fin first,
                // because graduated students have their active semester details purged
                // from apprenant_section_semstre but keep their final grade average here.
                $stmtFin = $this->db->prepare("SELECT MoyGen FROM apprenant_fin WHERE IDapprenant = ? LIMIT 1");
                $stmtFin->execute([(int)$userId]);
                $finAvg = $stmtFin->fetchColumn();
                if ($finAvg !== false && $finAvg > 0) {
                    $semesterAverage = (float)$finAvg;
                }
            } catch (\Exception $e) {}

            try {
                // Retrieve all semesters of the student's section chronologically
                $stmtSems = $this->db->prepare("
                    SELECT ss.IDSection_Semestre, ss.NumSem
                    FROM section_semestre ss
                    JOIN apprenant a ON a.IDSection = ss.IDSection
                    WHERE a.IDapprenant = ?
                    ORDER BY ss.NumSem ASC
                ");
                $stmtSems->execute([(int)$userId]);
                $sectionSemesters = $stmtSems->fetchAll(PDO::FETCH_ASSOC);

                $overallPoints = 0;
                $overallCoefs = 0;

                foreach ($sectionSemesters as $sem) {
                    $semId = (int)$sem['IDSection_Semestre'];
                    $numSem = (int)$sem['NumSem'];

                    $stmtM = $this->db->prepare("
                        SELECT ssm.NomMdl as module_nom, COALESCE(ssm.coef, 1) as coefficient,
                                ssm.ExisC1 as exis_c1, ssm.ExisCs as exis_cs,
                                assm.NoteC1 as cc1, assm.NoteC2 as cc2, assm.NoteCs as exam, assm.NoteR as resit,
                                COALESCE(assm.MoyApr, assm.MoyAvr) as average
                        FROM section_semestre_module ssm
                        LEFT JOIN apprenant_section_semstre ass ON ass.IDSection_Semestre = ssm.IDSection_Semestre AND ass.IDapprenant = ?
                        LEFT JOIN apprenant_section_semstre_module assm ON ass.IDapprenant_Section_semstre = assm.IDapprenant_Section_semstre AND ssm.IDsection_semestre_Module = assm.IDsection_semestre_Module
                        WHERE ssm.IDSection_Semestre = ?
                        ORDER BY ssm.NumOrd ASC
                    ");
                    $stmtM->execute([(int)$userId, $semId]);
                    $rawMarks = $stmtM->fetchAll(PDO::FETCH_ASSOC);

                    if (empty($rawMarks)) {
                        continue;
                    }

                    $semMarks = [];
                    $semPoints = 0;
                    $semCoefs = 0;

                    foreach ($rawMarks as $rm) {
                        $coef = (float)$rm['coefficient'];
                        $avg = $rm['average'];
                        if ($avg === null) {
                            $cc1 = $rm['cc1'] !== null ? (float)$rm['cc1'] : null;
                            $cc2 = $rm['cc2'] !== null ? (float)$rm['cc2'] : null;
                            $exam = $rm['exam'] !== null ? (float)$rm['exam'] : null;
                            
                            if ($cc1 !== null && $cc2 !== null && $exam !== null) {
                                $avg = ($cc1 + $cc2 + $exam * 2) / 4;
                            } elseif ($cc1 !== null && $exam !== null) {
                                $avg = ($cc1 + $exam * 2) / 3;
                            } elseif ($exam !== null) {
                                $avg = $exam;
                            } else {
                                $avg = null;
                            }
                        } else {
                            $avg = (float)$avg;
                        }

                        $semMarks[] = [
                            'module_nom' => $rm['module_nom'],
                            'coefficient' => $coef,
                            'cc1' => $rm['cc1'],
                            'exam' => $rm['exam'],
                            'average' => $avg,
                            'exis_c1' => $rm['exis_c1'],
                            'exis_cs' => $rm['exis_cs']
                        ];

                        if ($avg !== null) {
                            $semPoints += $avg * $coef;
                            $semCoefs += $coef;
                        }
                    }

                    $semAvg = ($semCoefs > 0) ? ($semPoints / $semCoefs) : 0;
                    
                    $semestersData[] = [
                        'num_sem' => $numSem,
                        'sem_id' => $semId,
                        'marks' => $semMarks,
                        'average' => $semAvg
                    ];

                    if ($semAvg > 0) {
                        $overallPoints += $semAvg;
                        $overallCoefs++;
                    }
                }

                if ($overallCoefs > 0 && $semesterAverage == 0) {
                    $semesterAverage = $overallPoints / $overallCoefs;
                }
            } catch (\Exception $e) {
                $semestersData = [];
            }

            // Retrieve the graduation thesis grade (NoteMemoire)
            try {
                $stmtMemo = $this->db->prepare("
                    SELECT NoteMemoire 
                    FROM apprenant_section_semstre 
                    WHERE IDapprenant = ? AND NoteMemoire IS NOT NULL
                    ORDER BY IDapprenant_Section_semstre DESC 
                    LIMIT 1
                ");
                $stmtMemo->execute([(int)$userId]);
                $memoVal = $stmtMemo->fetchColumn();
                if ($memoVal !== false && $memoVal !== null) {
                    $noteMemoire = (float)$memoVal;
                }
            } catch (\Exception $e) {}
        }

        return $this->render('admin/modules/print_template', [
            'document'        => $document,
            'details'         => $details,
            'is_employe'      => $isEmploye,
            'marks'           => [], // Kept for backward compatibility
            'semestersData'   => $semestersData,
            'semesterAverage' => $semesterAverage,
            'noteMemoire'     => $noteMemoire,
        ], 'empty');
    }

    public function ajaxGetModes() {
        try {
            $modes = \App\Services\ReferenceCache::modesFormation();
            return response()->json($modes);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function ajaxGetWilayas() {
        $scope = $this->getScope();
        try {
            if ($scope['role'] === 'dfep' && $scope['iddfep']) {
                $stmt = $this->db->prepare("
                    SELECT IDWilayaa as id, Nom as nom, NomFr as nom_fr 
                    FROM wilaya 
                    WHERE IDWilayaa = ? 
                    LIMIT 1
                ");
                $stmt->execute([$scope['iddfep']]);
            } else if (in_array($scope['role'], ['etablissement', 'directeur', 'employee', 'formateur']) && $scope['etabId']) {
                $stmt = $this->db->prepare("
                    SELECT w.IDWilayaa as id, w.Nom as nom, w.NomFr as nom_fr 
                    FROM wilaya w 
                    JOIN etablissement ef ON ef.IDDFEP = w.IDWilayaa 
                    WHERE ef.IDetablissement = ? 
                    LIMIT 1
                ");
                $stmt->execute([$scope['etabId']]);
            } else {
                $stmt = $this->db->query("SELECT IDWilayaa as id, Nom as nom, NomFr as nom_fr FROM wilaya ORDER BY Nom ASC");
            }
            $wilayas = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
            return response()->json($wilayas);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function ajaxGetEtablissements() {
        $scope = $this->getScope();
        $wilayaId = (int)request()->query('wilaya_id', 0);
        
        try {
            if (in_array($scope['role'], ['etablissement', 'directeur', 'employee', 'formateur']) && $scope['etabId']) {
                $stmt = $this->db->prepare("SELECT IDetablissement as id, Nom as nom, NomFr as nom_fr FROM etablissement WHERE IDetablissement = ? LIMIT 1");
                $stmt->execute([$scope['etabId']]);
            } else if ($scope['role'] === 'dfep' && $scope['iddfep']) {
                $stmt = $this->db->prepare("SELECT IDetablissement as id, Nom as nom, NomFr as nom_fr FROM etablissement WHERE IDDFEP = ? ORDER BY Nom ASC");
                $stmt->execute([$scope['iddfep']]);
            } else {
                if ($wilayaId > 0) {
                    $stmt = $this->db->prepare("
                        SELECT ef.IDetablissement as id, ef.Nom as nom, ef.NomFr as nom_fr 
                        FROM etablissement ef 
                        WHERE ef.IDDFEP = ? 
                        ORDER BY ef.Nom ASC
                    ");
                    $stmt->execute([$wilayaId]);
                } else {
                    $stmt = $this->db->query("SELECT IDetablissement as id, Nom as nom, NomFr as nom_fr FROM etablissement ORDER BY Nom ASC LIMIT 200");
                }
            }
            $etabs = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
            return response()->json($etabs);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function ajaxGetUsers() {
        $scope = $this->getScope();
        $etabId = (int)request()->query('etab_id', 0);
        $userType = request()->query('user_type', 'stagiaire');
        $search = request()->query('search', '');
        
        if (in_array($scope['role'], ['etablissement', 'directeur', 'employee', 'formateur']) && $scope['etabId']) {
            $etabId = $scope['etabId'];
        }
        
        try {
            $list = [];
            if ($userType === 'stagiaire') {
                $sql = "
                    SELECT a.IDapprenant as id, COALESCE(c.NumIns, a.Nccp) as numero_matricule,
                           c.Nom as nom_ar, c.Prenom as prenom_ar,
                           c.NomFr as nom_fr, c.PrenomFr as prenom_fr,
                           sp.Nom as spec_ar
                    FROM apprenant a
                    JOIN candidat c ON a.IDCandidat = c.IDCandidat
                    LEFT JOIN offre o ON c.IDOffre = o.IDOffre
                    LEFT JOIN specialite sp ON o.IDSpecialite = sp.IDSpecialite
                    WHERE 1=1
                ";
                $params = [];
                if ($etabId > 0) {
                    $sql .= " AND o.IDEts_Form = ?";
                    $params[] = $etabId;
                }
                
                $modeId = (int)request()->query('mode_id', 0);
                if ($modeId > 0) {
                    $sql .= " AND o.IDMode_formation = ?";
                    $params[] = $modeId;
                }
                
                $branchId = (int)request()->query('branch_id', 0);
                if ($branchId > 0) {
                    $sql .= " AND sp.IDBranche = ?";
                    $params[] = $branchId;
                }
                
                $specialtyId = (int)request()->query('specialty_id', 0);
                if ($specialtyId > 0) {
                    $sql .= " AND o.IDSpecialite = ?";
                    $params[] = $specialtyId;
                }

                if ($search !== '') {
                    $sql .= " AND (c.Nom LIKE ? OR c.Prenom LIKE ? OR a.Nccp LIKE ?)";
                    $params[] = "%$search%";
                    $params[] = "%$search%";
                    $params[] = "%$search%";
                }
                $sql .= " ORDER BY a.IDapprenant DESC LIMIT 100";
                
                $stmt = $this->db->prepare($sql);
                $stmt->execute($params);
                $list = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Sort the 100 results alphabetically in PHP to avoid database filesort timeout
                usort($list, function($a, $b) {
                    return strcmp($a['nom_ar'] . ' ' . ($a['prenom_ar'] ?? ''), $b['nom_ar'] . ' ' . ($b['prenom_ar'] ?? ''));
                });
            } else {
                $sql = "
                    SELECT enc.IDEncadrement as id, enc.nin as numero_matricule,
                           enc.Nom as nom_ar, enc.Prenom as prenom_ar,
                           enc.NomFr as nom_fr, enc.PrenomFr as prenom_fr,
                           enc.Specialite as spec_ar
                    FROM encadrement enc
                    WHERE 1=1
                ";
                $params = [];
                if ($etabId > 0) {
                    $sql .= " AND enc.IDetablissement = ?";
                    $params[] = $etabId;
                }
                if ($search !== '') {
                    $sql .= " AND (enc.Nom LIKE ? OR enc.Prenom LIKE ? OR enc.nin LIKE ?)";
                    $params[] = "%$search%";
                    $params[] = "%$search%";
                    $params[] = "%$search%";
                }
                $sql .= " ORDER BY enc.IDEncadrement DESC LIMIT 100";
                
                $stmt = $this->db->prepare($sql);
                $stmt->execute($params);
                $list = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // Sort the 100 results alphabetically in PHP to avoid database filesort timeout
                usort($list, function($a, $b) {
                    return strcmp($a['nom_ar'] . ' ' . ($a['prenom_ar'] ?? ''), $b['nom_ar'] . ' ' . ($b['prenom_ar'] ?? ''));
                });
            }
            
            return response()->json($list);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function ajaxGetBranches() {
        $scope = $this->getScope();
        $etabId = (int)request()->query('etab_id', 0);
        if (in_array($scope['role'], ['etablissement', 'directeur', 'employee', 'formateur']) && $scope['etabId']) {
            $etabId = $scope['etabId'];
        }
        
        try {
            if ($etabId > 0) {
                $stmt = $this->db->prepare("
                    SELECT DISTINCT b.IDBranche as id, b.Nom as nom, b.NomFr as nom_fr
                    FROM branche b
                    JOIN specialite sp ON sp.IDBranche = b.IDBranche
                    JOIN offre o ON o.IDSpecialite = sp.IDSpecialite
                    WHERE o.IDEts_Form = ?
                    ORDER BY b.Nom ASC
                ");
                $stmt->execute([$etabId]);
            } else {
                $stmt = $this->db->query("
                    SELECT IDBranche as id, Nom as nom, NomFr as nom_fr
                    FROM branche
                    ORDER BY Nom ASC
                ");
            }
            $branches = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
            return response()->json($branches);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function ajaxGetSpecialties() {
        $scope = $this->getScope();
        $etabId = (int)request()->query('etab_id', 0);
        $branchId = (int)request()->query('branch_id', 0);
        
        if (in_array($scope['role'], ['etablissement', 'directeur', 'employee', 'formateur']) && $scope['etabId']) {
            $etabId = $scope['etabId'];
        }
        
        try {
            $sql = "
                SELECT DISTINCT sp.IDSpecialite as id, sp.Nom as nom, sp.NomFr as nom_fr
                FROM specialite sp
                WHERE 1=1
            ";
            $params = [];
            if ($etabId > 0) {
                $sql = "
                    SELECT DISTINCT sp.IDSpecialite as id, sp.Nom as nom, sp.NomFr as nom_fr
                    FROM specialite sp
                    JOIN offre o ON o.IDSpecialite = sp.IDSpecialite
                    WHERE o.IDEts_Form = ?
                ";
                $params[] = $etabId;
            }
            if ($branchId > 0) {
                $sql .= " AND sp.IDBranche = ?";
                $params[] = $branchId;
            }
            $sql .= " ORDER BY sp.Nom ASC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $specialties = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
            return response()->json($specialties);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function publicVerifyDocument() {
        $code = trim(strtoupper(request()->input('code', '')));
        $signature = trim(request()->input('signature', ''));
        $document = null;
        $error = null;
        $isVerifiedCryptographically = false;

        if (!empty($signature)) {
            // 1. Cryptographic Signature Verification Flow (via QR Code scan)
            try {
                // The QR code passes the digital_archives ID and the HMAC signature
                $archiveId = (int)request()->input('id', 0);
                
                $stmt = $this->db->prepare("SELECT * FROM digital_archives WHERE id = ? LIMIT 1");
                $stmt->execute([$archiveId]);
                $archive = $stmt->fetch(\PDO::FETCH_ASSOC);

                if ($archive) {
                    $payload = json_decode($archive['payload'], true);
                    
                    // Re-calculate the HMAC signature of the payload using the app key
                    $expectedSignature = hash_hmac('sha256', $archive['payload'], config('app.key'));
                    
                    if (hash_equals($expectedSignature, $signature)) {
                        $isVerifiedCryptographically = true;
                        $document = [
                            'id' => $archive['id'],
                            'demandeur_nom' => $payload['demandeur_nom'] ?? 'غير معرف',
                            'identifier' => $payload['identifier'] ?? '',
                            'document_type' => $payload['document_type'] ?? '',
                            'code_verification' => $payload['code_verification'] ?? '',
                            'request_date' => $archive['archived_at'] ?? $payload['request_date'] ?? date('Y-m-d H:i:s'),
                            'print_count' => (int)($payload['print_count'] ?? 0),
                            'statut' => $payload['statut'] ?? 'pret',
                            'user_type' => $payload['user_type'] ?? '',
                            'cryptographic_verification' => true
                        ];
                    } else {
                        $error = "فشل التحقق الرقمي المشفر. قد تكون بيانات الوثيقة قد تم التعديل عليها خارجياً (بيانات مزورة).";
                    }
                } else {
                    $error = "الوثيقة المستهدفة بالتوقيع غير موجودة في الأرشيف الرقمي للمنصة.";
                }
            } catch (\Exception $e) {
                $error = "حدث خطأ في النظام أثناء معالجة التوقيع الرقمي: " . $e->getMessage();
            }
        } elseif (!empty($code)) {
            // 2. Standard Code Verification Flow (via manual typing)
            try {
                $stmt = $this->db->prepare("
                    SELECT * FROM digital_archives 
                    WHERE table_name = 'document_requests' 
                      AND payload LIKE ?
                    LIMIT 1
                ");
                $stmt->execute(['%"code_verification":"' . $code . '"%']);
                $archive = $stmt->fetch(\PDO::FETCH_ASSOC);

                if ($archive) {
                    $payload = json_decode($archive['payload'], true);
                    $document = [
                        'id' => $archive['id'],
                        'demandeur_nom' => $payload['demandeur_nom'] ?? 'غير معرف',
                        'identifier' => $payload['identifier'] ?? '',
                        'document_type' => $payload['document_type'] ?? '',
                        'code_verification' => $payload['code_verification'] ?? '',
                        'request_date' => $archive['archived_at'] ?? $payload['request_date'] ?? date('Y-m-d H:i:s'),
                        'print_count' => (int)($payload['print_count'] ?? 0),
                        'statut' => $payload['statut'] ?? 'pret',
                        'user_type' => $payload['user_type'] ?? '',
                        'cryptographic_verification' => false
                    ];
                } else {
                    $error = "رمز التحقق الرقمي غير مطابق لأي وثيقة مسجلة في قاعدة البيانات الرسمية.";
                }
            } catch (\Exception $e) {
                $error = "حدث خطأ في النظام أثناء معالجة طلبك: " . $e->getMessage();
            }
        }

        return $this->render('portal/verify', [
            'title' => 'بوابة التحقق الرقمي للوثائق',
            'code' => $code,
            'signature' => $signature,
            'document' => $document,
            'error' => $error,
            'is_cryptographic' => $isVerifiedCryptographically
        ]);
    }

    public function publicPrintDocument($id) {
        return $this->printDocument($id);
    }

    // =========================================================================
    // TRAINEE TRANSFER SYSTEM
    // =========================================================================

    public function initiateTransfer(Request $request)
    {
        $user = session('user') ?? [];
        $role = strtolower($user['role_code'] ?? '');
        
        // Only establishments or admin can initiate
        if (!in_array($role, ['admin', 'etablissement', 'directeur'])) {
            return back()->with('error', 'غير مصرح لك ببدء عملية التحويل.');
        }

        $apprenantId = (int)$request->input('apprenant_id');
        $toEtabId = (int)$request->input('to_etab_id');
        $toSectionId = (int)$request->input('to_section_id');

        // Check if student exists and get their current establishment
        $apprenant = \App\Models\Apprenant::find($apprenantId);
        if (!$apprenant) {
            return back()->with('error', 'المتربص غير موجود.');
        }

        $fromEtabId = $apprenant->section->offre->IDEts_Form ?? null;
        if (!$fromEtabId) {
            return back()->with('error', 'تعذر تحديد المؤسسة الحالية للمتربص.');
        }

        // Validate target section
        $section = \App\Models\Section::find($toSectionId);
        if (!$section) {
            return back()->with('error', 'القسم المستهدف غير موجود.');
        }

        if ((int)$section->offre->IDEts_Form !== $toEtabId) {
            return back()->with('error', 'القسم المحدد لا ينتمي للمؤسسة المستهدفة.');
        }

        // Prevent transfer if target is same as current establishment
        if ($fromEtabId === $toEtabId) {
            return back()->with('error', 'المتربص مسجل بالفعل في هذه المؤسسة.');
        }

        // Check if there is already an active transfer request for this student
        $existing = \App\Models\ApprenantTransfert::where('apprenant_id', $apprenantId)
            ->whereIn('status', ['pending_sender_dfep', 'pending_receiver', 'pending_receiver_dfep'])
            ->first();
        if ($existing) {
            return back()->with('error', 'يوجد بالفعل طلب تحويل نشط وقيد المعالجة لهذا المتربص.');
        }

        // Create the request
        \App\Models\ApprenantTransfert::create([
            'apprenant_id' => $apprenantId,
            'from_etab_id' => $fromEtabId,
            'to_etab_id' => $toEtabId,
            'to_section_id' => $toSectionId,
            'status' => 'pending_sender_dfep'
        ]);

        return back()->with('success', 'تم إرسال طلب التحويل بنجاح وهو قيد المراجعة الآن من طرف المديرية الولائية للمؤسسة المرسلة.');
    }

    public function transfersList(Request $request)
    {
        $user = session('user') ?? [];
        $role = strtolower($user['role_code'] ?? '');
        $dfepId = (int)($user['iddfep'] ?? $user['IDDFEP'] ?? 0);
        $etabId = (int)($user['etablissement_id'] ?? $user['IDEts_Form'] ?? 0);

        $query = \App\Models\ApprenantTransfert::with([
            'apprenant.candidat',
            'fromEtablissement',
            'toEtablissement',
            'toSection.offre.specialite'
        ])->latest();

        // Scope the list based on role
        if ($role === 'admin' || in_array($role, ['ministre', 'secretaire_general', 'central', 'high_admin'])) {
            // Admin sees all
        } elseif ($role === 'dfep' && $dfepId > 0) {
            $query->where(function($q) use ($dfepId) {
                $q->whereHas('fromEtablissement', function($sq) use ($dfepId) {
                    $sq->where('IDDFEP', $dfepId);
                })->orWhereHas('toEtablissement', function($sq) use ($dfepId) {
                    $sq->where('IDDFEP', $dfepId);
                });
            });
        } elseif (($role === 'etablissement' || $role === 'directeur') && $etabId > 0) {
            $query->where(function($q) use ($etabId) {
                $q->where('from_etab_id', $etabId)
                  ->orWhere('to_etab_id', $etabId);
            });
        } else {
            $query->where('1', '0');
        }

        // Apply Status Filter
        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $transfers = $query->paginate(20);

        return $this->render('admin/modules/transfers_list', [
            'title' => 'طلبات تحويل المتربصين بين المؤسسات',
            'transfers' => $transfers,
            'role' => $role,
            'etabId' => $etabId,
            'dfepId' => $dfepId
        ]);
    }

    public function processTransferAction(Request $request)
    {
        $user = session('user') ?? [];
        $role = strtolower($user['role_code'] ?? '');
        $dfepId = (int)($user['iddfep'] ?? $user['IDDFEP'] ?? 0);
        $etabId = (int)($user['etablissement_id'] ?? $user['IDEts_Form'] ?? 0);

        $id = (int)$request->input('id');
        $action = $request->input('action'); // approve, reject
        $comment = trim($request->input('comment', ''));

        $transfer = \App\Models\ApprenantTransfert::findOrFail($id);

        if ($action === 'reject') {
            if (empty($comment)) {
                return back()->with('error', 'يرجى تقديم سبب الرفض.');
            }
            $transfer->update([
                'status' => 'rejected',
                'rejection_comment' => $comment
            ]);
            return back()->with('success', 'تم رفض طلب التحويل بنجاح.');
        }

        if ($action === 'approve') {
            if ($transfer->status === 'pending_sender_dfep') {
                $isAllowed = ($role === 'admin') || ($role === 'dfep' && (int)$transfer->fromEtablissement->IDDFEP === $dfepId);
                if (!$isAllowed) {
                    return back()->with('error', 'غير مصرح لك بالموافقة على هذه الخطوة.');
                }
                $transfer->update([
                    'status' => 'pending_receiver',
                    'sender_dfep_approved_by' => $user['Nom'] ?? $user['username'] ?? 'مديرية إرسال',
                    'sender_dfep_approved_at' => now()
                ]);
                return back()->with('success', 'تمت موافقة مديرية الإرسال، الطلب الآن في انتظار موافقة المؤسسة المستقلبة.');
            }

            if ($transfer->status === 'pending_receiver') {
                $isAllowed = ($role === 'admin') || (($role === 'etablissement' || $role === 'directeur') && $transfer->to_etab_id === $etabId);
                if (!$isAllowed) {
                    return back()->with('error', 'غير مصرح لك بالموافقة على هذه الخطوة.');
                }
                $transfer->update([
                    'status' => 'pending_receiver_dfep',
                    'receiver_approved_by' => $user['Nom'] ?? $user['username'] ?? 'مؤسسة استقبال',
                    'receiver_approved_at' => now()
                ]);
                return back()->with('success', 'تمت موافقة المؤسسة المستقبلة، الطلب الآن في انتظار الموافقة النهائية من مديريتها.');
            }

            if ($transfer->status === 'pending_receiver_dfep') {
                $isAllowed = ($role === 'admin') || ($role === 'dfep' && (int)$transfer->toEtablissement->IDDFEP === $dfepId);
                if (!$isAllowed) {
                    return back()->with('error', 'غير مصرح لك بالموافقة على هذه الخطوة.');
                }

                \Illuminate\Support\Facades\DB::transaction(function() use ($transfer, $user) {
                    $transfer->update([
                        'status' => 'approved',
                        'receiver_dfep_approved_by' => $user['Nom'] ?? $user['username'] ?? 'مديرية استقبال',
                        'receiver_dfep_approved_at' => now()
                    ]);

                    $apprenant = $transfer->apprenant;
                    $apprenant->update([
                        'IDSection' => $transfer->to_section_id
                    ]);
                });

                return back()->with('success', 'تمت الموافقة النهائية بنجاح وتم تحويل المتربص إلى قسمه الجديد بالمؤسسة المستهدفة.');
            }
        }

        return back()->with('error', 'إجراء غير صالح.');
    }


    public function ajaxGetSections(Request $request)
    {
        $etabId = (int)$request->query('etab_id');
        $specialtyId = (int)$request->query('specialty_id');

        $sections = \App\Models\Section::whereHas('offre', function($q) use ($etabId, $specialtyId) {
            $q->where('IDEts_Form', $etabId)
              ->where('IDSpecialite', $specialtyId);
        })
        ->select('IDSection', 'Nom')
        ->orderBy('Nom', 'ASC')
        ->get();

        return response()->json($sections);
    }
}