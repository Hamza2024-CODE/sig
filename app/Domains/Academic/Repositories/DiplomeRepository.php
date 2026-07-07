<?php

namespace App\Domains\Academic\Repositories;

use App\Core\Database;
use PDO;

class DiplomeRepository
{
    protected PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Retrieve trainee profile details
     */
    public function findTraineeDetails(int $stagiaireId, string $etabFilter, array $params): ?array
    {
        $sql = "
            SELECT a.IDapprenant as id, a.Nccp as numero_matricule, a.IDSection as section_id,
                   c.Nom as nom_ar, c.Prenom as prenom_ar, c.NomFr as nom_fr, c.PrenomFr as prenom_fr, '2000-01-01' as date_naissance,
                   o.IDSpecialite as specialite_id, o.IDEts_Form as etablissement_id, 
                   sp.Nom as spec_ar, sp.NomFr as spec_fr
            FROM apprenant a
            JOIN candidat c ON a.IDCandidat = c.IDCandidat
            JOIN section s ON a.IDSection = s.IDSection
            JOIN offre o ON s.IDOffre = o.IDOffre
            JOIN specialite sp ON o.IDSpecialite = sp.IDSpecialite
            WHERE a.IDapprenant = ? " . $etabFilter . "
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Get the latest semester average
     */
    public function getLatestSemesterAverage(int $traineeId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT IDapprenant_Section_semstre, MoyApr 
            FROM apprenant_section_semstre 
            WHERE IDapprenant = ? 
            ORDER BY IDapprenant_Section_semstre DESC LIMIT 1
        ");
        $stmt->execute([$traineeId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Get all semester averages for a student
     */
    public function getAllSemesterAverages(int $traineeId): array
    {
        $stmt = $this->db->prepare("
            SELECT IDapprenant_Section_semstre, MoyApr, MoyAvr 
            FROM apprenant_section_semstre 
            WHERE IDapprenant = ?
        ");
        $stmt->execute([$traineeId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Get the latest section semester ID
     */
    public function getLatestSectionSemester(int $sectionId): ?int
    {
        $stmt = $this->db->prepare("
            SELECT IDSection_Semestre FROM section_semestre 
            WHERE IDSection = ? 
            ORDER BY NumSem DESC LIMIT 1
        ");
        $stmt->execute([$sectionId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? (int)$row['IDSection_Semestre'] : null;
    }

    /**
     * Check if a diploma already exists for this trainee inside a transaction with locking
     */
    public function checkExistingDiplomaForUpdate(PDO $pdo, int $traineeId): ?array
    {
        $stmt = $pdo->prepare("SELECT * FROM apprenant_fin WHERE IDapprenant = ? FOR UPDATE");
        $stmt->execute([$traineeId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Get next primary key ID securely under transaction lock
     */
    public function getNextIdWithLock(PDO $pdo): int
    {
        $stmt = $pdo->query("SELECT COALESCE(MAX(IDApprenant_Fin), 0) as max_id FROM apprenant_fin FOR UPDATE");
        $row = $stmt->fetch();
        return ($row['max_id'] !== null) ? (int)$row['max_id'] + 1 : 1;
    }

    /**
     * Update an existing diploma record inside transaction
     */
    public function updateDiploma(PDO $pdo, string $num, float $moy, int $mention, ?int $ssId, ?int $secSemId, int $traineeId): bool
    {
        $stmt = $pdo->prepare("
            UPDATE apprenant_fin 
            SET Numdiplome = ?, MoyGen = ?, DateDiplome = CURRENT_DATE, IDmention = ?, IDapprenant_Section_semstre = ?, IDDecision_evalf = NULL, IDSection_Semestre = ?, update_time = CURRENT_DATE, data_sync_time = CURRENT_DATE
            WHERE IDapprenant = ?
        ");
        return $stmt->execute([$num, $moy, $mention, $ssId, $secSemId, $traineeId]);
    }

    /**
     * Insert a new diploma record inside transaction
     */
    public function insertDiploma(PDO $pdo, int $id, int $traineeId, ?int $ssId, string $num, float $moy, int $mention, ?int $secSemId): bool
    {
        $stmt = $pdo->prepare("
            INSERT INTO apprenant_fin (IDApprenant_Fin, IDapprenant, IDapprenant_Section_semstre, Numdiplome, MoyGen, DateDiplome, IDmention, IDDecision_evalf, IDSection_Semestre, create_time, update_time, data_sync_time)
            VALUES (?, ?, ?, ?, ?, CURRENT_DATE, ?, NULL, ?, CURRENT_DATE, CURRENT_DATE, CURRENT_DATE)
        ");
        return $stmt->execute([$id, $traineeId, $ssId, $num, $moy, $mention, $secSemId]);
    }

    /**
     * Check if a mention exists
     */
    public function checkMentionExists(int $id): bool
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM mention WHERE IDmention = ?");
        $stmt->execute([$id]);
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Seed standard mentions
     */
    public function seedStandardMentions(): void
    {
        $stmt = $this->db->prepare("INSERT IGNORE INTO mention (IDmention, Nom, NomFr) VALUES (?, ?, ?)");
        $stmt->execute([1, 'مقبول', 'Passable']);
        $stmt->execute([2, 'قريب من الحسن', 'Assez Bien']);
        $stmt->execute([3, 'حسن', 'Bien']);
        $stmt->execute([4, 'حسن جدا', 'Très Bien']);
    }
}
