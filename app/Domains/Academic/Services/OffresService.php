<?php

namespace App\Domains\Academic\Services;

use App\Core\Database;
use App\Domains\Academic\Repositories\OffresRepository;
use App\Domains\Security\AuthorizationService;
use App\Audit\AuditService;
use Exception;
use PDO;

class OffresService
{
    protected OffresRepository $repository;
    protected AuthorizationService $authService;
    protected AuditService $auditService;

    public function __construct(
        OffresRepository $repository,
        AuthorizationService $authService,
        AuditService $auditService
    ) {
        $this->repository = $repository;
        $this->authService = $authService;
        $this->auditService = $auditService;
    }

    /**
     * Compile statistical aggregates and detailed list for training offers dashboard
     */
    public function getDashboardData(array $currentUser, array $getParams): array
    {
        // Heavy page: allow up to 120s for admin role (no scope = full table scans)
        ini_set('max_execution_time', '120');

        $roleCode = strtolower($currentUser['role_code'] ?? '');
        $etabId = (int)($currentUser['etablissement_id'] ?? 0);
        $dfepId = (int)($currentUser['iddfep'] ?? 0);

        // 1. Resolve header wilaya name
        $wilayaName = $this->repository->getWilayaName($roleCode, $etabId, $dfepId);

        // Enforce role-based restrictions on GET filters (Security/Scope Guard)
        if (in_array($roleCode, ['etablissement', 'directeur', 'formateur'])) {
            $getParams['filter_etab'] = $etabId;
            $getParams['filter_wilaya'] = null;
        } elseif ($roleCode === 'dfep') {
            $getParams['filter_wilaya'] = $dfepId;
            if (!empty($getParams['filter_etab'])) {
                $checkEtabId = (int)$getParams['filter_etab'];
                $db = \App\Core\Database::getInstance()->getConnection();
                $stmtC = $db->prepare("SELECT COUNT(*) FROM etablissement WHERE IDetablissement = ? AND IDDFEP = ?");
                $stmtC->execute([$checkEtabId, $dfepId]);
                if ((int)$stmtC->fetchColumn() === 0) {
                    $getParams['filter_etab'] = null;
                }
            }
        }

        // 2. Set up scoping conditions
        $whereConditions = [];
        $scopeParams = [];

        if (in_array($roleCode, ['etablissement', 'directeur', 'formateur']) && $etabId > 0) {
            $etabIds = \App\Support\EtablissementScope::resolve($etabId);
            if (empty($etabIds)) {
                $whereConditions[] = "1=0";
            } else {
                $placeholders = implode(',', array_fill(0, count($etabIds), '?'));
                $whereConditions[] = "o.IDEts_Form IN ($placeholders)";
                $scopeParams = array_merge($scopeParams, $etabIds);
            }
        } elseif ($roleCode === 'dfep' && $dfepId > 0) {
            $whereConditions[] = "e.IDDFEP = ?";
            $scopeParams[] = $dfepId;
        }

        if ((int)($currentUser['IDMode_formation'] ?? 0) === 10) {
            $whereConditions[] = "o.IDMode_formation = 10";
        }

        // Apply GET HTTP parameters filters
        if (!empty($getParams['filter_etablissement'])) {
            $reqFilter = (int)$getParams['filter_etablissement'];
            if ($etabId > 0) {
                $etabIds = \App\Support\EtablissementScope::resolve($etabId);
                abort_if(!in_array($reqFilter, $etabIds), 403, 'غير مصرح لك بالوصول لهذه المؤسسة.');
            }
            $whereConditions[] = "o.IDEts_Form = ?";
            $scopeParams[] = $reqFilter;
        } elseif (!empty($getParams['filter_etab'])) {
            $reqFilter = (int)$getParams['filter_etab'];
            if ($etabId > 0) {
                $etabIds = \App\Support\EtablissementScope::resolve($etabId);
                abort_if(!in_array($reqFilter, $etabIds), 403, 'غير مصرح لك بالوصول لهذه المؤسسة.');
            }
            $whereConditions[] = "o.IDEts_Form = ?";
            $scopeParams[] = $reqFilter;
        }
        if (!empty($getParams['filter_wilaya'])) {
            $whereConditions[] = "e.IDDFEP = ?";
            $scopeParams[] = (int)$getParams['filter_wilaya'];
        }
        if (!empty($getParams['filter_session'])) {
            $whereConditions[] = "o.IDSession = ?";
            $scopeParams[] = (int)$getParams['filter_session'];
        }
        if (!empty($getParams['filter_mode'])) {
            $modeStr = trim($getParams['filter_mode']);
            if ($modeStr === 'apprentissage') {
                $whereConditions[] = "o.IDMode_formation IN (2, 10)";
            } elseif ($modeStr === 'presentiel') {
                $whereConditions[] = "o.IDMode_formation IN (1, 4, 5, 6, 7, 8, 9)";
            } elseif ($modeStr === 'residentiell') {
                $whereConditions[] = "o.IDMode_formation = 3";
            } elseif ($modeStr === 'continu') {
                $whereConditions[] = "o.IDMode_formation = 2";
            } else {
                // Direct numeric ID (if ever passed as integer string)
                $whereConditions[] = "o.IDMode_formation = ?";
                $scopeParams[] = (int)$modeStr;
            }
        }
        if (!empty($getParams['filter_status'])) {
            $statusVal = $getParams['filter_status'];
            if ($statusVal === 'brouillon') {
                $whereConditions[] = "o.Valide = 0 AND o.ValidDfp = 0 AND o.ValideCentral = 0";
            } elseif ($statusVal === 'soumis') {
                $whereConditions[] = "o.Valide = 1 AND o.ValidDfp = 0 AND (o.Obs_Dfep IS NULL OR o.Obs_Dfep = '')";
            } elseif ($statusVal === 'valide_wilaya') {
                $whereConditions[] = "o.ValidDfp = 1 AND o.ValideCentral = 0 AND (o.Obs_Central IS NULL OR o.Obs_Central = '')";
            } elseif ($statusVal === 'valide_central') {
                $whereConditions[] = "o.ValideCentral = 1";
            } elseif ($statusVal === 'rejete_wilaya') {
                $whereConditions[] = "o.Obs_Dfep IS NOT NULL AND o.Obs_Dfep != '' AND o.ValidDfp = 0";
            } elseif ($statusVal === 'rejete_central') {
                $whereConditions[] = "o.Obs_Central IS NOT NULL AND o.Obs_Central != '' AND o.ValideCentral = 0";
            }
        }
        $whereConditions[] = "(o.NbrInscr > 0 OR o.Valide = 0 OR o.ValidDfp = 0 OR o.ValideCentral = 0 OR o.IDSession IN (SELECT IDSession FROM session WHERE Encour = 1))";
        $scopeWhere = count($whereConditions) > 0 ? implode(' AND ', $whereConditions) : '1=1';

        // 3. Retrieve aggregates and breakdowns
        $stats = $this->repository->getOffersStats($scopeWhere, $scopeParams);
        $dispositifs = $this->repository->getModeFormationBreakdown($scopeWhere, $scopeParams);
        $filieres = $this->repository->getFiliereBreakdown($scopeWhere, $scopeParams);

        // 4. Detailed list with formatted helper variables
        $rawOffers = $this->repository->getDetailedOffresList($scopeWhere, $scopeParams);
        $offresDetail = [];

        foreach ($rawOffers as $rd) {
            $offresDetail[] = $this->formatOffreRow($rd);
        }

        // 5. Fetch dropdown lists (Cascading Filters)
        $rawSpecs = $this->repository->getModalSpecialites();
        $specialites = [];
        foreach ($rawSpecs as $rs) {
            $specialites[] = [
                'id' => (int)$rs['id'],
                'code' => $rs['code'],
                'libelle_ar' => $this->cleanString($rs['libelle_ar']),
                'duree_semestres' => (int)($rs['duree_semestres'] ?? 2),
            ];
        }
        $db = \App\Core\Database::getInstance()->getConnection();

        // 5.1 Etablissements filtered by Wilaya
        if (!empty($getParams['filter_wilaya'])) {
            $filterDfepId = (int)$getParams['filter_wilaya'];
            $stmtE = $db->prepare("SELECT IDetablissement as id, Nom as nom_ar FROM etablissement WHERE IDDFEP = ? ORDER BY Nom ASC");
            $stmtE->execute([$filterDfepId]);
            $rawEtabs = $stmtE->fetchAll(\PDO::FETCH_ASSOC);
        } else {
            $rawEtabs = $this->repository->getModalEtablissements($roleCode, $etabId, $dfepId);
        }
        $etablissements = [];
        foreach ($rawEtabs as $re) {
            $etablissements[] = [
                'id' => (int)$re['id'],
                'nom_ar' => $this->cleanString($re['nom_ar']),
            ];
        }

        // 5.2 Modes filtered by active Wilaya and Etablissement filters
        $modeConds = ["1=1"];
        $modeParams = [];
        if (!empty($getParams['filter_wilaya'])) {
            $modeConds[] = "o.IDEts_Form IN (SELECT IDetablissement FROM etablissement WHERE IDDFEP = ?)";
            $modeParams[] = (int)$getParams['filter_wilaya'];
        }
        if (!empty($getParams['filter_etab'])) {
            $modeConds[] = "o.IDEts_Form = ?";
            $modeParams[] = (int)$getParams['filter_etab'];
        }
        $modeCondStr = implode(" AND ", $modeConds);
        $stmtM = $db->prepare("
            SELECT DISTINCT mf.IDMode_formation as id, mf.Nom as nom_ar, mf.NomFr as nom_fr 
            FROM offre o 
            JOIN mode_formation mf ON o.IDMode_formation = mf.IDMode_formation 
            WHERE $modeCondStr 
            ORDER BY mf.NomOrd ASC, mf.IDMode_formation ASC
        ");
        $stmtM->execute($modeParams);
        $modes_formation = $stmtM->fetchAll(\PDO::FETCH_ASSOC);

        // 5.3 Sessions filtered by active Wilaya, Etablissement, and Mode filters
        $sessConds = ["1=1"];
        $sessParams = [];
        if (!empty($getParams['filter_wilaya'])) {
            $sessConds[] = "o.IDEts_Form IN (SELECT IDetablissement FROM etablissement WHERE IDDFEP = ?)";
            $sessParams[] = (int)$getParams['filter_wilaya'];
        }
        if (!empty($getParams['filter_etab'])) {
            $sessConds[] = "o.IDEts_Form = ?";
            $sessParams[] = (int)$getParams['filter_etab'];
        }
        if (!empty($getParams['filter_mode'])) {
            $modeStr = trim($getParams['filter_mode']);
            if ($modeStr === 'apprentissage') {
                $sessConds[] = "o.IDMode_formation IN (2, 10)";
            } elseif ($modeStr === 'presentiel') {
                $sessConds[] = "o.IDMode_formation IN (1, 4, 5, 6, 7, 8, 9)";
            } elseif ($modeStr === 'residentiell') {
                $sessConds[] = "o.IDMode_formation = 3";
            } elseif ($modeStr === 'continu') {
                $sessConds[] = "o.IDMode_formation = 2";
            } else {
                $sessConds[] = "o.IDMode_formation = ?";
                $sessParams[] = (int)$modeStr;
            }
        }
        $sessCondStr = implode(" AND ", $sessConds);
        $stmtS = $db->prepare("
            SELECT DISTINCT s.IDSession as id, s.Nom as intitule_ar, s.NomFr as intitule_fr,
                            s.DateD as date_debut, NULL as date_fin, s.DateFInscr as date_fin_insc
            FROM offre o 
            JOIN session s ON o.IDSession = s.IDSession 
            WHERE $sessCondStr 
            ORDER BY s.DateD DESC
        ");
        $stmtS->execute($sessParams);
        $sessions = $stmtS->fetchAll(\PDO::FETCH_ASSOC);


        $qualifications_diplomes = $this->repository->getModalQualificationsDiplomes();
        $regimes_hebergement     = $this->repository->getModalRegimesHebergement();
        $regimes_cours           = $this->repository->getModalRegimesCours();

        // 6. Trainee / Student statistics (live from section aggregates)
        // Resolve the actual integer scope IDs to pass to getTraineesStats
        $scopeEtabId = 0;
        $scopeDfepId = 0;
        if (in_array($roleCode, ['etablissement', 'directeur', 'formateur']) && $etabId > 0) {
            $scopeEtabId = $etabId;
        } elseif ($roleCode === 'dfep' && $dfepId > 0) {
            $scopeDfepId = $dfepId;
        }
        // Apply GET filter overrides (admin selecting a specific wilaya/etab)
        if (!empty($getParams['filter_etablissement'])) {
            $scopeEtabId = (int)$getParams['filter_etablissement'];
            $scopeDfepId = 0;
        } elseif (!empty($getParams['filter_etab'])) {
            $scopeEtabId = (int)$getParams['filter_etab'];
            $scopeDfepId = 0;
        }
        if (!empty($getParams['filter_wilaya']) && $scopeEtabId === 0) {
            $scopeDfepId = (int)$getParams['filter_wilaya'];
        }
        $trainee_stats = $this->getTraineesStats($scopeEtabId, $scopeDfepId);

        return [
            'stats' => $stats,
            'dispositifs' => $dispositifs,
            'filieres' => $filieres,
            'offres_detail' => $offresDetail,
            'wilaya_name' => $wilayaName,
            'specialites' => $specialites,
            'etablissements' => $etablissements,
            'sessions' => $sessions,
            'qualifications_diplomes' => $qualifications_diplomes,
            'regimes_hebergement'     => $regimes_hebergement,
            'regimes_cours'           => $regimes_cours,
            'modes_formation'         => $modes_formation,
            'trainee_stats'           => $trainee_stats,
        ];
    }

    /**
     * Retrieve live trainee / student statistics.
     * Uses the EXACT same queries as the minister dashboard for consistency.
     * Active = apprenant not yet in apprenant_fin (not graduated) in last 5 sessions.
     */
    /**
     * Retrieve live trainee / student statistics, properly scoped by institution or wilaya.
     * Accepts direct integer IDs to avoid SQL alias confusion from the main offres query context.
     *
     * @param int $scopeEtabId  Institution ID (0 = no restriction)
     * @param int $scopeDfepId  Wilaya/DFEP ID (0 = no restriction, ignored if scopeEtabId > 0)
     */
    private function getTraineesStats(int $scopeEtabId = 0, int $scopeDfepId = 0): array
    {
        try {
            $db = \App\Core\Database::getInstance()->getConnection();

            $hasScope = $scopeEtabId > 0 || $scopeDfepId > 0;

            // Build the offre join and scope condition using clear, internal aliases
            $scopeJoin  = '';
            $scopeCond  = '';
            $scopeVal   = [];
            if ($scopeEtabId > 0) {
                $scopeJoin = 'JOIN offre ots ON s.IDOffre = ots.IDOffre';
                $scopeCond = 'AND ots.IDEts_Form = ?';
                $scopeVal  = [$scopeEtabId];
            } elseif ($scopeDfepId > 0) {
                $scopeJoin = 'JOIN offre ots ON s.IDOffre = ots.IDOffre
                              JOIN etablissement ets ON ots.IDEts_Form = ets.IDetablissement';
                $scopeCond = 'AND ets.IDDFEP = ?';
                $scopeVal  = [$scopeDfepId];
            }

            // ---- Get last 5 relevant sessions (scoped if needed) ----
            if ($hasScope) {
                $stmtSessions = $db->prepare("
                    SELECT DISTINCT sess.IDSession, sess.Nom, sess.NomFr, sess.DateD
                    FROM session sess
                    JOIN section s ON s.IDSession = sess.IDSession
                    {$scopeJoin}
                    WHERE 1=1 {$scopeCond}
                    ORDER BY sess.DateD DESC
                    LIMIT 5
                ");
                $stmtSessions->execute($scopeVal);
            } else {
                $stmtSessions = $db->query("
                    SELECT IDSession, Nom, NomFr, DateD
                    FROM session
                    ORDER BY DateD DESC
                    LIMIT 5
                ");
            }
            $last5Sessions = $stmtSessions->fetchAll(\PDO::FETCH_ASSOC);
            $sessionIds = array_column($last5Sessions, 'IDSession');

            if (empty($sessionIds)) {
                return $this->emptyTraineeStats();
            }

            $inPlaceholders = implode(',', array_fill(0, count($sessionIds), '?'));

            // ---- Active trainees breakdown by session ----
            $breakdownParams = array_merge($sessionIds, $scopeVal);
            $stmtBreakdown = $db->prepare("
                SELECT sess.IDSession, sess.Nom as session_nom, sess.NomFr as session_fr, sess.DateD,
                       COUNT(a.IDapprenant) as actifs,
                       SUM(CASE WHEN c.Civ = 2 THEN 1 ELSE 0 END) as actifs_femmes
                FROM session sess
                JOIN section s ON s.IDSession = sess.IDSession
                JOIN offre o ON s.IDOffre = o.IDOffre
                JOIN specialite sp ON o.IDSpecialite = sp.IDSpecialite
                {$scopeJoin}
                JOIN apprenant a ON a.IDSection = s.IDSection
                JOIN candidat c ON a.IDCandidat = c.IDCandidat
                LEFT JOIN apprenant_fin af ON af.IDapprenant = a.IDapprenant
                WHERE sess.IDSession IN ({$inPlaceholders})
                AND af.IDapprenant IS NULL
                AND DATE_ADD(sess.DateD, INTERVAL COALESCE(NULLIF(sp.dureeM, 0), sp.NbrSem * 6, 24) MONTH) >= CURRENT_DATE()
                {$scopeCond}
                GROUP BY sess.IDSession, sess.Nom, sess.NomFr, sess.DateD
                ORDER BY sess.DateD DESC
            ");
            $stmtBreakdown->execute($breakdownParams);
            $bySession = $stmtBreakdown->fetchAll(\PDO::FETCH_ASSOC) ?: [];

            $totalActifs  = array_sum(array_column($bySession, 'actifs'));
            $actifsFemmes = array_sum(array_column($bySession, 'actifs_femmes'));
            $totalReconduits = 0;
            foreach (array_slice($bySession, 1) as $row) {
                $totalReconduits += (int)$row['actifs'];
            }

            // ---- S1 new sections ----
            $currentSessionId = $sessionIds[0] ?? 0;
            $s1Params = array_merge([$currentSessionId], $scopeVal);
            $stmtS1 = $db->prepare("
                SELECT COUNT(DISTINCT ss.IDSection)
                FROM section_semestre ss
                JOIN section s ON ss.IDSection = s.IDSection
                {$scopeJoin}
                WHERE ss.Dernier = 1 AND ss.NumSem = 1 AND s.IDSession = ?
                {$scopeCond}
            ");
            $stmtS1->execute($s1Params);
            $sectionsNouvelles = (int)($stmtS1->fetchColumn() ?: 0);

            // ---- Total graduates (scoped) ----
            if ($hasScope) {
                $stmtGrad = $db->prepare("
                    SELECT COUNT(af.IDapprenant)
                    FROM apprenant_fin af
                    JOIN apprenant a ON a.IDapprenant = af.IDapprenant
                    JOIN section s ON a.IDSection = s.IDSection
                    {$scopeJoin}
                    WHERE af.IDDecision_evalf IN (1, 2, 3)
                    {$scopeCond}
                ");
                $stmtGrad->execute($scopeVal);
            } else {
                $stmtGrad = $db->query("
                    SELECT COUNT(*) FROM apprenant_fin
                    WHERE IDDecision_evalf IN (1, 2, 3)
                ");
            }
            $totalDiplomes = (int)($stmtGrad->fetchColumn() ?: 0);

            return [
                'total_actifs'       => $totalActifs,
                'actifs_femmes'      => $actifsFemmes,
                'total_inscrits'     => $totalActifs,
                'inscrits_femmes'    => $actifsFemmes,
                'total_diplomes'     => $totalDiplomes,
                'diplomes_femmes'    => 0,
                'sections_nouvelles' => $sectionsNouvelles,
                'total_places'       => 0,
                'taux_couverture'    => 0,
                'taux_activite'      => 0,
                'total_reconduits'   => $totalReconduits,
                'total_filles'       => $actifsFemmes,
                'by_session'         => $bySession,
            ];

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('getTraineesStats error: ' . $e->getMessage());
            return $this->emptyTraineeStats();
        }
    }

    private function emptyTraineeStats(): array
    {
        return [
            'total_actifs' => 0, 'actifs_femmes' => 0, 'total_inscrits' => 0,
            'inscrits_femmes' => 0, 'total_diplomes' => 0, 'diplomes_femmes' => 0,
            'sections_nouvelles' => 0, 'total_places' => 0, 'taux_couverture' => 0,
            'taux_activite' => 0, 'total_reconduits' => 0, 'total_filles' => 0,
            'by_session' => [],
        ];
    }

    /**
     * Retrieve pending validation lists for administration validation boards
     */
    public function getValidationDashboardData(array $currentUser): array
    {
        // Heavy page for central role (no scope filter = full table)
        ini_set('max_execution_time', '120');

        $roleCode = strtolower($currentUser['role_code'] ?? '');
        if (!in_array($roleCode, ['admin', 'dfep', 'central'])) {
            throw new Exception("غير مسموح لك بالولوج لهذه الصفحة / Accès non autorisé.");
        }

        $etabId = (int)($currentUser['etablissement_id'] ?? 0);
        $dfepId = (int)($currentUser['iddfep'] ?? 0);

        $wilayaName = $this->repository->getWilayaName($roleCode, $etabId, $dfepId);
        
        $lists = $this->repository->getValidationOffers($roleCode, $dfepId);
        
        $pending = [];
        foreach ($lists['pending'] as $row) {
            $pending[] = $this->formatOffreRow($row);
        }

        $processed = [];
        foreach ($lists['processed'] as $row) {
            $processed[] = $this->formatOffreRow($row);
        }

        $stats = [
            'total_pending' => $lists['total_pending'] ?? count($pending),
            'total_approved' => $lists['total_approved'] ?? 0,
            'total_rejected' => $lists['total_rejected'] ?? 0
        ];

        $sessions = $this->repository->getModalSessions();
        $etablissements = $this->repository->getModalEtablissements($roleCode, $etabId, $dfepId);

        return [
            'pending_offres' => $pending,
            'processed_offres' => $processed,
            'stats' => $stats,
            'wilaya_name' => $wilayaName,
            'sessions' => $sessions,
            'etablissements' => $etablissements,
            'role_code' => $roleCode
        ];
    }

    /**
     * Create a new training offer securely under transactions and sequence concurrency locks
     */
    public function createOffer(array $postData, array $currentUser): void
    {
        $this->authService->authorize($currentUser, 'offer.view'); // Base view access

        $specialiteId = (int)$postData['specialite_id'];
        $capacite = (int)($postData['capacite'] ?? 0);
        $debut = !empty($postData['date_debut']) ? $postData['date_debut'] : null;
        $fin = !empty($postData['date_fin']) ? $postData['date_fin'] : null;
        
        $etabId = (int)($currentUser['etablissement_id'] ?? 0);
        if ($etabId <= 0) {
            throw new Exception("المستخدم لا ينتمي لمؤسسة تكوينية / User has no associated establishment.");
        }

        $modeStr = trim($postData['mode_formation'] ?? '');
        // Form now sends numeric IDMode_formation directly; fall back to legacy text codes
        if (ctype_digit($modeStr) && (int)$modeStr > 0) {
            $mode = (int)$modeStr;
        } else {
            // Legacy text-code fallback mapping
            $mode = 1; // default: حضوري أولي
            if (in_array($modeStr, ['apprentissage', 'APP'])) { $mode = 10; }
            elseif (in_array($modeStr, ['continu', 'FC'])) { $mode = 2; }
            elseif (in_array($modeStr, ['soir', 'CS'])) { $mode = 3; }
        }

        // Resolve IDqualification_dplm from diplome_vise code
        $diplomeCode = trim($postData['diplome_vise'] ?? '');
        $qualifDplmId = $diplomeCode ? $this->repository->getQualificationIdByCode($diplomeCode) : 0;

        $dateDebutSelection = !empty($postData['date_debut_selection']) ? $postData['date_debut_selection'] : null;
        $dateExamenMedical = !empty($postData['date_examen_medical']) ? $postData['date_examen_medical'] : null;
        $dateVisiteAteliers = !empty($postData['date_visite_ateliers']) ? $postData['date_visite_ateliers'] : null;
        
        $encadrement = isset($postData['toggle_encadrement']) ? 1 : 0;
        $programme = isset($postData['toggle_programme']) ? 1 : 0;
        $equipement = isset($postData['toggle_equipement']) ? 1 : 0;
        $etabDelegueId = !empty($postData['etablissement_delegue_id']) ? (int)$postData['etablissement_delegue_id'] : null;

        // JSON encode regime_cours, type_branche, hebergement inside Obs
        $obsArray = [
            'regime_cours' => trim($postData['regime_cours'] ?? 'فردي'),
            'type_branche' => trim($postData['type_branche'] ?? 'جديدة'),
            'hebergement' => trim($postData['hebergement'] ?? 'خارجي')
        ];
        $obs = json_encode($obsArray, JSON_UNESCAPED_UNICODE);

        $offerData = [
            'specialite_id' => $specialiteId,
            'mode' => $mode,
            'debut' => $debut,
            'fin' => $fin,
            'capacite' => $capacite,
            'etablissement_id' => $etabId,
            'date_debut_selection' => $dateDebutSelection,
            'date_examen_medical' => $dateExamenMedical,
            'date_visite_ateliers' => $dateVisiteAteliers,
            'nbr_groupe' => $encadrement, // keep backward compatibility in schema
            'encadrement' => $encadrement,
            'programme' => $programme,
            'equipement' => $equipement,
            'etablissement_delegue_id' => $etabDelegueId,
            'obs' => $obs,
            'qualification_dplm_id' => $qualifDplmId,
            'nom_spec_custom_ar' => trim($postData['nom_spec_custom_ar'] ?? '') ?: null,
            'nom_spec_custom_fr' => trim($postData['nom_spec_custom_fr'] ?? '') ?: null,
        ];

        $sessionIdInput = $postData['session_id'] ?? '';
        $sessionNameInput = trim($postData['session_name'] ?? '');

        Database::getInstance()->transaction(function (PDO $pdo) use (&$offerData, $sessionIdInput, $sessionNameInput, $currentUser) {
            $sessId = 0;
            if ($sessionIdInput === 'custom' && !empty($sessionNameInput)) {
                $stmtCheck = $pdo->prepare("SELECT IDSession FROM session WHERE Nom = ? LIMIT 1");
                $stmtCheck->execute([$sessionNameInput]);
                $existing = $stmtCheck->fetchColumn();
                
                if ($existing) {
                    $sessId = (int)$existing;
                } else {
                    $stmtMax = $pdo->query("SELECT COALESCE(MAX(IDSession), 0) + 1 FROM session");
                    $sessId = (int)$stmtMax->fetchColumn();
                    
                    // Code and NomFr generation
                    $year = date('Y');
                    if (preg_match('/(\d{4})/', $sessionNameInput, $m)) {
                        $year = $m[1];
                    }
                    $prefix = 'SESS';
                    if (mb_strpos($sessionNameInput, 'فيفري') !== false) {
                        $prefix = 'F';
                    } elseif (mb_strpos($sessionNameInput, 'سبتمبر') !== false) {
                        $prefix = 'S';
                    } elseif (mb_strpos($sessionNameInput, 'أكتوبر') !== false) {
                        $prefix = 'O';
                    }
                    $code = $prefix . $year;
                    
                    $nomFr = 'Session ' . $year;
                    if ($prefix === 'F') {
                        $nomFr = 'Session Février ' . $year;
                    } elseif ($prefix === 'S') {
                        $nomFr = 'Session Septembre ' . $year;
                    } elseif ($prefix === 'O') {
                        $nomFr = 'Session Octobre ' . $year;
                    }
                    
                    $stmtInsertSess = $pdo->prepare("
                        INSERT INTO session (IDSession, Nom, NomFr, DateD, CodeMihnati, Encour, Clouture, Code, ouvertoffre, ouvertinscription)
                        VALUES (?, ?, ?, ?, 0, 1, 0, ?, 1, 1)
                    ");
                    $stmtInsertSess->execute([
                        $sessId,
                        $sessionNameInput,
                        $nomFr,
                        $offerData['debut'] ?: date('Y-m-d'),
                        $code
                    ]);
                }
            } else {
                $sessId = (int)$sessionIdInput;
            }
            
            $offerData['session_id'] = $sessId;

            // Concurrency lock calculate
            $newId = $this->repository->getNextIdWithLock($pdo);
            $this->repository->insertOffre($pdo, $newId, $offerData);

            // Audit Trail Log
            $this->auditService->log(
                $currentUser['username'] ?? 'unknown',
                'CREATE',
                'offre',
                (string)$newId,
                null,
                ['IDOffre' => $newId, 'IDSpecialite' => $offerData['specialite_id'], 'IDEts_Form' => $offerData['etablissement_id']]
            );
        });
    }

    /**
     * Update an existing training offer
     */
    public function updateOffer(array $postData, array $currentUser): void
    {
        $id = (int)$postData['id'];
        
        // Fetch existing offer details
        $offer = $this->repository->find($id);
        if (!$offer) {
            throw new Exception("عرض التكوين غير موجود / Offer not found.");
        }

        // Verify spatial boundaries
        $this->authService->authorize($currentUser, 'offer.view', [
            'etablissement_id' => $offer['IDEts_Form']
        ]);

        $specialiteId = (int)$postData['specialite_id'];
        $capacite = (int)($postData['capacite'] ?? 0);
        $debut = !empty($postData['date_debut']) ? $postData['date_debut'] : null;
        $fin = !empty($postData['date_fin']) ? $postData['date_fin'] : null;
        
        $modeStr = trim($postData['mode_formation'] ?? '');
        // Form now sends numeric IDMode_formation directly; fall back to legacy text codes
        if (ctype_digit($modeStr) && (int)$modeStr > 0) {
            $mode = (int)$modeStr;
        } else {
            // Legacy text-code fallback mapping
            $mode = 1; // default: حضوري أولي
            if (in_array($modeStr, ['apprentissage', 'APP'])) { $mode = 10; }
            elseif (in_array($modeStr, ['continu', 'FC'])) { $mode = 2; }
            elseif (in_array($modeStr, ['soir', 'CS'])) { $mode = 3; }
        }

        // Resolve IDqualification_dplm from diplome_vise code
        $diplomeCode = trim($postData['diplome_vise'] ?? '');
        $qualifDplmId = $diplomeCode ? $this->repository->getQualificationIdByCode($diplomeCode) : 0;

        $dateDebutSelection = !empty($postData['date_debut_selection']) ? $postData['date_debut_selection'] : null;
        $dateExamenMedical = !empty($postData['date_examen_medical']) ? $postData['date_examen_medical'] : null;
        $dateVisiteAteliers = !empty($postData['date_visite_ateliers']) ? $postData['date_visite_ateliers'] : null;
        
        $encadrement = isset($postData['toggle_encadrement']) ? 1 : 0;
        $programme = isset($postData['toggle_programme']) ? 1 : 0;
        $equipement = isset($postData['toggle_equipement']) ? 1 : 0;
        $etabDelegueId = !empty($postData['etablissement_delegue_id']) ? (int)$postData['etablissement_delegue_id'] : null;

        // JSON encode regime_cours, type_branche, hebergement inside Obs
        $obsArray = [
            'regime_cours' => trim($postData['regime_cours'] ?? 'فردي'),
            'type_branche' => trim($postData['type_branche'] ?? 'جديدة'),
            'hebergement' => trim($postData['hebergement'] ?? 'خارجي')
        ];
        $obs = json_encode($obsArray, JSON_UNESCAPED_UNICODE);

        $updateData = [
            'id' => $id,
            'specialite_id' => $specialiteId,
            'mode' => $mode,
            'debut' => $debut,
            'fin' => $fin,
            'capacite' => $capacite,
            'date_debut_selection' => $dateDebutSelection,
            'date_examen_medical' => $dateExamenMedical,
            'date_visite_ateliers' => $dateVisiteAteliers,
            'nbr_groupe' => $encadrement, // keep backward compatibility in schema
            'encadrement' => $encadrement,
            'programme' => $programme,
            'equipement' => $equipement,
            'etablissement_delegue_id' => $etabDelegueId,
            'obs' => $obs,
            'qualification_dplm_id' => $qualifDplmId,
            'nom_spec_custom_ar' => trim($postData['nom_spec_custom_ar'] ?? '') ?: null,
            'nom_spec_custom_fr' => trim($postData['nom_spec_custom_fr'] ?? '') ?: null,
        ];

        $sessionIdInput = $postData['session_id'] ?? '';
        $sessionNameInput = trim($postData['session_name'] ?? '');

        Database::getInstance()->transaction(function (PDO $pdo) use (&$updateData, $sessionIdInput, $sessionNameInput, $id, $offer, $currentUser) {
            $sessId = 0;
            if ($sessionIdInput === 'custom' && !empty($sessionNameInput)) {
                $stmtCheck = $pdo->prepare("SELECT IDSession FROM session WHERE Nom = ? LIMIT 1");
                $stmtCheck->execute([$sessionNameInput]);
                $existing = $stmtCheck->fetchColumn();
                
                if ($existing) {
                    $sessId = (int)$existing;
                } else {
                    $stmtMax = $pdo->query("SELECT COALESCE(MAX(IDSession), 0) + 1 FROM session");
                    $sessId = (int)$stmtMax->fetchColumn();
                    
                    // Code and NomFr generation
                    $year = date('Y');
                    if (preg_match('/(\d{4})/', $sessionNameInput, $m)) {
                        $year = $m[1];
                    }
                    $prefix = 'SESS';
                    if (mb_strpos($sessionNameInput, 'فيفري') !== false) {
                        $prefix = 'F';
                    } elseif (mb_strpos($sessionNameInput, 'سبتمبر') !== false) {
                        $prefix = 'S';
                    } elseif (mb_strpos($sessionNameInput, 'أكتوبر') !== false) {
                        $prefix = 'O';
                    }
                    $code = $prefix . $year;
                    
                    $nomFr = 'Session ' . $year;
                    if ($prefix === 'F') {
                        $nomFr = 'Session Février ' . $year;
                    } elseif ($prefix === 'S') {
                        $nomFr = 'Session Septembre ' . $year;
                    } elseif ($prefix === 'O') {
                        $nomFr = 'Session Octobre ' . $year;
                    }
                    
                    $stmtInsertSess = $pdo->prepare("
                        INSERT INTO session (IDSession, Nom, NomFr, DateD, CodeMihnati, Encour, Clouture, Code, ouvertoffre, ouvertinscription)
                        VALUES (?, ?, ?, ?, 0, 1, 0, ?, 1, 1)
                    ");
                    $stmtInsertSess->execute([
                        $sessId,
                        $sessionNameInput,
                        $nomFr,
                        $updateData['debut'] ?: date('Y-m-d'),
                        $code
                    ]);
                }
            } else {
                $sessId = (int)$sessionIdInput;
            }
            
            $updateData['session_id'] = $sessId;

            $this->repository->updateOffre($updateData);

            // Audit Trail Log
            $this->auditService->log(
                $currentUser['username'] ?? 'unknown',
                'UPDATE',
                'offre',
                (string)$id,
                $offer,
                $updateData
            );
        });
    }

    /**
     * Delete training offer with constraints validation
     */
    public function deleteOffer(int $id, array $currentUser): void
    {
        $offer = $this->repository->find($id);
        if (!$offer) {
            throw new Exception("عرض التكوين غير موجود / Offer not found.");
        }

        // Verify spatial boundaries
        $this->authService->authorize($currentUser, 'offer.view', [
            'etablissement_id' => $offer['IDEts_Form']
        ]);

        // Check if sections are linked
        $linkedSections = $this->repository->checkSectionLinks($id);
        if ($linkedSections > 0) {
            throw new Exception("لا يمكن حذف عرض التكوين هذا لوجود أقسام مرتبطة به / Offer linked to existing sections.");
        }

        $this->repository->delete($id);

        // Audit Trail Log
        $this->auditService->log(
            $currentUser['username'] ?? 'unknown',
            'DELETE',
            'offre',
            (string)$id,
            $offer,
            null
        );
    }

    /**
     * Submit offer to Regional Direction (DFEP)
     */
    public function submitOffer(int $id, array $currentUser): void
    {
        $offer = $this->repository->find($id);
        if (!$offer) {
            throw new Exception("عرض التكوين غير موجود / Offer not found.");
        }

        $this->authService->authorize($currentUser, 'offer.view', [
            'etablissement_id' => $offer['IDEts_Form']
        ]);

        $this->repository->submit($id);

        // Audit Trail Log
        $this->auditService->log(
            $currentUser['username'] ?? 'unknown',
            'SUBMIT',
            'offre',
            (string)$id,
            $offer,
            ['Valide' => 1]
        );
    }

    /**
     * Approve or reject offer at Regional Direction (DFEP) level
     */
    public function validateDirection(int $id, string $action, ?string $motif, array $currentUser): void
    {
        $roleCode = strtolower($currentUser['role_code'] ?? '');
        $dfepId = (int)($currentUser['iddfep'] ?? 0);

        if ($roleCode !== 'dfep') {
            throw new Exception("غير مصرح لك بالمصادقة على هذا العرض / Unauthorized.");
        }

        $offer = $this->repository->find($id);
        if (!$offer) {
            throw new Exception("عرض التكوين غير موجود / Offer not found.");
        }

        $val = ($action === 'approuver') ? 1 : 0;
        $this->repository->validateDirection($id, $val, $motif);

        // Audit Trail Log
        $this->auditService->log(
            $currentUser['username'] ?? 'unknown',
            'VALIDATE_DFEP',
            'offre',
            (string)$id,
            $offer,
            ['ValidDfp' => $val, 'Obs_Dfep' => $motif]
        );
    }

    /**
     * Approve or reject offer at Central Administration level
     */
    public function validateCentral(int $id, string $action, ?string $motif, array $currentUser): void
    {
        $roleCode = strtolower($currentUser['role_code'] ?? '');
        if (!in_array($roleCode, ['admin', 'central'])) {
            throw new Exception("غير مصرح لك بالمصادقة النهائية على هذا العرض / Unauthorized.");
        }

        $offer = $this->repository->find($id);
        if (!$offer) {
            throw new Exception("عرض التكوين غير موجود / Offer not found.");
        }

        $val = ($action === 'approuver') ? 1 : 0;
        $this->repository->validateCentral($id, $val, $motif);

        // Audit Trail Log
        $this->auditService->log(
            $currentUser['username'] ?? 'unknown',
            'VALIDATE_CENTRAL',
            'offre',
            (string)$id,
            $offer,
            ['ValideCentral' => $val, 'Obs_Central' => $motif]
        );
    }

    /**
     * Fetch offers for print handbook output
     */
    public function getPrintOffres(): array
    {
        return $this->repository->getPrintOffres();
    }

    /**
     * Map database row into a structured output representation
     */
    private function formatOffreRow(array $rd): array
    {
        if ($rd['ValideCentral']) {
            $statut = 'مقبول مركزيا';
        } elseif ($rd['Obs_Central'] !== null && $rd['Obs_Central'] !== '') {
            $statut = 'مرفوض مركزيا';
        } elseif ($rd['ValidDfp']) {
            $statut = 'مصادق عليه ولائيا';
        } elseif ($rd['Obs_Dfep'] !== null && $rd['Obs_Dfep'] !== '') {
            $statut = 'مرفوض ولائيا';
        } elseif ($rd['Valide']) {
            $statut = 'مرفوع للولاية';
        } else {
            $statut = 'مسودة';
        }

        // Resolve diploma from IDqualification_dplm (DB-driven)
        $diplome = '';
        $dplmId = (int)($rd['IDqualification_dplm'] ?? 0);
        if ($dplmId > 0) {
            $diplome = $this->repository->getQualificationCodeById($dplmId);
        }
        // Infer semesters, diploma, and level requisites from specialty name (fallback)
        $spec_name = $rd['spec_ar'] ?? '';
        $spec_name_fr = $rd['spec_fr'] ?? '';
        if (stripos($spec_name, 'تقني سامي') !== false || stripos($spec_name_fr, 'TS') !== false || stripos($spec_name_fr, 'Supérieur') !== false) {
            $semesters = 5;
            if (!$diplome) $diplome = 'BTS';
            $niveau = 'bac';
            $niveau_txt = 'ثالثة ثانوي';
        } elseif (stripos($spec_name, 'تقني') !== false || stripos($spec_name_fr, 'Technicien') !== false) {
            $semesters = 4;
            if (!$diplome) $diplome = 'BP';
            $niveau = 'bem';
            $niveau_txt = 'تعليم متوسط';
        } else {
            $semesters = 2;
            if (!$diplome) $diplome = 'CAP';
            $niveau = 'sans_niveau';
            $niveau_txt = 'بدون مستوى';
        }

        // Decode metadata from Obs column (JSON format)
        $obs = [];
        if (!empty($rd['Obs'])) {
            $obs = json_decode($rd['Obs'], true) ?? [];
        }
        $regime_cours = $obs['regime_cours'] ?? 'فردي';
        $type_branche = $obs['type_branche'] ?? 'جديدة';
        $hebergement = $obs['hebergement'] ?? 'خارجي';

        return [
            'id'           => $rd['id'],
            'code'         => 'OFF-' . $rd['id'],
            'specialite_id'=> $rd['specialite_id'],
            'spec_ar'      => $this->cleanString($rd['spec_ar']),
            'spec_fr'      => $this->cleanString($rd['spec_fr']),
            'spec_code'    => $rd['spec_code'] ?? '',
            'level_name'   => $rd['level_name'] ?? $niveau_txt,
            'centre'       => $this->cleanString($rd['centre']),
            'centre_delegue'=> $this->cleanString($rd['centre_delegue'] ?? ''),
            'session_id'   => $rd['session_id'],
            'session_name' => $rd['session_name'] ?? '',
            'etablissement_delegue_id' => $rd['etablissement_delegue_id'] ?? null,
            'mode_formation' => !empty($rd['mode_formation']) ? $this->cleanString($rd['mode_formation']) : match((int)($rd['IDMode_formation'] ?? 1)) {
                10 => 'تكوين عن طريق التمهين',
                2  => 'تكوين متواصل',
                3  => 'تكوين مسائي',
                1  => 'حضوري أولي',
                default => 'تكوين حضوري'
            },
            'mode_id'      => (int)($rd['IDMode_formation'] ?? 1),
            'date_debut'   => $rd['date_debut'],
            'date_fin'     => $rd['date_fin'],
            'date_visite_ateliers' => $rd['date_visite_ateliers'],
            'date_debut_selection' => $rd['date_debut_selection'],
            'date_examen_medical'  => $rd['date_examen_medical'],
            'statut_offre' => $statut,
            'places'       => (int)($rd['places'] ?? 0),
            'inscrits'     => (int)($rd['inscrits'] ?? 0),
            'inscrits_females' => (int)($rd['inscrits_females'] ?? 0),
            'laureats'     => (int)($rd['laureats'] ?? 0),
            'laureats_females' => (int)($rd['laureats_females'] ?? 0),
            'nbr_groupe'   => (int)($rd['nbrGroupe'] ?? 1),
            'valide'       => (int)($rd['Valide'] ?? 0),
            'valid_dfp'    => (int)($rd['ValidDfp'] ?? 0),
            'diplome_vise' => $diplome,
            'niveau'       => $niveau,
            'niveau_txt'   => $niveau_txt,
            'regime_cours' => $regime_cours,
            'type_branche' => $type_branche,
            'hebergement'  => $hebergement,
            'toggle_encadrement' => ($rd['encadrement'] ?? 0) > 0 ? 1 : 0,
            'toggle_programme'   => ($rd['programme'] ?? 0) > 0 ? 1 : 0,
            'toggle_equipement'  => ($rd['equipement'] ?? 0) > 0 ? 1 : 0,
            'duree'        => $semesters > 0 ? ($semesters . ' سداسيات') : 'غير محددة',
            'valide_par_direction' => $rd['ValidDfp'],
            'date_validation_direction' => null,
            'valide_par_central' => $rd['ValideCentral'],
            'date_approbation_centrale' => null,
            'motif_rejet' => $this->cleanString($rd['Obs_Central'] ?: ($rd['Obs_Dfep'] ?: null)),
            'nom_spec_custom_ar' => $this->cleanString($rd['nom_spec_custom_ar'] ?? null),
            'nom_spec_custom_fr' => $this->cleanString($rd['nom_spec_custom_fr'] ?? null),
        ];
    }

    /**
     * Fix CP850/OEM 850 corrupted UTF-8 strings.
     * When raw UTF-8 bytes like \xc3\xa9 (é) were interpreted as CP850 box characters (├®),
     * this function converts them back to correct UTF-8.
     */
    private function cleanString(mixed $str): string
    {
        if ($str === null) return '';
        $str = (string)$str;
        
        // If typical CP850 corrupted character combinations are found, repair them
        if (strpos($str, '├') !== false || strpos($str, '┬') !== false || strpos($str, '┬а') !== false) {
            $converted = @iconv("UTF-8", "CP850//IGNORE", $str);
            if ($converted !== false && $converted !== '') {
                return $converted;
            }
        }
        
        // Ensure valid UTF-8 string output to prevent htmlspecialchars truncation
        return mb_convert_encoding($str, 'UTF-8', 'UTF-8');
    }
}
