<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Domains\Academic\Services\ApprenantService;
use Illuminate\Support\Facades\DB;

/**
 * GradesController (Domain)
 *
 * Trainee grade management using Laravel Query Builder and services.
 */
class GradesController extends Controller
{
    protected ApprenantService $service;

    public function __construct(ApprenantService $service)
    {
        $this->service = $service;
        if (app()->runningInConsole()) { return; }
    }

    private function validateOffreAccess(array $offre, int $semestre): void
    {
        $user = session('user');
        $role = strtolower($user['role_code'] ?? '');
        $etabId = (int)($user['etablissement_id'] ?? 0);
        $dfepId = (int)($user['iddfep'] ?? 0);

        // 1. Semester Validation
        $maxSem = (int)($offre['duree_semestres'] ?? 4);
        if ($semestre < 1 || $semestre > $maxSem) {
            abort(403, 'السداسي المحدد غير صالح لهذا التخصص.');
        }

        // Check if previous semester is validated
        if ($semestre > 1) {
            $prevSem = $semestre - 1;
            $isPrevVal = DB::selectOne("
                SELECT 1 FROM section_semestre ss
                JOIN section s ON ss.IDSection = s.IDSection
                WHERE s.IDOffre = ? AND ss.NumSem = ?
                  AND ((ss.NumPv IS NOT NULL AND ss.NumPv != '') OR ss.visaevaldir = 1 OR ss.visaevaldfep = 1)
                LIMIT 1
            ", [(int)$offre['id'], $prevSem]) !== null;

            if (!$isPrevVal) {
                abort(403, 'عذراً، لا يمكنك الانتقال لهذا السداسي قبل إتمام ومداولة السداسي السابق أولاً.');
            }
        }

        // 2. Role-based Scope Validation
        if (in_array($role, ['admin', 'central', 'high_admin', 'secretaire_general', 'ministre'])) {
            // Admins can access all
        } elseif ($role === 'dfep' && $dfepId > 0) {
            if ((int)$offre['dfep_id'] !== $dfepId) {
                abort(403, 'غير مصرح لك بالوصول لبيانات ولاية أخرى.');
            }
        } else {
            // Etablissement / Directeur / Employee / Formateur
            if ($etabId > 0 && (int)$offre['etablissement_id'] !== $etabId) {
                abort(403, 'غير مصرح لك بالوصول لبيانات مؤسسة أخرى.');
            }
        }

        // 3. Mode Validation
        $isMode10 = ((int)($user['IDMode_formation'] ?? 0) === 10 || strtolower($user['role_fr'] ?? '') === 'apprentissage');
        if ($isMode10 && (int)$offre['mode_formation'] !== 10) {
            abort(403, 'غير مصرح لك بالوصول لغير نمط التمهين.');
        } elseif (strtolower($user['username'] ?? '') === 'sdtpp' && (int)$offre['mode_formation'] === 10) {
            abort(403, 'غير مصرح لك بالوصول لنمط التمهين.');
        }
    }

    /**
     * Grade management dashboard — offre list and quick stats.
     */
    public function index(): mixed
    {
        set_time_limit(300); // Allow up to 5 minutes for this heavy stats page
        $user    = session('user');
        $etabId  = $user['etablissement_id'] ?? null;
        $role    = strtolower($user['role_code'] ?? '');
        $dfepId  = $user['iddfep'] ?? null;

        $modeId = (int)($user['IDMode_formation'] ?? 0);
        if (request('force_mode_10')) {
            $modeId = 10;
        }
        $isMode10 = ($modeId === 10 || strtolower($user['role_fr'] ?? '') === 'apprentissage');

        $selectedWilaya = null;
        $selectedEtab = null;
        $selectedYear = request('filter_year') ? (int)request('filter_year') : null;

        if (in_array($role, ['admin', 'central', 'high_admin', 'secretaire_general', 'ministre'])) {
            $selectedWilaya = request('filter_wilaya') ? (int)request('filter_wilaya') : null;
            $selectedEtab = request('filter_etab') ? (int)request('filter_etab') : null;
        } elseif ($role === 'dfep' && $dfepId) {
            $selectedWilaya = $dfepId;
            $selectedEtab = request('filter_etab') ? (int)request('filter_etab') : null;
        } else {
            $selectedEtab = $etabId > 0 ? (int)$etabId : null;
            if ($selectedEtab > 0) {
                $selectedWilaya = (int) \Illuminate\Support\Facades\Cache::remember("etab_wilaya_{$selectedEtab}", 86400, function() use ($selectedEtab) {
                    $row = DB::selectOne("SELECT IDDFEP FROM etablissement WHERE IDetablissement = ?", [$selectedEtab]);
                    return $row ? (int)$row->IDDFEP : 0;
                });
            }
        }

        // We build the WHERE conditions and bindings for offres
        $whereClauses = ["s.Nom != ''", "s.Nom IS NOT NULL", "s.NbrSem > 0", "((s.NbrSem > 3 AND sess.DateD >= '2024-01-01') OR (s.NbrSem <= 3 AND sess.DateD >= '2025-01-01'))"];
        $bindings = [];

        if (request('type') === 'bep') {
            $whereClauses[] = "o.IDMode_formation = 8";
        }

        if ($isMode10) {
            $whereClauses[] = "o.IDMode_formation = 10";
        }

        if ($selectedWilaya > 0) {
            $whereClauses[] = "e.IDDFEP = ?";
            $bindings[] = $selectedWilaya;
        }
        if ($selectedEtab > 0) {
            $whereClauses[] = "o.IDEts_Form = ?";
            $bindings[] = $selectedEtab;
        }
        if ($selectedYear > 0) {
            $whereClauses[] = "sess.IDSession = ?";
            $bindings[] = $selectedYear;
        }

        // Offre list - scoped by role and filters
        if ($role === 'formateur' || $role === 'employee') {
            $whereClauses[] = "ssm.IDEncadrement = ?";
            $bindings[] = $user['id'];

            $sql = "
                SELECT DISTINCT o.IDOffre as id, o.IDMode_formation as mode_formation,
                       s.Nom as spec_ar, s.CodeSpec as spec_code,
                       s.NbrSem as duree_semestres,
                       CASE
                           WHEN o.IDMode_formation = 8 THEN 'BEP'
                           WHEN s.NbrSem >= 5 THEN 'BTS'
                           WHEN s.NbrSem = 4 THEN 'BTS'
                           WHEN s.NbrSem = 3 THEN 'TS'
                           WHEN s.NbrSem = 2 THEN 'CMP'
                           WHEN s.NbrSem = 1 THEN 'Qualifiant'
                           ELSE 'CAP'
                       END as diplome_vise,
                       e.Nom as etab_nom,
                       (SELECT COUNT(*) FROM section sec2
                        JOIN apprenant a ON sec2.IDSection = a.IDSection
                        WHERE sec2.IDOffre = o.IDOffre AND a.statut = 'actif') as nb_actifs
                FROM section_semestre_module ssm
                JOIN section_semestre ss ON ssm.IDSection_Semestre = ss.IDSection_Semestre
                JOIN section sec ON ss.IDSection = sec.IDSection
                JOIN offre o ON sec.IDOffre = o.IDOffre
                JOIN specialite s ON o.IDSpecialite = s.IDSpecialite
                JOIN etablissement e ON o.IDEts_Form = e.IDetablissement
                JOIN session sess ON o.IDSession = sess.IDSession
                WHERE " . implode(" AND ", $whereClauses) . "
                ORDER BY s.Nom
            ";
        } else {
            $whereClauses[] = "EXISTS (
                SELECT 1 
                FROM section sec 
                JOIN apprenant a ON a.IDSection = sec.IDSection 
                WHERE sec.IDOffre = o.IDOffre AND a.statut = 'actif'
            )";

            $limitStr = "";
            if (in_array($role, ['admin', 'central', 'high_admin', 'secretaire_general', 'ministre'])) {
                $limitStr = " LIMIT 100";
            }

            $sql = "
                SELECT o.IDOffre as id, o.IDMode_formation as mode_formation,
                       s.Nom as spec_ar, s.CodeSpec as spec_code,
                       s.NbrSem as duree_semestres,
                       CASE
                           WHEN o.IDMode_formation = 8 THEN 'BEP'
                           WHEN s.NbrSem >= 5 THEN 'BTS'
                           WHEN s.NbrSem = 4 THEN 'BTS'
                           WHEN s.NbrSem = 3 THEN 'TS'
                           WHEN s.NbrSem = 2 THEN 'CMP'
                           WHEN s.NbrSem = 1 THEN 'Qualifiant'
                           ELSE 'CAP'
                       END as diplome_vise,
                       e.Nom as etab_nom,
                       (SELECT COUNT(*) 
                        FROM apprenant a 
                        JOIN section sec2 ON a.IDSection = sec2.IDSection 
                        WHERE sec2.IDOffre = o.IDOffre AND a.statut = 'actif') as nb_actifs
                FROM offre o
                JOIN specialite s ON o.IDSpecialite = s.IDSpecialite
                JOIN etablissement e ON o.IDEts_Form = e.IDetablissement
                JOIN session sess ON o.IDSession = sess.IDSession
                WHERE " . implode(" AND ", $whereClauses) . "
                ORDER BY s.Nom
                $limitStr
            ";
        }

        $offres = array_map(fn($item) => (array)$item, DB::select($sql, $bindings));

        // Stats — role-scoped quick counts or dynamic filtering
        $statsWhere = [];
        $statsParams = [];

        // Always apply the dual session-year filter based on specialty length
        $statsWhere[] = "((sp.NbrSem > 3 AND sess.DateD >= '2024-01-01') OR (sp.NbrSem <= 3 AND sess.DateD >= '2025-01-01'))";

        if ($selectedWilaya > 0) {
            $statsWhere[] = "e.IDDFEP = :wilayaId";
            $statsParams['wilayaId'] = $selectedWilaya;
        }
        if ($selectedEtab > 0) {
            $statsWhere[] = "o.IDEts_Form = :etabId";
            $statsParams['etabId'] = $selectedEtab;
        }
        if ($selectedYear > 0) {
            $statsWhere[] = "sess.IDSession = :sessionId";
            $statsParams['sessionId'] = $selectedYear;
        }

        $statsFilter = count($statsWhere) > 0 ? implode(" AND ", $statsWhere) : null;
        if ($isMode10) {
            if ($statsFilter) {
                $statsFilter .= " AND o.IDMode_formation = 10";
            } else {
                $statsFilter = "o.IDMode_formation = 10";
            }
        }
        if (request('type') === 'bep') {
            if ($statsFilter) {
                $statsFilter .= " AND o.IDMode_formation = 8";
            } else {
                $statsFilter = "o.IDMode_formation = 8";
            }
        }

        if ($statsFilter) {
            $cacheKey = 'filtered_grades_stats_' . md5($statsFilter . serialize($statsParams));
            $stats = \App\Services\CacheService::remember($cacheKey, 600, function() use ($statsFilter, $statsParams) {
                $stmtStg = DB::selectOne("
                    SELECT COUNT(*) as total_stagiaires 
                    FROM apprenant a
                    JOIN section s ON a.IDSection = s.IDSection
                    JOIN offre o ON s.IDOffre = o.IDOffre
                    JOIN specialite sp ON o.IDSpecialite = sp.IDSpecialite
                    JOIN session sess ON s.IDSession = sess.IDSession
                    JOIN etablissement e ON s.IDEts_Form = e.IDetablissement
                    WHERE a.statut = 'actif' AND $statsFilter
                ", $statsParams);

                $notesActive = DB::selectOne("
                    SELECT COUNT(*) as cnt
                    FROM apprenant_section_semstre_module assm
                    JOIN apprenant_section_semstre ass ON assm.IDapprenant_Section_semstre = ass.IDapprenant_Section_semstre
                    JOIN apprenant a ON ass.IDapprenant = a.IDapprenant
                    JOIN section s ON a.IDSection = s.IDSection
                    JOIN offre o ON s.IDOffre = o.IDOffre
                    JOIN specialite sp ON o.IDSpecialite = sp.IDSpecialite
                    JOIN session sess ON s.IDSession = sess.IDSession
                    JOIN etablissement e ON s.IDEts_Form = e.IDetablissement
                    WHERE $statsFilter
                ", $statsParams);

                $notesFin = DB::selectOne("
                    SELECT COUNT(*) as cnt
                    FROM apprenant_section_semstre_module assm
                    JOIN apprenant_fin af ON assm.IDapprenant_Section_semstre = af.IDapprenant_Section_semstre
                    JOIN apprenant a ON af.IDapprenant = a.IDapprenant
                    JOIN section s ON a.IDSection = s.IDSection
                    JOIN offre o ON s.IDOffre = o.IDOffre
                    JOIN specialite sp ON o.IDSpecialite = sp.IDSpecialite
                    JOIN session sess ON s.IDSession = sess.IDSession
                    JOIN etablissement e ON s.IDEts_Form = e.IDetablissement
                    WHERE $statsFilter
                ", $statsParams);

                $total_notes = (int)($notesActive->cnt ?? 0) + (int)($notesFin->cnt ?? 0);

                $valActive = DB::selectOne("
                    SELECT COUNT(*) as cnt
                    FROM apprenant_section_semstre ass
                    JOIN apprenant a ON ass.IDapprenant = a.IDapprenant
                    JOIN section s ON a.IDSection = s.IDSection
                    JOIN offre o ON s.IDOffre = o.IDOffre
                    JOIN specialite sp ON o.IDSpecialite = sp.IDSpecialite
                    JOIN session sess ON s.IDSession = sess.IDSession
                    JOIN etablissement e ON s.IDEts_Form = e.IDetablissement
                    WHERE (ass.MoyApr > 0 OR ass.MoyAvr > 0) AND $statsFilter
                ", $statsParams);

                $valFin = DB::selectOne("
                    SELECT COUNT(*) as cnt
                    FROM apprenant_fin af
                    JOIN apprenant a ON af.IDapprenant = a.IDapprenant
                    JOIN section s ON a.IDSection = s.IDSection
                    JOIN offre o ON s.IDOffre = o.IDOffre
                    JOIN specialite sp ON o.IDSpecialite = sp.IDSpecialite
                    JOIN session sess ON s.IDSession = sess.IDSession
                    JOIN etablissement e ON s.IDEts_Form = e.IDetablissement
                    WHERE (af.MoyFinForm > 0 OR af.MoyGen > 0) AND $statsFilter
                ", $statsParams);

                $resultats_valides = (int)($valActive->cnt ?? 0) + (int)($valFin->cnt ?? 0);

                $stmtPvs = DB::selectOne("
                    SELECT COUNT(*) as pvs_approuves 
                    FROM section_semestre ss
                    JOIN section s ON ss.IDSection = s.IDSection
                    JOIN offre o ON s.IDOffre = o.IDOffre
                    JOIN specialite sp ON o.IDSpecialite = sp.IDSpecialite
                    JOIN session sess ON s.IDSession = sess.IDSession
                    JOIN etablissement e ON s.IDEts_Form = e.IDetablissement
                    WHERE (ss.NumPv IS NOT NULL AND ss.NumPv != '' OR ss.visaevaldir = 1 OR ss.visaevaldfep = 1) AND $statsFilter
                ", $statsParams);

                return [
                    'total_stagiaires' => (int)($stmtStg->total_stagiaires ?? 0),
                    'total_notes' => $total_notes,
                    'resultats_valides' => $resultats_valides,
                    'pvs_approuves' => (int)($stmtPvs->pvs_approuves ?? 0)
                ];
            });
        } else {
            // No filter, fall back to role-scoped cached query
            if (in_array($role, ['admin', 'central', 'high_admin', 'secretaire_general', 'ministre'])) {
                $cacheKey = 'admin_grades_stats' . ($isMode10 ? '_mode10' : '');
                $stats = \App\Services\CacheService::remember($cacheKey, 600, function() use ($isMode10) {
                    if ($isMode10) {
                        return (array) DB::selectOne("
                            SELECT
                                (SELECT COUNT(*) FROM apprenant a JOIN section sec ON a.IDSection = sec.IDSection JOIN offre o ON sec.IDOffre = o.IDOffre WHERE a.statut = 'actif' AND o.IDMode_formation = 10) as total_stagiaires,
                                (SELECT COUNT(*) FROM apprenant_section_semstre_module assm JOIN apprenant_section_semstre ass ON assm.IDapprenant_Section_semstre = ass.IDapprenant_Section_semstre JOIN apprenant a ON ass.IDapprenant = a.IDapprenant JOIN section sec ON a.IDSection = sec.IDSection JOIN offre o ON sec.IDOffre = o.IDOffre WHERE o.IDMode_formation = 10) as total_notes,
                                (
                                    SELECT (SELECT COUNT(*) FROM apprenant_section_semstre ass JOIN apprenant a ON ass.IDapprenant = a.IDapprenant JOIN section sec ON a.IDSection = sec.IDSection JOIN offre o ON sec.IDOffre = o.IDOffre WHERE (ass.MoyApr > 0 OR ass.MoyAvr > 0) AND o.IDMode_formation = 10) +
                                           (SELECT COUNT(*) FROM apprenant_fin af JOIN apprenant a ON af.IDapprenant = a.IDapprenant JOIN section sec ON a.IDSection = sec.IDSection JOIN offre o ON sec.IDOffre = o.IDOffre WHERE (af.MoyFinForm > 0 OR af.MoyGen > 0) AND o.IDMode_formation = 10)
                                ) as resultats_valides,
                                (SELECT COUNT(*) FROM section_semestre ss JOIN section sec ON ss.IDSection = sec.IDSection JOIN offre o ON sec.IDOffre = o.IDOffre WHERE (ss.NumPv IS NOT NULL AND ss.NumPv != '' OR ss.visaevaldir = 1 OR ss.visaevaldfep = 1) AND o.IDMode_formation = 10) as pvs_approuves
                        ");
                    }
                    return (array) DB::selectOne("
                        SELECT
                            (SELECT COUNT(*) FROM apprenant WHERE statut = 'actif') as total_stagiaires,
                            (SELECT COUNT(*) FROM apprenant_section_semstre_module) as total_notes,
                            (
                                SELECT (SELECT COUNT(*) FROM apprenant_section_semstre WHERE MoyApr > 0 OR MoyAvr > 0) +
                                       (SELECT COUNT(*) FROM apprenant_fin WHERE MoyFinForm > 0 OR MoyGen > 0)
                            ) as resultats_valides,
                            (SELECT COUNT(*) FROM section_semestre WHERE NumPv IS NOT NULL AND NumPv != '' OR visaevaldir = 1 OR visaevaldfep = 1) as pvs_approuves
                    ");
                });
            } elseif ($role === 'dfep' && $dfepId) {
                $cacheKey = 'dfep_grades_stats_' . $dfepId . ($isMode10 ? '_mode10' : '');
                $stats = \App\Services\CacheService::remember($cacheKey, 600, function() use ($dfepId, $isMode10) {
                    if ($isMode10) {
                        return (array) DB::selectOne("
                            SELECT
                                (SELECT COUNT(*) FROM apprenant a
                                 JOIN section sec ON a.IDSection = sec.IDSection
                                 JOIN offre o ON sec.IDOffre = o.IDOffre
                                 WHERE sec.IDDFEP = :dfepId1 AND a.statut = 'actif' AND o.IDMode_formation = 10) as total_stagiaires,
                                 
                                (
                                    (SELECT COUNT(*)
                                     FROM apprenant_section_semstre_module assm
                                     JOIN apprenant_section_semstre ass ON assm.IDapprenant_Section_semstre = ass.IDapprenant_Section_semstre
                                     JOIN apprenant a ON ass.IDapprenant = a.IDapprenant
                                     JOIN section sec ON a.IDSection = sec.IDSection
                                     JOIN offre o ON sec.IDOffre = o.IDOffre
                                     WHERE sec.IDDFEP = :dfepId2 AND o.IDMode_formation = 10)
                                    +
                                    (SELECT COUNT(*)
                                     FROM apprenant_section_semstre_module assm
                                     JOIN apprenant_fin af ON assm.IDapprenant_Section_semstre = af.IDapprenant_Section_semstre
                                     JOIN apprenant a ON af.IDapprenant = a.IDapprenant
                                     JOIN section sec ON a.IDSection = sec.IDSection
                                     JOIN offre o ON sec.IDOffre = o.IDOffre
                                     WHERE sec.IDDFEP = :dfepId3 AND o.IDMode_formation = 10)
                                ) as total_notes,
                                
                                (
                                    (SELECT COUNT(*)
                                     FROM apprenant_section_semstre ass
                                     JOIN apprenant a ON ass.IDapprenant = a.IDapprenant
                                     JOIN section sec ON a.IDSection = sec.IDSection
                                     JOIN offre o ON sec.IDOffre = o.IDOffre
                                     WHERE sec.IDDFEP = :dfepId4 AND (ass.MoyApr > 0 OR ass.MoyAvr > 0) AND o.IDMode_formation = 10)
                                    +
                                    (SELECT COUNT(*)
                                     FROM apprenant_fin af
                                     JOIN apprenant a ON af.IDapprenant = a.IDapprenant
                                     JOIN section sec ON a.IDSection = sec.IDSection
                                     JOIN offre o ON sec.IDOffre = o.IDOffre
                                     WHERE sec.IDDFEP = :dfepId5 AND (af.MoyFinForm > 0 OR af.MoyGen > 0) AND o.IDMode_formation = 10)
                                ) as resultats_valides,
                                
                                (SELECT COUNT(*) FROM section_semestre ss
                                 JOIN section sec ON ss.IDSection = sec.IDSection
                                 JOIN offre o ON sec.IDOffre = o.IDOffre
                                 WHERE sec.IDDFEP = :dfepId6 AND (ss.NumPv IS NOT NULL AND ss.NumPv != '' OR ss.visaevaldir = 1 OR ss.visaevaldfep = 1) AND o.IDMode_formation = 10) as pvs_approuves
                        ", [
                            'dfepId1' => $dfepId,
                            'dfepId2' => $dfepId,
                            'dfepId3' => $dfepId,
                            'dfepId4' => $dfepId,
                            'dfepId5' => $dfepId,
                            'dfepId6' => $dfepId,
                        ]);
                    }
                    return (array) DB::selectOne("
                        SELECT
                            (SELECT COUNT(*) FROM apprenant a
                             JOIN section sec ON a.IDSection = sec.IDSection
                             WHERE sec.IDDFEP = :dfepId1 AND a.statut = 'actif') as total_stagiaires,
                             
                            (
                                (SELECT COUNT(*)
                                 FROM apprenant_section_semstre_module assm
                                 JOIN apprenant_section_semstre ass ON assm.IDapprenant_Section_semstre = ass.IDapprenant_Section_semstre
                                 JOIN apprenant a ON ass.IDapprenant = a.IDapprenant
                                 JOIN section sec ON a.IDSection = sec.IDSection
                                 WHERE sec.IDDFEP = :dfepId2)
                                +
                                (SELECT COUNT(*)
                                 FROM apprenant_section_semstre_module assm
                                 JOIN apprenant_fin af ON assm.IDapprenant_Section_semstre = af.IDapprenant_Section_semstre
                                 JOIN apprenant a ON af.IDapprenant = a.IDapprenant
                                 JOIN section sec ON a.IDSection = sec.IDSection
                                 WHERE sec.IDDFEP = :dfepId3)
                            ) as total_notes,
                            
                            (
                                (SELECT COUNT(*)
                                 FROM apprenant_section_semstre ass
                                 JOIN apprenant a ON ass.IDapprenant = a.IDapprenant
                                 JOIN section sec ON a.IDSection = sec.IDSection
                                 WHERE sec.IDDFEP = :dfepId4 AND (ass.MoyApr > 0 OR ass.MoyAvr > 0))
                                +
                                (SELECT COUNT(*)
                                 FROM apprenant_fin af
                                 JOIN apprenant a ON af.IDapprenant = a.IDapprenant
                                 JOIN section sec ON a.IDSection = sec.IDSection
                                 WHERE sec.IDDFEP = :dfepId5 AND (af.MoyFinForm > 0 OR af.MoyGen > 0))
                            ) as resultats_valides,
                            
                            (SELECT COUNT(*) FROM section_semestre ss
                             JOIN section sec ON ss.IDSection = sec.IDSection
                             WHERE sec.IDDFEP = :dfepId6 AND (ss.NumPv IS NOT NULL AND ss.NumPv != '' OR ss.visaevaldir = 1 OR ss.visaevaldfep = 1)) as pvs_approuves
                    ", [
                        'dfepId1' => $dfepId,
                        'dfepId2' => $dfepId,
                        'dfepId3' => $dfepId,
                        'dfepId4' => $dfepId,
                        'dfepId5' => $dfepId,
                        'dfepId6' => $dfepId,
                    ]);
                });
            } elseif ($etabId) {
                $cacheKey = 'etab_grades_stats_' . $etabId . ($isMode10 ? '_mode10' : '');
                $stats = \App\Services\CacheService::remember($cacheKey, 600, function() use ($etabId, $isMode10) {
                    if ($isMode10) {
                        return (array) DB::selectOne("
                            SELECT
                                (SELECT COUNT(*) FROM apprenant a
                                 JOIN section sec ON a.IDSection = sec.IDSection
                                 JOIN offre o ON sec.IDOffre = o.IDOffre
                                 WHERE o.IDEts_Form = :etabId1 AND a.statut = 'actif' AND o.IDMode_formation = 10) as total_stagiaires,
                                 
                                (
                                    (SELECT COUNT(*)
                                     FROM apprenant_section_semstre_module assm
                                     JOIN apprenant_section_semstre ass ON assm.IDapprenant_Section_semstre = ass.IDapprenant_Section_semstre
                                     JOIN apprenant a ON ass.IDapprenant = a.IDapprenant
                                     JOIN section sec ON a.IDSection = sec.IDSection
                                     JOIN offre o ON sec.IDOffre = o.IDOffre
                                     WHERE o.IDEts_Form = :etabId2 AND o.IDMode_formation = 10)
                                    +
                                    (SELECT COUNT(*)
                                     FROM apprenant_section_semstre_module assm
                                     JOIN apprenant_fin af ON assm.IDapprenant_Section_semstre = af.IDapprenant_Section_semstre
                                     JOIN apprenant a ON af.IDapprenant = a.IDapprenant
                                     JOIN section sec ON a.IDSection = sec.IDSection
                                     JOIN offre o ON sec.IDOffre = o.IDOffre
                                     WHERE o.IDEts_Form = :etabId3 AND o.IDMode_formation = 10)
                                ) as total_notes,
                                
                                (
                                    (SELECT COUNT(*)
                                     FROM apprenant_section_semstre ass
                                     JOIN apprenant a ON ass.IDapprenant = a.IDapprenant
                                     JOIN section sec ON a.IDSection = sec.IDSection
                                     JOIN offre o ON sec.IDOffre = o.IDOffre
                                     WHERE o.IDEts_Form = :etabId4 AND (ass.MoyApr > 0 OR ass.MoyAvr > 0) AND o.IDMode_formation = 10)
                                    +
                                    (SELECT COUNT(*)
                                     FROM apprenant_fin af
                                     JOIN apprenant a ON af.IDapprenant = a.IDapprenant
                                     JOIN section sec ON a.IDSection = sec.IDSection
                                     JOIN offre o ON sec.IDOffre = o.IDOffre
                                     WHERE o.IDEts_Form = :etabId5 AND (af.MoyFinForm > 0 OR af.MoyGen > 0) AND o.IDMode_formation = 10)
                                ) as resultats_valides,
                                
                                (SELECT COUNT(*) FROM section_semestre ss
                                 JOIN section sec ON ss.IDSection = sec.IDSection
                                 JOIN offre o ON sec.IDOffre = o.IDOffre
                                 WHERE o.IDEts_Form = :etabId6 AND (ss.NumPv IS NOT NULL AND ss.NumPv != '' OR ss.visaevaldir = 1 OR ss.visaevaldfep = 1) AND o.IDMode_formation = 10) as pvs_approuves
                        ", [
                            'etabId1' => $etabId,
                            'etabId2' => $etabId,
                            'etabId3' => $etabId,
                            'etabId4' => $etabId,
                            'etabId5' => $etabId,
                            'etabId6' => $etabId,
                        ]);
                    }
                    return (array) DB::selectOne("
                        SELECT
                            (SELECT COUNT(*) FROM apprenant a
                             JOIN section sec ON a.IDSection = sec.IDSection
                             JOIN offre o ON sec.IDOffre = o.IDOffre
                             WHERE o.IDEts_Form = :etabId1 AND a.statut = 'actif') as total_stagiaires,
                             
                            (
                                (SELECT COUNT(*)
                                 FROM apprenant_section_semstre_module assm
                                 JOIN apprenant_section_semstre ass ON assm.IDapprenant_Section_semstre = ass.IDapprenant_Section_semstre
                                 JOIN apprenant a ON ass.IDapprenant = a.IDapprenant
                                 JOIN section sec ON a.IDSection = sec.IDSection
                                 JOIN offre o ON sec.IDOffre = o.IDOffre
                                 WHERE o.IDEts_Form = :etabId2)
                                +
                                (SELECT COUNT(*)
                                 FROM apprenant_section_semstre_module assm
                                 JOIN apprenant_fin af ON assm.IDapprenant_Section_semstre = af.IDapprenant_Section_semstre
                                 JOIN apprenant a ON af.IDapprenant = a.IDapprenant
                                 JOIN section sec ON a.IDSection = sec.IDSection
                                 JOIN offre o ON sec.IDOffre = o.IDOffre
                                 WHERE o.IDEts_Form = :etabId3)
                            ) as total_notes,
                            
                            (
                                (SELECT COUNT(*)
                                 FROM apprenant_section_semstre ass
                                 JOIN apprenant a ON ass.IDapprenant = a.IDapprenant
                                 JOIN section sec ON a.IDSection = sec.IDSection
                                 JOIN offre o ON sec.IDOffre = o.IDOffre
                                 WHERE o.IDEts_Form = :etabId4 AND (ass.MoyApr > 0 OR ass.MoyAvr > 0))
                                +
                                (SELECT COUNT(*)
                                 FROM apprenant_fin af
                                 JOIN apprenant a ON af.IDapprenant = a.IDapprenant
                                 JOIN section sec ON a.IDSection = sec.IDSection
                                 JOIN offre o ON sec.IDOffre = o.IDOffre
                                 WHERE o.IDEts_Form = :etabId5 AND (af.MoyFinForm > 0 OR af.MoyGen > 0))
                            ) as resultats_valides,
                            
                            (SELECT COUNT(*) FROM section_semestre ss
                             JOIN section sec ON ss.IDSection = sec.IDSection
                             JOIN offre o ON sec.IDOffre = o.IDOffre
                             WHERE o.IDEts_Form = :etabId6 AND (ss.NumPv IS NOT NULL AND ss.NumPv != '' OR ss.visaevaldir = 1 OR ss.visaevaldfep = 1)) as pvs_approuves
                    ", [
                        'etabId1' => $etabId,
                        'etabId2' => $etabId,
                        'etabId3' => $etabId,
                        'etabId4' => $etabId,
                        'etabId5' => $etabId,
                        'etabId6' => $etabId,
                    ]);
                });
            } else {
                $stats = ['total_stagiaires' => 0, 'total_notes' => 0, 'resultats_valides' => 0, 'pvs_approuves' => 0];
            }
        }

        $now = date('Y-m-d H:i:s');
        $hasActiveWindow = false;

        if (in_array($role, ['admin', 'central', 'high_admin', 'secretaire_general', 'ministre'])) {
            $hasActiveWindow = true;
        } else {
            $hasActiveWindow = DB::selectOne("
                SELECT 1 FROM grade_windows 
                WHERE ? BETWEEN date_ouverture AND date_cloture
                  AND (
                      scope_type = 'global'
                      OR (scope_type = 'wilaya' AND scope_id = ?)
                      OR (scope_type = 'etablissement' AND scope_id = ?)
                  )
                LIMIT 1
            ", [$now, $dfepId, $etabId]) !== null;
        }

        $filterOpts = $this->getFilterOptions($selectedWilaya);

        $validatedSemestersRaw = DB::select("
            SELECT s.IDOffre as id_offre, ss.NumSem as num_sem
            FROM section_semestre ss
            JOIN section s ON ss.IDSection = s.IDSection
            WHERE (ss.NumPv IS NOT NULL AND ss.NumPv != '') OR ss.visaevaldir = 1 OR ss.visaevaldfep = 1
        ");

        $validatedSemesters = [];
        foreach ($validatedSemestersRaw as $row) {
            $validatedSemesters[(int)$row->id_offre][] = (int)$row->num_sem;
        }

        return $this->render('admin/grades/index', [
            'title'  => 'نظام التنقيط - SGFEP / MFEP',
            'offres' => $offres,
            'stats'  => $stats,
            'wilayas' => $filterOpts['wilayas'],
            'etablissements' => $filterOpts['etablissements'],
            'years' => $filterOpts['years'],
            'selected_wilaya' => $selectedWilaya,
            'selected_etab' => $selectedEtab,
            'selected_year' => $selectedYear,
            'hasActiveWindow' => $hasActiveWindow,
            'validatedSemesters' => $validatedSemesters,
        ]);
    }

    public function reconduitsIndex(): mixed
    {
        request()->merge(['force_mode_10' => 1]);
        $view = $this->index();
        if (method_exists($view, 'with')) {
            $view->with('title', 'تسجيل نقاط المتربصين المستمرين (التمهين)');
        }
        return $view;
    }

    private function getFilterOptions(?int $selectedWilaya): array {
        $user = session('user');
        $role = strtolower($user['role_code'] ?? '');
        $dfepId = (int)($user['iddfep'] ?? 0);
        $etabId = (int)($user['etablissement_id'] ?? 0);

        // 1. Wilayas
        if (in_array($role, ['admin', 'central', 'high_admin', 'secretaire_general', 'ministre'])) {
            if (request('type') === 'bep') {
                $wilayas = array_map(fn($item) => (array)$item, DB::select("
                    SELECT DISTINCT d.IDDFEP as id, d.Nom as nom 
                    FROM dfep d
                    JOIN etablissement e ON e.IDDFEP = d.IDDFEP
                    JOIN offre o ON o.IDEts_Form = e.IDetablissement
                    WHERE o.IDMode_formation = 8
                    ORDER BY d.Nom
                "));
            } else {
                $wilayas = \Illuminate\Support\Facades\Cache::remember('filter_wilayas', 86400, function() {
                    return array_map(fn($item) => (array)$item, DB::select("SELECT IDDFEP as id, Nom as nom FROM dfep ORDER BY Nom"));
                });
            }
        } elseif ($role === 'dfep' && $dfepId > 0) {
            $wilayas = array_map(fn($item) => (array)$item, DB::select("SELECT IDDFEP as id, Nom as nom FROM dfep WHERE IDDFEP = ?", [$dfepId]));
        } else {
            $etabWilayaId = 0;
            if ($etabId > 0) {
                $etabWilayaId = (int) \Illuminate\Support\Facades\Cache::remember("etab_wilaya_{$etabId}", 86400, function() use ($etabId) {
                    $row = DB::selectOne("SELECT IDDFEP FROM etablissement WHERE IDetablissement = ?", [$etabId]);
                    return $row ? (int)$row->IDDFEP : 0;
                });
            }
            if ($etabWilayaId > 0) {
                $wilayas = array_map(fn($item) => (array)$item, DB::select("SELECT IDDFEP as id, Nom as nom FROM dfep WHERE IDDFEP = ?", [$etabWilayaId]));
            } else {
                $wilayas = [];
            }
        }

        // 2. Etablissements
        $etablissements = [];
        if (in_array($role, ['admin', 'central', 'high_admin', 'secretaire_general', 'ministre'])) {
            if ($selectedWilaya > 0) {
                if (request('type') === 'bep') {
                    $etablissements = array_map(fn($item) => (array)$item, DB::select("
                        SELECT DISTINCT e.IDetablissement as id, e.Nom as nom, e.IDDFEP 
                        FROM etablissement e
                        JOIN offre o ON o.IDEts_Form = e.IDetablissement
                        WHERE e.IDDFEP = ? AND o.IDMode_formation = 8
                        ORDER BY e.Nom
                    ", [$selectedWilaya]));
                } else {
                    $etablissements = \Illuminate\Support\Facades\Cache::remember("filter_etabs_wilaya_{$selectedWilaya}", 3600, function() use ($selectedWilaya) {
                        return array_map(fn($item) => (array)$item, DB::select("SELECT IDetablissement as id, Nom as nom, IDDFEP FROM etablissement WHERE IDDFEP = ? ORDER BY Nom", [$selectedWilaya]));
                    });
                }
            }
        } elseif ($role === 'dfep' && $dfepId > 0) {
            if (request('type') === 'bep') {
                $etablissements = array_map(fn($item) => (array)$item, DB::select("
                    SELECT DISTINCT e.IDetablissement as id, e.Nom as nom, e.IDDFEP 
                    FROM etablissement e
                    JOIN offre o ON o.IDEts_Form = e.IDetablissement
                    WHERE e.IDDFEP = ? AND o.IDMode_formation = 8
                    ORDER BY e.Nom
                ", [$dfepId]));
            } else {
                $etablissements = \Illuminate\Support\Facades\Cache::remember("filter_etabs_wilaya_{$dfepId}", 3600, function() use ($dfepId) {
                    return array_map(fn($item) => (array)$item, DB::select("SELECT IDetablissement as id, Nom as nom, IDDFEP FROM etablissement WHERE IDDFEP = ? ORDER BY Nom", [$dfepId]));
                });
            }
        } else {
            if ($etabId > 0) {
                $etablissements = array_map(fn($item) => (array)$item, DB::select("SELECT IDetablissement as id, Nom as nom, IDDFEP FROM etablissement WHERE IDetablissement = ?", [$etabId]));
            }
        }

        // 3. Sessions (replaced simple years for precise session-month filtering)
        $years = \Illuminate\Support\Facades\Cache::remember('filter_sessions_list', 86400, function() {
            return array_map(fn($item) => (array)$item, DB::select("
                SELECT IDSession as id, Nom as name 
                FROM session 
                WHERE DateD IS NOT NULL AND DateD > '2010-01-01'
                ORDER BY DateD DESC
            "));
        });

        return [
            'wilayas' => $wilayas,
            'etablissements' => $etablissements,
            'years' => $years
        ];
    }

    /**
     * Grade entry form for a specific offre/module
     */
    public function input(): mixed
    {
        $rawOffreId = request()->all()['offre_id'] ?? '';
        $offreId   = is_numeric($rawOffreId) ? (int)$rawOffreId : (\App\Helpers\SecureIdHelper::decrypt($rawOffreId) ?? 0);
        $rawSemestre = request()->all()['semestre'] ?? '';
        $semestre  = is_numeric($rawSemestre) ? (int)$rawSemestre : (\App\Helpers\SecureIdHelper::decrypt($rawSemestre) ?? 1);
        $matiereId = (int)(request()->all()['matiere_id'] ?? 0);

        if (!$offreId) {
            return $this->redirect('/dashboard/grades');
        }

        // Offre metadata
        $offre = (array) DB::selectOne("
            SELECT o.IDOffre as id, s.Nom as spec_ar, s.NbrSem as duree_semestres,
                   e.Nom as etab_nom, o.IDMode_formation as mode_formation,
                   o.IDEts_Form as etablissement_id, e.IDDFEP as dfep_id
            FROM offre o
            JOIN specialite s ON o.IDSpecialite = s.IDSpecialite
            JOIN etablissement e ON o.IDEts_Form = e.IDetablissement
            WHERE o.IDOffre = ?
        ", [$offreId]);

        if (empty($offre)) {
            return $this->redirect('/dashboard/grades');
        }

        $this->validateOffreAccess($offre, $semestre);

        $user = session('user');
        $role = strtolower($user['role_code'] ?? '');

        // Dynamically ensure section semesters and modules exist
        $this->service->ensureSectionSemestreModules($offreId, $semestre);

        // Fetch dynamic modules
        $isTeacher = in_array($role, ['formateur', 'employee']);
        $teacherFilterId = $isTeacher ? (int)$user['id'] : 0;
        
        $matieres = array_map(fn($item) => (array)$item, DB::select("
            SELECT DISTINCT 
                ssm.IDsection_semestre_Module as id,
                ssm.IDModule as module_id,
                ssm.NomMdl as libelle_ar,
                ssm.NomFrMdl as libelle_fr,
                ssm.coef as coefficient,
                CASE 
                    WHEN ssm.NomMdl LIKE '%تربص%' OR ssm.NomFrMdl LIKE '%stage%' OR ssm.NomMdl LIKE '%ميداني%' THEN 'stage_pratique'
                    WHEN ssm.NomMdl LIKE '%مذكرة%' OR ssm.NomFrMdl LIKE '%memoire%' OR ssm.NomMdl LIKE '%تخرج%' THEN 'memoire'
                    ELSE 'theorique'
                END as type_matiere
            FROM section_semestre_module ssm
            JOIN section_semestre ss ON ssm.IDSection_Semestre = ss.IDSection_Semestre
            JOIN section sec ON ss.IDSection = sec.IDSection
            WHERE sec.IDOffre = ? AND ss.NumSem = ?
              AND (? = 0 OR ssm.IDEncadrement = ?)
        ", [$offreId, $semestre, $teacherFilterId, $teacherFilterId]));

        // Fallback placeholders if DB is empty
        if (empty($matieres)) {
            $matieres = [
                ['id' => 1, 'code' => 'M01', 'libelle_ar' => 'وحدة تعليمية 1', 'libelle_fr' => 'Module 1', 'coefficient' => 2, 'type_matiere' => 'theorique'],
                ['id' => 2, 'code' => 'M02', 'libelle_ar' => 'وحدة تعليمية 2', 'libelle_fr' => 'Module 2', 'coefficient' => 3, 'type_matiere' => 'theorique'],
                ['id' => 3, 'code' => 'M03', 'libelle_ar' => 'تطبيق مهني',     'libelle_fr' => 'Pratique',  'coefficient' => 4, 'type_matiere' => 'pratique'],
            ];
        }

        // Validate selected matiere_id
        $isValidMatiere = false;
        foreach ($matieres as $m) {
            if ($m['id'] == $matiereId) {
                $isValidMatiere = true;
                break;
            }
        }
        if ((!$matiereId || !$isValidMatiere) && count($matieres) > 0) {
            $matiereId = $matieres[0]['id'];
        }

        $matiere  = array_values(array_filter($matieres, fn($m) => $m['id'] == $matiereId))[0] ?? null;
        
        $employeurId = isset(request()->all()['employeur_id']) && request()->all()['employeur_id'] !== '' ? (int)request()->all()['employeur_id'] : null;
        
        // Retrieve trainees
        $students = $this->service->getTraineesWithGrades($offreId, $matiereId, $employeurId);

        // Fetch active employers for apprenticeship offers
        $employeurs = [];
        if ((int)$offre['mode_formation'] === 10) {
            $employeurs = array_map(fn($item) => (array)$item, DB::select("
                SELECT DISTINCT e.IDEmployeur as id, e.Nom as nom, e.NomFr as nom_fr
                FROM employeur e
                JOIN apprenant a ON a.IDEmployeur = e.IDEmployeur
                JOIN section s ON a.IDSection = s.IDSection
                WHERE s.IDOffre = ? AND a.statut = 'actif'
                ORDER BY e.Nom
            ", [$offreId]));
        }

        // Read dynamic configuration
        $config = \App\Helpers\GradingConfigHelper::read();

        // Check if grade entry is locked using GradeWindowService
        $windowAccess = \App\Domains\Academic\Services\GradeWindowService::checkAccess($user, $offreId, $semestre);
        $is_locked = !$windowAccess['allow_edit'];

        return $this->render('admin/grades/input', [
            'title'    => 'رصد نقاط المتربصين - السداسي ' . $semestre,
            'offre'    => $offre,
            'config'   => $config,
            'matieres' => $matieres,
            'matiere'  => $matiere,
            'students' => $students,
            'semestre' => $semestre,
            'is_locked' => $is_locked,
            'windowAccess' => $windowAccess,
            'employeurs' => $employeurs,
            'employeur_id' => $employeurId
        ]);
    }

    /**
     * Save submitted grade batch
     */
    public function store(): mixed
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->redirect('/dashboard/grades');
        }

        // CSRF
        $token = request()->all()['csrf_token'] ?? '';
        if (empty($token) || $token !== (csrf_token() ?? '')) {
            session(['flash_error' => 'رمز التحقق من الأمن غير صالح. يرجى المحاولة مجدداً.']);
            return $this->redirect('/dashboard/grades');
        }

        $matiereId = (int)(request()->all()['matiere_id'] ?? 0);
        $rawOffreId = request()->all()['offre_id'] ?? '';
        $offreId   = is_numeric($rawOffreId) ? (int)$rawOffreId : (\App\Helpers\SecureIdHelper::decrypt($rawOffreId) ?? 0);
        $rawSemestre = request()->all()['semestre'] ?? '';
        $semestre  = is_numeric($rawSemestre) ? (int)$rawSemestre : (\App\Helpers\SecureIdHelper::decrypt($rawSemestre) ?? 1);
        $grades    = request()->all()['grades'] ?? [];

        // Strict verification of active grade entry window
        $windowAccess = \App\Domains\Academic\Services\GradeWindowService::checkAccess(session('user'), $offreId, $semestre);
        if (!$windowAccess['allow_edit']) {
            session(['flash_error' => 'فترة رصد النقاط مغلقة حالياً. لا يمكنك حفظ البيانات.']);
            $encOffreId = \App\Helpers\SecureIdHelper::encrypt($offreId);
            $encSemestre = \App\Helpers\SecureIdHelper::encrypt($semestre);
            return $this->redirect("/dashboard/grades/input?offre_id={$encOffreId}&semestre={$encSemestre}&matiere_id={$matiereId}");
        }

        // Offre metadata
        $offre = (array) DB::selectOne("
            SELECT o.IDOffre as id, o.IDMode_formation as mode_formation,
                   o.IDEts_Form as etablissement_id, e.IDDFEP as dfep_id,
                   s.NbrSem as duree_semestres
            FROM offre o
            JOIN specialite s ON o.IDSpecialite = s.IDSpecialite
            JOIN etablissement e ON o.IDEts_Form = e.IDetablissement
            WHERE o.IDOffre = ?
        ", [$offreId]);

        if (empty($offre)) {
            session(['flash_error' => 'التخصص غير موجود.']);
            return $this->redirect('/dashboard/grades');
        }

        $this->validateOffreAccess($offre, $semestre);

        try {
            $count = $this->service->saveGrades(session('user'), $offreId, $matiereId, $semestre, $grades);
            session(['flash_success' => 'تم حفظ نقاط ' . $count . ' متربص بنجاح.']);
        } catch (\Exception $e) {
            session(['flash_error' => 'خطأ في حفظ النقاط: ' . $e->getMessage()]);
        }

        $encOffreId = \App\Helpers\SecureIdHelper::encrypt($offreId);
        $encSemestre = \App\Helpers\SecureIdHelper::encrypt($semestre);
        return $this->redirect("/dashboard/grades/input?offre_id={$encOffreId}&semestre={$encSemestre}&matiere_id={$matiereId}");
    }

    public function transcript(string|int $id): mixed
    {
        return $this->redirect('/dashboard/grades');
    }

    public function deliberation(): mixed
    {
        $rawOffreId = request()->all()['offre_id'] ?? '';
        $offreId = is_numeric($rawOffreId) ? (int)$rawOffreId : (\App\Helpers\SecureIdHelper::decrypt($rawOffreId) ?? 0);
        $rawSemestre = request()->all()['semestre'] ?? '';
        $semestre = is_numeric($rawSemestre) ? (int)$rawSemestre : (\App\Helpers\SecureIdHelper::decrypt($rawSemestre) ?? 1);

        if (!$offreId) {
            return $this->redirect('/dashboard/grades');
        }

        // Fetch offer
        $offre = (array) DB::selectOne("
            SELECT o.IDOffre as id, s.Nom as spec_ar, s.NomFr as spec_fr, s.CodeSpec as spec_code,
                   s.NbrSem as duree_semestres,
                   CASE
                       WHEN s.NbrSem >= 5 THEN 'BTS'
                       WHEN s.NbrSem = 4 THEN 'BTS'
                       WHEN s.NbrSem = 3 THEN 'TS'
                       WHEN s.NbrSem = 2 THEN 'CMP'
                       WHEN s.NbrSem = 1 THEN 'Qualifiant'
                       ELSE 'CAP'
                   END as diplome_vise,
                   e.Nom as etab_ar, e.NomFr as etab_fr,
                   o.IDEts_Form as etablissement_id, e.IDDFEP as dfep_id,
                   o.IDMode_formation as mode_formation,
                   o.DateD as date_debut, o.DateF as date_fin
            FROM offre o
            JOIN specialite s ON o.IDSpecialite = s.IDSpecialite
            JOIN etablissement e ON o.IDEts_Form = e.IDetablissement
            WHERE o.IDOffre = ?
        ", [$offreId]);

        if (empty($offre)) {
            return $this->redirect('/dashboard/grades');
        }

        $this->validateOffreAccess($offre, $semestre);

        // Dynamically ensure section semesters and modules exist
        $this->service->ensureSectionSemestreModules($offreId, $semestre);

        // Fetch modules
        $matieres = array_map(fn($item) => (array)$item, DB::select("
            SELECT DISTINCT 
                ssm.IDsection_semestre_Module as id,
                ssm.IDModule as module_id,
                ssm.NomMdl as libelle_ar,
                ssm.NomFrMdl as libelle_fr,
                ssm.coef as coefficient,
                ssm.NomMdl as code
            FROM section_semestre_module ssm
            JOIN section_semestre ss ON ssm.IDSection_Semestre = ss.IDSection_Semestre
            JOIN section sec ON ss.IDSection = sec.IDSection
            WHERE sec.IDOffre = ? AND ss.NumSem = ?
        ", [$offreId, $semestre]));

        // Fetch trainees
        $trainees = array_map(fn($item) => (array)$item, DB::select("
            SELECT a.IDapprenant as id, a.Nccp as matricule, 
                   c.Nom as nom_ar, c.Prenom as prenom_ar, c.Civ as sexe,
                   COALESCE(ass.IDapprenant_Section_semstre, af.IDapprenant_Section_semstre) as ass_id,
                   COALESCE(ass.NoteStage, 0) as note_stage,
                   COALESCE(ass.NoteMemoire, 0) as note_memoire,
                   COALESCE(ass.NoteSoutenance, 0) as note_soutenance,
                   ass.DateAbdech as date_abandon
            FROM apprenant a
            LEFT JOIN candidat c ON a.IDCandidat = c.IDCandidat
            JOIN section s ON a.IDSection = s.IDSection
            JOIN section_semestre ss ON s.IDSection = ss.IDSection
            LEFT JOIN apprenant_section_semstre ass ON a.IDapprenant = ass.IDapprenant AND ass.IDSection_Semestre = ss.IDSection_Semestre
            LEFT JOIN apprenant_fin af ON a.IDapprenant = af.IDapprenant AND af.IDSection_Semestre = ss.IDSection_Semestre
            WHERE s.IDOffre = ? AND ss.NumSem = ? AND a.statut = 'actif'
            ORDER BY c.Nom, c.Prenom
        ", [$offreId, $semestre]));

        $config = \App\Helpers\GradingConfigHelper::read();
        $gradingService = new \App\Domains\Academic\Services\GradingSystemService();

        $rows = [];
        $nbAdmis = 0;
        $nbAjournes = 0;
        $nbExclus = 0;
        $rang = 1;

        foreach ($trainees as $stg) {
            $assId = (int)($stg['ass_id'] ?? 0);
            $marks = [];
            $modulesForGpa = [];

            $gradesBySsm = [];
            if ($assId > 0) {
                $gradesList = array_map(fn($item) => (array)$item, DB::select("
                    SELECT IDsection_semestre_Module as ssm_id, NoteC1 as cc1, NoteC2 as cc2, NoteCs as exam, NoteR as rattrapage
                    FROM apprenant_section_semstre_module
                    WHERE IDapprenant_Section_semstre = ?
                ", [$assId]));
                foreach ($gradesList as $gl) {
                    $gradesBySsm[$gl['ssm_id']] = $gl;
                }
            }

            $hasElimination = false;
            foreach ($matieres as $m) {
                $g = $gradesBySsm[$m['id']] ?? null;
                $typeM = (strpos(strtolower($m['libelle_ar']), 'stage') !== false || strpos(strtolower($m['libelle_fr']), 'stage') !== false) ? 'stage_pratique' :
                         ((strpos(strtolower($m['libelle_ar']), 'memoire') !== false || strpos(strtolower($m['libelle_fr']), 'memoire') !== false) ? 'memoire' : 'theorique');

                $calc = $gradingService->calculateModuleGrade([
                    'type_matiere' => $typeM,
                    'cc1' => $g['cc1'] ?? null,
                    'cc2' => $g['cc2'] ?? null,
                    'exam' => $g['exam'] ?? null,
                    'rattrapage' => $g['rattrapage'] ?? null,
                    'stage' => $stg['note_stage'] ?? null,
                    'memoire' => $stg['note_memoire'] ?? null,
                    'soutenance' => $stg['note_soutenance'] ?? null,
                ], $config, (int)$offre['mode_formation']);

                $marks[$m['id']] = $calc['moy_apr'];
                if ($calc['is_eliminated']) {
                    $hasElimination = true;
                }

                $modulesForGpa[] = [
                    'coefficient' => $m['coefficient'],
                    'note_avr' => $calc['moy_avr'],
                    'note_apr' => $calc['moy_apr']
                ];
            }

            $semCalc = $gradingService->calculateSemesterGpa($modulesForGpa, $stg['note_stage'], $offre['mode_formation'], $config);
            $gpa = $semCalc['gpa_apr'];

            if ($hasElimination) {
                $decision = 'مقصى';
                $nbExclus++;
            } elseif ($semCalc['is_admis']) {
                $decision = 'مقبول';
                $nbAdmis++;
            } else {
                $decision = 'مؤجل';
                $nbAjournes++;
            }

            $fullName = trim(($stg['nom_ar'] ?? '') . ' ' . ($stg['prenom_ar'] ?? ''));
            if (empty($fullName)) {
                $fullName = 'متربص #' . $stg['id'];
            }
            $rows[] = [
                'id' => $stg['id'],
                'rang' => $rang++,
                'matricule' => $stg['matricule'],
                'nom_ar' => $fullName,
                'sexe' => (in_array(strtolower(trim($stg['sexe'] ?? '')), ['m', 'ذكر', '1'])) ? 'ذكر' : 'أنثى',
                'marks' => $marks,
                'average' => $gpa,
                'decision' => $decision
            ];
        }

        $total = count($rows);
        $txReuss = $total > 0 ? round(($nbAdmis / $total) * 100, 2) : 0;

        return $this->render('admin/grades/deliberations', [
            'offre' => $offre,
            'config' => $config,
            'semestre' => $semestre,
            'matieres' => $matieres,
            'rows' => $rows,
            'nbAdmis' => $nbAdmis,
            'nbAjournes' => $nbAjournes,
            'nbExclus' => $nbExclus,
            'total' => $total,
            'txReuss' => $txReuss
        ]);
    }

    public function progress(): mixed
    {
        $user = session('user');
        $role = strtolower($user['role_code'] ?? '');
        $etabId = (int)($user['etablissement_id'] ?? $user['IDEts_Form'] ?? 0);
        $dfepId = (int)($user['iddfep'] ?? $user['IDDFEP'] ?? 0);

        $selectedWilaya = null;
        $selectedEtab = null;
        $selectedYear = request('filter_year') ? (int)request('filter_year') : null;
        $selectedSemestre = request('filter_semestre') ? (int)request('filter_semestre') : null;

        if (in_array($role, ['admin', 'central', 'high_admin', 'secretaire_general', 'ministre'])) {
            $selectedWilaya = request('filter_wilaya') ? (int)request('filter_wilaya') : null;
            $selectedEtab = request('filter_etab') ? (int)request('filter_etab') : null;
        } elseif ($role === 'dfep' && $dfepId) {
            $selectedWilaya = $dfepId;
            $selectedEtab = request('filter_etab') ? (int)request('filter_etab') : null;
        } else {
            $selectedEtab = $etabId > 0 ? (int)$etabId : null;
            if ($selectedEtab > 0) {
                $selectedWilaya = (int) \Illuminate\Support\Facades\Cache::remember("etab_wilaya_{$selectedEtab}", 86400, function() use ($selectedEtab) {
                    $row = DB::selectOne("SELECT IDDFEP FROM etablissement WHERE IDetablissement = ?", [$selectedEtab]);
                    return $row ? (int)$row->IDDFEP : 0;
                });
            }
        }

        $countWhere = [];
        $countParams = [];

        $isMode10 = ((int)($user['IDMode_formation'] ?? 0) === 10 || strtolower($user['role_fr'] ?? '') === 'apprentissage');
        if ($isMode10) {
            $countWhere[] = "o.IDMode_formation = 10";
            $needSectionSemestre = true;
            $needSection = true;
            $needOffre = true;
        }

        if ($selectedWilaya > 0) {
            $countWhere[] = "e.IDDFEP = ?";
            $countParams[] = $selectedWilaya;
        }
        if ($selectedEtab > 0) {
            $countWhere[] = "o.IDEts_Form = ?";
            $countParams[] = $selectedEtab;
        }
        if ($selectedYear > 0) {
            $countWhere[] = "YEAR(sess.DateD) = ?";
            $countParams[] = $selectedYear;
        }
        if ($selectedSemestre > 0) {
            $countWhere[] = "ss.NumSem = ?";
            $countParams[] = $selectedSemestre;
        }

        $req = request()->all();
        if (!empty($req['search'])) {
            $searchVal = '%' . trim($req['search']) . '%';
            $countWhere[] = "(ssm.NomMdl LIKE ? OR sec.Nom LIKE ? OR sp.Nom LIKE ? OR e.Nom LIKE ? OR enc.Nom LIKE ? OR enc.Prenom LIKE ?)";
            array_push($countParams, $searchVal, $searchVal, $searchVal, $searchVal, $searchVal, $searchVal);
        }

        // Determine which joins are needed for counting
        $needSectionSemestre = false;
        $needSection = false;
        $needOffre = false;
        $needSession = false;
        $needSpecialite = false;
        $needEtablissement = false;
        $needEncadrement = false;

        if ($selectedWilaya > 0) {
            $needSectionSemestre = true;
            $needSection = true;
            $needOffre = true;
            $needEtablissement = true;
        }
        if ($selectedEtab > 0) {
            $needSectionSemestre = true;
            $needSection = true;
            $needOffre = true;
        }
        if ($selectedYear > 0) {
            $needSectionSemestre = true;
            $needSection = true;
            $needOffre = true;
            $needSession = true;
        }
        if ($selectedSemestre > 0) {
            $needSectionSemestre = true;
        }
        if (!empty($req['search'])) {
            $needSectionSemestre = true;
            $needSection = true;
            $needOffre = true;
            $needSpecialite = true;
            $needEtablissement = true;
            $needEncadrement = true;
        }

        // Build COUNT query dynamically using normal JOIN
        $countSql = "SELECT COUNT(*) as cnt FROM section_semestre_module ssm";
        if ($needSectionSemestre) {
            $countSql .= " JOIN section_semestre ss ON ssm.IDSection_Semestre = ss.IDSection_Semestre";
        }
        if ($needSection) {
            $countSql .= " JOIN section sec ON ss.IDSection = sec.IDSection";
        }
        if ($needOffre) {
            $countSql .= " JOIN offre o ON sec.IDOffre = o.IDOffre";
        }
        if ($needSession) {
            $countSql .= " JOIN session sess ON o.IDSession = sess.IDSession";
        }
        if ($needSpecialite) {
            $countSql .= " JOIN specialite sp ON o.IDSpecialite = sp.IDSpecialite";
        }
        if ($needEtablissement) {
            $countSql .= " JOIN etablissement e ON o.IDEts_Form = e.IDetablissement";
        }
        if ($needEncadrement) {
            $countSql .= " LEFT JOIN encadrement enc ON ssm.IDEncadrement = enc.IDEncadrement";
        }

        if (!empty($countWhere)) {
            $countSql .= " WHERE " . implode(" AND ", $countWhere);
        }

        $total = DB::selectOne($countSql, $countParams)->cnt;

        // Determine join type (STRAIGHT_JOIN for fast index scan if unfiltered, JOIN if filtered)
        $joinType = "JOIN";
        if (empty($countWhere)) {
            $joinType = "STRAIGHT_JOIN";
        }

        $perPage = 50;
        $currentPage = \Illuminate\Pagination\LengthAwarePaginator::resolveCurrentPage();
        $offset = ($currentPage - 1) * $perPage;

        $sql = "
            SELECT 
                ssm.IDsection_semestre_Module as ssm_id,
                ssm.NomMdl as module_nom,
                sec.Nom as section_nom,
                ss.NumSem as semestre,
                o.IDOffre as offre_id,
                sp.Nom as specialite_nom,
                e.Nom as etab_nom,
                enc.Nom as teacher_nom,
                enc.Prenom as teacher_prenom,
                (SELECT COUNT(*) FROM apprenant a WHERE a.IDSection = sec.IDSection AND a.statut = 'actif') as total_students,
                ((SELECT COUNT(DISTINCT a.IDapprenant) 
                  FROM apprenant a
                  JOIN apprenant_section_semstre ass 
                         ON ass.IDapprenant = a.IDapprenant 
                        AND ass.IDSection_Semestre = ss.IDSection_Semestre
                  JOIN apprenant_section_semstre_module assm 
                         ON assm.IDapprenant_Section_semstre = ass.IDapprenant_Section_semstre
                  WHERE a.IDSection = sec.IDSection 
                    AND a.statut = 'actif'
                    AND assm.IDsection_semestre_Module = ssm.IDsection_semestre_Module 
                    AND (assm.NoteCs IS NOT NULL OR assm.NoteC1 IS NOT NULL OR assm.NoteC2 IS NOT NULL OR assm.MoyApr > 0))
                 +
                 (SELECT COUNT(DISTINCT a.IDapprenant) 
                  FROM apprenant a
                  JOIN apprenant_fin af 
                         ON af.IDapprenant = a.IDapprenant 
                        AND af.IDSection_Semestre = ss.IDSection_Semestre
                  JOIN apprenant_section_semstre_module assm 
                         ON assm.IDapprenant_Section_semstre = af.IDapprenant_Section_semstre
                  WHERE a.IDSection = sec.IDSection 
                    AND a.statut = 'actif'
                    AND assm.IDsection_semestre_Module = ssm.IDsection_semestre_Module 
                    AND (assm.NoteCs IS NOT NULL OR assm.NoteC1 IS NOT NULL OR assm.NoteC2 IS NOT NULL OR assm.MoyApr > 0))) as graded_students
            FROM section_semestre_module ssm
            {$joinType} section_semestre ss ON ssm.IDSection_Semestre = ss.IDSection_Semestre
            {$joinType} section sec ON ss.IDSection = sec.IDSection
            {$joinType} offre o ON sec.IDOffre = o.IDOffre
            {$joinType} session sess ON o.IDSession = sess.IDSession
            {$joinType} specialite sp ON o.IDSpecialite = sp.IDSpecialite
            {$joinType} etablissement e ON o.IDEts_Form = e.IDetablissement
            LEFT JOIN encadrement enc ON ssm.IDEncadrement = enc.IDEncadrement
        ";

        if (!empty($countWhere)) {
            $sql .= " WHERE " . implode(" AND ", $countWhere);
        }

        $sql .= "
            ORDER BY ssm.IDsection_semestre_Module DESC
            LIMIT ? OFFSET ?
        ";

        $pageParams = array_merge($countParams, [$perPage, $offset]);
        $items = array_map(fn($item) => (array)$item, DB::select($sql, $pageParams));

        $progressData = new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $currentPage,
            ['path' => \Illuminate\Pagination\LengthAwarePaginator::resolveCurrentPath()]
        );
        $progressData->withQueryString();

        $filterOpts = $this->getFilterOptions($selectedWilaya);

        return $this->render('admin/grades/progress', [
            'title' => 'متابعة تقدم رصد علامات الامتحانات والتقييمات',
            'progressData' => $progressData,
            'wilayas' => $filterOpts['wilayas'],
            'etablissements' => $filterOpts['etablissements'],
            'years' => $filterOpts['years'],
            'role_code' => $role,
            'dfep_id' => $dfepId,
            'selected_wilaya' => $selectedWilaya,
            'selected_etab' => $selectedEtab,
            'selected_year' => $selectedYear,
            'selected_semestre' => $selectedSemestre,
        ]);
    }

    public function gradingControl(): mixed
    {
        $user = session('user');
        $role = strtolower($user['role_code'] ?? '');
        if ($role !== 'admin') {
            return $this->redirect('/dashboard/grades');
        }

        $config = \App\Helpers\GradingConfigHelper::read();
        
        $establishments = array_map(fn($item) => (array)$item, DB::select("SELECT IDetablissement as id, Nom as nom FROM etablissement ORDER BY Nom"));

        $trainingModes = [
            1 => 'حضوري أولي',
            2 => 'التكوين المهني المتواصل',
            3 => 'الدروس المسائية',
            4 => 'في المؤسسات العقابية',
            5 => 'تكوين المرأة الماكثة في البيت',
            6 => 'التكوين التعاقدي',
            7 => 'التكوين عن طريق المعابر',
            8 => 'التعليم المهني',
            9 => 'تأهيلي أولي',
            10 => 'تكوين عن طريق التمهين',
            11 => 'محو الأمية - تأهيل مهني',
            12 => 'المؤسسات الخاصة المعتمدة',
            13 => 'فرع منتدب',
            14 => 'فرع في الوسط الريفي',
            15 => 'المعاقين حركياً',
            16 => 'المعاقين سمعياً',
            17 => 'المعاقين بصرياً',
            18 => 'عن بعد تكوين تعاقدي',
            19 => 'التكوين المهني لإعادة التكييف',
            20 => 'التكوين حسب الطلب',
            21 => 'عن بعد',
            22 => 'تكوين المستفيدين من منحة البطالة',
            23 => 'المترشحين الأحرار',
        ];

        $selectedModeId = isset(request()->all()['mode_id']) ? (int)request()->all()['mode_id'] : 1;
        if (!isset($trainingModes[$selectedModeId])) {
            $selectedModeId = 1;
        }

        return $this->render('admin/grades/grading_control', [
            'title' => 'لوحة تحكم إعدادات ومحددات نظام التقييم والمعدلات',
            'config' => $config,
            'establishments' => $establishments,
            'trainingModes' => $trainingModes,
            'selectedModeId' => $selectedModeId
        ]);
    }

    public function saveGradingControl(): mixed
    {
        $user = session('user');
        $role = strtolower($user['role_code'] ?? '');
        if ($role !== 'admin') {
            return $this->redirect('/dashboard/grades');
        }

        $token = request()->all()['csrf_token'] ?? '';
        if (empty($token) || $token !== (csrf_token() ?? '')) {
            session(['flash_error' => 'رمز التحقق من الأمن غير صالح.']);
            return $this->redirect('/dashboard/grades/control');
        }

        $config = \App\Helpers\GradingConfigHelper::read();

        $modeId = isset(request()->all()['mode_id']) ? (int)request()->all()['mode_id'] : 1;
        if (!isset($config['modes'])) {
            $config['modes'] = [];
        }
        if (!isset($config['modes'][$modeId])) {
            $config['modes'][$modeId] = [];
        }

        // Save mode-specific configurations
        $config['modes'][$modeId]['continuous_assessment_weight'] = isset(request()->all()['continuous_assessment_weight']) ? (float)request()->all()['continuous_assessment_weight'] : ($config['modes'][$modeId]['continuous_assessment_weight'] ?? $config['module_grade']['continuous_assessment_weight'] ?? 0.4);
        $config['modes'][$modeId]['quiz_weight'] = isset(request()->all()['quiz_weight']) ? (float)request()->all()['quiz_weight'] : ($config['modes'][$modeId]['quiz_weight'] ?? $config['module_grade']['quiz_weight'] ?? 0.4);
        $config['modes'][$modeId]['exam_weight'] = isset(request()->all()['exam_weight']) ? (float)request()->all()['exam_weight'] : ($config['modes'][$modeId]['exam_weight'] ?? $config['module_grade']['exam_weight'] ?? 0.6);
        $config['modes'][$modeId]['divisor'] = isset(request()->all()['divisor']) ? (float)request()->all()['divisor'] : ($config['modes'][$modeId]['divisor'] ?? $config['module_grade']['divisor'] ?? 1.0);
        $config['modes'][$modeId]['passing_threshold'] = isset(request()->all()['passing_threshold']) ? (float)request()->all()['passing_threshold'] : ($config['modes'][$modeId]['passing_threshold'] ?? $config['remedial']['passing_threshold'] ?? 10.0);
        $config['modes'][$modeId]['passing_gpa_threshold'] = isset(request()->all()['passing_gpa_threshold']) ? (float)request()->all()['passing_gpa_threshold'] : ($config['modes'][$modeId]['passing_gpa_threshold'] ?? $config['semester']['passing_gpa_threshold'] ?? 10.0);
        $config['modes'][$modeId]['elimination_threshold'] = isset(request()->all()['elimination_threshold']) ? (float)request()->all()['elimination_threshold'] : ($config['modes'][$modeId]['elimination_threshold'] ?? $config['semester']['elimination_threshold'] ?? 5.0);
        $config['modes'][$modeId]['company_coefficient'] = isset(request()->all()['company_coefficient']) ? (float)request()->all()['company_coefficient'] : ($config['modes'][$modeId]['company_coefficient'] ?? $config['semester']['apprenticeship']['company_coefficient'] ?? 4.0);

        // Distance learning mode specific configurations
        $config['modes'][$modeId]['dl_platform_activity'] = isset(request()->all()['dl_platform_activity']) ? (float)request()->all()['dl_platform_activity'] : ($config['modes'][$modeId]['dl_platform_activity'] ?? $config['distance_learning']['weights']['platform_activity'] ?? 0.3);
        $config['modes'][$modeId]['dl_assignments'] = isset(request()->all()['dl_assignments']) ? (float)request()->all()['dl_assignments'] : ($config['modes'][$modeId]['dl_assignments'] ?? $config['distance_learning']['weights']['assignments'] ?? 0.3);
        $config['modes'][$modeId]['dl_written_exam'] = isset(request()->all()['dl_written_exam']) ? (float)request()->all()['dl_written_exam'] : ($config['modes'][$modeId]['dl_written_exam'] ?? $config['distance_learning']['weights']['written_exam'] ?? 0.4);

        $config['workflow']['grading_start_date'] = request()->all()['grading_start_date'] ?? $config['workflow']['grading_start_date'];
        $config['workflow']['grading_end_date'] = request()->all()['grading_end_date'] ?? $config['workflow']['grading_end_date'];
        $config['workflow']['final_validation_active'] = isset(request()->all()['final_validation_active']) && request()->all()['final_validation_active'] === '1';

        $allowed = request()->all()['remedial_allowed_establishments'] ?? [];
        if (!is_array($allowed)) {
            $allowed = [];
        }
        $config['workflow']['remedial_allowed_establishments'] = array_map('intval', $allowed);

        if (\App\Helpers\GradingConfigHelper::write($config)) {
            session(['flash_success' => 'تم حفظ إعدادات نظام رصد النقاط بنجاح.']);
        } else {
            session(['flash_error' => 'حدث خطأ أثناء كتابة ملف الإعدادات.']);
        }

        return $this->redirect('/dashboard/grades/control');
    }

    /**
     * AJAX endpoint to fetch active employers for an offer
     */
    public function getEmployeurs(): mixed
    {
        $rawOffreId = request()->all()['offre_id'] ?? '';
        $offreId = is_numeric($rawOffreId) ? (int)$rawOffreId : (\App\Helpers\SecureIdHelper::decrypt($rawOffreId) ?? 0);
        
        $employeurs = array_map(fn($item) => (array)$item, DB::select("
            SELECT DISTINCT e.IDEmployeur as id, e.Nom as nom, e.NomFr as nom_fr
            FROM employeur e
            JOIN apprenant a ON a.IDEmployeur = e.IDEmployeur
            JOIN section s ON a.IDSection = s.IDSection
            WHERE s.IDOffre = ? AND a.statut = 'actif'
            ORDER BY e.Nom
        ", [$offreId]));
        
        header('Content-Type: application/json');
        echo json_encode($employeurs);
        exit;
    }

    public function windows(): mixed
    {
        $user = session('user');
        $role = strtolower($user['role_code'] ?? '');
        if ($role !== 'admin') {
            return $this->redirect('/dashboard/grades');
        }

        // Fetch all windows
        $windows = array_map(fn($item) => (array)$item, DB::select("
            SELECT w.*, u.Nom as creator_name
            FROM grade_windows w
            LEFT JOIN utilisateur u ON w.created_by = u.IDUtilisateur
            ORDER BY w.date_ouverture DESC
        "));

        // Fetch wilayas and establishments for selection scope
        $wilayas = array_map(fn($item) => (array)$item, DB::select("SELECT IDDFEP as id, Nom as nom FROM dfep ORDER BY Nom"));
        $etablissements = array_map(fn($item) => (array)$item, DB::select("SELECT IDetablissement as id, Nom as nom FROM etablissement ORDER BY Nom"));

        return $this->render('admin/grades/windows', [
            'title' => 'إدارة فترات ونوافذ رصد النقاط',
            'windows' => $windows,
            'wilayas' => $wilayas,
            'etablissements' => $etablissements
        ]);
    }

    public function storeWindow(): mixed
    {
        $user = session('user');
        $role = strtolower($user['role_code'] ?? '');
        if ($role !== 'admin') {
            return $this->redirect('/dashboard/grades');
        }

        $token = request()->all()['csrf_token'] ?? '';
        if (empty($token) || $token !== (csrf_token() ?? '')) {
            session(['flash_error' => 'رمز التحقق من الأمن غير صالح.']);
            return $this->redirect('/dashboard/grades/windows');
        }

        $label = trim(request()->all()['label'] ?? '');
        $semestre = request()->all()['semestre'] !== '' ? (int)request()->all()['semestre'] : null;
        $scopeType = request()->all()['scope_type'] ?? 'global';
        $scopeId = null;

        if ($scopeType === 'wilaya') {
            $scopeId = (int)(request()->all()['wilaya_id'] ?? 0);
        } elseif ($scopeType === 'etablissement') {
            $scopeId = (int)(request()->all()['etablissement_id'] ?? 0);
        }

        $dateOuv = request()->all()['date_ouverture'] ?? '';
        $dateClo = request()->all()['date_cloture'] ?? '';

        if (!$label || !$dateOuv || !$dateClo) {
            session(['flash_error' => 'يرجى ملء جميع الحقول المطلوبة بشكل صحيح.']);
            return $this->redirect('/dashboard/grades/windows');
        }

        // Format dates to standard format
        $dateOuv = date('Y-m-d H:i:s', strtotime($dateOuv));
        $dateClo = date('Y-m-d H:i:s', strtotime($dateClo));

        DB::insert("
            INSERT INTO grade_windows (label, semestre, scope_type, scope_id, date_ouverture, date_cloture, allow_edit, created_by, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, 1, ?, NOW(), NOW())
        ", [$label, $semestre, $scopeType, $scopeId, $dateOuv, $dateClo, $user['id']]);

        session(['flash_success' => 'تم إنشاء فترة رصد جديدة بنجاح.']);
        return $this->redirect('/dashboard/grades/windows');
    }

    public function deleteWindow(): mixed
    {
        $user = session('user');
        $role = strtolower($user['role_code'] ?? '');
        if ($role !== 'admin') {
            return $this->redirect('/dashboard/grades');
        }

        $id = request()->route('id') ?? (request()->all()['id'] ?? 0);

        DB::delete("DELETE FROM grade_windows WHERE id = ?", [$id]);

        session(['flash_success' => 'تم حذف فترة الرصد بنجاح.']);
        return $this->redirect('/dashboard/grades/windows');
    }

    /**
     * Confirm deliberation and transition successful trainees
     */
    public function confirmDeliberation(): mixed
    {
        $rawOffreId = request()->all()['offre_id'] ?? '';
        $offreId = is_numeric($rawOffreId) ? (int)$rawOffreId : (\App\Helpers\SecureIdHelper::decrypt($rawOffreId) ?? 0);
        $rawSemestre = request()->all()['semestre'] ?? '';
        $semestre = is_numeric($rawSemestre) ? (int)$rawSemestre : (\App\Helpers\SecureIdHelper::decrypt($rawSemestre) ?? 1);

        if (!$offreId) {
            return $this->redirect('/dashboard/grades');
        }

        $decisions = request()->all()['decisions'] ?? []; // Array of IDapprenant => Decision (مقبول, مؤجل, مقصى, تخلى)
        $abandonDates = request()->all()['abandon_dates'] ?? []; // Array of IDapprenant => Date (YYYY-MM-DD)
        $nextSem = $semestre + 1;

        // Fetch maximum semesters of the specialty
        $specMaxSem = 5; // Default fallback
        $specRow = DB::selectOne("
            SELECT s.NbrSem 
            FROM specialite s
            JOIN offre o ON o.IDSpecialite = s.IDSpecialite
            WHERE o.IDOffre = ?
        ", [$offreId]);
        if ($specRow) {
            $specMaxSem = (int)$specRow->NbrSem;
        }

        DB::transaction(function() use ($offreId, $semestre, $nextSem, $specMaxSem, $decisions, $abandonDates) {
            // Always ensure next semester is ready if it doesn't exceed specialty limit
            if ($nextSem <= $specMaxSem) {
                $this->service->ensureSectionSemestreModules($offreId, $nextSem);
            }

            foreach ($decisions as $apprenantId => $decision) {
                $apprenantId = (int)$apprenantId;
                
                // Find student IDSection
                $student = DB::selectOne("SELECT IDSection, statut FROM apprenant WHERE IDapprenant = ?", [$apprenantId]);
                if (!$student) continue;

                // Find or create current semester IDSection_Semestre
                $ssRow = DB::selectOne("SELECT IDSection_Semestre FROM section_semestre WHERE IDSection = ? AND NumSem = ?", [$student->IDSection, $semestre]);
                if (!$ssRow) continue;
                $currentSsId = $ssRow->IDSection_Semestre;

                // Find or create apprenant_section_semstre row for current semester
                $assRow = DB::selectOne("
                    SELECT IDapprenant_Section_semstre 
                    FROM apprenant_section_semstre 
                    WHERE IDapprenant = ? AND IDSection_Semestre = ?
                ", [$apprenantId, $currentSsId]);

                $decisionId = 1;
                $obsText = 'ناجح';

                if ($decision === 'مؤجل') {
                    $decisionId = 4;
                    $obsText = 'راسب';
                } elseif ($decision === 'مقصى') {
                    $decisionId = 8;
                    $obsText = 'مفصول';
                } elseif ($decision === 'تخلى') {
                    $decisionId = 6;
                    $obsText = 'متخلي';
                }

                $abandonDate = null;
                if ($decision === 'تخلى') {
                    $abandonDate = $abandonDates[$apprenantId] ?? null;
                    if (empty($abandonDate)) {
                        $abandonDate = null;
                    }
                }

                if ($assRow) {
                    DB::update("
                        UPDATE apprenant_section_semstre 
                        SET IDDecision_evals = ?, Obs = ?, DateAbdech = ? 
                        WHERE IDapprenant_Section_semstre = ?
                    ", [$decisionId, $obsText, $abandonDate, $assRow->IDapprenant_Section_semstre]);
                } else {
                    $maxAssId = (int)DB::selectOne("SELECT COALESCE(MAX(IDapprenant_Section_semstre), 0) as max_id FROM apprenant_section_semstre")->max_id;
                    $newAssId = $maxAssId + 1;
                    DB::insert("
                        INSERT INTO apprenant_section_semstre (IDapprenant_Section_semstre, IDapprenant, IDSection_Semestre, IDDecision_evals, Obs, DateAbdech)
                        VALUES (?, ?, ?, ?, ?, ?)
                    ", [$newAssId, $apprenantId, $currentSsId, $decisionId, $obsText, $abandonDate]);
                }

                // If abandoned (تخلى), update status in apprenant table
                if ($decision === 'تخلى') {
                    DB::update("UPDATE apprenant SET statut = 'abandon' WHERE IDapprenant = ?", [$apprenantId]);
                }

                // If successful (مقبول), transition to the next semester (if it exists)
                if ($decision === 'مقبول' && $nextSem <= $specMaxSem) {
                    // Find next semester IDSection_Semestre
                    $nextSsRow = DB::selectOne("SELECT IDSection_Semestre FROM section_semestre WHERE IDSection = ? AND NumSem = ?", [$student->IDSection, $nextSem]);
                    if ($nextSsRow) {
                        $nextSsId = $nextSsRow->IDSection_Semestre;

                        // Check if enrollment in next semester already exists
                        $nextAssRow = DB::selectOne("
                            SELECT IDapprenant_Section_semstre 
                            FROM apprenant_section_semstre 
                            WHERE IDapprenant = ? AND IDSection_Semestre = ?
                        ", [$apprenantId, $nextSsId]);

                        if (!$nextAssRow) {
                            $maxAssId = (int)DB::selectOne("SELECT COALESCE(MAX(IDapprenant_Section_semstre), 0) as max_id FROM apprenant_section_semstre")->max_id;
                            $newNextAssId = $maxAssId + 1;
                            DB::insert("
                                INSERT INTO apprenant_section_semstre (IDapprenant_Section_semstre, IDapprenant, IDSection_Semestre, IDDecision_evals, Obs)
                                VALUES (?, ?, ?, 0, 'en_attente')
                            ", [$newNextAssId, $apprenantId, $nextSsId]);
                        }
                    }
                }
            }
        });

        $successMsg = 'تم المصادقة على نتائج المداولات وتثبيتها بنجاح ونقل المتربصين للسداسي الموالي!';
        session([
            'success' => $successMsg,
            'flash_success' => $successMsg
        ]);

        if ($nextSem <= $specMaxSem) {
            $secureOffreId = \App\Helpers\SecureIdHelper::encrypt($offreId);
            return $this->redirect("/dashboard/grades/semestre-setup?offre_id={$secureOffreId}&semestre={$nextSem}");
        }

        return $this->redirect('/dashboard/grades');
    }

    /**
     * View to setup Next Semester (Dates & Teacher assignments)
     */
    public function semestreSetup(): mixed
    {
        $rawOffreId = request()->all()['offre_id'] ?? '';
        $offreId = is_numeric($rawOffreId) ? (int)$rawOffreId : (\App\Helpers\SecureIdHelper::decrypt($rawOffreId) ?? 0);
        $semestre = (int)(request()->all()['semestre'] ?? 1);

        if (!$offreId) {
            return $this->redirect('/dashboard/grades');
        }

        // Fetch offer details
        $offre = (array) DB::selectOne("
            SELECT o.IDOffre as id, s.Nom as spec_ar, sec.Nom as section_nom,
                   o.IDEts_Form as etablissement_id, e.Nom as etab_nom
            FROM offre o
            JOIN specialite s ON o.IDSpecialite = s.IDSpecialite
            JOIN section sec ON o.IDOffre = sec.IDOffre
            JOIN etablissement e ON o.IDEts_Form = e.IDetablissement
            WHERE o.IDOffre = ?
        ", [$offreId]);

        if (empty($offre)) {
            return $this->redirect('/dashboard/grades');
        }

        // Fetch current section_semestre details (dates)
        $semestreDetails = DB::selectOne("
            SELECT ss.IDSection_Semestre, ss.DateD as date_debut, ss.DateF as date_fin
            FROM section_semestre ss
            JOIN section sec ON ss.IDSection = sec.IDSection
            WHERE sec.IDOffre = ? AND ss.NumSem = ?
        ", [$offreId, $semestre]);

        if (!$semestreDetails) {
            // Create if it doesn't exist
            $this->service->ensureSectionSemestreModules($offreId, $semestre);
            $semestreDetails = DB::selectOne("
                SELECT ss.IDSection_Semestre, ss.DateD as date_debut, ss.DateF as date_fin
                FROM section_semestre ss
                JOIN section sec ON ss.IDSection = sec.IDSection
                WHERE sec.IDOffre = ? AND ss.NumSem = ?
            ", [$offreId, $semestre]);
        }

        // Fetch modules / subjects for this semester
        $modules = DB::select("
            SELECT ssm.IDsection_semestre_Module as id, ssm.NomMdl as nom_ar, ssm.NomFrMdl as nom_fr, ssm.coef, ssm.IDEncadrement
            FROM section_semestre_module ssm
            WHERE ssm.IDSection_Semestre = ?
            ORDER BY ssm.IDsection_semestre_Module
        ", [$semestreDetails->IDSection_Semestre]);

        // Fetch all teachers in this establishment
        $teachers = DB::select("
            SELECT IDEncadrement as id, Nom as nom, Prenom as prenom, IDGrade
            FROM encadrement
            WHERE IDetablissement = ?
            ORDER BY Nom ASC, Prenom ASC
        ", [$offre['etablissement_id']]);

        return $this->render('admin/grades/semestre_setup', [
            'title' => 'تهيئة وإعداد السداسي الموالي',
            'offre' => $offre,
            'semestre' => $semestre,
            'semestreDetails' => $semestreDetails,
            'modules' => $modules,
            'teachers' => $teachers
        ]);
    }

    /**
     * Save Next Semester configuration
     */
    public function saveSemestreSetup(): mixed
    {
        $rawOffreId = request()->all()['offre_id'] ?? '';
        $offreId = is_numeric($rawOffreId) ? (int)$rawOffreId : (\App\Helpers\SecureIdHelper::decrypt($rawOffreId) ?? 0);
        $semestre = (int)(request()->all()['semestre'] ?? 1);

        if (!$offreId) {
            return $this->redirect('/dashboard/grades');
        }

        $dateDebut = request()->all()['date_debut'] ?? null;
        $dateFin = request()->all()['date_fin'] ?? null;
        $assignments = request()->all()['teachers'] ?? []; // Array of module_id => teacher_id

        DB::transaction(function() use ($offreId, $semestre, $dateDebut, $dateFin, $assignments) {
            // 1. Update dates in section_semestre
            DB::update("
                UPDATE section_semestre ss
                JOIN section sec ON ss.IDSection = sec.IDSection
                SET ss.DateD = ?, ss.DateF = ?
                WHERE sec.IDOffre = ? AND ss.NumSem = ?
            ", [$dateDebut, $dateFin, $offreId, $semestre]);

            // 2. Update teacher assignments in section_semestre_module
            foreach ($assignments as $moduleId => $teacherId) {
                DB::update("
                    UPDATE section_semestre_module 
                    SET IDEncadrement = ? 
                    WHERE IDsection_semestre_Module = ?
                ", [(int)$teacherId, (int)$moduleId]);
            }
        });

        session(['flash_success' => 'تم حفظ تواريخ السداسي وتعيينات الأساتذة بنجاح!']);

        return $this->redirect('/dashboard/schedule');
    }
}
