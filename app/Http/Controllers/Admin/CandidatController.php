<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Domains\Academic\Services\CandidatService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class CandidatController extends Controller
{
    protected CandidatService $service;

    public function __construct(CandidatService $service)
    {
        $this->service = $service;
        if (app()->runningInConsole()) { return; }
    }

    /**
     * List candidats with optional status filter
     */
    public function index(Request $request): mixed
    {
        @set_time_limit(300);
        $statusFilter = $request->query('status', 'all');
        $user         = session('user');
        $role         = strtolower($user['role_code'] ?? '');
        $dfepId       = (int)($user['iddfep'] ?? $user['IDDFEP'] ?? 0);
        $etabId       = (int)($user['etablissement_id'] ?? $user['IDEts_Form'] ?? 0);

        if ($etabId > 0 && !in_array($role, ['admin', 'central', 'high_admin', 'dfep'])) {
            $isDfepEtab = DB::selectOne("SELECT 1 FROM etablissement WHERE IDetablissement = ? AND (IDNature_etsF = 5 OR IDetablissement = 308)", [$etabId]) !== null;
            if ($isDfepEtab) {
                $role = 'dfep';
                $user['role_code'] = 'dfep';
            }
        }

        $filters = [
            'search'           => $request->query('search', ''),
            'wilaya_id'        => $request->query('wilaya_id', ''),
            'etablissement_id' => $request->query('etablissement_id', ''),
            'mode_id'          => $request->query('mode_id', ''),
            'offre_id'         => $request->query('offre_id', ''),
        ];

        try {
            $candidates = $this->service->listCandidats($user, $statusFilter, $filters);
        } catch (\Exception $e) {
            $candidates = [];
            session(['flash_error' => 'خطأ في تحميل البيانات: ' . $e->getMessage()]);
        }

        // Fetch lookup lists for dropdowns (Wilaya, Center, Mode)
        $wilayas = [];
        $etablissements = [];
        $modes = DB::table('mode_formation')->select('IDMode_formation as id', 'Nom as name')->orderBy('Nom')->get();

        if ((int)($user['IDMode_formation'] ?? 0) === 10) {
            $modes = $modes->filter(fn($m) => (int)$m->id === 10);
        } elseif (strtolower($user['username'] ?? '') === 'sdtpp') {
            $modes = $modes->filter(fn($m) => (int)$m->id !== 10);
        }

        if (in_array($role, ['admin', 'central', 'high_admin'])) {
            $wilayas = DB::table('wilaya')->select('IDWilayaa as id', 'Nom as name')->orderBy('Nom')->get();
            
            $etablissementsQuery = DB::table('etablissement')
                ->select('etablissement.IDetablissement as id', 'etablissement.Nom as name')
                ->orderBy('etablissement.Nom');
            if ($filters['wilaya_id'] !== '') {
                $etablissementsQuery->join('dfep', 'etablissement.IDDFEP', '=', 'dfep.IDDFEP')
                    ->where('dfep.IDWilayaa', (int)$filters['wilaya_id']);
            }
            $etablissements = $etablissementsQuery->get();
        } elseif ($role === 'dfep') {
            $wilayas = DB::table('wilaya')
                ->join('dfep', 'wilaya.IDWilayaa', '=', 'dfep.IDWilayaa')
                ->where('dfep.IDDFEP', $dfepId)
                ->select('wilaya.IDWilayaa as id', 'wilaya.Nom as name')
                ->get();
            $etablissements = DB::table('etablissement')
                ->where('IDDFEP', $dfepId)
                ->select('etablissement.IDetablissement as id', 'etablissement.Nom as name')
                ->orderBy('etablissement.Nom')
                ->get();
        }

        // Fetch reference data for Add/Edit Candidate Modals and Filter Bar
        $offersQuery = DB::table('offre')
            ->join('specialite', 'offre.IDSpecialite', '=', 'specialite.IDSpecialite')
            ->join('etablissement', 'offre.IDEts_Form', '=', 'etablissement.IDetablissement')
            ->select('offre.IDOffre as id', 'specialite.Nom as spec_ar', 'etablissement.Nom as etab_ar');

        if ($filters['etablissement_id'] !== '') {
            $offersQuery->where('offre.IDEts_Form', (int)$filters['etablissement_id']);
        } elseif ($filters['wilaya_id'] !== '') {
            $offersQuery->whereIn('offre.IDEts_Form', function($q) use ($filters) {
                $q->select('IDetablissement')->from('etablissement')->whereIn('IDDFEP', function($q2) use ($filters) {
                    $q2->select('IDDFEP')->from('dfep')->where('IDWilayaa', (int)$filters['wilaya_id']);
                });
            });
        } elseif ($role === 'dfep' && $dfepId > 0) {
            $offersQuery->whereIn('offre.IDEts_Form', function($q) use ($dfepId) {
                $q->select('IDetablissement')->from('etablissement')->where('IDDFEP', $dfepId);
            });
        } elseif ($etabId > 0) {
            $offersQuery->where('offre.IDEts_Form', $etabId);
        }

        if ($filters['mode_id'] !== '') {
            $offersQuery->where('offre.IDMode_formation', (int)$filters['mode_id']);
        } elseif ((int)($user['IDMode_formation'] ?? 0) === 10) {
            $offersQuery->where('offre.IDMode_formation', 10);
        } elseif (strtolower($user['username'] ?? '') === 'sdtpp') {
            $offersQuery->where('offre.IDMode_formation', '!=', 10);
        }

        $offers = $offersQuery->limit(300)->get()->toArray();

        return $this->render('admin/candidates', [
            'title'            => 'إدارة ملفات المترشحين - SGFEP',
            'candidates'       => $candidates,
            'statusFilter'     => $statusFilter,
            'offers'           => $offers,
            'wilayas'          => $wilayas,
            'etablissements'   => $etablissements,
            'modes'            => $modes,
            'filters'          => $filters,
            'role'             => $role,
        ]);
    }

    /**
     * Process validation/rejection decision on a candidat
     */
    public function action(): mixed
    {
        if (request()->isMethod('post')) {
            $candidatId  = (int)(request()->all()['pre_inscr_id'] ?? 0);
            $decision    = request()->all()['decision'] ?? '';
            $motifRefus  = request()->all()['motif_refus'] ?? null;
            $user        = session('user');

            $candidate = DB::table('candidat')->where('IDCandidat', $candidatId)->first();
            if ($candidate) {
                $wilayaId = (int) DB::table('offre')
                    ->join('etablissement', 'offre.IDEts_Form', '=', 'etablissement.IDetablissement')
                    ->join('dfep', 'etablissement.IDDFEP', '=', 'dfep.IDDFEP')
                    ->where('offre.IDOffre', $candidate->IDOffre)
                    ->value('dfep.IDWilayaa');

                if (!\App\Helpers\SovereignLicensingHelper::checkEnrollmentPermission('edit', $wilayaId)) {
                    session(['flash_error' => 'معالجة قرارات المترشحين معطل حالياً لهذه الولاية / La modification de décision est désactivée pour cette wilaya.']);
                    return redirect()->back();
                }
            }

            try {
                $this->service->processValidation($user, $candidatId, $decision, $motifRefus);
                session(['flash_success' => 'تم معالجة الطلب وتحديث القرار بنجاح.']);
            } catch (\Exception $e) {
                session(['flash_error' => 'حدث خطأ أثناء معالجة الطلب: ' . $e->getMessage()]);
            }
        }

        $redirectUrl = $_SERVER['HTTP_REFERER'] ?? '/sig/dashboard/candidates';
        return $this->redirect($redirectUrl);
    }

    public function show($id)
    {
        try {
            $memosEnabled = \App\Helpers\SovereignLicensingHelper::getSetting('feature_large_memos_query_enabled', '1') === '1';

            $selectCols = [
                'candidat.*',
                'preinscrit.photo_path'
            ];

            if ($memosEnabled) {
                $selectCols = array_merge($selectCols, [
                    'preinscrit.certscol_path',
                    'preinscrit.actnaispdf_path',
                    'preinscrit.diplomecert_path',
                    DB::raw('COALESCE(preinscrit.contratpdf_path, candidat_contratapp.photo, candidat_contratapp.url) as contratpdf_path')
                ]);
            } else {
                $selectCols = array_merge($selectCols, [
                    DB::raw('NULL as certscol_path'),
                    DB::raw('NULL as actnaispdf_path'),
                    DB::raw('NULL as diplomecert_path'),
                    DB::raw('NULL as contratpdf_path')
                ]);
            }

            $candidate = DB::table('candidat')
                ->leftJoin('preinscrit', 'candidat.IDPreinscrit', '=', 'preinscrit.IDPreinscrit')
                ->leftJoin('candidat_contratapp', 'candidat.IDCandidat', '=', 'candidat_contratapp.IDCandidat')
                ->where('candidat.IDCandidat', $id)
                ->select($selectCols)
                ->first();

            if (!$candidate) {
                return response()->json(['success' => false, 'message' => 'المترشح غير موجود / Candidate not found'], 404);
            }

            return response()->json(['success' => true, 'data' => $candidate]);
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
            'nom' => 'required|string|max:50',
            'nom_fr' => 'required|string|max:50',
            'prenom' => 'required|string|max:50',
            'prenom_fr' => 'required|string|max:50',
            'date_nais' => 'required|string|max:20',
            'lieu_nais' => 'nullable|string|max:50',
            'lieu_nais_fr' => 'nullable|string|max:50',
            'num_ins' => 'required|string|max:20',
            'civ' => 'required|integer',
            'tel' => 'nullable|string|max:15',
            'email' => 'nullable|email|max:40',
            'nin' => 'nullable|string|max:50',
        ]);

        $wilayaId = (int) DB::table('offre')
            ->join('etablissement', 'offre.IDEts_Form', '=', 'etablissement.IDetablissement')
            ->join('dfep', 'etablissement.IDDFEP', '=', 'dfep.IDDFEP')
            ->where('offre.IDOffre', $validated['offre_id'])
            ->value('dfep.IDWilayaa');

        if (!\App\Helpers\SovereignLicensingHelper::checkEnrollmentPermission('add', $wilayaId)) {
            session(['flash_error' => 'تسجيل المترشحين الجدد معطل حالياً لهذه الولاية / L\'inscription est désactivée pour cette wilaya.']);
            return redirect()->back();
        }

        try {
            DB::transaction(function () use ($validated) {
                $maxId = (int)DB::table('candidat')->lockForUpdate()->max('IDCandidat');
                $newId = max(1, $maxId + 1);

                DB::table('candidat')->insert([
                    'IDCandidat' => $newId,
                    'IDOffre' => $validated['offre_id'],
                    'Nom' => $validated['nom'],
                    'NomFr' => $validated['nom_fr'],
                    'Prenom' => $validated['prenom'],
                    'PrenomFr' => $validated['prenom_fr'],
                    'DateNais' => $validated['date_nais'],
                    'LieuNais' => $validated['lieu_nais'] ?? null,
                    'LieuNaisFr' => $validated['lieu_nais_fr'] ?? null,
                    'NumIns' => $validated['num_ins'],
                    'Civ' => $validated['civ'],
                    'Tel' => $validated['tel'] ?? null,
                    'email' => $validated['email'] ?? null,
                    'Nin' => $validated['nin'] ?? null,
                    'Validation' => 0, // En attente
                    'dateInscr' => now(),
                    'boursier' => 0,
                    'create_time' => now(),
                ]);
            });

            session(['flash_success' => 'تم إضافة المترشح بنجاح / Candidate added successfully']);
        } catch (\Exception $e) {
            session(['flash_error' => 'حدث خطأ أثناء إضافة المترشح: ' . $e->getMessage()]);
        }

        return redirect()->back();
    }
    public function update(Request $request)
    {
        $user = session('user');
        if (!$user) return redirect('/login');

        $validated = $request->validate([
            'id' => 'required|integer',
            'nom' => 'required|string|max:50',
            'nom_fr' => 'required|string|max:50',
            'prenom' => 'required|string|max:50',
            'prenom_fr' => 'required|string|max:50',
            'date_nais' => 'required|string|max:20',
            'lieu_nais' => 'nullable|string|max:50',
            'lieu_nais_fr' => 'nullable|string|max:50',
            'num_ins' => 'required|string|max:20',
            'civ' => 'required|integer',
            'tel' => 'nullable|string|max:15',
            'email' => 'nullable|email|max:40',
            'nin' => 'nullable|string|max:50',
        ]);

        $candidate = DB::table('candidat')->where('IDCandidat', $validated['id'])->first();
        if (!$candidate) {
            session(['flash_error' => 'المترشح غير موجود.']);
            return redirect()->back();
        }

        $wilayaId = 0;
        if (!empty($candidate->IDOffre)) {
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
            session(['flash_error' => 'تعديل بيانات المترشحين معطل حالياً لهذه الولاية / La modification est désactivée pour cette wilaya.']);
            return redirect()->back();
        }

        try {
            DB::table('candidat')
                ->where('IDCandidat', $validated['id'])
                ->update([
                    'Nom' => $validated['nom'],
                    'NomFr' => $validated['nom_fr'],
                    'Prenom' => $validated['prenom'],
                    'PrenomFr' => $validated['prenom_fr'],
                    'DateNais' => $validated['date_nais'],
                    'LieuNais' => $validated['lieu_nais'] ?? null,
                    'LieuNaisFr' => $validated['lieu_nais_fr'] ?? null,
                    'NumIns' => $validated['num_ins'],
                    'Civ' => $validated['civ'],
                    'Tel' => $validated['tel'] ?? null,
                    'email' => $validated['email'] ?? null,
                    'Nin' => $validated['nin'] ?? null,
                    'update_time' => now(),
                ]);

            session(['flash_success' => 'تم تحديث بيانات المترشح بنجاح / Candidate updated successfully']);
        } catch (\Exception $e) {
            session(['flash_error' => 'حدث خطأ أثناء تحديث بيانات المترشح: ' . $e->getMessage()]);
        }

        return redirect()->back();
    }

    public function destroy($id)
    {
        $user = session('user');
        if (!$user) return redirect('/login');

        $candidate = DB::table('candidat')->where('IDCandidat', $id)->first();
        if ($candidate) {
            $wilayaId = 0;
            if (!empty($candidate->IDOffre)) {
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
                session(['flash_error' => 'حذف المترشحين معطل حالياً لهذه الولاية / La suppression est désactivée pour cette wilaya.']);
                return redirect()->back();
            }
        }

        try {
            // Guard: check if candidate is registered as a student
            $exists = DB::table('apprenant')->where('IDCandidat', $id)->exists();
            if ($exists) {
                session(['flash_error' => 'لا يمكن حذف المترشح لكونه مسجلاً كطالب في النظام / Candidate is already a student']);
            } else {
                DB::table('candidat')->where('IDCandidat', $id)->delete();
                session(['flash_success' => 'تم حذف المترشح بنجاح / Candidate deleted successfully']);
            }
        } catch (\Exception $e) {
            session(['flash_error' => 'حدث خطأ أثناء حذف المترشح: ' . $e->getMessage()]);
        }

        return redirect()->back();
    }
}
