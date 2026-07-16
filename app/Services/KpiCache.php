<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ═══════════════════════════════════════════════════════════════════════
 * KpiCache — المستوى الثاني من الكاش (5 → 15 دقيقة)
 * ═══════════════════════════════════════════════════════════════════════
 *
 * الحسابات الثقيلة للوحة القيادة (KPIs):
 *   - إجمالي المتربصين (حسب الولاية / المؤسسة / الوطني)
 *   - نسب الإناث / الذكور
 *   - توزيع أنماط التكوين
 *   - عدد المؤسسات، العروض، الموظفين
 *
 * القاعدة:
 *  ✅ نضع في الكاش: الأرقام المجملة (COUNT, SUM) — صغيرة الحجم.
 *  ❌ لا نضع أبداً: قوائم المتربصين الكاملة، نقاط الطلاب، ملفات.
 *
 * مثال: مدير ولائي يفتح اللوحة → يقرأ من RAM في 0.01 ثانية.
 *        طالب جديد يتسجل → يظهر في العداد بعد 5 دقائق (مقبول إدارياً).
 * ═══════════════════════════════════════════════════════════════════════
 */
final class KpiCache
{
    /** TTL المؤشرات الوطنية (admin) = 15 دقيقة */
    private const TTL_ADMIN   = 900;  // 15 min

    /** TTL مؤشرات DFEP = 10 دقائق */
    private const TTL_DFEP    = 600;  // 10 min

    /** TTL مؤشرات المؤسسة = 5 دقائق */
    private const TTL_ETAB    = 300;  // 5 min

    private const PREFIX = 'sgfep:kpi:';

    // ══════════════════════════════════════════════════════════════════════
    // §1  ADMIN / وطني  (فلاتر اختيارية: wilaya, etab, mode)
    // ══════════════════════════════════════════════════════════════════════

    public static function admin(
        ?string $selWilaya = null,
        ?string $selEtab   = null,
        ?string $selMode   = null
    ): array {
        $statsEnabled = \App\Helpers\SovereignLicensingHelper::getSetting('feature_complex_stats_enabled', '1') === '1';
        if (!$statsEnabled) {
            return [
                'total_stagiaires'    => 0,
                'total_filles'        => 0,
                'total_garcons'       => 0,
                'total_offres'        => 0,
                'total_etablissements'=> 0,
                'total_encadrements'  => 0,
                'total_specialites'   => 0,
                'total_users'         => 0,
                'total_wilayas'       => 48,
                'total_candidats'     => 0,
                'total_reconduits'    => 0,
                'total_sections_s1'   => 0,
            ];
        }

        $modeId = (int)session('user.IDMode_formation');
        if ($modeId === 10) {
            $selMode = '10';
        }
        $key = self::PREFIX . 'admin:' . md5("{$selWilaya}|{$selEtab}|{$selMode}");

        return Cache::remember($key, self::TTL_ADMIN, function () use ($selWilaya, $selEtab, $selMode) {
            // إذا لم تكن هناك فلاتر وكان جدول dashboard_stats مملوءاً:
            // اقرأ منه مباشرةً (أسرع بكثير) بدل من تشغيل COUNT(*) على ملايين السجلات.
            $hasFilters = !empty($selWilaya) || !empty($selEtab) || !empty($selMode);
            if (!$hasFilters && StatsService::isPopulated()) {
                return StatsService::asKpiArray();
            }

            return self::computeAdminKpis($selWilaya, $selEtab, $selMode);
        });
    }

    // ══════════════════════════════════════════════════════════════════════
    // §2  DFEP
    // ══════════════════════════════════════════════════════════════════════

    public static function dfep(int $dfepId): array
    {
        $statsEnabled = \App\Helpers\SovereignLicensingHelper::getSetting('feature_complex_stats_enabled', '1') === '1';
        if (!$statsEnabled) {
            return [
                'total_stagiaires'    => 0,
                'total_filles'        => 0,
                'total_garcons'       => 0,
                'total_offres'        => 0,
                'total_etablissements'=> 0,
                'total_encadrements'  => 0,
                'total_specialites'   => 0,
                'total_users'         => 0,
                'total_wilayas'       => 1,
                'total_candidats'     => 0,
                'total_reconduits'    => 0,
                'total_sections_s1'   => 0,
            ];
        }

        $modeId = (int)session('user.IDMode_formation');
        $key = self::PREFIX . "dfep:{$dfepId}" . ($modeId === 10 ? ':mode10' : '');

        return Cache::remember($key, self::TTL_DFEP, function () use ($dfepId) {
            return self::computeDfepKpis($dfepId);
        });
    }

    // ══════════════════════════════════════════════════════════════════════
    // §3  Établissement
    // ══════════════════════════════════════════════════════════════════════

    public static function etab(int $etabId): array
    {
        $statsEnabled = \App\Helpers\SovereignLicensingHelper::getSetting('feature_complex_stats_enabled', '1') === '1';
        if (!$statsEnabled) {
            return [
                'total_stagiaires'    => 0,
                'total_filles'        => 0,
                'total_garcons'       => 0,
                'total_offres'        => 0,
                'pending_inscriptions'=> 0,
                'total_etablissements'=> 1,
                'total_encadrements'  => 0,
                'total_specialites'   => 0,
                'total_users'         => 0,
                'total_wilayas'       => 1,
                'total_candidats'     => 0,
                'total_reconduits'    => 0,
                'total_sections_s1'   => 0,
            ];
        }

        $modeId = (int)session('user.IDMode_formation');
        $username = strtolower(session('user.username') ?? '');
        $excludeMode10 = ($username === 'sdtpp');
        $key = self::PREFIX . "etab:{$etabId}" . ($modeId === 10 ? ':mode10' : '') . ($excludeMode10 ? ':exclude_mode10' : '');

        return Cache::remember($key, self::TTL_ETAB, function () use ($etabId) {
            return self::computeEtabKpis($etabId);
        });
    }

    // ══════════════════════════════════════════════════════════════════════
    // §4  Cache Invalidation (يُستدعى عند تسجيل متربص / حذف عرض…)
    // ══════════════════════════════════════════════════════════════════════

    /** يُبطل كاش ولاية معينة + الوطني */
    public static function invalidateDfep(int $dfepId): void
    {
        Cache::forget(self::PREFIX . "dfep:{$dfepId}");
        // الوطني يُبطل أيضاً لأنه يشمل كل الولايات
        self::invalidateAdminAll();
    }

    /** يُبطل كاش مؤسسة معينة + الولاية + الوطني */
    public static function invalidateEtab(int $etabId, int $dfepId = 0): void
    {
        Cache::forget(self::PREFIX . "etab:{$etabId}");
        if ($dfepId > 0) {
            self::invalidateDfep($dfepId);
        } else {
            self::invalidateAdminAll();
        }
    }

    /** يُبطل كل كاش KPI الوطنية (admin) */
    public static function invalidateAdminAll(): void
    {
        // نُبطل مفتاح "بدون فلاتر" على الأقل
        Cache::forget(self::PREFIX . 'admin:' . md5('||'));
    }

    // ══════════════════════════════════════════════════════════════════════
    // §5  حسابات ثقيلة (لا تُستدعى مباشرة — عبر Cache::remember فقط)
    // ══════════════════════════════════════════════════════════════════════

    private static function computeAdminKpis(?string $selWilaya, ?string $selEtab, ?string $selMode): array
    {
        $hasFilters = !empty($selWilaya) || !empty($selEtab) || !empty($selMode);

        [$condOffre, $paramsOffre] = [['1=1'], []];
        [$condEtab,  $paramsEtab]  = [['1=1'], []];
        [$condEnc,   $paramsEnc]   = [['1=1'], []];

        if (!empty($selWilaya)) {
            $condOffre[] = 'o.IDEts_Form IN (SELECT IDetablissement FROM etablissement WHERE IDDFEP IN (SELECT IDDFEP FROM dfep WHERE IDWilayaa = ?))'; $paramsOffre[] = $selWilaya;
            $condEtab[]  = 'e.IDDFEP IN (SELECT IDDFEP FROM dfep WHERE IDWilayaa = ?)';                                                          $paramsEtab[]  = $selWilaya;
            $condEnc[]   = 'enc.IDetablissement IN (SELECT IDetablissement FROM etablissement WHERE IDDFEP IN (SELECT IDDFEP FROM dfep WHERE IDWilayaa = ?))'; $paramsEnc[] = $selWilaya;
        }
        if (!empty($selEtab)) {
            $condOffre[] = 'o.IDEts_Form = ?';       $paramsOffre[] = $selEtab;
            $condEtab[]  = 'e.IDetablissement = ?';  $paramsEtab[]  = $selEtab;
            $condEnc[]   = 'enc.IDetablissement = ?'; $paramsEnc[]  = $selEtab;
        }
        if (!empty($selMode)) {
            $condOffre[] = 'o.IDMode_formation = ?'; $paramsOffre[] = $selMode;
            $condEtab[]  = 'e.IDetablissement IN (SELECT IDEts_Form FROM offre WHERE IDMode_formation = ?)'; $paramsEtab[] = $selMode;
        }

        $wo = implode(' AND ', $condOffre);
        $we = implode(' AND ', $condEtab);
        $wn = implode(' AND ', $condEnc);

        $useApprenant = self::shouldUseApprenantTable();

        if (!$hasFilters) {
            $stagiaires = $useApprenant
                ? self::scalar("SELECT COUNT(a.IDapprenant) as c FROM apprenant a JOIN section s ON a.IDSection=s.IDSection LEFT JOIN apprenant_fin af ON a.IDapprenant=af.IDapprenant WHERE a.statut = 'actif' AND af.IDapprenant IS NULL AND s.DateDF <= CURRENT_DATE() AND s.DateFF >= CURRENT_DATE()", [])
                : self::scalar("SELECT COALESCE(SUM(NbrInscr),0) as c FROM offre WHERE NbrInscr>0", []);

            $filles         = self::scalar("SELECT COUNT(a.IDapprenant) as c FROM apprenant a JOIN section s ON a.IDSection=s.IDSection JOIN candidat c ON a.IDCandidat=c.IDCandidat LEFT JOIN apprenant_fin af ON a.IDapprenant=af.IDapprenant WHERE a.statut = 'actif' AND c.Civ = 2 AND af.IDapprenant IS NULL AND s.DateDF <= CURRENT_DATE() AND s.DateFF >= CURRENT_DATE()", []);
            $offres         = self::scalar("SELECT COUNT(*) as c FROM offre", []);
            $etablissements = self::scalar("SELECT COUNT(*) as c FROM etablissement", []);
            $encadrements   = self::scalar("SELECT COUNT(*) as c FROM encadrement", []);
            $specialites    = self::scalar("SELECT COUNT(DISTINCT IDSpecialite) as c FROM offre", []);
            $reconduits     = self::scalar("SELECT SUM(s.Nbrrecond) as c FROM section s WHERE s.DateDF <= CURRENT_DATE() AND s.DateFF >= CURRENT_DATE()", []);
            $sections_s1    = self::scalar("SELECT COUNT(*) as c FROM section_semestre WHERE Dernier = 1 AND NumSem = 1", []);
        } else {
            $stagiaires = $useApprenant
                ? self::scalar("SELECT COUNT(a.IDapprenant) as c FROM apprenant a JOIN section s ON a.IDSection=s.IDSection JOIN offre o ON s.IDOffre=o.IDOffre JOIN etablissement e ON o.IDEts_Form=e.IDetablissement LEFT JOIN apprenant_fin af ON a.IDapprenant=af.IDapprenant WHERE $wo AND a.statut = 'actif' AND af.IDapprenant IS NULL AND s.DateDF <= CURRENT_DATE() AND s.DateFF >= CURRENT_DATE()", $paramsOffre)
                : self::scalar("SELECT COALESCE(SUM(o.NbrInscr),0) as c FROM offre o INNER JOIN etablissement e ON o.IDEts_Form=e.IDetablissement WHERE $wo AND o.NbrInscr>0", $paramsOffre);

            $filles         = self::scalar("SELECT COUNT(a.IDapprenant) as c FROM apprenant a JOIN section s ON a.IDSection=s.IDSection JOIN offre o ON s.IDOffre=o.IDOffre JOIN etablissement e ON o.IDEts_Form=e.IDetablissement JOIN candidat c ON a.IDCandidat=c.IDCandidat LEFT JOIN apprenant_fin af ON a.IDapprenant=af.IDapprenant WHERE $wo AND a.statut = 'actif' AND c.Civ = 2 AND af.IDapprenant IS NULL AND s.DateDF <= CURRENT_DATE() AND s.DateFF >= CURRENT_DATE()", $paramsOffre);
            $offres         = self::scalar("SELECT COUNT(*) as c FROM offre o INNER JOIN etablissement e ON o.IDEts_Form=e.IDetablissement WHERE $wo", $paramsOffre);
            $etablissements = self::scalar("SELECT COUNT(*) as c FROM etablissement e WHERE $we", $paramsEtab);
            $encadrements   = self::scalar("SELECT COUNT(*) as c FROM encadrement enc WHERE $wn", $paramsEnc);
            $specialites    = self::scalar("SELECT COUNT(DISTINCT o.IDSpecialite) as c FROM offre o INNER JOIN etablissement e ON o.IDEts_Form=e.IDetablissement WHERE $wo", $paramsOffre);
            $reconduits     = self::scalar("SELECT SUM(s.Nbrrecond) as c FROM section s INNER JOIN offre o ON s.IDOffre=o.IDOffre INNER JOIN etablissement e ON o.IDEts_Form=e.IDetablissement WHERE s.DateDF <= CURRENT_DATE() AND s.DateFF >= CURRENT_DATE() AND $wo", $paramsOffre);
            $sections_s1    = self::scalar("SELECT COUNT(*) as c FROM section_semestre ss INNER JOIN section s ON ss.IDSection=s.IDSection INNER JOIN offre o ON s.IDOffre=o.IDOffre INNER JOIN etablissement e ON o.IDEts_Form=e.IDetablissement WHERE ss.Dernier = 1 AND ss.NumSem = 1 AND $wo", $paramsOffre);
        }

        $candidats      = self::scalar("SELECT COUNT(*) as c FROM candidat", []);

        $users = 0;
        try {
            $utCount = self::scalar("SELECT COUNT(*) as c FROM utilisateur", []);
            $etCount = self::scalar("SELECT COUNT(*) as c FROM etablissement WHERE nomUser IS NOT NULL AND nomUser != ''", []);
            $encCount = self::scalar("SELECT COUNT(*) as c FROM encadrement WHERE nin IS NOT NULL AND nin != '' AND MotDePass IS NOT NULL AND MotDePass != ''", []);
            $users = $utCount + $etCount + $encCount;
        } catch (\Throwable $e) {}

        return [
            'total_stagiaires'    => $stagiaires,
            'total_filles'        => $filles,
            'total_garcons'       => max(0, $stagiaires - $filles),
            'total_offres'        => $offres,
            'total_etablissements'=> $etablissements,
            'total_encadrements'  => $encadrements,
            'total_specialites'   => $specialites,
            'total_users'         => $users,
            'total_wilayas'       => 48,
            'total_candidats'     => $candidats,
            'total_reconduits'    => $reconduits,
            'total_sections_s1'   => $sections_s1,
        ];
    }

    private static function computeDfepKpis(int $dfepId): array
    {
        $modeId = (int)session('user.IDMode_formation');

        if ($modeId === 10) {
            $stagiaires = self::scalar(
                "SELECT COALESCE(SUM(o.NbrInscr),0) as c FROM offre o WHERE o.IDEts_Form IN (SELECT IDetablissement FROM etablissement WHERE IDDFEP=?) AND o.IDMode_formation=10 AND o.NbrInscr>0",
                [$dfepId]
            );
            if ($stagiaires === 0) {
                $stagiaires = self::scalar(
                    "SELECT COUNT(*) as c FROM candidat c INNER JOIN offre o ON c.IDOffre=o.IDOffre WHERE o.IDEts_Form IN (SELECT IDetablissement FROM etablissement WHERE IDDFEP=?) AND o.IDMode_formation=10",
                    [$dfepId]
                );
            }

            $filles = self::scalar("SELECT COALESCE(SUM(o.NbrInscrf),0) as c FROM offre o WHERE o.IDEts_Form IN (SELECT IDetablissement FROM etablissement WHERE IDDFEP=?) AND o.IDMode_formation=10 AND o.NbrInscrf>0", [$dfepId]);
            $candidats = self::scalar("SELECT COUNT(*) as c FROM candidat c INNER JOIN offre o ON c.IDOffre=o.IDOffre WHERE o.IDEts_Form IN (SELECT IDetablissement FROM etablissement WHERE IDDFEP=?) AND o.IDMode_formation=10", [$dfepId]);
            $encadrements = self::scalar("SELECT COUNT(*) as c FROM encadrement WHERE IDetablissement IN (SELECT IDetablissement FROM etablissement WHERE IDDFEP=?)", [$dfepId]);
            $specialites = self::scalar("SELECT COUNT(DISTINCT o.IDSpecialite) as c FROM offre o WHERE o.IDEts_Form IN (SELECT IDetablissement FROM etablissement WHERE IDDFEP=?) AND o.IDMode_formation=10", [$dfepId]);
            $reconduits = self::scalar("SELECT SUM(s.Nbrrecond) as c FROM section s JOIN offre o ON s.IDOffre=o.IDOffre WHERE o.IDEts_Form IN (SELECT IDetablissement FROM etablissement WHERE IDDFEP=?) AND o.IDMode_formation=10", [$dfepId]);
            $sections_s1 = self::scalar("SELECT COUNT(*) as c FROM section_semestre ss JOIN section s ON ss.IDSection=s.IDSection JOIN offre o ON s.IDOffre=o.IDOffre WHERE ss.Dernier = 1 AND ss.NumSem = 1 AND o.IDEts_Form IN (SELECT IDetablissement FROM etablissement WHERE IDDFEP=?) AND o.IDMode_formation=10", [$dfepId]);

            return [
                'total_stagiaires'    => $stagiaires,
                'total_filles'        => $filles,
                'total_garcons'       => max(0, $stagiaires - $filles),
                'total_offres'        => self::scalar("SELECT COUNT(*) as c FROM offre o WHERE o.IDEts_Form IN (SELECT IDetablissement FROM etablissement WHERE IDDFEP=?) AND o.IDMode_formation=10", [$dfepId]),
                'total_etablissements'=> self::scalar("SELECT COUNT(*) as c FROM etablissement WHERE IDDFEP=?", [$dfepId]),
                'total_encadrements'  => $encadrements,
                'total_specialites'   => $specialites,
                'total_users'         => self::scalar("SELECT COUNT(*) as c FROM utilisateur u LEFT JOIN etablissement e ON u.IDBureau=e.IDetablissement WHERE (u.Code = ? AND u.IDNature = 4) OR (e.IDDFEP = ?)", [$dfepId, $dfepId]) +
                                         self::scalar("SELECT COUNT(*) as c FROM etablissement WHERE IDDFEP=? AND nomUser IS NOT NULL AND nomUser != ''", [$dfepId]) +
                                         self::scalar("SELECT COUNT(*) as c FROM encadrement enc INNER JOIN etablissement e ON enc.IDetablissement=e.IDetablissement WHERE e.IDDFEP=? AND enc.nin IS NOT NULL AND enc.nin != '' AND enc.MotDePass IS NOT NULL AND enc.MotDePass != ''", [$dfepId]),
                'total_wilayas'       => 1,
                'total_candidats'     => $candidats,
                'total_reconduits'    => $reconduits,
                'total_sections_s1'   => $sections_s1,
            ];
        } else {
            $stagiaires = self::scalar(
                "SELECT COALESCE(SUM(o.NbrInscr),0) as c FROM offre o WHERE o.IDEts_Form IN (SELECT IDetablissement FROM etablissement WHERE IDDFEP=?) AND o.NbrInscr>0",
                [$dfepId]
            );
            if ($stagiaires === 0) {
                $stagiaires = self::scalar(
                    "SELECT COUNT(*) as c FROM candidat c INNER JOIN offre o ON c.IDOffre=o.IDOffre WHERE o.IDEts_Form IN (SELECT IDetablissement FROM etablissement WHERE IDDFEP=?)",
                    [$dfepId]
                );
            }

            $filles = self::scalar("SELECT COALESCE(SUM(o.NbrInscrf),0) as c FROM offre o WHERE o.IDEts_Form IN (SELECT IDetablissement FROM etablissement WHERE IDDFEP=?) AND o.NbrInscrf>0", [$dfepId]);
            $candidats = self::scalar("SELECT COUNT(*) as c FROM candidat c INNER JOIN offre o ON c.IDOffre=o.IDOffre WHERE o.IDEts_Form IN (SELECT IDetablissement FROM etablissement WHERE IDDFEP=?)", [$dfepId]);
            $encadrements = self::scalar("SELECT COUNT(*) as c FROM encadrement WHERE IDetablissement IN (SELECT IDetablissement FROM etablissement WHERE IDDFEP=?)", [$dfepId]);
            $specialites = self::scalar("SELECT COUNT(DISTINCT o.IDSpecialite) as c FROM offre o WHERE o.IDEts_Form IN (SELECT IDetablissement FROM etablissement WHERE IDDFEP=?)", [$dfepId]);
            $reconduits = self::scalar("SELECT SUM(s.Nbrrecond) as c FROM section s JOIN offre o ON s.IDOffre=o.IDOffre WHERE o.IDEts_Form IN (SELECT IDetablissement FROM etablissement WHERE IDDFEP=?)", [$dfepId]);
            $sections_s1 = self::scalar("SELECT COUNT(*) as c FROM section_semestre ss JOIN section s ON ss.IDSection=s.IDSection JOIN offre o ON s.IDOffre=o.IDOffre WHERE ss.Dernier = 1 AND ss.NumSem = 1 AND o.IDEts_Form IN (SELECT IDetablissement FROM etablissement WHERE IDDFEP=?)", [$dfepId]);

            return [
                'total_stagiaires'    => $stagiaires,
                'total_filles'        => $filles,
                'total_garcons'       => max(0, $stagiaires - $filles),
                'total_offres'        => self::scalar("SELECT COUNT(*) as c FROM offre o WHERE o.IDEts_Form IN (SELECT IDetablissement FROM etablissement WHERE IDDFEP=?)", [$dfepId]),
                'total_etablissements'=> self::scalar("SELECT COUNT(*) as c FROM etablissement WHERE IDDFEP=?", [$dfepId]),
                'total_encadrements'  => $encadrements,
                'total_specialites'   => $specialites,
                'total_users'         => self::scalar("SELECT COUNT(*) as c FROM utilisateur u LEFT JOIN etablissement e ON u.IDBureau=e.IDetablissement WHERE (u.Code = ? AND u.IDNature = 4) OR (e.IDDFEP = ?)", [$dfepId, $dfepId]) +
                                         self::scalar("SELECT COUNT(*) as c FROM etablissement WHERE IDDFEP=? AND nomUser IS NOT NULL AND nomUser != ''", [$dfepId]) +
                                         self::scalar("SELECT COUNT(*) as c FROM encadrement enc INNER JOIN etablissement e ON enc.IDetablissement=e.IDetablissement WHERE e.IDDFEP=? AND enc.nin IS NOT NULL AND enc.nin != '' AND enc.MotDePass IS NOT NULL AND enc.MotDePass != ''", [$dfepId]),
                'total_wilayas'       => 1,
                'total_candidats'     => $candidats,
                'total_reconduits'    => $reconduits,
                'total_sections_s1'   => $sections_s1,
            ];
        }
    }

    private static function getEtabScopeIds(int $etabId): array
    {
        $ids = [$etabId];
        try {
            $branches = DB::table('etablissement')
                ->where('IDEts_Form', $etabId)
                ->pluck('IDetablissement')
                ->toArray();
            $ids = array_merge($ids, $branches);
        } catch (\Throwable $e) {}
        return array_unique(array_filter($ids));
    }

    private static function computeEtabKpis(int $etabId): array
    {
        $modeId = (int)session('user.IDMode_formation');
        $username = strtolower(session('user.username') ?? '');
        $excludeMode10 = ($username === 'sdtpp');

        // Resolve scope IDs (the main center + all its branches/extensions)
        $etabIds = self::getEtabScopeIds($etabId);
        $placeholders = implode(',', array_fill(0, count($etabIds), '?'));

        if ($modeId === 10) {
            if (self::shouldUseApprenantTable()) {
                $stagiaires = self::scalar("
                    SELECT COUNT(a.IDapprenant) as c 
                    FROM apprenant a
                    JOIN section s ON a.IDSection = s.IDSection
                    JOIN offre o ON s.IDOffre = o.IDOffre
                    LEFT JOIN apprenant_fin af ON a.IDapprenant = af.IDapprenant
                    WHERE o.IDEts_Form IN ($placeholders) 
                      AND o.IDMode_formation = 10 
                      AND af.IDapprenant IS NULL
                ", $etabIds);

                $filles = self::scalar("
                    SELECT COUNT(a.IDapprenant) as c 
                    FROM apprenant a
                    JOIN section s ON a.IDSection = s.IDSection
                    JOIN offre o ON s.IDOffre = o.IDOffre
                    JOIN candidat cand ON a.IDCandidat = cand.IDCandidat
                    LEFT JOIN apprenant_fin af ON a.IDapprenant = af.IDapprenant
                    WHERE o.IDEts_Form IN ($placeholders) 
                      AND o.IDMode_formation = 10 
                      AND cand.Civ = 2
                      AND af.IDapprenant IS NULL
                ", $etabIds);
            } else {
                $stagiaires = self::scalar("SELECT COALESCE(SUM(NbrInscr),0) as c FROM offre WHERE IDEts_Form IN ($placeholders) AND IDMode_formation=10 AND NbrInscr>0", $etabIds);
                if ($stagiaires === 0) {
                    $stagiaires = self::scalar("SELECT COUNT(*) as c FROM candidat c INNER JOIN offre o ON c.IDOffre=o.IDOffre WHERE o.IDEts_Form IN ($placeholders) AND o.IDMode_formation=10", $etabIds);
                }
                $filles = self::scalar("SELECT COALESCE(SUM(NbrInscrf),0) as c FROM offre WHERE IDEts_Form IN ($placeholders) AND IDMode_formation=10 AND NbrInscrf>0", $etabIds);
            }

            $candidats = self::scalar("SELECT COUNT(*) as c FROM candidat c INNER JOIN offre o ON c.IDOffre=o.IDOffre WHERE o.IDEts_Form IN ($placeholders) AND o.IDMode_formation=10", $etabIds);
            $encadrements = self::scalar("SELECT COUNT(*) as c FROM encadrement WHERE IDetablissement IN ($placeholders)", $etabIds);
            $specialites = self::scalar("SELECT COUNT(DISTINCT IDSpecialite) as c FROM offre WHERE IDEts_Form IN ($placeholders) AND IDMode_formation=10", $etabIds);
            $reconduits = self::scalar("SELECT SUM(s.Nbrrecond) as c FROM section s JOIN offre o ON s.IDOffre=o.IDOffre WHERE o.IDEts_Form IN ($placeholders) AND o.IDMode_formation=10", $etabIds);
            $sections_s1 = self::scalar("SELECT COUNT(*) as c FROM section_semestre ss JOIN section s ON ss.IDSection=s.IDSection JOIN offre o ON s.IDOffre=o.IDOffre WHERE ss.Dernier = 1 AND ss.NumSem = 1 AND o.IDEts_Form IN ($placeholders) AND o.IDMode_formation=10", $etabIds);

            $offres = self::scalar("SELECT COUNT(*) as c FROM offre WHERE IDEts_Form IN ($placeholders) AND IDMode_formation=10", $etabIds);
            $pending_inscriptions = self::scalar("SELECT COUNT(*) as c FROM candidat c INNER JOIN offre o ON c.IDOffre=o.IDOffre WHERE o.IDEts_Form IN ($placeholders) AND o.IDMode_formation=10 AND c.dateInscr >= DATE_SUB(NOW(),INTERVAL 30 DAY)", $etabIds);
        } elseif ($excludeMode10) {
            if (self::shouldUseApprenantTable()) {
                $stagiaires = self::scalar("
                    SELECT COUNT(a.IDapprenant) as c 
                    FROM apprenant a
                    JOIN section s ON a.IDSection = s.IDSection
                    JOIN offre o ON s.IDOffre = o.IDOffre
                    LEFT JOIN apprenant_fin af ON a.IDapprenant = af.IDapprenant
                    WHERE o.IDEts_Form IN ($placeholders) 
                      AND o.IDMode_formation != 10 
                      AND af.IDapprenant IS NULL
                ", $etabIds);

                $filles = self::scalar("
                    SELECT COUNT(a.IDapprenant) as c 
                    FROM apprenant a
                    JOIN section s ON a.IDSection = s.IDSection
                    JOIN offre o ON s.IDOffre = o.IDOffre
                    JOIN candidat cand ON a.IDCandidat = cand.IDCandidat
                    LEFT JOIN apprenant_fin af ON a.IDapprenant = af.IDapprenant
                    WHERE o.IDEts_Form IN ($placeholders) 
                      AND o.IDMode_formation != 10 
                      AND cand.Civ = 2
                      AND af.IDapprenant IS NULL
                ", $etabIds);
            } else {
                $stagiaires = self::scalar("SELECT COALESCE(SUM(NbrInscr),0) as c FROM offre WHERE IDEts_Form IN ($placeholders) AND IDMode_formation != 10 AND NbrInscr>0", $etabIds);
                if ($stagiaires === 0) {
                    $stagiaires = self::scalar("SELECT COUNT(*) as c FROM candidat c INNER JOIN offre o ON c.IDOffre=o.IDOffre WHERE o.IDEts_Form IN ($placeholders) AND o.IDMode_formation != 10", $etabIds);
                }
                $filles = self::scalar("SELECT COALESCE(SUM(NbrInscrf),0) as c FROM offre WHERE IDEts_Form IN ($placeholders) AND IDMode_formation != 10 AND NbrInscrf>0", $etabIds);
            }

            $candidats = self::scalar("SELECT COUNT(*) as c FROM candidat c INNER JOIN offre o ON c.IDOffre=o.IDOffre WHERE o.IDEts_Form IN ($placeholders) AND o.IDMode_formation != 10", $etabIds);
            $encadrements = self::scalar("SELECT COUNT(*) as c FROM encadrement WHERE IDetablissement IN ($placeholders)", $etabIds);
            $specialites = self::scalar("SELECT COUNT(DISTINCT IDSpecialite) as c FROM offre WHERE IDEts_Form IN ($placeholders) AND IDMode_formation != 10", $etabIds);
            $reconduits = self::scalar("SELECT SUM(s.Nbrrecond) as c FROM section s JOIN offre o ON s.IDOffre=o.IDOffre WHERE o.IDEts_Form IN ($placeholders) AND o.IDMode_formation != 10", $etabIds);
            $sections_s1 = self::scalar("SELECT COUNT(*) as c FROM section_semestre ss JOIN section s ON ss.IDSection=s.IDSection JOIN offre o ON s.IDOffre=o.IDOffre WHERE ss.Dernier = 1 AND ss.NumSem = 1 AND o.IDEts_Form IN ($placeholders) AND o.IDMode_formation != 10", $etabIds);

            $offres = self::scalar("SELECT COUNT(*) as c FROM offre WHERE IDEts_Form IN ($placeholders) AND IDMode_formation != 10", $etabIds);
            $pending_inscriptions = self::scalar("SELECT COUNT(*) as c FROM candidat c INNER JOIN offre o ON c.IDOffre=o.IDOffre WHERE o.IDEts_Form IN ($placeholders) AND o.IDMode_formation != 10 AND c.dateInscr >= DATE_SUB(NOW(),INTERVAL 30 DAY)", $etabIds);
        } else {
            if (self::shouldUseApprenantTable()) {
                $stagiaires = self::scalar("
                    SELECT COUNT(a.IDapprenant) as c 
                    FROM apprenant a
                    JOIN section s ON a.IDSection = s.IDSection
                    JOIN offre o ON s.IDOffre = o.IDOffre
                    LEFT JOIN apprenant_fin af ON a.IDapprenant = af.IDapprenant
                    WHERE o.IDEts_Form IN ($placeholders) 
                      AND a.statut = 'actif'
                      AND af.IDapprenant IS NULL
                      AND s.DateDF <= CURRENT_DATE()
                      AND s.DateFF >= CURRENT_DATE()
                ", $etabIds);

                $filles = self::scalar("
                    SELECT COUNT(a.IDapprenant) as c 
                    FROM apprenant a
                    JOIN section s ON a.IDSection = s.IDSection
                    JOIN offre o ON s.IDOffre = o.IDOffre
                    JOIN candidat cand ON a.IDCandidat = cand.IDCandidat
                    LEFT JOIN apprenant_fin af ON a.IDapprenant = af.IDapprenant
                    WHERE o.IDEts_Form IN ($placeholders) 
                      AND a.statut = 'actif'
                      AND cand.Civ = 2
                      AND af.IDapprenant IS NULL
                      AND s.DateDF <= CURRENT_DATE()
                      AND s.DateFF >= CURRENT_DATE()
                ", $etabIds);
            } else {
                $stagiaires = self::scalar("SELECT COALESCE(SUM(NbrInscr),0) as c FROM offre WHERE IDEts_Form IN ($placeholders) AND NbrInscr>0", $etabIds);
                if ($stagiaires === 0) {
                    $stagiaires = self::scalar("SELECT COUNT(*) as c FROM candidat c INNER JOIN offre o ON c.IDOffre=o.IDOffre WHERE o.IDEts_Form IN ($placeholders)", $etabIds);
                }
                $filles = self::scalar("SELECT COALESCE(SUM(NbrInscrf),0) as c FROM offre WHERE IDEts_Form IN ($placeholders) AND NbrInscrf>0", $etabIds);
            }

            $candidats = self::scalar("SELECT COUNT(*) as c FROM candidat c INNER JOIN offre o ON c.IDOffre=o.IDOffre WHERE o.IDEts_Form IN ($placeholders)", $etabIds);
            $encadrements = self::scalar("SELECT COUNT(*) as c FROM encadrement WHERE IDetablissement IN ($placeholders)", $etabIds);
            $specialites = self::scalar("SELECT COUNT(DISTINCT IDSpecialite) as c FROM offre WHERE IDEts_Form IN ($placeholders)", $etabIds);
            $reconduits = self::scalar("SELECT SUM(s.Nbrrecond) as c FROM section s JOIN offre o ON s.IDOffre=o.IDOffre WHERE o.IDEts_Form IN ($placeholders)", $etabIds);
            $sections_s1 = self::scalar("SELECT COUNT(*) as c FROM section_semestre ss JOIN section s ON ss.IDSection=s.IDSection JOIN offre o ON s.IDOffre=o.IDOffre WHERE ss.Dernier = 1 AND ss.NumSem = 1 AND o.IDEts_Form IN ($placeholders)", $etabIds);

            $offres = self::scalar("SELECT COUNT(*) as c FROM offre WHERE IDEts_Form IN ($placeholders)", $etabIds);
            $pending_inscriptions = self::scalar("SELECT COUNT(*) as c FROM candidat c INNER JOIN offre o ON c.IDOffre=o.IDOffre WHERE o.IDEts_Form IN ($placeholders) AND c.dateInscr >= DATE_SUB(NOW(),INTERVAL 30 DAY)", $etabIds);
        }

        return [
            'total_stagiaires'    => $stagiaires,
            'total_filles'        => $filles,
            'total_garcons'       => max(0, $stagiaires - $filles),
            'total_offres'        => $offres,
            'pending_inscriptions'=> $pending_inscriptions,
            'total_etablissements'=> 1,
            'total_encadrements'  => $encadrements,
            'total_specialites'   => $specialites,
            'total_users'         => self::scalar("SELECT COUNT(*) as c FROM utilisateur WHERE IDBureau IN ($placeholders)", $etabIds) +
                                     self::scalar("SELECT COUNT(*) as c FROM etablissement WHERE IDetablissement IN ($placeholders) AND nomUser IS NOT NULL AND nomUser != ''", $etabIds) +
                                     self::scalar("SELECT COUNT(*) as c FROM encadrement WHERE IDetablissement IN ($placeholders) AND nin IS NOT NULL AND nin != '' AND MotDePass IS NOT NULL AND MotDePass != ''", $etabIds),
            'total_wilayas'       => 1,
            'total_candidats'     => $candidats,
            'total_reconduits'    => $reconduits,
            'total_sections_s1'   => $sections_s1,
        ];
    }

    // ── Utilities ─────────────────────────────────────────────────────────

    /** نُعيد رقماً واحداً (int) — الأكثر أماناً للكاش (بضعة bytes فقط) */
    private static function scalar(string $sql, array $params): int
    {
        try {
            $row = DB::selectOne($sql, $params);
            return $row ? (int)$row->c : 0;
        } catch (\Throwable $e) {
            Log::error('[KpiCache] Query failed: ' . $e->getMessage());
            return 0;
        }
    }

    /** التحقق مما إذا كان جدول apprenant جاهزاً للاستخدام */
    private static function shouldUseApprenantTable(): bool
    {
        // بعد إصلاح أعمدة MySQL ومزامنة البيانات، نستخدم apprenant للحصول على عدد دقيق.
        // الجدول مجهزاً للاستخدام بعد المزامنة.
        static $checked = null;
        if ($checked !== null) return $checked;

        try {
            $count = (int) DB::selectOne("SELECT COUNT(*) as c FROM apprenant LIMIT 1")->c;
            return $checked = ($count > 0);
        } catch (\Throwable $e) {
            return $checked = false;
        }
    }
}
