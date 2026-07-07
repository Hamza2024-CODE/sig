<?php

namespace App\Domains\Security;

use Exception;

class AuthorizationService
{
    protected PermissionResolver $resolver;

    public function __construct(PermissionResolver $resolver)
    {
        $this->resolver = $resolver;
    }

    /**
     * Authorize action for the current user session and optional target resource context
     */
    public function authorize(array $user, string $permission, $resource = null): void
    {
        $role = $user['role_code'] ?? '';
        if (!$this->resolver->hasPermission($role, $permission)) {
            throw new Exception("صلاحيات غير كافية لإجراء هذه العملية / Permissions insuffisantes.");
        }

        if ($resource !== null) {
            $this->verifyOwnership($user, $permission, $resource);
        }
    }

    /**
     * Enforce strict spatial boundaries and user context ownership check
     */
    private function verifyOwnership(array $user, string $permission, $resource): void
    {
        $role = strtolower($user['role_code'] ?? '');
        
        // Stagiaires can only view/interact with their own profile/records
        if ($role === 'stagiaire' && isset($resource['stagiaire_id'])) {
            if ((int)($user['id_stagiaire_profile'] ?? 0) !== (int)$resource['stagiaire_id']) {
                throw new Exception("غير مصرح لك بالوصول لبيانات مستخدم آخر / Accès non autorisé.");
            }
        }

        // Directors, establishments, and instructors can only view students inside their own establishment
        if (in_array($role, ['directeur', 'etablissement', 'formateur']) && isset($resource['etablissement_id'])) {
            if ((int)($user['etablissement_id'] ?? 0) !== (int)$resource['etablissement_id']) {
                throw new Exception("المتربص لا ينتمي لمؤسستك التكوينية / Trainee belongs to another establishment.");
            }
        }
    }
}
