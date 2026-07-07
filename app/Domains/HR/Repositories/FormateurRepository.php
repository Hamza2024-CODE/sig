<?php

namespace App\Domains\HR\Repositories;

use App\Core\Database;
use PDO;

class FormateurRepository
{
    protected PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Fetch all formateurs from legacy 'encadrement' table.
     */
    public function findAllFormateurs(): array
    {
        $stmt = $this->db->query("
            SELECT IDEncadrement as id, Nom as nom, Prenom as prenom, Email as email, IDetablissement as etab_id 
            FROM encadrement
            ORDER BY Nom ASC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Query legacy 'emploitemp' table for the busy slots of a given formateur.
     */
    public function findBusySlotsByFormateur(int $formateurId): array
    {
        $stmt = $this->db->prepare("
            SELECT et.Jour as jour_num, et.Heured as heure_debut, et.Heuref as heure_fin, et.Obs as salle
            FROM emploitemp et
            JOIN section_semestre_module ssm ON et.IDsection_semestre_Module = ssm.IDsection_semestre_Module
            WHERE ssm.IDEncadrement = ?
        ");
        $stmt->execute([$formateurId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
