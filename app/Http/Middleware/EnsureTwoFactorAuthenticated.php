<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureTwoFactorAuthenticated
{
    // الأدوار الإدارية الحساسة التي يُفرض عليها MFA
    protected array $mandatoryRoles = [
        'Super_Admin', 'DISI', 'DFM', 'DRFP', 'Finance', 'HR', 'admin', 'high_admin', 'central'
    ];

    public function handle(Request $request, Closure $next)
    {
        // Exclude MFA routes, logout, activation
        $path = trim($request->getPathInfo(), '/');
        $excludedPaths = [
            'security/mfa/setup',
            'sig/security/mfa/setup',
            'security/mfa/verify',
            'sig/security/mfa/verify',
            'security/mfa/recovery-codes',
            'sig/security/mfa/recovery-codes',
            'logout',
            'sig/logout',
            'activate',
            'sig/activate'
        ];

        foreach ($excludedPaths as $excluded) {
            if ($path === $excluded || strpos($path, $excluded . '/') === 0) {
                return $next($request);
            }
        }

        if ($request->routeIs('security.mfa.*') || $request->routeIs('logout') || $request->routeIs('activation.*')) {
            return $next($request);
        }

        // Get authenticated user session
        $userSession = session('user');
        if (!$userSession) {
            return $next($request);
        }

        $userId = $userSession['id'] ?? null;
        if (!$userId) {
            return $next($request);
        }

        // Dynamically resolve the model based on the session's login table
        $loginTable = $userSession['login_table'] ?? 'utilisateur';
        $user = null;
        if ($loginTable === 'utilisateur') {
            $user = \App\Models\User::find($userId);
        } elseif ($loginTable === 'etablissement') {
            $user = \App\Models\Etablissement::find($userId);
        } elseif ($loginTable === 'encadrement') {
            $user = \App\Models\Encadrement::find($userId);
        }

        if (!$user) {
            return $next($request);
        }

        // Read global and per-user MFA settings from registry
        $settingsFile = base_path('storage/mfa_settings.json');
        $mfaSettings = [
            'global_mode' => 'everyone',
            'forced_users' => [],
            'exempted_users' => []
        ];
        if (file_exists($settingsFile)) {
            $mfaSettings = json_decode(file_get_contents($settingsFile), true) ?: $mfaSettings;
        }

        $globalMode = $mfaSettings['global_mode'] ?? 'everyone';
        $forcedUsers = $mfaSettings['forced_users'] ?? [];
        $exemptedUsers = $mfaSettings['exempted_users'] ?? [];

        $userKey = $loginTable . '_' . $userId;

        // Determine if MFA is mandatory for this specific user
        $isMandatory = false;
        if (in_array($userKey, $exemptedUsers)) {
            $isMandatory = false;
        } elseif ($globalMode === 'everyone') {
            $isMandatory = true;
        } elseif ($globalMode === 'disabled') {
            $isMandatory = false;
        } elseif (in_array($userKey, $forcedUsers)) {
            $isMandatory = true;
        } elseif ($globalMode === 'sensitive') {
            // Check if user has an administrative role or is an establishment
            if ($loginTable === 'utilisateur') {
                $roleCode = strtolower($userSession['role_code'] ?? 'user');
                $mandatoryRoles = ['admin', 'high_admin', 'central', 'dfep', 'super_admin', 'disi', 'dfm', 'drfp', 'finance', 'hr'];
                if (in_array($roleCode, $mandatoryRoles)) {
                    $isMandatory = true;
                }
            } elseif ($loginTable === 'etablissement') {
                $isMandatory = true; // Centers and institutes are always sensitive
            }
        }

        // 1. If MFA is mandatory for this user and they haven't enabled it, force setup
        if ($isMandatory && !$user->mfa_enabled) {
            $setupRoute = $request->is('sig/*') || $request->is('sig') ? '/sig/security/mfa/setup' : '/security/mfa/setup';
            return redirect($setupRoute)->with('warning', 'الامتثال الأمني للمنصة يفرض عليك تفعيل المصادقة الثنائية (MFA) لتأمين حسابك قبل الاستمرار.');
        }

        // 2. If MFA is enabled (either forced or voluntary), verify challenge
        if ($user->mfa_enabled) {
            $hasPassedMfa = $request->session()->get('mfa_verified', false);
            $isTrustedDevice = $this->isDeviceTrusted($request, $user, $loginTable);

            if (!$hasPassedMfa && !$isTrustedDevice) {
                // Fire security event: MFA challenge issued
                event(new \App\Events\SecurityEventTriggered('MFA_CHALLENGE_ISSUED', 'info', $user, 'تحدي المصادقة الثنائية مطلوب'));

                $verifyRoute = $request->is('sig/*') || $request->is('sig') ? '/sig/security/mfa/verify' : '/security/mfa/verify';
                return redirect($verifyRoute);
            }
        }

        return $next($request);
    }

    protected function isDeviceTrusted(Request $request, $user, string $loginTable): bool
    {
        // 1. هل يمتلك المتصفح ملف تعريف الارتباط (Cookie) الخاص بالجهاز الموثوق؟
        $deviceId = $request->cookie('trusted_device_id');

        if (!$deviceId) {
            return false;
        }

        // Get correct user ID based on table primary key
        $userIdValue = match($loginTable) {
            'etablissement' => $user->IDetablissement,
            'encadrement' => $user->IDEncadrement,
            default => $user->IDUtilisateur,
        };

        // 2. هل هذا الجهاز موجود في قاعدة البيانات، يخص هذا المستخدم، وصالح للاستخدام؟
        $trustedDevice = \App\Models\TrustedDevice::where('id', $deviceId)
            ->where('user_id', $userIdValue)
            ->where('user_type', $loginTable)
            ->where('expires_at', '>', now())
            ->first();

        if ($trustedDevice) {
            // تحديث آخر نشاط لتتبع الأجهزة (مع تجديد الـ IP إذا تغير)
            $trustedDevice->update([
                'last_activity' => now(),
                'ip_address' => $request->ip()
            ]);
            return true;
        }

        return false;
    }
}
