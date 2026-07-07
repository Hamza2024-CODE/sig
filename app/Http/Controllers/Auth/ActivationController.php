<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Helpers\SovereignLicensingHelper;
use App\Core\AuditLogger;

class ActivationController extends Controller
{
    /**
     * Helper to redirect to correct path based on sig prefix.
     */
    private function redirectTarget(string $path)
    {
        if (request()->is('sig/*') || request()->is('sig')) {
            return redirect()->to(url('sig/' . ltrim($path, '/')));
        }
        return redirect()->to(url($path));
    }

    /**
     * Show the activation shield page.
     */
    public function showShield()
    {
        $user = session('user');
        if (!$user) {
            return $this->redirectTarget('login');
        }

        // If activation is not required or the user is already activated, redirect to dashboard
        if (!SovereignLicensingHelper::isActivationRequired() || SovereignLicensingHelper::isUserActivated((int)$user['id'])) {
            return $this->redirectTarget('dashboard');
        }

        return view('auth.activate', [
            'title' => 'درع التفعيل — Activation Shield',
            'user' => $user
        ]);
    }

    /**
     * Handle the activation key submission.
     */
    public function activate(Request $request)
    {
        $user = session('user');
        if (!$user) {
            return $this->redirectTarget('login');
        }

        $key = trim($request->input('activation_key', ''));
        if (empty($key)) {
            return $this->redirectTarget('activate')->with('error', 'يرجى إدخال رمز التفعيل. / Veuillez entrer le code d\'activation.');
        }

        $userId = (int)$user['id'];
        $result = SovereignLicensingHelper::activateKey($key, $userId);

        if ($result['success']) {
            // Log successful activation in security audit logs
            AuditLogger::logWarning("[SOVEREIGN] User ID {$userId} ({$user['username']}) successfully activated the platform with key: {$key}");
            
            return $this->redirectTarget('dashboard')->with('flash_success', $result['message']);
        }

        // Log failed activation attempt
        AuditLogger::logError("[SECURITY] Failed activation attempt by User ID {$userId} ({$user['username']}) with key: {$key}");

        return $this->redirectTarget('activate')->with('error', $result['message']);
    }
}
