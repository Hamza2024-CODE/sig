<?php

namespace App\Domains\HR\Repositories;

use App\Core\Database;
use PDO;

class RHRepository
{
    protected PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    private function etabScope(int $etabId, int $dfepId, string $col = 'IDetablissement'): array
    {
        if ($etabId > 0) return [["$col = ?"], [$etabId]];
        if ($dfepId > 0) return [["$col IN (SELECT IDetablissement FROM etablissement WHERE IDDFEP = ?)"], [$dfepId]];
        return [[], []];
    }

    private function execScope(string $sql, array $params)
    {
        if ($params) { $s = $this->db->prepare($sql); $s->execute($params); return $s; }
        return $this->db->query($sql);
    }

    public function getPersonnel(int $etabId, int $dfepId = 0): array
    {
        [$conds, $params] = $this->etabScope($etabId, $dfepId, 'e.IDetablissement');
        $where = $conds ? 'AND ' . implode(' AND ', $conds) : '';
        $sql = "
            SELECT e.IDEncadrement as id, e.Nom as nom, e.Prenom as prenom, e.Specialite as specialite,
                   e.Daterecr as date_recrutement, e.nin, em.photo, g.Nom as grade, f.Nom as fonction,
                   ns.Nom as niveau_scol_nom
            FROM encadrement e
            LEFT JOIN (
                SELECT IDEncadrement, MIN(photo) as photo 
                FROM encadremen_memo 
                WHERE photo IS NOT NULL AND photo <> '' 
                GROUP BY IDEncadrement
            ) em ON e.IDEncadrement = em.IDEncadrement
            LEFT JOIN grade g ON e.IDGrade = g.IDGrade
            LEFT JOIN fonctions f ON e.IDFonctions = f.IDFonctions
            LEFT JOIN niveau_scol_enca ns ON e.IDNiveau_Scol_enca = ns.IDNiveau_Scol_enca
            WHERE 1=1 $where
            ORDER BY e.IDEncadrement DESC LIMIT 500
        ";
        return $this->execScope($sql, $params)->fetchAll(PDO::FETCH_ASSOC);
    }



    /** Returns the REAL total count (ignores LIMIT used in getPersonnel) */
    public function getPersonnelCount(int $etabId, int $dfepId = 0): int
    {
        [$conds, $params] = $this->etabScope($etabId, $dfepId, 'e.IDetablissement');
        $where = $conds ? 'AND ' . implode(' AND ', $conds) : '';
        $sql = "SELECT COUNT(*) FROM encadrement e WHERE 1=1 $where";
        return (int) $this->execScope($sql, $params)->fetchColumn();
    }


    public function insertPersonnel(array $d): int
    {
        $id = (int)($this->db->query("SELECT COALESCE(MAX(IDEncadrement), 0) + 1 FROM encadrement")->fetchColumn() ?: 1);
        $stmt = $this->db->prepare("
            INSERT INTO encadrement 
                (IDEncadrement, IDetablissement, Nom, Prenom, Specialite, Daterecr, nin, photo, IDGrade, IDFonctions, IDMode_Recrutement, IDSituationAdministrat, DateInstall, IDNiveau_Scol_enca)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $id,
            $d['etab_id'],
            $d['nom'],
            $d['prenom'],
            $d['specialite'] ?? '',
            $d['date_recrutement'] ?? date('Y-m-d'),
            $d['nin'] ?? '',
            $d['photo'] ?? '',
            $d['grade_id'] ?? 1,
            $d['fonction_id'] ?? 1,
            $d['mode_recrutement_id'] ?? 1,
            $d['situation_id'] ?? 1,
            $d['date_installation'] ?? date('Y-m-d'),
            $d['niveau_scol_id'] ?? null
        ]);
        return $id;
    }

    public function getRecrutements(int $etabId, int $dfepId = 0): array
    {
        [$conds, $params] = $this->etabScope($etabId, $dfepId, 'e.IDetablissement');
        $where = $conds ? 'AND ' . implode(' AND ', $conds) : '';
        $sql = "
            SELECT e.IDEncadrement as id, e.Nom as nom, e.Prenom as prenom, e.Daterecr as date_recrutement,
                   g.Nom as grade, mr.Nom as mode_recrutement
            FROM encadrement e
            LEFT JOIN grade g ON e.IDGrade = g.IDGrade
            LEFT JOIN mode_recrutement mr ON e.IDMode_Recrutement = mr.IDMode_Recrutement
            WHERE 1=1 $where
            ORDER BY e.Daterecr DESC LIMIT 500
        ";
        return $this->execScope($sql, $params)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getFormations(int $etabId, int $dfepId = 0): array
    {
        [$conds, $params] = $this->etabScope($etabId, $dfepId, 'e.IDetablissement');
        $where = $conds ? 'AND ' . implode(' AND ', $conds) : '';
        $sql = "
            SELECT s.IDStagePerfectionnemnt as id, e.Nom as nom, e.Prenom as prenom,
                   s.theme, s.DateD as date_debut, s.DateF as date_fin, s.EtablissemntFormarion as etablissement_formation
            FROM stageperfectionnemnt s
            JOIN encadrement e ON s.IDEncadrement = e.IDEncadrement
            WHERE 1=1 $where
            ORDER BY s.IDStagePerfectionnemnt DESC LIMIT 200
        ";
        return $this->execScope($sql, $params)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function insertFormation(array $d): int
    {
        $id = (int)($this->db->query("SELECT COALESCE(MAX(IDStagePerfectionnemnt), 0) + 1 FROM stageperfectionnemnt")->fetchColumn() ?: 1);
        $stmt = $this->db->prepare("
            INSERT INTO stageperfectionnemnt 
                (IDStagePerfectionnemnt, IDEncadrement, theme, DateD, DateF, EtablissemntFormarion, NumAttestation, DateAttestation, typeStage)
            VALUES (?, ?, ?, ?, ?, ?, '', ?, 1)
        ");
        $stmt->execute([
            $id,
            $d['encadrement_id'],
            $d['theme'],
            $d['date_debut'] ?? date('Y-m-d'),
            $d['date_fin'] ?? date('Y-m-d'),
            $d['etablissement_formation'] ?? '',
            $d['date_attestation'] ?? date('Y-m-d')
        ]);
        return $id;
    }

    public function getActivites(int $etabId, int $dfepId = 0): array
    {
        [$conds, $params] = $this->etabScope($etabId, $dfepId);
        $where = $conds ? 'WHERE ' . implode(' AND ', $conds) : '';
        $sql = "SELECT IDActivite as id, Nom as nom, NomFr as nom_fr, DateDeb as date_debut, DateFIn as date_fin, Lieu as lieu, Description as description FROM activite $where ORDER BY DateDeb DESC LIMIT 200";
        return $this->execScope($sql, $params)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function insertActivite(array $d): int
    {
        $id = (int)($this->db->query("SELECT COALESCE(MAX(IDActivite), 0) + 1 FROM activite")->fetchColumn() ?: 1);
        $stmt = $this->db->prepare("
            INSERT INTO activite 
                (IDActivite, IDetablissement, Nom, NomFr, DateDeb, DateFIn, Lieu, Description, Duree, IDActiviteType, Encour)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, 1, 1)
        ");
        $stmt->execute([
            $id, $d['etab_id'], $d['nom'], $d['nom_fr']??'', $d['date_debut']??date('Y-m-d'), $d['date_fin']??date('Y-m-d'), $d['lieu']??'', $d['description']??''
        ]);
        return $id;
    }

    public function getCompetances(int $etabId, int $dfepId = 0): array
    {
        [$conds, $params] = $this->etabScope($etabId, $dfepId, 'c.IDEtablissement');
        $where = $conds ? 'AND ' . implode(' AND ', $conds) : '';
        $sql = "
            SELECT c.IDCompetance as id, c.nomprn as nom_prenom, c.DateNais as date_naissance,
                   c.grade, c.diplome, c.specialite, c.Tel as tel, c.etablissemnt, c.Wilaya as wilaya
            FROM competance c
            WHERE 1=1 $where
            ORDER BY c.IDCompetance DESC LIMIT 500
        ";
        return $this->execScope($sql, $params)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getCompetancesCount(int $etabId, int $dfepId = 0): int
    {
        [$conds, $params] = $this->etabScope($etabId, $dfepId, 'c.IDEtablissement');
        $where = $conds ? 'AND ' . implode(' AND ', $conds) : '';
        $sql = "SELECT COUNT(*) FROM competance c WHERE 1=1 $where";
        return (int) $this->execScope($sql, $params)->fetchColumn();
    }

    public function insertCompetance(array $d): int
    {
        $id = (int)($this->db->query("SELECT COALESCE(MAX(IDCompetance), 0) + 1 FROM competance")->fetchColumn() ?: 1);
        
        $etab = $this->db->prepare("SELECT Nom, IDDFEP FROM etablissement WHERE IDetablissement = ?");
        $etab->execute([$d['etab_id']]);
        $etabData = $etab->fetch(PDO::FETCH_ASSOC);
        $etabName = $etabData['Nom'] ?? '';
        $dfepId = (int)($etabData['IDDFEP'] ?? 0);
        
        $wilayaName = '';
        if ($dfepId > 0) {
            $w = $this->db->prepare("SELECT Nom FROM wilaya WHERE IDWilayaa = (SELECT IDWilayaa FROM dfep WHERE IDDFEP = ?)");
            $w->execute([$dfepId]);
            $wilayaName = $w->fetchColumn() ?: '';
        }

        $stmt = $this->db->prepare("
            INSERT INTO competance 
                (IDCompetance, nomprn, grade, diplome, specialite, Tel, DateNais, IDEtablissement, IDDFEP, etablissemnt, Wilaya)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $id,
            $d['nom_prenom'],
            $d['grade'] ?? '',
            $d['diplome'] ?? '',
            $d['specialite'] ?? '',
            $d['tel'] ?? '',
            $d['date_naissance'] ?? '',
            $d['etab_id'],
            $dfepId,
            $etabName,
            $wilayaName
        ]);
        return $id;
    }

    public function updateCompetance(int $id, array $d): bool
    {
        $etab = $this->db->prepare("SELECT Nom, IDDFEP FROM etablissement WHERE IDetablissement = ?");
        $etab->execute([$d['etab_id']]);
        $etabData = $etab->fetch(PDO::FETCH_ASSOC);
        $etabName = $etabData['Nom'] ?? '';
        $dfepId = (int)($etabData['IDDFEP'] ?? 0);
        
        $wilayaName = '';
        if ($dfepId > 0) {
            $w = $this->db->prepare("SELECT Nom FROM wilaya WHERE IDWilayaa = (SELECT IDWilayaa FROM dfep WHERE IDDFEP = ?)");
            $w->execute([$dfepId]);
            $wilayaName = $w->fetchColumn() ?: '';
        }

        $stmt = $this->db->prepare("
            UPDATE competance SET
                nomprn = ?, grade = ?, diplome = ?, specialite = ?, Tel = ?, DateNais = ?, IDEtablissement = ?, IDDFEP = ?, etablissemnt = ?, Wilaya = ?
            WHERE IDCompetance = ?
        ");
        return $stmt->execute([
            $d['nom_prenom'],
            $d['grade'] ?? '',
            $d['diplome'] ?? '',
            $d['specialite'] ?? '',
            $d['tel'] ?? '',
            $d['date_naissance'] ?? '',
            $d['etab_id'],
            $dfepId,
            $etabName,
            $wilayaName,
            $id
        ]);
    }

    public function deleteCompetance(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM competance WHERE IDCompetance = ?");
        return $stmt->execute([$id]);
    }

    public function getRecrutementsCount(int $etabId, int $dfepId = 0): int
    {
        return $this->getPersonnelCount($etabId, $dfepId);
    }

    public function getFormationsCount(int $etabId, int $dfepId = 0): int
    {
        [$conds, $params] = $this->etabScope($etabId, $dfepId, 'e.IDetablissement');
        $where = $conds ? 'AND ' . implode(' AND ', $conds) : '';
        $sql = "
            SELECT COUNT(*)
            FROM stageperfectionnemnt s
            JOIN encadrement e ON s.IDEncadrement = e.IDEncadrement
            WHERE 1=1 $where
        ";
        return (int) $this->execScope($sql, $params)->fetchColumn();
    }

    public function getActivitesCount(int $etabId, int $dfepId = 0): int
    {
        [$conds, $params] = $this->etabScope($etabId, $dfepId);
        $where = $conds ? 'WHERE ' . implode(' AND ', $conds) : '';
        $sql = "SELECT COUNT(*) FROM activite $where";
        return (int) $this->execScope($sql, $params)->fetchColumn();
    }

    public function getGrades(): array
    {
        $stmt = $this->db->query("
            SELECT g.IDGrade as id, g.Nom as nom, g.NomFr as nom_fr, c.IDNomenclatureCorp as corp_id
            FROM grade g
            LEFT JOIN corp c ON g.IDCorp = c.IDCorp
            ORDER BY g.NumOrd ASC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getFonctions(): array
    {
        $stmt = $this->db->query("SELECT IDFonctions as id, Nom as nom, NomFr as nom_fr FROM fonctions ORDER BY IDFonctions ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getModesRecrutement(): array
    {
        $stmt = $this->db->query("SELECT IDMode_Recrutement as id, Nom as nom, NomFr as nom_fr FROM mode_recrutement ORDER BY IDMode_Recrutement ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
