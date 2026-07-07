<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Helpers\PermissionHelper;
use Exception;

class PermissionsController extends Controller
{
    /**
     * Lists all users and their custom permissions state.
     */
    public function index()
    {
        $sessionUser = session('user');
        if (!$sessionUser) {
            return redirect()->route('login');
        }

        $role_code = strtolower($sessionUser['role_code'] ?? '');
        if ($role_code !== 'admin') {
            session(['flash_error' => 'غير مصرح لك بالولوج إلى هذه الصفحة / Accès refusé.']);
            return redirect()->route('dashboard');
        }

        try {
            // Fetch all users
            $users = DB::select("
                SELECT u.IDUtilisateur as id, u.NomUser as username, u.Nom as nom_complet,
                       u.admin,
                       CASE 
                           WHEN u.admin = 1 THEN 'admin'
                           WHEN u.IDNature = 4 THEN 'dfep'
                           WHEN u.IDNature = 2 THEN 'directeur'
                           WHEN u.IDNature = 3 THEN 'formateur'
                           WHEN u.IDNature = 5 THEN 'stagiaire'
                           ELSE 'special'
                       END as role_code,
                       CASE 
                           WHEN u.admin = 1 THEN 'مدير النظام'
                           WHEN u.IDNature = 4 THEN 'DFEP ولائي'
                           WHEN u.IDNature = 2 THEN 'مدير مؤسسة'
                           WHEN u.IDNature = 3 THEN 'مكوّن'
                           WHEN u.IDNature = 5 THEN 'متربص'
                           ELSE 'حساب خاص'
                       END as role_ar
                FROM utilisateur u
                ORDER BY u.NomUser ASC
            ");
            
            $users = array_map(fn($item) => (array)$item, $users);

            // Fetch custom privileges
            $customPrivs = DB::select("
                SELECT pu.IDUtilisateur, pu.IDPrivelege, pu.DroiAjout, pu.DroiModif, pu.DroitSuppr, pu.DroitTous
                FROM privelege_utilisateur pu
            ");

            // Group custom privileges by user
            $userPrivs = [];
            foreach ($customPrivs as $cp) {
                $uid = (int)$cp->IDUtilisateur;
                if (!isset($userPrivs[$uid])) {
                    $userPrivs[$uid] = [];
                }
                $userPrivs[$uid][(int)$cp->IDPrivelege] = [
                    'DroiAjout' => (int)$cp->DroiAjout,
                    'DroiModif' => (int)$cp->DroiModif,
                    'DroitSuppr' => (int)$cp->DroitSuppr,
                    'DroitTous' => (int)$cp->DroitTous
                ];
            }

            // Map modules to IDPrivelege keys
            $modulesMap = [
                'offres'       => [1],
                'inscriptions' => [2, 3, 4],
                'discipline'   => [70],
                'grades'       => [6, 7],
                'documents'    => [8],
                'repas'        => [72]
            ];

            // Resolve states for each user
            // State: 2 = Inherit (no records), 1 = Grant (has record with at least one flag = 1), 0 = Deny (has record but all flags = 0)
            foreach ($users as &$u) {
                $uid = (int)$u['id'];
                $u['permissions'] = [];

                foreach ($modulesMap as $module => $ids) {
                    $hasRecord = false;
                    $hasGrant = false;

                    foreach ($ids as $id) {
                        if (isset($userPrivs[$uid][$id])) {
                            $hasRecord = true;
                            $p = $userPrivs[$uid][$id];
                            if ($p['DroiAjout'] || $p['DroiModif'] || $p['DroitSuppr'] || $p['DroitTous']) {
                                $hasGrant = true;
                            }
                        }
                    }

                    if (!$hasRecord) {
                        $u['permissions'][$module] = 2; // Inherit
                    } else {
                        $u['permissions'][$module] = $hasGrant ? 1 : 0; // Grant or Deny
                    }
                }
            }

        } catch (Exception $e) {
            session(['flash_error' => 'خطأ في جلب بيانات الصلاحيات: ' . $e->getMessage()]);
            $users = [];
        }

        return view('admin.permissions.index', [
            'title' => 'إدارة وتخصيص صلاحيات المستخدمين / Permissions',
            'users' => $users
        ]);
    }

    /**
     * Updates custom user permissions in the database.
     */
    public function update(Request $request)
    {
        $sessionUser = session('user');
        if (!$sessionUser) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $role_code = strtolower($sessionUser['role_code'] ?? '');
        if ($role_code !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $userId = (int)$request->input('user_id', 0);
        $permissions = $request->input('permissions', []);

        if (!$userId) {
            session(['flash_error' => 'معرف المستخدم غير صالح.']);
            return redirect()->back();
        }

        // Module mapping
        $modulesMap = [
            'offres'       => [1 => 5],          // IDPrivelege => Code
            'inscriptions' => [2 => 6, 3 => 7, 4 => 8],
            'discipline'   => [70 => 102],
            'grades'       => [6 => 10, 7 => 11],
            'documents'    => [8 => 12],
            'repas'        => [72 => 104]
        ];

        try {
            DB::beginTransaction();

            // Disable FK checks
            if (DB::getDriverName() === 'sqlite') {
                DB::statement('PRAGMA foreign_keys = OFF;');
            } else {
                DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            }

            // Get max IDPrivelege_Utilisateur for incrementing
            $maxIdRow = DB::selectOne("SELECT MAX(IDPrivelege_Utilisateur) as m FROM privelege_utilisateur");
            $newId = max(1, (int)($maxIdRow->m ?? 0) + 1);

            foreach ($modulesMap as $module => $privs) {
                // Determine new state (default is 2 = Inherit)
                $state = isset($permissions[$module]) ? (int)$permissions[$module] : 2;

                // Delete existing custom privilege records for this module and user
                $privIds = array_keys($privs);
                $placeholders = implode(',', array_fill(0, count($privIds), '?'));
                $deleteParams = array_merge([$userId], $privIds);
                DB::delete("DELETE FROM privelege_utilisateur WHERE IDUtilisateur = ? AND IDPrivelege IN ($placeholders)", $deleteParams);

                if ($state === 1 || $state === 0) {
                    // Grant (1) or Deny (0)
                    $flagVal = ($state === 1) ? 1 : 0;

                    foreach ($privs as $idPriv => $code) {
                        DB::insert("
                            INSERT INTO privelege_utilisateur 
                                (IDUtilisateur, IDPrivelege, DroiAjout, DroiModif, DroitSuppr, DroitTous, 
                                 IDBureau, IDMode_formation, activee, Code, IDMode_gestion, IDNature, IDPrivelege_Utilisateur)
                            VALUES (?, ?, ?, ?, ?, ?, 0, 0, 0, ?, 0, 0, ?)
                        ", [$userId, $idPriv, $flagVal, $flagVal, $flagVal, $flagVal, $code, $newId]);
                        
                        $newId++;
                    }
                }
            }

            // Re-enable FK checks
            if (DB::getDriverName() === 'sqlite') {
                DB::statement('PRAGMA foreign_keys = ON;');
            } else {
                DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            }

            DB::commit();

            // Invalidate permission cache for this user
            PermissionHelper::invalidateCache($userId);

            session(['flash_success' => 'تم حفظ وتكييف صلاحيات المستخدم بنجاح.']);

        } catch (Exception $e) {
            DB::rollBack();
            if (DB::getDriverName() === 'sqlite') {
                DB::statement('PRAGMA foreign_keys = ON;');
            } else {
                DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            }
            session(['flash_error' => 'حدث خطأ أثناء حفظ الصلاحيات: ' . $e->getMessage()]);
        }

        return redirect()->back();
    }
}
