<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // Cache Warming - runs every 10 minutes to ensure caches are populated and renewed before they expire (900s)
        $schedule->command('sgfep:cache:warm')
                 ->everyTenMinutes()
                 ->withoutOverlapping()
                 ->runInBackground()
                 ->appendOutputTo(storage_path('logs/cache_warm_every_10m.log'));

        // Flush and clean warm - daily at 03:00 to clear stale data
        $schedule->command('sgfep:cache:warm --flush')
                 ->dailyAt('03:00')
                 ->withoutOverlapping()
                 ->runInBackground()
                 ->appendOutputTo(storage_path('logs/cache_warm_flush.log'));

        // ── مراقب المزامنة الذاتي ومصلح الأخطاء (Sync Watchdog & Self-Healing) ──
        // يعمل كل دقيقة للتحقق من المهام المتوقفة وإعادة محاولة السجلات الفاشلة.
        $schedule->command('sync:watchdog')
                 ->everyMinute()
                 ->withoutOverlapping()
                 ->runInBackground()
                 ->appendOutputTo(storage_path('logs/sync_watchdog.log'));

        // ── التدقيق الدوري لإحصائيات المزامنة (MySQL ↔ HFSQL) ──────────────
        // يعمل يومياً الساعة 02:00 صباحاً لتصحيح أي تضارب في إحصائيات المقارنة.
        $schedule->command('sync:verify-stats')
                 ->dailyAt('02:00')
                 ->withoutOverlapping()
                 ->runInBackground()
                 ->appendOutputTo(storage_path('logs/sync_verify_stats_daily.log'));

        // ── التجميع اليومي لمؤشرات الأداء (Daily KPI Aggregator) ────────────
        // يعمل يومياً الساعة 02:00 صباحاً لحفظ لقطات البيانات الاستراتيجية.
        $schedule->command('generate:kpi-snapshots')
                 ->dailyAt('02:00')
                 ->withoutOverlapping()
                 ->runInBackground()
                 ->appendOutputTo(storage_path('logs/kpi_snapshots_daily.log'));


        // ── تحديث جدول dashboard_stats (KPI Snapshot) ────────────────────
        // يُعيد حساب الإحصائيات كل ساعة ويُخزّنها في الجدول المادي.
        // الإزاحة 5 دقائق لتجنب التداخل مع sgfep:cache:warm.
        $schedule->command('stats:refresh')
                 ->hourlyAt(5)
                 ->withoutOverlapping()
                 ->runInBackground()
                 ->appendOutputTo(storage_path('logs/stats_refresh.log'));

        // تحديث فوري مرة واحدة يومياً بعد منتصف الليل (تحديث شامل)
        $schedule->command('stats:refresh --force')
                 ->dailyAt('02:30')
                 ->withoutOverlapping()
                 ->runInBackground()
                 ->appendOutputTo(storage_path('logs/stats_refresh_daily.log'));

        // ── تنظيف ملفات التصدير القديمة (أكثر من 7 أيام) ──────────────
        $schedule->call(function () {
            \Illuminate\Support\Facades\DB::table('export_requests')
                ->where('status', 'ready')
                ->where('created_at', '<', now()->subDays(7))
                ->each(function ($req) {
                    if ($req->file_path && \Illuminate\Support\Facades\Storage::exists($req->file_path)) {
                        \Illuminate\Support\Facades\Storage::delete($req->file_path);
                    }
                });
            \Illuminate\Support\Facades\DB::table('export_requests')
                ->where('created_at', '<', now()->subDays(7))
                ->delete();
        })->weekly()->sundays()->at('04:00')->name('cleanup-exports');

        // ── تطهير سجلات الوصول القديمة (أكثر من 180 يوماً) ──────────────
        $schedule->command('logs:prune-access')
                 ->dailyAt('04:30')
                 ->withoutOverlapping()
                 ->runInBackground()
                 ->appendOutputTo(storage_path('logs/logs_prune_access.log'));

        // ── تنظيف البيانات المنتهية الصلاحية — RGPD Art.5 ──────────────
        // يحذف: محاولات الدخول (+90يوم)، أجهزة موثوقة منتهية، رموز استرداد مستخدمة (+30يوم)
        $schedule->call(function () {
            // محاولات الدخول الفاشلة > 90 يوم
            \Illuminate\Support\Facades\DB::table('login_attempts')
                ->where('attempted_at', '<', time() - (90 * 86400))
                ->delete();

            // أجهزة موثوقة منتهية الصلاحية
            \Illuminate\Support\Facades\DB::table('trusted_devices')
                ->where('expires_at', '<', now())
                ->delete();

            // رموز الاسترداد المستخدمة منذ أكثر من 30 يوم
            \Illuminate\Support\Facades\DB::table('user_recovery_codes')
                ->whereNotNull('used_at')
                ->where('used_at', '<', now()->subDays(30))
                ->delete();

            // سجلات الأمن > 365 يوم (احتفظ بـ 1 سنة)
            \Illuminate\Support\Facades\DB::table('security_logs')
                ->where('created_at', '<', now()->subDays(365))
                ->delete();
        })->weekly()->mondays()->at('03:30')->name('rgpd-purge-expired');
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
