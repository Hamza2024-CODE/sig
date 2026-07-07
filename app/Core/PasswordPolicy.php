<?php

namespace App\Core;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;

class PasswordPolicy
{
    /**
     * Validate password against system security policy.
     * Returns true if valid, or a string error message if invalid.
     */
    public static function validate(string $password, ?array $userSession = null): bool|string
    {
        $policy = config('security.password_policy', [
            'min_length' => 10,
            'require_uppercase' => true,
            'require_lowercase' => true,
            'require_digits' => true,
            'require_special' => true,
            'allow_nin_as_password' => false,
        ]);

        $minLen = $policy['min_length'] ?? 10;
        if (strlen($password) < $minLen) {
            return "يجب أن تكون كلمة المرور مكونة من {$minLen} أحرف أو أكثر.";
        }

        if (($policy['require_uppercase'] ?? false) && !preg_match('/[A-Z]/', $password)) {
            return "يجب أن تحتوي كلمة المرور على حرف كبير واحد على الأقل (A-Z).";
        }

        if (($policy['require_lowercase'] ?? false) && !preg_match('/[a-z]/', $password)) {
            return "يجب أن تحتوي كلمة المرور على حرف صغير واحد على الأقل (a-z).";
        }

        if (($policy['require_digits'] ?? false) && !preg_match('/[0-9]/', $password)) {
            return "يجب أن تحتوي كلمة المرور على رقم واحد على الأقل (0-9).";
        }

        if (($policy['require_special'] ?? false) && !preg_match('/[^A-Za-z0-9]/', $password)) {
            return "يجب أن تحتوي كلمة المرور على رمز خاص واحد على الأقل (مثل @, #, $, %, etc.).";
        }

        // Prevent setting username as password
        if ($userSession) {
            $username = strtolower($userSession['username'] ?? '');
            if (!empty($username) && (strtolower($password) === $username || strpos(strtolower($password), $username) !== false)) {
                return "لا يمكن أن تحتوي كلمة المرور على اسم المستخدم الخاص بك لدواعي أمنية.";
            }

            // Prevent setting NIN as password
            if (!($policy['allow_nin_as_password'] ?? false)) {
                $userId = $userSession['id'] ?? null;
                $loginTable = $userSession['login_table'] ?? '';

                if ($userId && $loginTable === 'encadrement') {
                    $enc = DB::table('encadrement')->where('IDEncadrement', $userId)->first();
                    if ($enc && !empty($enc->nin)) {
                        $rawNin = $enc->nin;
                        try {
                            $rawNin = Crypt::decryptString($enc->nin);
                        } catch (\Exception $e) {}

                        $rawNin = trim($rawNin);
                        if (!empty($rawNin) && (strpos($password, $rawNin) !== false || strpos($rawNin, $password) !== false)) {
                            return "يجب ألا تطابق كلمة المرور الرقم الوطني للتعريف الخاص بك (NIN) لدواعي أمنية.";
                        }
                    }
                }
            }
        }

        return true;
    }
}