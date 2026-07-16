<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class EtablissementScope
{
    /**
     * Resolve the allowed establishment IDs for a given establishment.
     *
     * @param int|null $etabId
     * @return array
     */
    public static function resolve(?int $etabId): array
    {
        if (!$etabId || $etabId <= 0) {
            return [];
        }

        return Cache::remember("etab_scope_{$etabId}", 600, function () use ($etabId) {
            try {
                $etabRow = DB::table('etablissement')
                    ->where('IDetablissement', $etabId)
                    ->select('PublPrive')
                    ->first();

                if (!$etabRow) {
                    return [];
                }

                $isPrivate = ((int)($etabRow->PublPrive ?? 0) === 1);

                if ($isPrivate) {
                    // Private school: strict isolation.
                    // A private school can only see its own data.
                    return [$etabId];
                }

                // Public school: can see itself, its sub-branches, and supervised private schools.
                $ids = [$etabId];

                // 1. Direct sub-branches (annexes)
                $branches = DB::table('etablissement')
                    ->where('IDEts_Form', $etabId)
                    ->pluck('IDetablissement')
                    ->toArray();
                $ids = array_merge($ids, $branches);

                // 2. Supervised private schools (where this public center is the supervisor or parent coordinator)
                $supervised = DB::table('etablissement')
                    ->where('PublPrive', 1)
                    ->where(function ($query) use ($etabId) {
                        $query->where('DeIDetablissementRatache', $etabId)
                              ->orWhere('DeIDetablissementRatacheInsfp', $etabId)
                              ->orWhere('IDEts_Form', $etabId);
                    })
                    ->pluck('IDetablissement')
                    ->toArray();
                $ids = array_merge($ids, $supervised);

                return array_values(array_unique(array_filter($ids)));

            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error("[EtablissementScope] Error resolving ID {$etabId}: " . $e->getMessage());
                return [$etabId];
            }
        });
    }

    /**
     * Clear cache for a specific establishment scope or all scopes.
     *
     * @param int|null $etabId
     * @return void
     */
    public static function clearCache(?int $etabId = null): void
    {
        if ($etabId && $etabId > 0) {
            Cache::forget("etab_scope_{$etabId}");
        } else {
            Cache::flush();
        }
    }
}
