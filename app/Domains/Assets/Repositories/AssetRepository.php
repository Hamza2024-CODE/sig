<?php

namespace App\Domains\Assets\Repositories;

use App\Core\Database;
use PDO;

class AssetRepository
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

    private function photoSortClause(string $photoExpr, string $fallbackCol): string
    {
        $enabled = \App\Helpers\SovereignLicensingHelper::getSetting('feature_photo_sorting_enabled', '1') === '1';
        if (!$enabled) {
            return "{$fallbackCol} DESC";
        }
        return "CASE WHEN {$photoExpr} = 'empty' THEN NULL ELSE {$photoExpr} END DESC, {$fallbackCol} DESC";
    }

    /**
     * Get list of equipment associated with an etablissement.
     */
    public function findEquipmentsByEtablissement(int $etabId, int $dfepId = 0): array
    {
        [$conds, $params] = $this->etabScope($etabId, $dfepId, 'e.IDetablissement');
        $where = $conds ? 'WHERE ' . implode(' AND ', $conds) : '';
        $sql = "
            SELECT e.IDEquipement as id, e.Nom as designation, e.NomFr as designation_fr,
                   e.Code as code, e.DateMiseEnexploi as date_exploitation, e.Datereception as date_reception,
                   e.Obs as description, COALESCE(e.Validation, 0) as is_validated,
                   et.Nom as etablissement_nom, d.Nom as wilaya_nom, CASE WHEN em.photo = 'empty' THEN NULL ELSE em.photo END as photo
            FROM equipement e
            LEFT JOIN etablissement et ON e.IDetablissement = et.IDetablissement
            LEFT JOIN dfep d ON et.IDDFEP = d.IDDFEP
            LEFT JOIN equipement_memo em ON em.IDEquipement = e.IDEquipement
            $where ORDER BY {$this->photoSortClause('em.photo', 'e.IDEquipement')} LIMIT 200
        ";
        return $this->execScope($sql, $params)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Insert a new equipment request (Validation = 0) in the legacy 'equipement' table.
     */
    public function insertEquipmentRequest(array $data): int
    {
        $id = (int)($this->db->query("SELECT COALESCE(MAX(IDEquipement), 0) + 1 FROM equipement")->fetchColumn() ?: 1);
        $stmt = $this->db->prepare("
            INSERT INTO equipement (IDEquipement, Nom, NomFr, Code, DateMiseEnexploi, Datereception, IDSpecialite, IDequipement_etat, DateInstalation, IDetablissement, Validation, Obs)
            VALUES (?, ?, ?, ?, ?, ?, 1, ?, ?, ?, 0, ?)
        ");
        $stmt->execute([
            $id,
            $data['designation'],
            $data['designation_fr'] ?? '',
            $data['code'] ?? '',
            $data['date_exploitation'] ?? date('Y-m-d'),
            $data['date_reception'] ?? date('Y-m-d'),
            $data['etat_id'] ?? 1,
            $data['date_installation'] ?? date('Y-m-d'),
            $data['etablissement_id'],
            $data['description'] ?? ''
        ]);

        return $id;
    }

    public function getVehicules(int $etabId, int $dfepId = 0): array
    {
        [$conds, $params] = $this->etabScope($etabId, $dfepId, 'v.IDetablissement');
        $where = $conds ? 'WHERE ' . implode(' AND ', $conds) : '';
        $sql = "
            SELECT v.IDVehicule as id, v.Immatriculation as immatriculation, v.Annepremier as annee,
                   v.marqueCommerce as marque, v.NumChasse as chassis, v.nbrPlace as places,
                   v.Immatriculationprec as immatriculation_prec, v.Validation,
                   et.Nom as etablissement_nom, d.Nom as wilaya_nom, CASE WHEN vm.photo = 'empty' THEN NULL ELSE vm.photo END as photo
            FROM vehicule v
            LEFT JOIN etablissement et ON v.IDetablissement = et.IDetablissement
            LEFT JOIN dfep d ON et.IDDFEP = d.IDDFEP
            LEFT JOIN vehicule_memo vm ON vm.IDVehicule = v.IDVehicule
            $where ORDER BY {$this->photoSortClause('vm.photo', 'v.IDVehicule')} LIMIT 200
        ";
        return $this->execScope($sql, $params)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function insertVehicule(array $d): int
    {
        $id = (int)($this->db->query("SELECT COALESCE(MAX(IDVehicule), 0) + 1 FROM vehicule")->fetchColumn() ?: 1);
        $stmt = $this->db->prepare("
            INSERT INTO vehicule 
                (IDVehicule, IDetablissement, Immatriculation, Annepremier, marqueCommerce, NumChasse, nbrPlace, Immatriculationprec, Validation, ValidationDfp, IDVehiclesType, IDVehiculeEnergie, IDVehiculegenre, IDVehiculesMarque)
            VALUES (?,?,?,?,?,?,?,?,0,0,1,1,1,1)
        ");
        $stmt->execute([
            $id, $d['etab_id'], $d['immatriculation'], $d['annee']??date('Y'), $d['marque']??'', $d['chassis']??'', $d['places']??5, $d['immatriculation_prec']??''
        ]);
        return $id;
    }

    public function getLocaux(int $etabId, int $dfepId = 0): array
    {
        [$conds, $params] = $this->etabScope($etabId, $dfepId, 'l.IDetablissement');
        $where = $conds ? 'WHERE ' . implode(' AND ', $conds) : '';
        $sql = "
            SELECT l.IDLocaux as id, l.Nom as nom, l.NomFr as nom_fr, l.nbrPlace as places, 
                   l.nbrTable as tables, l.NbrChaise as chaises, l.Obs as observation, 
                   l.etage, l.tableaffichage, l.datashow, l.climatiseur,
                   lt.Nom as type_nom, le.Nom as etat_nom,
                   et.Nom as etablissement_nom, d.Nom as wilaya_nom
            FROM locaux l
            LEFT JOIN localtype lt ON l.IDTypeLocal = lt.IDTypeLocal
            LEFT JOIN locaux_etatactual le ON l.EtatActual = le.EtatActual
            LEFT JOIN etablissement et ON l.IDetablissement = et.IDetablissement
            LEFT JOIN dfep d ON et.IDDFEP = d.IDDFEP
            $where 
            ORDER BY l.IDLocaux DESC LIMIT 200
        ";
        return $this->execScope($sql, $params)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function insertLocal(array $d): int
    {
        $id = (int)($this->db->query("SELECT COALESCE(MAX(IDLocaux), 0) + 1 FROM locaux")->fetchColumn() ?: 1);
        $stmt = $this->db->prepare("
            INSERT INTO locaux 
                (IDLocaux, Nom, NomFr, nbrPlace, nbrTable, NbrChaise, IDetablissement, IDTypeLocal, EtatActual, etage, tableaffichage, datashow, climatiseur, Validation, ValidationDfp)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 0)
        ");
        $stmt->execute([
            $id,
            $d['nom'],
            $d['nom_fr'] ?? '',
            $d['places'] ?? 30,
            $d['tables'] ?? 15,
            $d['chaises'] ?? 30,
            $d['etab_id'],
            $d['type_id'] ?? 1,
            $d['etat_id'] ?? 1,
            $d['etage'] ?? 0,
            $d['tableaffichage'] ?? 0,
            $d['datashow'] ?? 0,
            $d['climatiseur'] ?? 0
        ]);
        return $id;
    }

    public function getLogements(int $etabId, int $dfepId = 0): array
    {
        $col_occup = hex2bin('4f63637570e294ace294a4c394c3b6c389e294acc3b3');
        $col_elec = hex2bin('656c6563747269636974e294ace294a4c394c3b6c389e294acc3b3');
        [$conds, $params] = $this->etabScope($etabId, $dfepId, 'l.IDetablissement');
        $where = $conds ? 'WHERE ' . implode(' AND ', $conds) : '';
        
        $sql = "
            SELECT l.IDLogement as id, l.surface, l.InterneExterne as interne_externe, l.Adres as adresse, 
                   l.Obs as observation, l.Validation, l.NomPrnEmployeur as occupant_nom, l.etage, l.gaz, 
                   l.`$col_elec` as electricite, l.eau, l.structuree, l.ETAT as etat,
                   lt.Nom as type_nom, ln.Nom as nature_nom, lo.Nom as occupe_nom, lj.Nom as juridique_nom,
                   et.Nom as etablissement_nom, d.Nom as wilaya_nom, CASE WHEN lm.photo = 'empty' THEN NULL ELSE lm.photo END as photo
            FROM logement l
            LEFT JOIN logementtype lt ON l.IDLogementType = lt.IDLogementType
            LEFT JOIN logement_nature ln ON l.IDLogement_Nature = ln.IDLogement_Nature
            LEFT JOIN logementoccupe lo ON l.`$col_occup` = lo.Occupe
            LEFT JOIN logementnaturejur lj ON l.SituationJur = lj.SituationJur
            LEFT JOIN etablissement et ON l.IDetablissement = et.IDetablissement
            LEFT JOIN dfep d ON et.IDDFEP = d.IDDFEP
            LEFT JOIN logement_memo lm ON lm.IDLogement = l.IDLogement
            $where 
            ORDER BY {$this->photoSortClause('lm.photo', 'l.IDLogement')} LIMIT 200
        ";
        return $this->execScope($sql, $params)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function insertLogement(array $d): int
    {
        $id = (int)($this->db->query("SELECT COALESCE(MAX(IDLogement), 0) + 1 FROM logement")->fetchColumn() ?: 1);
        $col_occup = hex2bin('4f63637570e294ace294a4c394c3b6c389e294acc3b3');
        $col_elec = hex2bin('656c6563747269636974e294ace294a4c394c3b6c389e294acc3b3');
        $stmt = $this->db->prepare("
            INSERT INTO logement 
                (IDLogement, IDetablissement, surface, InterneExterne, Adres, Obs, IDLogementType, IDLogement_Nature, `$col_occup`, IDLogement_CauseOccup, SituationJur, NomPrnEmployeur, etage, structuree, gaz, `$col_elec`, eau, ETAT, Tel, Validation, ValidationDfp)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 0, 0)
        ");
        $stmt->execute([
            $id,
            $d['etab_id'],
            $d['surface'] ?? 0.0,
            $d['interne_externe'] ?? 1,
            $d['adresse'] ?? '',
            $d['observation'] ?? '',
            $d['type_id'] ?? 1,
            $d['nature_id'] ?? 1,
            $d['occup_id'] ?? 1,
            $d['cause_id'] ?? 1,
            $d['juridique_id'] ?? 1,
            $d['occupant_nom'] ?? '',
            $d['etage'] ?? 0,
            $d['structuree'] ?? 0,
            $d['gaz'] ?? 0,
            $d['electricite'] ?? 0,
            $d['eau'] ?? 0,
            $d['etat'] ?? 1
        ]);
        return $id;
    }

    public function getEquipmentsCount(int $etabId, int $dfepId = 0): int
    {
        [$conds, $params] = $this->etabScope($etabId, $dfepId, 'IDetablissement');
        $where = $conds ? 'WHERE ' . implode(' AND ', $conds) : '';
        $sql = "SELECT COUNT(*) FROM equipement $where";
        return (int) $this->execScope($sql, $params)->fetchColumn();
    }

    public function getVehiculesCount(int $etabId, int $dfepId = 0): int
    {
        [$conds, $params] = $this->etabScope($etabId, $dfepId, 'IDetablissement');
        $where = $conds ? 'WHERE ' . implode(' AND ', $conds) : '';
        $sql = "SELECT COUNT(*) FROM vehicule $where";
        return (int) $this->execScope($sql, $params)->fetchColumn();
    }

    public function getLocauxCount(int $etabId, int $dfepId = 0): int
    {
        [$conds, $params] = $this->etabScope($etabId, $dfepId, 'IDetablissement');
        $where = $conds ? 'WHERE ' . implode(' AND ', $conds) : '';
        $sql = "SELECT COUNT(*) FROM locaux $where";
        return (int) $this->execScope($sql, $params)->fetchColumn();
    }

    public function getLogementsCount(int $etabId, int $dfepId = 0): int
    {
        [$conds, $params] = $this->etabScope($etabId, $dfepId, 'IDetablissement');
        $where = $conds ? 'WHERE ' . implode(' AND ', $conds) : '';
        $sql = "SELECT COUNT(*) FROM logement $where";
        return (int) $this->execScope($sql, $params)->fetchColumn();
    }

    public function getProprietes(int $etabId = 0, int $dfepId = 0): array
    {
        $stmt = $this->db->query("SELECT IDPropriete_Location as id, Nom as nom, NomFr as nom_fr FROM propriete_location ORDER BY IDPropriete_Location DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function insertPropriete(array $d): int
    {
        $id = (int)($this->db->query("SELECT COALESCE(MAX(IDPropriete_Location), 0) + 1 FROM propriete_location")->fetchColumn() ?: 1);
        $stmt = $this->db->prepare("INSERT INTO propriete_location (IDPropriete_Location, Nom, NomFr) VALUES (?,?,?)");
        $stmt->execute([
            $id, $d['nom'], $d['nom_fr']??''
        ]);
        return $id;
    }
}
