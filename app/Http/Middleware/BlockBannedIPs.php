<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * BlockBannedIPs Middleware — Defense Shield
 *
 * Checks if the incoming client IP address is banned.
 * If banned, rejects the request with HTTP 403 Forbidden.
 */
class BlockBannedIPs
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $ip = $request->ip();

        // Check if IP banning feature is enabled (defaults to false)
        $settingsFile = base_path('storage/ip_ban_settings.json');
        $ipBanningEnabled = false;
        if (file_exists($settingsFile)) {
            $settings = json_decode(file_get_contents($settingsFile), true);
            $ipBanningEnabled = $settings['ip_banning_enabled'] ?? false;
        }

        if (!$ipBanningEnabled) {
            return $next($request);
        }

        // Check if the ip_bans table exists (prevents crashes in SQLite testing environments)
        if (!\Illuminate\Support\Facades\Schema::hasTable('ip_bans')) {
            return $next($request);
        }

        // Check if this IP is banned in the database
        $ban = DB::table('ip_bans')
            ->where('ip_address', $ip)
            ->where(function ($query) {
                $query->whereNull('banned_until')
                      ->orWhere('banned_until', '>', now());
            })
            ->first();

        if ($ban) {
            $timeLeft = $ban->banned_until ? now()->diffInMinutes($ban->banned_until) : null;
            $message = 'تم حظر عنوان الـ IP الخاص بك لدواعي أمنية.';
            if ($timeLeft !== null) {
                $message .= ' يرجى الانتظار ' . ceil($timeLeft) . ' دقيقة قبل المحاولة مجدداً.';
            }

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                    'banned'  => true
                ], 403);
            }

            // Return a premium, dark-mode warning screen
            return response()->view('errors.banned', [
                'ip'         => $ip,
                'reason'     => $ban->reason ?? 'سلوك مشبوه متكرر أو محاولات دخول فاشلة.',
                'banned_until' => $ban->banned_until,
            ], 403);
        }

        return $next($request);
    }
}
