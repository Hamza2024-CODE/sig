<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Domains\Academic\Services\DiplomeService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Exception;

/**
 * DiplomeController — Final Evaluation & Diploma Management
 *
 * ═══════════════════════════════════════════════════════════════
 * DATABASE REALITY (confirmed by diagnosis scripts):
 * ─────────────────────────────────────────────────────────────
 *  • apprenant      : 1,324,898 rows  — 1,017,208 are ORPHANED (no matching candidat)
 *  • apprenant_fin  : 1,205,127 rows  — most have IDapprenant that don't exist in apprenant
 *  • candidat       :   730,000 rows
 *  • VALID full chain (apprenant ⟶ candidat ⟶ apprenant_fin ⟶ section ⟶ offre) ≈ 94,135 rows
 *
 * ═══════════════════════════════════════════════════════════════
 * QUERY STRATEGY (proven by benchmarks):
 * ─────────────────────────────────────────────────────────────
 *  • STRAIGHT_JOIN driving from apprenant (PK scan) avoids temp tables / filesort.
 *  • ORDER BY a.IDapprenant DESC + WHERE a.IDapprenant < :cursor (keyset pagination)
 *    makes every page equally fast (~3s) regardless of page depth.
 *  • LIMIT/OFFSET (without cursor) degrades: offset 300 → 5s, offset 3000 → 42s.
 *  • COUNT(*) with full joins always takes ~65s → cached with 10-min TTL.
 *  • First load after cache expiry: total_count shown from stale cache (or 0 if cold).
 *    A background artisan command (sgfep:cache:warm) refreshes it periodically.
 * ═══════════════════════════════════════════════════════════════
 */
class DiplomeController extends Controller
{
    protected DiplomeService $service;

    public function __construct(DiplomeService $service)
    {
        $this->service = $service;
        if (app()->runningInConsole()) { return; }
        $this->middleware(function ($request, $next) {
            if (!session()->has("user")) {
                return redirect('/login');
            }
            $path = $request->path();
            if (str_contains($path, 'statistiques') || str_contains($path, 'liste-2021-present')) {
                $user = session('user') ?? [];
                $role = strtolower($user['role_code'] ?? '');
                $allowedRoles = ['admin', 'ministre', 'secretaire_general', 'central', 'high_admin', 'dfep'];
                if (!in_array($role, $allowedRoles)) {
                    abort(403, 'Unauthorized action.');
                }
            }
            return $next($request);
        });
    }

    private const PER_PAGE = 30;

    // ─────────────────────────────────────────────────────────────
    // Core FROM/JOIN used for all page data queries.
    // STRAIGHT_JOIN forces apprenant as the driving table (PK scan).
    // ─────────────────────────────────────────────────────────────
    private const BASE_SELECT = "
        SELECT STRAIGHT_JOIN
               a.IDapprenant as id, a.Nccp as numero_matricule, a.statut,
               c.Nom as nom_ar, c.Prenom as prenom_ar,
               c.NomFr as nom_fr, c.PrenomFr as prenom_fr,
               sp.Nom as spec_ar, sp.NomFr as spec_fr,
               f.IDApprenant_Fin as diplome_id,
               f.Numdiplome as numero_diplome,
               f.MoyGen as moyenne_generale,
               CASE WHEN f.MoyGen >= 16 THEN 'tres_bien'
                    WHEN f.MoyGen >= 14 THEN 'bien'
                    WHEN f.MoyGen >= 12 THEN 'assez_bien'
                    ELSE 'passable' END as mention,
               CASE WHEN f.Numdiplome IS NOT NULL AND f.Numdiplome != '' THEN 1 ELSE 0 END as est_delivre
        FROM apprenant a
        JOIN candidat c    ON c.IDCandidat = a.IDCandidat
        JOIN apprenant_fin f ON f.IDapprenant = a.IDapprenant
        JOIN section s     ON a.IDSection = s.IDSection
        JOIN offre o       ON s.IDOffre = o.IDOffre
        JOIN specialite sp ON o.IDSpecialite = sp.IDSpecialite
        JOIN session sess  ON o.IDSession = sess.IDSession
        JOIN semestre_formation sf ON sess.IDSemestre_formation = sf.IDSemestre_formation
    ";

    private const BASE_COUNT = "
        SELECT STRAIGHT_JOIN COUNT(*) as c
        FROM apprenant a
        JOIN candidat c    ON c.IDCandidat = a.IDCandidat
        JOIN apprenant_fin f ON f.IDapprenant = a.IDapprenant
        JOIN section s     ON a.IDSection = s.IDSection
        JOIN offre o       ON s.IDOffre = o.IDOffre
        JOIN session sess  ON o.IDSession = sess.IDSession
        JOIN semestre_formation sf ON sess.IDSemestre_formation = sf.IDSemestre_formation
    ";

    /**
     * List trainees with final evaluation data.
     */
    public function index(\Illuminate\Http\Request $request): mixed
    {
        $user      = session('user') ?? [];
        $role_code = strtolower($user['role_code'] ?? '');
        $etab_id   = (int)($user['etablissement_id'] ?? $user['IDEts_Form'] ?? 0);
        $dfep_id   = (int)($user['iddfep'] ?? $user['IDDFEP'] ?? 0);

        $search      = trim($request->query('search', ''));
        $filterEtab  = (int)$request->query('filter_etab', 0);
        $filterStatus = $request->query('filter_status', 'all');
        $filterMode  = (int)$request->query('filter_mode', 0);
        $filterAnnee = (int)$request->query('filter_annee', 0);
        $filterSpec  = (int)$request->query('filter_spec', 0);
        $filterQualif = (int)$request->query('filter_qualif', 0);
        $cursor      = (int)$request->query('cursor', 0);   // IDapprenant of last row seen (keyset)
        $page        = max(1, (int)$request->query('page', 1)); // for display only

        $isAdmin = in_array($role_code, ['admin', 'ministre', 'secretaire_general', 'central', 'high_admin']);
        $isDfep  = ($role_code === 'dfep' && $dfep_id > 0);
        $isEtab  = (!$isAdmin && !$isDfep && $etab_id > 0);

        // ── WHERE clauses ─────────────────────────────────────────
        $where  = [];
        $params = [];

        // Scope by role / establishment
        if ($isAdmin && $filterEtab > 0) {
            $where[]  = "o.IDEts_Form = ?";
            $params[] = $filterEtab;
        } elseif ($isDfep) {
            if ($filterEtab > 0) {
                $where[]  = "o.IDEts_Form = ?";
                $params[] = $filterEtab;
            } else {
                $where[]  = "o.IDEts_Form IN (SELECT IDetablissement FROM etablissement WHERE IDDFEP = ?)";
                $params[] = $dfep_id;
            }
        } elseif ($isEtab) {
            $where[]  = "o.IDEts_Form = ?";
            $params[] = $etab_id;
        } elseif (!$isAdmin) {
            $where[] = "1=0";
        }

        // Status filter
        if ($filterStatus === 'issued') {
            $where[] = "f.Numdiplome IS NOT NULL AND f.Numdiplome != ''";
        } elseif ($filterStatus === 'not_issued') {
            $where[] = "(f.Numdiplome IS NULL OR f.Numdiplome = '')";
        }

        // Advanced filters
        if ($filterMode > 0) {
            $where[] = "o.IDMode_formation = ?";
            $params[] = $filterMode;
        }
        if ($filterAnnee > 0) {
            $where[] = "sf.IDAnnee_Formation = ?";
            $params[] = $filterAnnee;
        }
        if ($filterSpec > 0) {
            $where[] = "o.IDSpecialite = ?";
            $params[] = $filterSpec;
        }
        if ($filterQualif > 0) {
            $where[] = "o.IDqualification_dplm = ?";
            $params[] = $filterQualif;
        }

        // Search filter: resolve candidate IDs first
        $hasSearch = $search !== '';
        if ($hasSearch) {
            if (is_numeric($search)) {
                $appRows = DB::select(
                    "SELECT IDapprenant FROM apprenant WHERE Nccp = ? LIMIT 500",
                    [$search]
                );
                $aids = array_map(fn($r) => (int)$r->IDapprenant, $appRows);
                if (!empty($aids)) {
                    $ph      = implode(',', array_fill(0, count($aids), '?'));
                    $where[] = "a.IDapprenant IN ($ph)";
                    $params  = array_merge($params, $aids);
                } else {
                    $where[] = "1=0";
                }
            } else {
                $isArabic = (bool)preg_match('/\p{Arabic}/u', $search);
                $like     = $search . '%';
                $cRows    = $this->searchCandidats($isArabic, $like);
                if (empty($cRows)) {
                    $cRows = $this->searchCandidats($isArabic, '%' . $search . '%');
                }
                if (!empty($cRows)) {
                    $cids    = array_map(fn($r) => (int)$r->IDCandidat, $cRows);
                    $cidPh   = implode(',', array_fill(0, count($cids), '?'));
                    $aRows   = DB::select("SELECT IDapprenant FROM apprenant WHERE IDCandidat IN ($cidPh) LIMIT 500", $cids);
                    $aids    = array_map(fn($r) => (int)$r->IDapprenant, $aRows);
                    if (!empty($aids)) {
                        $aidPh   = implode(',', array_fill(0, count($aids), '?'));
                        $where[] = "a.IDapprenant IN ($aidPh)";
                        $params  = array_merge($params, $aids);
                    } else {
                        $where[] = "1=0";
                    }
                } else {
                    $where[] = "1=0";
                }
            }
        }

        // Keyset pagination cursor (only for unfiltered/unstatus queries)
        $useCursor = ($cursor > 0 && !$hasSearch);
        if ($useCursor) {
            $where[] = "a.IDapprenant < ?";
            $params[] = $cursor;
        }

        $whereSQL = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        // Determine if we should use STRAIGHT_JOIN (unfiltered admin views only)
        $isFiltered = (!$isAdmin || $filterEtab > 0 || $filterMode > 0 || $filterAnnee > 0 || $filterSpec > 0 || $filterQualif > 0 || $search !== '' || $filterStatus !== 'all');
        $selectPrefix = $isFiltered ? "SELECT" : "SELECT STRAIGHT_JOIN";
        $baseSelect = str_replace("SELECT STRAIGHT_JOIN", $selectPrefix, self::BASE_SELECT);
        $baseCount  = str_replace("SELECT STRAIGHT_JOIN", $selectPrefix, self::BASE_COUNT);

        // ── Total count (cached, computed async) ──────────────────
        $ckSuffix    = md5($whereSQL . serialize(array_filter($params, fn($p) => $p !== $cursor)));
        $countCacheKey = "dip_count_v3_{$ckSuffix}";

        $totalCount = (int)Cache::get($countCacheKey, 0);

        // If cache is cold and no filters, use the known baseline to avoid blocking
        if ($totalCount === 0 && $isAdmin && empty($where)) {
            $totalCount = (int)Cache::get('dip_total_baseline', 94135);
        }

        // If count is missing, refresh it in the background (non-blocking)
        if ($totalCount === 0 || (!Cache::has($countCacheKey) && !$hasSearch)) {
            // Only compute if not a search (those are fast enough to compute inline)
            if ($hasSearch || !$isAdmin) {
                // For searches/scoped: compute count inline (fast subset)
                $countWhere = $whereSQL;
                $countParams = $params;
                if ($useCursor) {
                    // Remove cursor condition for count
                    $countWhere = !empty($where)
                        ? 'WHERE ' . implode(' AND ', array_filter($where, fn($w) => !str_contains($w, 'IDapprenant <')))
                        : '';
                    $countParams = array_filter($params, fn($p) => $p !== $cursor);
                }
                try {
                    $totalCount = (int)(DB::selectOne($baseCount . ' ' . $countWhere, array_values($countParams))->c ?? 0);
                    Cache::put($countCacheKey, $totalCount, 120);
                } catch (\Throwable $e) {
                    $totalCount = 0;
                }
            }
        }

        // ── Issued count (always fast on apprenant_fin alone for admin) ──
        $issuedCount = Cache::remember('dip_issued_v3', 600, function () {
            try {
                return (int)DB::selectOne(
                    "SELECT COUNT(*) as c FROM apprenant_fin WHERE Numdiplome IS NOT NULL AND Numdiplome != ''"
                )->c;
            } catch (\Throwable $e) {
                return 0;
            }
        });

        // ── Page data query (STRAIGHT_JOIN, always fast ~3s) ─────
        $stagiaires = [];
        $nextCursor = 0;
        $prevCursor = 0;

        try {
            $rows = DB::select(
                $baseSelect . ' ' . $whereSQL .
                " ORDER BY a.IDapprenant DESC LIMIT " . (self::PER_PAGE + 1),
                $params
            );

            // Has next page?
            $hasMore = count($rows) > self::PER_PAGE;
            if ($hasMore) {
                array_pop($rows);
            }

            $stagiaires = array_map(fn($item) => (array)$item, $rows);
            $nextCursor = $hasMore && !empty($stagiaires) ? (int)(end($stagiaires)['id']) : 0;
        } catch (\Throwable $e) {
            $stagiaires = [];
        }

        // Compute total pages from cached count (approximate)
        $totalPages = $totalCount > 0 ? (int)ceil($totalCount / self::PER_PAGE) : 1;

        // ── Reference data ────────────────────────────────────────
        $etablissements = [];
        if ($isAdmin) {
            $etablissements = \App\Services\ReferenceCache::etablissements();
        } elseif ($isDfep) {
            $etablissements = \App\Services\ReferenceCache::etablissementsForDfep($dfep_id);
        }

        $modes = \App\Services\ReferenceCache::modesFormation();
        $annees = \App\Services\ReferenceCache::anneesFormation();
        $specialites = \App\Services\ReferenceCache::specialites();
        $qualifications = \App\Services\ReferenceCache::qualifications();

        return $this->render('admin/diplomes/index', [
            'title'          => 'التقييم النهائي وإصدار الشهادات / Délibération & Diplômes',
            'stagiaires'     => $stagiaires,
            'issuedCount'    => $issuedCount,
            'total_count'    => $totalCount,
            'page'           => $page,
            'total_pages'    => $totalPages,
            'per_page'       => self::PER_PAGE,
            'search'         => $search,
            'filter_etab'    => $filterEtab,
            'filter_status'  => $filterStatus,
            'filter_mode'    => $filterMode,
            'filter_annee'   => $filterAnnee,
            'filter_spec'    => $filterSpec,
            'filter_qualif'  => $filterQualif,
            'etablissements' => $etablissements,
            'modes'          => $modes,
            'annees'         => $annees,
            'specialites'    => $specialites,
            'qualifications' => $qualifications,
            'role_code'      => $role_code,
            'next_cursor'    => $nextCursor,
            'has_more'       => $nextCursor > 0,
        ]);
    }

    /**
     * Dedicated page displaying all graduates since 2021 (for DFEP/Admin only)
     */
    public function liste2021(\Illuminate\Http\Request $request): mixed
    {
        $user      = session('user') ?? [];
        $role_code = strtolower($user['role_code'] ?? '');
        $etab_id   = (int)($user['etablissement_id'] ?? $user['IDEts_Form'] ?? 0);
        $dfep_id   = (int)($user['iddfep'] ?? $user['IDDFEP'] ?? 0);

        $search      = trim($request->query('search', ''));
        $filterEtab  = (int)$request->query('filter_etab', 0);
        $filterStatus = $request->query('filter_status', 'all');
        $filterMode  = (int)$request->query('filter_mode', 0);
        $filterAnnee = (int)$request->query('filter_annee', 0);
        $filterSpec  = (int)$request->query('filter_spec', 0);
        $filterQualif = (int)$request->query('filter_qualif', 0);
        $cursor      = (int)$request->query('cursor', 0);   // IDapprenant of last row seen (keyset)
        $page        = max(1, (int)$request->query('page', 1)); // for display only

        $isAdmin = in_array($role_code, ['admin', 'ministre', 'secretaire_general', 'central', 'high_admin']);
        $isDfep  = ($role_code === 'dfep' && $dfep_id > 0);
        $isEtab  = (!$isAdmin && !$isDfep && $etab_id > 0);

        // ── WHERE clauses ─────────────────────────────────────────
        $where  = [];
        $params = [];

        // Scope by role / establishment
        if ($isAdmin && $filterEtab > 0) {
            $where[]  = "o.IDEts_Form = ?";
            $params[] = $filterEtab;
        } elseif ($isDfep) {
            if ($filterEtab > 0) {
                $where[]  = "o.IDEts_Form = ?";
                $params[] = $filterEtab;
            } else {
                $where[]  = "o.IDEts_Form IN (SELECT IDetablissement FROM etablissement WHERE IDDFEP = ?)";
                $params[] = $dfep_id;
            }
        } elseif ($isEtab) {
            $where[]  = "o.IDEts_Form = ?";
            $params[] = $etab_id;
        } elseif (!$isAdmin) {
            $where[] = "1=0";
        }

        // Status filter
        if ($filterStatus === 'issued') {
            $where[] = "f.Numdiplome IS NOT NULL AND f.Numdiplome != ''";
        } elseif ($filterStatus === 'not_issued') {
            $where[] = "(f.Numdiplome IS NULL OR f.Numdiplome = '')";
        }

        // Advanced filters
        if ($filterMode > 0) {
            $where[] = "o.IDMode_formation = ?";
            $params[] = $filterMode;
        }
        if ($filterAnnee > 0) {
            $where[] = "sf.IDAnnee_Formation = ?";
            $params[] = $filterAnnee;
        } else {
            // Default to showing graduates from 2021 onwards
            $where[] = "sf.IDAnnee_Formation >= 14";
        }
        if ($filterSpec > 0) {
            $where[] = "o.IDSpecialite = ?";
            $params[] = $filterSpec;
        }
        if ($filterQualif > 0) {
            $where[] = "o.IDqualification_dplm = ?";
            $params[] = $filterQualif;
        }

        // Search filter: resolve candidate IDs first
        $hasSearch = $search !== '';
        if ($hasSearch) {
            if (is_numeric($search)) {
                $appRows = DB::select(
                    "SELECT IDapprenant FROM apprenant WHERE Nccp = ? LIMIT 500",
                    [$search]
                );
                $aids = array_map(fn($r) => (int)$r->IDapprenant, $appRows);
                if (!empty($aids)) {
                    $ph      = implode(',', array_fill(0, count($aids), '?'));
                    $where[] = "a.IDapprenant IN ($ph)";
                    $params  = array_merge($params, $aids);
                } else {
                    $where[] = "1=0";
                }
            } else {
                $isArabic = (bool)preg_match('/\p{Arabic}/u', $search);
                $like     = $search . '%';
                $cRows    = $this->searchCandidats($isArabic, $like);
                if (empty($cRows)) {
                    $cRows = $this->searchCandidats($isArabic, '%' . $search . '%');
                }
                if (!empty($cRows)) {
                    $cids    = array_map(fn($r) => (int)$r->IDCandidat, $cRows);
                    $cidPh   = implode(',', array_fill(0, count($cids), '?'));
                    $aRows   = DB::select("SELECT IDapprenant FROM apprenant WHERE IDCandidat IN ($cidPh) LIMIT 500", $cids);
                    $aids    = array_map(fn($r) => (int)$r->IDapprenant, $aRows);
                    if (!empty($aids)) {
                        $aidPh   = implode(',', array_fill(0, count($aids), '?'));
                        $where[] = "a.IDapprenant IN ($aidPh)";
                        $params  = array_merge($params, $aids);
                    } else {
                        $where[] = "1=0";
                    }
                } else {
                    $where[] = "1=0";
                }
            }
        }

        // Keyset pagination cursor (only for unfiltered/unstatus queries)
        $useCursor = ($cursor > 0 && !$hasSearch);
        if ($useCursor) {
            $where[] = "a.IDapprenant < ?";
            $params[] = $cursor;
        }

        $whereSQL = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        // Determine if we should use STRAIGHT_JOIN (unfiltered admin views only)
        $isFiltered = (!$isAdmin || $filterEtab > 0 || $filterMode > 0 || $filterAnnee > 0 || $filterSpec > 0 || $filterQualif > 0 || $search !== '' || $filterStatus !== 'all');
        $selectPrefix = $isFiltered ? "SELECT" : "SELECT STRAIGHT_JOIN";
        $baseSelect = str_replace("SELECT STRAIGHT_JOIN", $selectPrefix, self::BASE_SELECT);
        $baseCount  = str_replace("SELECT STRAIGHT_JOIN", $selectPrefix, self::BASE_COUNT);

        // ── Total count (cached, computed async) ──────────────────
        $ckSuffix    = md5($whereSQL . serialize(array_filter($params, fn($p) => $p !== $cursor)));
        $countCacheKey = "dip_count_v3_{$ckSuffix}";

        $totalCount = (int)Cache::get($countCacheKey, 0);

        // If cache is cold and no filters, use the known baseline to avoid blocking
        if ($totalCount === 0 && $isAdmin && empty($where)) {
            $totalCount = (int)Cache::get('dip_total_baseline', 94135);
        }

        // If count is missing, refresh it in the background (non-blocking)
        if ($totalCount === 0 || (!Cache::has($countCacheKey) && !$hasSearch)) {
            if ($hasSearch || !$isAdmin) {
                $countWhere = $whereSQL;
                $countParams = $params;
                if ($useCursor) {
                    $countWhere = !empty($where)
                        ? 'WHERE ' . implode(' AND ', array_filter($where, fn($w) => !str_contains($w, 'IDapprenant <')))
                        : '';
                    $countParams = array_filter($params, fn($p) => $p !== $cursor);
                }
                try {
                    $totalCount = (int)(DB::selectOne($baseCount . ' ' . $countWhere, array_values($countParams))->c ?? 0);
                    Cache::put($countCacheKey, $totalCount, 120);
                } catch (\Throwable $e) {
                    $totalCount = 0;
                }
            }
        }

        // ── Issued count (always fast on apprenant_fin alone for admin) ──
        $issuedCount = Cache::remember('dip_issued_v3', 600, function () {
            try {
                return (int)DB::selectOne("SELECT COUNT(*) as c FROM apprenant_fin WHERE Numdiplome IS NOT NULL AND Numdiplome != ''")->c;
            } catch (\Throwable $e) {
                return 0;
            }
        });

        // ── Page data query (STRAIGHT_JOIN, always fast ~3s) ─────
        $stagiaires = [];
        $nextCursor = 0;

        try {
            $rows = DB::select(
                $baseSelect . ' ' . $whereSQL .
                " ORDER BY a.IDapprenant DESC LIMIT " . (self::PER_PAGE + 1),
                $params
            );

            $hasMore = count($rows) > self::PER_PAGE;
            if ($hasMore) {
                array_pop($rows);
            }

            $stagiaires = array_map(fn($item) => (array)$item, $rows);
            $nextCursor = $hasMore && !empty($stagiaires) ? (int)(end($stagiaires)['id']) : 0;
        } catch (\Throwable $e) {
            $stagiaires = [];
        }

        $totalPages = $totalCount > 0 ? (int)ceil($totalCount / self::PER_PAGE) : 1;

        // ── Reference data ────────────────────────────────────────
        $etablissements = [];
        if ($isAdmin) {
            $etablissements = \App\Services\ReferenceCache::etablissements();
        } elseif ($isDfep) {
            $etablissements = \App\Services\ReferenceCache::etablissementsForDfep($dfep_id);
        }

        $modes = \App\Services\ReferenceCache::modesFormation();
        $annees = \App\Services\ReferenceCache::anneesFormation();
        $specialites = \App\Services\ReferenceCache::specialites();
        $qualifications = \App\Services\ReferenceCache::qualifications();

        return $this->render('admin/diplomes/liste_2021', [
            'title'          => 'سجل وخريجي الفترة (منذ 2021) / Registre des Diplômés (Depuis 2021)',
            'stagiaires'     => $stagiaires,
            'issuedCount'    => $issuedCount,
            'total_count'    => $totalCount,
            'page'           => $page,
            'total_pages'    => $totalPages,
            'per_page'       => self::PER_PAGE,
            'search'         => $search,
            'filter_etab'    => $filterEtab,
            'filter_status'  => $filterStatus,
            'filter_mode'    => $filterMode,
            'filter_annee'   => $filterAnnee,
            'filter_spec'    => $filterSpec,
            'filter_qualif'  => $filterQualif,
            'etablissements' => $etablissements,
            'modes'          => $modes,
            'annees'         => $annees,
            'specialites'    => $specialites,
            'qualifications' => $qualifications,
            'role_code'      => $role_code,
            'next_cursor'    => $nextCursor,
            'has_more'       => $nextCursor > 0,
        ]);
    
    /**
     * Display graduates statistics dashboard
     */
    public function statistiques(\Illuminate\Http\Request $request): mixed
    {
        $user      = session('user') ?? [];
        $role_code = strtolower($user['role_code'] ?? '');
        $etab_id   = (int)($user['etablissement_id'] ?? $user['IDEts_Form'] ?? 0);
        $dfep_id   = (int)($user['iddfep'] ?? $user['IDDFEP'] ?? 0);

        $filterWilaya = (int)$request->query('filter_wilaya', 0);
        $filterEtab   = (int)$request->query('filter_etab', 0);
        $filterMode   = (int)$request->query('filter_mode', 0);
        $filterAnnee  = (int)$request->query('filter_annee', 0);
        $filterSpec   = (int)$request->query('filter_spec', 0);

        $isAdmin = in_array($role_code, ['admin', 'ministre', 'secretaire_general', 'central', 'high_admin']);
        $isDfep  = ($role_code === 'dfep' && $dfep_id > 0);
        $isEtab  = (!$isAdmin && !$isDfep && $etab_id > 0);

        // Reference lists for filter options (fast, from ReferenceCache)
        $wilayas = [];
        $etablissements = [];
        if ($isAdmin) {
            $wilayas = \App\Services\ReferenceCache::wilayas();
            if ($filterWilaya > 0) {
                $etablissements = \App\Services\ReferenceCache::etablissementsForDfep($filterWilaya);
            } else {
                $etablissements = \App\Services\ReferenceCache::etablissements();
            }
        } elseif ($isDfep) {
            $etablissements = \App\Services\ReferenceCache::etablissementsForDfep($dfep_id);
        }

        $modes = \App\Services\ReferenceCache::modesFormation();
        $annees = \App\Services\ReferenceCache::anneesFormation();
        $specialites = \App\Services\ReferenceCache::specialites();

        // Calculate dynamic cache key based on role, scope, and filters
        $filtersArray = [
            'filter_wilaya' => $filterWilaya,
            'filter_etab'   => $filterEtab,
            'filter_mode'   => $filterMode,
            'filter_annee'  => $filterAnnee,
            'filter_spec'   => $filterSpec,
        ];
        $cacheKey = 'graduates_stats_' . md5(json_encode([
            'role' => $role_code,
            'iddfep' => $dfep_id,
            'etab_id' => $etab_id,
            'filters' => $filtersArray,
        ]));

        $isAjax = (bool)$request->query('ajax', false);

        // Check if data is in Cache
        $cachedData = Cache::get($cacheKey);

        if ($cachedData) {
            if ($isAjax) {
                return response()->json([
                    'status' => 'ready',
                    'data' => $cachedData
                ]);
            }
            $stats = $cachedData;
        } else {
            // Check if generation is already in progress
            $generatingKey = 'generating_stats_' . $cacheKey;
            $isGenerating = Cache::has($generatingKey);

            if (!$isGenerating) {
                // Set generating flag for 5 minutes
                Cache::put($generatingKey, true, 300);

                // Run background process to compute stats
                $filtersJson = escapeshellarg(json_encode($filtersArray));
                $cmd = "php artisan sgfep:generate-graduates-stats " . escapeshellarg($cacheKey) . " " . escapeshellarg($role_code) . " " . $dfep_id . " " . $etab_id . " " . $filtersJson;
                
                // Launch asynchronously
                if (strncasecmp(PHP_OS, 'WIN', 3) === 0) {
                    if (function_exists('popen')) {
                        @pclose(popen("start /B " . $cmd . " > NUL 2>&1", "r"));
                    }
                } else {
                    $launched = false;
                    foreach (['exec', 'shell_exec', 'system', 'passthru'] as $func) {
                        if (function_exists($func)) {
                            try {
                                @$func($cmd . " > /dev/null 2>&1 &");
                                $launched = true;
                                break;
                            } catch (\Throwable $e) {}
                        }
                    }
                    if (!$launched) {
                        // Fallback: Run synchronously using Artisan to prevent Call to undefined function/disabled function crashes
                        try {
                            \Illuminate\Support\Facades\Artisan::call('sgfep:generate-graduates-stats', [
                                'cacheKey' => $cacheKey,
                                'role' => $role_code,
                                'dfepId' => $dfep_id,
                                'etabId' => $etab_id,
                                'filtersJson' => json_encode($filtersArray),
                            ]);
                        } catch (\Throwable $e) {
                            \Illuminate\Support\Facades\Log::error("Failed to run stats synchronously: " . $e->getMessage());
                        }
                    }
                }
            }

            if ($isAjax) {
                return response()->json([
                    'status' => 'generating'
                ]);
            }

            // Provide default empty arrays so view renders instantly and shows loading state
            $stats = [
                'kpi' => ['total_graduates' => 0, 'issued_diplomas' => 0, 'pending_diplomas' => 0],
                'wilayaStats' => [],
                'yearStats' => [],
                'modeStats' => [],
                'branchStats' => [],
                'etabStats' => [],
                'specStats' => [],
                'is_generating' => true // Signal to view to start polling
            ];
        }

        return $this->render('admin/diplomes/statistiques', [
            'title'          => 'إحصائيات خريجي قطاع التكوين المهني / Statistiques des Diplômés',
            'kpi'            => $stats['kpi'] ?? ['total_graduates' => 0, 'issued_diplomas' => 0, 'pending_diplomas' => 0],
            'wilayaStats'    => $stats['wilayaStats'] ?? [],
            'yearStats'      => $stats['yearStats'] ?? [],
            'modeStats'      => $stats['modeStats'] ?? [],
            'branchStats'    => $stats['branchStats'] ?? [],
            'etabStats'      => $stats['etabStats'] ?? [],
            'specStats'      => $stats['specStats'] ?? [],
            'role_code'      => $role_code,
            'isAdmin'        => $isAdmin,
            'isDfep'         => $isDfep,
            'isEtab'         => $isEtab,
            'filter_wilaya'  => $filterWilaya,
            'filter_etab'    => $filterEtab,
            'filter_mode'    => $filterMode,
            'filter_annee'   => $filterAnnee,
            'filter_spec'    => $filterSpec,
            'wilayas'        => $wilayas,
            'etablissements' => $etablissements,
            'modes'          => $modes,
            'annees'         => $annees,
            'specialites'    => $specialites,
            'is_generating'  => $stats['is_generating'] ?? false
        ]);
    }

    /**
     * Helper: search candidat table by name (Arabic or French)
     */
    private function searchCandidats(bool $isArabic, string $like): array
    {
        if ($isArabic) {
            return DB::select("
                (SELECT IDCandidat FROM candidat WHERE Nom    LIKE ? LIMIT 200)
                UNION
                (SELECT IDCandidat FROM candidat WHERE Prenom LIKE ? LIMIT 200)
                LIMIT 200
            ", [$like, $like]);
        }
        return DB::select("
            (SELECT IDCandidat FROM candidat WHERE NomFr    LIKE ? LIMIT 200)
            UNION
            (SELECT IDCandidat FROM candidat WHERE PrenomFr LIKE ? LIMIT 200)
            LIMIT 200
        ", [$like, $like]);
    }

    /**
     * Generate graduation diploma and issue records
     */
    public function generate(string|int $stagiaire_id): mixed
    {
        try {
            $this->service->generateDiploma((int)$stagiaire_id, session('user'));
            session()->flash('flash_success', 'تم إصدار شهادة التخرج الرسمية بنجاح / Diplôme généré avec succès');
        } catch (Exception $e) {
            session()->flash('flash_error', $e->getMessage());
        }

        return redirect()->to(url('dashboard/diplomes'));
    }

    /**
     * Clean up biographical information from TachesPrincipale to extract clean job titles.
     */
    private function cleanJobTitle(?string $title): string
    {
        if (empty($title)) return '';
        $title = trim($title);
        
        // Clean up lists/bullet points at start
        $title = preg_replace('/^[\s\-•*+]+/u', '', $title);
        
        // If it's already short and clean, return it
        if (mb_strlen($title) <= 55) {
            return $title;
        }
        
        // If it contains "المهام الحالية:" followed by a title
        if (preg_match('/المهام الحالية\s*:\s*([^(\-\n\t.]+)/u', $title, $matches)) {
            return trim($matches[1]);
        }
        
        // Search for common titles
        $keywords = ['مديرة المركز', 'مدير المركز', 'مديرة المعهد', 'مدير المعهد', 'مدير فرعي للتعليم', 'مدير فرعي للتمهين', 'مدير فرعي'];
        foreach ($keywords as $kw) {
            if (mb_stripos($title, $kw) !== false) {
                return $kw;
            }
        }
        
        // Extract first sentence/clause if too long
        if (preg_match('/^([^(\-\n.]+)/u', $title, $matches)) {
            $candidate = trim($matches[1]);
            if (mb_strlen($candidate) < 55) {
                return $candidate;
            }
        }
        
        return 'مدير(ة) المؤسسة';
    }

    /**
     * Print physical official state diploma
     */
    public function printDiploma(string|int $id): mixed
    {
        if (\App\Helpers\SovereignLicensingHelper::getSetting('feature_print_actions_enabled', '1') !== '1') {
            abort(403, 'طباعة الشهادة معطلة من قبل مدير النظام / Printing is disabled by administrator.');
        }

        $id        = (int)$id;
        $user      = session('user');
        $role_code = strtolower($user['role_code'] ?? '');
        $etab_id   = $user['etablissement_id'] ?? null;
        $dfep_id   = $user['iddfep'] ?? null;

        $etabFilter = "";
        $params = [$id];

        if ($role_code === 'dfep' && $dfep_id) {
            $etabFilter = " AND o.IDEts_Form IN (SELECT IDetablissement FROM etablissement WHERE IDDFEP = ?) ";
            $params[] = $dfep_id;
        } elseif (in_array($role_code, ['etablissement', 'directeur', 'formateur']) && $etab_id) {
            $etabFilter = " AND o.IDEts_Form = ? ";
            $params[] = $etab_id;
        }

        $d = DB::selectOne("
            SELECT a.IDapprenant as stagiaire_id, a.Nccp as numero_matricule,
                   c.Nom as nom_ar, c.Prenom as prenom_ar, c.NomFr as nom_fr, c.PrenomFr as prenom_fr,
                   DATE_FORMAT(c.DateNais, '%d/%m/%Y') as date_naissance,
                   c.LieuNais as lieu_naissance, c.LieuNaisFr as lieu_naissance_fr,
                   sp.Nom as spec_ar, sp.NomFr as spec_fr,
                   e.Nom as etab_ar, e.NomFr as etab_fr, e.IDetablissement as etab_id,
                   w.Nom as wilaya_ar, w.NomFr as wilaya_fr,
                    f.IDApprenant_Fin as diplome_id,
                   f.Numdiplome as numero_diplome, f.MoyGen as moyenne_generale,
                   f.DateDiplome as date_deliberation, f.DateDiplome as date_emission,
                   f.DateDiplome as date_delivrance,
                   f.numSerieDiplome as num_serie,
                   q.Nom as type_diplome_ar, q.NomFr as type_diplome_fr,
                   q.IDqualification_dplm as qualif_id,
                   f.NumPvFin as num_deliberation,
                   f.DatePvFin as date_pv_fin,
                   nfp.Nom as niveau_qualification
            FROM apprenant_fin f
            JOIN apprenant a  ON f.IDapprenant     = a.IDapprenant
            JOIN candidat c   ON a.IDCandidat       = c.IDCandidat
            JOIN section s    ON a.IDSection        = s.IDSection
            JOIN offre o      ON s.IDOffre          = o.IDOffre
            JOIN specialite sp ON o.IDSpecialite    = sp.IDSpecialite
            JOIN etablissement e ON o.IDEts_Form    = e.IDetablissement
            LEFT JOIN wilaya w ON e.IDDFEP           = w.IDWilayaa
            LEFT JOIN qualification_dplm q ON COALESCE(NULLIF(o.IDqualification_dplm, 0), NULLIF(sp.IDqualification_dplm, 0)) = q.IDqualification_dplm
            LEFT JOIN niveau_fp nfp ON sp.IDNiveau_Fp = nfp.IDNiveau_Fp
            WHERE f.IDApprenant_Fin = ? {$etabFilter}
        ", $params);

        if (!$d) {
            return response('الشهادة غير موجودة / Document introuvable', 404);
        }

        $d = (array)$d;

        try {
            $qrUrl = url('/verify-diploma?id=' . $d['diplome_id']);
            $d['qr_base64'] = (new \chillerlan\QRCode\QRCode)->render($qrUrl);
        } catch (\Throwable $e) {
            $d['qr_base64'] = '';
        }

        // Custom date formatting for Arabic (YYYY/MM/DD) and French (DD/MM/YYYY)
        if (!empty($d['date_naissance'])) {
            $parts = explode('/', $d['date_naissance']);
            if (count($parts) === 3) {
                $d['date_naissance_ar'] = $parts[2] . '/' . $parts[1] . '/' . $parts[0]; // YYYY/MM/DD
                $d['date_naissance_fr'] = $d['date_naissance']; // DD/MM/YYYY
            } else {
                $d['date_naissance_ar'] = $d['date_naissance'];
                $d['date_naissance_fr'] = $d['date_naissance'];
            }
        } else {
            $d['date_naissance_ar'] = '';
            $d['date_naissance_fr'] = '';
        }

        // Deliberation PV number (fallback to 31 if empty/0)
        $d['num_deliberation'] = (!empty($d['num_deliberation']) && $d['num_deliberation'] != 0) ? $d['num_deliberation'] : '31';

        // Deliberation date: use DatePvFin if present, otherwise fall back to DateDiplome
        $delibDate = (!empty($d['date_pv_fin']) && $d['date_pv_fin'] !== '0000-00-00') ? $d['date_pv_fin'] : ($d['date_deliberation'] ?? '');
        if (!empty($delibDate)) {
            $d['date_deliberation_ar'] = date('Y/m/d', strtotime($delibDate));
        } else {
            $d['date_deliberation_ar'] = '';
        }

        // Emission date
        if (!empty($d['date_emission'])) {
            $d['date_emission_ar'] = date('Y/m/d', strtotime($d['date_emission']));
        } else {
            $d['date_emission_ar'] = '';
        }

        if (empty($d['nom_fr']) && !empty($d['nom_ar'])) {
            $d['nom_fr'] = \App\Helpers\TakwinHelper::transliterateArabic($d['nom_ar']);
        }
        if (empty($d['prenom_fr']) && !empty($d['prenom_ar'])) {
            $d['prenom_fr'] = \App\Helpers\TakwinHelper::transliterateArabic($d['prenom_ar']);
        }
        
        if (!empty($d['lieu_naissance'])) {
            $d['lieu_naissance'] = trim(preg_replace('/\s+/', ' ', $d['lieu_naissance']));
            if (str_contains($d['lieu_naissance'], 'الجزا ئر')) {
                $d['lieu_naissance'] = str_replace('الجزا ئر', 'الجزائر', $d['lieu_naissance']);
            }
        }

        if (!empty($d['wilaya_ar'])) {
            $d['wilaya_ar'] = trim(preg_replace('/\s+/', ' ', $d['wilaya_ar']));
            if (str_contains($d['wilaya_ar'], 'الجزا ئر')) {
                $d['wilaya_ar'] = str_replace('الجزا ئر', 'الجزائر', $d['wilaya_ar']);
            }
        }
        if (empty($d['wilaya_ar']) && !empty($d['etab_ar'])) {
            $detected = \App\Helpers\TakwinHelper::detectWilayaFromEtab($d['etab_ar']);
            $d['wilaya_ar'] = $detected['ar'];
            $d['wilaya_fr'] = $detected['fr'];
        }

        $moyenne = (float)$d['moyenne_generale'];
        $mention = 'passable';
        if ($moyenne >= 16)      $mention = 'tres_bien';
        elseif ($moyenne >= 14)  $mention = 'bien';
        elseif ($moyenne >= 12)  $mention = 'assez_bien';

        $d['mention_label'] = [
            'tres_bien'  => 'جيد جداً / Très Bien',
            'bien'       => 'حسن / Bien',
            'assez_bien' => 'قريب من الحسن / Assez Bien',
            'passable'   => 'مقبول / Passable',
        ][$mention] ?? 'مقبول';

        $d['mention_ar'] = [
            'tres_bien'  => 'جيد جداً',
            'bien'       => 'حسن',
            'assez_bien' => 'قريب من الحسن',
            'passable'   => 'مقبول',
        ][$mention] ?? 'مقبول';

        $qualifId = (int)($d['qualif_id'] ?? 0);
        $d['niveau_qualification'] = !empty($d['niveau_qualification']) ? $d['niveau_qualification'] : match ($qualifId) {
            5, 12, 14 => 'الخامس',
            7, 11, 13 => 'الرابع',
            6         => 'الثالث',
            9         => 'الثاني',
            10        => 'الأول',
            default   => 'الخامس'
        };

        // Signatures from DB
        $etab_id = (int)($d['etab_id'] ?? 0);

        $director = DB::selectOne("
            SELECT Nom, Prenom, TachesPrincipale FROM encadrement
            WHERE IDetablissement = ?
              AND (TachesPrincipale LIKE '%مدير%المعهد%' OR TachesPrincipale LIKE '%مديرة%المعهد%'
                   OR TachesPrincipale LIKE '%مدير%المركز%' OR TachesPrincipale LIKE '%مدير%مؤسسة%'
                   OR TachesPrincipale LIKE '%مدير%')
              AND TachesPrincipale NOT LIKE '%مدير فرعي%'
            LIMIT 1
        ", [$etab_id]);

        if (!$director) {
            $director = DB::selectOne("
                SELECT Nom, Prenom, TachesPrincipale FROM encadrement
                WHERE IDetablissement = ?
                  AND (TachesPrincipale LIKE '%مدير%' OR TachesPrincipale LIKE '%مديرة%')
                  AND TachesPrincipale NOT LIKE '%مدير فرعي%'
                LIMIT 1
            ", [$etab_id]);
        }

        $pedagogical = DB::selectOne("
            SELECT Nom, Prenom, TachesPrincipale FROM encadrement
            WHERE IDetablissement = ?
              AND (TachesPrincipale LIKE '%دراسات%' OR TachesPrincipale LIKE '%بيداغوجي%'
                   OR TachesPrincipale LIKE '%تعليم%' OR TachesPrincipale LIKE '%تمهين%')
              AND TachesPrincipale LIKE '%مدير فرعي%'
            LIMIT 1
        ", [$etab_id]);

        if (!$pedagogical) {
            $pedagogical = DB::selectOne("
                SELECT Nom, Prenom, TachesPrincipale FROM encadrement
                WHERE IDetablissement = ? AND TachesPrincipale LIKE '%مدير فرعي%'
                LIMIT 1
            ", [$etab_id]);
        }

        if (!$director) {
            $director = DB::selectOne("
                SELECT Nom, Prenom, TachesPrincipale FROM encadrement
                WHERE (Nom LIKE '%ولدسعيد%' OR Nom LIKE '%ولد سعيد%') AND Prenom LIKE '%فتيحة%'
                LIMIT 1
            ");
        }
        if (!$pedagogical) {
            $pedagogical = DB::selectOne("
                SELECT Nom, Prenom, TachesPrincipale FROM encadrement
                WHERE Nom LIKE '%زقنون%' AND Prenom LIKE '%عمر%'
                LIMIT 1
            ");
        }

        $d['director_name']    = $director    ? ($director->Prenom    . ' ' . $director->Nom)    : 'ولد سعيد فتيحة';
        $d['director_title']   = ($director && !empty($director->TachesPrincipale)) ? ($this->cleanJobTitle($director->TachesPrincipale) ?: 'مديرة المعهد') : 'مديرة المعهد';
        $d['pedagogical_name'] = $pedagogical ? ($pedagogical->Prenom . ' ' . $pedagogical->Nom) : 'زقنون عمر';
        $d['pedagogical_title']= ($pedagogical && !empty($pedagogical->TachesPrincipale)) ? ($this->cleanJobTitle($pedagogical->TachesPrincipale) ?: 'مدير فرعي للتعليم والتوجيه المهني المتواصل') : 'مدير فرعي للتعليم والتوجيه المهني المتواصل';

        return $this->render('admin/diplomes/print', ['d' => $d], 'print');
    }

    /**
     * Bulk-print all diplomas for a cohort (section / offre / custom IDs list).
     * URL params:
     *   ids        — comma-separated apprenant_fin IDs  (highest priority)
     *   section_id — print all issued diplomas of a section
     *   offre_id   — print all issued diplomas of an offre
     * At most 200 diplomas per batch to prevent browser freeze.
     */
    public function printBatch(\Illuminate\Http\Request $request): mixed
    {
        if (\App\Helpers\SovereignLicensingHelper::getSetting('feature_print_actions_enabled', '1') !== '1') {
            abort(403, 'طباعة الشهادة معطلة من قبل مدير النظام.');
        }

        $user      = session('user') ?? [];
        $role_code = strtolower($user['role_code'] ?? '');
        $etab_id   = (int)($user['etablissement_id'] ?? $user['IDEts_Form'] ?? 0);
        $dfep_id   = (int)($user['iddfep'] ?? $user['IDDFEP'] ?? 0);

        $isAdmin = in_array($role_code, ['admin', 'ministre', 'secretaire_general', 'central', 'high_admin']);
        $isDfep  = ($role_code === 'dfep' && $dfep_id > 0);
        $isEtab  = (!$isAdmin && !$isDfep && $etab_id > 0);

        // ── Scope filter ──────────────────────────────────────────────
        $scopeFilter = '';
        $scopeParams = [];
        if ($isDfep) {
            $scopeFilter = " AND o.IDEts_Form IN (SELECT IDetablissement FROM etablissement WHERE IDDFEP = ?)";
            $scopeParams[] = $dfep_id;
        } elseif ($isEtab) {
            $scopeFilter = " AND o.IDEts_Form = ?";
            $scopeParams[] = $etab_id;
        } elseif (!$isAdmin) {
            abort(403);
        }

        // ── Build WHERE for the selection mode ───────────────────────
        $idsRaw    = trim($request->query('ids', ''));
        $sectionId = (int)$request->query('section_id', 0);
        $offreId   = (int)$request->query('offre_id', 0);

        $selectionWhere  = '';
        $selectionParams = [];

        if ($idsRaw !== '') {
            // Custom ID list (apprenant_fin IDs)
            $idList = array_filter(array_map('intval', explode(',', $idsRaw)));
            if (empty($idList)) abort(400, 'IDs invalides.');
            $idList = array_slice($idList, 0, 200); // cap at 200
            $ph = implode(',', array_fill(0, count($idList), '?'));
            $selectionWhere  = " AND f.IDApprenant_Fin IN ($ph)";
            $selectionParams = $idList;
        } elseif ($sectionId > 0) {
            $selectionWhere  = " AND a.IDSection = ?";
            $selectionParams = [$sectionId];
        } elseif ($offreId > 0) {
            $selectionWhere  = " AND s.IDOffre = ?";
            $selectionParams = [$offreId];
        } else {
            abort(400, 'يجب تحديد ids أو section_id أو offre_id.');
        }

        $rows = DB::select("
            SELECT a.IDapprenant as stagiaire_id, a.Nccp as numero_matricule,
                   c.Nom as nom_ar, c.Prenom as prenom_ar, c.NomFr as nom_fr, c.PrenomFr as prenom_fr,
                   DATE_FORMAT(c.DateNais, '%d/%m/%Y') as date_naissance,
                   c.LieuNais as lieu_naissance, c.LieuNaisFr as lieu_naissance_fr,
                   sp.Nom as spec_ar, sp.NomFr as spec_fr,
                   e.Nom as etab_ar, e.NomFr as etab_fr, e.IDetablissement as etab_id,
                   w.Nom as wilaya_ar, w.NomFr as wilaya_fr,
                   f.IDApprenant_Fin as diplome_id,
                   f.Numdiplome as numero_diplome, f.MoyGen as moyenne_generale,
                   f.DateDiplome as date_deliberation, f.DateDiplome as date_emission,
                   f.numSerieDiplome as num_serie,
                   q.Nom as type_diplome_ar, q.NomFr as type_diplome_fr,
                   q.IDqualification_dplm as qualif_id,
                   f.NumPvFin as num_deliberation, f.DatePvFin as date_pv_fin,
                   nfp.Nom as niveau_qualification
            FROM apprenant_fin f
            JOIN apprenant a   ON f.IDapprenant  = a.IDapprenant
            JOIN candidat c    ON a.IDCandidat   = c.IDCandidat
            JOIN section s     ON a.IDSection    = s.IDSection
            JOIN offre o       ON s.IDOffre      = o.IDOffre
            JOIN specialite sp ON o.IDSpecialite = sp.IDSpecialite
            JOIN etablissement e ON o.IDEts_Form = e.IDetablissement
            LEFT JOIN wilaya w ON e.IDDFEP = w.IDWilayaa
            LEFT JOIN qualification_dplm q ON COALESCE(NULLIF(o.IDqualification_dplm,0), NULLIF(sp.IDqualification_dplm,0)) = q.IDqualification_dplm
            LEFT JOIN niveau_fp nfp ON sp.IDNiveau_Fp = nfp.IDNiveau_Fp
            WHERE f.Numdiplome IS NOT NULL AND f.Numdiplome != ''
              {$scopeFilter}
              {$selectionWhere}
            ORDER BY a.IDSection, c.Nom, c.Prenom
            LIMIT 200
        ", array_merge($scopeParams, $selectionParams));

        if (empty($rows)) {
            return back()->with('flash_error', 'لا توجد شهادات محررة لهذه المجموعة.');
        }

        // ── Format each diploma record (same logic as printDiploma) ──
        $diplomas = [];
        foreach ($rows as $row) {
            $d = (array)$row;

            try {
                $qrUrl = url('/verify-diploma?id=' . $d['diplome_id']);
                $d['qr_base64'] = (new \chillerlan\QRCode\QRCode)->render($qrUrl);
            } catch (\Throwable $e) {
                $d['qr_base64'] = '';
            }

            // Date formatting
            if (!empty($d['date_naissance'])) {
                $parts = explode('/', $d['date_naissance']);
                if (count($parts) === 3) {
                    $d['date_naissance_ar'] = $parts[2] . '/' . $parts[1] . '/' . $parts[0];
                    $d['date_naissance_fr'] = $d['date_naissance'];
                } else {
                    $d['date_naissance_ar'] = $d['date_naissance'];
                    $d['date_naissance_fr'] = $d['date_naissance'];
                }
            } else {
                $d['date_naissance_ar'] = '';
                $d['date_naissance_fr'] = '';
            }

            $d['num_deliberation'] = (!empty($d['num_deliberation']) && $d['num_deliberation'] != 0) ? $d['num_deliberation'] : '31';

            $delibDate = (!empty($d['date_pv_fin']) && $d['date_pv_fin'] !== '0000-00-00') ? $d['date_pv_fin'] : ($d['date_deliberation'] ?? '');
            $d['date_deliberation_ar'] = !empty($delibDate) ? date('Y/m/d', strtotime($delibDate)) : '';
            $d['date_emission_ar']     = !empty($d['date_emission']) ? date('Y/m/d', strtotime($d['date_emission'])) : '';

            if (empty($d['nom_fr']) && !empty($d['nom_ar'])) {
                $d['nom_fr'] = \App\Helpers\TakwinHelper::transliterateArabic($d['nom_ar']);
            }
            if (empty($d['prenom_fr']) && !empty($d['prenom_ar'])) {
                $d['prenom_fr'] = \App\Helpers\TakwinHelper::transliterateArabic($d['prenom_ar']);
            }

            if (!empty($d['lieu_naissance'])) {
                $d['lieu_naissance'] = trim(preg_replace('/\s+/', ' ', $d['lieu_naissance']));
                $d['lieu_naissance'] = str_replace('الجزا ئر', 'الجزائر', $d['lieu_naissance']);
            }
            if (!empty($d['wilaya_ar'])) {
                $d['wilaya_ar'] = trim(preg_replace('/\s+/', ' ', $d['wilaya_ar']));
                $d['wilaya_ar'] = str_replace('الجزا ئر', 'الجزائر', $d['wilaya_ar']);
            }
            if (empty($d['wilaya_ar']) && !empty($d['etab_ar'])) {
                $detected = \App\Helpers\TakwinHelper::detectWilayaFromEtab($d['etab_ar']);
                $d['wilaya_ar'] = $detected['ar'];
                $d['wilaya_fr'] = $detected['fr'];
            }

            $moyenne = (float)$d['moyenne_generale'];
            $mention = 'passable';
            if ($moyenne >= 16)     $mention = 'tres_bien';
            elseif ($moyenne >= 14) $mention = 'bien';
            elseif ($moyenne >= 12) $mention = 'assez_bien';

            $d['mention_ar'] = [
                'tres_bien'  => 'جيد جداً',
                'bien'       => 'حسن',
                'assez_bien' => 'قريب من الحسن',
                'passable'   => 'مقبول',
            ][$mention] ?? 'مقبول';

            $qualifId = (int)($d['qualif_id'] ?? 0);
            $d['niveau_qualification'] = !empty($d['niveau_qualification']) ? $d['niveau_qualification'] : match ($qualifId) {
                5, 12, 14 => 'الخامس',
                7, 11, 13 => 'الرابع',
                6         => 'الثالث',
                9         => 'الثاني',
                10        => 'الأول',
                default   => 'الخامس'
            };

            // Signatures (one DB query per unique etablissement, cached in-process)
            static $sigCache = [];
            $eid = (int)($d['etab_id'] ?? 0);
            if (!isset($sigCache[$eid])) {
                $director = DB::selectOne("
                    SELECT Nom, Prenom, TachesPrincipale FROM encadrement
                    WHERE IDetablissement = ?
                      AND (TachesPrincipale LIKE '%مدير%المعهد%' OR TachesPrincipale LIKE '%مديرة%المعهد%'
                           OR TachesPrincipale LIKE '%مدير%المركز%' OR TachesPrincipale LIKE '%مدير%مؤسسة%'
                           OR TachesPrincipale LIKE '%مدير%')
                      AND TachesPrincipale NOT LIKE '%مدير فرعي%'
                    LIMIT 1
                ", [$eid]);
                $pedagogical = DB::selectOne("
                    SELECT Nom, Prenom, TachesPrincipale FROM encadrement
                    WHERE IDetablissement = ?
                      AND (TachesPrincipale LIKE '%دراسات%' OR TachesPrincipale LIKE '%بيداغوجي%'
                           OR TachesPrincipale LIKE '%تعليم%' OR TachesPrincipale LIKE '%تمهين%')
                      AND TachesPrincipale LIKE '%مدير فرعي%'
                    LIMIT 1
                ", [$eid]);
                $sigCache[$eid] = [
                    'director_name'     => $director    ? ($director->Prenom    . ' ' . $director->Nom)    : 'ولد سعيد فتيحة',
                    'director_title'    => ($director && !empty($director->TachesPrincipale)) ? ($this->cleanJobTitle($director->TachesPrincipale) ?: 'مديرة المعهد') : 'مديرة المعهد',
                    'pedagogical_name'  => $pedagogical ? ($pedagogical->Prenom . ' ' . $pedagogical->Nom) : 'زقنون عمر',
                    'pedagogical_title' => ($pedagogical && !empty($pedagogical->TachesPrincipale)) ? ($this->cleanJobTitle($pedagogical->TachesPrincipale) ?: 'مدير فرعي للتعليم') : 'مدير فرعي للتعليم والتوجيه المهني المتواصل',
                ];
            }
            $d = array_merge($d, $sigCache[$eid]);
            $diplomas[] = $d;
        }

        $settings = \App\Helpers\TakwinHelper::getSettings();
        return $this->render('admin/diplomes/print_batch', [
            'diplomas' => $diplomas,
            'count'    => count($diplomas),
            'title'    => 'طباعة جماعية — ' . count($diplomas) . ' شهادة',
        ], 'print');
    }

    /**
     * Fetch issued diploma details for Edit Modal (AJAX)
     */
    public function show(string|int $id): mixed
    {
        $id = (int)$id;
        $user      = session('user') ?? [];
        $role_code = strtolower($user['role_code'] ?? '');
        $etab_id   = (int)($user['etablissement_id'] ?? $user['IDEts_Form'] ?? 0);
        $dfep_id   = (int)($user['iddfep'] ?? $user['IDDFEP'] ?? 0);

        $isAdmin = in_array($role_code, ['admin', 'ministre', 'secretaire_general', 'central', 'high_admin']);
        $isDfep  = ($role_code === 'dfep' && $dfep_id > 0);
        $isEtab  = (!$isAdmin && !$isDfep && $etab_id > 0);

        $where = ["f.IDApprenant_Fin = ?"];
        $params = [$id];

        if ($isDfep) {
            $where[] = "o.IDEts_Form IN (SELECT IDetablissement FROM etablissement WHERE IDDFEP = ?)";
            $params[] = $dfep_id;
        } elseif ($isEtab) {
            $where[] = "o.IDEts_Form = ?";
            $params[] = $etab_id;
        } elseif (!$isAdmin) {
            return response()->json(['error' => 'Non autorisé'], 403);
        }

        $whereSQL = 'WHERE ' . implode(' AND ', $where);

        $d = DB::selectOne("
            SELECT f.IDApprenant_Fin as id,
                   f.Numdiplome as numero_diplome,
                   f.numSerieDiplome as num_serie,
                   f.MoyGen as moyenne_generale,
                   f.DateDiplome as date_diplome,
                   f.NumPvFin as num_pv_fin,
                   f.DatePvFin as date_pv_fin,
                   c.Nom as nom_ar, c.Prenom as prenom_ar,
                   c.NomFr as nom_fr, c.PrenomFr as prenom_fr
            FROM apprenant_fin f
            JOIN apprenant a  ON f.IDapprenant = a.IDapprenant
            JOIN candidat c   ON a.IDCandidat = c.IDCandidat
            JOIN section s    ON a.IDSection = s.IDSection
            JOIN offre o      ON s.IDOffre = o.IDOffre
            {$whereSQL}
            LIMIT 1
        ", $params);

        if (!$d) {
            return response()->json(['error' => 'Record not found or access denied'], 404);
        }

        return response()->json($d);
    }

    /**
     * Update issued diploma record
     */
    public function update(\Illuminate\Http\Request $request): mixed
    {
        $id = (int)$request->input('id');
        if (!$id) {
            return redirect()->back()->with('flash_error', 'معرف السجل غير صالح / ID invalide');
        }

        $user      = session('user') ?? [];
        $role_code = strtolower($user['role_code'] ?? '');
        $etab_id   = (int)($user['etablissement_id'] ?? $user['IDEts_Form'] ?? 0);
        $dfep_id   = (int)($user['iddfep'] ?? $user['IDDFEP'] ?? 0);

        $isAdmin = in_array($role_code, ['admin', 'ministre', 'secretaire_general', 'central', 'high_admin']);
        $isDfep  = ($role_code === 'dfep' && $dfep_id > 0);
        $isEtab  = (!$isAdmin && !$isDfep && $etab_id > 0);

        // Security / Scope check
        $where = ["f.IDApprenant_Fin = ?"];
        $params = [$id];

        if ($isDfep) {
            $where[] = "o.IDEts_Form IN (SELECT IDetablissement FROM etablissement WHERE IDDFEP = ?)";
            $params[] = $dfep_id;
        } elseif ($isEtab) {
            $where[] = "o.IDEts_Form = ?";
            $params[] = $etab_id;
        } elseif (!$isAdmin) {
            return redirect()->back()->with('flash_error', 'غير مسموح لك بإجراء هذه العملية / Action non autorisée');
        }

        $whereSQL = 'WHERE ' . implode(' AND ', $where);

        $exists = DB::selectOne("
            SELECT f.IDApprenant_Fin
            FROM apprenant_fin f
            JOIN apprenant a  ON f.IDapprenant = a.IDapprenant
            JOIN section s    ON a.IDSection = s.IDSection
            JOIN offre o      ON s.IDOffre = o.IDOffre
            {$whereSQL}
            LIMIT 1
        ", $params);

        if (!$exists) {
            return redirect()->back()->with('flash_error', 'السجل غير موجود أو لا تملك صلاحية تعديله / Introuvable ou non autorisé');
        }

        // Validate
        $request->validate([
            'numero_diplome'   => 'required|string|max:20',
            'num_serie'        => 'required|string|max:15',
            'moyenne_generale' => 'required|numeric|min:0|max:20',
            'date_diplome'     => 'required|date',
            'num_pv_fin'       => 'nullable|integer',
            'date_pv_fin'      => 'nullable|date',
        ]);

        try {
            DB::transaction(function () use ($request, $id) {
                DB::update("
                    UPDATE apprenant_fin
                    SET Numdiplome = ?,
                        numSerieDiplome = ?,
                        MoyGen = ?,
                        DateDiplome = ?,
                        NumPvFin = ?,
                        DatePvFin = ?,
                        update_time = ?
                    WHERE IDApprenant_Fin = ?
                ", [
                    $request->input('numero_diplome'),
                    $request->input('num_serie'),
                    (double)$request->input('moyenne_generale'),
                    $request->input('date_diplome'),
                    $request->input('num_pv_fin') ? (int)$request->input('num_pv_fin') : 0,
                    $request->input('date_pv_fin') ?: null,
                    date('Y-m-d'),
                    $id
                ]);
            });

            // Clear cache for count
            Cache::forget('dip_issued_v3');

            return redirect()->back()->with('flash_success', 'تم تحديث بيانات الشهادة بنجاح / Données du diplôme mises à jour');
        } catch (Exception $e) {
            return redirect()->back()->with('flash_error', 'حدث خطأ أثناء التحديث / Erreur de mise à jour: ' . $e->getMessage());
        }
    }

    /**
     * Delete / Annul an issued diploma record
     */
    public function destroy(string|int $id): mixed
    {
        $id = (int)$id;
        $user      = session('user') ?? [];
        $role_code = strtolower($user['role_code'] ?? '');
        $etab_id   = (int)($user['etablissement_id'] ?? $user['IDEts_Form'] ?? 0);
        $dfep_id   = (int)($user['iddfep'] ?? $user['IDDFEP'] ?? 0);

        $isAdmin = in_array($role_code, ['admin', 'ministre', 'secretaire_general', 'central', 'high_admin']);
        $isDfep  = ($role_code === 'dfep' && $dfep_id > 0);
        $isEtab  = (!$isAdmin && !$isDfep && $etab_id > 0);

        // Security / Scope check
        $where = ["f.IDApprenant_Fin = ?"];
        $params = [$id];

        if ($isDfep) {
            $where[] = "o.IDEts_Form IN (SELECT IDetablissement FROM etablissement WHERE IDDFEP = ?)";
            $params[] = $dfep_id;
        } elseif ($isEtab) {
            $where[] = "o.IDEts_Form = ?";
            $params[] = $etab_id;
        } elseif (!$isAdmin) {
            return redirect()->back()->with('flash_error', 'غير مسموح لك بإجراء هذه العملية / Action non autorisée');
        }

        $whereSQL = 'WHERE ' . implode(' AND ', $where);

        $exists = DB::selectOne("
            SELECT f.IDApprenant_Fin
            FROM apprenant_fin f
            JOIN apprenant a  ON f.IDapprenant = a.IDapprenant
            JOIN section s    ON a.IDSection = s.IDSection
            JOIN offre o      ON s.IDOffre = o.IDOffre
            {$whereSQL}
            LIMIT 1
        ", $params);

        if (!$exists) {
            return redirect()->back()->with('flash_error', 'السجل غير موجود أو لا تملك صلاحية حذفه / Introuvable ou non autorisé');
        }

        try {
            DB::transaction(function () use ($id) {
                DB::delete("DELETE FROM apprenant_fin WHERE IDApprenant_Fin = ?", [$id]);
            });

            return redirect()->back()->with('flash_success', 'تم إلغاء الشهادة وإرجاع الحالة بنجاح / Certificat annulé avec succès');
        } catch (Exception $e) {
            return redirect()->back()->with('flash_error', 'حدث خطأ أثناء الإلغاء / Erreur lors de l\'annulation: ' . $e->getMessage());
        }
    }

    /**
     * Download diploma as PDF.
     */
    public function downloadPdf(string|int $id): mixed
    {
        if (\App\Helpers\SovereignLicensingHelper::getSetting('feature_print_actions_enabled', '1') !== '1') {
            abort(403);
        }

        $id        = (int)$id;
        $user      = session('user');
        $role_code = strtolower($user['role_code'] ?? '');
        $etab_id   = $user['etablissement_id'] ?? null;
        $dfep_id   = $user['iddfep'] ?? null;

        $etabFilter = "";
        $params = [$id];

        if ($role_code === 'dfep' && $dfep_id) {
            $etabFilter = " AND o.IDEts_Form IN (SELECT IDetablissement FROM etablissement WHERE IDDFEP = ?) ";
            $params[] = $dfep_id;
        } elseif (in_array($role_code, ['etablissement', 'directeur', 'formateur']) && $etab_id) {
            $etabFilter = " AND o.IDEts_Form = ? ";
            $params[] = $etab_id;
        }

        $d = DB::selectOne("
            SELECT a.IDapprenant as stagiaire_id, a.Nccp as numero_matricule,
                   c.Nom as nom_ar, c.Prenom as prenom_ar, c.NomFr as nom_fr, c.PrenomFr as prenom_fr,
                   DATE_FORMAT(c.DateNais, '%d/%m/%Y') as date_naissance,
                   c.LieuNais as lieu_naissance, c.LieuNaisFr as lieu_naissance_fr,
                   sp.Nom as spec_ar, sp.NomFr as spec_fr,
                   e.Nom as etab_ar, e.NomFr as etab_fr, e.IDetablissement as etab_id,
                   w.Nom as wilaya_ar, w.NomFr as wilaya_fr,
                   f.IDApprenant_Fin as diplome_id,
                   f.Numdiplome as numero_diplome, f.MoyGen as moyenne_generale,
                   f.DateDiplome as date_deliberation, f.DateDiplome as date_emission,
                   f.DateDiplome as date_delivrance,
                   f.numSerieDiplome as num_serie,
                   q.Nom as type_diplome_ar, q.NomFr as type_diplome_fr,
                   q.IDqualification_dplm as qualif_id,
                   f.NumPvFin as num_deliberation,
                   f.DatePvFin as date_pv_fin,
                   nfp.Nom as niveau_qualification
            FROM apprenant_fin f
            JOIN apprenant a  ON f.IDapprenant     = a.IDapprenant
            JOIN candidat c   ON a.IDCandidat       = c.IDCandidat
            JOIN section s    ON a.IDSection        = s.IDSection
            JOIN offre o      ON s.IDOffre          = o.IDOffre
            JOIN specialite sp ON o.IDSpecialite    = sp.IDSpecialite
            JOIN etablissement e ON o.IDEts_Form    = e.IDetablissement
            LEFT JOIN wilaya w ON e.IDDFEP           = w.IDWilayaa
            LEFT JOIN qualification_dplm q ON COALESCE(NULLIF(o.IDqualification_dplm, 0), NULLIF(sp.IDqualification_dplm, 0)) = q.IDqualification_dplm
            LEFT JOIN niveau_fp nfp ON sp.IDNiveau_Fp = nfp.IDNiveau_Fp
            WHERE f.IDApprenant_Fin = ? {$etabFilter}
        ", $params);

        if (!$d) {
            return response('الشهادة غير موجودة', 404);
        }

        $d = (array)$d;

        // Custom formatting (same as printDiploma)
        try {
            $qrUrl = url('/verify-diploma?id=' . $d['diplome_id']);
            $d['qr_base64'] = (new \chillerlan\QRCode\QRCode)->render($qrUrl);
        } catch (\Throwable $e) {
            $d['qr_base64'] = '';
        }

        if (!empty($d['date_naissance'])) {
            $parts = explode('/', $d['date_naissance']);
            if (count($parts) === 3) {
                $d['date_naissance_ar'] = $parts[2] . '/' . $parts[1] . '/' . $parts[0];
                $d['date_naissance_fr'] = $d['date_naissance'];
            }
        }
        $d['num_deliberation'] = (!empty($d['num_deliberation']) && $d['num_deliberation'] != 0) ? $d['num_deliberation'] : '31';
        $delibDate = (!empty($d['date_pv_fin']) && $d['date_pv_fin'] !== '0000-00-00') ? $d['date_pv_fin'] : ($d['date_deliberation'] ?? '');
        $d['date_deliberation_ar'] = !empty($delibDate) ? date('Y/m/d', strtotime($delibDate)) : '';
        $d['date_emission_ar'] = !empty($d['date_emission']) ? date('Y/m/d', strtotime($d['date_emission'])) : '';

        if (empty($d['nom_fr']) && !empty($d['nom_ar'])) {
            $d['nom_fr'] = \App\Helpers\TakwinHelper::transliterateArabic($d['nom_ar']);
        }
        if (empty($d['prenom_fr']) && !empty($d['prenom_ar'])) {
            $d['prenom_fr'] = \App\Helpers\TakwinHelper::transliterateArabic($d['prenom_ar']);
        }
        if (!empty($d['lieu_naissance'])) {
            $d['lieu_naissance'] = trim(preg_replace('/\s+/', ' ', $d['lieu_naissance']));
            $d['lieu_naissance'] = str_replace('الجزا ئر', 'الجزائر', $d['lieu_naissance']);
        }
        if (!empty($d['wilaya_ar'])) {
            $d['wilaya_ar'] = trim(preg_replace('/\s+/', ' ', $d['wilaya_ar']));
            $d['wilaya_ar'] = str_replace('الجزا ئر', 'الجزائر', $d['wilaya_ar']);
        }
        if (empty($d['wilaya_ar']) && !empty($d['etab_ar'])) {
            $detected = \App\Helpers\TakwinHelper::detectWilayaFromEtab($d['etab_ar']);
            $d['wilaya_ar'] = $detected['ar'];
            $d['wilaya_fr'] = $detected['fr'];
        }

        $moyenne = (float)$d['moyenne_generale'];
        $mention = 'passable';
        if ($moyenne >= 16)      $mention = 'tres_bien';
        elseif ($moyenne >= 14)  $mention = 'bien';
        elseif ($moyenne >= 12)  $mention = 'assez_bien';

        $d['mention_ar'] = [
            'tres_bien'  => 'جيد جداً',
            'bien'       => 'حسن',
            'assez_bien' => 'قريب من الحسن',
            'passable'   => 'مقبول',
        ][$mention] ?? 'مقبول';

        $qualifId = (int)($d['qualif_id'] ?? 0);
        $d['niveau_qualification'] = !empty($d['niveau_qualification']) ? $d['niveau_qualification'] : match ($qualifId) {
            5, 12, 14 => 'الخامس',
            7, 11, 13 => 'الرابع',
            6         => 'الثالث',
            9         => 'الثاني',
            10        => 'الأول',
            default   => 'الخامس'
        };

        $background = request()->query('background', '1') === '1';

        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4-L',
            'autoScriptToLang' => true,
            'autoLangToFont' => true,
            'margin_top' => 0,
            'margin_bottom' => 0,
            'margin_left' => 0,
            'margin_right' => 0
        ]);
        $mpdf->SetDirectionality('rtl');

        $html = view('admin.diplomes.pdf', [
            'diplomas' => [$d],
            'background' => $background,
            'settings' => \App\Helpers\TakwinHelper::getSettings()
        ])->render();

        $mpdf->WriteHTML($html);
        return response($mpdf->Output('diplome_' . $d['numero_matricule'] . '.pdf', \Mpdf\Output\Destination::DOWNLOAD))
            ->header('Content-Type', 'application/pdf');
    }

    /**
     * Download batch of diplomas as a single PDF.
     */
    public function downloadPdfBatch(\Illuminate\Http\Request $request): mixed
    {
        if (\App\Helpers\SovereignLicensingHelper::getSetting('feature_print_actions_enabled', '1') !== '1') {
            abort(403);
        }

        $user      = session('user') ?? [];
        $role_code = strtolower($user['role_code'] ?? '');
        $etab_id   = (int)($user['etablissement_id'] ?? $user['IDEts_Form'] ?? 0);
        $dfep_id   = (int)($user['iddfep'] ?? $user['IDDFEP'] ?? 0);

        $isAdmin = in_array($role_code, ['admin', 'ministre', 'secretaire_general', 'central', 'high_admin']);
        $isDfep  = ($role_code === 'dfep' && $dfep_id > 0);
        $isEtab  = (!$isAdmin && !$isDfep && $etab_id > 0);

        $scopeFilter = '';
        $scopeParams = [];
        if ($isDfep) {
            $scopeFilter = " AND o.IDEts_Form IN (SELECT IDetablissement FROM etablissement WHERE IDDFEP = ?)";
            $scopeParams[] = $dfep_id;
        } elseif ($isEtab) {
            $scopeFilter = " AND o.IDEts_Form = ?";
            $scopeParams[] = $etab_id;
        } elseif (!$isAdmin) {
            abort(403);
        }

        $idsRaw    = trim($request->query('ids', ''));
        $sectionId = (int)$request->query('section_id', 0);
        $offreId   = (int)$request->query('offre_id', 0);

        $selectionWhere  = '';
        $selectionParams = [];

        if ($idsRaw !== '') {
            $idList = array_filter(array_map('intval', explode(',', $idsRaw)));
            if (empty($idList)) abort(400, 'IDs invalides.');
            $idList = array_slice($idList, 0, 100); // cap at 100 for PDF to avoid timeout
            $ph = implode(',', array_fill(0, count($idList), '?'));
            $selectionWhere  = " AND f.IDApprenant_Fin IN ($ph)";
            $selectionParams = $idList;
        } elseif ($sectionId > 0) {
            $selectionWhere  = " AND a.IDSection = ?";
            $selectionParams = [$sectionId];
        } elseif ($offreId > 0) {
            $selectionWhere  = " AND s.IDOffre = ?";
            $selectionParams = [$offreId];
        } else {
            abort(400, 'Paramètres manquants.');
        }

        $rows = DB::select("
            SELECT a.IDapprenant as stagiaire_id, a.Nccp as numero_matricule,
                   c.Nom as nom_ar, c.Prenom as prenom_ar, c.NomFr as nom_fr, c.PrenomFr as prenom_fr,
                   DATE_FORMAT(c.DateNais, '%d/%m/%Y') as date_naissance,
                   c.LieuNais as lieu_naissance, c.LieuNaisFr as lieu_naissance_fr,
                   sp.Nom as spec_ar, sp.NomFr as spec_fr,
                   e.Nom as etab_ar, e.NomFr as etab_fr, e.IDetablissement as etab_id,
                   w.Nom as wilaya_ar, w.NomFr as wilaya_fr,
                   f.IDApprenant_Fin as diplome_id,
                   f.Numdiplome as numero_diplome, f.MoyGen as moyenne_generale,
                   f.DateDiplome as date_deliberation, f.DateDiplome as date_emission,
                   f.numSerieDiplome as num_serie,
                   q.Nom as type_diplome_ar, q.NomFr as type_diplome_fr,
                   q.IDqualification_dplm as qualif_id,
                   f.NumPvFin as num_deliberation, f.DatePvFin as date_pv_fin,
                   nfp.Nom as niveau_qualification
            FROM apprenant_fin f
            JOIN apprenant a   ON f.IDapprenant  = a.IDapprenant
            JOIN candidat c    ON a.IDCandidat   = c.IDCandidat
            JOIN section s     ON a.IDSection    = s.IDSection
            JOIN offre o       ON s.IDOffre      = o.IDOffre
            JOIN specialite sp ON o.IDSpecialite = sp.IDSpecialite
            JOIN etablissement e ON o.IDEts_Form = e.IDetablissement
            LEFT JOIN wilaya w ON e.IDDFEP = w.IDWilayaa
            LEFT JOIN qualification_dplm q ON COALESCE(NULLIF(o.IDqualification_dplm,0), NULLIF(sp.IDqualification_dplm,0)) = q.IDqualification_dplm
            LEFT JOIN niveau_fp nfp ON sp.IDNiveau_Fp = nfp.IDNiveau_Fp
            WHERE f.Numdiplome IS NOT NULL AND f.Numdiplome != ''
              {$scopeFilter}
              {$selectionWhere}
            ORDER BY a.IDSection, c.Nom, c.Prenom
            LIMIT 100
        ", array_merge($scopeParams, $selectionParams));

        if (empty($rows)) {
            return back()->with('flash_error', 'لا توجد شهادات محررة لهذه المجموعة.');
        }

        $diplomas = [];
        foreach ($rows as $row) {
            $d = (array)$row;

            try {
                $qrUrl = url('/verify-diploma?id=' . $d['diplome_id']);
                $d['qr_base64'] = (new \chillerlan\QRCode\QRCode)->render($qrUrl);
            } catch (\Throwable $e) {
                $d['qr_base64'] = '';
            }

            if (!empty($d['date_naissance'])) {
                $parts = explode('/', $d['date_naissance']);
                if (count($parts) === 3) {
                    $d['date_naissance_ar'] = $parts[2] . '/' . $parts[1] . '/' . $parts[0];
                    $d['date_naissance_fr'] = $d['date_naissance'];
                }
            }
            $d['num_deliberation'] = (!empty($d['num_deliberation']) && $d['num_deliberation'] != 0) ? $d['num_deliberation'] : '31';
            $delibDate = (!empty($d['date_pv_fin']) && $d['date_pv_fin'] !== '0000-00-00') ? $d['date_pv_fin'] : ($d['date_deliberation'] ?? '');
            $d['date_deliberation_ar'] = !empty($delibDate) ? date('Y/m/d', strtotime($delibDate)) : '';
            $d['date_emission_ar'] = !empty($d['date_emission']) ? date('Y/m/d', strtotime($d['date_emission'])) : '';

            if (empty($d['nom_fr']) && !empty($d['nom_ar'])) {
                $d['nom_fr'] = \App\Helpers\TakwinHelper::transliterateArabic($d['nom_ar']);
            }
            if (empty($d['prenom_fr']) && !empty($d['prenom_ar'])) {
                $d['prenom_fr'] = \App\Helpers\TakwinHelper::transliterateArabic($d['prenom_ar']);
            }
            if (!empty($d['lieu_naissance'])) {
                $d['lieu_naissance'] = trim(preg_replace('/\s+/', ' ', $d['lieu_naissance']));
                $d['lieu_naissance'] = str_replace('الجزا ئر', 'الجزائر', $d['lieu_naissance']);
            }
            if (!empty($d['wilaya_ar'])) {
                $d['wilaya_ar'] = trim(preg_replace('/\s+/', ' ', $d['wilaya_ar']));
                $d['wilaya_ar'] = str_replace('الجزا ئر', 'الجزائر', $d['wilaya_ar']);
            }
            if (empty($d['wilaya_ar']) && !empty($d['etab_ar'])) {
                $detected = \App\Helpers\TakwinHelper::detectWilayaFromEtab($d['etab_ar']);
                $d['wilaya_ar'] = $detected['ar'];
                $d['wilaya_fr'] = $detected['fr'];
            }

            $moyenne = (float)$d['moyenne_generale'];
            $mention = 'passable';
            if ($moyenne >= 16)      $mention = 'tres_bien';
            elseif ($moyenne >= 14)  $mention = 'bien';
            elseif ($moyenne >= 12)  $mention = 'assez_bien';

            $d['mention_ar'] = [
                'tres_bien'  => 'جيد جداً',
                'bien'       => 'حسن',
                'assez_bien' => 'قريب من الحسن',
                'passable'   => 'مقبول',
            ][$mention] ?? 'مقبول';

            $qualifId = (int)($d['qualif_id'] ?? 0);
            $d['niveau_qualification'] = !empty($d['niveau_qualification']) ? $d['niveau_qualification'] : match ($qualifId) {
                5, 12, 14 => 'الخامس',
                7, 11, 13 => 'الرابع',
                6         => 'الثالث',
                9         => 'الثاني',
                10        => 'الأول',
                default   => 'الخامس'
            };

            $diplomas[] = $d;
        }

        $background = $request->query('background', '1') === '1';

        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4-L',
            'autoScriptToLang' => true,
            'autoLangToFont' => true,
            'margin_top' => 0,
            'margin_bottom' => 0,
            'margin_left' => 0,
            'margin_right' => 0
        ]);
        $mpdf->SetDirectionality('rtl');

        $html = view('admin.diplomes.pdf', [
            'diplomas' => $diplomas,
            'background' => $background,
            'settings' => \App\Helpers\TakwinHelper::getSettings()
        ])->render();

        $mpdf->WriteHTML($html);
        return response($mpdf->Output('diplomes_batch.pdf', \Mpdf\Output\Destination::DOWNLOAD))
            ->header('Content-Type', 'application/pdf');
    }
}
