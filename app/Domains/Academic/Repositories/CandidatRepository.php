<?php

namespace App\Domains\Academic\Repositories;

use App\Core\Database;
use PDO;

/**
 * CandidatRepository
 *
 * Wraps all RAW SQL against the WinDev `candidat` table.
 * Schema is frozen — SQL must NEVER be rewritten.
 */
class CandidatRepository
{
    protected PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    // ─── Read ─────────────────────────────────────────────────────────────────

    /**
     * Fetch paginated list of candidats with role-based WHERE clause injected.
     *
     * @param  string $extraWhere  Optional " AND ..." clause built by the service
     * @param  array  $params      Positional parameters for the extra WHERE
     * @param  string $statusFilter  'all' | 'pending' | 'approved' | 'rejected'
     * @param  int    $limit
     * @return array
     */
    public function findAllFiltered(
        string $extraWhere,
        array  $params,
        string $statusFilter = 'all',
        int    $limit        = 500
    ): array {
        $statusWhere = '';

        if ($statusFilter === 'pending') {
            $statusWhere = " AND (c.Validation IS NULL OR c.Validation = 0)";
        } elseif ($statusFilter === 'approved') {
            $statusWhere = " AND c.Validation = 1";
        } elseif ($statusFilter === 'rejected') {
            $statusWhere = " AND c.Validation = 2";
        }

        $sql = "
            SELECT c.IDCandidat as candidat_id, c.Nom as nom_ar, c.Prenom as prenom_ar,
                   c.NomFr as nom_fr, c.PrenomFr as prenom_fr, c.Nin as nin, c.Civ as sexe, c.Tel as telephone,
                   c.DateNais as date_naissance, c.LieuNais as lieu_naissance,
                   c.IDCandidat as pre_inscr_id, c.NumIns as numero_inscription,
                   c.Validation, c.Obs as motif_refus,
                   o.IDMode_formation as mode_formation,
                   s.Nom as specialite_ar,
                   ef.Nom as etab_nom,
                   sess.Nom as session_nom
            FROM candidat c
            JOIN offre o ON c.IDOffre = o.IDOffre
            JOIN specialite s ON o.IDSpecialite = s.IDSpecialite
            JOIN etablissement ef ON o.IDEts_Form = ef.IDetablissement
            JOIN session sess ON o.IDSession = sess.IDSession
            WHERE 1=1
            {$extraWhere}
            {$statusWhere}
            ORDER BY c.IDCandidat DESC
            LIMIT {$limit}
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Memory-safe Generator for streaming candidat datasets to ReportingService.
     * Fetches in chunks of $chunkSize — peak RAM = O(chunkSize).
     * SQL preserved exactly from findAllFiltered().
     *
     * @param  string $extraWhere   Role-scoped WHERE fragment
     * @param  array  $params       Positional params for extraWhere
     * @param  string $statusFilter 'all' | 'pending' | 'approved' | 'rejected'
     * @param  int    $chunkSize    Rows per DB round-trip (default 200)
     * @return \Generator           Yields one enriched row array at a time
     */
    public function streamCandidatsChunked(
        string $extraWhere,
        array  $params,
        string $statusFilter = 'all',
        int    $chunkSize    = 200
    ): \Generator {
        $statusWhere = '';
        if ($statusFilter === 'pending') {
            $statusWhere = " AND (c.Validation IS NULL OR c.Validation = 0)";
        } elseif ($statusFilter === 'approved') {
            $statusWhere = " AND c.Validation = 1";
        } elseif ($statusFilter === 'rejected') {
            $statusWhere = " AND c.Validation = 2";
        }

        $offset = 0;
        do {
            $chunkParams = array_merge($params, [$chunkSize, $offset]);
            $stmt = $this->db->prepare("
                SELECT c.IDCandidat as candidat_id, c.Nom as nom_ar, c.Prenom as prenom_ar,
                       c.NomFr as nom_fr, c.PrenomFr as prenom_fr, c.Nin as nin, c.Civ as sexe,
                       c.Tel as telephone, c.DateNais as date_naissance, c.LieuNais as lieu_naissance,
                       c.NumIns as numero_inscription,
                       c.Validation, c.Obs as motif_refus,
                       o.IDMode_formation as mode_formation,
                       s.Nom as specialite_ar,
                       ef.Nom as etab_nom,
                       sess.Nom as session_nom
                FROM candidat c
                JOIN offre o ON c.IDOffre = o.IDOffre
                JOIN specialite s ON o.IDSpecialite = s.IDSpecialite
                JOIN etablissement ef ON o.IDEts_Form = ef.IDetablissement
                JOIN session sess ON o.IDSession = sess.IDSession
                JOIN semestre_formation sf ON sess.IDSemestre_formation = sf.IDSemestre_formation
                WHERE 1=1
                  AND sf.IDAnnee_Formation >= 19
                {$extraWhere}
                {$statusWhere}
                ORDER BY c.IDCandidat DESC
                LIMIT ? OFFSET ?
            ");
            $stmt->execute($chunkParams);
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            foreach ($rows as $row) {
                // Compute decision label for export column
                $row['decision'] = match((int)($row['Validation'] ?? 0)) {
                    1       => 'مقبول',
                    2       => 'غير مقبول',
                    default => 'قيد الانتظار',
                };
                yield $row;
            }

            $offset += $chunkSize;
        } while (count($rows) === $chunkSize);
    }

    /**
     * Find a single candidat by primary key
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT c.IDCandidat as candidat_id, c.Nom as nom_ar, c.Prenom as prenom_ar,
                   c.NomFr as nom_fr, c.PrenomFr as prenom_fr, c.Nin as nin, c.Civ as sexe,
                   c.Tel as telephone, c.NumIns as numero_inscription,
                   c.Validation, c.Obs as motif_refus, c.IDOffre
            FROM candidat c
            WHERE c.IDCandidat = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Check if an apprenant record already exists for this candidat
     */
    public function existsApprenant(PDO $pdo, int $candidatId): bool
    {
        $stmt = $pdo->prepare("SELECT IDapprenant FROM apprenant WHERE IDCandidat = ?");
        $stmt->execute([$candidatId]);
        return (bool)$stmt->fetch();
    }

    // ─── Write ────────────────────────────────────────────────────────────────

    /**
     * Update the Validation status and optional rejection reason
     * SQL preserved exactly as in legacy CandidateController::action()
     */
    public function updateValidation(PDO $pdo, int $candidatId, int $status, ?string $obs): bool
    {
        $stmt = $pdo->prepare("
            UPDATE candidat
            SET Validation = ?, Obs = ?, ValidationDfp = 0
            WHERE IDCandidat = ?
        ");
        return $stmt->execute([$status, $obs, $candidatId]);
    }

    /**
     * Insert a new apprenant record when a candidat is approved.
     * SQL preserved exactly as in legacy CandidateController::action()
     */
    public function insertApprenant(PDO $pdo, int $candidatId, string $matricule): bool
    {
        $stmt = $pdo->prepare("
            INSERT INTO apprenant (IDCandidat, Nccp, active, IDSection)
            VALUES (?, ?, 1, 0)
        ");
        return $stmt->execute([$candidatId, $matricule]);
    }
}
