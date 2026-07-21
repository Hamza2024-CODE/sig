<?php

namespace App\Domains\Academic\Services;

use App\Core\Database;
use App\Domains\Academic\Repositories\ApprenantRepository;
use App\Domains\Security\AuthorizationService;
use App\Audit\AuditService;
use App\Events\EventDispatcher;
use Exception;
use PDO;

/**
 * ApprenantService
 *
 * Business logic for:
 *   - Trainee management (effectifs / apprenants)
 *   - Grades / modules (GradesController)
 *   - Absences (AbsencesController)
 *
 * All SQL delegation goes through ApprenantRepository.
 * Business rules preserved from legacy Admin controllers.
 */
class ApprenantService
{
    protected ApprenantRepository  $repository;
    protected AuthorizationService $authService;
    protected AuditService        $auditService;

    public function __construct(
        ApprenantRepository  $repository,
        AuthorizationService $authService,
        AuditService        $auditService
    ) {
        $this->repository  = $repository;
        $this->authService = $authService;
        $this->auditService = $auditService;
    }

    // ─── Role Filtering ───────────────────────────────────────────────────────

    /**
     * Build role-scoped SQL WHERE fragment + params for section-based filtering.
     * Logic preserved from AbsencesController::getEtabFilter() and GradesController
     */
    public function buildRoleFilter(array $user, string $prefix = 's'): array
    {
        $roleCode = strtolower($user['role_code'] ?? '');
        $etabId   = (int)($user['etablissement_id'] ?? 0);
        $dfepId   = (int)($user['iddfep'] ?? 0);

        $extraWhere = '';
        $params     = [];

        if (in_array($roleCode, ['admin', 'central'])) {
            // unrestricted
        } elseif ($roleCode === 'dfep' && $dfepId > 0) {
            $extraWhere = " AND ({$prefix}.IDDFEP = ? OR {$prefix}.IDOffre IN (SELECT IDOffre FROM offre WHERE IDEts_Form IN (SELECT IDetablissement FROM etablissement WHERE IDDFEP = ?)))";
            $params[]   = $dfepId;
            $params[]   = $dfepId;
        } elseif ($etabId > 0) {
            $extraWhere = " AND {$prefix}.IDOffre IN (SELECT IDOffre FROM offre WHERE IDEts_Form = ?)";
            $params[]   = $etabId;
        }

        return [$extraWhere, $params];
    }

    // ─── Trainee Queries ──────────────────────────────────────────────────────

    /**
     * List active apprenants with role-based filtering
     */
    public function listActive(array $user): array
    {
        [$extraWhere, $params] = $this->buildRoleFilter($user);
        return $this->repository->findActiveFiltered($extraWhere, $params);
    }

    /**
     * Simple dropdown list (for modals / selects)
     */
    public function listDropdown(): array
    {
        return $this->repository->findActiveDropdown();
    }

    // ─── Grade Operations ─────────────────────────────────────────────────────

    /**
     * Ensure section semesters and their modules exist in the database.
     * If they don't, initialize them from specialty module templates or fallback placeholders.
     */
    public function ensureSectionSemestreModules(int $offreId, int $semestre): void
    {
        $db = Database::getInstance()->getConnection();
        
        // Find sections for this offer
        $stmtSec = $db->prepare("SELECT IDSection, IDSpecialite FROM section WHERE IDOffre = ?");
        $stmtSec->execute([$offreId]);
        $sections = $stmtSec->fetchAll(PDO::FETCH_ASSOC);
        if (empty($sections)) {
            return;
        }

        foreach ($sections as $sec) {
            $sectionId = (int)$sec['IDSection'];
            $specId = (int)$sec['IDSpecialite'];

            // Find or create section_semestre row
            $stmtSs = $db->prepare("SELECT IDSection_Semestre FROM section_semestre WHERE IDSection = ? AND NumSem = ?");
            $stmtSs->execute([$sectionId, $semestre]);
            $ssId = $stmtSs->fetchColumn();

            if (!$ssId) {
                // Generate primary key manually
                $maxSsId = (int)$db->query("SELECT COALESCE(MAX(IDSection_Semestre), 0) FROM section_semestre")->fetchColumn();
                $ssId = $maxSsId + 1;
                $stmtInsSs = $db->prepare("
                    INSERT INTO section_semestre (
                        IDSection_Semestre, IDSection, NumSem, IDSemestre_formation, Groupe,
                        visaemtdir, visaemtdfep, visaevaldir, visaevaldfep, Dernier, NbrAppren, NbrApprenf
                    ) VALUES (?, ?, ?, 0, 0, 0, 0, 0, 0, 1, 0, 0)
                ");
                $stmtInsSs->execute([$ssId, $sectionId, $semestre]);
            }

            // Check if modules already exist in section_semestre_module for this section semester
            $stmtCount = $db->prepare("SELECT COUNT(*) FROM section_semestre_module WHERE IDSection_Semestre = ?");
            $stmtCount->execute([$ssId]);
            $modulesCount = (int)$stmtCount->fetchColumn();

            if ($modulesCount > 0) {
                continue;
            }

            // Look up specialty module templates
            $stmtTemplates = $db->prepare("
                SELECT * FROM specialite_module 
                WHERE IDSpecialite = ? AND NumSem = ?
            ");
            $stmtTemplates->execute([$specId, $semestre]);
            $templates = $stmtTemplates->fetchAll(PDO::FETCH_ASSOC);

            if (empty($templates)) {
                // Fallback standard modules if no specialty modules are defined
                $templates = [
                    [
                        'IDSpecialite_Module' => null,
                        'Nom' => 'وحدة تعليمية 1',
                        'NomFr' => 'Module 1',
                        'Coef' => 2,
                        'NumOrd' => 1,
                        'IDModule_ModeEnseigne' => null
                    ],
                    [
                        'IDSpecialite_Module' => null,
                        'Nom' => 'وحدة تعليمية 2',
                        'NomFr' => 'Module 2',
                        'Coef' => 3,
                        'NumOrd' => 2,
                        'IDModule_ModeEnseigne' => null
                    ],
                    [
                        'IDSpecialite_Module' => null,
                        'Nom' => 'تطبيق مهني',
                        'NomFr' => 'Pratique',
                        'Coef' => 4,
                        'NumOrd' => 3,
                        'IDModule_ModeEnseigne' => null
                    ]
                ];
            }

            // Insert modules dynamically
            $maxSsmId = (int)$db->query("SELECT COALESCE(MAX(IDsection_semestre_Module), 0) FROM section_semestre_module")->fetchColumn();
            $stmtInsSsm = $db->prepare("
                INSERT INTO section_semestre_module (
                    IDsection_semestre_Module, IDModule, IDSection_Semestre, IDEncadrement, coef, Ne,
                    ExisC1, ExisC2, ExisCs, ExisCr, Periode, visaenc1, visaenc2, visaencs, visaencr,
                    NumOrd, IDModule_ModeEnseigne, NomMdl, NomFrMdl, VolHor, VolHors
                ) VALUES (?, ?, ?, NULL, ?, 0, 1, 1, 1, 1, 0, 0, 0, 0, 0, ?, ?, ?, ?, 0, 0)
            ");

            foreach ($templates as $idx => $tmpl) {
                $maxSsmId++;
                $nomAr = !empty($tmpl['Nom']) ? $tmpl['Nom'] : (!empty($tmpl['NomFr']) ? $tmpl['NomFr'] : '');
                $nomFr = !empty($tmpl['NomFr']) ? $tmpl['NomFr'] : (!empty($tmpl['Nom']) ? $tmpl['Nom'] : '');
                $coef = !empty($tmpl['Coef']) ? (int)$tmpl['Coef'] : 2;

                $modeEnseigneId = $tmpl['IDModule_ModeEnseigne'] ?? null;
                if ($modeEnseigneId !== null) {
                    $exists = \Illuminate\Support\Facades\DB::table('module_modeenseigne')->where('IDModule_ModeEnseigne', $modeEnseigneId)->exists();
                    if (!$exists) {
                        $modeEnseigneId = null;
                    }
                }

                $stmtInsSsm->execute([
                    $maxSsmId,
                    $tmpl['IDSpecialite_Module'] ?? null,
                    $ssId,
                    $coef,
                    $tmpl['NumOrd'] ?? ($idx + 1),
                    $modeEnseigneId,
                    $nomAr,
                    $nomFr
                ]);
            }
        }
    }

    /**
     * Fetch trainees with grade data for a given offre and module.
     * Business rules preserved from GradesController::input()
     */
    public function getTraineesWithGrades(int $offreId, int $matiereId, ?int $employeurId = null): array
    {
        return $this->repository->findWithGradesByOffre($offreId, $matiereId, $employeurId);
    }

    /**
     * Batch-save grades for multiple trainees.
     * Integrates GradingSystemService calculations and workflow lock checks.
     *
     * @param  array $user       Authenticated user
     * @param  int   $offreId
     * @param  int   $matiereId  IDsection_semestre_Module
     * @param  int   $semestre
     * @param  array $grades     ['<apprenantId>' => ['cc1' => '', 'cc2' => '', 'exam' => '', ...]]
     * @return int   Number of records saved
     * @throws Exception
     */
    public function saveGrades(array $user, int $offreId, int $matiereId, int $semestre, array $grades): int
    {
        $this->authService->authorize($user, 'grades.store');

        // Check window constraint for Teachers
        $config = \App\Helpers\GradingConfigHelper::read();
        $role = strtolower($user['role_code'] ?? 'user');
        if (in_array($role, ['formateur', 'employee'])) {
            $now = date('Y-m-d');
            $start = $config['workflow']['grading_start_date'] ?? '';
            $end = $config['workflow']['grading_end_date'] ?? '';
            
            // Check if there is an override for this formateur or establishment
            $allowedEstabs = $config['workflow']['remedial_allowed_establishments'] ?? [];
            $userEtab = (int)($user['etablissement_id'] ?? 0);
            
            $isAllowed = false;
            if ($start && $end && $now >= $start && $now <= $end) {
                $isAllowed = true;
            }
            if (in_array($userEtab, $allowedEstabs)) {
                $isAllowed = true;
            }

            if (!$isAllowed) {
                throw new Exception("فترة رصد النقاط مغلقة حالياً أو غير مصرح لمؤسستك بالتعديل. (الفترة الرسمية: من {$start} إلى {$end})");
            }
        }

        $count = 0;
        $gradingService = new \App\Domains\Academic\Services\GradingSystemService();

        Database::getInstance()->transaction(function (PDO $pdo) use ($grades, $matiereId, $offreId, $config, $gradingService, &$count) {
            // Load module metadata
            $stmt = $pdo->prepare("
                SELECT ssm.IDsection_semestre_Module, ssm.IDModule, ssm.IDSection_Semestre, ss.IDSection, o.IDMode_formation, o.IDOffre,
                       CASE 
                           WHEN ssm.NomMdl LIKE '%تربص%' OR ssm.NomFrMdl LIKE '%stage%' OR ssm.NomMdl LIKE '%ميداني%' THEN 'stage_pratique'
                           WHEN ssm.NomMdl LIKE '%مذكرة%' OR ssm.NomFrMdl LIKE '%memoire%' OR ssm.NomMdl LIKE '%تخرج%' THEN 'memoire'
                           ELSE 'theorique'
                       END as type_matiere
                FROM section_semestre_module ssm
                JOIN section_semestre ss ON ssm.IDSection_Semestre = ss.IDSection_Semestre
                JOIN section sec ON ss.IDSection = sec.IDSection
                JOIN offre o ON sec.IDOffre = o.IDOffre
                WHERE ssm.IDsection_semestre_Module = ?
            ");
            $stmt->execute([$matiereId]);
            $moduleMeta = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$moduleMeta) {
                throw new Exception("لم يتم العثور على المادة المحددة.");
            }
            if ((int)$moduleMeta['IDOffre'] !== $offreId) {
                throw new Exception("المادة المحددة لا تنتمي إلى هذا العرض لتفادي تداخل وتلف البيانات.");
            }

            $sectionSemestreId = (int)$moduleMeta['IDSection_Semestre'];
            $moduleId          = (int)$moduleMeta['IDModule'];
            $typeMatiere       = $moduleMeta['type_matiere'];
            $modeFormation     = $moduleMeta['IDMode_formation'];

            foreach ($grades as $sid => $g) {
                $sid  = (int)$sid;
                $obs  = trim($g['observation'] ?? '');

                // Parse detailed grades
                $gradeInputs = [
                    'type_matiere' => $typeMatiere,
                    'cc1' => isset($g['cc1']) && $g['cc1'] !== '' ? (float)$g['cc1'] : null,
                    'cc2' => isset($g['cc2']) && $g['cc2'] !== '' ? (float)$g['cc2'] : null,
                    'exam' => isset($g['exam']) && $g['exam'] !== '' ? (float)$g['exam'] : null,
                    'rattrapage' => isset($g['rattrapage']) && $g['rattrapage'] !== '' ? (float)$g['rattrapage'] : null,
                    'stage' => isset($g['stage']) && $g['stage'] !== '' ? (float)$g['stage'] : null,
                    'memoire' => isset($g['memoire']) && $g['memoire'] !== '' ? (float)$g['memoire'] : null,
                    'soutenance' => isset($g['soutenance']) && $g['soutenance'] !== '' ? (float)$g['soutenance'] : null,
                ];

                // Calculate module average and status
                $calc = $gradingService->calculateModuleGrade($gradeInputs, $config, (int)$modeFormation);

                // Save to legacy table (apprenant_section_semstre_module)
                $assId = $this->repository->findOrCreateSectionSemestre($pdo, $sid, $sectionSemestreId);
                $this->repository->upsertDetailedGrade(
                    $pdo, 
                    $assId, 
                    $matiereId, 
                    $moduleId, 
                    $gradeInputs['cc1'], 
                    $gradeInputs['cc2'], 
                    $gradeInputs['exam'], 
                    $gradeInputs['rattrapage'], 
                    $calc['moy_avr'], 
                    $calc['moy_apr'], 
                    $obs
                );

                // Calculate overall semester GPA for this trainee
                $stmtMods = $pdo->prepare("
                    SELECT ssm2.coef as coefficient, assm2.MoyAvr as note_avr, assm2.MoyApr as note_apr
                    FROM section_semestre_module ssm2
                    LEFT JOIN apprenant_section_semstre_module assm2 
                        ON assm2.IDsection_semestre_Module = ssm2.IDsection_semestre_Module
                        AND assm2.IDapprenant_Section_semstre = ?
                    WHERE ssm2.IDSection_Semestre = ?
                ");
                $stmtMods->execute([$assId, $sectionSemestreId]);
                $studentSemesterModules = $stmtMods->fetchAll(PDO::FETCH_ASSOC);

                // Get NoteStage from student semester record
                $stmtStage = $pdo->prepare("SELECT NoteStage FROM apprenant_section_semstre WHERE IDapprenant_Section_semstre = ?");
                $stmtStage->execute([$assId]);
                $noteStageVal = $stmtStage->fetchColumn();

                // Compute overall GPA
                $semCalc = $gradingService->calculateSemesterGpa($studentSemesterModules, $noteStageVal, $modeFormation, $config);

                // Update special grades & GPAs in apprenant_section_semstre
                $this->repository->updateSemesterSpecialGrades(
                    $pdo, 
                    $assId, 
                    $gradeInputs['stage'], 
                    $gradeInputs['memoire'], 
                    $gradeInputs['soutenance'], 
                    $semCalc['gpa_avr'], 
                    $semCalc['gpa_apr']
                );

                $count++;
            }
        });

        return $count;
    }

    // ─── Absence Operations ───────────────────────────────────────────────────

    /**
     * Fetch absence dashboard statistics.
     * Business rules preserved from AbsencesController::index()
     */
    public function getAbsenceDashboardStats(array $user): array
    {
        [$extraWhere, $params] = $this->buildRoleFilter($user);

        $statsCacheKey = 'attendance_stats_' . ($user['role_code'] ?? 'user') . '_' . ($user['iddfep'] ?? 0) . '_' . ($user['etablissement_id'] ?? 0);

        return \Illuminate\Support\Facades\Cache::remember($statsCacheKey, 300, function() use ($extraWhere, $params) {
            return [
                'total_trainees' => $this->repository->countActive($extraWhere, $params),
                'total_absences' => $this->repository->countAbsences($extraWhere, $params),
                'total_warnings' => $this->repository->countWarnings($extraWhere, $params),
                'recent_absences' => $this->repository->findRecentAbsences($extraWhere, $params),
            ];
        });
    }

    /**
     * Fetch complete attendance page dataset: sections, specialties, filtered trainees (new & continuing), and stats.
     */
    public function getAttendancePageData(array $user, array $filters = []): array
    {
        [$extraWhere, $params] = $this->buildRoleFilter($user);

        $sections    = $this->repository->findSectionsForAttendance($extraWhere, $params);
        $specialites = $this->repository->findSpecialitesForAttendance($extraWhere, $params);

        $sectionId    = isset($filters['section_id']) && $filters['section_id'] !== '' ? (int)$filters['section_id'] : null;
        $specialiteId = isset($filters['specialite_id']) && $filters['specialite_id'] !== '' ? (int)$filters['specialite_id'] : null;
        $traineeType  = $filters['trainee_type'] ?? 'all';
        $search       = $filters['search'] ?? null;

        $trainees = $this->repository->findTraineesForAttendance(
            $extraWhere, $params, $sectionId, $specialiteId, $traineeType, $search, 500
        );

        $stats = $this->getAbsenceDashboardStats($user);

        // Cache exact total new (S1) and continuing (S2+) count for 300s
        $cacheKey = 'attendance_counts_' . ($user['role_code'] ?? 'user') . '_' . ($user['iddfep'] ?? 0) . '_' . ($user['etablissement_id'] ?? 0) . '_' . md5(serialize($filters));

        [$displayedNew, $displayedContinuing] = \Illuminate\Support\Facades\Cache::remember($cacheKey, 300, function() use ($extraWhere, $params, $sectionId, $specialiteId, $search) {
            return [
                $this->repository->countNewTrainees($extraWhere, $params, $sectionId, $specialiteId, $search),
                $this->repository->countContinuingTrainees($extraWhere, $params, $sectionId, $specialiteId, $search),
            ];
        });

        return [
            'sections'            => $sections,
            'specialites'         => $specialites,
            'trainees'            => $trainees,
            'stats'               => $stats,
            'displayed_new'       => $displayedNew,
            'displayed_continuing'=> $displayedContinuing,
            'selected_filters'    => compact('sectionId', 'specialiteId', 'traineeType', 'search'),
        ];
    }

    /**
     * Fetch list of apprenants for absence recording.
     * Business rules preserved from AbsencesController::add()
     */
    public function listForAbsenceRecording(array $user): array
    {
        [$extraWhere, $params] = $this->buildRoleFilter($user);
        return $this->repository->findActiveFiltered($extraWhere, $params, 200);
    }

    /**
     * Record absences for a list of apprenant IDs on a given date.
     * Business rules preserved from AbsencesController::store()
     *
     * @param  array  $user
     * @param  array  $absentIds     Apprenant IDs
     * @param  string $date          'Y-m-d'
     * @param  string $heure         'H:i:s'
     * @throws Exception
     */
    public function recordAbsences(array $user, array $absentIds, string $date, string $heure): int
    {
        $this->authService->authorize($user, 'discipline.manage');

        [$extraWhere, $params] = $this->buildRoleFilter($user);

        $recorded = 0;

        Database::getInstance()->transaction(function (PDO $pdo) use (
            $absentIds, $date, $heure, $extraWhere, $params, &$recorded
        ) {
            foreach ($absentIds as $apprenantId) {
                $apprenantId = (int)$apprenantId;

                $assId = $this->repository->findSectionSemestreId(
                    $apprenantId, $extraWhere, $params
                );

                if ($assId > 0) {
                    $this->repository->insertAbsence($pdo, $assId, $date, $heure);
                    $recorded++;
                }
            }
        });

        return $recorded;
    }

    /**
     * Fetch warning list (apprenants with 3+ absences).
     * Business rules preserved from AbsencesController::warnings()
     */
    public function getAbsenceWarnings(array $user): array
    {
        [$extraWhere, $params] = $this->buildRoleFilter($user);
        return $this->repository->findAbsenceWarnings($extraWhere, $params);
    }

    /**
     * Fetch trainee detail with total absence count for warning letter rendering.
     * Business rules preserved from AbsencesController::printWarning()
     *
     * @throws Exception
     */
    public function getTraineeForWarningLetter(array $user, int $apprenantId): array
    {
        [$extraWhere, $params] = $this->buildRoleFilter($user);

        $trainee = $this->repository->findTraineeWithAbsenceCount($apprenantId, $extraWhere, $params);

        if (!$trainee) {
            throw new Exception("لم يتم العثور على المتربص أو لا تملك صلاحية الوصول إليه.");
        }

        // Compute warning level (legacy AbsencesController::printWarning logic)
        $total = (float)$trainee['total_absences'];
        $trainee['warning_type']  = 'إنذار أول';
        $trainee['warning_level'] = 1;

        if ($total >= 8) {
            $trainee['warning_type']  = 'قرار إقصاء نهائي';
            $trainee['warning_level'] = 3;
        } elseif ($total >= 5) {
            $trainee['warning_type']  = 'إنذار ثاني';
            $trainee['warning_level'] = 2;
        }

        return $trainee;
    }
}
