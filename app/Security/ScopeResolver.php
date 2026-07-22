<?php

namespace App\Security;

use App\Support\EtablissementScope;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class ScopeResolver
{
    /**
     * Resolves the allowed establishment IDs for the given UserContext.
     */
    public function resolveAllowedEtablissements(UserContext $user): array
    {
        if ($user->isAdmin()) {
            return []; // Empty signifies global access (no restriction)
        }

        $cacheKey = "user_etab_scope_v2_" . $user->id . "_" . $user->roleCode;
        return Cache::remember($cacheKey, 86400, function () use ($user) {
            $role = $user->roleCode;

            if ($role === 'dfep' && $user->dfepId > 0) {
                return DB::table('etablissement')
                    ->where('IDDFEP', $user->dfepId)
                    ->pluck('IDetablissement')
                    ->toArray();
            }

            if ($user->etablissementId > 0) {
                return EtablissementScope::resolve($user->etablissementId);
            }

            return [];
        });
    }

    /**
     * Resolves the allowed wilaya IDs for the given UserContext.
     */
    public function resolveAllowedWilayas(UserContext $user): array
    {
        if ($user->isAdmin()) {
            return []; // Empty signifies global access
        }

        $cacheKey = "user_wilaya_scope_v2_" . $user->id;
        return Cache::remember($cacheKey, 86400, function () use ($user) {
            if ($user->dfepId > 0) {
                return [$user->dfepId];
            }
            if ($user->wilayaId > 0) {
                return [$user->wilayaId];
            }
            if ($user->etablissementId > 0) {
                $etab = DB::table('etablissement')
                    ->where('IDetablissement', $user->etablissementId)
                    ->select('IDDFEP')
                    ->first();
                if ($etab && $etab->IDDFEP > 0) {
                    return [$etab->IDDFEP];
                }
            }
            return [];
        });
    }
}
