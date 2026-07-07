<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Core\AuditLogger;
use App\Helpers\PermissionHelper;
use PDO;

class RolesController extends Controller
{

    /**
     * Lists all roles and their default permissions
     */
    public function index()
    {
        $role_code = session('user')['role_code'] ?? '';
        if ($role_code !== 'admin') {
            session(['flash_error' => 'غير مسموح لك بالوصول إلى هذه الصفحة / Accès refusé.']);
            return $this->redirect('/dashboard');
        }

        try {
            $rolesJsonPath = base_path('config/roles.json');
            if (file_exists($rolesJsonPath)) {
                $roles = json_decode(file_get_contents($rolesJsonPath), true) ?? [];
            } else {
                $roles = [];
            }
            
            // Format permissions JSON
            foreach ($roles as &$role) {
                $role['permissions'] = !empty($role['permissions']) ? json_decode($role['permissions'], true) : [];
            }
        } catch (\Exception $e) {
            session(['flash_error' => 'خطأ في جلب بيانات الأدوار: ' . $e->getMessage()]);
            $roles = [];
        }

        return $this->render('admin/roles/index', [
            'title' => 'إدارة الأدوار والصلاحيات الافتراضية / Rôles & Autorisations',
            'roles' => $roles
        ]);
    }

    /**
     * Updates default permissions for a specific role
     */
    public function update()
    {
        $role_code = session('user')['role_code'] ?? '';
        if ($role_code !== 'admin') {
            return $this->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        if (request()->isMethod('post')) {
            $id = (int)(request()->all()['id'] ?? 0);
            $permissions = isset(request()->all()['permissions']) ? request()->all()['permissions'] : [];

            if (empty($id)) {
                session(['flash_error' => 'معرف الدور غير صالح.']);
                return $this->redirect('/dashboard/roles');
            }

            try {
                $rolesJsonPath = base_path('config/roles.json');
                $roles = file_exists($rolesJsonPath) ? (json_decode(file_get_contents($rolesJsonPath), true) ?? []) : [];
                
                $oldRoleIndex = -1;
                foreach ($roles as $index => $r) {
                    if ((int)$r['id'] === $id) {
                        $oldRoleIndex = $index;
                        break;
                    }
                }

                if ($oldRoleIndex === -1) {
                    session(['flash_error' => 'الدور المحدد غير موجود.']);
                    return $this->redirect('/dashboard/roles');
                }

                $oldRole = $roles[$oldRoleIndex];

                // Prevent editing core admin permissions
                if ($oldRole['code'] === 'admin') {
                    session(['flash_error' => 'لا يمكن تعديل الصلاحيات الافتراضية للمدير العام للنظام (Admin).']);
                    return $this->redirect('/dashboard/roles');
                }

                // Filter and structure permissions
                $filteredPermissions = [];
                $validKeys = ['offres', 'inscriptions', 'discipline', 'grades', 'documents', 'repas'];
                foreach ($validKeys as $key) {
                    if (isset($permissions[$key]) && $permissions[$key] == '1') {
                        $filteredPermissions[$key] = '1';
                    }
                }

                $jsonPermissions = empty($filteredPermissions) ? null : json_encode($filteredPermissions);

                // Update roles.json
                $roles[$oldRoleIndex]['permissions'] = empty($filteredPermissions) ? "{}" : $jsonPermissions;
                file_put_contents($rolesJsonPath, json_encode($roles, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

                AuditLogger::log(
                    'UPDATE_ROLE_PERMS',
                    'config/roles.json',
                    $id,
                    ['code' => $oldRole['code'], 'old_permissions' => $oldRole['permissions'] ?? '{}'],
                    ['permissions' => $jsonPermissions]
                );

                PermissionHelper::invalidateCache();

                session(['flash_success' => 'تم تحديث الصلاحيات بنجاح.']);
                return $this->redirect('/dashboard/roles');

            } catch (\Exception $e) {
                session(['flash_error' => 'حدث خطأ أثناء تحديث الصلاحيات: ' . $e->getMessage()]);
                return $this->redirect('/dashboard/roles');
            }
        }
    }
}
