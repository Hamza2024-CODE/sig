<?php

namespace App\Domains\Finance\Repositories;

use App\Core\Database;
use PDO;

/**
 * FinanceRepository — Data Access Layer for Financial Management
 * Uses raw PDO (Database::getInstance()) consistent with the rest of the app.
 * Manages: poste_budgetaire, etablissement_grade, programme, sous_programme, fournisseur
 */
class FinanceRepository
{
    protected PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    // =========================================================================
    // SCOPE HELPERS
    // =========================================================================

    /**
     * Build WHERE conditions + params based on role scope.
     *  etabId > 0           → single establishment
     *  etabId = 0, dfepId>0 → all establishments of that wilaya (DFEP/central)
     *  etabId = 0, dfepId=0 → ALL (admin)
     *
     * @return array [string[] $conditions, mixed[] $params]
     */
    private function etabScope(int $etabId, int $dfepId, string $etabCol = 'IDetablissement'): array
    {
        if ($etabId > 0) {
            return [["$etabCol = ?"], [$etabId]];
        }
        if ($dfepId > 0) {
            return [["$etabCol IN (SELECT IDetablissement FROM etablissement WHERE IDDFEP = ?)"], [$dfepId]];
        }
        return [[], []]; // admin: no filter
    }

    /** Run a prepared or direct query depending on whether params are needed. */
    private function execScope(string $sql, array $params)
    {
        if ($params) {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        }
        return $this->db->query($sql);
    }

    // =========================================================================
    // SHARED FILTER DATA (Wilaya + Etablissement cascade)
    // =========================================================================

    /** All wilayas ordered by Num for the filter dropdown. */
    public function getWilayas(): array
    {
        return $this->db->query("
            SELECT IDWilayaa as id, Nom as nom, NomFr as nom_fr, Num as num
            FROM wilaya ORDER BY Num ASC
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * All establishments with their IDDFEP for client-side cascade.
     * Returns array grouped by IDDFEP for JSON encoding in the view.
     */
    public function getEtabsForFilter(): array
    {
        $rows = $this->db->query("
            SELECT IDetablissement as id, Nom as nom, IDDFEP as dfep_id
            FROM etablissement
            WHERE Nom IS NOT NULL AND Nom != ''
            ORDER BY IDDFEP, Nom ASC
        ")->fetchAll(PDO::FETCH_ASSOC);

        // Group by DFEP/Wilaya ID for JS cascade
        $grouped = [];
        foreach ($rows as $r) {
            $grouped[(int)$r['dfep_id']][] = ['id' => $r['id'], 'nom' => $r['nom']];
        }
        return $grouped;
    }

    // =========================================================================
    // BUDGET STATS (Summary Cards)
    // =========================================================================

    public function getStatsBudget(int $etabId, int $dfepId = 0): array
    {
        [$conds, $params] = $this->etabScope($etabId, $dfepId, 'eg.IDetablissement');
        $where = $conds ? 'WHERE ' . implode(' AND ', $conds) : '';
        $sql = "
            SELECT
                COALESCE(SUM(eg.allo), 0)             as total_postes,
                COALESCE(SUM(eg.Occu), 0)             as postes_occupes,
                COALESCE(SUM(eg.vacan), 0)            as postes_vacants,
                COALESCE(SUM(eg.Surplus), 0)          as surplus,
                COALESCE(SUM(eg.Besoin), 0)           as besoins,
                COALESCE(SUM(eg.Depenceannuel), 0)    as depense_annuelle,
                COALESCE(SUM(eg.Traitementannuel), 0) as traitement_annuel,
                COALESCE(SUM(eg.Primeetindemnites), 0) as primes_totales
            FROM etablissement_grade eg
            $where
        ";
        return $this->execScope($sql, $params)->fetch(PDO::FETCH_ASSOC) ?: [];
    }


    // =========================================================================
    // ETABLISSEMENT_GRADE — CRUD (رتب وشبكة الأجور بالمؤسسة)
    // =========================================================================

    public function getGradesEtablissement(int $etabId, int $page = 1, int $perPage = 25, array $filters = [], int $dfepId = 0): array
    {
        $offset = ($page - 1) * $perPage;
        // Build scope conditions
        [$conds, $params] = $this->etabScope($etabId, $dfepId, 'eg.IDetablissement');

        if (!empty($filters['categorie'])) {
            $conds[] = 'eg.categorie = ?';
            $params[] = $filters['categorie'];
        }
        if (!empty($filters['annee'])) {
            $conds[] = 'eg.IDannee = ?';
            $params[] = $filters['annee'];
        }
        if (!empty($filters['search'])) {
            $conds[] = '(g.Nom LIKE ? OR g.NomFr LIKE ?)';
            $params[] = '%' . $filters['search'] . '%';
            $params[] = '%' . $filters['search'] . '%';
        }
        $whereStr = $conds ? 'WHERE ' . implode(' AND ', $conds) : '';

        $stmt = $this->db->prepare("
            SELECT eg.IDetablissement_Grade as id, eg.IDGrade as grade_id,
                   g.Nom as grade_nom, g.NomFr as grade_nom_fr,
                   eg.Nbr as nbr_total, eg.NbrF as nbr_femmes,
                   eg.Occu as nbr_occupe, eg.vacan as nbr_vacant,
                   eg.Besoin as nbr_besoin, eg.Surplus as nbr_surplus,
                   eg.categorie, eg.IDannee as annee,
                   eg.Indice, eg.indiceinf, eg.indicemoy, eg.indicesup,
                   eg.Traitementannuel, eg.Primeetindemnites, eg.Depenceannuel,
                   eg.Validation, eg.ValidationDfp,
                   et.Nom as etablissement_nom
            FROM etablissement_grade eg
            LEFT JOIN grade g ON g.IDGrade = eg.IDGrade
            LEFT JOIN etablissement et ON eg.IDetablissement = et.IDetablissement
            $whereStr
            ORDER BY g.NumOrd ASC, eg.IDannee DESC
            LIMIT $perPage OFFSET $offset
        ");
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmtCount = $this->db->prepare("SELECT COUNT(*) FROM etablissement_grade eg LEFT JOIN grade g ON g.IDGrade = eg.IDGrade $whereStr");
        $stmtCount->execute($params);
        $total = (int)$stmtCount->fetchColumn();

        return ['data' => $rows, 'total' => $total, 'page' => $page, 'per_page' => $perPage, 'pages' => max(1, (int)ceil($total / $perPage))];
    }

    public function findGrade(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT eg.*, g.Nom as grade_nom, g.NomFr as grade_nom_fr FROM etablissement_grade eg LEFT JOIN grade g ON g.IDGrade=eg.IDGrade WHERE eg.IDetablissement_Grade=?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function insertGrade(array $d): int
    {
        $id = (int)($this->db->query("SELECT COALESCE(MAX(IDetablissement_Grade), 0) + 1 FROM etablissement_grade")->fetchColumn() ?: 1);
        $stmt = $this->db->prepare("
            INSERT INTO etablissement_grade
                (IDetablissement_Grade, IDGrade, IDetablissement, Nbr, NbrF, Occu, vacan, Besoin, Surplus,
                 categorie, IDannee, Indice, indiceinf, indicemoy, indicesup,
                 Traitementannuel, Primeetindemnites, Depenceannuel, Validation, ValidationDfp)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,0,0)
        ");
        $stmt->execute([
            $id,
            $d['grade_id'], $d['etab_id'],
            $d['nbr'] ?? 0,      $d['nbr_f'] ?? 0,
            $d['occupe'] ?? 0,   $d['vacant'] ?? 0,
            $d['besoin'] ?? 0,   $d['surplus'] ?? 0,
            $d['categorie'] ?? 'A',
            $d['annee'] ?? date('Y'),
            $d['indice'] ?? 0,   $d['indice_inf'] ?? 0,
            $d['indice_moy'] ?? 0, $d['indice_sup'] ?? 0,
            $d['traitement'] ?? 0.0,
            $d['primes'] ?? 0.0,
            $d['depense'] ?? 0.0,
        ]);
        return $id;
    }

    public function updateGrade(int $id, array $d): bool
    {
        $stmt = $this->db->prepare("
            UPDATE etablissement_grade
            SET Nbr=?, NbrF=?, Occu=?, vacan=?, Besoin=?, Surplus=?,
                categorie=?, IDannee=?, Indice=?, indiceinf=?, indicemoy=?, indicesup=?,
                Traitementannuel=?, Primeetindemnites=?, Depenceannuel=?
            WHERE IDetablissement_Grade=?
        ");
        return $stmt->execute([
            $d['nbr'] ?? 0,    $d['nbr_f'] ?? 0,
            $d['occupe'] ?? 0, $d['vacant'] ?? 0,
            $d['besoin'] ?? 0, $d['surplus'] ?? 0,
            $d['categorie'] ?? 'A',
            $d['annee'] ?? date('Y'),
            $d['indice'] ?? 0, $d['indice_inf'] ?? 0,
            $d['indice_moy'] ?? 0, $d['indice_sup'] ?? 0,
            $d['traitement'] ?? 0.0,
            $d['primes'] ?? 0.0,
            $d['depense'] ?? 0.0,
            $id
        ]);
    }

    public function deleteGrade(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM etablissement_grade WHERE IDetablissement_Grade=?");
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }

    // =========================================================================
    // PROGRAMMES & SOUS-PROGRAMMES — CRUD
    // =========================================================================

    public function getProgrammes(): array
    {
        $stmt = $this->db->query("
            SELECT p.IDProgramme as id, p.Nom as nom, p.NomFr as nom_fr,
                   p.Code as code, p.CodeComplet as code_complet,
                   p.NumOrd, p.anneaplication as annee,
                   COUNT(sp.IDSous_Programme) as nb_sous_programmes
            FROM programme p
            LEFT JOIN sous_programme sp ON sp.IDProgramme = p.IDProgramme
            WHERE p.desactive = 0 OR p.desactive IS NULL
            GROUP BY p.IDProgramme, p.Nom, p.NomFr, p.Code, p.CodeComplet, p.NumOrd, p.anneaplication
            ORDER BY p.NumOrd ASC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getSousProgrammes(int $programmeId = 0): array
    {
        if ($programmeId > 0) {
            $stmt = $this->db->prepare("SELECT IDSous_Programme as id, Nom as nom, NomFr as nom_fr, Code as code, CodeComplet as code_complet, IDProgramme as programme_id, NumOrd FROM sous_programme WHERE IDProgramme=? ORDER BY NumOrd");
            $stmt->execute([$programmeId]);
        } else {
            $stmt = $this->db->query("SELECT IDSous_Programme as id, Nom as nom, NomFr as nom_fr, Code as code, CodeComplet as code_complet, IDProgramme as programme_id, NumOrd FROM sous_programme ORDER BY IDProgramme, NumOrd");
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function insertProgramme(array $d): int
    {
        $id = (int)($this->db->query("SELECT COALESCE(MAX(IDProgramme), 0) + 1 FROM programme")->fetchColumn() ?: 1);
        $maxOrd = (int)($this->db->query("SELECT COALESCE(MAX(NumOrd),0) FROM programme")->fetchColumn() ?: 0) + 1;
        $stmt = $this->db->prepare("INSERT INTO programme (IDProgramme, Nom, NomFr, Code, IDPortefeuille, NumOrd, CodeComplet, anneaplication, desactive, Obs) VALUES (?,?,?,?,1,?,?,?,0,?)");
        $stmt->execute([$id, $d['nom'], $d['nom_fr']??'', $d['code']??'', $maxOrd, $d['code_complet']??'', $d['annee']??date('Y'), $d['obs']??'']);
        return $id;
    }

    public function updateProgramme(int $id, array $d): bool
    {
        $stmt = $this->db->prepare("UPDATE programme SET Nom=?,NomFr=?,Code=?,CodeComplet=?,anneaplication=?,Obs=? WHERE IDProgramme=?");
        return $stmt->execute([$d['nom'], $d['nom_fr']??'', $d['code']??'', $d['code_complet']??'', $d['annee']??date('Y'), $d['obs']??'', $id]);
    }

    public function deleteProgramme(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM programme WHERE IDProgramme=?");
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }

    public function insertSousProgramme(array $d): int
    {
        $id = (int)($this->db->query("SELECT COALESCE(MAX(IDSous_Programme), 0) + 1 FROM sous_programme")->fetchColumn() ?: 1);
        
        $ordStmt = $this->db->prepare("SELECT COALESCE(MAX(NumOrd),0) FROM sous_programme WHERE IDProgramme=?");
        $ordStmt->execute([$d['programme_id']]);
        $maxOrd = (int)$ordStmt->fetchColumn() + 1;

        $stmt = $this->db->prepare("INSERT INTO sous_programme (IDSous_Programme, Nom, NomFr, Code, IDProgramme, NumOrd, CodeComplet) VALUES (?,?,?,?,?,?,?)");
        $stmt->execute([$id, $d['nom'], $d['nom_fr']??'', $d['code']??'', $d['programme_id'], $maxOrd, $d['code_complet']??'']);
        return $id;
    }

    public function updateSousProgramme(int $id, array $d): bool
    {
        $stmt = $this->db->prepare("UPDATE sous_programme SET Nom=?,NomFr=?,Code=?,CodeComplet=? WHERE IDSous_Programme=?");
        return $stmt->execute([$d['nom'], $d['nom_fr']??'', $d['code']??'', $d['code_complet']??'', $id]);
    }

    public function deleteSousProgramme(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM sous_programme WHERE IDSous_Programme=?");
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }

    // =========================================================================
    // FOURNISSEURS — CRUD
    // =========================================================================

    public function getFournisseurs(string $search = '', int $page = 1, int $perPage = 25): array
    {
        $offset = ($page - 1) * $perPage;
        if ($search) {
            $stmt = $this->db->prepare("SELECT IDFournisseur as id, Nom as nom, NomFr as nom_fr FROM fournisseur WHERE Nom LIKE ? OR NomFr LIKE ? ORDER BY Nom LIMIT $perPage OFFSET $offset");
            $stmt->execute(["%$search%", "%$search%"]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $total = (int)$this->db->prepare("SELECT COUNT(*) FROM fournisseur WHERE Nom LIKE ? OR NomFr LIKE ?")->execute(["%$search%", "%$search%"]);
        } else {
            $stmt = $this->db->query("SELECT IDFournisseur as id, Nom as nom, NomFr as nom_fr FROM fournisseur ORDER BY Nom LIMIT $perPage OFFSET $offset");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $total = (int)$this->db->query("SELECT COUNT(*) FROM fournisseur")->fetchColumn();
        }
        return ['data' => $rows, 'total' => $total, 'page' => $page, 'per_page' => $perPage, 'pages' => max(1, (int)ceil($total / $perPage))];
    }

    public function insertFournisseur(array $d): int
    {
        $id = (int)($this->db->query("SELECT COALESCE(MAX(IDFournisseur), 0) + 1 FROM fournisseur")->fetchColumn() ?: 1);
        $stmt = $this->db->prepare("INSERT INTO fournisseur (IDFournisseur, Nom, NomFr) VALUES (?,?,?)");
        $stmt->execute([$id, $d['nom'], $d['nom_fr'] ?? '']);
        return $id;
    }

    public function updateFournisseur(int $id, array $d): bool
    {
        $stmt = $this->db->prepare("UPDATE fournisseur SET Nom=?, NomFr=? WHERE IDFournisseur=?");
        return $stmt->execute([$d['nom'], $d['nom_fr'] ?? '', $id]);
    }

    public function deleteFournisseur(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM fournisseur WHERE IDFournisseur=?");
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }

    // =========================================================================
    // REFERENCE DATA (Dropdowns)
    // =========================================================================

    public function getAllGrades(): array
    {
        $stmt = $this->db->query("SELECT IDGrade as id, Nom as nom, NomFr as nom_fr FROM grade ORDER BY NumOrd ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getModesRecrutement(): array
    {
        $stmt = $this->db->query("SELECT IDMode_Recrutement as id, Nom as nom, NomFr as nom_fr FROM mode_recrutement");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getDistinctAnnees(): array
    {
        $stmt = $this->db->query("SELECT DISTINCT IDannee as annee FROM etablissement_grade WHERE IDannee > 0 ORDER BY IDannee DESC LIMIT 15");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Legacy — kept for DashboardController compatibility
    public function getBoursesSummary(): array
    {
        try {
            $stmt = $this->db->query("SELECT COALESCE(COUNT(IDbourse),0) as total_stipends, COALESCE(SUM(Montant),0.0) as total_amount, COALESCE(SUM(neteapaye),0.0) as total_net_paid FROM bourse");
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: ['total_stipends'=>0,'total_amount'=>0,'total_net_paid'=>0];
        } catch (\Exception $e) {
            return ['total_stipends'=>0,'total_amount'=>0,'total_net_paid'=>0];
        }
    }

    public function getBudgetsList(): array
    {
        try {
            $stmt = $this->db->query("SELECT IDActions_titre as id, Nom as title_ar, NomFr as title_fr, COALESCE(AEOuvertes,0.0) as ae_ouvertes, COALESCE(CPOuvertes,0.0) as cp_ouvertes, COALESCE(CPinitiale,0.0) as cp_initiale, COALESCE(CPadditif,0.0) as cp_additif, COALESCE(validedfep,0) as is_validated_dfep, COALESCE(ValideCentral,0) as is_validated_central FROM Actions_titre ORDER BY IDActions_titre DESC LIMIT 50");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            return [];
        }
    }
    // =========================================================================
    // ADDITIONAL BUDGETS, OPERATIONS, BOURSES & STOCKS GETTERS/SETTERS
    // =========================================================================

    public function getBudgets(int $etabId, int $dfepId = 0): array
    {
        [$conds, $params] = $this->etabScope($etabId, $dfepId, 'b.IDetablissement');
        $where = $conds ? 'WHERE ' . implode(' AND ', $conds) : '';
        $sql = "SELECT b.IDBudget as id, b.Nom as nom, b.NomFr as nom_fr, b.IDannee as annee, b.code, b.AE, b.CP, et.Nom as etablissement_nom 
                FROM budget b 
                LEFT JOIN etablissement et ON b.IDetablissement = et.IDetablissement 
                $where 
                ORDER BY b.IDannee DESC LIMIT 200";
        return $this->execScope($sql, $params)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function insertBudget(array $d): int
    {
        $id = (int)($this->db->query("SELECT COALESCE(MAX(IDBudget),0)+1 FROM budget")->fetchColumn() ?: 1);
        $stmt = $this->db->prepare("INSERT INTO budget (IDBudget, Nom, NomFr, IDannee, IDetablissement, code, AE, CP, Encour) VALUES (?,?,?,?,?,?,?,?,1)");
        $stmt->execute([
            $id, $d['nom'], $d['nom_fr']??'', $d['annee']??date('Y'), $d['etab_id'], $d['code']??'', $d['ae']??0.0, $d['cp']??0.0
        ]);
        return $id;
    }

    public function getOperations(int $etabId, int $dfepId = 0): array
    {
        if ($etabId > 0) {
            $stmt = $this->db->prepare("
                SELECT o.IDOperations as id, o.Nom as nom, o.NomFr as nom_fr, o.APINI as ap_initiale, o.Num as numero,
                       o.DateInscription as date_inscription, o.TauxPhysique as taux_physique, o.Tauxfin as taux_financier,
                       o.MantEngagment as montant_engagement, o.Mantpayement as montant_paiement, o.Obs as observation,
                       et.Nom as etablissement_nom
                FROM operations o
                JOIN operations_etablissement oe ON o.IDOperations = oe.IDOperations
                LEFT JOIN etablissement et ON oe.IDetablissement = et.IDetablissement
                WHERE oe.IDetablissement = ?
                ORDER BY o.IDOperations DESC
            ");
            $stmt->execute([$etabId]);
        } elseif ($dfepId > 0) {
            $stmt = $this->db->prepare("
                SELECT o.IDOperations as id, o.Nom as nom, o.NomFr as nom_fr, o.APINI as ap_initiale, o.Num as numero,
                       o.DateInscription as date_inscription, o.TauxPhysique as taux_physique, o.Tauxfin as taux_financier,
                       o.MantEngagment as montant_engagement, o.Mantpayement as montant_paiement, o.Obs as observation,
                       et.Nom as etablissement_nom
                FROM operations o
                LEFT JOIN operations_etablissement oe ON o.IDOperations = oe.IDOperations
                LEFT JOIN etablissement et ON oe.IDetablissement = et.IDetablissement
                WHERE o.IDDFEP = ?
                ORDER BY o.IDOperations DESC LIMIT 200
            ");
            $stmt->execute([$dfepId]);
        } else {
            // Admin: all operations
            $stmt = $this->db->query("
                SELECT o.IDOperations as id, o.Nom as nom, o.NomFr as nom_fr, o.APINI as ap_initiale, o.Num as numero,
                       o.DateInscription as date_inscription, o.TauxPhysique as taux_physique, o.Tauxfin as taux_financier,
                       o.MantEngagment as montant_engagement, o.Mantpayement as montant_paiement, o.Obs as observation,
                       et.Nom as etablissement_nom
                FROM operations o
                LEFT JOIN operations_etablissement oe ON o.IDOperations = oe.IDOperations
                LEFT JOIN etablissement et ON oe.IDetablissement = et.IDetablissement
                ORDER BY o.IDOperations DESC LIMIT 500
            ");
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function insertOperation(array $d): int
    {
        $id = (int)($this->db->query("SELECT COALESCE(MAX(IDOperations),0)+1 FROM operations")->fetchColumn() ?: 1);
        $dfepStmt = $this->db->prepare("SELECT IDDFEP FROM etablissement WHERE IDetablissement = ? LIMIT 1");
        $dfepStmt->execute([$d['etab_id']]);
        $dfepId = (int)$dfepStmt->fetchColumn() ?: 1;

        $stmt = $this->db->prepare("
            INSERT INTO operations 
                (IDOperations, Nom, NomFr, APINI, Num, DateInscription, TauxPhysique, Tauxfin, 
                 MantEngagment, Mantpayement, Obs, IDDFEP, Validation, ValidationMin)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,0,0)
        ");
        $stmt->execute([
            $id, $d['nom'], $d['nom_fr']??'', $d['ap_initiale']??0.0, $d['numero']??'', 
            $d['date_inscription']??date('Y-m-d'), $d['taux_physique']??0.0, $d['taux_financier']??0.0,
            $d['montant_engagement']??0.0, $d['montant_paiement']??0.0, $d['observation']??'', $dfepId
        ]);

        // Also insert into junction table
        if (!empty($d['etab_id'])) {
            $oeId = (int)($this->db->query("SELECT COALESCE(MAX(IDOperations_Etablissement),0)+1 FROM operations_etablissement")->fetchColumn() ?: 1);
            $oeStmt = $this->db->prepare("INSERT INTO operations_etablissement (IDOperations_Etablissement, IDetablissement, IDOperations) VALUES (?,?,?)");
            $oeStmt->execute([$oeId, $d['etab_id'], $id]);
        }

        return $id;
    }

    public function getBourses(int $etabId, int $dfepId = 0): array
    {
        [$conds, $params] = $this->etabScope($etabId, $dfepId, 'o.IDEts_Form');
        $where = $conds ? 'AND ' . implode(' AND ', $conds) : '';
        $sql = "
            SELECT b.IDbourse as id, b.Montant as montant, b.dureepaye as duree_payee, b.neteapaye as net_paye,
                   b.datedebenga as date_debut, b.datefinenga as date_fin,
                   c.Nom as nom, c.Prenom as prenom, sp.Nom as specialite,
                   et.Nom as etablissement_nom, d.Nom as wilaya_nom
            FROM bourse b
            JOIN apprenant a ON b.IDapprenant = a.IDapprenant
            JOIN candidat c ON a.IDCandidat = c.IDCandidat
            LEFT JOIN offre o ON c.IDOffre = o.IDOffre
            LEFT JOIN specialite sp ON o.IDSpecialite = sp.IDSpecialite
            LEFT JOIN etablissement et ON o.IDEts_Form = et.IDetablissement
            LEFT JOIN dfep d ON et.IDDFEP = d.IDDFEP
            WHERE 1=1 $where
            ORDER BY b.IDbourse DESC
            LIMIT 200
        ";
        return $this->execScope($sql, $params)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getBoursesForExport(int $etabId, int $dfepId = 0): array
    {
        [$conds, $params] = $this->etabScope($etabId, $dfepId, 'o.IDEts_Form');
        $where = $conds ? 'AND ' . implode(' AND ', $conds) : '';
        $sql = "
            SELECT b.IDbourse as id, b.Montant as montant, b.dureepaye as duree_payee, b.neteapaye as net_paye,
                   b.datedebenga as date_debut, b.datefinenga as date_fin,
                   c.Nom as nom, c.Prenom as prenom, c.DateNais as date_naissance, c.Nin as nin,
                   a.Nccp as nccp, sp.Nom as specialite,
                   et.Nom as etablissement_nom, d.Nom as wilaya_nom
            FROM bourse b
            JOIN apprenant a ON b.IDapprenant = a.IDapprenant
            JOIN candidat c ON a.IDCandidat = c.IDCandidat
            LEFT JOIN offre o ON c.IDOffre = o.IDOffre
            LEFT JOIN specialite sp ON o.IDSpecialite = sp.IDSpecialite
            LEFT JOIN etablissement et ON o.IDEts_Form = et.IDetablissement
            LEFT JOIN dfep d ON et.IDDFEP = d.IDDFEP
            WHERE 1=1 $where
            ORDER BY b.IDbourse DESC
            LIMIT 50000
        ";
        return $this->execScope($sql, $params)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function insertBourse(array $d): int
    {
        $id = (int)($this->db->query("SELECT COALESCE(MAX(IDbourse),0)+1 FROM bourse")->fetchColumn() ?: 1);
        $stmt = $this->db->prepare("
            INSERT INTO bourse 
                (IDbourse, Montant, dureepaye, dureereste, IDapprenant, datedebenga, datefinenga, neteapaye)
            VALUES (?,?,?,?,?,?,?,?)
        ");
        $stmt->execute([
            $id, $d['montant']??0.0, $d['duree_payee']??1, 0, $d['apprenant_id'],
            date('Y-m-d'), date('Y-m-d', strtotime('+6 months')), $d['montant']??0.0
        ]);
        return $id;
    }

    public function getStocks(): array
    {
        $stmt = $this->db->query("SELECT IDArticlea as id, Nom as nom, NomFr as nom_fr, code, CONTENU_ART as contenu, Numord as num_ord FROM articlea ORDER BY Numord ASC LIMIT 100");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function insertStock(array $d): int
    {
        $id = (int)($this->db->query("SELECT COALESCE(MAX(IDArticlea),0)+1 FROM articlea")->fetchColumn() ?: 1);
        $stmt = $this->db->prepare("INSERT INTO articlea (IDArticlea, Nom, NomFr, code, CONTENU_ART, Numord) VALUES (?,?,?,?,?,?)");
        $stmt->execute([
            $id, $d['nom'], $d['nom_fr']??'', $d['code']??rand(1000, 9999), $d['contenu']??'', $d['num_ord']??1
        ]);
        return $id;
    }

    public function getEtablissementDetails(int $etabId): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM etablissement WHERE IDetablissement = ?");
        $stmt->execute([$etabId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function updateEtablissementDetails(int $etabId, array $d): bool
    {
        $stmt = $this->db->prepare("UPDATE etablissement SET Nom=?, NomFr=?, Code=?, Obs=?, Adresse=? WHERE IDetablissement=?");
        return $stmt->execute([
            $d['nom'], $d['nom_fr']??'', $d['code']??'', $d['obs']??'', $d['adresse']??'', $etabId
        ]);
    }

    public function getBoursesStats(int $etabId, int $dfepId = 0): array
    {
        if ($etabId === 0 && $dfepId === 0) {
            $sql = "SELECT COALESCE(COUNT(IDbourse), 0) as total, COALESCE(SUM(neteapaye), 0.0) as total_amount FROM bourse";
            return $this->db->query($sql)->fetch(PDO::FETCH_ASSOC) ?: ['total' => 0, 'total_amount' => 0.0];
        }

        [$conds, $params] = $this->etabScope($etabId, $dfepId, 'o.IDEts_Form');
        $where = $conds ? 'AND ' . implode(' AND ', $conds) : '';
        $sql = "
            SELECT 
                COALESCE(COUNT(b.IDbourse), 0) as total,
                COALESCE(SUM(b.neteapaye), 0.0) as total_amount
            FROM bourse b
            JOIN apprenant a ON b.IDapprenant = a.IDapprenant
            JOIN candidat c ON a.IDCandidat = c.IDCandidat
            LEFT JOIN offre o ON c.IDOffre = o.IDOffre
            WHERE 1=1 $where
        ";
        return $this->execScope($sql, $params)->fetch(PDO::FETCH_ASSOC) ?: ['total' => 0, 'total_amount' => 0.0];
    }

    public function getOperationsStats(int $etabId, int $dfepId = 0): array
    {
        if ($etabId > 0) {
            $sql = "
                SELECT COALESCE(COUNT(*), 0) as total, COALESCE(SUM(APINI), 0.0) as total_ap
                FROM operations
                WHERE IDDFEP = (SELECT IDDFEP FROM etablissement WHERE IDetablissement = ? LIMIT 1)
            ";
            $params = [$etabId];
        } elseif ($dfepId > 0) {
            $sql = "SELECT COALESCE(COUNT(*), 0) as total, COALESCE(SUM(APINI), 0.0) as total_ap FROM operations WHERE IDDFEP = ?";
            $params = [$dfepId];
        } else {
            $sql = "SELECT COALESCE(COUNT(*), 0) as total, COALESCE(SUM(APINI), 0.0) as total_ap FROM operations";
            $params = [];
        }
        return $this->execScope($sql, $params)->fetch(PDO::FETCH_ASSOC) ?: ['total' => 0, 'total_ap' => 0.0];
    }

    public function getBudgetsStats(int $etabId, int $dfepId = 0): array
    {
        [$conds, $params] = $this->etabScope($etabId, $dfepId, 'IDetablissement');
        $where = $conds ? 'WHERE ' . implode(' AND ', $conds) : '';
        $sql = "SELECT COALESCE(COUNT(*), 0) as total, COALESCE(SUM(CP), 0.0) as total_cp FROM budget $where";
        return $this->execScope($sql, $params)->fetch(PDO::FETCH_ASSOC) ?: ['total' => 0, 'total_cp' => 0.0];
    }

    public function getStocksStats(): int
    {
        $sql = "SELECT COUNT(*) FROM articlea";
        return (int) $this->db->query($sql)->fetchColumn();
    }

    public function getProgrammesStats(): int
    {
        $sql = "SELECT COUNT(*) FROM programme WHERE desactive = 0 OR desactive IS NULL";
        return (int) $this->db->query($sql)->fetchColumn();
    }

    public function getApprenantsList(int $etabId, int $dfepId = 0): array
    {
        [$conds, $params] = $this->etabScope($etabId, $dfepId, 'o.IDEts_Form');
        $where = $conds ? 'AND ' . implode(' AND ', $conds) : '';
        $sql = "
            SELECT a.IDapprenant as id, c.Nom as nom, c.Prenom as prenom
            FROM apprenant a
            JOIN candidat c ON a.IDCandidat = c.IDCandidat
            LEFT JOIN offre o ON c.IDOffre = o.IDOffre
            WHERE 1=1 $where
            ORDER BY c.Nom, c.Prenom
            LIMIT 500
        ";
        return $this->execScope($sql, $params)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getBoursesWilayaStats(int $dfepId = 0, int $etabId = 0): array
    {
        if ($etabId > 0) {
            $sql = "
                SELECT 
                    et.Nom as wilaya_nom,
                    COUNT(a.IDapprenant) as total_apprenants,
                    SUM(CASE WHEN b.has_bourse = 1 THEN 1 ELSE 0 END) as paid_bourses,
                    ROUND((SUM(CASE WHEN b.has_bourse = 1 THEN 1 ELSE 0 END) * 100.0) / NULLIF(COUNT(a.IDapprenant), 0), 1) as percentage
                FROM apprenant a
                JOIN section s ON a.IDSection = s.IDSection
                JOIN offre o ON s.IDOffre = o.IDOffre
                JOIN session sess ON o.IDSession = sess.IDSession
                JOIN specialite sp ON o.IDSpecialite = sp.IDSpecialite
                LEFT JOIN etablissement et ON o.IDEts_Form = et.IDetablissement
                LEFT JOIN (SELECT IDapprenant, 1 as has_bourse FROM bourse GROUP BY IDapprenant) b ON a.IDapprenant = b.IDapprenant
                LEFT JOIN apprenant_fin af ON a.IDapprenant = af.IDapprenant
                WHERE et.IDetablissement = ?
                  AND a.statut = 'actif'
                  AND af.IDapprenant IS NULL
                  AND DATE_ADD(sess.DateD, INTERVAL COALESCE(NULLIF(sp.dureeM, 0), sp.NbrSem * 6, 24) MONTH) >= CURRENT_DATE()
                GROUP BY et.Nom, et.IDetablissement
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$etabId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        if ($dfepId > 0) {
            $sql = "
                SELECT 
                    et.Nom as wilaya_nom,
                    COUNT(a.IDapprenant) as total_apprenants,
                    SUM(CASE WHEN b.has_bourse = 1 THEN 1 ELSE 0 END) as paid_bourses,
                    ROUND((SUM(CASE WHEN b.has_bourse = 1 THEN 1 ELSE 0 END) * 100.0) / NULLIF(COUNT(a.IDapprenant), 0), 1) as percentage
                FROM apprenant a
                JOIN section s ON a.IDSection = s.IDSection
                JOIN offre o ON s.IDOffre = o.IDOffre
                JOIN session sess ON o.IDSession = sess.IDSession
                JOIN specialite sp ON o.IDSpecialite = sp.IDSpecialite
                LEFT JOIN etablissement et ON o.IDEts_Form = et.IDetablissement
                LEFT JOIN (SELECT IDapprenant, 1 as has_bourse FROM bourse GROUP BY IDapprenant) b ON a.IDapprenant = b.IDapprenant
                LEFT JOIN apprenant_fin af ON a.IDapprenant = af.IDapprenant
                WHERE et.IDDFEP = ?
                  AND a.statut = 'actif'
                  AND af.IDapprenant IS NULL
                  AND DATE_ADD(sess.DateD, INTERVAL COALESCE(NULLIF(sp.dureeM, 0), sp.NbrSem * 6, 24) MONTH) >= CURRENT_DATE()
                GROUP BY et.Nom, et.IDetablissement
                ORDER BY et.Nom
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$dfepId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        $sql = "
            SELECT 
                COALESCE(d.Nom, 'غير محدد') as wilaya_nom,
                COUNT(a.IDapprenant) as total_apprenants,
                SUM(CASE WHEN b.has_bourse = 1 THEN 1 ELSE 0 END) as paid_bourses,
                ROUND((SUM(CASE WHEN b.has_bourse = 1 THEN 1 ELSE 0 END) * 100.0) / NULLIF(COUNT(a.IDapprenant), 0), 1) as percentage
            FROM apprenant a
            JOIN section s ON a.IDSection = s.IDSection
            JOIN offre o ON s.IDOffre = o.IDOffre
            JOIN session sess ON o.IDSession = sess.IDSession
            JOIN specialite sp ON o.IDSpecialite = sp.IDSpecialite
            LEFT JOIN etablissement et ON o.IDEts_Form = et.IDetablissement
            LEFT JOIN dfep d ON et.IDDFEP = d.IDDFEP
            LEFT JOIN (SELECT IDapprenant, 1 as has_bourse FROM bourse GROUP BY IDapprenant) b ON a.IDapprenant = b.IDapprenant
            LEFT JOIN apprenant_fin af ON a.IDapprenant = af.IDapprenant
            WHERE a.statut = 'actif'
              AND af.IDapprenant IS NULL
              AND DATE_ADD(sess.DateD, INTERVAL COALESCE(NULLIF(sp.dureeM, 0), sp.NbrSem * 6, 24) MONTH) >= CURRENT_DATE()
            GROUP BY d.Nom, d.IDDFEP
            ORDER BY d.Nom
        ";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getEmployeesWilayaStats(int $dfepId = 0, int $etabId = 0): array
    {
        if ($etabId > 0) {
            $sql = "
                SELECT 
                    et.Nom as wilaya_nom,
                    COUNT(e.IDEncadrement) as total_employees,
                    SUM(CASE WHEN e.MotDePass IS NOT NULL AND e.MotDePass != '' THEN 1 ELSE 0 END) as active_accounts,
                    ROUND((SUM(CASE WHEN e.MotDePass IS NOT NULL AND e.MotDePass != '' THEN 1 ELSE 0 END) * 100.0) / NULLIF(COUNT(e.IDEncadrement), 0), 1) as percentage
                FROM encadrement e
                JOIN etablissement et ON e.IDetablissement = et.IDetablissement
                WHERE et.IDetablissement = ?
                GROUP BY et.Nom, et.IDetablissement
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$etabId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        if ($dfepId > 0) {
            $sql = "
                SELECT 
                    et.Nom as wilaya_nom,
                    COUNT(e.IDEncadrement) as total_employees,
                    SUM(CASE WHEN e.MotDePass IS NOT NULL AND e.MotDePass != '' THEN 1 ELSE 0 END) as active_accounts,
                    ROUND((SUM(CASE WHEN e.MotDePass IS NOT NULL AND e.MotDePass != '' THEN 1 ELSE 0 END) * 100.0) / NULLIF(COUNT(e.IDEncadrement), 0), 1) as percentage
                FROM encadrement e
                JOIN etablissement et ON e.IDetablissement = et.IDetablissement
                WHERE et.IDDFEP = ?
                GROUP BY et.Nom, et.IDetablissement
                ORDER BY et.Nom
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$dfepId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        $sql = "
            SELECT 
                COALESCE(d.Nom, 'غير محدد') as wilaya_nom,
                COUNT(e.IDEncadrement) as total_employees,
                SUM(CASE WHEN e.MotDePass IS NOT NULL AND e.MotDePass != '' THEN 1 ELSE 0 END) as active_accounts,
                ROUND((SUM(CASE WHEN e.MotDePass IS NOT NULL AND e.MotDePass != '' THEN 1 ELSE 0 END) * 100.0) / NULLIF(COUNT(e.IDEncadrement), 0), 1) as percentage
            FROM encadrement e
            JOIN etablissement et ON e.IDetablissement = et.IDetablissement
            JOIN dfep d ON et.IDDFEP = d.IDDFEP
            GROUP BY d.Nom, d.IDDFEP
            ORDER BY d.Nom
        ";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
}
