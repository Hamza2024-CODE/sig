<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class MonitorServices extends Command
{
    // The signature and description of the command
    protected $signature = 'services:monitor {--test : Send a test notification to verify the setup}';
    protected $description = 'Test the status of critical services (MySQL, HFSQL) and alert on failure';

    public function handle()
    {
        // 0. If test option is passed, send a welcome test alert
        if ($this->option('test')) {
            $this->info("Sending a test welcome message via Telegram and Email...");
            $this->sendTestAlert();
            return;
        }

        $failures = [];

        // 1. Test MySQL connection
        try {
            DB::connection()->getPdo();
        } catch (\Exception $e) {
            $failures['MySQL Database'] = $e->getMessage();
        }

        // 2. Test HFSQL DSN connection (Temporarily disabled due to unixODBC/iODBC driver conflict on Linux. Will be re-enabled on Windows Server)
        /*
        if (function_exists('odbc_connect')) {
            try {
                $dsn = env('HFSQL_DSN');
                $user = env('HFSQL_USERNAME');
                $pass = env('HFSQL_PASSWORD');
                if ($dsn) {
                    $conn = @odbc_connect($dsn, $user, $pass);
                    if (!$conn) {
                        throw new \Exception("ODBC connection failed");
                    }
                    @odbc_close($conn);
                }
            } catch (\Exception $e) {
                $failures['HFSQL Database'] = $e->getMessage();
            }
        }
        */

        // 3. Test HTTP Login endpoint using native curl
        try {
            $loginUrl = rtrim(config('app.url'), '/') . '/login';
            $ch = curl_init($loginUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Ignore SSL verification errors in monitor
            curl_setopt($ch, CURLOPT_USERAGENT, 'SGFEP-Monitor/1.0');
            curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode < 200 || $httpCode >= 400) {
                throw new \Exception("Web portal returned status code: " . $httpCode);
            }
        } catch (\Exception $e) {
            $failures['Web Portal Endpoint (HTTP)'] = $e->getMessage();
        }

        // Send alert if there are failures
        if (!empty($failures)) {
            $this->sendAlerts($failures);
        }
    }

    protected function sendTestAlert()
    {
        $message = "🎉 <b>تهانينا! نظام التنبيهات يعمل بنجاح!</b>\n\n";
        $message .= "تم ربط البوت <b>saskiibot</b> بنجاح مع حسابك الشخصي.\n";
        $message .= "ستصلك التنبيهات الأمنية والتشغيلية للمنصة هنا فور حدوث أي طارئ.\n\n";
        $message .= "⏰ الوقت الحالي: " . now()->timezone('Africa/Algiers')->format('Y-m-d H:i:s') . "\n";
        $message .= "💻 السيرفر: " . gethostname();

        // Send to Telegram Bot using native curl
        $botToken = env('TELEGRAM_BOT_TOKEN');
        $chatId   = env('TELEGRAM_CHAT_ID');
        if ($botToken && $chatId) {
            try {
                $url = "https://api.telegram.org/bot{$botToken}/sendMessage";
                $data = [
                    'chat_id' => $chatId,
                    'text' => $message,
                    'parse_mode' => 'HTML'
                ];

                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
                curl_setopt($ch, CURLOPT_TIMEOUT, 5);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_exec($ch);
                curl_close($ch);
                $this->info("Telegram test message sent!");
            } catch (\Exception $ex) {
                $this->error("Failed to send Telegram notification: " . $ex->getMessage());
            }
        } else {
            $this->error("Telegram token or Chat ID is missing in .env!");
        }

        // Send to Email
        $adminEmail = env('ADMIN_ALERT_EMAIL');
        if ($adminEmail && app()->bound('mail.manager')) {
            try {
                Mail::raw(strip_tags($message), function ($mail) use ($adminEmail) {
                    $mail->to($adminEmail)
                         ->subject('🎉 [SGFEP Monitor] Test Welcome Notification');
                });
                $this->info("Email test message sent!");
            } catch (\Exception $ex) {
                $this->error("Failed to send Email notification: " . $ex->getMessage());
            }
        }
    }

    protected function sendAlerts(array $failures)
    {
        // Alert message template
        $message = "🚨 <b>[SGFEP Alert] Service Failure Detected</b>\n";
        $message .= "The following critical services are down:\n\n";
        foreach ($failures as $service => $err) {
            $message .= "❌ <b>{$service}:</b>\n<code>{$err}</code>\n\n";
        }
        $message .= "⏰ Time: " . now()->timezone('Africa/Algiers')->format('Y-m-d H:i:s') . "\n";
        $message .= "⚠️ Please inspect the server immediately.";

        // Send to Telegram Bot using native curl (avoiding Guzzle dependency)
        $botToken = env('TELEGRAM_BOT_TOKEN');
        $chatId   = env('TELEGRAM_CHAT_ID');
        if ($botToken && $chatId) {
            try {
                $url = "https://api.telegram.org/bot{$botToken}/sendMessage";
                $data = [
                    'chat_id' => $chatId,
                    'text' => $message,
                    'parse_mode' => 'HTML'
                ];

                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
                curl_setopt($ch, CURLOPT_TIMEOUT, 5);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_exec($ch);
                curl_close($ch);
            } catch (\Exception $ex) {
                Log::error("Failed to send Telegram notification: " . $ex->getMessage());
            }
        }

        // Send to Email
        $adminEmail = env('ADMIN_ALERT_EMAIL');
        if ($adminEmail && app()->bound('mail.manager')) {
            try {
                Mail::raw(strip_tags($message), function ($mail) use ($adminEmail) {
                    $mail->to($adminEmail)
                         ->subject('🚨 [SGFEP Alert] Service Interruption Detected');
                });
            } catch (\Exception $ex) {
                Log::error("Failed to send Email notification: " . $ex->getMessage());
            }
        }
    }
}
