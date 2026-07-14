<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class ApprenantController extends Controller
{
    private const PER_PAGE = 30;

    public function index(Request $request)
    {
        @set_time_limit(300);
        $user   = session('user') ?? [];
        $role   = strtolower($user['role_code'] ?? '');
        $etabId = (int)($user['etablissement_id'] ?? $user['IDEts_Form'] ?? 0);
        $dfepId = (int)($user['iddfep'] ?? $user['IDDFEP'] ?? 0);

        // ── build filters ────────────────────────────────────────────
        $where  = [];
        $params = [];

        // Role restriction
        if (in_array($role, ['admin', 'ministre', 'secretaire_general', 'central', 'high_admin'])) {
            // no restriction
        } elseif ($role === 'dfep' && $dfepId > 0) {
            $where[]  = 'et.IDDFEP = ?';
            $params[] = $dfepId;
        } elseif ($etabId > 0) {
            $where[]  = 'o.IDEts_Form = ?';
            $params[] = $etabId;
        } else {
            $where[] = '1=0';
        }

        if ((int)($user['IDMode_formation'] ?? 0) === 10) {
            $where[] = 'o.IDMode_formation = 10';
        } elseif (strtolower($user['username'] ?? '') === 'sdtpp') {
            $where[] = 'o.IDMode_formation != 10';
        }

        // Text Search
        $search = trim($request->query('search', ''));
        if ($search !== '') {
            $where[]  = "(a.Nccp LIKE ? OR c.Nom LIKE ? OR c.Prenom LIKE ? OR c.NomFr LIKE ? OR c.PrenomFr LIKE ?)";
            $like     = "%{$search}%";
            $params   = array_merge($params, [$like, $like, $like, $like, $like]);
        }

        // Section filter
        $filterSection = (int)$request->query('filter_section', 0);
        if ($filterSection > 0) {
            $where[]  = 'a.IDSection = ?';
            $params[] = $filterSection;
        }

        // Status filter (Default to 'actif' if not explicitly provided)
        $filterStatus = $request->has('filter_status') ? trim($request->query('filter_status', '')) : 'actif';
        if ($filterStatus === 'actif') {
            $where[] = 'a.statut = ?';
            $params[] = 'actif';
            $where[] = 'NOT EXISTS (SELECT 1 FROM apprenant_fin af WHERE af.IDapprenant = a.IDapprenant)';
            $where[] = 'DATE_ADD(sess.DateD, INTERVAL COALESCE(NULLIF(sp.dureeM, 0), sp.NbrSem * 6, 24) MONTH) >= CURRENT_DATE()';
        } elseif ($filterStatus !== '' && $filterStatus !== 'all') {
            $where[]  = 'a.statut = ?';
            $params[] = $filterStatus;
        }

        // Establishment filter (only for admin/dfep)
        $filterEtab = (int)$request->query('filter_etab', 0);
        if ($filterEtab > 0 && $etabId === 0) {
            $where[]  = 'o.IDEts_Form = ?';
            $params[] = $filterEtab;
        }

        $whereSQL = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        // ── Get Total Count (cached 2 mins for performance) ───────────
        $cacheKey = 'students_count_' . md5($whereSQL . implode(',', $params));
        $totalCount = Cache::remember($cacheKey, 120, function () use ($whereSQL, $params) {
            try {
                $joins = "";
                if (strpos($whereSQL, 'c.') !== false) {
                    $joins .= " INNER JOIN candidat c ON a.IDCandidat = c.IDCandidat";
                }
                if (strpos($whereSQL, 's.') !== false || strpos($whereSQL, 'o.') !== false || strpos($whereSQL, 'et.') !== false || strpos($whereSQL, 'sess.') !== false || strpos($whereSQL, 'sp.') !== false) {
                    $joins .= " LEFT JOIN section s ON a.IDSection = s.IDSection";
                }
                if (strpos($whereSQL, 'o.') !== false || strpos($whereSQL, 'et.') !== false || strpos($whereSQL, 'sess.') !== false || strpos($whereSQL, 'sp.') !== false) {
                    $joins .= " LEFT JOIN offre o ON s.IDOffre = o.IDOffre";
                }
                if (strpos($whereSQL, 'et.') !== false) {
                    $joins .= " LEFT JOIN etablissement et ON o.IDEts_Form = et.IDetablissement";
                }
                if (strpos($whereSQL, 'sess.') !== false) {
                    $joins .= " LEFT JOIN session sess ON o.IDSession = sess.IDSession";
                }
                if (strpos($whereSQL, 'sp.') !== false) {
                    $joins .= " LEFT JOIN specialite sp ON o.IDSpecialite = sp.IDSpecialite";
                }

                return (int) DB::selectOne(
                    "SELECT COUNT(*) as c
                     FROM apprenant a
                     {$joins}
                     {$whereSQL}",
                    $params
                )->c;
            } catch (\Throwable $e) {
                return 0;
            }
        });

        // ── Pagination ───────────────────────────────────────────────────
        $page       = max(1, (int)$request->query('page', 1));
        $totalPages = $totalCount > 0 ? (int)ceil($totalCount / self::PER_PAGE) : 1;
        $page       = min($page, $totalPages);
        $offset     = ($page - 1) * self::PER_PAGE;

        // ── Main Query ────────────────────────────────────────────────────
        $students = [];
        try {
            $rows = DB::select(
                "SELECT a.IDapprenant as id,
                        c.NumIns as numero_matricule,
                        a.statut,
                        a.Valide as valide,
                        a.Groupe as groupe,
                        a.IDSection as section_id,
                        c.Nom as nom_ar,
                        c.Prenom as prenom_ar,
                        c.NomFr as nom_fr,
                        c.PrenomFr as prenom_fr,
                        c.Civ as civ,
                        s.Nom as section_nom,
                        sp.Nom as spec_ar,
                        et.Nom as etab_ar,
                        sess.Nom as session_nom,
                        c.dateInscr as date_inscription
                 FROM apprenant a
                 INNER JOIN candidat c ON a.IDCandidat = c.IDCandidat
                 LEFT JOIN section s   ON a.IDSection = s.IDSection
                 LEFT JOIN offre o     ON s.IDOffre = o.IDOffre
                 LEFT JOIN specialite sp ON o.IDSpecialite = sp.IDSpecialite
                 LEFT JOIN etablissement et ON o.IDEts_Form = et.IDetablissement
                 LEFT JOIN session sess ON o.IDSession = sess.IDSession
                 {$whereSQL}
                 ORDER BY a.IDapprenant DESC
                 LIMIT " . self::PER_PAGE . " OFFSET {$offset}",
                $params
            );
            $students = array_map(fn($r) => (array)$r, $rows);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('[ApprenantController] query error: ' . $e->getMessage());
        }

        // ── Reference Data ───────────────────────────────────────────────
        $wilayas = \App\Services\ReferenceCache::wilayas();
        $etablissements = match(true) {
            $dfepId > 0  => \App\Services\ReferenceCache::etablissementsForDfep($dfepId),
            $etabId > 0  => \App\Services\ReferenceCache::etablissementById($etabId),
            default      => \App\Services\ReferenceCache::etablissements(),
        };

        // Available sections
        $sectionsQuery = DB::table('section')
            ->join('offre', 'section.IDOffre', '=', 'offre.IDOffre')
            ->join('specialite', 'offre.IDSpecialite', '=', 'specialite.IDSpecialite')
            ->select('section.IDSection as id', 'section.Nom as nom_ar', 'specialite.Nom as spec_ar', 'offre.IDEts_Form as etab_id');

        if ($dfepId > 0) {
            $sectionsQuery->whereIn('offre.IDEts_Form', function($q) use ($dfepId) {
                $q->select('IDetablissement')->from('etablissement')->where('IDDFEP', $dfepId);
            });
        } elseif ($etabId > 0) {
            $sectionsQuery->where('offre.IDEts_Form', $etabId);
        } elseif ($filterEtab > 0) {
            $sectionsQuery->where('offre.IDEts_Form', $filterEtab);
        } else {
            // High-level user without filtering by establishment:
            // Prevent loading 150k sections by limiting to the selected section or returning a small subset (100)
            if ($filterSection > 0) {
                $sectionsQuery->where('section.IDSection', $filterSection);
            } else {
                $sectionsQuery->limit(100);
            }
        }

        if ((int)($user['IDMode_formation'] ?? 0) === 10) {
            $sectionsQuery->where('offre.IDMode_formation', 10);
        } elseif (strtolower($user['username'] ?? '') === 'sdtpp') {
            $sectionsQuery->where('offre.IDMode_formation', '!=', 10);
        }

        $sections = $sectionsQuery->get()->toArray();

        // Candidates that are NOT yet students (for the add student modal)
        $candQuery = DB::table('candidat')
            ->leftJoin('apprenant', 'candidat.IDCandidat', '=', 'apprenant.IDCandidat')
            ->whereNull('apprenant.IDCandidat')
            ->select('candidat.IDCandidat as id', 'candidat.Nom as nom_ar', 'candidat.Prenom as prenom_ar', 'candidat.NomFr as nom_fr', 'candidat.PrenomFr as prenom_fr', 'candidat.Nin as nin', 'candidat.NumIns as num_ins');
            
        if ($dfepId > 0) {
            $candQuery->whereIn('candidat.IDOffre', function($q) use ($dfepId) {
                $q->select('IDOffre')->from('offre')->whereIn('IDEts_Form', function($iq) use ($dfepId) {
                    $iq->select('IDetablissement')->from('etablissement')->where('IDDFEP', $dfepId);
                });
            });
        } elseif ($etabId > 0) {
            $candQuery->whereIn('candidat.IDOffre', function($q) use ($etabId) {
                $q->select('IDOffre')->from('offre')->where('IDEts_Form', $etabId);
            });
        }

        if ((int)($user['IDMode_formation'] ?? 0) === 10) {
            $candQuery->join('offre', 'candidat.IDOffre', '=', 'offre.IDOffre')
                      ->where('offre.IDMode_formation', 10);
        } elseif (strtolower($user['username'] ?? '') === 'sdtpp') {
            $candQuery->join('offre', 'candidat.IDOffre', '=', 'offre.IDOffre')
                      ->where('offre.IDMode_formation', '!=', 10);
        }

        $availableCandidates = $candQuery->limit(200)->get()->toArray();

        return $this->render('admin/apprenants/index', [
            'title'               => 'سجل الطلاب والمتكونين / Registre des Stagiaires',
            'students'            => $students,
            'total_count'         => $totalCount,
            'page'                => $page,
            'total_pages'         => $totalPages,
            'per_page'            => self::PER_PAGE,
            'search'              => $search,
            'filter_section'      => $filterSection,
            'filter_status'       => $filterStatus,
            'filter_etab'         => $filterEtab,
            'wilayas'             => $wilayas,
            'etablissements'      => $etablissements,
            'sections'            => $sections,
            'availableCandidates' => $availableCandidates,
            'role_code'           => $role,
        ]);
    }

    public function show($id)
    {
        try {
            $memosEnabled = \App\Helpers\SovereignLicensingHelper::getSetting('feature_large_memos_query_enabled', '1') === '1';

            $selectCols = [
                'apprenant.*',
                'section.Nom as section_nom',
                'candidat.Nom as nom_ar',
                'candidat.Prenom as prenom_ar',
                'candidat.NomFr as nom_fr',
                'candidat.PrenomFr as prenom_fr',
                'candidat.DateNais as date_nais',
                'candidat.LieuNais as lieu_nais',
                'candidat.Nin as nin',
                'candidat.Civ as civ',
                'candidat.Tel as tel',
                'candidat.email as email',
                'candidat.photo as photo_path',
                'preinscrit.photo_path as pre_photo_path',
            ];

            if ($memosEnabled) {
                $selectCols = array_merge($selectCols, [
                    'preinscrit.certscol_path',
                    'preinscrit.actnaispdf_path',
                    'preinscrit.diplomecert_path',
                    DB::raw('COALESCE(preinscrit.contratpdf_path, candidat_contratapp.photo, candidat_contratapp.url) as contratpdf_path'),
                    'candidat_document.relevedenotes_url',
                    'candidat_document.enneexperience_url',
                    'candidat_document.exdiplome_url',
                    'candidat_document.actn_url'
                ]);
            } else {
                $selectCols = array_merge($selectCols, [
                    DB::raw('NULL as certscol_path'),
                    DB::raw('NULL as actnaispdf_path'),
                    DB::raw('NULL as diplomecert_path'),
                    DB::raw('NULL as contratpdf_path'),
                    DB::raw('NULL as relevedenotes_url'),
                    DB::raw('NULL as enneexperience_url'),
                    DB::raw('NULL as exdiplome_url'),
                    DB::raw('NULL as actn_url')
                ]);
            }

            $student = DB::table('apprenant')
                ->join('candidat', 'apprenant.IDCandidat', '=', 'candidat.IDCandidat')
                ->leftJoin('preinscrit', 'candidat.IDPreinscrit', '=', 'preinscrit.IDPreinscrit')
                ->leftJoin('candidat_document', 'candidat.IDCandidat', '=', 'candidat_document.IDCandidat')
                ->leftJoin('candidat_contratapp', 'candidat.IDCandidat', '=', 'candidat_contratapp.IDCandidat')
                ->leftJoin('section', 'apprenant.IDSection', '=', 'section.IDSection')
                ->where('apprenant.IDapprenant', $id)
                ->select($selectCols)
                ->first();

            if (!$student) {
                return response()->json(['success' => false, 'message' => 'الطالب غير موجود / Trainee not found'], 404);
            }

            return response()->json(['success' => true, 'data' => $student]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        $user = session('user');
        if (!$user) return redirect('/login');

        $validated = $request->validate([
            'candidat_id' => 'required|integer',
            'section_id' => 'required|integer',
            'nccp' => 'required|string|max:50',
            'statut' => 'required|string|max:50',
            'valide' => 'required|integer',
            'groupe' => 'required|integer',
        ]);

        $candidate = DB::table('candidat')->where('IDCandidat', $validated['candidat_id'])->first();
        if (!$candidate) {
            session(['flash_error' => 'المترشح غير موجود.']);
            return redirect()->back();
        }

        $wilayaId = (int) DB::table('offre')
            ->join('etablissement', 'offre.IDEts_Form', '=', 'etablissement.IDetablissement')
            ->join('dfep', 'etablissement.IDDFEP', '=', 'dfep.IDDFEP')
            ->where('offre.IDOffre', $candidate->IDOffre)
            ->value('dfep.IDWilayaa');

        if (!\App\Helpers\SovereignLicensingHelper::checkEnrollmentPermission('add', $wilayaId)) {
            session(['flash_error' => 'إضافة الطلاب الجدد معطل حالياً لهذه الولاية / L\'inscription est désactivée pour cette wilaya.']);
            return redirect()->back();
        }

        try {
            DB::transaction(function () use ($validated) {
                $maxId = (int)DB::table('apprenant')->lockForUpdate()->max('IDapprenant');
                $newId = max(1, $maxId + 1);

                DB::table('apprenant')->insert([
                    'IDapprenant' => $newId,
                    'IDCandidat' => $validated['candidat_id'],
                    'IDSection' => $validated['section_id'],
                    'Nccp' => $validated['nccp'],
                    'statut' => $validated['statut'],
                    'Valide' => $validated['valide'],
                    'Groupe' => $validated['groupe'],
                    'create_time' => now(),
                ]);
            });

            session(['flash_success' => 'تم إضافة الطالب بنجاح / Stagiaire ajouté avec succès']);
        } catch (\Exception $e) {
            session(['flash_error' => 'حدث خطأ أثناء إضافة الطالب: ' . $e->getMessage()]);
        }

        return redirect()->back();
    }

    public function update(Request $request)
    {
        $user = session('user');
        if (!$user) return redirect('/login');

        $validated = $request->validate([
            'id' => 'required|integer',
            'section_id' => 'required|integer',
            'nccp' => 'required|string|max:50',
            'statut' => 'required|string|max:50',
            'valide' => 'required|integer',
            'groupe' => 'required|integer',
        ]);

        $student = DB::table('apprenant')->where('IDapprenant', $validated['id'])->first();
        if (!$student) {
            session(['flash_error' => 'الطالب غير موجود.']);
            return redirect()->back();
        }

        $candidate = DB::table('candidat')->where('IDCandidat', $student->IDCandidat)->first();
        $wilayaId = 0;
        if ($candidate && !empty($candidate->IDOffre)) {
            try {
                $wilayaId = (int) DB::table('offre')
                    ->join('etablissement', 'offre.IDEts_Form', '=', 'etablissement.IDetablissement')
                    ->join('dfep', 'etablissement.IDDFEP', '=', 'dfep.IDDFEP')
                    ->where('offre.IDOffre', $candidate->IDOffre)
                    ->value('dfep.IDWilayaa');
            } catch (\Exception $e) {
                $wilayaId = 0;
            }
        }

        if (!\App\Helpers\SovereignLicensingHelper::checkEnrollmentPermission('edit', $wilayaId)) {
            session(['flash_error' => 'تعديل بيانات الطلاب معطل حالياً لهذه الولاية / La modification est désactivée pour cette wilaya.']);
            return redirect()->back();
        }

        try {
            DB::table('apprenant')
                ->where('IDapprenant', $validated['id'])
                ->update([
                    'IDSection' => $validated['section_id'],
                    'Nccp' => $validated['nccp'],
                    'statut' => $validated['statut'],
                    'Valide' => $validated['valide'],
                    'Groupe' => $validated['groupe'],
                    'update_time' => now(),
                ]);

            session(['flash_success' => 'تم تحديث بيانات الطالب بنجاح / Stagiaire modifié avec succès']);
        } catch (\Exception $e) {
            session(['flash_error' => 'حدث خطأ أثناء تحديث بيانات الطالب: ' . $e->getMessage()]);
        }

        return redirect()->back();
    }

    public function destroy($id)
    {
        $user = session('user');
        if (!$user) return redirect('/login');

        $student = DB::table('apprenant')->where('IDapprenant', $id)->first();
        if ($student) {
            $candidate = DB::table('candidat')->where('IDCandidat', $student->IDCandidat)->first();
            $wilayaId = 0;
            if ($candidate && !empty($candidate->IDOffre)) {
                try {
                    $wilayaId = (int) DB::table('offre')
                        ->join('etablissement', 'offre.IDEts_Form', '=', 'etablissement.IDetablissement')
                        ->join('dfep', 'etablissement.IDDFEP', '=', 'dfep.IDDFEP')
                        ->where('offre.IDOffre', $candidate->IDOffre)
                        ->value('dfep.IDWilayaa');
                } catch (\Exception $e) {
                    $wilayaId = 0;
                }
            }

            if (!\App\Helpers\SovereignLicensingHelper::checkEnrollmentPermission('delete', $wilayaId)) {
                session(['flash_error' => 'حذف الطلاب معطل حالياً لهذه الولاية / La suppression est désactivée pour cette wilaya.']);
                return redirect()->back();
            }
        }

        try {
            // Guard: check references in apprenant_fin
            $exists = DB::table('apprenant_fin')->where('IDapprenant', $id)->exists();
            if ($exists) {
                session(['flash_error' => 'لا يمكن حذف الطالب لوجود سجل تخرج رسمي مرتبط به / Stagiaire diplômé']);
            } else {
                DB::table('apprenant')->where('IDapprenant', $id)->delete();
                session(['flash_success' => 'تم حذف الطالب بنجاح / Stagiaire supprimé avec succès']);
            }
        } catch (\Exception $e) {
            session(['flash_error' => 'حدث خطأ أثناء حذف الطالب: ' . $e->getMessage()]);
        }

        return redirect()->back();
    }
}
