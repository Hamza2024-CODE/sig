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
            $visited = [];
            return self::resolveRecursive($etabId, $visited);
        });
    }

    /**
     * Recursive resolver helper with cycle detection.
     */
    private static function resolveRecursive(int $etabId, array &$visited): array
    {
        if (isset($visited[$etabId])) {
            return [];
        }
        $visited[$etabId] = true;

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
                return [$etabId];
            }

            // Public school: can see itself + children recursively
            $ids = [$etabId];

            $children = DB::table('etablissement')
                ->where(function ($query) use ($etabId) {
                    $query->where('IDEts_Form', $etabId)
                          ->orWhere('DeIDetablissementRatache', $etabId)
                          ->orWhere('DeIDetablissementRatacheInsfp', $etabId);
                })
                ->where('IDetablissement', '!=', $etabId)
                ->pluck('IDetablissement')
                ->toArray();

            foreach ($children as $childId) {
                $childIds = self::resolveRecursive((int)$childId, $visited);
                $ids = array_merge($ids, $childIds);
            }

            return array_values(array_unique(array_filter($ids)));

        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("[EtablissementScope] Error resolving ID {$etabId}: " . $e->getMessage());
            return [$etabId];
        }
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
