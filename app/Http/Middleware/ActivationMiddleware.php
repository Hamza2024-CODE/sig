<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Helpers\SovereignLicensingHelper;

class ActivationMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // 1. Bypass check for public-facing files, logout, or the activation page itself
        if ($request->is('activate') || $request->is('sig/activate') || $request->is('logout') || $request->is('sig/logout') || $request->is('verify*')) {
            return $next($request);
        }

        // 2. Retrieve the logged-in user from the session
        $user = session('user');
        if (!$user) {
            return $next($request);
        }

        // 3. If activation requirement is ON globally
        if (SovereignLicensingHelper::isActivationRequired()) {
            
            // 4. Exempt only system administrators (admin, DISI) so they can access settings
            $roleCode = strtolower($user['role_code'] ?? '');
            $username = strtolower($user['username'] ?? '');
            if ($roleCode === 'admin' || $username === 'disi' || $username === 'admin') {
                return $next($request);
            }

            // 5. Verify if the user is activated
            $userId = (int)($user['id'] ?? 0);
            if (!SovereignLicensingHelper::isUserActivated($userId)) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'error' => 'activation_required',
                        'message' => 'يجب تفعيل المنصة للوصول إلى هذا المورد. / Activation de la plateforme requise.'
                    ], 403);
                }
                
                if ($request->is('sig/*') || $request->is('sig')) {
                    return redirect()->to(url('sig/activate'))->with('error', 'يجب تفعيل حسابك للاستمرار. / Votre compte doit être activé.');
                }
                
                return redirect()->route('activation.shield')->with('error', 'يجب تفعيل حسابك للاستمرار. / Votre compte doit être activé.');
            }
        }

        return $next($request);
    }
}
