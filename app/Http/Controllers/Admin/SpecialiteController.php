<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

/**
 * SpecialiteController
 *
 * Manages specialties and branches using Laravel Query Builder and Eloquent:
 *   filieres        → branche   (IDBranche, Nom, NomFr, Code, activee)
 *   specialites     → specialite (IDSpecialite, IDBranche, Nom, NomFr, CodeSpec, NbrSem, activee)
 *   offres_formation → offre    (IDOffre, IDSpecialite, IDSession, IDEts_Form, ...)
 */
class SpecialiteController extends Controller
{
    public function __construct()
    {
        if (app()->runningInConsole()) { return; }
    }

    /**
     * Cartographie interactive — خريطة الشعب والتخصصات
     *
     * Builds per-wilaya statistics for the Leaflet.js map:
     *   - nb_etab  : number of establishments in the wilaya
     *   - nb_spec  : number of distinct specialties offered
     *   - nb_offres: number of training offers
     *   - top_specs: array of top specialty names
     *   - specs_by_year / offres_by_year : broken down by annee_formation
     */
    public function cartographie()
    {
        // ── Re-use index() data (branches, specialites, offres, stats) ──
        $user   = session('user') ?? [];
        $role   = strtolower($user['role_code'] ?? 'user');
        $iddfep = (int)($user['iddfep'] ?? 0);
        $etabId = (int)($user['etablissement_id'] ?? 0);

        $whereClause = "1=1";
        $params = [];
        if ($role === 'dfep' && $iddfep > 0) {
            $whereClause = "e.IDDFEP = ?";
            $params[] = $iddfep;
        } elseif (in_array($role, ['etablissement', 'directeur', 'employee', 'formateur']) && $etabId > 0) {
            $whereClause = "o.IDEts_Form = ?";
            $params[] = $etabId;
        }

        // 1. Fetch static/reference data with caching
        $wilayas = \App\Services\ReferenceCache::wilayas();
        $filieres = \App\Services\ReferenceCache::branches();

        $specialites = \Illuminate\Support\Facades\Cache::remember('cartographie_specialites_list', 86400, function() {
            return array_map(fn($r) => (array)$r, DB::select("
                SELECT sp.IDSpecialite AS id, sp.CodeSpec AS code,
                       sp.Nom AS libelle_ar, sp.NomFr AS libelle_fr,
                       sp.IDBranche AS filiere_id,
                       b.Nom AS filiere_ar, b.NomFr AS filiere_fr, b.Code AS filiere_code
                FROM specialite sp
                LEFT JOIN branche b ON sp.IDBranche = b.IDBranche
                ORDER BY sp.Nom ASC
            "));
        });
        // 2. Fetch stats summary with caching (24h)
        $cacheKeySuffix = "{$role}_{$iddfep}_{$etabId}";
        $statsOffres = \Illuminate\Support\Facades\Cache::remember("cartographie_stats_summary_{$cacheKeySuffix}", 86400, function() use ($whereClause, $params) {
            $stats = ['total_offres' => 0, 'total_capacite' => 0, 'total_inscrits' => 0];
            try {
                $sq = DB::selectOne("
                    SELECT COUNT(*) as total_offres,
                           COALESCE(SUM(o.nbrPrevision),0) as total_capacite,
                           COALESCE(SUM(o.NbrInscr),0) as total_inscrits
                    FROM offre o
                    LEFT JOIN etablissement e ON o.IDEts_Form = e.IDetablissement
                    WHERE $whereClause
                ", $params);
                if ($sq) {
                    $stats = [
                        'total_offres'    => (int)$sq->total_offres,
                        'total_capacite'  => (int)$sq->total_capacite,
                        'total_inscrits'  => (int)$sq->total_inscrits,
                    ];
                }
            } catch (\Exception $e) {}
            return $stats;
        });

        // 3. Build per-wilaya stats with caching (24h)
        $wilayaNames = [];
        foreach ($wilayas as $w) {
            $wilayaNames[(int)$w['id']] = ['nom' => $w['nom_ar'], 'fr' => $w['nom_fr']];
        }

        $wilayaStats = \Illuminate\Support\Facades\Cache::remember("cartographie_wilaya_stats_{$cacheKeySuffix}", 86400, function() use ($whereClause, $params, $wilayaNames) {
            $wilayaStats = [];
            try {
                // Fetch aggregate stats without slow joins
                $aggStats = DB::select("
                    SELECT d.IDWilayaa AS wilaya_id,
                           COUNT(DISTINCT o.IDSpecialite) AS nb_spec,
                           COUNT(o.IDOffre) AS nb_offres,
                           COUNT(DISTINCT o.IDEts_Form) AS nb_etab,
                           
                           COUNT(DISTINCT CASE WHEN o.IDSession IN (31, 32) THEN o.IDSpecialite END) AS nb_spec_2024,
                           COUNT(DISTINCT CASE WHEN o.IDSession IN (33, 34) THEN o.IDSpecialite END) AS nb_spec_2025,
                           COUNT(DISTINCT CASE WHEN o.IDSession = 35 THEN o.IDSpecialite END) AS nb_spec_2026,
                           
                           COUNT(CASE WHEN o.IDSession IN (31, 32) THEN o.IDOffre END) AS nb_offres_2024,
                           COUNT(CASE WHEN o.IDSession IN (33, 34) THEN o.IDOffre END) AS nb_offres_2025,
                           COUNT(CASE WHEN o.IDSession = 35 THEN o.IDOffre END) AS nb_offres_2026
                    FROM offre o
                    LEFT JOIN etablissement e ON o.IDEts_Form = e.IDetablissement
                    LEFT JOIN dfep d          ON e.IDDFEP     = d.IDDFEP
                    WHERE $whereClause
                    GROUP BY d.IDWilayaa
                ", $params);

                // Fetch top specialties per wilaya (fast count group query)
                $specStats = DB::select("
                    SELECT d.IDWilayaa AS wilaya_id, sp.Nom AS spec_name, COUNT(o.IDOffre) AS cnt
                    FROM offre o
                    JOIN etablissement e ON o.IDEts_Form = e.IDetablissement
                    JOIN dfep d          ON e.IDDFEP     = d.IDDFEP
                    JOIN specialite sp   ON o.IDSpecialite = sp.IDSpecialite
                    WHERE $whereClause
                    GROUP BY d.IDWilayaa, sp.Nom
                    ORDER BY d.IDWilayaa, cnt DESC
                ", $params);

                $wilayaSpecs = [];
                foreach ($specStats as $row) {
                    $wid = (int)$row->wilaya_id;
                    if ($wid <= 0) continue;
                    if (!isset($wilayaSpecs[$wid])) {
                        $wilayaSpecs[$wid] = [];
                    }
                    if (count($wilayaSpecs[$wid]) < 10) {
                        $wilayaSpecs[$wid][] = $row->spec_name;
                    }
                }

                foreach ($aggStats as $row) {
                    $wid = (int)$row->wilaya_id;
                    if ($wid <= 0) continue;

                    $wilayaStats[] = [
                        'wilaya_id'     => $wid,
                        'wilaya_nom'    => $wilayaNames[$wid]['nom'] ?? '—',
                        'wilaya_fr'     => $wilayaNames[$wid]['fr']  ?? '—',
                        'nb_etab'       => (int)$row->nb_etab,
                        'nb_spec'       => (int)$row->nb_spec,
                        'nb_offres'     => (int)$row->nb_offres,
                        'top_specs'     => $wilayaSpecs[$wid] ?? [],
                        'specs_by_year' => [
                            '2024' => (int)$row->nb_spec_2024,
                            '2025' => (int)$row->nb_spec_2025,
                            '2026' => (int)$row->nb_spec_2026,
                        ],
                        'offres_by_year'=> [
                            '2024' => (int)$row->nb_offres_2024,
                            '2025' => (int)$row->nb_offres_2025,
                            '2026' => (int)$row->nb_offres_2026,
                        ]
                    ];
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Cartography Aggregation Error: " . $e->getMessage());
            }
            return $wilayaStats;
        });

        // 4. Fetch top specialties globally for active scope (24h)
        $topSpecialites = \Illuminate\Support\Facades\Cache::remember("cartographie_top_specs_{$cacheKeySuffix}", 86400, function() use ($whereClause, $params) {
            try {
                return array_map(fn($r) => (array)$r, DB::select("
                    SELECT o.IDSpecialite AS specialite_id,
                           sp.Nom AS spec_ar,
                           COUNT(o.IDOffre) AS cnt
                    FROM offre o
                    LEFT JOIN specialite sp ON o.IDSpecialite = sp.IDSpecialite
                    LEFT JOIN etablissement e ON o.IDEts_Form = e.IDetablissement
                    WHERE $whereClause
                    GROUP BY o.IDSpecialite, sp.Nom
                    ORDER BY cnt DESC
                    LIMIT 5
                ", $params));
            } catch (\Exception $e) {
                return [];
            }
        });

        // No longer read GeoJSON file in PHP, passed asynchronously via JS fetch.
        $geoJsonData = null;

        return $this->render('admin/specialites/cartographie', [
            'title'          => 'خريطة الشعب والتخصصات — Cartographie Nationale',
            'filieres'       => $filieres,
            'specialites'    => $specialites,
            'offres'         => [], 
            'topSpecialites' => $topSpecialites,
            'statsOffres'    => $statsOffres,
            'wilayas'        => $wilayas,
            'wilayaStats'    => $wilayaStats,
            'geoJsonData'    => $geoJsonData,
        ]);
    }


    /**
     * List all branches (فروع) and specialties (تخصصات) — index page
     */
    public function index()
    {
        $user   = session('user') ?? [];
        $role   = strtolower($user['role_code'] ?? 'user');
        $iddfep = (int)($user['iddfep'] ?? 0);
        $etabId = (int)($user['etablissement_id'] ?? 0);

        // Build scoping clauses
        $whereClause = "1=1";
        $params = [];

        if ($role === 'dfep' && $iddfep > 0) {
            $whereClause = "e.IDDFEP = ?";
            $params[] = $iddfep;
        } elseif (in_array($role, ['etablissement', 'directeur', 'employee', 'formateur']) && $etabId > 0) {
            $whereClause = "o.IDEts_Form = ?";
            $params[] = $etabId;
        }

        $specWhereClause = "1=1";
        $specParams = [];

        if ($role === 'dfep' && $iddfep > 0) {
            $specWhereClause = "sp.IDSpecialite IN (
                SELECT DISTINCT o.IDSpecialite 
                FROM offre o 
                JOIN etablissement e ON o.IDEts_Form = e.IDetablissement 
                WHERE e.IDDFEP = ?
            )";
            $specParams[] = $iddfep;
        } elseif (in_array($role, ['etablissement', 'directeur', 'employee', 'formateur']) && $etabId > 0) {
            $specWhereClause = "sp.IDSpecialite IN (
                SELECT DISTINCT o.IDSpecialite 
                FROM offre o 
                WHERE o.IDEts_Form = ?
            )";
            $specParams[] = $etabId;
        }

        // Fetch all branches (unfiltered) for modals
        try {
            $allFilieres = array_map(fn($item) => (array)$item, DB::select("
                SELECT IDBranche AS id, Code AS code, Nom AS libelle_ar, NomFr AS libelle_fr, activee
                FROM branche
                ORDER BY Nom ASC
            "));
        } catch (\Exception $e) {
            $allFilieres = [];
        }

        // Fetch all specialties (unfiltered) for modals
        try {
            $allSpecialites = array_map(fn($item) => (array)$item, DB::select("
                SELECT
                    sp.IDSpecialite    AS id,
                    sp.CodeSpec        AS code,
                    sp.Nom             AS libelle_ar,
                    sp.NomFr           AS libelle_fr,
                    sp.NbrSem          AS duree_semestres,
                    sp.NbrAnne         AS duree_annees,
                    sp.activee         AS activee,
                    sp.IDBranche       AS filiere_id,
                    b.Nom              AS filiere_ar,
                    b.NomFr            AS filiere_fr,
                    b.Code             AS filiere_code
                FROM specialite sp
                LEFT JOIN branche b ON sp.IDBranche = b.IDBranche
                ORDER BY sp.Nom ASC
            "));
        } catch (\Exception $e) {
            $allSpecialites = [];
        }

        // Fetch specialties with branch labels filtered by scope
        try {
            $specialites = array_map(fn($item) => (array)$item, DB::select("
                SELECT
                    sp.IDSpecialite    AS id,
                    sp.CodeSpec        AS code,
                    sp.Nom             AS libelle_ar,
                    sp.NomFr           AS libelle_fr,
                    sp.NbrSem          AS duree_semestres,
                    sp.NbrAnne         AS duree_annees,
                    sp.activee         AS activee,
                    sp.IDBranche       AS filiere_id,
                    b.Nom              AS filiere_ar,
                    b.NomFr            AS filiere_fr,
                    b.Code             AS filiere_code
                FROM specialite sp
                LEFT JOIN branche b ON sp.IDBranche = b.IDBranche
                WHERE $specWhereClause
                ORDER BY sp.Nom ASC
            ", $specParams));
        } catch (\Exception $e) {
            $specialites = [];
            error_log("SpecialiteController::index() specialite error: " . $e->getMessage());
        }

        // Filter branches for display using specialties active branch IDs
        $filieres = [];
        if ($role === 'dfep' || in_array($role, ['etablissement', 'directeur', 'employee', 'formateur'])) {
            $activeBranchIds = array_unique(array_column($specialites, 'filiere_id'));
            foreach ($allFilieres as $f) {
                if (in_array($f['id'], $activeBranchIds)) {
                    $filieres[] = $f;
                }
            }
        } else {
            $filieres = $allFilieres;
        }

        // Fetch active training offers from WINDEV offre + specialite + etablissement + session filtered by scope
        try {
            $offres = array_map(fn($item) => (array)$item, DB::select("
                SELECT
                    o.IDOffre              AS id,
                    CONCAT('OFF-', o.IDOffre) AS code,
                    o.IDSpecialite         AS specialite_id,
                    sp.Nom                 AS spec_ar,
                    sp.NomFr               AS spec_fr,
                    sp.CodeSpec            AS spec_code,
                    e.Nom                  AS etab_ar,
                    e.NomFr                AS etab_fr,
                    sess.Nom               AS session_ar,
                    sess.Nom               AS session_name,
                    mf.Nom                 AS mode_ar,
                    mf.NomFr               AS mode_fr,
                    o.DateD                AS date_debut,
                    o.DateF                AS date_fin,
                    o.DateSelection        AS date_debut_selection,
                    o.NbrInscr             AS inscrits,
                    o.nbrPrevision         AS capacite,
                    o.Valide               AS valide_etab,
                    o.ValidDfp             AS valide_dfep,
                    o.ValideCentral        AS valide_centrale,
                    o.IDMode_formation     AS mode_formation,
                    CASE
                        WHEN sp.NbrSem = 5 THEN 'BTS'
                        WHEN sp.NbrSem >= 3 THEN 'BP'
                        ELSE 'CAP'
                    END                    AS diplome_vise,
                    CASE
                        WHEN sp.NbrSem = 5 THEN 'ثالثة ثانوي'
                        WHEN sp.NbrSem >= 3 THEN 'تعليم متوسط'
                        ELSE 'بدون مستوى'
                    END                    AS niveau_requis
                FROM offre o
                LEFT JOIN specialite sp    ON o.IDSpecialite = sp.IDSpecialite
                LEFT JOIN etablissement e  ON o.IDEts_Form = e.IDetablissement
                LEFT JOIN session sess     ON o.IDSession = sess.IDSession
                LEFT JOIN mode_formation mf ON o.IDMode_formation = mf.IDMode_formation
                WHERE $whereClause
                ORDER BY o.IDOffre DESC
                LIMIT 200
            ", $params));
        } catch (\Exception $e) {
            $offres = [];
            error_log("SpecialiteController::index() offre error: " . $e->getMessage());
        }

        // Sessions for offer-creation modal
        try {
            $sessions = array_map(fn($item) => (array)$item, DB::select("
                SELECT IDSession AS id, Code AS code_session, Nom AS intitule_ar, NomFr AS intitule_fr, DateD AS date_debut
                FROM session
                ORDER BY DateD DESC
            "));
        } catch (\Exception $e) {
            $sessions = [];
        }

        // Modes of formation
        try {
            $modes = array_map(fn($item) => (array)$item, DB::select("
                SELECT IDMode_formation AS id, Nom AS libelle_ar, NomFr AS libelle_fr, Code AS code
                FROM mode_formation
                ORDER BY NumOrd ASC, NomOrd ASC
            "));
        } catch (\Exception $e) {
            $modes = [];
        }

        // Fetch database statistics for offers filtered by scope
        $statsOffres = [
            'total_offres' => 0,
            'total_capacite' => 0,
            'total_inscrits' => 0
        ];
        try {
            $sQuery = DB::selectOne("
                SELECT 
                    COUNT(*) as total_offres,
                    COALESCE(SUM(o.nbrPrevision), 0) as total_capacite,
                    COALESCE(SUM(o.NbrInscr), 0) as total_inscrits
                FROM offre o
                LEFT JOIN etablissement e ON o.IDEts_Form = e.IDetablissement
                WHERE $whereClause
            ", $params);
            if ($sQuery) {
                $statsOffres['total_offres'] = (int)$sQuery->total_offres;
                $statsOffres['total_capacite'] = (int)$sQuery->total_capacite;
                $statsOffres['total_inscrits'] = (int)$sQuery->total_inscrits;
            }
        } catch (\Exception $e) {
            error_log("SpecialiteController::index() statsOffres error: " . $e->getMessage());
        }

        return $this->render('admin/specialites/index', [
            'title'          => 'تنظيم الفروع والتخصصات / Branches & Spécialités',
            'filieres'       => $filieres,
            'specialites'    => $specialites,
            'allSpecialites' => $allSpecialites,
            'allFilieres'    => $allFilieres,
            'offres'         => $offres,
            'sessions'       => $sessions,
            'modes'          => $modes,
            'statsOffres'    => $statsOffres,
        ]);
    }

    /**
     * Store new specialty — insert into WINDEV specialite table
     */
    public function storeSpecialite()
    {
        if (request()->isMethod('post')) {
            $code       = trim(request()->all()['code'] ?? '');
            $libelle_ar = trim(request()->all()['libelle_ar'] ?? '');
            $libelle_fr = trim(request()->all()['libelle_fr'] ?? '');
            $filiere_id = (int)(request()->all()['filiere_id'] ?? 0);
            $duree_sem  = (int)(request()->all()['duree_semestres'] ?? 4);
            $duree_ann  = $duree_sem > 0 ? round($duree_sem / 2, 1) : 2;

            if (empty($libelle_ar) || $filiere_id <= 0) {
                session(['flash_error' => 'يرجى ملء جميع الحقول الإلزامية / Champs obligatoires manquants.']);
                return $this->redirect('/dashboard/specialites');
            }

            try {
                // Generate primary key manually as WINDEV table lacks auto-increment
                $maxId = (int) DB::table('specialite')->max('IDSpecialite');
                $newId = max(100, $maxId + 1);

                DB::insert("
                    INSERT INTO specialite (IDSpecialite, CodeSpec, Nom, NomFr, IDBranche, NbrSem, NbrAnne, activee)
                    VALUES (?, ?, ?, ?, ?, ?, ?, 1)
                ", [$newId, $code, $libelle_ar, $libelle_fr, $filiere_id, $duree_sem, $duree_ann]);

                session(['flash_success' => 'تم إضافة التخصص بنجاح / Spécialité ajoutée avec succès']);
            } catch (\Exception $e) {
                session(['flash_error' => 'حدث خطأ أثناء حفظ التخصص: ' . $e->getMessage()]);
            }
        }
        return $this->redirect('/dashboard/specialites');
    }

    /**
     * Store new training offer — insert into WINDEV offre table
     */
    public function storeOffre()
    {
        if (request()->isMethod('post')) {
            $specialite_id = (int)(request()->all()['specialite_id'] ?? 0);
            $session_id    = (int)(request()->all()['session_id'] ?? 0);
            $capacite      = (int)(request()->all()['capacite'] ?? 0);
            $debut         = !empty(request()->all()['date_debut']) ? request()->all()['date_debut'] : null;
            $fin           = !empty(request()->all()['date_fin']) ? request()->all()['date_fin'] : null;
            $etab_id       = (int)(session('user')['etablissement_id'] ?? 0);

            // Map string mode_formation to WINDEV integer FK
            $modeStr = trim(request()->all()['mode_formation'] ?? '');
            $mode_id = ($modeStr === 'apprentissage' || $modeStr === '2') ? 2 : 1;

            // Resolve session by name if not provided by ID
            if ($session_id <= 0 && !empty(request()->all()['session_name'])) {
                $sname = trim(request()->all()['session_name']);
                $session_id = (int) DB::table('session')->where('Nom', $sname)->value('IDSession');
            }

            try {
                // Manually generate primary key — IDOffre has no auto-increment
                $maxId = (int) DB::table('offre')->max('IDOffre');
                $newId = max(1, $maxId + 1);

                DB::insert("
                    INSERT INTO offre
                        (IDOffre, IDSession, IDSpecialite, IDMode_formation, DateD, DateF, nbrPrevision, IDEts_Form, Valide, ValidDfp, ValideCentral)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, 0, 0)
                ", [$newId, $session_id, $specialite_id, $mode_id, $debut, $fin, $capacite, $etab_id]);

                session(['flash_success' => 'تم إدراج عرض التكوين الجديد بنجاح / Offre ajoutée']);
            } catch (\Exception $e) {
                session(['flash_error' => 'حدث خطأ أثناء حفظ عرض التكوين: ' . $e->getMessage()]);
            }
        }
        return $this->redirect('/dashboard/specialites');
    }

    /**
     * Update specialty — update WINDEV specialite table
     */
    public function updateSpecialite()
    {
        if (request()->isMethod('post')) {
            $id         = (int)(request()->all()['id'] ?? 0);
            $code       = trim(request()->all()['code'] ?? '');
            $libelle_ar = trim(request()->all()['libelle_ar'] ?? '');
            $libelle_fr = trim(request()->all()['libelle_fr'] ?? '');
            $filiere_id = (int)(request()->all()['filiere_id'] ?? 0);
            $duree_sem  = (int)(request()->all()['duree_semestres'] ?? 4);
            $duree_ann  = $duree_sem > 0 ? round($duree_sem / 2, 1) : 2;

            try {
                DB::update("
                    UPDATE specialite
                    SET CodeSpec = ?, Nom = ?, NomFr = ?, IDBranche = ?, NbrSem = ?, NbrAnne = ?
                    WHERE IDSpecialite = ?
                ", [$code, $libelle_ar, $libelle_fr, $filiere_id, $duree_sem, $duree_ann, $id]);

                session(['flash_success' => 'تم تحديث التخصص بنجاح / Spécialité modifiée avec succès']);
            } catch (\Exception $e) {
                session(['flash_error' => 'حدث خطأ أثناء تحديث التخصص: ' . $e->getMessage()]);
            }
        }
        return $this->redirect('/dashboard/specialites');
    }

    /**
     * Delete specialty — delete from WINDEV specialite table (with FK guard)
     */
    public function deleteSpecialite($id)
    {
        $id = (int)$id;
        try {
            // Guard: check if any offre references this specialty
            $hasOffers = DB::table('offre')->where('IDSpecialite', $id)->exists();
            if ($hasOffers) {
                session(['flash_error' => 'لا يمكن حذف التخصص لوجود عروض تكوين مرتبطة به / Spécialité liée à des offres']);
            } else {
                DB::delete("DELETE FROM specialite WHERE IDSpecialite = ?", [$id]);
                session(['flash_success' => 'تم حذف التخصص بنجاح / Spécialité supprimée avec succès']);
            }
        } catch (\Exception $e) {
            session(['flash_error' => 'حدث خطأ أثناء حذف التخصص: ' . $e->getMessage()]);
        }
        return $this->redirect('/dashboard/specialites');
    }

    /**
     * Print view — uses WINDEV specialite + branche
     */
    public function printSpecialites()
    {
        if (\App\Helpers\SovereignLicensingHelper::getSetting('feature_print_actions_enabled', '1') !== '1') {
            abort(403, 'طباعة التخصصات معطلة من قبل مدير النظام / Printing is disabled by administrator.');
        }

        $user   = session('user') ?? [];
        $role   = strtolower($user['role_code'] ?? 'user');
        $iddfep = (int)($user['iddfep'] ?? 0);
        $etabId = (int)($user['etablissement_id'] ?? 0);

        $specWhereClause = "1=1";
        $specParams = [];

        if ($role === 'dfep' && $iddfep > 0) {
            $specWhereClause = "sp.IDSpecialite IN (
                SELECT DISTINCT o.IDSpecialite 
                FROM offre o 
                JOIN etablissement e ON o.IDEts_Form = e.IDetablissement 
                WHERE e.IDDFEP = ?
            )";
            $specParams[] = $iddfep;
        } elseif (in_array($role, ['etablissement', 'directeur', 'employee', 'formateur']) && $etabId > 0) {
            $specWhereClause = "sp.IDSpecialite IN (
                SELECT DISTINCT o.IDSpecialite 
                FROM offre o 
                WHERE o.IDEts_Form = ?
            )";
            $specParams[] = $etabId;
        }

        try {
            $specialites = array_map(fn($item) => (array)$item, DB::select("
                SELECT
                    sp.IDSpecialite  AS id,
                    sp.CodeSpec      AS code,
                    sp.Nom           AS libelle_ar,
                    sp.NomFr         AS libelle_fr,
                    sp.NbrSem        AS duree_semestres,
                    b.Nom            AS filiere_ar,
                    b.NomFr          AS filiere_fr,
                    b.Code           AS filiere_code
                FROM specialite sp
                LEFT JOIN branche b ON sp.IDBranche = b.IDBranche
                WHERE $specWhereClause
                ORDER BY b.Nom ASC, sp.Nom ASC
            ", $specParams));
        } catch (\Exception $e) {
            $specialites = [];
        }

        return $this->render('admin/specialites/print', [
            'title'      => 'طباعة دليل التخصصات المهنية - SGFEP',
            'specialites' => $specialites,
        ], 'print');
    }

    /**
     * Import specialties from CSV
     */
    public function importSpecialites()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->redirect('/dashboard/specialites');
        }

        $csvText = request()->all()['csv_text'] ?? '';

        if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
            $fileContent = file_get_contents($_FILES['csv_file']['tmp_name']);
            // Strip UTF-8 BOM
            if (substr($fileContent, 0, 3) === "\xEF\xBB\xBF") {
                $fileContent = substr($fileContent, 3);
            }
            $csvText = $fileContent;
        }

        if (empty(trim($csvText))) {
            session(['flash_error' => 'يرجى تقديم ملف CSV أو نص صالح / Veuillez fournir un fichier CSV valide']);
            return $this->redirect('/dashboard/specialites');
        }

        // Map branche Code → IDBranche for fast lookup
        $brancheQuery = DB::table('branche')->select('IDBranche', 'Code')->get();
        $brancheMap   = [];
        foreach ($brancheQuery as $b) {
            $brancheMap[trim(strtoupper($b->Code))] = $b->IDBranche;
        }

        $lines = preg_split('/\r\n|\r|\n/', $csvText);

        $insertedCount = 0;
        $updatedCount  = 0;
        $skippedCount  = 0;
        $errors        = [];

        $queryInsert = "
            INSERT INTO specialite (IDSpecialite, CodeSpec, IDBranche, Nom, NomFr, NbrSem, NbrAnne, activee)
            VALUES (?, ?, ?, ?, ?, ?, ?, 1)
            ON DUPLICATE KEY UPDATE
                CodeSpec  = VALUES(CodeSpec),
                IDBranche = VALUES(IDBranche),
                Nom       = VALUES(Nom),
                NomFr     = VALUES(NomFr),
                NbrSem    = VALUES(NbrSem),
                NbrAnne   = VALUES(NbrAnne)
        ";

        foreach ($lines as $index => $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            // Skip header row
            if ($index === 0 && (
                stripos($line, 'CodeSpec') !== false ||
                stripos($line, 'الرمز') !== false ||
                stripos($line, 'Code') !== false
            )) {
                continue;
            }

            $row = str_getcsv($line);
            if (count($row) < 4) {
                $skippedCount++;
                continue;
            }

            $codeSpec  = trim($row[0] ?? '');
            $legacyId  = (int)trim($row[1] ?? '0');
            $nomAr     = trim($row[3] ?? '');
            $nomFr     = trim($row[4] ?? '');

            $dureeMois = isset($row[9]) && is_numeric(trim($row[9])) ? (int)trim($row[9]) : 0;
            if ($dureeMois > 0) {
                $nbrSem = max(1, (int)round($dureeMois / 6));
            } else {
                $nbrSem = isset($row[5]) && is_numeric(trim($row[5])) ? (int)trim($row[5]) : 4;
            }

            if ($legacyId <= 0 || empty($nomAr)) {
                $skippedCount++;
                continue;
            }

            // Skip cartouche / placeholder rows
            if (strpos($codeSpec, '_CART') !== false) {
                $skippedCount++;
                continue;
            }

            // Determine branche from CodeSpec prefix
            $brancheCode = strtoupper(substr($codeSpec, 0, 3));
            if (strpos($codeSpec, '/') !== false) {
                $parts       = explode('/', $codeSpec);
                $brancheCode = strtoupper(substr(trim($parts[1] ?? ''), 0, 3));
            }

            $brancheId = $brancheMap[$brancheCode] ?? null;
            if ($brancheId === null) {
                // Self-heal: dynamically register the unknown branch so the row is not lost
                try {
                    $maxBranche = (int) DB::table('branche')->max('IDBranche');
                    $newBranche = max(1, $maxBranche + 1);
                    DB::table('branche')->insert([
                        'IDBranche' => $newBranche,
                        'Code' => $brancheCode,
                        'Nom' => $brancheCode,
                        'NomFr' => $brancheCode,
                        'activee' => 1
                    ]);
                    $brancheMap[$brancheCode] = $newBranche;
                    $brancheId = $newBranche;
                } catch (\Exception $eB) {
                    $errors[] = "شعبة {$brancheCode} غير موجودة وفشل إنشاؤها للتخصص {$codeSpec}: " . $eB->getMessage();
                    $skippedCount++;
                    continue;
                }
            }

            $nbrAnne = $nbrSem > 0 ? round($nbrSem / 2, 1) : 2;

            try {
                $affected = DB::affectingStatement($queryInsert, [$legacyId, $codeSpec, $brancheId, $nomAr, $nomFr, $nbrSem, $nbrAnne]);
                if ($affected == 1) {
                    $insertedCount++;
                } else {
                    $updatedCount++;
                }
            } catch (\Exception $e) {
                $errors[]     = "خطأ في {$codeSpec}: " . $e->getMessage();
                $skippedCount++;
            }
        }

        $msg  = "استيراد التخصصات: مضافة {$insertedCount}، محدثة {$updatedCount}، متجاوزة {$skippedCount}.";
        if (!empty($errors)) {
            $msg .= ' تنبيهات: ' . implode(' | ', array_slice($errors, 0, 3));
            if (count($errors) > 3) {
                $msg .= ' ...';
            }
        }
        session(['flash_success' => $msg]);

        return $this->redirect('/dashboard/specialites');
    }
}
