<?php

namespace App\Security;

class PolicyResolver
{
    private PermissionResolver $permissionResolver;
    private OwnershipResolver $ownershipResolver;

    public function __construct(PermissionResolver $permissionResolver, OwnershipResolver $ownershipResolver)
    {
        $this->permissionResolver = $permissionResolver;
        $this->ownershipResolver = $ownershipResolver;
    }

    /**
     * Resolves model-level policies and validates action permissions and ownership.
     */
    public function check(UserContext $user, string $action, $resource = null): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        // 1. Evaluate general action permission
        if (!$this->permissionResolver->hasPermission($user, $action)) {
            return false;
        }

        // 2. Evaluate target resource ownership if a record instance is provided
        if ($resource !== null) {
            return $this->ownershipResolver->isOwner($user, $resource);
        }

        return true;
    }
}
