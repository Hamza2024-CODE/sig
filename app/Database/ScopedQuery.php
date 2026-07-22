<?php

namespace App\Database;

use App\Security\AuthorizationService;
use App\Security\ScopeResolver;
use Illuminate\Support\Facades\DB;

class ScopedQuery
{
    /**
     * Returns an Eloquent Query Builder scoped to the user's allowed establishments or wilayas.
     */
    public static function for(string $modelClass)
    {
        $model = new $modelClass();
        $builder = $model->newQuery();

        $auth = app(AuthorizationService::class);
        $user = $auth->user();

        if (!$user) {
            return $builder->whereRaw('1=0');
        }

        if ($user->isAdmin()) {
            return $builder;
        }

        $resolver = app(ScopeResolver::class);
        $allowedEtabs = $resolver->resolveAllowedEtablissements($user);
        $allowedWilayas = $resolver->resolveAllowedWilayas($user);

        $table = $model->getTable();

        if (empty($allowedEtabs) && empty($allowedWilayas)) {
            return $builder->whereRaw('1=0');
        }

        switch ($modelClass) {
            case \App\Models\Apprenant::class:
                return $builder->whereExists(function ($query) use ($table, $allowedEtabs) {
                    $query->select(DB::raw(1))
                        ->from('section')
                        ->join('offre', 'section.IDOffre', '=', 'offre.IDOffre')
                        ->whereRaw("{$table}.IDSection = section.IDSection")
                        ->whereIn('offre.IDEts_Form', $allowedEtabs);
                });

            case \App\Models\Candidat::class:
                return $builder->whereExists(function ($query) use ($table, $allowedEtabs) {
                    $query->select(DB::raw(1))
                        ->from('offre')
                        ->whereRaw("{$table}.IDOffre = offre.IDOffre")
                        ->whereIn('offre.IDEts_Form', $allowedEtabs);
                });

            case \App\Models\Offre::class:
                return $builder->whereIn("{$table}.IDEts_Form", $allowedEtabs);

            case \App\Models\Section::class:
                return $builder->whereIn("{$table}.IDEts_Form", $allowedEtabs);

            case \App\Models\Budget::class:
                return $builder->whereIn("{$table}.etablissement_id", $allowedEtabs);

            case \App\Models\Etablissement::class:
                return $builder->whereIn("{$table}.IDetablissement", $allowedEtabs);

            case \App\Models\Encadrement::class:
                return $builder->whereIn("{$table}.IDetablissement", $allowedEtabs);

            default:
                return self::applyGenericFallback($builder, $table, $allowedEtabs, $allowedWilayas);
        }
    }

    private static function applyGenericFallback($builder, string $table, array $allowedEtabs, array $allowedWilayas)
    {
        return $builder->where(function ($query) use ($table, $allowedEtabs, $allowedWilayas) {
            // Apply scopes if etablissement columns exist
            $query->whereIn("{$table}.IDetablissement", $allowedEtabs)
                  ->orWhereIn("{$table}.etablissement_id", $allowedEtabs);

            if (!empty($allowedWilayas)) {
                $query->orWhereIn("{$table}.IDDFEP", $allowedWilayas)
                      ->orWhereIn("{$table}.wilaya_id", $allowedWilayas);
            }
        });
    }
}
