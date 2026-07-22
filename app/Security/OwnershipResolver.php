<?php

namespace App\Security;

class OwnershipResolver
{
    private ScopeResolver $scopeResolver;

    public function __construct(ScopeResolver $scopeResolver)
    {
        $this->scopeResolver = $scopeResolver;
    }

    /**
     * Checks if the UserContext is authorized to access/modify the given resource object.
     * Evaluates model or stdClass properties in-memory. Zero database queries.
     */
    public function isOwner(UserContext $user, $resource): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if (!is_object($resource)) {
            return false;
        }

        $allowedEtabs = $this->scopeResolver->resolveAllowedEtablissements($user);
        $allowedWilayas = $this->scopeResolver->resolveAllowedWilayas($user);

        // Check etablissement scopes
        $etabKeys = ['etablissement_id', 'IDetablissement', 'IDEts_Form', 'IDEts_FormM', 'IDets_Form', 'etab_id'];
        $foundEtab = false;
        foreach ($etabKeys as $key) {
            if (property_exists($resource, $key) || isset($resource->$key)) {
                $foundEtab = true;
                $etabVal = (int)$resource->$key;
                if (in_array($etabVal, $allowedEtabs)) {
                    return true;
                }
            }
        }

        // Check wilaya scopes
        $wilayaKeys = ['wilaya_id', 'IDDFEP', 'IDWilayaa'];
        $foundWilaya = false;
        foreach ($wilayaKeys as $key) {
            if (property_exists($resource, $key) || isset($resource->$key)) {
                $foundWilaya = true;
                $wilayaVal = (int)$resource->$key;
                if (in_array($wilayaVal, $allowedWilayas)) {
                    return true;
                }
            }
        }

        // If scoping columns were found but none matched the user scopes, reject access
        if ($foundEtab || $foundWilaya) {
            return false;
        }

        // If the object lacks standard scope properties, fallback to role authorization checks
        return true;
    }
}
