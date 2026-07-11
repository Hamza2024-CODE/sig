<?php

namespace App\Domains\Academic\Repositories;

use App\Core\Database;
use PDO;

class OffresRepository
{
    protected PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Retrieve Wilaya or Center name for subtitle header
     */
    public function getWilayaName(string $roleCode, int $etabId, int $dfepId): string
    {
        $name = 'الوطنية (الإدارة المركزية)';
        if ($roleCode === 'dfep' && $dfepId > 0) {
            $wn = $this->db->prepare("SELECT Nom FROM dfep WHERE IDDFEP = ? LIMIT 1");
            $wn->execute([$dfepId]);
            $row = $wn->fetchColumn();
            if ($row) $name = $row;
        } elseif (in_array($roleCode, ['etablissement', 'directeur']) && $etabId > 0) {
            $en = $this->db->prepare("SELECT Nom FROM etablissement WHERE IDetablissement = ? LIMIT 1");
            $en->execute([$etabId]);
            $row = $en->fetchColumn();
            if ($row) $name = $row;
        }
        return $name;
    }

    /**
     * Retrieve offers stats counters
     */
    public function getOffersStats(string $scopeWhere, array $scopeParams): array
    {
        $joinEtab = strpos($scopeWhere, 'e.') !== false
            ? "LEFT JOIN etablissement e ON o.IDEts_Form = e.IDetablissement"
            : "";
        
        $cacheKey = 'offres_stats_' . md5($scopeWhere . '_' . serialize($scopeParams));
        
        return \App\Services\CacheService::remember($cacheKey, 600, function() use ($scopeWhere, $scopeParams, $joinEtab) {
            // Combine total offers and total places into one query
            $s = $this->db->prepare("
                SELECT COUNT(*) as total_offres, COALESCE(SUM(o.nbrPrevision), 0) as total_places 
                FROM offre o 
                $joinEtab 
                WHERE $scopeWhere
            ");
            $s->execute($scopeParams);
            $res1 = $s->fetch(PDO::FETCH_ASSOC);
            $total_offres = (int)($res1['total_offres'] ?? 0);
            $total_places = (int)($res1['total_places'] ?? 0);

            if ($scopeWhere === '1=1') {
                // High-performance direct queries for national scope (admin/central)
                // الناشطين (active) = المستمرون (apprenant) + المسجلون (candidat)
                // ① Registered students (مسجلين) = all candidat rows
                $s = $this->db->query("
                    SELECT COUNT(*) as total_inscrits, 
                           SUM(CASE WHEN Civ = 2 OR Civ = 'F' THEN 1 ELSE 0 END) as total_femmes 
                    FROM candidat
                ");
                $res2 = $s->fetch(PDO::FETCH_ASSOC);
                $total_inscrits = (int)($res2['total_inscrits'] ?? 0);
                $total_femmes   = (int)($res2['total_femmes'] ?? 0);

                // ② Active/Continuing students (الناشطين = المستمرون المسجلون في أقسام)
                // = apprenant rows (those placed in a section with statut='actif')
                // Use cached value if available since count(*) on 1.3M rows takes ~5s
                $activeKey = 'offres_active_count_national';
                $activeData = \App\Services\CacheService::remember($activeKey, 3600, function() {
                    $sa = $this->db->query("
                        SELECT COUNT(*) as actifs,
                               SUM(CASE WHEN c.Civ = 2 OR c.Civ = 'F' THEN 1 ELSE 0 END) as actifs_femmes
                        FROM apprenant a
                        JOIN candidat c ON a.IDCandidat = c.IDCandidat
                    ");
                    return $sa->fetch(PDO::FETCH_ASSOC);
                });
                $total_active  = (int)($activeData['actifs'] ?? $total_inscrits);
                $active_femmes = (int)($activeData['actifs_femmes'] ?? $total_femmes);
            } else {
                // Scoped role (dfep/etablissement): count candidates linked to scoped offers
                // Use subquery to let MySQL use IDOffre index on candidat table
                $s = $this->db->prepare("
                    SELECT COUNT(*) as total_inscrits,
                           SUM(CASE WHEN c.Civ = 2 OR c.Civ = 'F' THEN 1 ELSE 0 END) as total_femmes
                    FROM candidat c
                    WHERE c.IDOffre IN (
                        SELECT o.IDOffre FROM offre o
                        $joinEtab
                        WHERE $scopeWhere
                    )
                ");
                $s->execute($scopeParams);
                $res2 = $s->fetch(PDO::FETCH_ASSOC);
                $total_inscrits = (int)($res2['total_inscrits'] ?? 0);
                $total_femmes   = (int)($res2['total_femmes'] ?? 0);

                // Active/Continuing (المستمرون المسجلون في أقسام) for scoped roles
                $sa = $this->db->prepare("
                    SELECT COUNT(DISTINCT a.IDapprenant) as actifs,
                           SUM(CASE WHEN c.Civ = 2 OR c.Civ = 'F' THEN 1 ELSE 0 END) as actifs_femmes
                    FROM apprenant a
                    JOIN candidat c ON a.IDCandidat = c.IDCandidat
                    WHERE c.IDOffre IN (
                        SELECT o.IDOffre FROM offre o
                        $joinEtab
                        WHERE $scopeWhere
                    )
                ");
                $sa->execute($scopeParams);
                $resA = $sa->fetch(PDO::FETCH_ASSOC);
                $total_active  = (int)($resA['actifs'] ?? $total_inscrits);
                $active_femmes = (int)($resA['actifs_femmes'] ?? $total_femmes);
            }

            return [
                'total_offres'       => $total_offres,
                'total_places'       => $total_places,
                'total_inscrits'     => $total_inscrits,   // مسجلين (registered candidats)
                'inscrits_femmes'    => $total_femmes,
                'total_actifs'       => $total_active,      // ناشطين = مستمرين (apprenants in sections)
                'actifs_femmes'      => $active_femmes,
                // Keep legacy keys for backward compatibility with templates
                'total_diplomes'     => $total_active,
                'diplomes_femmes'    => $active_femmes,
                'taux_inscrits_prevu'=> $total_places > 0 ? round(($total_inscrits / $total_places) * 100) : 0,
                'taux_actifs_prevu'  => $total_places > 0 ? round(($total_active / $total_places) * 100) : 0,
                // Legacy key alias
                'taux_diplomes_prevu'=> $total_places > 0 ? round(($total_active / $total_places) * 100) : 0,
            ];
        });
    }


    /**
     * Retrieve mode formation distribution stats
     */
    public function getModeFormationBreakdown(string $scopeWhere, array $scopeParams): array
    {
        $cacheKey = 'offres_mode_breakdown_' . md5($scopeWhere . '_' . serialize($scopeParams));

        return \App\Services\CacheService::remember($cacheKey, 600, function() use ($scopeWhere, $scopeParams) {
            // Only JOIN etablissement when the scope WHERE clause references the 'e.' alias
            // (dfep/etablissement roles). For admin (scopeWhere='1=1'), skip the JOIN entirely
            // to avoid an unnecessary full-table scan that causes timeouts on large datasets.
            $joinEtab = strpos($scopeWhere, 'e.') !== false
                ? "LEFT JOIN etablissement e ON o.IDEts_Form = e.IDetablissement"
                : "";

            $stmtD = $this->db->prepare("
                SELECT mf.Nom as nom_ar, mf.NomFr as nom_fr,
                       o.count, o.places, o.inscrits
                FROM (
                    SELECT o.IDMode_formation, COUNT(o.IDOffre) as count, 
                           SUM(o.nbrPrevision) as places, SUM(o.NbrInscr) as inscrits
                    FROM offre o
                    $joinEtab
                    WHERE $scopeWhere
                    GROUP BY o.IDMode_formation
                ) o
                LEFT JOIN mode_formation mf ON o.IDMode_formation = mf.IDMode_formation
            ");
            $stmtD->execute($scopeParams);
            $dispositifs = [];
            foreach ($stmtD->fetchAll(PDO::FETCH_ASSOC) as $rd) {
                $dispositifs[] = [
                    'nom_ar'   => $rd['nom_ar'] ?: 'غير محدد',
                    'nom_fr'   => $rd['nom_fr'] ?: 'Non défini',
                    'count'    => (int)$rd['count'],
                    'places'   => (int)($rd['places'] ?? 0),
                    'inscrits' => (int)($rd['inscrits'] ?? 0),
                ];
            }
            return $dispositifs;
        });
    }

    /**
     * Retrieve branches distribution stats
     */
    public function getFiliereBreakdown(string $scopeWhere, array $scopeParams): array
    {
        $cacheKey = 'offres_filiere_breakdown_' . md5($scopeWhere . '_' . serialize($scopeParams));

        return \App\Services\CacheService::remember($cacheKey, 600, function() use ($scopeWhere, $scopeParams) {
            // Only JOIN etablissement when the scope WHERE clause references the 'e.' alias
            // (dfep/etablissement roles). For admin (scopeWhere='1=1'), skip the JOIN entirely
            // to avoid an unnecessary full-table scan that causes timeouts on large datasets.
            $joinEtab = strpos($scopeWhere, 'e.') !== false
                ? "LEFT JOIN etablissement e ON o.IDEts_Form = e.IDetablissement"
                : "";

            $stmtFil = $this->db->prepare("
                SELECT b.Nom as nom_ar, b.NomFr as nom_fr,
                       SUM(o.count) as count, SUM(o.places) as places, SUM(o.inscrits) as inscrits
                FROM (
                    SELECT o.IDSpecialite, COUNT(o.IDOffre) as count, 
                           SUM(o.nbrPrevision) as places, SUM(o.NbrInscr) as inscrits
                    FROM offre o
                    $joinEtab
                    WHERE $scopeWhere
                    GROUP BY o.IDSpecialite
                ) o
                JOIN specialite sp ON o.IDSpecialite = sp.IDSpecialite
                JOIN branche b     ON sp.IDBranche   = b.IDBranche
                GROUP BY b.IDBranche, b.Nom, b.NomFr
                ORDER BY count DESC
            ");
            $stmtFil->execute($scopeParams);
            $filieres = [];
            foreach ($stmtFil->fetchAll(PDO::FETCH_ASSOC) as $rf) {
                $filieres[] = [
                    'nom_ar'   => $rf['nom_ar'],
                    'nom_fr'   => $rf['nom_fr'],
                    'count'    => (int)$rf['count'],
                    'places'   => (int)($rf['places'] ?? 0),
                    'inscrits' => (int)($rf['inscrits'] ?? 0),
                ];
            }
            return $filieres;
        });
    }

    /**
     * Retrieve list of offers matching scopes
     */
    public function getDetailedOffresList(string $scopeWhere, array $scopeParams): array
    {
        $stmtDet = $this->db->prepare("
            SELECT o.IDOffre as id, o.IDSpecialite as specialite_id,
                   sp.Nom as spec_ar, sp.NomFr as spec_fr, sp.CodeSpec as spec_code,
                   nf.Nom as level_name,
                   e.Nom as centre, e.IDetablissement as etablissement_id,
                   e.IDDFEP as etab_dfep_id,
                   o.IDEts_FormM as etablissement_delegue_id,
                   ed.Nom as centre_delegue,
                   sess.Nom as session_name, sess.IDSession as session_id,
                   o.NbrInscr as inscrits, o.NbrInscrf as inscrits_females,
                   o.nbrPrevision as places,
                   o.Nbrinco as laureats, o.Nbrincof as laureats_females,
                   o.DateD as date_debut, o.DateF as date_fin,
                   o.DateSelection as date_debut_selection, o.DateVisiteMedical as date_examen_medical,
                   o.DateVisiteAtelier as date_visite_ateliers,
                   o.Valide, o.ValidDfp, o.ValideCentral,
                   o.Obs_Dfep, o.Obs_Central, o.Obs,
                   mf.Nom as mode_formation, o.IDMode_formation,
                   o.nbrGroupe, o.encadrement, o.Programme as programme, o.Equipement as equipement,
                   o.Nom_specialite_carte as nom_spec_custom_ar,
                   o.Nom_specialite_cartefr as nom_spec_custom_fr,
                   o.IDqualification_dplm
            FROM offre o
            LEFT JOIN specialite    sp   ON o.IDSpecialite    = sp.IDSpecialite
            LEFT JOIN niveau_fp     nf   ON sp.IDNiveau_Fp    = nf.IDNiveau_Fp
            LEFT JOIN etablissement e    ON o.IDEts_Form      = e.IDetablissement
            LEFT JOIN etablissement ed   ON o.IDEts_FormM     = ed.IDetablissement
            LEFT JOIN session       sess ON o.IDSession       = sess.IDSession
            LEFT JOIN mode_formation mf  ON o.IDMode_formation = mf.IDMode_formation
            WHERE $scopeWhere
            ORDER BY e.IDDFEP ASC, e.IDetablissement ASC, o.IDOffre DESC
            LIMIT 2000
        ");
        $stmtDet->execute($scopeParams);
        return $stmtDet->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retrieve specialities list for dynamic dropdowns
     */
    public function getModalSpecialites(): array
    {
        $raw = $this->db->query("
            SELECT IDSpecialite as id, CodeSpec as code, Nom as libelle_ar 
            FROM specialite 
            ORDER BY Nom ASC
        ")->fetchAll(PDO::FETCH_ASSOC);
        
        $specialites = [];
        foreach ($raw as $rs) {
            $name = $rs['libelle_ar'] ?? '';
            $duree = 2;
            if (stripos($name, 'تقني سامي') !== false) {
                $duree = 5;
            } elseif (stripos($name, 'تقني') !== false) {
                $duree = 4;
            }
            $rs['duree_semestres'] = $duree;
            $specialites[] = $rs;
        }
        return $specialites;
    }

    /**
     * Retrieve active establishments dropdown scoped by role
     */
    public function getModalEtablissements(string $roleCode, int $etabId, int $dfepId): array
    {
        if (in_array($roleCode, ['etablissement', 'directeur']) && $etabId > 0) {
            $stmt = $this->db->prepare("SELECT IDetablissement as id, Nom as nom_ar FROM etablissement WHERE IDetablissement = ?");
            $stmt->execute([$etabId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } elseif ($roleCode === 'dfep' && $dfepId > 0) {
            $stmt = $this->db->prepare("SELECT IDetablissement as id, Nom as nom_ar FROM etablissement WHERE IDDFEP = ? ORDER BY Nom ASC");
            $stmt->execute([$dfepId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            return $this->db->query("SELECT IDetablissement as id, Nom as nom_ar FROM etablissement ORDER BY Nom ASC")->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    /**
     * Retrieve training sessions list
     */
    public function getModalSessions(): array
    {
        return $this->db->query("
            SELECT IDSession as id, Code as code_session, Nom as intitule_ar, NomFr as intitule_fr, 
                   DateD as date_debut, DateD as date_fin, DateFInscr as date_fin_insc 
            FROM session 
            ORDER BY DateD DESC
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retrieve qualifications/diplomas list from qualification_dplm table
     * Used for "الشهادة المستهدفة" (Diplôme visé) dropdown
     */
    public function getModalQualificationsDiplomes(): array
    {
        return $this->db->query("
            SELECT IDqualification_dplm as id, Nom as nom_ar, NomFr as nom_fr,
                   Abr as abr_ar, AbrFr as abr_fr, code
            FROM qualification_dplm
            ORDER BY NumOrd ASC, Nom ASC
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retrieve accommodation regimes from apprenant_regime table
     * Used for "نظام الإيواء" (Hébergement) dropdown
     */
    public function getModalRegimesHebergement(): array
    {
        return $this->db->query("
            SELECT IDapprenant_Regime as id, Nom as nom_ar,
                   COALESCE(NULLIF(NomFr,''), Nom) as nom_fr
            FROM apprenant_regime
            ORDER BY IDapprenant_Regime ASC
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retrieve study regime (cours schedules) from mode table
     * Used for "نظام الدراسة" (Régime de cours) dropdown
     * Filters to relevant study-schedule modes only (IDs 1,3,5 = unique/soir/distance)
     */
    public function getModalRegimesCours(): array
    {
        return $this->db->query("
            SELECT IDmode as id, libelleArabe as nom_ar, libelleLatin as nom_fr, code
            FROM mode
            ORDER BY IDmode ASC
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retrieve training modes (نمط التكوين) from mode_formation table
     * Used for "نمط التكوين" (Mode de Formation) dropdown — numeric ID as value
     */
    public function getModalModesFormation(): array
    {
        return $this->db->query("
            SELECT IDMode_formation as id, Nom as nom_ar, NomFr as nom_fr, Code as code, AbrFr as abr_fr
            FROM mode_formation
            ORDER BY NomOrd ASC, IDMode_formation ASC
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Lookup IDqualification_dplm by code (e.g. 'BTS', 'CAP')
     * Returns 0 if not found
     */
    public function getQualificationIdByCode(string $code): int
    {
        $stmt = $this->db->prepare("SELECT IDqualification_dplm FROM qualification_dplm WHERE code = ? LIMIT 1");
        $stmt->execute([$code]);
        return (int)($stmt->fetchColumn() ?: 0);
    }

    /**
     * Reverse lookup: return code for a given IDqualification_dplm
     * Returns empty string if not found
     */
    public function getQualificationCodeById(int $id): string
    {
        $stmt = $this->db->prepare("SELECT code FROM qualification_dplm WHERE IDqualification_dplm = ? LIMIT 1");
        $stmt->execute([$id]);
        return (string)($stmt->fetchColumn() ?: '');
    }

    /**
     * Retrieve pending validation offers matching role scopes
     */
    public function getValidationOffers(string $roleCode, int $dfepId): array
    {
        $baseQuery = "
            SELECT o.IDOffre as id, o.IDSpecialite as specialite_id,
                   sp.Nom as spec_ar, sp.NomFr as spec_fr, sp.CodeSpec as spec_code,
                   nf.Nom as level_name,
                   e.Nom as centre, e.IDetablissement as etablissement_id,
                   o.IDEts_FormM as etablissement_delegue_id,
                   ed.Nom as centre_delegue,
                   sess.Nom as session_name, sess.IDSession as session_id,
                   o.NbrInscr as inscrits, o.NbrInscrf as inscrits_females,
                   o.nbrPrevision as places,
                   o.Nbrinco as laureats, o.Nbrincof as laureats_females,
                   o.DateD as date_debut, o.DateF as date_fin,
                   o.DateSelection as date_debut_selection, o.DateVisiteMedical as date_examen_medical,
                   o.DateVisiteAtelier as date_visite_ateliers,
                   o.Valide, o.ValidDfp, o.ValideCentral,
                   o.Obs_Dfep, o.Obs_Central, o.Obs,
                   mf.Nom as mode_formation, o.IDMode_formation,
                   o.nbrGroupe, o.encadrement, o.Programme as programme, o.Equipement as equipement
            FROM offre o
            LEFT JOIN specialite sp ON o.IDSpecialite = sp.IDSpecialite
            LEFT JOIN niveau_fp nf ON sp.IDNiveau_Fp = nf.IDNiveau_Fp
            LEFT JOIN etablissement e ON o.IDEts_Form = e.IDetablissement
            LEFT JOIN etablissement ed ON o.IDEts_FormM = ed.IDetablissement
            LEFT JOIN session sess ON o.IDSession = sess.IDSession
            LEFT JOIN mode_formation mf ON o.IDMode_formation = mf.IDMode_formation
        ";

        if ($roleCode === 'dfep') {
            // Pending for DFP: submitted by establishment (Valide=1), not yet approved by DFP (ValidDfp=0), and no rejection reason
            $stmt1 = $this->db->prepare($baseQuery . "
                WHERE e.IDDFEP = ? AND o.Valide = 1 AND o.ValidDfp = 0 AND (o.Obs_Dfep IS NULL OR o.Obs_Dfep = '')
                ORDER BY o.IDOffre DESC
            ");
            $stmt1->execute([$dfepId]);
            $pending = $stmt1->fetchAll(PDO::FETCH_ASSOC);

            // History for DFP: Approved (ValidDfp=1) OR Rejected (Obs_Dfep set and ValidDfp=0)
            $stmt2 = $this->db->prepare($baseQuery . "
                WHERE e.IDDFEP = ? AND (o.ValidDfp = 1 OR (o.Valide = 1 AND o.ValidDfp = 0 AND o.Obs_Dfep IS NOT NULL AND o.Obs_Dfep != ''))
                ORDER BY o.IDOffre DESC
            ");
            $stmt2->execute([$dfepId]);
            $processed = $stmt2->fetchAll(PDO::FETCH_ASSOC);
        } else {
            // Central/Admin: All offers
            // Pending: Approved by DFP (ValidDfp=1), not yet approved by Central (ValideCentral=0), and no rejection reason
            // LIMIT 500 — prevents OOM when the offre table is large (no scoping by wilaya for central role)
            $stmtPending = $this->db->prepare($baseQuery . "
                WHERE o.ValidDfp = 1 AND o.ValideCentral = 0 AND (o.Obs_Central IS NULL OR o.Obs_Central = '')
                ORDER BY o.IDOffre DESC
                LIMIT 500
            ");
            $stmtPending->execute();
            $pending = $stmtPending->fetchAll(PDO::FETCH_ASSOC);

            // History: Approved centrally (ValideCentral=1) OR Rejected centrally (Obs_Central set and ValideCentral=0)
            // LIMIT 500 — prevents OOM when the offre table is large
            $stmtProcessed = $this->db->prepare($baseQuery . "
                WHERE o.ValideCentral = 1 OR (o.ValidDfp = 1 AND o.ValideCentral = 0 AND o.Obs_Central IS NOT NULL AND o.Obs_Central != '')
                ORDER BY o.IDOffre DESC
                LIMIT 500
            ");
            $stmtProcessed->execute();
            $processed = $stmtProcessed->fetchAll(PDO::FETCH_ASSOC);
        }

        return ['pending' => $pending, 'processed' => $processed];
    }

    /**
     * Retrieve single offer details by ID
     */
    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM offre WHERE IDOffre = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Retrieve count of linked sections before delete
     */
    public function checkSectionLinks(int $id): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM section WHERE IDOffre = ?");
        $stmt->execute([$id]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Delete training offer
     */
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM offre WHERE IDOffre = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Submit offer to Regional Direction (DFEP)
     */
    public function submit(int $id): bool
    {
        $stmt = $this->db->prepare("UPDATE offre SET Valide = 1 WHERE IDOffre = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Validate/Reject offer by Regional Direction (DFEP)
     */
    public function validateDirection(int $id, int $val, ?string $motif): bool
    {
        $stmt = $this->db->prepare("UPDATE offre SET ValidDfp = ?, Obs_Dfep = ? WHERE IDOffre = ?");
        return $stmt->execute([$val, $motif, $id]);
    }

    /**
     * Validate/Reject offer by Central Administration
     */
    public function validateCentral(int $id, int $val, ?string $motif): bool
    {
        $stmt = $this->db->prepare("UPDATE offre SET ValideCentral = ?, Obs_Central = ? WHERE IDOffre = ?");
        return $stmt->execute([$val, $motif, $id]);
    }

    /**
     * Retrieve print handbook offers list (legacy method — preserved)
     * LIMIT 2000 prevents OOM when printing with large datasets; use streamOffresChunked() for unlimited export.
     */
    public function getPrintOffres(): array
    {
        $stmt = $this->db->prepare("
            SELECT
                CONCAT('OFF-', o.IDOffre)          AS code,
                sp.Nom                             AS spec_ar,
                sp.NomFr                           AS spec_fr,
                CASE
                    WHEN sp.NbrSem = 5 THEN 'BTS'
                    WHEN sp.NbrSem >= 3 THEN 'BP'
                    ELSE 'CAP'
                END                                AS diplome_vise,
                CASE o.IDMode_formation
                    WHEN 2 THEN 'apprentissage'
                    ELSE 'présentiel'
                END                                AS mode_formation,
                CASE
                    WHEN sp.NbrSem = 5 THEN 'ثالثة ثانوي'
                    WHEN sp.NbrSem >= 3 THEN 'تعليم متوسط'
                    ELSE 'بدون مستوى'
                END                                AS niveau_requis,
                o.nbrPrevision                     AS capacite,
                e.Nom                              AS etab_ar,
                sess.Nom                           AS session_name,
                o.DateD                            AS date_debut,
                o.DateF                            AS date_fin,
                o.NbrInscr                         AS inscrits
            FROM offre o
            LEFT JOIN specialite    sp   ON o.IDSpecialite    = sp.IDSpecialite
            LEFT JOIN etablissement e    ON o.IDEts_Form      = e.IDetablissement
            LEFT JOIN session       sess ON o.IDSession       = sess.IDSession
            ORDER BY o.DateD DESC
            LIMIT 2000
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Memory-safe Generator for streaming offers to ReportingService.
     * Fetches in chunks of $chunkSize — peak RAM = O(chunkSize).
     * SQL derived from getPrintOffres() — preserved exactly.
     *
     * @param  string $scopeWhere   Role-scoped WHERE clause (e.g. "e.IDDFEP = ?")
     * @param  array  $scopeParams  Positional params for scopeWhere
     * @param  int    $chunkSize    Rows per DB round-trip (default 200)
     * @return \Generator           Yields one offer row at a time
     */
    public function streamOffresChunked(string $scopeWhere, array $scopeParams, int $chunkSize = 200): \Generator
    {
        $offset = 0;
        do {
            $chunkParams = array_merge($scopeParams, [$chunkSize, $offset]);
            $stmt = $this->db->prepare("
                SELECT
                    CONCAT('OFF-', o.IDOffre)  AS code,
                    sp.Nom                     AS spec_ar,
                    sp.NomFr                   AS spec_fr,
                    CASE
                        WHEN sp.NbrSem = 5 THEN 'BTS'
                        WHEN sp.NbrSem >= 3 THEN 'BP'
                        ELSE 'CAP'
                    END                        AS diplome_vise,
                    CASE o.IDMode_formation
                        WHEN 2 THEN 'apprentissage'
                        ELSE 'présentiel'
                    END                        AS mode_formation,
                    o.nbrPrevision             AS capacite,
                    e.Nom                      AS etab_ar,
                    sess.Nom                   AS session_name,
                    o.DateD                    AS date_debut,
                    o.DateF                    AS date_fin,
                    o.NbrInscr                 AS inscrits
                FROM offre o
                LEFT JOIN specialite    sp   ON o.IDSpecialite    = sp.IDSpecialite
                LEFT JOIN etablissement e    ON o.IDEts_Form      = e.IDetablissement
                LEFT JOIN session       sess ON o.IDSession       = sess.IDSession
                WHERE {$scopeWhere}
                ORDER BY o.DateD DESC
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
     * Concurrency lock calculation on MAX(IDOffre) + 1
     */
    public function getNextIdWithLock(PDO $pdo): int
    {
        $stmt = $pdo->query("SELECT COALESCE(MAX(IDOffre), 0) as max_id FROM offre FOR UPDATE");
        $row = $stmt->fetch();
        return ($row['max_id'] !== null) ? (int)$row['max_id'] + 1 : 1;
    }

    /**
     * Insert a new offer inside transaction.
     * Notes:
     *  - offre_ibfk_4 (IDEts_Form → ets_form) was dropped: ets_form is empty (WinDev migration artefact)
     *  - offre_ibfk_6 (IDEts_FormM → ets_form) was dropped for the same reason
     *  - IDMois is NULL: mois table has IDs 1-12 only (no 0 row)
     */
    public function insertOffre(PDO $pdo, int $id, array $data): bool
    {
        $stmt = $pdo->prepare("
            INSERT INTO `offre` (
                `IDOffre`, `IDSession`, `IDSpecialite`, `IDMode_formation`, `DateD`, `DateF`,
                `DateVisiteAtelier`, `DateVisiteMedical`, `encadrement`, `Programme`, `Equipement`,
                `DateSelection`, `NbrInscr`, `NbrInscrf`, `nbrPrevision`, `IDEts_Form`, `IDEts_FormM`,
                `ValidDfp`, `ValideCentral`, `Valide`, `brigde`, `EtatSection`, `IDMihnati`, `Obs`,
                `nbrGroupe`, `NbrinscMihnati`, `NbrPreinscMihnati`, `NbrincoMihnati`, `IDOffreN`,
                `IDMois`, `Datesselction`, `Nbrinco`, `Nbrincof`, `ValidationDfp`, `Nom_specialite_carte`,
                `IDqualification_dplm`, `Obs_Dfep`, `Obs_Central`, `Nom_specialite_cartefr`
            ) VALUES (
                ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?
            )
        ");
        return $stmt->execute([
            $id,                                 // IDOffre
            $data['session_id'],                 // IDSession
            $data['specialite_id'],              // IDSpecialite
            $data['mode'],                       // IDMode_formation
            $data['debut'],                      // DateD
            $data['fin'],                        // DateF
            $data['date_visite_ateliers'],       // DateVisiteAtelier
            $data['date_examen_medical'],        // DateVisiteMedical
            $data['encadrement'],                // encadrement
            $data['programme'],                  // Programme
            $data['equipement'],                 // Equipement
            $data['date_debut_selection'],       // DateSelection
            0,                                   // NbrInscr
            0,                                   // NbrInscrf
            $data['capacite'],                   // nbrPrevision
            $data['etablissement_id'],           // IDEts_Form
            $data['etablissement_delegue_id'],   // IDEts_FormM
            0,                                   // ValidDfp
            0,                                   // ValideCentral
            0,                                   // Valide
            0,                                   // brigde
            0,                                   // EtatSection
            0,                                   // IDMihnati
            $data['obs'],                        // Obs
            $data['nbr_groupe'],                 // nbrGroupe
            0,                                   // NbrinscMihnati
            0,                                   // NbrPreinscMihnati
            0,                                   // NbrincoMihnati
            0,                                   // IDOffreN
            null,                                // IDMois
            null,                                // Datesselction
            0,                                   // Nbrinco
            0,                                   // Nbrincof
            0,                                   // ValidationDfp
            $data['nom_spec_custom_ar'] ?? null, // Nom_specialite_carte
            $data['qualification_dplm_id'] ?? 0, // IDqualification_dplm
            null,                                // Obs_Dfep
            null,                                // Obs_Central
            $data['nom_spec_custom_fr'] ?? null  // Nom_specialite_cartefr
        ]);
    }



    /**
     * Update an existing offer
     */
    public function updateOffre(array $data): bool
    {
        $stmt = $this->db->prepare("
            UPDATE offre
            SET IDSession = ?, IDSpecialite = ?, IDMode_formation = ?, DateD = ?, DateF = ?, nbrPrevision = ?,
                DateSelection = ?, DateVisiteMedical = ?, DateVisiteAtelier = ?, nbrGroupe = ?,
                encadrement = ?, Programme = ?, Equipement = ?, IDEts_FormM = ?, Obs = ?,
                IDqualification_dplm = ?,
                Nom_specialite_carte = ?, Nom_specialite_cartefr = ?
            WHERE IDOffre = ?
        ");
        return $stmt->execute([
            $data['session_id'], $data['specialite_id'], $data['mode'],
            $data['debut'], $data['fin'], $data['capacite'],
            $data['date_debut_selection'], $data['date_examen_medical'], $data['date_visite_ateliers'], $data['nbr_groupe'],
            $data['encadrement'], $data['programme'], $data['equipement'], $data['etablissement_delegue_id'], $data['obs'],
            $data['qualification_dplm_id'] ?? 0,
            $data['nom_spec_custom_ar'] ?? null,
            $data['nom_spec_custom_fr'] ?? null,
            $data['id']
        ]);
    }
}
