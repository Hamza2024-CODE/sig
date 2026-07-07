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

        // 2. Set up scoping conditions
        $whereConditions = [];
        $scopeParams = [];

        if (in_array($roleCode, ['etablissement', 'directeur', 'formateur']) && $etabId > 0) {
            $whereConditions[] = "o.IDEts_Form = ?";
            $scopeParams[] = $etabId;
        } elseif ($roleCode === 'dfep' && $dfepId > 0) {
            $whereConditions[] = "e.IDDFEP = ?";
            $scopeParams[] = $dfepId;
        }

        if ((int)($currentUser['IDMode_formation'] ?? 0) === 10) {
            $whereConditions[] = "o.IDMode_formation = 10";
        }

        // Apply GET HTTP parameters filters
        if (!empty($getParams['filter_etablissement'])) {
            $whereConditions[] = "o.IDEts_Form = ?";
            $scopeParams[] = (int)$getParams['filter_etablissement'];
        } elseif (!empty($getParams['filter_etab'])) {
            $whereConditions[] = "o.IDEts_Form = ?";
            $scopeParams[] = (int)$getParams['filter_etab'];
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
                $whereConditions[] = "o.IDMode_formation IN ('2', '10')";
            } elseif ($modeStr === 'presentiel') {
                $whereConditions[] = "o.IDMode_formation = '1'";
            } else {
                $whereConditions[] = "o.IDMode_formation = ?";
                $scopeParams[] = $modeStr;
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

        // 5. Fetch dropdown lists
        $specialites = $this->repository->getModalSpecialites();
        $etablissements = $this->repository->getModalEtablissements($roleCode, $etabId, $dfepId);
        $sessions = $this->repository->getModalSessions();
        // Study conditions dropdowns — sourced from DB
        $qualifications_diplomes = $this->repository->getModalQualificationsDiplomes();
        $regimes_hebergement     = $this->repository->getModalRegimesHebergement();
        $regimes_cours           = $this->repository->getModalRegimesCours();
        $modes_formation         = $this->repository->getModalModesFormation();

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
            'total_pending' => count($pending),
            'total_approved' => 0,
            'total_rejected' => 0
        ];

        foreach ($processed as $o) {
            if ($o['statut_offre'] === 'مقبول مركزيا' || $o['statut_offre'] === 'مصادق عليه ولائيا') {
                $stats['total_approved']++;
            } elseif ($o['statut_offre'] === 'مرفوض ولائيا' || $o['statut_offre'] === 'مرفوض مركزيا') {
                $stats['total_rejected']++;
            }
        }

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
            'spec_ar'      => $rd['spec_ar'],
            'spec_fr'      => $rd['spec_fr'],
            'centre'       => $rd['centre'],
            'centre_delegue'=> $rd['centre_delegue'] ?? '',
            'session_id'   => $rd['session_id'],
            'session_name' => $rd['session_name'] ?? '',
            'etablissement_delegue_id' => $rd['etablissement_delegue_id'] ?? null,
            'mode_formation' => $rd['mode_formation'] ?? '',
            'mode_id'      => (int)($rd['IDMode_formation'] ?? 1),
            'date_debut'   => $rd['date_debut'],
            'date_fin'     => $rd['date_fin'],
            'date_visite_ateliers' => $rd['date_visite_ateliers'],
            'date_debut_selection' => $rd['date_debut_selection'],
            'date_examen_medical'  => $rd['date_examen_medical'],
            'statut_offre' => $statut,
            'places'       => (int)($rd['places'] ?? 0),
            'inscrits'     => (int)($rd['inscrits'] ?? 0),
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
            'motif_rejet' => $rd['Obs_Central'] ?: ($rd['Obs_Dfep'] ?: null)
        ];
    }
}
