<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * CheckSession – Session-Based Authentication Middleware
 *
 * Supports two session sources:
 *  1. Laravel session store (session('user'))
 *  2. Native PHP $_SESSION['user'] (legacy bridge fallback)
 *
 * If no authenticated user is found, redirects to /login.
 */
class CheckSession
{
    public function handle(Request $request, Closure $next)
    {
        // Try Laravel session first
        $user = session('user');

        // Fallback: PHP native session
        if (!$user) {
            if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
                @session_start();
            }
            $user = $_SESSION['user'] ?? null;
        } else {
            // Laravel session is active, ensure PHP session is in sync
            if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
                @session_start();
            }
            if (session_status() === PHP_SESSION_ACTIVE && !isset($_SESSION['user'])) {
                $_SESSION['user'] = $user;
                $_SESSION['last_activity'] = time();
            }
        }

        if (!$user) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Unauthenticated', 'message' => 'يجب تسجيل الدخول أولاً'], 401);
            }
            return redirect()->route('login')->with('error', 'يجب تسجيل الدخول للوصول إلى هذه الصفحة.');
        }

        // Enforce Single Active Session (Disallow concurrent sessions)
        if (!app()->environment('testing')) {
            try {
                $userKey = strtolower($user['role_code'] ?? 'user') . '_' . ($user['id'] ?? '0') . '_' . strtolower($user['username'] ?? '');
                $currentSessionId = session()->getId();

                // Fetch active session from DB
                $activeSession = \Illuminate\Support\Facades\DB::table('active_sessions')
                    ->where('user_key', $userKey)
                    ->first();

                if ($activeSession && $activeSession->session_id !== $currentSessionId) {
                    // Check if the stored session file actually exists on disk (not stale/expired)
                    $sessionPath = storage_path('framework/sessions/' . $activeSession->session_id);
                    $storedSessionExists = file_exists($sessionPath);

                    if ($storedSessionExists) {
                        // A real concurrent session — log out current request
                        session()->flush();
                        if (session_status() === PHP_SESSION_ACTIVE) {
                            $_SESSION = [];
                        }

                        try {
                            \App\Core\AuditLogger::logWarning("[SECURITY] Concurrent session blocked for: {$userKey}");
                        } catch (\Exception $e) {}

                        if ($request->expectsJson()) {
                            return response()->json([
                                'error' => 'Session terminated',
                                'message' => 'تم تسجيل خروجك بسبب تسجيل دخول للحساب من متصفح أو جهاز آخر.'
                            ], 401);
                        }

                        return redirect()->route('login')->with('error', 'تم تسجيل خروجك تلقائياً لأن حسابك فُتح في متصفح أو جهاز آخر.');
                    } else {
                        // Stale session in registry — update with current valid session
                        \Illuminate\Support\Facades\DB::table('active_sessions')
                            ->where('user_key', $userKey)
                            ->update([
                                'session_id' => $currentSessionId,
                                'updated_at' => now()
                            ]);
                    }
                } elseif (!$activeSession) {
                    // Register current session
                    \Illuminate\Support\Facades\DB::table('active_sessions')->insert([
                        'user_key' => $userKey,
                        'session_id' => $currentSessionId,
                        'updated_at' => now()
                    ]);
                } else {
                    // Update heartbeat
                    \Illuminate\Support\Facades\DB::table('active_sessions')
                        ->where('user_key', $userKey)
                        ->update(['updated_at' => now()]);
                }
            } catch (\Exception $ex) {
                try {
                    \App\Core\AuditLogger::logError("[SECURITY] Failed to validate concurrent sessions: " . $ex->getMessage());
                } catch (\Exception $e) {}
            }
        }

        // Enforce session timeout (30 minutes)
        $lastActivity = session('last_activity') ?? ($_SESSION['last_activity'] ?? time());
        if ((time() - $lastActivity) > 1800) {
            session()->flush();
            if (isset($_SESSION)) {
                $_SESSION = [];
            }
            return redirect()->route('login')->with('error', 'انتهت مدة جلستك. يرجى تسجيل الدخول مجدداً.');
        }

        // Refresh activity timestamp
        session(['last_activity' => time()]);
        if (isset($_SESSION)) {
            $_SESSION['last_activity'] = time();
        }

        // Share user data and preferences with all views
        view()->share('sessionUser', $user);
        view()->share('userRole', strtolower($user['role_code'] ?? 'user'));
        try {
            $prefs = \App\Models\UserPreferences::forUser($user);
            view()->share('userPrefs', $prefs);
        } catch (\Throwable $e) {
            view()->share('userPrefs', (object)\App\Models\UserPreferences::defaults());
        }

        return $next($request);
    }
}
