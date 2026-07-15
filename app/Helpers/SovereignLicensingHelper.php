<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class SovereignLicensingHelper
{
    const DEVELOPER_NAME = 'Hamza Boubakare Seddike';
    const CACHE_TTL = 3600; // 1 hour

    /**
     * Get a setting value with caching
     */
    public static function getSetting(string $key, $default = null)
    {
        return Cache::remember("sovereign:setting:{$key}", self::CACHE_TTL, function () use ($key, $default) {
            try {
                $row = DB::table('platform_settings')->where('key', $key)->first();
                return $row ? $row->value : $default;
            } catch (\Throwable $e) {
                return $default;
            }
        });
    }

    /**
     * Set a setting value and clear cache
     */
    public static function setSetting(string $key, $value): void
    {
        DB::table('platform_settings')->updateOrInsert(
            ['key' => $key],
            ['value' => (string)$value, 'updated_at' => now()]
        );
        Cache::forget("sovereign:setting:{$key}");
    }

    /**
     * Generate developer signature HMAC secret
     */
    private static function getSecret(): string
    {
        return hash('sha256', self::DEVELOPER_NAME);
    }

    /**
     * Generate a cryptographic license key
     */
    public static function generateLicenseKey(): string
    {
        $secret = self::getSecret();
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $randomPart = '';
        for ($i = 0; $i < 12; $i++) {
            $randomPart .= $chars[random_int(0, strlen($chars) - 1)];
        }
        
        $payload = 'GFEP-' . substr($randomPart, 0, 4) . '-' . substr($randomPart, 4, 4) . '-' . substr($randomPart, 8, 4);
        $hmac = hash_hmac('sha256', $payload, $secret);
        $checksum = strtoupper(substr($hmac, 0, 8));
        
        return $payload . '-' . substr($checksum, 0, 4) . '-' . substr($checksum, 4, 4);
    }

    /**
     * Get the dynamic offline Master Key
     */
    public static function getMasterKey(): string
    {
        $secret = self::getSecret();
        $payload = 'GFEP-MASTER-KEY';
        $hmac = hash_hmac('sha256', $payload, $secret);
        $checksum = strtoupper(substr($hmac, 0, 8));
        return $payload . '-' . substr($checksum, 0, 4) . '-' . substr($checksum, 4, 4);
    }

    /**
     * Verify if a key is the offline Master Key
     */
    public static function isMasterKey(string $key): bool
    {
        return hash_equals(self::getMasterKey(), strtoupper(trim($key)));
    }

    /**
     * Verify the cryptographic validity of a license key format and signature
     */
    public static function verifyLicenseKeyFormat(string $key): bool
    {
        $key = strtoupper(trim($key));
        
        // Check if it's the Master Key
        if (self::isMasterKey($key)) {
            return true;
        }

        if (strlen($key) !== 29 || strpos($key, 'GFEP-') !== 0) {
            return false;
        }
        
        $payload = substr($key, 0, 19);
        $checksumPart = substr($key, 20); // AAAA-BBBB
        $checksumClean = str_replace('-', '', $checksumPart);
        
        $expectedHmac = hash_hmac('sha256', $payload, self::getSecret());
        $expectedChecksum = strtoupper(substr($expectedHmac, 0, 8));
        
        return hash_equals($expectedChecksum, $checksumClean);
    }

    /**
     * Check if activation is required globally
     */
    public static function isActivationRequired(): bool
    {
        return false; // Disabled globally to prevent blocking users
    }

    /**
     * Check if a specific user has a valid active license key
     */
    public static function isUserActivated(int $userId): bool
    {
        return true; // Disabled globally to prevent blocking users
        return Cache::remember("sovereign:user_active:{$userId}", 300, function () use ($userId) {
            try {
                // If master bypass is active globally in settings
                if (self::getSetting('master_bypass_active', '0') === '1') {
                    return true;
                }

                // Check user's license key status
                $activeLicense = DB::table('license_keys')
                    ->where('user_id', $userId)
                    ->whereNotNull('activated_at')
                    ->where('expires_at', '>', now())
                    ->first();
                
                if ($activeLicense) {
                    return true;
                }

                // Also check if the user belongs to an establishment that is activated
                // 1. Get establishment ID from session first (if available and matching current user ID)
                $sessionUser = session('user');
                $etsId = null;
                if ($sessionUser && (int)($sessionUser['id'] ?? 0) === $userId) {
                    $etsId = $sessionUser['etablissement_id'] ?? null;
                }
                
                // 2. Fallback to utilisateur table if not found in session
                if (!$etsId) {
                    $user = DB::table('utilisateur')->where('IDUtilisateur', $userId)->first();
                    if ($user) {
                        $etsId = self::getProp($user, 'IDBureau');
                    }
                }
                
                if ($etsId) {
                    // Fetch both IDs (IDetablissement and IDEts_Form) to match against license keys
                    $etsIds = [$etsId];
                    try {
                        $etsRow = DB::table('etablissement')
                            ->where('IDetablissement', $etsId)
                            ->orWhere('IDEts_Form', $etsId)
                            ->first();
                        if ($etsRow) {
                            $dbId = self::getProp($etsRow, 'IDetablissement');
                            $dbForm = self::getProp($etsRow, 'IDEts_Form');
                            if ($dbId) $etsIds[] = (int)$dbId;
                            if ($dbForm) $etsIds[] = (int)$dbForm;
                        }
                    } catch (\Throwable $e) {}

                    $etsIds = array_unique(array_filter($etsIds));

                    $etsLicense = DB::table('license_keys')
                        ->whereIn('ets_id', $etsIds)
                        ->whereNotNull('activated_at')
                        ->where('expires_at', '>', now())
                        ->first();
                    if ($etsLicense) {
                        return true;
                    }
                }
            } catch (\Throwable $e) {
                // If DB has an issue but they activated session-wise, we handle it gracefully.
            }

            return false;
        });
    }

    /**
     * Get a property from an object case-insensitively
     */
    public static function getProp($obj, string $prop)
    {
        if (!$obj) return null;
        $lowerProp = strtolower($prop);
        if (is_array($obj)) {
            foreach ($obj as $k => $v) {
                if (strtolower($k) === $lowerProp) {
                    return $v;
                }
            }
        } elseif (is_object($obj)) {
            foreach (get_object_vars($obj) as $k => $v) {
                if (strtolower($k) === $lowerProp) {
                    return $v;
                }
            }
            if (isset($obj->$prop)) {
                return $obj->$prop;
            }
            $lowerPropAlt = strtolower($prop);
            if (isset($obj->$lowerPropAlt)) {
                return $obj->$lowerPropAlt;
            }
        }
        return null;
    }

    /**
     * Activate a key for a user
     */
    public static function activateKey(string $key, int $userId): array
    {
        $key = strtoupper(trim($key));
        
        // 1. Master Key Offline Bypass
        if (self::isMasterKey($key)) {
            self::setSetting('master_bypass_active', '1');
            Cache::forget("sovereign:user_active:{$userId}");
            return [
                'success' => true,
                'message' => 'تم تفعيل نظام الطوارئ بنجاح عبر مفتاح المطور! / Activation d\'urgence via Master Key réussie.',
                'expires_at' => now()->addYears(99)
            ];
        }

        // 2. Cryptographic Validation
        if (!self::verifyLicenseKeyFormat($key)) {
            return ['success' => false, 'message' => 'رمز التفعيل غير صالح بنيوياً. / Code d\'activation invalide.'];
        }

        // 3. Database Check
        try {
            $license = DB::table('license_keys')->where('license_key', $key)->first();
            if (!$license) {
                return ['success' => false, 'message' => 'رمز التفعيل غير مسجل في النظام. / Code d\'activation non enregistré.'];
            }

            if ($license->activated_at !== null) {
                return ['success' => false, 'message' => 'تم استخدام رمز التفعيل هذا مسبقاً. / Code d\'activation déjà utilisé.'];
            }

            // Fetch the user details based on login_table session variable
            $sessionUser = session('user');
            $user = null;
            $userEtsId = null;
            
            if ($sessionUser && (int)($sessionUser['id'] ?? 0) === $userId) {
                $loginTable = $sessionUser['login_table'] ?? 'utilisateur';
                if ($loginTable === 'etablissement') {
                    $user = DB::table('etablissement')->where('IDetablissement', $userId)->first();
                    $userEtsId = $sessionUser['etablissement_id'] ?? ($user ? (self::getProp($user, 'IDetablissement') ?? $userId) : $userId);
                } else {
                    $user = DB::table('utilisateur')->where('IDUtilisateur', $userId)->first();
                    $userEtsId = $user ? self::getProp($user, 'IDBureau') : null;
                }
            } else {
                // Fallback if session doesn't match or is empty
                $user = DB::table('utilisateur')->where('IDUtilisateur', $userId)->first();
                $userEtsId = $user ? self::getProp($user, 'IDBureau') : null;
            }

            if (!$user) {
                return ['success' => false, 'message' => 'حساب المستخدم غير موجود. / Utilisateur introuvable.'];
            }

            // A. Check if the key was pre-assigned to a different user
            if ($license->user_id !== null && (int)$license->user_id !== $userId) {
                return ['success' => false, 'message' => 'هذا الرمز مخصص لحساب آخر فقط. / Ce code est réservé à un autre compte.'];
            }

            // B. Check if the key was pre-assigned to a different establishment
            if ($license->ets_id !== null) {
                $isEtsMatch = false;
                try {
                    if ((int)$license->ets_id === (int)$userEtsId) {
                        $isEtsMatch = true;
                    } else {
                        // Check if ets_id matches either column in database for this user's establishment ID
                        $userEtsRow = DB::table('etablissement')
                            ->where('IDetablissement', $userEtsId)
                            ->orWhere('IDEts_Form', $userEtsId)
                            ->first();
                        
                        if ($userEtsRow) {
                            $dbId = self::getProp($userEtsRow, 'IDetablissement');
                            $dbForm = self::getProp($userEtsRow, 'IDEts_Form');
                            if ((int)$license->ets_id === (int)$dbId || (int)$license->ets_id === (int)$dbForm) {
                                $isEtsMatch = true;
                            }
                        }
                    }
                } catch (\Throwable $e) {}

                if (!$isEtsMatch) {
                    return ['success' => false, 'message' => 'هذا الرمز مخصص لمؤسسة أخرى فقط. / Ce code est réservé à une autre institution.'];
                }
            }

            // Expiry is 365 days
            $activatedAt = now();
            $expiresAt = now()->addDays(365);

            DB::table('license_keys')
                ->where('id', $license->id)
                ->update([
                    'user_id'      => $userId,
                    'ets_id'       => $userEtsId ?: $license->ets_id, // Save user's establishment if none was set
                    'activated_at' => $activatedAt,
                    'expires_at'   => $expiresAt,
                ]);

            // Clear cache
            Cache::forget("sovereign:user_active:{$userId}");
            
            return [
                'success' => true,
                'message' => 'تم تفعيل النظام بنجاح لمدة 365 يوماً! / Activation réussie pour 365 jours.',
                'expires_at' => $expiresAt
            ];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => 'خطأ في قاعدة البيانات: ' . $e->getMessage()];
        }
    }

    /**
     * Check if enrollment actions (add, edit, delete, export) are permitted globally or per Wilaya
     */
    public static function checkEnrollmentPermission(string $action, ?int $wilayaId = null): bool
    {
        // 1. Check global toggle
        $globalKey = "enrollment_{$action}_enabled";
        $globalEnabled = self::getSetting($globalKey, '1') === '1';
        if (!$globalEnabled) {
            return false;
        }

        // 2. Check per-Wilaya override
        if ($wilayaId !== null && $wilayaId > 0) {
            $restrictedKey = "enrollment_restricted_wilayas_{$action}";
            $restrictedWilayas = self::getSetting($restrictedKey, '');
            $restrictedArray = array_filter(array_map('trim', explode(',', $restrictedWilayas)));
            if (in_array((string)$wilayaId, $restrictedArray)) {
                return false;
            }
        }

        return true;
    }
}
