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

    public function authorize(array $user, string $permission, $resource = null): void
    {
        $globalAuth = app(\App\Security\AuthorizationService::class);

        $action = 'view';
        if (str_contains($permission, 'create') || str_contains($permission, 'store') || str_contains($permission, 'add')) {
            $action = 'create';
        } elseif (str_contains($permission, 'update') || str_contains($permission, 'edit')) {
            $action = 'update';
        } elseif (str_contains($permission, 'delete') || str_contains($permission, 'destroy')) {
            $action = 'delete';
        }

        try {
            $resourceObj = null;
            if ($resource !== null) {
                $resourceObj = (object)$resource;
            }
            $globalAuth->authorize($action, $resourceObj);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            throw new Exception($e->getMessage());
        }
    }
}
