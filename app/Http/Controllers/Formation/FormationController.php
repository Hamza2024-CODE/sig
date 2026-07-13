<?php
namespace App\Http\Controllers\Formation;

use App\Http\Controllers\Controller;

use PDO;

class FormationController extends Controller {
    protected $db;

    public function __construct() {
        if (app()->runningInConsole()) { return; }
        $this->db = new \App\Core\LaravelDbAdapter();
    }

    public function formation() {
        $stats = ['programmes' => 0, 'en_cours_maj' => 0, 'equipements' => 0];
        $list = [];
        $specialites = [];

        $user = session('user');
        $role = strtolower($user['role_code'] ?? '');
        $etabId = (int)($user['etablissement_id'] ?? 0);
        $dfepId = (int)($user['iddfep'] ?? 0);

        $where = "1=1";
        $params = [];

        if (in_array($role, ['admin', 'central'])) {
            // unrestricted
        } elseif ($role === 'dfep' && $dfepId > 0) {
            $where = "e.IDetablissement IN (SELECT IDetablissement FROM etablissement WHERE IDDFEP = ?)";
            $params[] = $dfepId;
        } elseif ($etabId > 0) {
            $where = "e.IDetablissement = ?";
            $params[] = $etabId;
        }

        try {
            // Use WINDEV tables
            $stats['programmes'] = (int)$this->db->query("SELECT COUNT(*) FROM specialite")->fetchColumn();
            
            $stmtEquip = $this->db->prepare("SELECT COUNT(*) FROM equipement e WHERE $where");
            $stmtEquip->execute($params);
            $stats['equipements'] = (int)$stmtEquip->fetchColumn();

            $stmtMaj = $this->db->prepare("SELECT COUNT(*) FROM equipement e WHERE e.Validation = 0 AND $where");
            $stmtMaj->execute($params);
            $stats['en_cours_maj'] = (int)$stmtMaj->fetchColumn();

            // List equipements linked to establishment and speciality
            $stmt = $this->db->prepare("
                SELECT e.IDEquipement as id, e.Nom as designation, ee.DateMiseExploitation as date_inventaire,
                       e.Obs as etat, sp.Nom as spec_ar, e.IDSpecialite as specialite_id,
                       e.DateInstalation, e.IDetablissement,
                       COALESCE(NULLIF(TRIM(ef.Nom), ''), NULLIF(TRIM(ef.Abr), ''), NULLIF(TRIM(ef.NomFr), ''), 'مؤسسة تكوينية') as etab_nom,
                       1 as quantite
                FROM equipement e
                LEFT JOIN specialite sp ON e.IDSpecialite = sp.IDSpecialite
                LEFT JOIN equipement_etablissement ee ON e.IDEquipement = ee.IDEquipement
                LEFT JOIN etablissement ef ON e.IDetablissement = ef.IDetablissement
                WHERE $where
                ORDER BY e.IDEquipement DESC
                LIMIT 100
            ");
            $stmt->execute($params);
            $list = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $specialites = $this->db->query("SELECT IDSpecialite as id, Nom as libelle_ar FROM specialite ORDER BY Nom ASC")->fetchAll(PDO::FETCH_ASSOC);
            $etablissements = $this->db->query("SELECT IDetablissement as id, Nom as nom_ar FROM etablissement ORDER BY Nom ASC")->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log("Error in formation(): " . $e->getMessage());
        }

        return $this->render('admin/modules/formation', [
            'title' => 'تسيير التكوين والعتاد / Gestion de la Formation',
            'stats' => $stats,
            'list' => $list,
            'specialites' => $specialites,
            'etablissements' => $etablissements
        ]);
    }

    public function storeEquipment() {
        if (request()->isMethod('post')) {
            $nom = request()->all()['nom_equipement'];
            $specialite_id = (int)request()->all()['specialite_id'];
            $etat = request()->all()['etat'] ?? 'ممتاز';
            $date_acq = request()->all()['date_acquisition'];
            $etab_id = (int)(request()->all()['etablissement_id'] ?? session('user')['etablissement_id'] ?? 0);

            try {
                $stmt = $this->db->prepare("
                    INSERT INTO equipement (Nom, IDSpecialite, IDequipement_etat, DateInstalation, IDetablissement, Validation, Obs)
                    VALUES (?, ?, 1, ?, ?, 0, ?)
                ");
                $stmt->execute([$nom, $specialite_id, $date_acq, $etab_id, $etat]);
                session(['flash_success' => 'تم جرد وإضافة التجهيز التقني بنجاح!']);
            } catch (\Exception $e) {
                session(['flash_error' => 'حدث خطأ أثناء إضافة التجهيز: ' . $e->getMessage()]);
            }
        }
        return $this->redirect('/dashboard/formation');
    }

    public function updateEquipment() {
        if (request()->isMethod('post')) {
            $id = (int)request()->all()['id'];
            $nom = request()->all()['nom_equipement'];
            $specialite_id = (int)request()->all()['specialite_id'];
            $date_acq = request()->all()['date_acquisition'];
            $etat = request()->all()['etat'] ?? 'ممتاز';
            $etab_id = (int)request()->all()['etablissement_id'];

            try {
                $stmt = $this->db->prepare("
                    UPDATE equipement
                    SET Nom = ?, IDSpecialite = ?, DateInstalation = ?, Obs = ?, IDetablissement = ?
                    WHERE IDEquipement = ?
                ");
                $stmt->execute([$nom, $specialite_id, $date_acq, $etat, $etab_id, $id]);
                session(['flash_success' => 'تم تحديث بيانات التجهيز التقني بنجاح!']);
            } catch (\Exception $e) {
                session(['flash_error' => 'حدث خطأ أثناء التحديث: ' . $e->getMessage()]);
            }
        }
        return $this->redirect('/dashboard/formation');
    }

    public function deleteEquipment($id) {
        try {
            $stmt = $this->db->prepare("DELETE FROM equipement WHERE IDEquipement = ?");
            $stmt->execute([(int)$id]);
            session(['flash_success' => 'تم حذف التجهيز التقني بنجاح!']);
        } catch (\Exception $e) {
            session(['flash_error' => 'حدث خطأ أثناء حذف التجهيز: ' . $e->getMessage()]);
        }
        return $this->redirect('/dashboard/formation');
    }
}
