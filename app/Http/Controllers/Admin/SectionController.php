<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class SectionController extends Controller
{
    private const PER_PAGE = 30;

    public function index(Request $request)
    {
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
            $where[]  = 'sec.IDEts_Form = ?';
            $params[] = $etabId;
        } else {
            $where[] = '1=0';
        }

        if ((int)($user['IDMode_formation'] ?? 0) === 10) {
            $where[] = 'sec.IDMode_formation = 10';
        }

        // Text Search
        $search = trim($request->query('search', ''));
        if ($search !== '') {
            $where[]  = "(sec.Nom LIKE ? OR sec.NomFr LIKE ? OR sp.Nom LIKE ? OR sp.NomFr LIKE ?)";
            $like     = "%{$search}%";
            $params   = array_merge($params, [$like, $like, $like, $like]);
        }

        // Session filter (defaults to active session where Encour = 1)
        $filterSession = (int)$request->query('filter_session', 0);
        if ($filterSession === 0) {
            $activeSessionId = (int)DB::table('session')->where('Encour', 1)->value('IDSession');
            $filterSession = $activeSessionId > 0 ? $activeSessionId : 35;
        }
        $where[]  = 'sec.IDSession = ?';
        $params[] = $filterSession;

        // Establishment filter (only for admin/dfep)
        $filterEtab = (int)$request->query('filter_etab', 0);
        if ($filterEtab > 0 && $etabId === 0) {
            $where[]  = 'sec.IDEts_Form = ?';
            $params[] = $filterEtab;
        }

        $whereSQL = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        // ── Get Total Count (cached 2 mins) ───────────────────────────
        $cacheKey = 'sections_count_' . md5($whereSQL . implode(',', $params));
        $totalCount = Cache::remember($cacheKey, 120, function () use ($whereSQL, $params) {
            try {
                return (int) DB::selectOne(
                    "SELECT COUNT(*) as c
                     FROM section sec
                     LEFT JOIN etablissement et ON sec.IDEts_Form = et.IDetablissement
                     LEFT JOIN specialite sp ON sec.IDSpecialite = sp.IDSpecialite
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
        $sections = [];
        try {
            $rows = DB::select(
                "SELECT sec.IDSection as id,
                        sec.Nom as nom_ar,
                        sec.NomFr as nom_fr,
                        sec.DateDF as date_debut,
                        sec.DateFF as date_fin,
                        sec.Duree as duree,
                        sec.Groupe as groupe,
                        sp.Nom as spec_ar,
                        sp.NomFr as spec_fr,
                        et.Nom as etab_ar,
                        sess.Nom as session_nom,
                        enc.Nom as enc_nom,
                        enc.Prenom as enc_prenom
                 FROM section sec
                 LEFT JOIN etablissement et ON sec.IDEts_Form = et.IDetablissement
                 LEFT JOIN specialite sp ON sec.IDSpecialite = sp.IDSpecialite
                 LEFT JOIN session sess ON sec.IDSession = sess.IDSession
                 LEFT JOIN encadrement enc ON sec.IDEncadrement = enc.IDEncadrement
                 {$whereSQL}
                 ORDER BY sec.IDSection DESC
                 LIMIT " . self::PER_PAGE . " OFFSET {$offset}",
                $params
            );
            $sections = array_map(fn($r) => (array)$r, $rows);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('[SectionController] query error: ' . $e->getMessage());
        }

        // ── Reference Data ───────────────────────────────────────────────
        $wilayas = \App\Services\ReferenceCache::wilayas();
        $etablissements = match(true) {
            $dfepId > 0  => \App\Services\ReferenceCache::etablissementsForDfep($dfepId),
            $etabId > 0  => \App\Services\ReferenceCache::etablissementById($etabId),
            default      => \App\Services\ReferenceCache::etablissements(),
        };

        $offersQuery = DB::table('offre')
            ->join('specialite', 'offre.IDSpecialite', '=', 'specialite.IDSpecialite')
            ->join('etablissement', 'offre.IDEts_Form', '=', 'etablissement.IDetablissement')
            ->select('offre.IDOffre as id', 'specialite.Nom as spec_ar', 'specialite.NomFr as spec_fr', 'etablissement.Nom as etab_ar', 'offre.IDSession as session_id', 'offre.DateD as date_debut', 'offre.DateF as date_fin', DB::raw('COALESCE(NULLIF(specialite.dureeM, 0), specialite.NbrSem * 6, 24) as duree'))
            ->where('offre.IDSession', '=', $filterSession);

        if ($dfepId > 0) {
            $offersQuery->whereIn('offre.IDEts_Form', function($q) use ($dfepId) {
                $q->select('IDetablissement')->from('etablissement')->where('IDDFEP', $dfepId);
            });
        } elseif ($etabId > 0) {
            $offersQuery->where('offre.IDEts_Form', $etabId);
        }

        if ((int)($user['IDMode_formation'] ?? 0) === 10) {
            $offersQuery->where('offre.IDMode_formation', 10);
        }

        $offers = $offersQuery->limit(200)->get()->toArray();

        // Available Sessions
        $sessions = DB::table('session')->select('IDSession as id', 'Nom as nom_ar')->orderBy('IDSession', 'DESC')->get()->toArray();

        // Available Trainers
        $trainersQuery = DB::table('encadrement')->select('IDEncadrement as id', 'Nom as nom', 'Prenom as prenom');
        if ($etabId > 0) {
            $trainersQuery->where('IDetablissement', $etabId);
        }
        $trainers = $trainersQuery->limit(200)->get()->toArray();

        return $this->render('admin/sections/index', [
            'title'          => 'الأقسام والشعب التكوينية / Sections de Formation',
            'sections'       => $sections,
            'total_count'    => $totalCount,
            'page'           => $page,
            'total_pages'    => $totalPages,
            'per_page'       => self::PER_PAGE,
            'search'         => $search,
            'filter_session' => $filterSession,
            'filter_etab'    => $filterEtab,
            'wilayas'        => $wilayas,
            'etablissements' => $etablissements,
            'offers'         => $offers,
            'sessions'       => $sessions,
            'trainers'       => $trainers,
            'role_code'      => $role,
        ]);
    }

    public function show($id)
    {
        try {
            $section = DB::table('section')
                ->where('IDSection', $id)
                ->first();

            if (!$section) {
                return response()->json(['success' => false, 'message' => 'القسم غير موجود / Section not found'], 404);
            }

            return response()->json(['success' => true, 'data' => $section]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        $user = session('user');
        if (!$user) return redirect('/login');

        $validated = $request->validate([
            'offre_id' => 'required|integer',
            'nom_ar' => 'required|string|max:150',
            'nom_fr' => 'required|string|max:150',
            'date_debut' => 'required|date',
            'date_fin' => 'required|date',
            'duree' => 'required|numeric',
            'groupe' => 'required|integer',
            'encadrement_id' => 'nullable|integer',
        ]);

        try {
            // Load offer to extract properties
            $offre = DB::table('offre')->where('IDOffre', $validated['offre_id'])->first();
            if (!$offre) {
                session(['flash_error' => 'عرض التكوين المحدد غير صالح / Invalid offer selected']);
                return redirect()->back();
            }

            DB::transaction(function () use ($validated, $offre) {
                $maxId = (int)DB::table('section')->lockForUpdate()->max('IDSection');
                $newId = max(1, $maxId + 1);

                DB::table('section')->insert([
                    'IDSection' => $newId,
                    'IDOffre' => $offre->IDOffre,
                    'Nom' => $validated['nom_ar'],
                    'NomFr' => $validated['nom_fr'],
                    'DateDF' => $validated['date_debut'],
                    'DateFF' => $validated['date_fin'],
                    'Duree' => $validated['duree'],
                    'Groupe' => $validated['groupe'],
                    'IDEncadrement' => $validated['encadrement_id'] ?: 0,
                    'IDSession' => $offre->IDSession,
                    'IDEts_Form' => $offre->IDEts_Form,
                    'IDSpecialite' => $offre->IDSpecialite,
                    'IDMode_formation' => $offre->IDMode_formation,
                    'IDDFEP' => DB::table('etablissement')->where('IDetablissement', $offre->IDEts_Form)->value('IDDFEP') ?: 0,
                    'Validation' => 0,
                    'visadfep' => 0,
                    'visadir' => 0,
                ]);

                DB::table('offre')
                    ->where('IDOffre', $offre->IDOffre)
                    ->update(['nbrGroupe' => $validated['groupe']]);
            });

            session(['flash_success' => 'تم إضافة القسم بنجاح / Section ajoutée avec succès']);
        } catch (\Exception $e) {
            session(['flash_error' => 'حدث خطأ أثناء إضافة القسم: ' . $e->getMessage()]);
        }

        return redirect()->back();
    }

    public function update(Request $request)
    {
        $user = session('user');
        if (!$user) return redirect('/login');

        $validated = $request->validate([
            'id' => 'required|integer',
            'nom_ar' => 'required|string|max:150',
            'nom_fr' => 'required|string|max:150',
            'date_debut' => 'required|date',
            'date_fin' => 'required|date',
            'duree' => 'required|numeric',
            'groupe' => 'required|integer',
            'encadrement_id' => 'nullable|integer',
        ]);

        try {
            DB::transaction(function () use ($validated) {
                $section = DB::table('section')->where('IDSection', $validated['id'])->first();
                if ($section) {
                    DB::table('offre')
                        ->where('IDOffre', $section->IDOffre)
                        ->update(['nbrGroupe' => $validated['groupe']]);
                }

                DB::table('section')
                    ->where('IDSection', $validated['id'])
                    ->update([
                        'Nom' => $validated['nom_ar'],
                        'NomFr' => $validated['nom_fr'],
                        'DateDF' => $validated['date_debut'],
                        'DateFF' => $validated['date_fin'],
                        'Duree' => $validated['duree'],
                        'Groupe' => $validated['groupe'],
                        'IDEncadrement' => $validated['encadrement_id'] ?: 0,
                    ]);
            });

            session(['flash_success' => 'تم تحديث القسم بنجاح / Section modifiée avec succès']);
        } catch (\Exception $e) {
            session(['flash_error' => 'حدث خطأ أثناء تحديث القسم: ' . $e->getMessage()]);
        }

        return redirect()->back();
    }

    public function destroy($id)
    {
        $user = session('user');
        if (!$user) return redirect('/login');

        try {
            // Guard: check if section contains students
            $hasStudents = DB::table('apprenant')->where('IDSection', $id)->exists();
            if ($hasStudents) {
                session(['flash_error' => 'لا يمكن حذف القسم لوجود طلاب مسجلين فيه / Section contains trainees']);
            } else {
                DB::table('section')->where('IDSection', $id)->delete();
                session(['flash_success' => 'تم حذف القسم بنجاح / Section supprimée avec succès']);
            }
        } catch (\Exception $e) {
            session(['flash_error' => 'حدث خطأ أثناء حذف القسم: ' . $e->getMessage()]);
        }

        return redirect()->back();
    }

    public function ajaxGetTrainees($id)
    {
        try {
            $section = DB::selectOne("
                SELECT sec.IDSection as id,
                       sec.Nom as nom_ar,
                       sec.IDMode_formation,
                       sec.DateDF as date_debut,
                       sec.DateFF as date_fin,
                       sec.Groupe as groupe,
                       sp.Nom as spec_ar,
                       sp.CodeSpec as spec_code,
                       nv.Nom as niveau_qualif,
                       et.Nom as etab_nom,
                       df.Nom as dfep_nom,
                       w.Nom as wilaya_nom,
                       CONCAT(enc.Nom, ' ', enc.Prenom) as responsable
                FROM section sec
                LEFT JOIN etablissement et ON sec.IDEts_Form = et.IDetablissement
                LEFT JOIN dfep df ON et.IDDFEP = df.IDDFEP
                LEFT JOIN wilaya w ON df.IDWilayaa = w.IDWilaya
                LEFT JOIN specialite sp ON sec.IDSpecialite = sp.IDSpecialite
                LEFT JOIN niveau_fp nv ON sp.IDNiveau_Fp = nv.IDNiveau_Fp
                LEFT JOIN encadrement enc ON sec.IDEncadrement = enc.IDEncadrement
                WHERE sec.IDSection = ?
            ", [(int)$id]);

            if (!$section) {
                return response()->json(['success' => false, 'message' => 'القسم غير موجود'], 404);
            }

            $trainees = DB::select("
                SELECT a.IDapprenant,
                       a.Nccp as nccp,
                       c.Nin as nin,
                       c.Nom as nom_ar,
                       c.Prenom as prenom_ar,
                       c.NomFr as nom_fr,
                       c.PrenomFr as prenom_fr,
                       c.DateNais as date_naissance,
                       c.LieuNais as lieu_naissance,
                       c.Adres as adresse,
                       c.PrenomPere as prenom_pere,
                       c.NomMere as nom_mere,
                       c.PrenomMere as prenom_mere,
                       a.NumActe as num_contrat,
                       a.DateActe as date_contrat,
                       a.NomEmployeur as nom_employeur,
                       c.Civ as sexe,
                       c.endicape,
                       c.Nationalite,
                       ns.Nom as niveau_scolaire
                FROM apprenant a
                JOIN candidat c ON a.IDCandidat = c.IDCandidat
                LEFT JOIN niveau_scol ns ON c.IDNiveau_Scol = ns.IDNiveau_Scol
                WHERE a.IDSection = ? AND a.statut = 'actif'
                ORDER BY c.Nom ASC, c.Prenom ASC
            ", [(int)$id]);

            return response()->json([
                'success'  => true,
                'section'  => $section,
                'trainees' => $trainees
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
