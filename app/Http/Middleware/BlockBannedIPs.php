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

        if (!$ipBanningEnabled && !env('BLOCK_FOREIGN_IPS', false)) {
            return $next($request);
        }

        // 1. Apply Geo-IP Block if BLOCK_FOREIGN_IPS is enabled
        if (env('BLOCK_FOREIGN_IPS', false)) {
            $isAlgerian = \Illuminate\Support\Facades\Cache::remember('ip_country_dz_' . $ip, 86400, function () use ($ip) {
                if ($ip === '127.0.0.1' || $ip === '::1' || preg_match('/^(10\.|172\.(1[6-9]|2[0-9]|3[0-1])\.|192\.168\.)/', $ip)) {
                    return true;
                }
                try {
                    $ctx = stream_context_create(['http' => ['timeout' => 1.2]]);
                    $res = @file_get_contents("http://ip-api.com/json/{$ip}?fields=status,countryCode", false, $ctx);
                    if ($res) {
                        $geo = json_decode($res, true);
                        if (($geo['status'] ?? '') === 'success' && ($geo['countryCode'] ?? '') !== 'DZ') {
                            return false; // Not Algeria
                        }
                    }
                } catch (\Throwable $e) {}
                return true; // Default to true on failure to prevent false positives
            });

            if (!$isAlgerian) {
                if ($request->expectsJson() || $request->is('api/*')) {
                    return response()->json([
                        'success' => false,
                        'message' => 'الوصول للمنصة مقتصر حالياً على داخل دولة الجزائر لدواعي أمنية.',
                        'banned'  => true
                    ], 403);
                }

                return response()->view('errors.banned', [
                    'ip'         => $ip,
                    'reason'     => 'الوصول للمنصة مقتصر على عناوين IP داخل الجزائر فقط حالياً لدواعي أمنية وتفادي محاولات الاختراق الخارجية.',
                    'banned_until' => null,
                ], 403);
            }
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
