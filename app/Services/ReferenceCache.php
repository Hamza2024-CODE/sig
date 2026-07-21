<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ═══════════════════════════════════════════════════════════════════════
 * ReferenceCache — المستوى الأول من الكاش (24 ساعة)
 * ═══════════════════════════════════════════════════════════════════════
 *
 * البيانات المرجعية الثابتة (لا تتغير إلا نادراً):
 *   - الولايات (48)
 *   - الشعب والتخصصات
 *   - أنماط التكوين
 *   - السنوات التكوينية
 *   - طبيعة المؤسسات
 *
 * القاعدة: تُجلب مرة واحدة في اليوم وتبقى في RAM.
 *          أي استدعاء لاحق يقرأ من الذاكرة في <1ms.
 *
 * NEVER cache: قوائم المتربصين، نقاط التقييم، ملفات Base64.
 * ═══════════════════════════════════════════════════════════════════════
 */
final class ReferenceCache
{
    /** TTL البيانات المرجعية = 24 ساعة */
    private const TTL_REFERENCE = 86400;   // 24h

    /** TTL البيانات المرجعية المتوسطة = 6 ساعات */
    private const TTL_SEMI_STATIC = 21600; // 6h

    /** بادئة مفاتيح الكاش لتفادي التعارض */
    private const PREFIX = 'sgfep:ref:';

    // ── 48 ولاية (لا تتغير أبداً) ─────────────────────────────────────────
    public static function wilayas(): array
    {
        return Cache::remember(self::PREFIX . 'wilayas', self::TTL_REFERENCE, function () {
            return self::select(
                "SELECT IDWilayaa as id, Code as code, Nom as nom_ar, NomFr as nom_fr
                 FROM wilaya ORDER BY Code ASC"
            );
        });
    }
    // ── البلديات (Communes) ──────────────────────────────────────────────
    public static function communes(): array
    {
        return Cache::remember(self::PREFIX . 'communes', self::TTL_REFERENCE, function () {
            return self::select(
                "SELECT IDcommune as id, NomCommune as nom_ar, Codecommune as code, Codedaira as daira_code
                 FROM commune ORDER BY NomCommune ASC"
            );
        });
    }

    public static function communesForWilaya(int $wilayaId): array
    {
        return Cache::remember(self::PREFIX . "communes_wilaya_{$wilayaId}", self::TTL_REFERENCE, function () use ($wilayaId) {
            return self::select(
                "SELECT c.IDcommune as id, c.NomCommune as nom_ar, c.Codecommune as code
                 FROM commune c
                 INNER JOIN daira d ON c.Codedaira = d.Codedaira
                 WHERE d.IDWilayaa = ? ORDER BY c.NomCommune ASC",
                [$wilayaId]
            );
        });
    }

    /** ولايات DFEP واحدة فقط */
    public static function wilayaForDfep(int $dfepId): array
    {
        return Cache::remember(self::PREFIX . "wilaya_dfep_{$dfepId}", self::TTL_REFERENCE, function () use ($dfepId) {
            return self::select(
                "SELECT w.IDWilayaa as id, w.Code as code, w.Nom as nom_ar, w.NomFr as nom_fr
                 FROM wilaya w
                 INNER JOIN dfep d ON d.IDWilayaa = w.IDWilayaa
                 WHERE d.IDDFEP = ? LIMIT 1",
                [$dfepId]
            );
        });
    }

    // ── الشعب (Branches / Filières) ──────────────────────────────────────
    public static function branches(): array
    {
        return Cache::remember(self::PREFIX . 'branches', self::TTL_REFERENCE, function () {
            return self::select(
                "SELECT IDBranche as id, Code as code, Nom as libelle_ar, NomFr as libelle_fr
                 FROM branche ORDER BY Code ASC"
            );
        });
    }

    // ── التخصصات الوطنية ─────────────────────────────────────────────────
    public static function specialites(): array
    {
        return Cache::remember(self::PREFIX . 'specialites', self::TTL_REFERENCE, function () {
            return self::select(
                "SELECT IDSpecialite as id, CodeSpec as code,
                        Nom as libelle_ar, NomFr as libelle_fr,
                        IDBranche as filiere_id
                 FROM specialite ORDER BY Nom ASC"
            );
        });
    }

    // ── أنماط التكوين ─────────────────────────────────────────────────────
    public static function modesFormation(): array
    {
        return Cache::remember(self::PREFIX . 'modes_formation', self::TTL_REFERENCE, function () {
            return self::select(
                "SELECT IDMode_formation as id, Nom as libelle_ar, NomFr as libelle_fr,
                        Abr as abr_ar, AbrFr as abr_fr, Code as code
                 FROM mode_formation ORDER BY NumOrd ASC, NomOrd ASC"
            );
        });
    }

    // ── السنوات التكوينية ────────────────────────────────────────────────
    public static function anneesFormation(): array
    {
        return Cache::remember(self::PREFIX . 'annees_formation', self::TTL_SEMI_STATIC, function () {
            return self::select(
                "SELECT IDAnnee_Formation as id, Nom as libelle_ar, NomFr as libelle_fr,
                        CodeAnne as code, Encour as en_cours,
                        DateD as date_debut, DateF as date_fin
                 FROM annee_formation WHERE IDAnnee_Formation > 0 ORDER BY NumOrd DESC"
            );
        });
    }

    // ── الدورات (Sessions) ───────────────────────────────────────────────
    public static function sessions(): array
    {
        return Cache::remember(self::PREFIX . 'sessions', self::TTL_SEMI_STATIC, function () {
            return self::select(
                "SELECT IDSession as id, Code as code_session,
                        Nom as intitule_ar, NomFr as intitule_fr
                 FROM session ORDER BY DateD DESC"
            );
        });
    }

    // ── طبيعة المؤسسات (INSFP, CFPA…) ───────────────────────────────────
    public static function naturesEtablissement(): array
    {
        return Cache::remember(self::PREFIX . 'natures_etab', self::TTL_REFERENCE, function () {
            return self::select(
                "SELECT IDNature_etsF as id, Abr as abr, Nom as libelle_ar, NomFr as libelle_fr
                 FROM nature_etsf ORDER BY NumOrd ASC"
            );
        });
    }

    // ── أنواع الشهادات (Qualifications/Diplômes) ─────────────────────────
    public static function qualifications(): array
    {
        return Cache::remember(self::PREFIX . 'qualifications', self::TTL_REFERENCE, function () {
            return self::select(
                "SELECT IDqualification_dplm as id, Nom as libelle_ar, NomFr as libelle_fr, code
                 FROM qualification_dplm ORDER BY NumOrd ASC"
            );
        });
    }

    // ── المؤسسات (مقسّمة حسب dfep_id أو etablissement_id) ───────────────

    /** كل المؤسسات — ✅ مسموح في الكاش لأنها بيانات مرجعية، وليست بيانات معاملات */
    public static function etablissements(): array
    {
        return Cache::remember(self::PREFIX . 'etablissements_all', self::TTL_SEMI_STATIC, function () {
            return self::select(
                "SELECT IDetablissement as id, Code as code,
                        Nom as nom_ar, NomFr as nom_fr, IDDFEP as wilaya_id
                 FROM etablissement ORDER BY Nom ASC"
            );
        });
    }

    /** مؤسسات ولاية واحدة (DFEP) */
    public static function etablissementsForDfep(int $dfepId): array
    {
        return Cache::remember(self::PREFIX . "etablissements_dfep_{$dfepId}", self::TTL_SEMI_STATIC, function () use ($dfepId) {
            return self::select(
                "SELECT IDetablissement as id, Code as code,
                        Nom as nom_ar, NomFr as nom_fr, IDDFEP as wilaya_id
                 FROM etablissement WHERE IDDFEP = ? ORDER BY Nom ASC",
                [$dfepId]
            );
        });
    }

    /** مؤسسة واحدة فقط (directeur/etablissement) */
    public static function etablissementById(int $etabId): array
    {
        return Cache::remember(self::PREFIX . "etablissement_{$etabId}", self::TTL_SEMI_STATIC, function () use ($etabId) {
            return self::select(
                "SELECT IDetablissement as id, Code as code,
                        Nom as nom_ar, NomFr as nom_fr, IDDFEP as wilaya_id
                 FROM etablissement WHERE IDetablissement = ?",
                [$etabId]
            );
        });
    }

    // ── Cache Warming (يُشغَّل عند boot أو عبر artisan) ─────────────────

    /**
     * يُسخّن كل الكاش من المستوى 1 دفعة واحدة.
     * يُستخدم في: php artisan sgfep:cache:warm
     */
    public static function warmAll(): array
    {
        $warmed = [];
        $methods = ['wilayas', 'branches', 'specialites', 'modesFormation', 'anneesFormation', 'sessions', 'naturesEtablissement', 'qualifications', 'etablissements', 'communes'];

        foreach ($methods as $method) {
            try {
                Cache::forget(self::PREFIX . self::keyFor($method));
                self::$method();
                $warmed[] = $method;
            } catch (\Throwable $e) {
                Log::warning("[ReferenceCache] Failed to warm {$method}: " . $e->getMessage());
            }
        }

        return $warmed;
    }

    /**
     * يُبطل كل كاش المستوى 1.
     * يُستخدم عند: تغيير بيانات التخصصات أو المؤسسات من لوحة الإدارة.
     */
    public static function flushAll(): void
    {
        $tags = ['wilayas', 'branches', 'specialites', 'modes_formation',
                 'annees_formation', 'sessions', 'natures_etab', 'qualifications',
                 'etablissements_all', 'communes'];

        foreach ($tags as $tag) {
            Cache::forget(self::PREFIX . $tag);
        }
    }

    /** يُبطل كاش مؤسسات ولاية معينة (بعد إضافة مؤسسة جديدة) */
    public static function flushEtablissementsForDfep(int $dfepId): void
    {
        Cache::forget(self::PREFIX . "etablissements_dfep_{$dfepId}");
        Cache::forget(self::PREFIX . 'etablissements_all');
    }

    // ── Private helpers ───────────────────────────────────────────────────

    private static function select(string $sql, array $params = []): array
    {
        try {
            $rows = DB::select($sql, $params);
            return array_map(fn($r) => (array)$r, $rows);
        } catch (\Throwable $e) {
            Log::error('[ReferenceCache] Query failed: ' . $e->getMessage(), ['sql' => $sql]);
            return [];
        }
    }

    private static function keyFor(string $method): string
    {
        return match ($method) {
            'wilayas'              => 'wilayas',
            'branches'             => 'branches',
            'specialites'          => 'specialites',
            'modesFormation'       => 'modes_formation',
            'anneesFormation'      => 'annees_formation',
            'sessions'             => 'sessions',
            'naturesEtablissement' => 'natures_etab',
            'qualifications'       => 'qualifications',
            'etablissements'       => 'etablissements_all',
            default                => $method,
        };
    }
}
