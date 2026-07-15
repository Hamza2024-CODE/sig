<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ═══════════════════════════════════════════════════════════════════════
 * StatsService — آلية التحديث الهجينة للإحصائيات (Hybrid Refresh)
 * ═══════════════════════════════════════════════════════════════════════
 *
 * طبقات القراءة (بالترتيب من الأسرع إلى الأبطأ):
 *
 *   Layer 1 → Laravel Cache (RAM / File)  → TTL: 15 دقيقة
 *   Layer 2 → جدول dashboard_stats       → قراءة فورية (INDEX على stat_key)
 *   Layer 3 → حسابات حية (COUNT(*))      → بطيئة — تُستدعى عند الضرورة فقط
 *
 * استراتيجية التحديث:
 *   • Cron يومي / ساعوي → refreshAll() يُعيد حساب كل شيء.
 *   • عند إضافة سجل    → increment($key) : UPDATE واحد بسيط.
 *   • صفحة الداشبورد   → get($key) : قراءة من Cache أو dashboard_stats.
 * ═══════════════════════════════════════════════════════════════════════
 */
final class StatsService
{
    // ── Cache TTL ────────────────────────────────────────────────────────
    private const TTL_CACHE = 900;       // 15 دقيقة في Laravel Cache
    private const CACHE_PREFIX = 'sgfep:dstats:';

    // ── مفاتيح الإحصائيات الوطنية (global) ──────────────────────────────
    public const KEY_APPRENANTS     = 'global.total_apprenants';
    public const KEY_FILLES         = 'global.total_filles';
    public const KEY_GARCONS        = 'global.total_garcons';
    public const KEY_OFFRES         = 'global.total_offres';
    public const KEY_ETABLISSEMENTS = 'global.total_etablissements';
    public const KEY_ENCADREMENTS   = 'global.total_encadrements';
    public const KEY_SPECIALITES    = 'global.total_specialites';
    public const KEY_CANDIDATS      = 'global.total_candidats';
    public const KEY_SECTIONS       = 'global.total_sections';
    public const KEY_DIPLOMES       = 'global.total_diplomes';
    public const KEY_LAST_SYNC_TS   = 'global.last_sync_ts';
    public const KEY_RECONDUITS     = 'global.total_reconduits';
    public const KEY_SECTIONS_S1    = 'global.total_sections_s1';

    // ════════════════════════════════════════════════════════════════════
    // §1 — القراءة (Read)
    // ════════════════════════════════════════════════════════════════════

    /**
     * يقرأ قيمة إحصائية واحدة بأسرع طريقة ممكنة.
     * Layer 1: Cache → Layer 2: dashboard_stats → Layer 3: 0 (fallback)
     */
    public static function get(string $key, int $default = 0): int
    {
        $cacheKey = self::CACHE_PREFIX . $key;

        return (int) Cache::remember($cacheKey, self::TTL_CACHE, function () use ($key, $default) {
            try {
                $row = DB::table('dashboard_stats')
                    ->where('stat_key', $key)
                    ->value('stat_value');
                return $row !== null ? (int)$row : $default;
            } catch (\Throwable $e) {
                return $default;
            }
        });
    }

    /**
     * يقرأ مجموعة إحصائيات دفعةً واحدة (استعلام واحد فقط).
     * @param  string[] $keys
     * @return array<string, int>
     */
    public static function getMany(array $keys): array
    {
        // حاول القراءة من Cache أولاً
        $result = [];
        $missing = [];
        foreach ($keys as $key) {
            $cached = Cache::get(self::CACHE_PREFIX . $key);
            if ($cached !== null) {
                $result[$key] = (int)$cached;
            } else {
                $missing[] = $key;
            }
        }

        if (!empty($missing)) {
            try {
                $rows = DB::table('dashboard_stats')
                    ->whereIn('stat_key', $missing)
                    ->pluck('stat_value', 'stat_key');

                foreach ($missing as $key) {
                    $val = isset($rows[$key]) ? (int)$rows[$key] : 0;
                    $result[$key] = $val;
                    Cache::put(self::CACHE_PREFIX . $key, $val, self::TTL_CACHE);
                }
            } catch (\Throwable $e) {
                foreach ($missing as $key) {
                    $result[$key] = 0;
                }
            }
        }

        return $result;
    }

    /**
     * يُعيد كل الإحصائيات الوطنية في استعلام واحد.
     */
    public static function getAllGlobal(): array
    {
        $allKeys = [
            self::KEY_APPRENANTS,
            self::KEY_FILLES,
            self::KEY_GARCONS,
            self::KEY_OFFRES,
            self::KEY_ETABLISSEMENTS,
            self::KEY_ENCADREMENTS,
            self::KEY_SPECIALITES,
            self::KEY_CANDIDATS,
            self::KEY_SECTIONS,
            self::KEY_SECTIONS_S1,
            self::KEY_DIPLOMES,
            self::KEY_LAST_SYNC_TS,
            self::KEY_RECONDUITS,
        ];

        return self::getMany($allKeys);
    }

    // ════════════════════════════════════════════════════════════════════
    // §2 — التحديث اللحظي (On-Demand Increment/Decrement)
    // ════════════════════════════════════════════════════════════════════

    /**
     * يُزيد عداداً بمقدار $amount.
     * استعلام بسيط جداً — استخدمه عند إضافة سجل جديد.
     *
     * @example StatsService::increment(StatsService::KEY_APPRENANTS);
     */
    public static function increment(string $key, int $amount = 1): void
    {
        try {
            DB::table('dashboard_stats')
                ->where('stat_key', $key)
                ->increment('stat_value', $amount);

            // إبطال الكاش حتى تُقرأ القيمة الجديدة في الطلب القادم
            Cache::forget(self::CACHE_PREFIX . $key);
        } catch (\Throwable $e) {
            Log::error("[StatsService] increment failed for key={$key}: " . $e->getMessage());
        }
    }

    /**
     * يُنقص عداداً بمقدار $amount.
     */
    public static function decrement(string $key, int $amount = 1): void
    {
        try {
            DB::table('dashboard_stats')
                ->where('stat_key', $key)
                ->decrement('stat_value', $amount);

            Cache::forget(self::CACHE_PREFIX . $key);
        } catch (\Throwable $e) {
            Log::error("[StatsService] decrement failed for key={$key}: " . $e->getMessage());
        }
    }

    /**
     * يضبط قيمة عداد مباشرةً (بدلاً من الزيادة/النقص).
     */
    public static function set(string $key, int $value, string $group = 'global', ?string $label = null): void
    {
        try {
            DB::table('dashboard_stats')->upsert(
                [
                    'stat_key'    => $key,
                    'stat_value'  => $value,
                    'stat_group'  => $group,
                    'stat_label'  => $label,
                    'last_updated'=> now(),
                ],
                ['stat_key'],
                ['stat_value', 'last_updated']
            );

            Cache::forget(self::CACHE_PREFIX . $key);
        } catch (\Throwable $e) {
            Log::error("[StatsService] set failed for key={$key}: " . $e->getMessage());
        }
    }

    // ════════════════════════════════════════════════════════════════════
    // §3 — التحديث الكامل (Full Refresh via Cron)
    // ════════════════════════════════════════════════════════════════════

    /**
     * يُعيد حساب جميع الإحصائيات من المصدر ويُخزّنها.
     * يُستدعى من RefreshStatsCommand (cron).
     *
     * @return array<string, int> النتائج المحسوبة
     */
    public static function refreshAll(): array
    {
        $stats = self::computeAll();

        // حفظ دفعي في dashboard_stats (upsert واحد)
        $rows = [];
        foreach ($stats as $key => $value) {
            $rows[] = [
                'stat_key'    => $key,
                'stat_value'  => $value,
                'stat_group'  => 'global',
                'last_updated'=> now(),
            ];
        }

        try {
            DB::table('dashboard_stats')->upsert(
                $rows,
                ['stat_key'],
                ['stat_value', 'last_updated']
            );
        } catch (\Throwable $e) {
            Log::error('[StatsService] upsert failed: ' . $e->getMessage());
        }

        // إبطال كاش Laravel لكل المفاتيح
        foreach (array_keys($stats) as $key) {
            Cache::forget(self::CACHE_PREFIX . $key);
        }

        return $stats;
    }

    // ════════════════════════════════════════════════════════════════════
    // §4 — الحسابات الثقيلة (Private — تُستدعى مرة واحدة فقط عبر Cron)
    // ════════════════════════════════════════════════════════════════════

    private static function computeAll(): array
    {
        $results = [];

        // §4.1 — المتربصون النشطون (DEOH logic)
        $apprenants = self::safeCount("
            SELECT COUNT(a.IDapprenant)
            FROM apprenant a
            JOIN section s ON a.IDSection = s.IDSection
            LEFT JOIN apprenant_fin af ON a.IDapprenant = af.IDapprenant
            WHERE a.statut = 'actif'
              AND af.IDapprenant IS NULL
              AND s.DateDF <= CURRENT_DATE()
              AND s.DateFF >= CURRENT_DATE()
        ");
        $results[self::KEY_APPRENANTS] = $apprenants;

        // §4.2 — الإناث والذكور (DEOH logic)
        $filles = self::safeCount("
            SELECT COUNT(a.IDapprenant)
            FROM apprenant a
            JOIN section s ON a.IDSection = s.IDSection
            JOIN candidat c ON a.IDCandidat = c.IDCandidat
            LEFT JOIN apprenant_fin af ON a.IDapprenant = af.IDapprenant
            WHERE a.statut = 'actif'
              AND c.Civ = 2
              AND af.IDapprenant IS NULL
              AND s.DateDF <= CURRENT_DATE()
              AND s.DateFF >= CURRENT_DATE()
        ");
        $results[self::KEY_FILLES]  = $filles;
        $results[self::KEY_GARCONS] = max(0, $apprenants - $filles);

        // §4.3 — العروض والمؤسسات والتخصصات والأقسام
        $results[self::KEY_OFFRES]         = self::safeCount("SELECT COUNT(*) FROM offre");
        $results[self::KEY_ETABLISSEMENTS] = self::safeCount("SELECT COUNT(*) FROM etablissement");
        $results[self::KEY_SPECIALITES]    = self::safeCount("SELECT COUNT(DISTINCT IDSpecialite) FROM offre");
        $results[self::KEY_SECTIONS]       = self::safeCount("SELECT COUNT(*) FROM section");

        // §4.4 — الإطارات
        $results[self::KEY_ENCADREMENTS] = self::safeCount("SELECT COUNT(*) FROM Encadrement");

        // §4.5 — المترشحون
        $results[self::KEY_CANDIDATS] = self::safeCount("SELECT COUNT(*) FROM candidat");

        // §4.6 — الشهادات المسلّمة (من apprenant_fin)
        $results[self::KEY_DIPLOMES] = self::safeCount(
            "SELECT COUNT(*) FROM apprenant_fin WHERE Numdiplome IS NOT NULL AND Numdiplome != ''"
        );

        // §4.7 — المتربصون المستمرون (DEOH logic)
        $results[self::KEY_RECONDUITS] = self::safeCount("
            SELECT SUM(s.Nbrrecond)
            FROM section s
            WHERE s.DateDF <= CURRENT_DATE()
              AND s.DateFF >= CURRENT_DATE()
        ");

        $results[self::KEY_SECTIONS_S1] = self::safeCount("
            SELECT COUNT(*)
            FROM section_semestre
            WHERE NumSem = 1
              AND Dernier = 1
        ");

        // §4.8 — تسجيل وقت آخر تحديث
        $results[self::KEY_LAST_SYNC_TS] = time();

        return $results;
    }

    /**
     * تنفيذ COUNT آمن — يُعيد 0 عند الفشل دون رمي استثناء.
     */
    private static function safeCount(string $sql): int
    {
        try {
            $row = DB::selectOne($sql);
            if (!$row) return 0;
            $vals = array_values((array)$row);
            return (int)($vals[0] ?? 0);
        } catch (\Throwable $e) {
            Log::warning("[StatsService] safeCount failed — SQL: {$sql} | Error: " . $e->getMessage());
            return 0;
        }
    }

    // ════════════════════════════════════════════════════════════════════
    // §5 — واجهة KpiCache (تحويل التنسيق لاستخدام KpiCache الحالي)
    // ════════════════════════════════════════════════════════════════════

    /**
     * يُعيد بيانات KPI بتنسيق متوافق مع KpiCache::admin()
     * يقرأ من dashboard_stats أولاً (سريع)، ثم يُعيد البنية المعتادة.
     */
    public static function asKpiArray(): array
    {
        $s = self::getAllGlobal();

        return [
            'total_stagiaires'     => $s[self::KEY_APPRENANTS]     ?? 0,
            'total_filles'         => $s[self::KEY_FILLES]          ?? 0,
            'total_garcons'        => $s[self::KEY_GARCONS]         ?? 0,
            'total_offres'         => $s[self::KEY_OFFRES]          ?? 0,
            'total_etablissements' => $s[self::KEY_ETABLISSEMENTS]  ?? 0,
            'total_encadrements'   => $s[self::KEY_ENCADREMENTS]    ?? 0,
            'total_specialites'    => $s[self::KEY_SPECIALITES]     ?? 0,
            'total_users'          => self::safeCount("SELECT COUNT(*) FROM utilisateur") +
                                     self::safeCount("SELECT COUNT(*) FROM etablissement WHERE nomUser IS NOT NULL AND nomUser != ''") +
                                     self::safeCount("SELECT COUNT(*) FROM encadrement WHERE nin IS NOT NULL AND nin != '' AND MotDePass IS NOT NULL AND MotDePass != ''"),
            'total_wilayas'        => 48,
            'total_candidats'      => $s[self::KEY_CANDIDATS]       ?? 0,
            'total_reconduits'     => $s[self::KEY_RECONDUITS]      ?? 0,
        ];
    }

    /**
     * تحقق من وجود بيانات في جدول dashboard_stats.
     */
    public static function isPopulated(): bool
    {
        try {
            return DB::table('dashboard_stats')
                ->where('stat_key', self::KEY_LAST_SYNC_TS)
                ->where('stat_value', '>', 0)
                ->exists();
        } catch (\Throwable $e) {
            return false;
        }
    }
}
