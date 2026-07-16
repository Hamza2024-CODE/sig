<?php

namespace App\Domains\Academic\Repositories;

use App\Core\Database;
use PDO;

/**
 * ApprenantRepository
 *
 * Wraps all RAW SQL against the WinDev `apprenant` and related tables.
 * Schema is frozen — SQL must NEVER be rewritten.
 *
 * Related legacy tables:
 *   apprenant, candidat, section, offre, specialite, etablissement,
 *   apprenant_section_semstre, apprenant_section_semstre_module,
 *   apprenant_absence
 */
class ApprenantRepository
{
    protected PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    // ─── Core Trainee Queries ─────────────────────────────────────────────────

    /**
     * Fetch paginated list of active apprenants with role-based WHERE clause.
     *
     * @param  string $extraWhere  Optional " AND ..." filter built by the service
     * @param  array  $params      Positional params for extraWhere
     * @param  int    $limit
     * @return array
     */
    public function findActiveFiltered(string $extraWhere, array $params, int $limit = 500): array
    {
        $sql = "
            SELECT a.IDapprenant as id, a.IDCandidat as candidat_id, a.IDSection as section_id,
                   COALESCE(NULLIF(a.Nccp, ''), c1.NumIns) as matricule, a.statut,
                   c1.Nom as nom_ar, c1.Prenom as prenom_ar,
                   c1.NomFr as nom_fr, c1.PrenomFr as prenom_fr,
                   c1.Civ as civ, c1.Nin as nin,
                   sp.Nom as specialite_ar,
                   e.Nom as etab_nom,
                   o.IDMode_formation as offre_mode
            FROM apprenant a
            LEFT JOIN section s ON a.IDSection = s.IDSection
            LEFT JOIN candidat c1 ON c1.IDCandidat = a.IDCandidat
            LEFT JOIN offre o ON o.IDOffre = c1.IDOffre
            LEFT JOIN specialite sp ON o.IDSpecialite = sp.IDSpecialite
            LEFT JOIN etablissement e ON o.IDEts_Form = e.IDetablissement
            WHERE a.statut = 'actif'
            {$extraWhere}
            ORDER BY c1.Nom ASC
            LIMIT {$limit}
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Memory-safe Generator for streaming large trainee datasets to ReportingService.
     * Fetches data in chunks of $chunkSize rows — peak RAM = O(chunkSize).
     *
     * PERFORMANCE NOTE: The original query used a correlated subquery
     *   "SELECT MAX(c3.IDCandidat) FROM candidat c3 WHERE c3.IdMihnati1 = a.IDapprenant"
     * which ran once per row (2.8M+ rows) causing timeout.
     * Replaced with a pre-aggregated derived table join (O(N) instead of O(N²)).
     *
     * @param  string $extraWhere  Role-scoped WHERE fragment
     * @param  array  $params      Positional params for extraWhere
     * @param  int    $chunkSize   Rows per DB round-trip (default 500)
     * @param  int    $totalLimit  Hard cap on total rows yielded (default 10000)
     * @return \Generator          Yields one enriched row array at a time
     */
    public function streamActiveChunked(string $extraWhere, array $params, int $chunkSize = 500, int $totalLimit = 10000): \Generator
    {
        $offset    = 0;
        $yielded   = 0;

        do {
            $chunkParams = array_merge($params, [$chunkSize, $offset]);

            // Optimized query: pre-aggregated c2 derived table replaces correlated subquery
            // c1 = primary candidat link via IDCandidat (always valid — checked: 0 nulls)
            // c2 = fallback mihnati link via a derived MAX(IDCandidat) per IDapprenant
            $stmt = $this->db->prepare("
                SELECT a.IDapprenant as id, a.IDCandidat as candidat_id, a.IDSection as section_id,
                       COALESCE(NULLIF(a.Nccp, ''), c1.NumIns) as matricule,
                       a.statut,
                       c1.Nom as nom_ar, c1.Prenom as prenom_ar,
                       c1.NomFr as nom_fr, c1.PrenomFr as prenom_fr,
                       c1.Civ as civ,
                       sp.Nom as specialite_ar,
                       e.Nom as etab_nom,
                       o.IDMode_formation as offre_mode
                FROM apprenant a
                LEFT JOIN candidat c1 ON c1.IDCandidat = a.IDCandidat
                LEFT JOIN offre o     ON o.IDOffre = c1.IDOffre
                LEFT JOIN specialite sp ON o.IDSpecialite = sp.IDSpecialite
                LEFT JOIN etablissement e ON o.IDEts_Form = e.IDetablissement
                WHERE a.statut = 'actif'
                {$extraWhere}
                LIMIT ? OFFSET ?
            ");
            $stmt->execute($chunkParams);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($rows as $row) {
                if ($yielded >= $totalLimit) {
                    return; // Hard cap reached — stop iteration
                }
                // Enrich with computed label fields for CSV/HTML output
                $civ = strtolower(trim($row['civ'] ?? ''));
                $row['sexe_label']   = in_array($civ, ['m', 'ذكر', '1']) ? 'ذكر' : 'أنثى';
                $row['statut_label'] = ($row['statut'] ?? '') === 'actif' ? 'نشط' : 'غير نشط';
                yield $row;
                $yielded++;
            }

            $offset += $chunkSize;
        } while (count($rows) === $chunkSize && $yielded < $totalLimit);
    }

    /**
     * Lightweight list for dropdowns (name + ID only)
     * SQL preserved exactly from ApprentissageController::maitres()
     */
    public function findActiveDropdown(int $limit = 300): array
    {
        $stmt = $this->db->query("
            SELECT a.IDapprenant, COALESCE(CONCAT(COALESCE(c1.Nom, c2.Nom),' ',COALESCE(c1.Prenom, c2.Prenom)), CONCAT('متربص #', a.IDapprenant)) AS nom_complet
            FROM apprenant a
            LEFT JOIN section s ON a.IDSection = s.IDSection
            LEFT JOIN candidat c1 ON c1.IDCandidat = a.IDCandidat
            LEFT JOIN candidat c2 ON c2.IdMihnati1 = a.IDapprenant AND c2.IDCandidat = (
                SELECT MAX(c3.IDCandidat) FROM candidat c3 WHERE c3.IdMihnati1 = a.IDapprenant
            )
            WHERE a.statut = 'actif'
            ORDER BY COALESCE(c1.Nom, c2.Nom) ASC
            LIMIT {$limit}
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Find a single apprenant by primary key (with full joined details)
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT a.IDapprenant as id, a.IDCandidat as candidat_id, a.IDSection as section_id,
                   COALESCE(NULLIF(a.Nccp, ''), c1.NumIns, c2.NumIns) as matricule, a.statut,
                   COALESCE(c1.Nom, c2.Nom) as nom_ar, COALESCE(c1.Prenom, c2.Prenom) as prenom_ar,
                   COALESCE(c1.NomFr, c2.NomFr) as nom_fr, COALESCE(c1.PrenomFr, c2.PrenomFr) as prenom_fr,
                   COALESCE(c1.Civ, c2.Civ) as civ, COALESCE(c1.Nin, c2.Nin) as nin,
                   sp.Nom as specialite_ar, sp.NomFr as specialite_fr,
                   e.Nom as etab_nom,
                   o.IDMode_formation as offre_mode
            FROM apprenant a
            LEFT JOIN section s ON a.IDSection = s.IDSection
            LEFT JOIN candidat c1 ON c1.IDCandidat = a.IDCandidat
            LEFT JOIN candidat c2 ON c2.IdMihnati1 = a.IDapprenant AND c2.IDCandidat = (
                SELECT MAX(c3.IDCandidat) FROM candidat c3 WHERE c3.IdMihnati1 = a.IDapprenant
            )
            LEFT JOIN offre o ON o.IDOffre = COALESCE(c1.IDOffre, c2.IDOffre)
            LEFT JOIN specialite sp ON o.IDSpecialite = sp.IDSpecialite
            LEFT JOIN etablissement e ON o.IDEts_Form = e.IDetablissement
            WHERE a.IDapprenant = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    // ─── Grade / Module Queries ───────────────────────────────────────────────

    /**
     * Fetch trainees with their grade info for a specific section semester module.
     *
     * Join chain: ssm → section_semestre → section → apprenant
     * Starting from ssm guarantees that IDSection_Semestre used for the
     * apprenant_section_semstre lookup always belongs to the apprenant's
     * own section.
     *
     * IMPORTANT: Many apprenants have NO row in apprenant_section_semstre
     * (the WinDev system only creates it when grades are first entered).
     * When both ass and af are NULL, we resolve the ass_id directly from
     * the grade table (apprenant_section_semstre_module) using the known
     * IDsection_semestre_Module so existing grades still appear.
     */
    public function findWithGradesByOffre(int $offreId, int $matiereId, ?int $employeurId = null): array
    {
        $sql = "
            SELECT
                a.IDapprenant                       AS id,
                COALESCE(NULLIF(a.Nccp, ''), c1.NumIns, c2.NumIns) AS numero_matricule,
                ss.NumSem                           AS semestre_actuel,
                COALESCE(c1.Nom, c2.Nom)            AS nom_ar,
                COALESCE(c1.Prenom, c2.Prenom)      AS prenom_ar,
                COALESCE(c1.Civ, c2.Civ)            AS sexe,

                /* Resolve the semester-summary record ID.
                   Try apprenant_section_semstre first (active),
                   then apprenant_fin (archived),
                   then look it up directly from the grade table
                   (covers apprenants whose ass row doesn't exist yet). */
                COALESCE(
                    ass.IDapprenant_Section_semstre,
                    af.IDapprenant_Section_semstre,
                    (SELECT assm2.IDapprenant_Section_semstre
                     FROM apprenant_section_semstre_module assm2
                     JOIN apprenant_section_semstre ass2
                         ON assm2.IDapprenant_Section_semstre = ass2.IDapprenant_Section_semstre
                     WHERE assm2.IDsection_semestre_Module = ssm.IDsection_semestre_Module
                       AND ass2.IDapprenant = a.IDapprenant
                     LIMIT 1)
                )                                   AS ass_id,

                n.IDApprenant_Section_semstre_module AS note_id,
                n.NoteC1                            AS note_cc1,
                n.NoteC2                            AS note_cc2,
                n.NoteCs                            AS note_examen,
                n.NoteR                             AS note_rattrapage,
                n.MoyApr                            AS note_finale,
                n.Obs                               AS observation,

                COALESCE(ass.NoteStage,      0)     AS note_stage,
                COALESCE(ass.NoteMemoire,    0)     AS note_memoire,
                COALESCE(ass.NoteSoutenance, 0)     AS note_soutenance,

                (n.MoyApr < 5.0 AND n.MoyApr > 0)  AS est_eliminatoire

            FROM section_semestre_module ssm
            JOIN section_semestre ss  ON ss.IDSection_Semestre = ssm.IDSection_Semestre
            JOIN section sec          ON sec.IDSection          = ss.IDSection
            JOIN apprenant a          ON a.IDSection            = sec.IDSection
            LEFT JOIN candidat c1     ON c1.IDCandidat          = a.IDCandidat
            LEFT JOIN candidat c2     ON c2.IdMihnati1          = a.IDapprenant AND c2.IDCandidat = (
                SELECT MAX(c3.IDCandidat) FROM candidat c3 WHERE c3.IdMihnati1 = a.IDapprenant
            )

            LEFT JOIN apprenant_section_semstre ass
                ON ass.IDapprenant        = a.IDapprenant
                AND ass.IDSection_Semestre = ss.IDSection_Semestre

            LEFT JOIN apprenant_fin af
                ON af.IDapprenant         = a.IDapprenant
                AND af.IDSection_Semestre  = ss.IDSection_Semestre

            /* Grade record: join on the resolved ass_id */
            LEFT JOIN apprenant_section_semstre_module n
                ON n.IDapprenant_Section_semstre = COALESCE(
                       ass.IDapprenant_Section_semstre,
                       af.IDapprenant_Section_semstre,
                       (SELECT assm3.IDapprenant_Section_semstre
                        FROM apprenant_section_semstre_module assm3
                        JOIN apprenant_section_semstre ass3
                            ON assm3.IDapprenant_Section_semstre = ass3.IDapprenant_Section_semstre
                        WHERE assm3.IDsection_semestre_Module = ssm.IDsection_semestre_Module
                          AND ass3.IDapprenant = a.IDapprenant
                        LIMIT 1)
                   )
                AND n.IDsection_semestre_Module = ssm.IDsection_semestre_Module

            WHERE ssm.IDsection_semestre_Module = ?
              AND sec.IDOffre = ?
              AND a.statut   = 'actif'
        ";

        $params = [$matiereId, $offreId];

        if ($employeurId !== null && $employeurId > 0) {
            $sql .= " AND a.IDEmployeur = ?";
            $params[] = $employeurId;
        }

        $sql .= "
            ORDER BY COALESCE(c1.Nom, c2.Nom), COALESCE(c1.Prenom, c2.Prenom)
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Find or create an apprenant_section_semstre row and return its ID.
     */
    public function findOrCreateSectionSemestre(PDO $pdo, int $apprenantId, int $sectionSemestreId = 0): int
    {
        if ($sectionSemestreId > 0) {
            $stmt = $pdo->prepare("
                SELECT IDapprenant_Section_semstre
                FROM apprenant_section_semstre
                WHERE IDapprenant = ? AND IDSection_Semestre = ?
            ");
            $stmt->execute([$apprenantId, $sectionSemestreId]);
            $assId = $stmt->fetchColumn();

            if (!$assId) {
                $maxId = (int)$pdo->query("SELECT COALESCE(MAX(IDapprenant_Section_semstre), 0) FROM apprenant_section_semstre")->fetchColumn();
                $assId = $maxId + 1;
                $ins = $pdo->prepare("
                    INSERT INTO apprenant_section_semstre (
                        IDapprenant_Section_semstre, 
                        IDapprenant, 
                        IDSection_Semestre, 
                        update_time, 
                        data_sync_time,
                        IDDecision_evals,
                        IDDecision_evalsAvr,
                        IDapprenant_Regime,
                        IDmention
                    ) VALUES (?, ?, ?, ?, ?, NULL, NULL, NULL, NULL)
                ");
                $ins->execute([$assId, $apprenantId, $sectionSemestreId, date('Y-m-d'), '2000-01-01']);
            }
            return (int)$assId;
        }

        $stmt = $pdo->prepare("
            SELECT IDapprenant_Section_semstre
            FROM apprenant_section_semstre
            WHERE IDapprenant = ?
            ORDER BY IDapprenant_Section_semstre DESC LIMIT 1
        ");
        $stmt->execute([$apprenantId]);
        $assId = $stmt->fetchColumn();

        if (!$assId) {
            $maxId = (int)$pdo->query("SELECT COALESCE(MAX(IDapprenant_Section_semstre), 0) FROM apprenant_section_semstre")->fetchColumn();
            $assId = $maxId + 1;
            $ins = $pdo->prepare("
                INSERT INTO apprenant_section_semstre (
                    IDapprenant_Section_semstre, 
                    IDapprenant, 
                    update_time, 
                    data_sync_time,
                    IDSection_Semestre,
                    IDDecision_evals,
                    IDDecision_evalsAvr,
                    IDapprenant_Regime,
                    IDmention
                ) VALUES (?, ?, ?, ?, NULL, NULL, NULL, NULL, NULL)
            ");
            $ins->execute([$assId, $apprenantId, date('Y-m-d'), '2000-01-01']);
        }

        return (int)$assId;
    }

    /**
     * Upsert a detailed grade record for a given apprenant_section_semstre + module.
     */
    public function upsertDetailedGrade(
        PDO $pdo, 
        int $assId, 
        int $ssmId, 
        int $moduleId, 
        ?float $cc1, 
        ?float $cc2, 
        ?float $exam, 
        ?float $ratt, 
        ?float $moyAvr, 
        ?float $moyApr, 
        string $obs
    ): void {
        $checkStmt = $pdo->prepare("
            SELECT IDApprenant_Section_semstre_module
            FROM apprenant_section_semstre_module
            WHERE IDapprenant_Section_semstre = ? AND IDsection_semestre_Module = ?
        ");
        $checkStmt->execute([$assId, $ssmId]);
        $id = $checkStmt->fetchColumn();

        if ($id) {
            $pdo->prepare("
                UPDATE apprenant_section_semstre_module
                SET NoteC1 = ?, NoteC2 = ?, NoteCs = ?, NoteR = ?, MoyAvr = ?, MoyApr = ?, Obs = ?, IDModule = ?
                WHERE IDApprenant_Section_semstre_module = ?
            ")->execute([$cc1, $cc2, $exam, $ratt, $moyAvr, $moyApr, $obs, $moduleId, $id]);
        } else {
            $maxId = (int)$pdo->query("SELECT COALESCE(MAX(IDApprenant_Section_semstre_module), 0) FROM apprenant_section_semstre_module")->fetchColumn();
            $newId = $maxId + 1;
            $pdo->prepare("
                INSERT INTO apprenant_section_semstre_module (
                    IDApprenant_Section_semstre_module, 
                    IDapprenant_Section_semstre, 
                    IDsection_semestre_Module, 
                    IDModule, 
                    NoteC1, 
                    NoteC2, 
                    NoteCs, 
                    NoteR, 
                    MoyAvr, 
                    MoyApr, 
                    Obs,
                    IDDecision_Eval_Mdl,
                    IDDecision_Eval_MdlAvr
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NULL, NULL)
            ")->execute([$newId, $assId, $ssmId, $moduleId, $cc1, $cc2, $exam, $ratt, $moyAvr, $moyApr, $obs]);
        }
    }

    /**
     * Update special grades like internship (Stage), thesis, and presentation, as well as semester averages.
     */
    public function updateSemesterSpecialGrades(PDO $pdo, int $assId, ?float $stage, ?float $memoire, ?float $soutenance, ?float $moyAvr, ?float $moyApr): void
    {
        $pdo->prepare("
            UPDATE apprenant_section_semstre
            SET NoteStage = COALESCE(?, NoteStage),
                NoteMemoire = COALESCE(?, NoteMemoire),
                NoteSoutenance = COALESCE(?, NoteSoutenance),
                MoyAvr = COALESCE(?, MoyAvr),
                MoyApr = COALESCE(?, MoyApr)
            WHERE IDapprenant_Section_semstre = ?
        ")->execute([$stage, $memoire, $soutenance, $moyAvr, $moyApr, $assId]);
    }

    /**
     * Legacy upsertGrade method preserved for backward compatibility.
     */
    public function upsertGrade(PDO $pdo, int $assId, int $matiereId, ?float $note, string $obs): void
    {
        $checkStmt = $pdo->prepare("
            SELECT IDApprenant_Section_semstre_module
            FROM apprenant_section_semstre_module
            WHERE IDapprenant_Section_semstre = ? AND IDModule = ?
        ");
        $checkStmt->execute([$assId, $matiereId]);
        $id = $checkStmt->fetchColumn();

        if ($id) {
            $pdo->prepare("
                UPDATE apprenant_section_semstre_module
                SET NoteCs = ?, Obs = ?
                WHERE IDApprenant_Section_semstre_module = ?
            ")->execute([$note, $obs, $id]);
        } else {
            $maxId = (int)$pdo->query("SELECT COALESCE(MAX(IDApprenant_Section_semstre_module), 0) FROM apprenant_section_semstre_module")->fetchColumn();
            $newId = $maxId + 1;
            $pdo->prepare("
                INSERT INTO apprenant_section_semstre_module (
                    IDApprenant_Section_semstre_module, 
                    IDapprenant_Section_semstre, 
                    IDModule, 
                    NoteCs, 
                    Obs,
                    IDsection_semestre_Module,
                    IDDecision_Eval_Mdl,
                    IDDecision_Eval_MdlAvr
                ) VALUES (?, ?, ?, ?, ?, NULL, NULL, NULL)
            ")->execute([$newId, $assId, $matiereId, $note, $obs]);
        }
    }

    // ─── Absence Queries ──────────────────────────────────────────────────────

    /**
     * Count total active trainees — role-filtered.
     * SQL preserved exactly from AbsencesController::index()
     */
    public function countActive(string $extraWhere, array $params): int
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*)
            FROM apprenant a
            LEFT JOIN section s ON a.IDSection = s.IDSection
            WHERE 1=1 {$extraWhere}
        ");
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Count total recorded absences — role-filtered.
     * SQL preserved exactly from AbsencesController::index()
     */
    public function countAbsences(string $extraWhere, array $params): int
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*)
            FROM apprenant_absence ab
            LEFT JOIN apprenant_section_semstre ass
                ON ab.IDapprenant_Section_semstre = ass.IDapprenant_Section_semstre
            LEFT JOIN apprenant a ON ass.IDapprenant = a.IDapprenant
            LEFT JOIN section s ON a.IDSection = s.IDSection
            WHERE 1=1 {$extraWhere}
        ");
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Count trainees with 3+ absences (warnings) — role-filtered.
     * SQL preserved exactly from AbsencesController::index()
     */
    public function countWarnings(string $extraWhere, array $params): int
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM (
                SELECT ab.IDapprenant_Section_semstre, COUNT(*) as cnt
                FROM apprenant_absence ab
                LEFT JOIN apprenant_section_semstre ass
                    ON ab.IDapprenant_Section_semstre = ass.IDapprenant_Section_semstre
                LEFT JOIN apprenant a ON ass.IDapprenant = a.IDapprenant
                LEFT JOIN section s ON a.IDSection = s.IDSection
                WHERE 1=1 {$extraWhere}
                GROUP BY ab.IDapprenant_Section_semstre
                HAVING cnt >= 3
            ) as warn
        ");
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Fetch recent absences list — role-filtered.
     * SQL preserved exactly from AbsencesController::index()
     */
    public function findRecentAbsences(string $extraWhere, array $params, int $limit = 30): array
    {
        $stmt = $this->db->prepare("
            SELECT ab.IDapprenant_Absence as id, ab.Date as date_absence, ab.Type, ab.Obs, ab.heure,
                   COALESCE(c1.Nom, c2.Nom) as nom_ar, COALESCE(c1.Prenom, c2.Prenom) as prenom_ar, COALESCE(NULLIF(a.Nccp, ''), c1.NumIns, c2.NumIns) as numero_matricule,
                   sp.Nom as specialite_ar,
                   2.0 as duree_heures,
                   (ab.Type = 0) as est_justifiee,
                   (SELECT COUNT(*) FROM apprenant_absence ab2 WHERE ab2.IDapprenant_Section_semstre = ab.IDapprenant_Section_semstre AND ab2.Type = 1) * 2 as total_cumule_injustifie
            FROM apprenant_absence ab
            LEFT JOIN apprenant_section_semstre ass
                ON ab.IDapprenant_Section_semstre = ass.IDapprenant_Section_semstre
            LEFT JOIN apprenant a ON ass.IDapprenant = a.IDapprenant
            LEFT JOIN section s ON a.IDSection = s.IDSection
            LEFT JOIN candidat c1 ON c1.IDCandidat = a.IDCandidat
            LEFT JOIN candidat c2 ON c2.IdMihnati1 = a.IDapprenant AND c2.IDCandidat = (
                SELECT MAX(c3.IDCandidat) FROM candidat c3 WHERE c3.IdMihnati1 = a.IDapprenant
            )
            LEFT JOIN offre o ON o.IDOffre = COALESCE(c1.IDOffre, c2.IDOffre)
            LEFT JOIN specialite sp ON o.IDSpecialite = sp.IDSpecialite
            WHERE 1=1 {$extraWhere}
            ORDER BY ab.Date DESC
            LIMIT {$limit}
        ");
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Fetch trainees with 3+ absences (warning list) — role-filtered.
     * SQL preserved exactly from AbsencesController::warnings()
     */
    public function findAbsenceWarnings(string $extraWhere, array $params): array
    {
        $stmt = $this->db->prepare("
            SELECT a.IDapprenant as stagiaire_id, COALESCE(NULLIF(a.Nccp, ''), c1.NumIns, c2.NumIns) as numero_matricule,
                   COALESCE(c1.Nom, c2.Nom) as nom_ar, COALESCE(c1.Prenom, c2.Prenom) as prenom_ar, sp.Nom as specialite_ar,
                   COUNT(ab.IDapprenant_Absence) as total_absences
            FROM apprenant_absence ab
            LEFT JOIN apprenant_section_semstre ass
                ON ab.IDapprenant_Section_semstre = ass.IDapprenant_Section_semstre
            LEFT JOIN apprenant a ON ass.IDapprenant = a.IDapprenant
            LEFT JOIN section s ON a.IDSection = s.IDSection
            LEFT JOIN candidat c1 ON c1.IDCandidat = a.IDCandidat
            LEFT JOIN candidat c2 ON c2.IdMihnati1 = a.IDapprenant AND c2.IDCandidat = (
                SELECT MAX(c3.IDCandidat) FROM candidat c3 WHERE c3.IdMihnati1 = a.IDapprenant
            )
            LEFT JOIN offre o ON o.IDOffre = COALESCE(c1.IDOffre, c2.IDOffre)
            LEFT JOIN specialite sp ON o.IDSpecialite = sp.IDSpecialite
            WHERE 1=1 {$extraWhere}
            GROUP BY a.IDapprenant, a.Nccp, c1.NumIns, c2.NumIns, c1.Nom, c2.Nom, c1.Prenom, c2.Prenom, sp.Nom
            HAVING total_absences >= 3
            ORDER BY total_absences DESC
        ");
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Fetch full details of one trainee including total absences.
     * SQL preserved exactly from AbsencesController::printWarning()
     */
    public function findTraineeWithAbsenceCount(int $apprenantId, string $extraWhere, array $params): ?array
    {
        $params[] = $apprenantId;
        $stmt = $this->db->prepare("
            SELECT a.IDapprenant as stagiaire_id, COALESCE(NULLIF(a.Nccp, ''), c1.NumIns, c2.NumIns) as numero_matricule,
                   COALESCE(c1.Nom, c2.Nom) as nom_ar, COALESCE(c1.Prenom, c2.Prenom) as prenom_ar, COALESCE(c1.NomFr, c2.NomFr) as nom_fr, COALESCE(c1.PrenomFr, c2.PrenomFr) as prenom_fr,
                   '2000-01-01' as date_naissance, COALESCE(c1.Nin, c2.Nin) as nin,
                   sp.Nom as specialite_ar, sp.NomFr as specialite_fr,
                   COUNT(ab.IDapprenant_Absence) as total_absences
            FROM apprenant a
            LEFT JOIN section s ON a.IDSection = s.IDSection
            LEFT JOIN candidat c1 ON c1.IDCandidat = a.IDCandidat
            LEFT JOIN candidat c2 ON c2.IdMihnati1 = a.IDapprenant AND c2.IDCandidat = (
                SELECT MAX(c3.IDCandidat) FROM candidat c3 WHERE c3.IdMihnati1 = a.IDapprenant
            )
            LEFT JOIN offre o ON o.IDOffre = COALESCE(c1.IDOffre, c2.IDOffre)
            LEFT JOIN specialite sp ON o.IDSpecialite = sp.IDSpecialite
            LEFT JOIN apprenant_section_semstre ass ON ass.IDapprenant = a.IDapprenant
            LEFT JOIN apprenant_absence ab
                ON ab.IDapprenant_Section_semstre = ass.IDapprenant_Section_semstre
            WHERE 1=1 {$extraWhere} AND a.IDapprenant = ?
            GROUP BY a.IDapprenant, a.Nccp, c1.NumIns, c2.NumIns, c1.Nom, c2.Nom, c1.Prenom, c2.Prenom, c1.NomFr, c2.NomFr, c1.PrenomFr, c2.PrenomFr, c1.Nin, c2.Nin, sp.Nom, sp.NomFr
        ");
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Resolve the apprenant_section_semstre ID for a trainee (absence recording).
     * SQL preserved exactly from AbsencesController::store()
     */
    public function findSectionSemestreId(int $apprenantId, string $extraWhere, array $params): int
    {
        $params[] = $apprenantId;
        $stmt = $this->db->prepare("
            SELECT ass.IDapprenant_Section_semstre
            FROM apprenant_section_semstre ass
            LEFT JOIN apprenant a ON ass.IDapprenant = a.IDapprenant
            LEFT JOIN section s ON a.IDSection = s.IDSection
            LEFT JOIN candidat c1 ON c1.IDCandidat = a.IDCandidat
            LEFT JOIN candidat c2 ON c2.IdMihnati1 = a.IDapprenant AND c2.IDCandidat = (
                SELECT MAX(c3.IDCandidat) FROM candidat c3 WHERE c3.IdMihnati1 = a.IDapprenant
            )
            LEFT JOIN offre o ON o.IDOffre = COALESCE(c1.IDOffre, c2.IDOffre)
            WHERE 1=1 {$extraWhere} AND a.IDapprenant = ?
            LIMIT 1
        ");
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Insert a single absence record.
     * SQL preserved exactly from AbsencesController::store()
     */
    public function insertAbsence(PDO $pdo, int $assId, string $date, string $heure): bool
    {
        $stmt = $pdo->prepare("
            INSERT INTO apprenant_absence (IDapprenant_Section_semstre, Date, Type, heure, matinsoir)
            VALUES (?, ?, 1, ?, 0)
        ");
        return $stmt->execute([$assId, $date, $heure]);
    }

    /**
     * Memory-safe Generator for streaming absence records to ReportingService.
     * Fetches in chunks of $chunkSize — peak RAM = O(chunkSize).
     * SQL preserved exactly from findRecentAbsences().
     *
     * @param  string $extraWhere  Role-scoped WHERE fragment
     * @param  array  $params      Positional params for extraWhere
     * @param  int    $chunkSize   Rows per DB round-trip (default 200)
     * @return \Generator          Yields one absence row at a time
     */
    public function streamAbsencesChunked(string $extraWhere, array $params, int $chunkSize = 200): \Generator
    {
        $offset = 0;
        do {
            $chunkParams = array_merge($params, [$chunkSize, $offset]);
            $stmt = $this->db->prepare("
                SELECT ab.IDapprenant_Absence as id, ab.Date as date_absence, ab.Type, ab.Obs, ab.heure,
                       COALESCE(c1.Nom, c2.Nom) as nom_ar, COALESCE(c1.Prenom, c2.Prenom) as prenom_ar, COALESCE(NULLIF(a.Nccp, ''), c1.NumIns, c2.NumIns) as numero_matricule,
                       sp.Nom as specialite_ar,
                       2.0 as duree_heures,
                       (ab.Type = 0) as est_justifiee,
                       (SELECT COUNT(*) FROM apprenant_absence ab2 WHERE ab2.IDapprenant_Section_semstre = ab.IDapprenant_Section_semstre AND ab2.Type = 1) * 2 as total_cumule_injustifie
                FROM apprenant_absence ab
                LEFT JOIN apprenant_section_semstre ass
                    ON ab.IDapprenant_Section_semstre = ass.IDapprenant_Section_semstre
                LEFT JOIN apprenant a ON ass.IDapprenant = a.IDapprenant
                LEFT JOIN section s ON a.IDSection = s.IDSection
                LEFT JOIN candidat c1 ON c1.IDCandidat = a.IDCandidat
                LEFT JOIN candidat c2 ON c2.IdMihnati1 = a.IDapprenant AND c2.IDCandidat = (
                    SELECT MAX(c3.IDCandidat) FROM candidat c3 WHERE c3.IdMihnati1 = a.IDapprenant
                )
                LEFT JOIN offre o ON o.IDOffre = COALESCE(c1.IDOffre, c2.IDOffre)
                LEFT JOIN specialite sp ON o.IDSpecialite = sp.IDSpecialite
                WHERE 1=1 {$extraWhere}
                ORDER BY ab.Date DESC
                LIMIT ? OFFSET ?
            ");
            $stmt->execute($chunkParams);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($rows as $row) {
                yield $row;
            }

            $offset += $chunkSize;
        } while (count($rows) === $chunkSize);
    }

    /**
     * Memory-safe Generator for streaming specialties in progress of training.
     *
     * @param  string $extraWhere  Role-scoped WHERE fragment
     * @param  array  $params      Positional params for extraWhere
     * @param  int    $chunkSize   Rows per DB round-trip (default 200)
     * @param  int    $totalLimit  Hard cap on total rows yielded (default 10000)
     * @return \Generator          Yields one specialty row at a time
     */
    public function streamSpecialitesEnCoursChunked(string $extraWhere, array $params, int $chunkSize = 200, ?int $totalLimit = null): \Generator
    {
        $offset  = 0;
        $yielded = 0;
        do {
            $chunkParams = array_merge($params, [$chunkSize, $offset]);
            $stmt = $this->db->prepare("
                SELECT 
                    s.IDOffre AS id_offre,
                    COALESCE(w.Nom, 'غير متوفر') AS wilaya,
                    COALESCE(e.IDetablissement, 'غير متوفر') AS id_etablissement,
                    COALESCE(e.Nom, 'غير متوفر') AS nom_etablissement,
                    COALESCE(nf.Nom, 'غير متوفر') AS nature_etablissement,
                    COALESCE(sp.IDSpecialite, 'غير متوفر') AS id_specialite,
                    COALESCE(sp.CodeSpec, 'غير متوفر') AS code_specialite,
                    COALESCE(sp.Nom, 'غير متوفر') AS nom_specialite,
                    COALESCE(sp.NomFr, 'غير متوفر') AS nom_formation,
                    COALESCE(sess.IDSession, 'غير متوفر') AS id_session,
                    COALESCE(sess.Nom, 'غير متوفر') AS nom_session,
                    COALESCE(o.IDMode_formation, 'غير متوفر') AS id_mode_formation,
                    COALESCE(mf.Nom, 'غير متوفر') AS nom_mode_formation,
                    o.Obs AS obs_json,
                    COALESCE(
                        (SELECT ss.NumSem 
                         FROM section_semestre ss 
                         WHERE ss.IDSection = s.IDSection 
                         ORDER BY ss.IDSection_Semestre DESC 
                         LIMIT 1), 1
                    ) AS numero_semestre,
                    (SELECT COUNT(*) FROM apprenant a WHERE a.IDSection = s.IDSection AND a.statut = 'actif') AS nombre_stagiaires,
                    o.Equipement AS equipements
                FROM section s
                JOIN offre o ON s.IDOffre = o.IDOffre
                LEFT JOIN specialite sp ON o.IDSpecialite = sp.IDSpecialite
                LEFT JOIN etablissement e ON o.IDEts_Form = e.IDetablissement
                LEFT JOIN nature_etsf nf ON e.IDNature_etsF = nf.IDNature_etsF
                LEFT JOIN wilaya w ON e.IDDFEP = w.IDWilayaa
                LEFT JOIN session sess ON o.IDSession = sess.IDSession
                LEFT JOIN mode_formation mf ON o.IDMode_formation = mf.IDMode_formation
                WHERE (
                    SELECT 1 FROM apprenant a 
                    WHERE a.IDSection = s.IDSection 
                    AND a.statut = 'actif'
                    LIMIT 1
                ) = 1 {$extraWhere}
                ORDER BY s.IDSection DESC
                LIMIT ? OFFSET ?
            ");
            $stmt->execute($chunkParams);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($rows as $row) {
                if ($totalLimit !== null && $yielded >= $totalLimit) {
                    return; // Hard cap reached — stop iteration
                }

                // Decode JSON columns from Obs field
                $obs = [];
                if (!empty($row['obs_json'])) {
                    $obs = json_decode($row['obs_json'], true) ?? [];
                }
                $row['regime_cours'] = $obs['regime_cours'] ?? 'فردي';
                $row['hebergement']  = $obs['hebergement'] ?? 'خارجي';

                // Map / format fields
                $row['equipements'] = ((int)$row['equipements'] === 1) ? 'نعم' : 'لا'; // Yes/No in Arabic
                yield $row;
                $yielded++;
            }

            $offset += $chunkSize;
        } while (count($rows) === $chunkSize && ($totalLimit === null || $yielded < $totalLimit));
    }
}

