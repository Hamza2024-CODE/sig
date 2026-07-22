<?php

namespace App\Domains\Academic\Services;

use Illuminate\Support\Facades\DB;

class GradeWindowService
{
    /**
     * Check if a user has access to enter/edit grades for a specific offer and semester.
     *
     * Returns an array:
     * [
     *     'allowed' => bool,     // Can access the input view
     *     'allow_edit' => bool,  // Can submit/save grades
     *     'reason' => string,    // Reason for the decision
     *     'window' => array|null // The active window record, if any
     * ]
     */
    public static function checkAccess(array $user, int $offreId, int $semestre): array
    {
        $role = strtolower($user['role_code'] ?? '');

        // 1. Administrators/Central roles have full unrestricted read/write access at all times
        if (in_array($role, ['admin', 'central', 'high_admin', 'secretaire_general', 'ministre'])) {
            return [
                'allowed' => true,
                'allow_edit' => true,
                'reason' => 'admin_bypass',
                'window' => null
            ];
        }

        // 2. DFEP (Directorate) is always read-only (supervision only)
        if ($role === 'dfep') {
            return [
                'allowed' => true,
                'allow_edit' => false,
                'reason' => 'dfep_supervision_only',
                'window' => null
            ];
        }

        // 3. Fetch offer metadata to determine wilaya/etablissement identifiers
        $offre = DB::selectOne("
            SELECT o.IDOffre as id, o.IDEts_Form as etablissement_id, e.IDDFEP as dfep_id
            FROM offre o
            JOIN etablissement e ON o.IDEts_Form = e.IDetablissement
            WHERE o.IDOffre = ?
        ", [$offreId]);

        if (!$offre) {
            return [
                'allowed' => false,
                'allow_edit' => false,
                'reason' => 'offre_not_found',
                'window' => null
            ];
        }

        // 4. Retrieve any active time windows
        $now = date('Y-m-d H:i:s');
        $windows = DB::select("
            SELECT * FROM grade_windows 
            WHERE ? BETWEEN date_ouverture AND date_cloture
            ORDER BY scope_type = 'etablissement' DESC, scope_type = 'wilaya' DESC, scope_type = 'global' DESC
        ", [$now]);

        foreach ($windows as $w) {
            $w = (array)$w;

            // Check if semestre restriction matches
            if ($w['semestre'] !== null && (int)$w['semestre'] !== $semestre) {
                continue;
            }

            // Match scope
            if ($w['scope_type'] === 'global') {
                return [
                    'allowed' => true,
                    'allow_edit' => (bool)$w['allow_edit'],
                    'reason' => 'global_active_window',
                    'window' => $w
                ];
            }

            if ($w['scope_type'] === 'wilaya' && (int)$w['scope_id'] === (int)$offre->dfep_id) {
                return [
                    'allowed' => true,
                    'allow_edit' => (bool)$w['allow_edit'],
                    'reason' => 'wilaya_active_window',
                    'window' => $w
                ];
            }

            if ($w['scope_type'] === 'etablissement' && (int)$w['scope_id'] === (int)$offre->etablissement_id) {
                return [
                    'allowed' => true,
                    'allow_edit' => (bool)$w['allow_edit'],
                    'reason' => 'etablissement_active_window',
                    'window' => $w
                ];
            }
        }

        // 5. If no active window is found, search for a scheduled future window to display info to the user
        $nextWindow = DB::selectOne("
            SELECT * FROM grade_windows 
            WHERE date_ouverture > ?
            ORDER BY date_ouverture ASC
            LIMIT 1
        ", [$now]);

        return [
            'allowed' => false,
            'allow_edit' => false,
            'reason' => 'no_active_window',
            'window' => null,
            'next_window' => $nextWindow ? (array)$nextWindow : null
        ];
    }
}
