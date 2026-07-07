<?php

namespace App\Domains\Security;

class PermissionResolver
{
    private array $roleMatrix = [
        'admin'         => ['*'],
        'dfep'          => ['diploma.view', 'diploma.print', 'offer.view', 'offer.validate', 'reports.export', 'reports.export_pdf', 'inscriptions.validate', 'import_candidats', 'grades.store', 'discipline.manage'],
        'directeur'     => ['diploma.view', 'diploma.generate', 'diploma.print', 'offer.view', 'reports.export', 'reports.export_pdf', 'inscriptions.validate', 'import_candidats', 'grades.store', 'discipline.manage'],
        'etablissement' => ['diploma.view', 'diploma.generate', 'diploma.print', 'offer.view', 'reports.export', 'reports.export_pdf', 'inscriptions.validate', 'import_candidats', 'grades.store', 'discipline.manage'],
        'formateur'     => ['diploma.view', 'offer.view', 'grades.store'],
        'stagiaire'     => ['diploma.view_own', 'grades.view_own'],
        'central'       => ['*'],
    ];

    /**
     * Check if a role is authorized for a specific permission key
     */
    public function hasPermission(string $role, string $permission): bool
    {
        $permissions = $this->roleMatrix[strtolower($role)] ?? [];
        if (in_array('*', $permissions)) {
            return true;
        }
        return in_array($permission, $permissions);
    }
}
