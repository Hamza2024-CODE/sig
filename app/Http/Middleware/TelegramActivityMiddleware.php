<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TelegramActivityMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // 1. Check if Telegram Bot is configured
        $botToken = env('TELEGRAM_BOT_TOKEN');
        $chatId   = env('TELEGRAM_CHAT_ID');

        if (!$botToken || !$chatId) {
            return $next($request);
        }

        // 2. Check if user is authenticated via session
        if (!session()->has('user')) {
            return $next($request);
        }

        $user = session('user');
        $path = $request->path();

        // 3. Skip static assets, debug bar, api, login/logout, and internal system paths
        $ignoredPrefixes = ['css', 'js', 'assets', 'images', 'fonts', 'vendor', '_debugbar', 'api', 'login', 'logout', 'livewire'];
        foreach ($ignoredPrefixes as $prefix) {
            if (str_starts_with($path, $prefix) || str_contains($path, '/' . $prefix)) {
                return $next($request);
            }
        }

        // Skip background Ajax calls for polling/statistics
        if ($request->ajax() && $request->isMethod('GET')) {
            return $next($request);
        }

        // Skip background notification fetch / count queries
        if ($request->is('*notifications/fetch*') || $request->is('*notifications/count*') || $request->is('*notifications/read*')) {
            return $next($request);
        }

        $ip = $request->ip();
        $timeStr = now()->timezone('Africa/Algiers')->format('Y-m-d H:i:s');
        $userName = $user['nom_complet'] ?? $user['username'] ?? 'مستخدم';
        $userLogin = $user['username'] ?? '';
        $userRole = $user['role_ar'] ?? 'غير محدد';
        $wilaya = $user['wilaya_name'] ?? '';

        // Retrieve Etablissement (school name) if logged in user is associated with an etablissement
        $etabName = '';
        $etabId = (int)($user['etablissement_id'] ?? $user['IDEts_Form'] ?? 0);
        if ($etabId > 0) {
            if (session()->has('user_etab_name')) {
                $etabName = session('user_etab_name');
            } else {
                try {
                    $etabName = \Illuminate\Support\Facades\DB::table('etablissement')
                        ->where('IDetablissement', $etabId)
                        ->value('Nom');
                    if ($etabName) {
                        session(['user_etab_name' => $etabName]);
                    }
                } catch (\Throwable $e) {}
            }
        }

        // 4. Handle Page Visits (GET)
        if ($request->isMethod('GET')) {
            $currentUrl = $request->fullUrl();

            // Prevent duplicate notifications on simple page refresh
            if (session('last_telegram_visited_page') === $currentUrl) {
                return $next($request);
            }
            session(['last_telegram_visited_page' => $currentUrl]);

            $message = "👁️ <b>زيارة صفحة جديدة</b>\n\n";
            $message .= "• <b>المستخدم:</b> {$userName}\n";
            $message .= "• <b>اسم الحساب:</b> <code>{$userLogin}</code>\n";
            $message .= "• <b>الدور:</b> {$userRole}\n";
            if (!empty($etabName)) {
                $message .= "• <b>المؤسسة:</b> {$etabName}\n";
            }
            if (!empty($wilaya)) {
                $message .= "• <b>الولاية:</b> {$wilaya}\n";
            }
            $message .= "• <b>الصفحة الحالية:</b> <a href=\"{$currentUrl}\">" . urldecode($request->getRequestUri()) . "</a>\n";
            $message .= "• <b>عنوان IP:</b> <code>{$ip}</code>\n";
            $message .= "• <b>الوقت:</b> {$timeStr}";

            $this->sendToTelegram($botToken, $chatId, $message);
        }
        // 5. Handle Actions (POST / PUT / DELETE)
        elseif ($request->isMethod('POST') || $request->isMethod('PUT') || $request->isMethod('DELETE') || $request->isMethod('PATCH')) {
            // Ignore auth requests to prevent double login notifications or logging raw login attempts
            if ($request->is('login') || $request->is('logout') || str_contains($path, 'login') || str_contains($path, 'logout')) {
                return $next($request);
            }

            $currentUrl = $request->fullUrl();
            $method = $request->method();

            $message = "⚡ <b>إجراء عمل على المنصة</b>\n\n";
            $message .= "• <b>المستخدم:</b> {$userName}\n";
            $message .= "• <b>اسم الحساب:</b> <code>{$userLogin}</code>\n";
            $message .= "• <b>الدور:</b> {$userRole}\n";
            if (!empty($etabName)) {
                $message .= "• <b>المؤسسة:</b> {$etabName}\n";
            }
            if (!empty($wilaya)) {
                $message .= "• <b>الولاية:</b> {$wilaya}\n";
            }
            $message .= "• <b>الطلب:</b> <code>{$method}</code> <a href=\"{$currentUrl}\">" . urldecode($request->getRequestUri()) . "</a>\n";

            // Extract inputs excluding passwords and tokens
            $inputs = $request->except(['password', '_token', '_method', 'password_confirmation', 'new_password', 'current_password']);
            if (!empty($inputs)) {
                $message .= "• <b>البيانات المرسلة:</b>\n";
                $i = 0;
                foreach ($inputs as $key => $value) {
                    if ($i++ > 15) { // limit fields count to prevent telegram message length limit
                        $message .= "  ... <i>(تم إيقاف عرض باقي الحقول لكثرتها)</i>\n";
                        break;
                    }
                    if (is_array($value)) {
                        $value = json_encode($value, JSON_UNESCAPED_UNICODE);
                    }
                    if (strlen($value) > 120) {
                        $value = mb_substr($value, 0, 117) . '...';
                    }
                    $message .= "  - <code>{$key}</code>: " . htmlspecialchars($value) . "\n";
                }
            }

            $message .= "• <b>عنوان IP:</b> <code>{$ip}</code>\n";
            $message .= "• <b>الوقت:</b> {$timeStr}";

            $this->sendToTelegram($botToken, $chatId, $message);
        }

        return $next($request);
    }

    /**
     * Send message using curl multipart/form-data to Telegram Bot API.
     */
    protected function sendToTelegram(string $botToken, string $chatId, string $message): void
    {
        try {
            $url = "https://api.telegram.org/bot{$botToken}/sendMessage";
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => 'HTML',
                'disable_web_page_preview' => true
            ]));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_TIMEOUT, 3);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_exec($ch);
            curl_close($ch);
        } catch (\Exception $e) {
            Log::error('Telegram Tracker failed: ' . $e->getMessage());
        }
    }
}
