<?php

namespace App\Helpers;

use App\Core\Database;
use PDO;

/**
 * PermissionHelper - WINDEV sgfep compatible
 *
 * Role hierarchy:
 *   admin       → full access (from utilisateur.admin=1)
 *   dfep        → directorate level, all modules (IDNature=4)
 *   central     → admin central (IDNature=2)
 *   etablissement → institution level (nomUser=centre code)
 *   directeur   → center director
 *   employee    → instructor/staff
 *
 * Permissions are derived from role_code + WINDEV privelege tables,
 * with a session fallback so login always works even if privelege tables are empty.
 */
class PermissionHelper
{
    private static array $userCache = [];
    private static array $roleCache = [];

    /**
     * Built-in role permission defaults (used when DB tables are empty).
     * Keys map to permission slugs used in the sidebar/controllers.
     */
    private static array $builtinRolePerms = [
        'admin' => [
            'offres'       => '1', 'inscriptions'  => '1', 'grades'      => '1',
            'discipline'   => '1', 'documents'     => '1', 'repas'       => '1',
            'analytics'    => '1', 'users'         => '1', 'database'    => '1',
            'specialites'  => '1', 'formateurs'    => '1',
            'import_candidats' => '1', 'export_pdf' => '1',
        ],
        'dfep' => [
            'offres'       => '1', 'inscriptions'  => '1', 'grades'      => '1',
            'discipline'   => '1', 'documents'     => '1', 'repas'       => '1',
            'analytics'    => '1', 'users'         => '1', 'database'    => '0',
            'specialites'  => '1', 'formateurs'    => '1',
            'import_candidats' => '1', 'export_pdf' => '1',
        ],
        'central' => [
            'offres'       => '1', 'inscriptions'  => '1', 'grades'      => '1',
            'discipline'   => '1', 'documents'     => '1', 'repas'       => '0',
            'analytics'    => '1', 'users'         => '1', 'database'    => '0',
            'specialites'  => '1', 'formateurs'    => '1',
            'import_candidats' => '1', 'export_pdf' => '1',
        ],
        'etablissement' => [
            'offres'       => '1', 'inscriptions'  => '1', 'grades'      => '1',
            'discipline'   => '1', 'documents'     => '1', 'repas'       => '1',
            'analytics'    => '0', 'users'         => '0', 'database'    => '0',
            'specialites'  => '1', 'formateurs'    => '1',
            'import_candidats' => '1', 'export_pdf' => '1',
        ],
        'directeur' => [
            'offres'       => '1', 'inscriptions'  => '1', 'grades'      => '1',
            'discipline'   => '1', 'documents'     => '1', 'repas'       => '1',
            'analytics'    => '0', 'users'         => '0', 'database'    => '0',
            'specialites'  => '1', 'formateurs'    => '1',
            'import_candidats' => '1', 'export_pdf' => '1',
        ],
        'employee' => [
            'offres'       => '0', 'inscriptions'  => '0', 'grades'      => '0',
            'discipline'   => '0', 'documents'     => '0', 'repas'       => '0',
            'analytics'    => '0', 'users'         => '0', 'database'    => '0',
            'specialites'  => '0', 'formateurs'    => '0',
            'import_candidats' => '0', 'export_pdf' => '0',
        ],
        'special' => [
            'offres'       => '0', 'inscriptions'  => '0', 'grades'      => '0',
            'discipline'   => '0', 'documents'     => '0', 'repas'       => '0',
            'analytics'    => '0', 'users'         => '0', 'database'    => '0',
            'specialites'  => '0', 'formateurs'    => '0',
            'import_candidats' => '0', 'export_pdf' => '0',
        ],
    ];

    /**
     * Check if the current (or given) user has a specific permission.
     */
    public static function has(string $permission, ?int $userId = null): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        if ($userId === null) {
            $userId = $_SESSION['user']['id'] ?? null;
        }

        if (!$userId) {
            return false;
        }

        $userData = self::getUserData($userId);
        if (!$userData) {
            return false;
        }

        $roleCode = strtolower($userData['role_code'] ?? '');

        // Admin always has all permissions
        if ($roleCode === 'admin') {
            return true;
        }

        // Check user-specific permissions (from session or WINDEV privelege tables)
        $userPerms = [];
        if (!empty($userData['permissions'])) {
            $userPerms = is_string($userData['permissions'])
                ? (json_decode($userData['permissions'], true) ?? [])
                : $userData['permissions'];
        }
        if (is_array($userPerms) && isset($userPerms[$permission])) {
            return (string)$userPerms[$permission] === '1';
        }

        // Role-based fallback (try DB, then built-in defaults)
        $rolePerms = self::getRolePermissions($roleCode);
        if (isset($rolePerms[$permission])) {
            return (string)$rolePerms[$permission] === '1';
        }

        return false;
    }

    /**
     * Invalidate cached permission data for a user.
     */
    public static function invalidateCache(?int $userId = null): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        $uid = $userId ?? ($_SESSION['user']['id'] ?? null);
        if ($uid !== null) {
            unset(self::$userCache[$uid]);
            unset($_SESSION['perm_cache']['users'][$uid]);
        }
        self::$roleCache = [];
        unset($_SESSION['perm_cache']['roles']);
    }

    /**
     * Get user data: role_code + permissions.
     * Priority: static cache → session cache → session['user'] → DB
     */
    private static function getUserData(int $userId): ?array
    {
        // 1. Request-level static cache
        if (isset(self::$userCache[$userId])) {
            return self::$userCache[$userId];
        }

        // 2. Session-level cache
        if (isset($_SESSION['perm_cache']['users'][$userId])) {
            $cached = $_SESSION['perm_cache']['users'][$userId];
            self::$userCache[$userId] = $cached;
            return $cached;
        }

        // 3. Session user data (always available after login, most reliable)
        if (isset($_SESSION['user'])) {
            $sessionUser = $_SESSION['user'];
            // Check if this is the logged-in user
            if ((int)($sessionUser['id'] ?? 0) === $userId) {
                $roleCode = strtolower($sessionUser['role_code'] ?? '');
                $data = [
                    'role_code'   => $roleCode,
                    'permissions' => $sessionUser['permissions'] ?? [],
                ];
                self::$userCache[$userId] = $data;
                $_SESSION['perm_cache']['users'][$userId] = $data;
                return $data;
            }
            // Also check by etablissement_id for Channel 2 users (etablissement/directeur)
            // whose 'id' in session is IDetablissement, not IDUtilisateur
            $sessionRole = strtolower($sessionUser['role_code'] ?? '');
            if (in_array($sessionRole, ['etablissement', 'directeur', 'employee']) &&
                (int)($sessionUser['etablissement_id'] ?? 0) === $userId) {
                $data = [
                    'role_code'   => $sessionRole,
                    'permissions' => $sessionUser['permissions'] ?? [],
                ];
                self::$userCache[$userId] = $data;
                $_SESSION['perm_cache']['users'][$userId] = $data;
                return $data;
            }
        }

        // 4. Database fetch (for admin managing other users)
        try {
            $db = Database::getInstance()->getConnection();

            // Try WINDEV utilisateur table + privelege_utilisateur for real WINDEV privileges
            $stmt = $db->prepare("
                SELECT u.IDNature, u.admin
                FROM utilisateur u
                WHERE u.IDUtilisateur = ?
                LIMIT 1
            ");
            $stmt->execute([$userId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row) {
                $roleCode = 'special';
                if ($row['admin'] == 1) {
                    $roleCode = 'admin';
                } elseif ($row['IDNature'] == 4) {
                    $roleCode = 'dfep';
                } elseif ($row['IDNature'] == 2) {
                    $roleCode = 'central';
                } elseif ($row['IDNature'] == 3) {
                    $roleCode = 'directeur';
                } elseif ($row['IDNature'] == 5) {
                    $roleCode = 'stagiaire';
                }

                // Load real permissions from privelege_utilisateur
                // Map WINDEV privelege codes → Laravel permission slugs
                $privMap = [
                    5  => 'offres',        // BTN_2_OFFRE
                    6  => 'inscriptions',  // BTN_2_INSCRIPTION
                    7  => 'inscriptions',  // BTN_2_SECTION (merge)
                    8  => 'inscriptions',  // BTN_2_EFFECTIF
                    10 => 'grades',        // BTN_2_EVAL_SEMEST
                    11 => 'grades',        // BTN_2_EVAL_FINAL
                    12 => 'documents',     // BTN_2_ATTESTATION
                    102 => 'discipline',   // BTN_2_SUIVI
                    104 => 'repas',        // BTN_3_BOURSEPSALAIRE
                ];

                $perms = [];
                try {
                    $stmtP = $db->prepare("
                        SELECT pu.IDPrivelege, pu.DroiAjout, pu.DroiModif, pu.DroitSuppr, pu.DroitTous,
                               p.code
                        FROM privelege_utilisateur pu
                        INNER JOIN privelege p ON pu.IDPrivelege = p.IDPrivelege
                        WHERE pu.IDUtilisateur = ?
                    ");
                    $stmtP->execute([$userId]);
                    $privRows = $stmtP->fetchAll(PDO::FETCH_ASSOC);

                    foreach ($privRows as $pr) {
                        $code = (int)$pr['code'];
                        if (isset($privMap[$code])) {
                            $slug = $privMap[$code];
                            // Has at least one right set → permission granted
                            $hasAccess = ($pr['DroiAjout'] || $pr['DroiModif'] ||
                                          $pr['DroitSuppr'] || $pr['DroitTous']) ? '1' : '0';
                            // Once granted, don't downgrade
                            if (!isset($perms[$slug]) || $hasAccess === '1') {
                                $perms[$slug] = $hasAccess;
                            }
                        }
                    }
                } catch (\Exception $ep) {
                    // privelege_utilisateur may be empty — use role defaults
                }

                $data = ['role_code' => $roleCode, 'permissions' => $perms];
                self::$userCache[$userId] = $data;
                $_SESSION['perm_cache']['users'][$userId] = $data;
                return $data;
            }
        } catch (\Exception $e) {
            // Silently fall through
        }

        return null;
    }

    /**
     * Get role-level permissions.
     * Priority: static cache → session cache → built-in defaults
     */
    private static function getRolePermissions(string $roleCode): array
    {
        $roleCode = strtolower($roleCode);

        if (isset(self::$roleCache[$roleCode])) {
            return self::$roleCache[$roleCode];
        }

        if (isset($_SESSION['perm_cache']['roles'][$roleCode])) {
            $cached = $_SESSION['perm_cache']['roles'][$roleCode];
            self::$roleCache[$roleCode] = $cached;
            return $cached;
        }

        // Try to read from config/roles.json first
        // BASE_PATH may not be defined in Legacy context — resolve relative to this file
        $rolesJsonPath = defined('BASE_PATH')
            ? BASE_PATH . '/config/roles.json'
            : dirname(__DIR__, 3) . '/config/roles.json';
        $perms = null;
        if (file_exists($rolesJsonPath)) {
            $rolesData = json_decode(file_get_contents($rolesJsonPath), true);
            if (is_array($rolesData)) {
                foreach ($rolesData as $r) {
                    if (strtolower($r['code'] ?? '') === $roleCode) {
                        $permsStr = $r['permissions'] ?? '{}';
                        $perms = is_string($permsStr) ? (json_decode($permsStr, true) ?? []) : $permsStr;
                        break;
                    }
                }
            }
        }

        // Fallback to built-in defaults if not found in JSON
        if ($perms === null) {
            $perms = self::$builtinRolePerms[$roleCode] ?? [];
        }

        self::$roleCache[$roleCode] = $perms;
        $_SESSION['perm_cache']['roles'][$roleCode] = $perms;
        return $perms;
    }
}
