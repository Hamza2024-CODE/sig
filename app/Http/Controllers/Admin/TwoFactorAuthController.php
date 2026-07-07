<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Google2faService;
use App\Models\User;
use App\Models\TrustedDevice;
use App\Events\SecurityEventTriggered;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class TwoFactorAuthController extends Controller
{
    protected Google2faService $mfaService;

    public function __construct(Google2faService $mfaService)
    {
        $this->mfaService = $mfaService;
    }

    /**
     * Show MFA setup page.
     */
    public function setup()
    {
        $userSession = session('user');
        if (!$userSession) {
            return redirect()->route('login');
        }

        $loginTable = $userSession['login_table'] ?? 'utilisateur';
        $user = match($loginTable) {
            'etablissement' => \App\Models\Etablissement::find($userSession['id']),
            'encadrement' => \App\Models\Encadrement::find($userSession['id']),
            default => User::find($userSession['id']),
        };
        if (!$user) {
            return redirect()->route('login');
        }

        // Generate a new secret if not already set
        $secret = $user->google2fa_secret ?? $this->mfaService->generateSecretKey();

        if (!$user->google2fa_secret) {
            $user->google2fa_secret = $secret;
            $user->save();
        }

        $usernameValue = match($loginTable) {
            'etablissement' => $user->nomUser,
            'encadrement' => $user->nin,
            default => $user->NomUser,
        };

        $qrCodeUrl = $this->mfaService->getQrCodeUrl(
            config('app.name', 'SGFEP'),
            $usernameValue ?? 'User',
            $secret
        );

        $qrCodeImage = $this->mfaService->getQrCodeImage($qrCodeUrl);

        return view('admin.security.mfa.setup', compact('qrCodeImage', 'secret', 'user'));
    }

    /**
     * Confirm MFA setup by validating first OTP and outputting recovery codes.
     */
    public function confirmSetup(Request $request)
    {
        $request->validate(['otp' => 'required|string']);
        $otp = trim($request->otp);

        $userSession = session('user');
        if (!$userSession) {
            return redirect()->route('login');
        }

        $loginTable = $userSession['login_table'] ?? 'utilisateur';
        $user = match($loginTable) {
            'etablissement' => \App\Models\Etablissement::find($userSession['id']),
            'encadrement' => \App\Models\Encadrement::find($userSession['id']),
            default => User::find($userSession['id']),
        };
        if (!$user) {
            return redirect()->route('login');
        }

        if ($this->mfaService->verifyOtp($user->google2fa_secret, $otp)) {
            $user->mfa_enabled = true;
            $user->mfa_enabled_at = now();
            $user->save();

            // Set session variable so they don't get prompted immediately
            $request->session()->put('mfa_verified', true);
            if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
                @session_start();
            }
            if (session_status() === PHP_SESSION_ACTIVE) {
                $_SESSION['mfa_verified'] = true;
            }

            $userIdValue = match($loginTable) {
                'etablissement' => $user->IDetablissement,
                'encadrement' => $user->IDEncadrement,
                default => $user->IDUtilisateur,
            };

            // Generate recovery codes
            $recoveryCodes = $this->mfaService->generateRecoveryCodes($userIdValue);

            event(new SecurityEventTriggered('MFA_ENABLED', 'info', $user, 'تم تفعيل المصادقة الثنائية بنجاح'));

            return view('admin.security.mfa.recovery-codes', compact('recoveryCodes'));
        }

        return back()->withErrors(['otp' => 'الرمز غير صحيح، يرجى المحاولة مجدداً.']);
    }

    /**
     * Show MFA verification challenge page.
     */
    public function showVerifyForm()
    {
        $userSession = session('user');
        if (!$userSession) {
            return redirect()->route('login');
        }

        return view('admin.security.mfa.verify');
    }

    /**
     * Validate OTP or Recovery Code and authenticate.
     */
    public function verify(Request $request)
    {
        $request->validate([
            'otp' => 'required|string',
            'device_fingerprint' => 'nullable|string'
        ]);
        $otp = trim($request->otp);
        $fingerprint = trim($request->input('device_fingerprint', ''));

        $userSession = session('user');
        if (!$userSession) {
            return redirect()->route('login');
        }

        $loginTable = $userSession['login_table'] ?? 'utilisateur';
        $user = match($loginTable) {
            'etablissement' => \App\Models\Etablissement::find($userSession['id']),
            'encadrement' => \App\Models\Encadrement::find($userSession['id']),
            default => User::find($userSession['id']),
        };
        if (!$user) {
            return redirect()->route('login');
        }

        $mfaSuccess = false;
        $eventMessage = 'تم التحقق من الرمز بنجاح';
        $eventType = 'OTP_SUCCESS';

        // Check if user entered an 11-char recovery code (format: XXXXX-XXXXX)
        if (strlen($otp) === 11 && strpos($otp, '-') === 5) {
            $userIdValue = match($loginTable) {
                'etablissement' => $user->IDetablissement,
                'encadrement' => $user->IDEncadrement,
                default => $user->IDUtilisateur,
            };

            // Validate recovery code
            $dbCodes = DB::table('user_recovery_codes')
                ->where('user_id', $userIdValue)
                ->whereNull('used_at')
                ->get();

            foreach ($dbCodes as $dbCode) {
                if (Hash::check($otp, $dbCode->code_hash)) {
                    // Mark as used
                    DB::table('user_recovery_codes')
                        ->where('id', $dbCode->id)
                        ->update(['used_at' => now()]);

                    $mfaSuccess = true;
                    $eventType = 'RECOVERY_CODE_USED';
                    $eventMessage = 'تم تسجيل الدخول باستخدام رمز الاسترداد الاحتياطي';
                    break;
                }
            }
        } else {
            // Validate standard TOTP OTP
            if ($this->mfaService->verifyOtp($user->google2fa_secret, $otp)) {
                $mfaSuccess = true;
            }
        }

        if ($mfaSuccess) {
            $request->session()->put('mfa_verified', true);
            if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
                @session_start();
            }
            if (session_status() === PHP_SESSION_ACTIVE) {
                $_SESSION['mfa_verified'] = true;
            }

            event(new SecurityEventTriggered($eventType, 'info', $user, $eventMessage));
            $redirectUrl = $request->is('sig/*') || $request->is('sig') ? '/sig/dashboard' : '/dashboard';

            // Register trusted device if requested and fingerprint is present
            if ($request->has('remember_device') && !empty($fingerprint)) {
                $userIdValue = match($loginTable) {
                    'etablissement' => $user->IDetablissement,
                    'encadrement' => $user->IDEncadrement,
                    default => $user->IDUtilisateur,
                };

                $device = TrustedDevice::firstOrNew([
                    'user_id' => $userIdValue,
                    'user_type' => $loginTable,
                    'device_fingerprint' => $fingerprint,
                ]);

                // Extract OS from User Agent for display
                $ua = $request->userAgent() ?? 'Unknown';
                $os = 'Unknown Device';
                if (preg_match('/Windows/i', $ua)) $os = 'Windows PC';
                elseif (preg_match('/Macintosh/i', $ua)) $os = 'macOS Device';
                elseif (preg_match('/Linux/i', $ua)) $os = 'Linux PC';
                elseif (preg_match('/Android/i', $ua)) $os = 'Android Phone';
                elseif (preg_match('/iPhone|iPad/i', $ua)) $os = 'iOS Device';

                $device->device_name = 'Trusted ' . $os;
                $device->ip_address = $request->ip();
                $device->user_agent = $request->userAgent();
                $device->last_activity = now();
                $device->expires_at = now()->addDays(30);
                $device->save();

                event(new SecurityEventTriggered('TRUSTED_DEVICE_ADDED', 'info', $user, 'تم إضافة جهاز موثوق جديد للبصمة الرقمية'));

                // Set encrypted Laravel cookie
                $cookie = cookie('trusted_device_id', $device->id, 60 * 24 * 30); // 30 days
                return redirect($redirectUrl)->withCookie($cookie);
            }

            return redirect($redirectUrl);
        }

        event(new SecurityEventTriggered('OTP_FAILED', 'warning', $user, 'إدخال رمز مصادقة خاطئ'));
        return back()->withErrors(['otp' => 'الرمز المدخل غير صحيح، يرجى المحاولة مجدداً أو استخدام رمز استرداد احتياطي.']);
    }

    /**
     * Display the User's Personal MFA Hub.
     */
    public function index(Request $request)
    {
        $userSession = session('user');
        if (!$userSession) {
            return redirect()->route('login');
        }

        $loginTable = $userSession['login_table'] ?? 'utilisateur';
        $user = match($loginTable) {
            'etablissement' => \App\Models\Etablissement::find($userSession['id']),
            'encadrement' => \App\Models\Encadrement::find($userSession['id']),
            default => User::find($userSession['id']),
        };

        if (!$user) {
            return redirect()->route('login');
        }

        // If MFA is not enabled, redirect them to the setup flow
        if (!$user->mfa_enabled) {
            $setupRoute = $request->is('sig/*') || $request->is('sig') ? '/sig/security/mfa/setup' : '/security/mfa/setup';
            return redirect($setupRoute);
        }

        // Read settings to check if MFA is forced on them
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

        $userIdValue = match($loginTable) {
            'etablissement' => $user->IDetablissement,
            'encadrement' => $user->IDEncadrement,
            default => $user->IDUtilisateur,
        };

        $userKey = $loginTable . '_' . $userIdValue;

        // Determine if MFA is mandatory
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
            if ($loginTable === 'utilisateur') {
                $roleCode = strtolower($userSession['role_code'] ?? 'user');
                $mandatoryRoles = ['admin', 'high_admin', 'central', 'dfep', 'super_admin', 'disi', 'dfm', 'drfp', 'finance', 'hr'];
                if (in_array($roleCode, $mandatoryRoles) || in_array($user->IDNature, [1, 2])) {
                    $isMandatory = true;
                }
            } elseif ($loginTable === 'etablissement') {
                $isMandatory = true;
            }
        }

        // Fetch user's trusted devices
        $trustedDevices = TrustedDevice::where('user_id', $userIdValue)
            ->where('user_type', $loginTable)
            ->where('expires_at', '>', now())
            ->orderBy('last_activity', 'desc')
            ->get();

        return view('admin.security.mfa.status', compact('user', 'isMandatory', 'trustedDevices', 'loginTable'));
    }

    /**
     * Disable MFA for the user's own account (requires password verification).
     */
    public function disable(Request $request)
    {
        $request->validate([
            'password' => 'required|string'
        ]);

        $userSession = session('user');
        if (!$userSession) {
            return redirect()->route('login');
        }

        $loginTable = $userSession['login_table'] ?? 'utilisateur';
        $user = match($loginTable) {
            'etablissement' => \App\Models\Etablissement::find($userSession['id']),
            'encadrement' => \App\Models\Encadrement::find($userSession['id']),
            default => User::find($userSession['id']),
        };

        if (!$user) {
            return redirect()->route('login');
        }

        // 1. Verify password
        $passwordField = ($loginTable === 'utilisateur') ? $user->MotPass : $user->MotDePass;
        if (!Hash::check($request->password, $passwordField) && $request->password !== $passwordField) {
            return back()->withErrors(['password' => 'كلمة المرور المدخلة غير صحيحة.']);
        }

        // 2. Check if MFA is forced on them
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

        $userIdValue = match($loginTable) {
            'etablissement' => $user->IDetablissement,
            'encadrement' => $user->IDEncadrement,
            default => $user->IDUtilisateur,
        };

        $userKey = $loginTable . '_' . $userIdValue;

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
            if ($loginTable === 'utilisateur') {
                $roleCode = strtolower($userSession['role_code'] ?? 'user');
                $mandatoryRoles = ['admin', 'high_admin', 'central', 'dfep', 'super_admin', 'disi', 'dfm', 'drfp', 'finance', 'hr'];
                if (in_array($roleCode, $mandatoryRoles) || in_array($user->IDNature, [1, 2])) {
                    $isMandatory = true;
                }
            } elseif ($loginTable === 'etablissement') {
                $isMandatory = true;
            }
        }

        if ($isMandatory) {
            return back()->withErrors(['password' => 'لا يمكنك تعطيل المصادقة الثنائية لأنها مفروضة إجبارياً على حسابك لتأمين المنصة.']);
        }

        // 3. Deactivate
        $user->mfa_enabled = false;
        $user->google2fa_secret = null;
        $user->mfa_enabled_at = null;
        $user->save();

        // 4. Delete recovery codes and trusted devices
        DB::table('user_recovery_codes')->where('user_id', $userIdValue)->delete();
        DB::table('trusted_devices')->where('user_id', $userIdValue)->where('user_type', $loginTable)->delete();

        // Forget verified session status
        $request->session()->forget('mfa_verified');
        if (session_status() === PHP_SESSION_ACTIVE) {
            unset($_SESSION['mfa_verified']);
        }

        event(new SecurityEventTriggered('MFA_DISABLED', 'warning', $user, 'قام المستخدم بتعطيل المصادقة الثنائية لحسابه الشخصي'));

        $redirectRoute = $request->is('sig/*') ? '/sig/dashboard' : '/dashboard';
        return redirect($redirectRoute)->with('success', 'تم إيقاف وتعطيل المصادقة الثنائية لحسابك بنجاح.');
    }

    /**
     * Regenerate Recovery Codes for the user (invalidates old ones).
     */
    public function regenerateRecoveryCodes(Request $request)
    {
        $request->validate([
            'password' => 'required|string'
        ]);

        $userSession = session('user');
        if (!$userSession) {
            return redirect()->route('login');
        }

        $loginTable = $userSession['login_table'] ?? 'utilisateur';
        $user = match($loginTable) {
            'etablissement' => \App\Models\Etablissement::find($userSession['id']),
            'encadrement' => \App\Models\Encadrement::find($userSession['id']),
            default => User::find($userSession['id']),
        };

        if (!$user || !$user->mfa_enabled) {
            return redirect()->route('login');
        }

        // 1. Verify password
        $passwordField = ($loginTable === 'utilisateur') ? $user->MotPass : $user->MotDePass;
        if (!Hash::check($request->password, $passwordField) && $request->password !== $passwordField) {
            return back()->withErrors(['password' => 'كلمة المرور المدخلة غير صحيحة.']);
        }

        $userIdValue = match($loginTable) {
            'etablissement' => $user->IDetablissement,
            'encadrement' => $user->IDEncadrement,
            default => $user->IDUtilisateur,
        };

        // 2. Generate new recovery codes
        $recoveryCodes = $this->mfaService->generateRecoveryCodes($userIdValue);

        event(new SecurityEventTriggered('RECOVERY_CODES_REGENERATED', 'info', $user, 'تم إعادة توليد رموز الاسترداد الاحتياطية للمستخدم'));

        return view('admin.security.mfa.recovery-codes', compact('recoveryCodes'));
    }

    /**
     * Revoke a specific trusted device.
     */
    public function revokeDevice(Request $request, $id)
    {
        $userSession = session('user');
        if (!$userSession) {
            return redirect()->route('login');
        }

        $loginTable = $userSession['login_table'] ?? 'utilisateur';
        $user = match($loginTable) {
            'etablissement' => \App\Models\Etablissement::find($userSession['id']),
            'encadrement' => \App\Models\Encadrement::find($userSession['id']),
            default => User::find($userSession['id']),
        };

        if (!$user) {
            return redirect()->route('login');
        }

        $userIdValue = match($loginTable) {
            'etablissement' => $user->IDetablissement,
            'encadrement' => $user->IDEncadrement,
            default => $user->IDUtilisateur,
        };

        // Find the device and ensure it belongs to this user
        $device = TrustedDevice::where('id', $id)
            ->where('user_id', $userIdValue)
            ->where('user_type', $loginTable)
            ->first();
        
        if ($device) {
            $deviceName = $device->device_name;
            $device->delete();
            event(new SecurityEventTriggered('TRUSTED_DEVICE_REVOKED', 'info', $user, "إلغاء توثيق الجهاز: {$deviceName}"));
        }

        $redirectRoute = $request->is('sig/*') ? '/sig/security/mfa' : '/security/mfa';
        return redirect($redirectRoute)->with('success', 'تم إلغاء توثيق الجهاز المختار بنجاح.');
    }
}
