<?php

namespace App\Domains\Academic\Controllers;

use App\Http\Controllers\Controller;
use App\Domains\Academic\Services\ApprenantService;
use App\Core\Database;
use PDO;

/**
 * GradesController (Domain)
 *
 * Thin HTTP adapter for trainee grade management.
 * Delegates ALL business logic to ApprenantService.
 * Replaces App\Controllers\Admin\GradesController.
 *
 * Routes handled (see config/routes.php):
 *   GET  /dashboard/grades                    → index()
 *   GET  /dashboard/grades/input              → input()
 *   POST /dashboard/grades/store              → store()
 *   GET  /dashboard/grades/transcript/{id}    → transcript()
 *   GET  /dashboard/grades/deliberation       → deliberation()
 */
class GradesController extends Controller
{
    protected ApprenantService $service;
    protected PDO $db;

    public function __construct(ApprenantService $service)
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['user'])) { $this->redirect('/login'); exit; }

        $this->service = $service;
        $this->db      = Database::getInstance()->getConnection();
    }

    /**
     * Grade management dashboard — offre list and quick stats.
     * SQL for offre listing stays here (presentation logic, not business logic).
     */
    /**
     * Grade management dashboard — offre list and quick stats.
     */
    public function index(): void
    {
        set_time_limit(300); // Allow up to 5 minutes for this heavy stats page
        $user    = $_SESSION['user'];
        $etabId  = $user['etablissement_id'] ?? null;
        $role    = strtolower($user['role_code'] ?? '');
        $dfepId  = $user['iddfep'] ?? null;

        // Offre list - scoped by role
        if ($role === 'formateur' || $role === 'employee') {
            // Formateur: see only offres they teach
            $stmt = $this->db->prepare("
                SELECT DISTINCT o.IDOffre as id, o.IDMode_formation as mode_formation,
                       s.Nom as spec_ar, s.CodeSpec as spec_code,
                       s.NbrSem as duree_semestres,
                       CASE
                           WHEN s.NbrSem >= 5 THEN 'BTS'
                           WHEN s.NbrSem = 4 THEN 'BTS'
                           WHEN s.NbrSem = 3 THEN 'TS'
                           WHEN s.NbrSem = 2 THEN 'CMP'
                           WHEN s.NbrSem = 1 THEN 'Qualifiant'
                           ELSE 'CAP'
                       END as diplome_vise,
                       e.Nom as etab_nom,
                       (SELECT COUNT(*) FROM section sec2
                        JOIN apprenant a ON sec2.IDSection = a.IDSection
                        WHERE sec2.IDOffre = o.IDOffre AND a.statut = 'actif') as nb_actifs
                FROM section_semestre_module ssm
                JOIN section_semestre ss ON ssm.IDSection_Semestre = ss.IDSection_Semestre
                JOIN section sec ON ss.IDSection = sec.IDSection
                JOIN offre o ON sec.IDOffre = o.IDOffre
                JOIN specialite s ON o.IDSpecialite = s.IDSpecialite
                JOIN etablissement e ON o.IDEts_Form = e.IDetablissement
                WHERE ssm.IDEncadrement = ?
                ORDER BY s.Nom
            ");
            $stmt->execute([$user['id']]);

        } elseif (in_array($role, ['admin', 'central'])) {
            // Admin / Central: pre-aggregate nb_actifs to avoid N correlated subqueries
            $stmt = $this->db->prepare("
                SELECT o.IDOffre as id, o.IDMode_formation as mode_formation,
                       s.Nom as spec_ar, s.CodeSpec as spec_code,
                       s.NbrSem as duree_semestres,
                       CASE
                           WHEN s.NbrSem >= 5 THEN 'BTS'
                           WHEN s.NbrSem = 4 THEN 'BTS'
                           WHEN s.NbrSem = 3 THEN 'TS'
                           WHEN s.NbrSem = 2 THEN 'CMP'
                           WHEN s.NbrSem = 1 THEN 'Qualifiant'
                           ELSE 'CAP'
                       END as diplome_vise,
                       e.Nom as etab_nom,
                       COALESCE(ac.nb_actifs, 0) as nb_actifs
                FROM offre o
                JOIN specialite s ON o.IDSpecialite = s.IDSpecialite
                JOIN etablissement e ON o.IDEts_Form = e.IDetablissement
                LEFT JOIN (
                    SELECT sec.IDOffre, COUNT(a.IDapprenant) as nb_actifs
                    FROM apprenant a
                    JOIN section sec ON a.IDSection = sec.IDSection
                    WHERE a.statut = 'actif'
                    GROUP BY sec.IDOffre
                ) ac ON ac.IDOffre = o.IDOffre
                ORDER BY s.Nom
                LIMIT 100
            ");
            $stmt->execute([]);

        } elseif ($role === 'dfep' && $dfepId) {
            // DFEP: pre-aggregate nb_actifs scoped to their wilaya
            $stmt = $this->db->prepare("
                SELECT o.IDOffre as id, o.IDMode_formation as mode_formation,
                       s.Nom as spec_ar, s.CodeSpec as spec_code,
                       s.NbrSem as duree_semestres,
                       CASE
                           WHEN s.NbrSem >= 5 THEN 'BTS'
                           WHEN s.NbrSem = 4 THEN 'BTS'
                           WHEN s.NbrSem = 3 THEN 'TS'
                           WHEN s.NbrSem = 2 THEN 'CMP'
                           WHEN s.NbrSem = 1 THEN 'Qualifiant'
                           ELSE 'CAP'
                       END as diplome_vise,
                       e.Nom as etab_nom,
                       COALESCE(ac.nb_actifs, 0) as nb_actifs
                FROM offre o
                JOIN specialite s ON o.IDSpecialite = s.IDSpecialite
                JOIN etablissement e ON o.IDEts_Form = e.IDetablissement
                LEFT JOIN (
                    SELECT sec.IDOffre, COUNT(a.IDapprenant) as nb_actifs
                    FROM apprenant a
                    JOIN section sec ON a.IDSection = sec.IDSection
                    JOIN offre o2 ON sec.IDOffre = o2.IDOffre
                    JOIN etablissement e2 ON o2.IDEts_Form = e2.IDetablissement
                    WHERE a.statut = 'actif' AND e2.IDDFEP = ?
                    GROUP BY sec.IDOffre
                ) ac ON ac.IDOffre = o.IDOffre
                WHERE e.IDDFEP = ?
                ORDER BY s.Nom
                LIMIT 100
            ");
            $stmt->execute([$dfepId, $dfepId]);

        } else {
            // Directeur / Etablissement: pre-aggregate nb_actifs for their centre
            $stmt = $this->db->prepare("
                SELECT o.IDOffre as id, o.IDMode_formation as mode_formation,
                       s.Nom as spec_ar, s.CodeSpec as spec_code,
                       s.NbrSem as duree_semestres,
                       CASE
                           WHEN s.NbrSem >= 5 THEN 'BTS'
                           WHEN s.NbrSem = 4 THEN 'BTS'
                           WHEN s.NbrSem = 3 THEN 'TS'
                           WHEN s.NbrSem = 2 THEN 'CMP'
                           WHEN s.NbrSem = 1 THEN 'Qualifiant'
                           ELSE 'CAP'
                       END as diplome_vise,
                       e.Nom as etab_nom,
                       COALESCE(ac.nb_actifs, 0) as nb_actifs
                FROM offre o
                JOIN specialite s ON o.IDSpecialite = s.IDSpecialite
                JOIN etablissement e ON o.IDEts_Form = e.IDetablissement
                LEFT JOIN (
                    SELECT sec.IDOffre, COUNT(a.IDapprenant) as nb_actifs
                    FROM apprenant a
                    JOIN section sec ON a.IDSection = sec.IDSection
                    WHERE a.statut = 'actif'
                    GROUP BY sec.IDOffre
                ) ac ON ac.IDOffre = o.IDOffre
                WHERE o.IDEts_Form = ?
                ORDER BY s.Nom
            ");
            $stmt->execute([$etabId]);
        }
        $offres = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Stats — role-scoped quick counts
        $dfepId = $user['iddfep'] ?? null;
        $statsStmt = null;

        if (in_array($role, ['admin', 'central'])) {
            $stats = \App\Services\CacheService::remember('admin_grades_stats', 600, function() {
                $statsStmt = $this->db->prepare("
                    SELECT
                        (SELECT COUNT(*) FROM apprenant WHERE statut = 'actif') as total_stagiaires,
                        (SELECT COUNT(*) FROM apprenant_section_semstre_module) as total_notes,
                        (
                            SELECT (SELECT COUNT(*) FROM apprenant_section_semstre WHERE MoyApr > 0 OR MoyAvr > 0) +
                                   (SELECT COUNT(*) FROM apprenant_fin WHERE MoyFinForm > 0 OR MoyGen > 0)
                        ) as resultats_valides,
                        (SELECT COUNT(*) FROM section_semestre WHERE NumPv IS NOT NULL AND NumPv != '' OR visaevaldir = 1 OR visaevaldfep = 1) as pvs_approuves
                ");
                $statsStmt->execute();
                return $statsStmt->fetch(PDO::FETCH_ASSOC);
            });
        } elseif ($role === 'dfep' && $dfepId) {
            $statsStmt = $this->db->prepare("
                SELECT
                    (SELECT COUNT(*) FROM apprenant a
                     JOIN section sec ON a.IDSection = sec.IDSection
                     WHERE sec.IDDFEP = :dfepId1) as total_stagiaires,
                     
                    (
                        (SELECT COUNT(*)
                         FROM apprenant_section_semstre_module assm
                         JOIN apprenant_section_semstre ass ON assm.IDapprenant_Section_semstre = ass.IDapprenant_Section_semstre
                         JOIN apprenant a ON ass.IDapprenant = a.IDapprenant
                         JOIN section sec ON a.IDSection = sec.IDSection
                         WHERE sec.IDDFEP = :dfepId2)
                        +
                        (SELECT COUNT(*)
                         FROM apprenant_section_semstre_module assm
                         JOIN apprenant_fin af ON assm.IDapprenant_Section_semstre = af.IDapprenant_Section_semstre
                         JOIN apprenant a ON af.IDapprenant = a.IDapprenant
                         JOIN section sec ON a.IDSection = sec.IDSection
                         WHERE sec.IDDFEP = :dfepId3)
                    ) as total_notes,
                    
                    (
                        (SELECT COUNT(*)
                         FROM apprenant_section_semstre ass
                         JOIN apprenant a ON ass.IDapprenant = a.IDapprenant
                         JOIN section sec ON a.IDSection = sec.IDSection
                         WHERE sec.IDDFEP = :dfepId4 AND (ass.MoyApr > 0 OR ass.MoyAvr > 0))
                        +
                        (SELECT COUNT(*)
                         FROM apprenant_fin af
                         JOIN apprenant a ON af.IDapprenant = a.IDapprenant
                         JOIN section sec ON a.IDSection = sec.IDSection
                         WHERE sec.IDDFEP = :dfepId5 AND (af.MoyFinForm > 0 OR af.MoyGen > 0))
                    ) as resultats_valides,
                    
                    (SELECT COUNT(*) FROM section_semestre ss
                     JOIN section sec ON ss.IDSection = sec.IDSection
                     WHERE sec.IDDFEP = :dfepId6 AND (ss.NumPv IS NOT NULL AND ss.NumPv != '' OR ss.visaevaldir = 1 OR ss.visaevaldfep = 1)) as pvs_approuves
            ");
            $statsStmt->execute([
                'dfepId1' => $dfepId,
                'dfepId2' => $dfepId,
                'dfepId3' => $dfepId,
                'dfepId4' => $dfepId,
                'dfepId5' => $dfepId,
                'dfepId6' => $dfepId,
            ]);
            $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
        } elseif ($etabId) {
            $statsStmt = $this->db->prepare("
                SELECT
                    (SELECT COUNT(*) FROM apprenant a
                     JOIN section sec ON a.IDSection = sec.IDSection
                     JOIN offre o ON sec.IDOffre = o.IDOffre
                     WHERE o.IDEts_Form = :etabId1) as total_stagiaires,
                     
                    (
                        (SELECT COUNT(*)
                         FROM apprenant_section_semstre_module assm
                         JOIN apprenant_section_semstre ass ON assm.IDapprenant_Section_semstre = ass.IDapprenant_Section_semstre
                         JOIN apprenant a ON ass.IDapprenant = a.IDapprenant
                         JOIN section sec ON a.IDSection = sec.IDSection
                         JOIN offre o ON sec.IDOffre = o.IDOffre
                         WHERE o.IDEts_Form = :etabId2)
                        +
                        (SELECT COUNT(*)
                         FROM apprenant_section_semstre_module assm
                         JOIN apprenant_fin af ON assm.IDapprenant_Section_semstre = af.IDapprenant_Section_semstre
                         JOIN apprenant a ON af.IDapprenant = a.IDapprenant
                         JOIN section sec ON a.IDSection = sec.IDSection
                         JOIN offre o ON sec.IDOffre = o.IDOffre
                         WHERE o.IDEts_Form = :etabId3)
                    ) as total_notes,
                    
                    (
                        (SELECT COUNT(*)
                         FROM apprenant_section_semstre ass
                         JOIN apprenant a ON ass.IDapprenant = a.IDapprenant
                         JOIN section sec ON a.IDSection = sec.IDSection
                         JOIN offre o ON sec.IDOffre = o.IDOffre
                         WHERE o.IDEts_Form = :etabId4 AND (ass.MoyApr > 0 OR ass.MoyAvr > 0))
                        +
                        (SELECT COUNT(*)
                         FROM apprenant_fin af
                         JOIN apprenant a ON af.IDapprenant = a.IDapprenant
                         JOIN section sec ON a.IDSection = sec.IDSection
                         JOIN offre o ON sec.IDOffre = o.IDOffre
                         WHERE o.IDEts_Form = :etabId5 AND (af.MoyFinForm > 0 OR af.MoyGen > 0))
                    ) as resultats_valides,
                    
                    (SELECT COUNT(*) FROM section_semestre ss
                     JOIN section sec ON ss.IDSection = sec.IDSection
                     JOIN offre o ON sec.IDOffre = o.IDOffre
                     WHERE o.IDEts_Form = :etabId6 AND (ss.NumPv IS NOT NULL AND ss.NumPv != '' OR ss.visaevaldir = 1 OR ss.visaevaldfep = 1)) as pvs_approuves
            ");
            $statsStmt->execute([
                'etabId1' => $etabId,
                'etabId2' => $etabId,
                'etabId3' => $etabId,
                'etabId4' => $etabId,
                'etabId5' => $etabId,
                'etabId6' => $etabId,
            ]);
        } else {
            $statsStmt = $this->db->prepare("
                SELECT 0 as total_stagiaires, 0 as total_notes, 0 as resultats_valides, 0 as pvs_approuves
            ");
            $statsStmt->execute();
        }

        // For admin path, $stats was already set by CacheService::remember()
        // For other paths, fetch from $statsStmt
        if (!isset($stats)) {
            $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
        }

        $this->render('admin/grades/index', [
            'title'  => 'نظام التنقيط - SGFEP / MFEP',
            'offres' => $offres,
            'stats'  => $stats,
        ]);
    }

    /**
     * Grade entry form for a specific offre/module
     */
    public function input(): void
    {
        $offreId   = (int)($_GET['offre_id']  ?? 0);
        $semestre  = (int)($_GET['semestre']  ?? 1);
        $matiereId = (int)($_GET['matiere_id'] ?? 0);

        if (!$offreId) {
            $this->redirect('/dashboard/grades');
            return;
        }

        // Offre metadata
        $stmt = $this->db->prepare("
            SELECT o.IDOffre as id, s.Nom as spec_ar, s.NbrSem as duree_semestres,
                   e.Nom as etab_nom, o.IDMode_formation as mode_formation
            FROM offre o
            JOIN specialite s ON o.IDSpecialite = s.IDSpecialite
            JOIN etablissement e ON o.IDEts_Form = e.IDetablissement
            WHERE o.IDOffre = ?
        ");
        $stmt->execute([$offreId]);
        $offre = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$offre) {
            $this->redirect('/dashboard/grades');
            return;
        }

        $user = $_SESSION['user'];
        $role = strtolower($user['role_code'] ?? '');

        // Fetch dynamic modules
        $stmtM = $this->db->prepare("
            SELECT DISTINCT 
                ssm.IDsection_semestre_Module as id,
                ssm.IDModule as module_id,
                ssm.NomMdl as libelle_ar,
                ssm.NomFrMdl as libelle_fr,
                ssm.coef as coefficient,
                CASE 
                    WHEN ssm.NomMdl LIKE '%تربص%' OR ssm.NomFrMdl LIKE '%stage%' OR ssm.NomMdl LIKE '%ميداني%' THEN 'stage_pratique'
                    WHEN ssm.NomMdl LIKE '%مذكرة%' OR ssm.NomFrMdl LIKE '%memoire%' OR ssm.NomMdl LIKE '%تخرج%' THEN 'memoire'
                    ELSE 'theorique'
                END as type_matiere
            FROM section_semestre_module ssm
            JOIN section_semestre ss ON ssm.IDSection_Semestre = ss.IDSection_Semestre
            JOIN section sec ON ss.IDSection = sec.IDSection
            WHERE sec.IDOffre = ? AND ss.NumSem = ?
              AND (? = 0 OR ssm.IDEncadrement = ?)
        ");
        $isTeacher = in_array($role, ['formateur', 'employee']);
        $teacherFilterId = $isTeacher ? (int)$user['id'] : 0;
        $stmtM->execute([$offreId, $semestre, $teacherFilterId, $teacherFilterId]);
        $matieres = $stmtM->fetchAll(PDO::FETCH_ASSOC);

        // Fallback placeholders if DB is empty
        if (empty($matieres)) {
            $matieres = [
                ['id' => 1, 'code' => 'M01', 'libelle_ar' => 'وحدة تعليمية 1', 'libelle_fr' => 'Module 1', 'coefficient' => 2, 'type_matiere' => 'theorique'],
                ['id' => 2, 'code' => 'M02', 'libelle_ar' => 'وحدة تعليمية 2', 'libelle_fr' => 'Module 2', 'coefficient' => 3, 'type_matiere' => 'theorique'],
                ['id' => 3, 'code' => 'M03', 'libelle_ar' => 'تطبيق مهني',     'libelle_fr' => 'Pratique',  'coefficient' => 4, 'type_matiere' => 'pratique'],
            ];
        }

        if (!$matiereId && count($matieres) > 0) {
            $matiereId = $matieres[0]['id'];
        }

        $matiere  = array_values(array_filter($matieres, fn($m) => $m['id'] == $matiereId))[0] ?? null;
        
        $employeurId = isset($_GET['employeur_id']) && $_GET['employeur_id'] !== '' ? (int)$_GET['employeur_id'] : null;
        
        // Retrieve trainees
        $students = $this->service->getTraineesWithGrades($offreId, $matiereId, $employeurId);

        // Fetch active employers for apprenticeship offers
        $employeurs = [];
        if ((int)$offre['mode_formation'] === 10) {
            $stmtE = $this->db->prepare("
                SELECT DISTINCT e.IDEmployeur as id, e.Nom as nom, e.NomFr as nom_fr
                FROM employeur e
                JOIN apprenant a ON a.IDEmployeur = e.IDEmployeur
                JOIN section s ON a.IDSection = s.IDSection
                WHERE s.IDOffre = ? AND a.statut = 'actif'
                ORDER BY e.Nom
            ");
            $stmtE->execute([$offreId]);
            $employeurs = $stmtE->fetchAll(PDO::FETCH_ASSOC);
        }

        // Read dynamic configuration
        $config = \App\Helpers\GradingConfigHelper::read();

        // Check if grade entry is locked (outside Time Window)
        $is_locked = false;
        if ($isTeacher) {
            $now = date('Y-m-d');
            $start = $config['workflow']['grading_start_date'] ?? '';
            $end = $config['workflow']['grading_end_date'] ?? '';
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
                $is_locked = true;
            }
        }

        $this->render('admin/grades/input', [
            'title'    => 'رصد نقاط المتربصين - السداسي ' . $semestre,
            'offre'    => $offre,
            'config'   => $config,
            'matieres' => $matieres,
            'matiere'  => $matiere,
            'students' => $students,
            'semestre' => $semestre,
            'is_locked' => $is_locked,
            'employeurs' => $employeurs,
            'employeur_id' => $employeurId
        ]);
    }

    /**
     * Save submitted grade batch
     */
    public function store(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/dashboard/grades');
            return;
        }

        // CSRF
        $token = $_POST['csrf_token'] ?? '';
        if (empty($token) || $token !== ($_SESSION['csrf_token'] ?? '')) {
            $_SESSION['flash_error'] = 'رمز التحقق من الأمن غير صالح. يرجى المحاولة مجدداً.';
            $this->redirect('/dashboard/grades');
            return;
        }

        $matiereId = (int)($_POST['matiere_id'] ?? 0);
        $offreId   = (int)($_POST['offre_id']   ?? 0);
        $semestre  = (int)($_POST['semestre']   ?? 1);
        $grades    = $_POST['grades'] ?? [];

        try {
            $count = $this->service->saveGrades($_SESSION['user'], $offreId, $matiereId, $semestre, $grades);
            $_SESSION['flash_success'] = 'تم حفظ نقاط ' . $count . ' متربص بنجاح.';
        } catch (\Exception $e) {
            $_SESSION['flash_error'] = 'خطأ في حفظ النقاط: ' . $e->getMessage();
        }

        $this->redirect("/dashboard/grades/input?offre_id={$offreId}&semestre={$semestre}&matiere_id={$matiereId}");
    }

    public function transcript(string|int $id): void
    {
        $this->redirect('/dashboard/grades');
    }

    public function deliberation(): void
    {
        $offreId = (int)($_GET['offre_id'] ?? 0);
        $semestre = (int)($_GET['semestre'] ?? 1);

        if (!$offreId) {
            $this->redirect('/dashboard/grades');
            return;
        }

        // Fetch offer
        $stmt = $this->db->prepare("
            SELECT o.IDOffre as id, s.Nom as spec_ar, s.NomFr as spec_fr, s.CodeSpec as spec_code,
                   s.NbrSem as duree_semestres,
                   CASE
                       WHEN s.NbrSem >= 5 THEN 'BTS'
                       WHEN s.NbrSem = 4 THEN 'BTS'
                       WHEN s.NbrSem = 3 THEN 'TS'
                       WHEN s.NbrSem = 2 THEN 'CMP'
                       WHEN s.NbrSem = 1 THEN 'Qualifiant'
                       ELSE 'CAP'
                   END as diplome_vise,
                   e.Nom as etab_ar, e.NomFr as etab_fr,
                   o.IDMode_formation as mode_formation,
                   o.DateD as date_debut, o.DateF as date_fin
            FROM offre o
            JOIN specialite s ON o.IDSpecialite = s.IDSpecialite
            JOIN etablissement e ON o.IDEts_Form = e.IDetablissement
            WHERE o.IDOffre = ?
        ");
        $stmt->execute([$offreId]);
        $offre = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$offre) {
            $this->redirect('/dashboard/grades');
            return;
        }

        // Fetch modules
        $stmtM = $this->db->prepare("
            SELECT DISTINCT 
                ssm.IDsection_semestre_Module as id,
                ssm.IDModule as module_id,
                ssm.NomMdl as libelle_ar,
                ssm.NomFrMdl as libelle_fr,
                ssm.coef as coefficient,
                ssm.NomMdl as code
            FROM section_semestre_module ssm
            JOIN section_semestre ss ON ssm.IDSection_Semestre = ss.IDSection_Semestre
            JOIN section sec ON ss.IDSection = sec.IDSection
            WHERE sec.IDOffre = ? AND ss.NumSem = ?
        ");
        $stmtM->execute([$offreId, $semestre]);
        $matieres = $stmtM->fetchAll(PDO::FETCH_ASSOC);

        // Fetch trainees
        $stmtS = $this->db->prepare("
            SELECT a.IDapprenant as id, a.Nccp as matricule, 
                   c.Nom as nom_ar, c.Prenom as prenom_ar, c.Civ as sexe,
                   COALESCE(ass.IDapprenant_Section_semstre, af.IDapprenant_Section_semstre) as ass_id,
                   COALESCE(ass.NoteStage, 0) as note_stage,
                   COALESCE(ass.NoteMemoire, 0) as note_memoire,
                   COALESCE(ass.NoteSoutenance, 0) as note_soutenance
            FROM apprenant a
            LEFT JOIN candidat c ON a.IDCandidat = c.IDCandidat
            JOIN section s ON a.IDSection = s.IDSection
            JOIN section_semestre ss ON s.IDSection = ss.IDSection
            LEFT JOIN apprenant_section_semstre ass ON a.IDapprenant = ass.IDapprenant AND ass.IDSection_Semestre = ss.IDSection_Semestre
            LEFT JOIN apprenant_fin af ON a.IDapprenant = af.IDapprenant AND af.IDSection_Semestre = ss.IDSection_Semestre
            WHERE s.IDOffre = ? AND ss.NumSem = ? AND a.statut = 'actif'
            ORDER BY c.Nom, c.Prenom
        ");
        $stmtS->execute([$offreId, $semestre]);
        $trainees = $stmtS->fetchAll(PDO::FETCH_ASSOC);

        $config = \App\Helpers\GradingConfigHelper::read();
        $gradingService = new \App\Domains\Academic\Services\GradingSystemService();

        $rows = [];
        $nbAdmis = 0;
        $nbAjournes = 0;
        $nbExclus = 0;
        $rang = 1;

        foreach ($trainees as $stg) {
            $assId = (int)($stg['ass_id'] ?? 0);
            $marks = [];
            $modulesForGpa = [];

            $gradesList = [];
            if ($assId > 0) {
                $stmtG = $this->db->prepare("
                    SELECT IDsection_semestre_Module as ssm_id, NoteC1 as cc1, NoteC2 as cc2, NoteCs as exam, NoteR as rattrapage
                    FROM apprenant_section_semstre_module
                    WHERE IDapprenant_Section_semstre = ?
                ");
                $stmtG->execute([$assId]);
                $gradesList = $stmtG->fetchAll(PDO::FETCH_ASSOC);
            }
            $gradesBySsm = [];
            foreach ($gradesList as $gl) {
                $gradesBySsm[$gl['ssm_id']] = $gl;
            }

            $hasElimination = false;
            foreach ($matieres as $m) {
                $g = $gradesBySsm[$m['id']] ?? null;
                $typeM = (strpos(strtolower($m['libelle_ar']), 'stage') !== false || strpos(strtolower($m['libelle_fr']), 'stage') !== false) ? 'stage_pratique' :
                         ((strpos(strtolower($m['libelle_ar']), 'memoire') !== false || strpos(strtolower($m['libelle_fr']), 'memoire') !== false) ? 'memoire' : 'theorique');

                $calc = $gradingService->calculateModuleGrade([
                    'type_matiere' => $typeM,
                    'cc1' => $g['cc1'] ?? null,
                    'cc2' => $g['cc2'] ?? null,
                    'exam' => $g['exam'] ?? null,
                    'rattrapage' => $g['rattrapage'] ?? null,
                    'stage' => $stg['note_stage'] ?? null,
                    'memoire' => $stg['note_memoire'] ?? null,
                    'soutenance' => $stg['note_soutenance'] ?? null,
                ], $config, (int)$offre['mode_formation']);

                $marks[$m['id']] = $calc['moy_apr'];
                if ($calc['is_eliminated']) {
                    $hasElimination = true;
                }

                $modulesForGpa[] = [
                    'coefficient' => $m['coefficient'],
                    'note_avr' => $calc['moy_avr'],
                    'note_apr' => $calc['moy_apr']
                ];
            }

            $semCalc = $gradingService->calculateSemesterGpa($modulesForGpa, $stg['note_stage'], $offre['mode_formation'], $config);
            $gpa = $semCalc['gpa_apr'];

            if ($hasElimination) {
                $decision = 'مقصى';
                $nbExclus++;
            } elseif ($semCalc['is_admis']) {
                $decision = 'مقبول';
                $nbAdmis++;
            } else {
                $decision = 'مؤجل';
                $nbAjournes++;
            }

            $fullName = trim(($stg['nom_ar'] ?? '') . ' ' . ($stg['prenom_ar'] ?? ''));
            if (empty($fullName)) {
                $fullName = 'متربص #' . $stg['id'];
            }
            $rows[] = [
                'rang' => $rang++,
                'matricule' => $stg['matricule'],
                'nom_ar' => $fullName,
                'sexe' => (in_array(strtolower(trim($stg['sexe'] ?? '')), ['m', 'ذكر', '1'])) ? 'ذكر' : 'أنثى',
                'marks' => $marks,
                'average' => $gpa,
                'decision' => $decision
            ];
        }

        $total = count($rows);
        $txReuss = $total > 0 ? round(($nbAdmis / $total) * 100, 2) : 0;

        $this->render('admin/grades/deliberations', [
            'offre' => $offre,
            'config' => $config,
            'semestre' => $semestre,
            'matieres' => $matieres,
            'rows' => $rows,
            'nbAdmis' => $nbAdmis,
            'nbAjournes' => $nbAjournes,
            'nbExclus' => $nbExclus,
            'total' => $total,
            'txReuss' => $txReuss
        ]);
    }

    public function progress(): void
    {
        $user = $_SESSION['user'];
        $role = strtolower($user['role_code'] ?? '');
        $etabId = $user['etablissement_id'] ?? null;
        $dfepId = $user['iddfep'] ?? null;

        $where = [];
        $params = [];

        if (in_array($role, ['admin', 'central'])) {
            // No restriction
        } elseif ($role === 'dfep' && $dfepId) {
            $where[] = "e.IDDFEP = ?";
            $params[] = $dfepId;
        } elseif ($etabId) {
            $where[] = "o.IDEts_Form = ?";
            $params[] = $etabId;
        }

        $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

        $sql = "
            SELECT 
                ssm.IDsection_semestre_Module as ssm_id,
                ssm.NomMdl as module_nom,
                sec.Nom as section_nom,
                ss.NumSem as semestre,
                o.IDOffre as offre_id,
                sp.Nom as specialite_nom,
                e.Nom as etab_nom,
                enc.Nom as teacher_nom,
                enc.Prenom as teacher_prenom,
                (SELECT COUNT(*) FROM apprenant a WHERE a.IDSection = sec.IDSection AND a.statut = 'actif') as total_students,
                ((SELECT COUNT(DISTINCT a.IDapprenant) 
                  FROM apprenant a
                  JOIN apprenant_section_semstre ass 
                         ON ass.IDapprenant = a.IDapprenant 
                        AND ass.IDSection_Semestre = ss.IDSection_Semestre
                  JOIN apprenant_section_semstre_module assm 
                         ON assm.IDapprenant_Section_semstre = ass.IDapprenant_Section_semstre
                  WHERE a.IDSection = sec.IDSection 
                    AND a.statut = 'actif'
                    AND assm.IDsection_semestre_Module = ssm.IDsection_semestre_Module 
                    AND (assm.NoteCs IS NOT NULL OR assm.NoteC1 IS NOT NULL OR assm.NoteC2 IS NOT NULL OR assm.MoyApr > 0))
                 +
                 (SELECT COUNT(DISTINCT a.IDapprenant) 
                  FROM apprenant a
                  JOIN apprenant_fin af 
                         ON af.IDapprenant = a.IDapprenant 
                        AND af.IDSection_Semestre = ss.IDSection_Semestre
                  JOIN apprenant_section_semstre_module assm 
                         ON assm.IDapprenant_Section_semstre = af.IDapprenant_Section_semstre
                  WHERE a.IDSection = sec.IDSection 
                    AND a.statut = 'actif'
                    AND assm.IDsection_semestre_Module = ssm.IDsection_semestre_Module 
                    AND (assm.NoteCs IS NOT NULL OR assm.NoteC1 IS NOT NULL OR assm.NoteC2 IS NOT NULL OR assm.MoyApr > 0))) as graded_students
            FROM section_semestre_module ssm
            JOIN section_semestre ss ON ssm.IDSection_Semestre = ss.IDSection_Semestre
            JOIN section sec ON ss.IDSection = sec.IDSection
            JOIN offre o ON sec.IDOffre = o.IDOffre
            JOIN specialite sp ON o.IDSpecialite = sp.IDSpecialite
            JOIN etablissement e ON o.IDEts_Form = e.IDetablissement
            LEFT JOIN encadrement enc ON ssm.IDEncadrement = enc.IDEncadrement
            {$whereClause}
            ORDER BY e.Nom, sp.Nom, sec.Nom, ssm.NomMdl
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $progressData = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->render('admin/grades/progress', [
            'title' => 'متابعة تقدم رصد علامات الامتحانات والتقييمات',
            'progressData' => $progressData
        ]);
    }

    public function gradingControl(): void
    {
        $user = $_SESSION['user'];
        $role = strtolower($user['role_code'] ?? '');
        if ($role !== 'admin') {
            $this->redirect('/dashboard/grades');
            return;
        }

        $config = \App\Helpers\GradingConfigHelper::read();
        
        $stmt = $this->db->query("SELECT IDetablissement as id, Nom as nom FROM etablissement ORDER BY Nom");
        $establishments = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $trainingModes = [
            1 => 'حضوري أولي',
            2 => 'التكوين المهني المتواصل',
            3 => 'الدروس المسائية',
            4 => 'في المؤسسات العقابية',
            5 => 'تكوين المرأة الماكثة في البيت',
            6 => 'التكوين التعاقدي',
            7 => 'التكوين عن طريق المعابر',
            8 => 'التعليم المهني',
            9 => 'تأهيلي أولي',
            10 => 'تكوين عن طريق التمهين',
            11 => 'محو الأمية - تأهيل مهني',
            12 => 'المؤسسات الخاصة المعتمدة',
            13 => 'فرع منتدب',
            14 => 'فرع في الوسط الريفي',
            15 => 'المعاقين حركياً',
            16 => 'المعاقين سمعياً',
            17 => 'المعاقين بصرياً',
            18 => 'عن بعد تكوين تعاقدي',
            19 => 'التكوين المهني لإعادة التكييف',
            20 => 'التكوين حسب الطلب',
            21 => 'عن بعد',
            22 => 'تكوين المستفيدين من منحة البطالة',
            23 => 'المترشحين الأحرار',
        ];

        $selectedModeId = isset($_GET['mode_id']) ? (int)$_GET['mode_id'] : 1;
        if (!isset($trainingModes[$selectedModeId])) {
            $selectedModeId = 1;
        }

        $this->render('admin/grades/grading_control', [
            'title' => 'لوحة تحكم إعدادات ومحددات نظام التقييم والمعدلات',
            'config' => $config,
            'establishments' => $establishments,
            'trainingModes' => $trainingModes,
            'selectedModeId' => $selectedModeId
        ]);
    }

    public function saveGradingControl(): void
    {
        $user = $_SESSION['user'];
        $role = strtolower($user['role_code'] ?? '');
        if ($role !== 'admin') {
            $this->redirect('/dashboard/grades');
            return;
        }

        $token = $_POST['csrf_token'] ?? '';
        if (empty($token) || $token !== ($_SESSION['csrf_token'] ?? '')) {
            $_SESSION['flash_error'] = 'رمز التحقق من الأمن غير صالح.';
            $this->redirect('/dashboard/grades/control');
            return;
        }

        $config = \App\Helpers\GradingConfigHelper::read();

        $modeId = isset($_POST['mode_id']) ? (int)$_POST['mode_id'] : 1;
        if (!isset($config['modes'])) {
            $config['modes'] = [];
        }
        if (!isset($config['modes'][$modeId])) {
            $config['modes'][$modeId] = [];
        }

        // Save mode-specific configurations
        $config['modes'][$modeId]['continuous_assessment_weight'] = isset($_POST['continuous_assessment_weight']) ? (float)$_POST['continuous_assessment_weight'] : ($config['modes'][$modeId]['continuous_assessment_weight'] ?? $config['module_grade']['continuous_assessment_weight'] ?? 0.4);
        $config['modes'][$modeId]['quiz_weight'] = isset($_POST['quiz_weight']) ? (float)$_POST['quiz_weight'] : ($config['modes'][$modeId]['quiz_weight'] ?? $config['module_grade']['quiz_weight'] ?? 0.4);
        $config['modes'][$modeId]['exam_weight'] = isset($_POST['exam_weight']) ? (float)$_POST['exam_weight'] : ($config['modes'][$modeId]['exam_weight'] ?? $config['module_grade']['exam_weight'] ?? 0.6);
        $config['modes'][$modeId]['divisor'] = isset($_POST['divisor']) ? (float)$_POST['divisor'] : ($config['modes'][$modeId]['divisor'] ?? $config['module_grade']['divisor'] ?? 1.0);
        $config['modes'][$modeId]['passing_threshold'] = isset($_POST['passing_threshold']) ? (float)$_POST['passing_threshold'] : ($config['modes'][$modeId]['passing_threshold'] ?? $config['remedial']['passing_threshold'] ?? 10.0);
        $config['modes'][$modeId]['passing_gpa_threshold'] = isset($_POST['passing_gpa_threshold']) ? (float)$_POST['passing_gpa_threshold'] : ($config['modes'][$modeId]['passing_gpa_threshold'] ?? $config['semester']['passing_gpa_threshold'] ?? 10.0);
        $config['modes'][$modeId]['elimination_threshold'] = isset($_POST['elimination_threshold']) ? (float)$_POST['elimination_threshold'] : ($config['modes'][$modeId]['elimination_threshold'] ?? $config['semester']['elimination_threshold'] ?? 5.0);
        $config['modes'][$modeId]['company_coefficient'] = isset($_POST['company_coefficient']) ? (float)$_POST['company_coefficient'] : ($config['modes'][$modeId]['company_coefficient'] ?? $config['semester']['apprenticeship']['company_coefficient'] ?? 4.0);

        // Distance learning mode specific configurations
        $config['modes'][$modeId]['dl_platform_activity'] = isset($_POST['dl_platform_activity']) ? (float)$_POST['dl_platform_activity'] : ($config['modes'][$modeId]['dl_platform_activity'] ?? $config['distance_learning']['weights']['platform_activity'] ?? 0.3);
        $config['modes'][$modeId]['dl_assignments'] = isset($_POST['dl_assignments']) ? (float)$_POST['dl_assignments'] : ($config['modes'][$modeId]['dl_assignments'] ?? $config['distance_learning']['weights']['assignments'] ?? 0.3);
        $config['modes'][$modeId]['dl_written_exam'] = isset($_POST['dl_written_exam']) ? (float)$_POST['dl_written_exam'] : ($config['modes'][$modeId]['dl_written_exam'] ?? $config['distance_learning']['weights']['written_exam'] ?? 0.4);

        $config['workflow']['grading_start_date'] = $_POST['grading_start_date'] ?? $config['workflow']['grading_start_date'];
        $config['workflow']['grading_end_date'] = $_POST['grading_end_date'] ?? $config['workflow']['grading_end_date'];
        $config['workflow']['final_validation_active'] = isset($_POST['final_validation_active']) && $_POST['final_validation_active'] === '1';

        $allowed = $_POST['remedial_allowed_establishments'] ?? [];
        if (!is_array($allowed)) {
            $allowed = [];
        }
        $config['workflow']['remedial_allowed_establishments'] = array_map('intval', $allowed);

        if (\App\Helpers\GradingConfigHelper::write($config)) {
            $_SESSION['flash_success'] = 'تم حفظ إعدادات نظام رصد النقاط بنجاح.';
        } else {
            $_SESSION['flash_error'] = 'حدث خطأ أثناء كتابة ملف الإعدادات.';
        }

        $this->redirect('/dashboard/grades/control');
    }

    /**
     * AJAX endpoint to fetch active employers for an offer
     */
    public function getEmployeurs(): void
    {
        $offreId = (int)($_GET['offre_id'] ?? 0);
        
        $stmtE = $this->db->prepare("
            SELECT DISTINCT e.IDEmployeur as id, e.Nom as nom, e.NomFr as nom_fr
            FROM employeur e
            JOIN apprenant a ON a.IDEmployeur = e.IDEmployeur
            JOIN section s ON a.IDSection = s.IDSection
            WHERE s.IDOffre = ? AND a.statut = 'actif'
            ORDER BY e.Nom
        ");
        $stmtE->execute([$offreId]);
        $employeurs = $stmtE->fetchAll(PDO::FETCH_ASSOC);
        
        header('Content-Type: application/json');
        echo json_encode($employeurs);
        exit;
    }
}
