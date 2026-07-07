<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * OAuthController — OAuth 2.0 / SSO Integration Layer.
 *
 * Implements an Authorization Code flow compatible with:
 *   - Microsoft Azure AD (for ministry staff)
 *   - Google Workspace (for teacher accounts)
 *   - Custom SAML/OIDC provider (for national identity systems)
 *
 * In production, replace $this->getProviderConfig() with
 * actual client_id / client_secret from .env and use
 * a proper OAuth library (e.g., league/oauth2-client).
 *
 * Currently operates in SIMULATION MODE when SSO_ENABLED=false in .env
 */
class OAuthController extends Controller
{
    /**
     * GET /auth/oauth/{provider}/redirect
     * Redirects the user to the external OAuth provider's authorization page.
     */
    public function redirect(string $provider)
    {
        $config = $this->getProviderConfig($provider);

        if (!$config) {
            return redirect('/login')->with('flash_error', "مزوّد المصادقة ({$provider}) غير مدعوم.");
        }

        // If SSO is disabled in settings, show friendly info page
        if (!$this->isSsoEnabled()) {
            return redirect('/login')->with('flash_info', 'ميزة تسجيل الدخول الموحد (SSO) غير مفعلة حالياً. تواصل مع المدير لتفعيلها.');
        }

        // Generate a state token to prevent CSRF in OAuth callback
        $state = Str::random(40);
        session(['oauth_state' => $state, 'oauth_provider' => $provider]);

        // Build the authorization URL
        $params = http_build_query([
            'client_id'     => $config['client_id'],
            'redirect_uri'  => url('/auth/oauth/' . $provider . '/callback'),
            'response_type' => 'code',
            'scope'         => $config['scope'],
            'state'         => $state,
        ]);

        return redirect($config['auth_url'] . '?' . $params);
    }

    /**
     * GET /auth/oauth/{provider}/callback
     * Handles the provider callback, exchanges code for token, and logs the user in.
     */
    public function callback(Request $request, string $provider)
    {
        // CSRF state verification
        if ($request->input('state') !== session('oauth_state')) {
            return redirect('/login')->with('flash_error', 'فشل التحقق من الأمان (state mismatch). يرجى المحاولة مجدداً.');
        }

        $config = $this->getProviderConfig($provider);
        if (!$config) {
            return redirect('/login')->with('flash_error', 'مزوّد المصادقة غير صالح.');
        }

        // In simulation mode: auto-approve with a mock user profile
        if (!$this->isSsoEnabled()) {
            return $this->simulateLogin($provider);
        }

        $code = $request->input('code');
        if (!$code) {
            return redirect('/login')->with('flash_error', 'لم يتم استلام رمز التفويض من مزوّد المصادقة.');
        }

        try {
            // Exchange authorization code for access token
            $tokenResponse = $this->exchangeCodeForToken($code, $config, $provider);

            if (!isset($tokenResponse['access_token'])) {
                throw new \RuntimeException('لم يتم استلام رمز الوصول من مزوّد المصادقة.');
            }

            // Fetch user profile from provider
            $profile = $this->fetchUserProfile($tokenResponse['access_token'], $config);

            // Find or provision user in local DB
            $user = $this->findOrProvisionUser($profile, $provider);

            // Establish platform session
            $this->establishSession($user);

            \App\Core\AuditLogger::log('SSO_LOGIN', 'utilisateur', $user['id'], [
                'provider' => $provider,
                'email'    => $profile['email'] ?? null,
            ]);

            return redirect('/dashboard')->with('flash_success', 'تم تسجيل دخولك عبر ' . $config['label'] . ' بنجاح.');

        } catch (\Exception $e) {
            return redirect('/login')->with('flash_error', 'فشل تسجيل الدخول الموحد: ' . $e->getMessage());
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Private helpers
    // ─────────────────────────────────────────────────────────────────────────

    private function getProviderConfig(string $provider): ?array
    {
        $providers = [
            'microsoft' => [
                'label'      => 'Microsoft Azure AD',
                'client_id'  => env('AZURE_CLIENT_ID', 'DEMO_CLIENT_ID'),
                'client_secret' => env('AZURE_CLIENT_SECRET', ''),
                'auth_url'   => 'https://login.microsoftonline.com/' . env('AZURE_TENANT_ID', 'common') . '/oauth2/v2.0/authorize',
                'token_url'  => 'https://login.microsoftonline.com/' . env('AZURE_TENANT_ID', 'common') . '/oauth2/v2.0/token',
                'user_url'   => 'https://graph.microsoft.com/v1.0/me',
                'scope'      => 'openid email profile User.Read',
            ],
            'google' => [
                'label'      => 'Google Workspace',
                'client_id'  => env('GOOGLE_CLIENT_ID', 'DEMO_CLIENT_ID'),
                'client_secret' => env('GOOGLE_CLIENT_SECRET', ''),
                'auth_url'   => 'https://accounts.google.com/o/oauth2/v2/auth',
                'token_url'  => 'https://oauth2.googleapis.com/token',
                'user_url'   => 'https://www.googleapis.com/oauth2/v3/userinfo',
                'scope'      => 'openid email profile',
            ],
        ];

        return $providers[$provider] ?? null;
    }

    private function isSsoEnabled(): bool
    {
        return filter_var(env('SSO_ENABLED', false), FILTER_VALIDATE_BOOLEAN);
    }

    private function exchangeCodeForToken(string $code, array $config, string $provider): array
    {
        $ch = curl_init($config['token_url']);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS     => http_build_query([
                'grant_type'    => 'authorization_code',
                'code'          => $code,
                'redirect_uri'  => url('/auth/oauth/' . $provider . '/callback'),
                'client_id'     => $config['client_id'],
                'client_secret' => $config['client_secret'],
            ]),
        ]);
        $body = curl_exec($ch);
        curl_close($ch);
        return json_decode($body, true) ?: [];
    }

    private function fetchUserProfile(string $accessToken, array $config): array
    {
        $ch = curl_init($config['user_url']);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $accessToken],
        ]);
        $body = curl_exec($ch);
        curl_close($ch);
        return json_decode($body, true) ?: [];
    }

    private function findOrProvisionUser(array $profile, string $provider): array
    {
        $email = $profile['email'] ?? $profile['mail'] ?? null;
        if (!$email) {
            throw new \RuntimeException('لم يتم الحصول على بريد إلكتروني من مزوّد المصادقة.');
        }

        // Try to match existing user by email
        $user = DB::selectOne(
            "SELECT IDUtilisateur as id, NomUser as username, Role as role FROM utilisateur WHERE Email = ? LIMIT 1",
            [$email]
        );

        if (!$user) {
            throw new \RuntimeException('لا يوجد حساب مرتبط بهذا البريد الإلكتروني في المنصة. تواصل مع المدير.');
        }

        return (array) $user;
    }

    private function simulateLogin(string $provider): \Illuminate\Http\RedirectResponse
    {
        return redirect('/login')->with('flash_info',
            "محاكاة SSO ({$provider}): النظام في وضع المحاكاة. لتفعيل SSO الحقيقي، أضف SSO_ENABLED=true وبيانات OAuth في ملف .env"
        );
    }

    private function establishSession(array $user): void
    {
        session([
            'authenticated' => true,
            'user_id'       => $user['id'],
            'username'      => $user['username'],
            'role'          => $user['role'] ?? 'user',
            'login_table'   => 'utilisateur',
            'sso_login'     => true,
        ]);
    }
}
