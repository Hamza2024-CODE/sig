<?php

namespace App\Security;

class PermissionResolver
{
    /**
     * Evaluates if the UserContext has permission for the specified action.
     */
    public function hasPermission(UserContext $user, string $action): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $permissions = $user->permissions;

        if (($permissions['tous'] ?? 0) === 1) {
            return true;
        }

        switch (strtolower($action)) {
            case 'create':
            case 'store':
            case 'add':
                return ($permissions['ajout'] ?? 0) === 1;

            case 'update':
            case 'edit':
                return ($permissions['modif'] ?? 0) === 1;

            case 'delete':
            case 'destroy':
            case 'remove':
                return ($permissions['suppr'] ?? 0) === 1;

            case 'view':
            case 'show':
            case 'index':
                return true; // Read actions are permitted by default for validated sessions

            default:
                return false;
        }
    }
}
