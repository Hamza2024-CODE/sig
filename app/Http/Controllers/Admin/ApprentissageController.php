<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ApprentissageController — المؤسسات الاقتصادية ومعلمو التمهين
 *
 * Laravel 100% Query Builder integration.
 */
class ApprentissageController extends Controller
{
    public function __construct()
    {
        if (app()->runningInConsole()) { return; }
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Check if a table exists in the database.
     */
    private function tableExists(string $table): bool
    {
        return Schema::hasTable($table);
    }

    // ─── المؤسسات الاقتصادية (Economic Partners) ─────────────────────────────

    public function partenaires(): mixed
    {
        $partenaires = [];
        $stats = ['total' => 0, 'publiques' => 0, 'privees' => 0, 'wilayas' => 0];

        $user = session('user');
        $role = strtolower($user['role_code'] ?? '');
        $wilayaId = $user['wilaya_id'] ?? null;

        // Fallbacks if session keys are missing/empty
        if (empty($wilayaId) && !in_array($role, ['admin', 'central'])) {
            if (!empty($user['iddfep']) && $user['iddfep'] > 0) {
                $wilayaId = (int)$user['iddfep'];
            } elseif (!empty($user['etablissement_id'])) {
                try {
                    $wilayaId = (int) DB::table('etablissement')
                        ->where('IDetablissement', $user['etablissement_id'])
                        ->value('IDDFEP');
                } catch (\Exception $e) {}
            }
        }

        $where = "1=1";
        $params = [];

        if (in_array($role, ['admin', 'central'])) {
            // unrestricted
        } elseif ($wilayaId > 0) {
            $where = "e.IDWilayaa = ?";
            $params[] = $wilayaId;
        }

        error_log('[DEBUG] role: ' . $role . ', wilaya: ' . $wilayaId . ', where: ' . $where);
        error_log('[DEBUG] tableExists(employeur): ' . ($this->tableExists('employeur') ? 'true' : 'false'));

        if ($this->tableExists('employeur')) {
            try {
                // Total
                $stats['total'] = (int) DB::table('employeur as e')->whereRaw($where, $params)->count();

                // By sector
                $stats['publiques'] = (int) DB::table('employeur as e')
                    ->whereIn('e.IDSecteurs', ['1', 'public', 'عام'])
                    ->whereRaw($where, $params)
                    ->count();
                
                $stats['privees'] = $stats['total'] - $stats['publiques'];

                // Distinct wilayas covered
                $stats['wilayas'] = (int) DB::table('employeur as e')
                    ->whereNotNull('e.IDWilayaa')
                    ->where('e.IDWilayaa', '>', 0)
                    ->whereRaw($where, $params)
                    ->distinct()
                    ->count('e.IDWilayaa');

                // Main list
                $partenaires = array_map(fn($item) => (array)$item, DB::select("
                    SELECT e.IDEmployeur AS IDEntreprise, e.Nom, e.NomFr, e.Adrs AS Adresse, e.Tel,
                           e.ActiviteIni AS Activite, e.IDSecteurs AS Secteur,
                           w.Nom  AS wilaya_ar,
                           w.NomFr AS wilaya_fr
                    FROM employeur e
                    LEFT JOIN wilaya w ON w.IDWilayaa = e.IDWilayaa
                    WHERE $where
                    ORDER BY e.Nom ASC
                    LIMIT 500
                ", $params));

                error_log('[DEBUG] count($partenaires): ' . count($partenaires));

            } catch (\Exception $e) {
                error_log('[ApprentissageController::partenaires] ' . $e->getMessage());
            }
        } else {
            error_log('[DEBUG] tableExists returned FALSE!');
        }

        $wilayas = [];
        try {
            $wilayas = array_map(fn($item) => (array)$item, DB::select("SELECT IDWilayaa as id, Code as code, Nom as nom_ar, NomFr as nom_fr FROM wilaya ORDER BY Code ASC"));
        } catch (\Exception $e) {}

        return $this->render('admin/apprentissage/partenaires', [
            'title'      => 'المؤسسات الاقتصادية الشريكة',
            'partenaires' => $partenaires,
            'stats'      => $stats,
            'wilayas'    => $wilayas,
        ]);
    }

    public function storePartenaire(): mixed
    {
        $this->verifyCsrf();

        if (!$this->tableExists('employeur')) {
            session(['flash_error' => 'جدول المؤسسات غير متاح في قاعدة البيانات.']);
            return $this->redirect('/dashboard/partenaires');
        }

        $nom      = trim(request()->all()['nom'] ?? '');
        $nomFr    = trim(request()->all()['nom_fr'] ?? '');
        $adresse  = trim(request()->all()['adresse'] ?? '');
        $tel      = trim(request()->all()['tel'] ?? '');
        $activite = trim(request()->all()['activite'] ?? '');
        $secteur  = trim(request()->all()['secteur'] ?? 'prive');
        $wilayaId = !empty(request()->all()['wilaya_id']) ? (int)request()->all()['wilaya_id'] : null;

        $user = session('user');
        $role = strtolower($user['role_code'] ?? '');
        $userWilayaId = $user['wilaya_id'] ?? null;

        if (empty($userWilayaId) && !in_array($role, ['admin', 'central'])) {
            if (!empty($user['iddfep']) && $user['iddfep'] > 0) {
                $userWilayaId = (int)$user['iddfep'];
            } elseif (!empty($user['etablissement_id'])) {
                try {
                    $userWilayaId = (int) DB::table('etablissement')
                        ->where('IDetablissement', $user['etablissement_id'])
                        ->value('IDDFEP');
                } catch (\Exception $e) {}
            }
        }

        if (!in_array($role, ['admin', 'central']) && $userWilayaId > 0) {
            $wilayaId = $userWilayaId;
        }

        if (empty($nom)) {
            session(['flash_error' => 'يجب إدخال اسم المؤسسة.']);
            return $this->redirect('/dashboard/partenaires');
        }

        try {
            // Find max IDEmployeur and calculate next ID (no AUTO_INCREMENT)
            $maxId = DB::table('employeur')->max('IDEmployeur');
            $nextId = (int)$maxId + 1;

            // Map public/private sector to valid integer values for IDSecteurs (bigint)
            $secteurId = ($secteur === 'public') ? 1 : 27;

            DB::table('employeur')->insert([
                'IDEmployeur' => $nextId,
                'Nom' => $nom,
                'NomFr' => $nomFr,
                'Adrs' => $adresse,
                'Tel' => $tel,
                'ActiviteIni' => $activite,
                'IDSecteurs' => $secteurId,
                'IDWilayaa' => $wilayaId,
                'IDEmployeurType' => null,
                'IDCommunn' => null,
                'IDDFEP' => null,
                'IDEmployeur_Nature' => null
            ]);

            session(['flash_success' => 'تمت إضافة المؤسسة الشريكة بنجاح.']);
        } catch (\Exception $e) {
            error_log('[ApprentissageController::storePartenaire] ' . $e->getMessage());
            session(['flash_error' => 'حدث خطأ أثناء حفظ المؤسسة.']);
        }

        return $this->redirect('/dashboard/partenaires');
    }

    public function updatePartenaire(): mixed
    {
        $this->verifyCsrf();

        $id      = (int)(request()->all()['id'] ?? 0);
        $nom     = trim(request()->all()['nom'] ?? '');
        $nomFr   = trim(request()->all()['nom_fr'] ?? '');
        $adresse = trim(request()->all()['adresse'] ?? '');
        $tel     = trim(request()->all()['tel'] ?? '');
        $activite = trim(request()->all()['activite'] ?? '');
        $secteur = trim(request()->all()['secteur'] ?? 'prive');

        $user = session('user');
        $role = strtolower($user['role_code'] ?? '');
        $userWilayaId = $user['wilaya_id'] ?? null;

        if (empty($userWilayaId) && !in_array($role, ['admin', 'central'])) {
            if (!empty($user['iddfep']) && $user['iddfep'] > 0) {
                $userWilayaId = (int)$user['iddfep'];
            } elseif (!empty($user['etablissement_id'])) {
                try {
                    $userWilayaId = (int) DB::table('etablissement')
                        ->where('IDetablissement', $user['etablissement_id'])
                        ->value('IDDFEP');
                } catch (\Exception $e) {}
            }
        }

        if (!in_array($role, ['admin', 'central']) && $userWilayaId > 0) {
            try {
                $partnerWilaya = (int) DB::table('employeur')
                    ->where('IDEmployeur', $id)
                    ->value('IDWilayaa');
                if ($partnerWilaya > 0 && $partnerWilaya !== (int)$userWilayaId) {
                    session(['flash_error' => 'غير مصرح لك بتعديل بيانات هذه المؤسسة الشريكة.']);
                    return $this->redirect('/dashboard/partenaires');
                }
            } catch (\Exception $e) {}
        }

        if ($id < 1 || empty($nom)) {
            session(['flash_error' => 'بيانات غير صالحة.']);
            return $this->redirect('/dashboard/partenaires');
        }

        try {
            // Map public/private sector to valid integer values for IDSecteurs (bigint)
            $secteurId = ($secteur === 'public') ? 1 : 27;

            DB::table('employeur')
                ->where('IDEmployeur', $id)
                ->update([
                    'Nom' => $nom,
                    'NomFr' => $nomFr,
                    'Adrs' => $adresse,
                    'Tel' => $tel,
                    'ActiviteIni' => $activite,
                    'IDSecteurs' => $secteurId
                ]);

            session(['flash_success' => 'تم تعديل بيانات المؤسسة.']);
        } catch (\Exception $e) {
            error_log('[ApprentissageController::updatePartenaire] ' . $e->getMessage());
            session(['flash_error' => 'حدث خطأ أثناء التعديل.']);
        }

        return $this->redirect('/dashboard/partenaires');
    }

    public function deletePartenaire(int $id): mixed
    {
        $this->verifyCsrf();

        if ($id < 1) {
            session(['flash_error' => 'معرّف غير صالح.']);
            return $this->redirect('/dashboard/partenaires');
        }

        $user = session('user');
        $role = strtolower($user['role_code'] ?? '');
        $userWilayaId = $user['wilaya_id'] ?? null;

        if (empty($userWilayaId) && !in_array($role, ['admin', 'central'])) {
            if (!empty($user['iddfep']) && $user['iddfep'] > 0) {
                $userWilayaId = (int)$user['iddfep'];
            } elseif (!empty($user['etablissement_id'])) {
                try {
                    $userWilayaId = (int) DB::table('etablissement')
                        ->where('IDetablissement', $user['etablissement_id'])
                        ->value('IDDFEP');
                } catch (\Exception $e) {}
            }
        }

        if (!in_array($role, ['admin', 'central']) && $userWilayaId > 0) {
            try {
                $partnerWilaya = (int) DB::table('employeur')
                    ->where('IDEmployeur', $id)
                    ->value('IDWilayaa');
                if ($partnerWilaya > 0 && $partnerWilaya !== (int)$userWilayaId) {
                    session(['flash_error' => 'غير مصرح لك بحذف هذه المؤسسة الشريكة.']);
                    return $this->redirect('/dashboard/partenaires');
                }
            } catch (\Exception $e) {}
        }

        try {
            DB::table('employeur')->where('IDEmployeur', $id)->delete();
            session(['flash_success' => 'تم حذف المؤسسة.']);
        } catch (\Exception $e) {
            error_log('[ApprentissageController::deletePartenaire] ' . $e->getMessage());
            session(['flash_error' => 'حدث خطأ أثناء الحذف.']);
        }

        return $this->redirect('/dashboard/partenaires');
    }

    // ─── معلمو التمهين (Apprenticeship Masters) ──────────────────────────────

    public function maitres(): mixed
    {
        $maitres    = [];
        $entreprises = [];
        $stats = ['total' => 0, 'actifs' => 0, 'entreprises' => 0];

        $user = session('user');
        $role = strtolower($user['role_code'] ?? '');
        $etabId = (int)($user['etablissement_id'] ?? 0);
        $dfepId = (int)($user['iddfep'] ?? 0);

        if ($this->tableExists('maitre_apprenti')) {
            try {
                $stats['total'] = (int) DB::table('maitre_apprenti')->count();

                $stats['actifs'] = (int) DB::table('maitre_apprenti')
                    ->where(function($q) {
                        $q->whereNull('DateFIn')
                          ->orWhere('DateFIn', '>=', DB::raw('CURDATE()'));
                    })->count();

                $stats['entreprises'] = (int) DB::table('maitre_apprenti')
                    ->whereNotNull('IDEmployeur')
                    ->where('IDEmployeur', '>', 0)
                    ->distinct()
                    ->count('IDEmployeur');

                $maitres = array_map(fn($item) => (array)$item, DB::select("
                    SELECT m.IDMaitre_Apprenti AS IDMaitre, m.Nom, m.NomFr,
                           '' AS Prenom, '' AS PrenomFr,
                           '' AS Tel, m.Fonctionn AS Fonction,
                           m.Datedeb AS DateD, m.DateFIn AS DateF,
                           e.Nom AS entreprise_ar, e.NomFr AS entreprise_fr,
                           '' AS apprenant_ar
                    FROM maitre_apprenti m
                    LEFT JOIN employeur e ON e.IDEmployeur = m.IDEmployeur
                    ORDER BY m.Nom ASC
                    LIMIT 500
                "));

            } catch (\Exception $e) {
                error_log('[ApprentissageController::maitres] ' . $e->getMessage());
            }
        }

        // Load enterprise list for the add/edit modal
        if ($this->tableExists('employeur')) {
            try {
                $entreprises = array_map(fn($item) => (array)$item, DB::select("SELECT IDEmployeur AS IDEntreprise, Nom FROM employeur ORDER BY Nom ASC LIMIT 200"));
            } catch (\Exception $e) { /* silent */ }
        }

        // Load apprenants for assignment
        $apprenants = [];
        try {
            $apprenants = array_map(fn($item) => (array)$item, DB::select("
                SELECT a.IDapprenant, CONCAT(c.Nom,' ',c.Prenom) AS nom_complet
                FROM apprenant a
                JOIN candidat c ON c.IDCandidat = a.IDCandidat
                WHERE a.statut = 'actif'
                ORDER BY c.Nom ASC
                LIMIT 300
            "));
        } catch (\Exception $e) { /* silent */ }

        return $this->render('admin/apprentissage/maitres', [
            'title'       => 'معلمو التمهين',
            'maitres'     => $maitres,
            'entreprises' => $entreprises,
            'apprenants'  => $apprenants,
            'stats'       => $stats,
        ]);
    }

    public function storeMaitre(): mixed
    {
        $this->verifyCsrf();

        if (!$this->tableExists('maitre_apprenti')) {
            session(['flash_error' => 'جدول معلمي التمهين غير متاح في قاعدة البيانات.']);
            return $this->redirect('/dashboard/maitres-apprentissage');
        }

        $nom          = trim(request()->all()['nom'] ?? '');
        $nomFr        = trim(request()->all()['nom_fr'] ?? '');
        $fonction     = trim(request()->all()['fonction'] ?? '');
        $idEmployeur  = (int)(request()->all()['id_entreprise'] ?? 0) ?: null;
        $dateD        = !empty(request()->all()['date_debut']) ? request()->all()['date_debut'] : null;
        $dateF        = !empty(request()->all()['date_fin']) ? request()->all()['date_fin'] : null;

        if (empty($nom)) {
            session(['flash_error' => 'يجب إدخال اسم معلم التمهين.']);
            return $this->redirect('/dashboard/maitres-apprentissage');
        }

        try {
            $maxId = (int) DB::table('maitre_apprenti')->max('IDMaitre_Apprenti');
            $newId = $maxId + 1;

            DB::table('maitre_apprenti')->insert([
                'IDMaitre_Apprenti' => $newId,
                'Nom' => $nom,
                'NomFr' => $nomFr,
                'Fonctionn' => $fonction,
                'IDEmployeur' => $idEmployeur,
                'Datedeb' => $dateD,
                'DateFIn' => $dateF
            ]);

            session(['flash_success' => 'تمت إضافة معلم التمهين بنجاح.']);
        } catch (\Exception $e) {
            error_log('[ApprentissageController::storeMaitre] ' . $e->getMessage());
            session(['flash_error' => 'حدث خطأ أثناء الحفظ: ' . $e->getMessage()]);
        }

        return $this->redirect('/dashboard/maitres-apprentissage');
    }

    public function updateMaitre(): mixed
    {
        $this->verifyCsrf();

        $id       = (int)(request()->all()['id'] ?? 0);
        $nom      = trim(request()->all()['nom'] ?? '');
        $nomFr    = trim(request()->all()['nom_fr'] ?? '');
        $fonction = trim(request()->all()['fonction'] ?? '');
        $dateF    = !empty(request()->all()['date_fin']) ? request()->all()['date_fin'] : null;

        if ($id < 1 || empty($nom)) {
            session(['flash_error' => 'بيانات غير صالحة.']);
            return $this->redirect('/dashboard/maitres-apprentissage');
        }

        try {
            DB::table('maitre_apprenti')
                ->where('IDMaitre_Apprenti', $id)
                ->update([
                    'Nom' => $nom,
                    'NomFr' => $nomFr,
                    'Fonctionn' => $fonction,
                    'DateFIn' => $dateF
                ]);

            session(['flash_success' => 'تم تعديل بيانات معلم التمهين.']);
        } catch (\Exception $e) {
            error_log('[ApprentissageController::updateMaitre] ' . $e->getMessage());
            session(['flash_error' => 'حدث خطأ أثناء التعديل.']);
        }

        return $this->redirect('/dashboard/maitres-apprentissage');
    }

    public function deleteMaitre(int $id): mixed
    {
        $this->verifyCsrf();

        if ($id < 1) {
            session(['flash_error' => 'معرّف غير صالح.']);
            return $this->redirect('/dashboard/maitres-apprentissage');
        }

        try {
            DB::table('maitre_apprenti')->where('IDMaitre_Apprenti', $id)->delete();
            session(['flash_success' => 'تم حذف معلم التمهين.']);
        } catch (\Exception $e) {
            error_log('[ApprentissageController::deleteMaitre] ' . $e->getMessage());
            session(['flash_error' => 'حدث خطأ أثناء الحذف.']);
        }

        return $this->redirect('/dashboard/maitres-apprentissage');
    }

    // ─── Internal helpers ─────────────────────────────────────────────────────

    private function verifyCsrf(): void
    {
        if (empty(request()->all()['csrf_token']) || request()->all()['csrf_token'] !== (csrf_token() ?? '')) {
            http_response_code(403);
            exit('طلب غير مصرح به.');
        }
    }
}
