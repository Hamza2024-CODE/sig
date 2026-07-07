<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\StatsService;
use App\Services\KpiCache;
use Illuminate\Support\Facades\Cache;

/**
 * ═══════════════════════════════════════════════════════════════════════
 * RefreshStatsCommand — php artisan stats:refresh
 * ═══════════════════════════════════════════════════════════════════════
 *
 * يُعيد حساب جميع الإحصائيات ويُخزّنها في dashboard_stats.
 * مُجدوَل تلقائياً كل ساعة من Kernel.php.
 *
 * الاستخدام اليدوي:
 *   php artisan stats:refresh            ← تحديث كامل
 *   php artisan stats:refresh --force    ← تحديث + إبطال كاش KPI
 *   php artisan stats:refresh --dry-run  ← عرض فقط دون حفظ
 * ═══════════════════════════════════════════════════════════════════════
 */
class RefreshStatsCommand extends Command
{
    protected $signature = 'stats:refresh
                            {--force    : إبطال كاش KpiCache أيضاً}
                            {--dry-run  : احسب فقط ولا تحفظ}';

    protected $description = 'يُعيد حساب الإحصائيات ويُخزّنها في dashboard_stats';

    public function handle(): int
    {
        $dryRun  = $this->option('dry-run');
        $force   = $this->option('force');
        $isDry   = (bool)$dryRun;

        $this->info('');
        $this->info('══════════════════════════════════════════════');
        $this->info('  📊 تحديث الإحصائيات — stats:refresh         ');
        $this->info('══════════════════════════════════════════════');

        if ($isDry) {
            $this->warn('⚠  وضع المعاينة (dry-run) — لن يتم الحفظ');
        }

        $start = microtime(true);

        // ── تحديث dashboard_stats ─────────────────────────────────────
        $this->info('');
        $this->info('🔄 جاري الحساب من قاعدة البيانات...');

        if (!$isDry) {
            $stats = StatsService::refreshAll();
        } else {
            // Dry-run: compute without saving
            $reflection = new \ReflectionClass(StatsService::class);
            $method = $reflection->getMethod('computeAll');
            $method->setAccessible(true);
            $stats = $method->invoke(null);
        }

        // ── طباعة النتائج ─────────────────────────────────────────────
        $this->info('');
        $this->table(
            ['المفتاح', 'القيمة', 'الوصف'],
            [
                [StatsService::KEY_APPRENANTS,     number_format($stats[StatsService::KEY_APPRENANTS]    ?? 0), 'إجمالي المتربصين'],
                [StatsService::KEY_FILLES,         number_format($stats[StatsService::KEY_FILLES]         ?? 0), 'الإناث'],
                [StatsService::KEY_GARCONS,        number_format($stats[StatsService::KEY_GARCONS]        ?? 0), 'الذكور'],
                [StatsService::KEY_OFFRES,         number_format($stats[StatsService::KEY_OFFRES]         ?? 0), 'عدد العروض'],
                [StatsService::KEY_ETABLISSEMENTS, number_format($stats[StatsService::KEY_ETABLISSEMENTS] ?? 0), 'عدد المؤسسات'],
                [StatsService::KEY_ENCADREMENTS,   number_format($stats[StatsService::KEY_ENCADREMENTS]   ?? 0), 'عدد الإطارات'],
                [StatsService::KEY_SPECIALITES,    number_format($stats[StatsService::KEY_SPECIALITES]    ?? 0), 'التخصصات'],
                [StatsService::KEY_CANDIDATS,      number_format($stats[StatsService::KEY_CANDIDATS]      ?? 0), 'المترشحون'],
                [StatsService::KEY_SECTIONS,       number_format($stats[StatsService::KEY_SECTIONS]       ?? 0), 'الأقسام'],
                [StatsService::KEY_DIPLOMES,       number_format($stats[StatsService::KEY_DIPLOMES]       ?? 0), 'الشهادات المسلمة'],
            ]
        );

        // ── إبطال KpiCache إذا طُلب ──────────────────────────────────
        if ($force && !$isDry) {
            $this->info('');
            $this->info('🗑  إبطال كاش KpiCache الوطني...');
            KpiCache::invalidateAdminAll();
            Cache::flush(); // كامل إبطال Cache (حذر: يؤثر على جميع المفاتيح)
            $this->info('   ✅ تم');
        }

        $elapsed = round(microtime(true) - $start, 2);

        $this->info('');
        $this->info("✅ اكتمل التحديث في {$elapsed} ثانية" . ($isDry ? ' (dry-run — لم يُحفظ شيء)' : ''));
        $this->info('');

        return Command::SUCCESS;
    }
}
