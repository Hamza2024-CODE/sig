<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class PreinscritController extends Controller
{
    private const PER_PAGE = 25;

    public function index(Request $request)
    {
        @set_time_limit(300);
        $user = session('user');
        if (!$user) return redirect('/login');

        $role = strtolower($user['role_code'] ?? '');
        $etabId = (int)($user['etablissement_id'] ?? $user['IDEts_Form'] ?? 0);
        $dfepId = (int)($user['iddfep'] ?? $user['IDDFEP'] ?? 0);

        $search = trim($request->input('search', ''));
        $status = $request->input('status', '0'); // '0' = pending, '1' = validated, '2' = rejected, 'all'
        $offreId = $request->input('offre_id', '');
        $wilayaId = $request->input('wilaya_id', '');
        $etablissementId = $request->input('etablissement_id', '');
        $modeId = $request->input('mode_id', '');

        // Reset child filters if parent filter changes to prevent impossible intersections
        if ($wilayaId !== '' && $etablissementId !== '') {
            $exists = DB::table('etablissement as et')
                ->join('dfep as d', 'et.IDDFEP', '=', 'd.IDDFEP')
                ->where('et.IDetablissement', (int)$etablissementId)
                ->where('d.IDWilayaa', (int)$wilayaId)
                ->exists();
            if (!$exists) {
                $etablissementId = '';
            }
        }

        if ($etablissementId !== '' && $offreId !== '') {
            $exists = DB::table('offre')
                ->where('IDOffre', (int)$offreId)
                ->where('IDEts_Form', (int)$etablissementId)
                ->exists();
            if (!$exists) {
                $offreId = '';
            }
        }

        if ($wilayaId !== '' && $offreId !== '') {
            $exists = DB::table('offre as o')
                ->join('etablissement as et', 'o.IDEts_Form', '=', 'et.IDetablissement')
                ->join('dfep as d', 'et.IDDFEP', '=', 'd.IDDFEP')
                ->where('o.IDOffre', (int)$offreId)
                ->where('d.IDWilayaa', (int)$wilayaId)
                ->exists();
            if (!$exists) {
                $offreId = '';
            }
        }

        // 1. Build Query
        $query = DB::table('preinscrit as p')
            ->leftJoin('offre as o', 'p.IDOffre', '=', 'o.IDOffre')
            ->leftJoin('specialite as sp', 'o.IDSpecialite', '=', 'sp.IDSpecialite')
            ->leftJoin('etablissement as et', 'o.IDEts_Form', '=', 'et.IDetablissement')
            ->leftJoin('dfep as d', 'et.IDDFEP', '=', 'd.IDDFEP')
            ->leftJoin('niveau_scol as ns', 'p.IDNiveau_Scol', '=', 'ns.IDNiveau_Scol')
            ->leftJoin('mode_formation as mf', 'o.IDMode_formation', '=', 'mf.IDMode_formation')
            ->leftJoin('identites as idt', 'p.Nin', '=', 'idt.nin')
            ->leftJoin('session as sess', 'o.IDSession', '=', 'sess.IDSession')
            ->leftJoin('semestre_formation as sf', 'sess.IDSemestre_formation', '=', 'sf.IDSemestre_formation')
            ->where('sf.IDAnnee_Formation', '>=', 19)
            ->select(
                'p.*',
                'sp.Nom as spec_nom',
                'et.Nom as etab_nom',
                'ns.Nom as niveau_nom',
                'mf.Nom as mode_nom',
                'idt.nom_ar as correct_nom_ar',
                'idt.prenom_ar as correct_prenom_ar'
            );

        // Role-based scope filters
        if ($role === 'dfep') {
            $query->where('et.IDDFEP', $dfepId);
        } elseif (!in_array($role, ['admin', 'central', 'high_admin'])) {
            // Etablissement role or similar
            $query->where('o.IDEts_Form', $etabId);
        }

        // Enforce Apprenticeship Mode User Scoping
        if ((int)($userSession['IDMode_formation'] ?? 0) === 10) {
            $query->where('o.IDMode_formation', 10);
        }

        // Apply filters
        if ($search !== '') {
            $query->where(function($q) use ($search) {
                $q->where('p.Nin', 'LIKE', "%{$search}%")
                  ->orWhere('p.Nom', 'LIKE', "%{$search}%")
                  ->orWhere('p.Prenom', 'LIKE', "%{$search}%")
                  ->orWhere('p.NomFr', 'LIKE', "%{$search}%")
                  ->orWhere('p.PrenomFr', 'LIKE', "%{$search}%")
                  ->orWhere('idt.nom_ar', 'LIKE', "%{$search}%")
                  ->orWhere('idt.prenom_ar', 'LIKE', "%{$search}%");
            });
        }

        if ($status !== 'all') {
            if ($status === '0' || $status === 0 || $status === '' || is_null($status)) {
                $query->where(function($q) {
                    $q->whereNull('p.Validation')
                      ->orWhere('p.Validation', 0);
                });
            } else {
                $query->where('p.Validation', (int)$status);
            }
        }

        if ($offreId !== '') {
            $query->where('p.IDOffre', (int)$offreId);
        }

        // Dynamic filters (Wilaya, Center, Mode)
        if ($wilayaId !== '' && in_array($role, ['admin', 'central', 'high_admin'])) {
            $query->where('d.IDWilayaa', (int)$wilayaId);
        }

        if ($etablissementId !== '' && in_array($role, ['admin', 'central', 'high_admin', 'dfep'])) {
            $query->where('o.IDEts_Form', (int)$etablissementId);
        }

        if ($modeId !== '') {
            $query->where('o.IDMode_formation', (int)$modeId);
        }

        $preinscriptions = $query->orderBy('p.IDPreinscrit', 'desc')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        // 2. Fetch lookup lists for dropdowns
        $wilayas = [];
        $etablissements = [];
        $modes = DB::table('mode_formation')->select('IDMode_formation as id', 'Nom as name')->orderBy('Nom')->get();

        if (in_array($role, ['admin', 'central', 'high_admin'])) {
            $wilayas = DB::table('wilaya')->select('IDWilayaa as id', 'Nom as name')->orderBy('Nom')->get();
            
            $etablissementsQuery = DB::table('etablissement')
                ->select('etablissement.IDetablissement as id', 'etablissement.Nom as name')
                ->orderBy('etablissement.Nom');
            if ($wilayaId !== '') {
                $etablissementsQuery->join('dfep', 'etablissement.IDDFEP', '=', 'dfep.IDDFEP')
                    ->where('dfep.IDWilayaa', (int)$wilayaId);
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

        // Fetch offers for filter dropdown
        $offersQuery = DB::table('offre')
            ->join('specialite', 'offre.IDSpecialite', '=', 'specialite.IDSpecialite')
            ->join('etablissement', 'offre.IDEts_Form', '=', 'etablissement.IDetablissement')
            ->select('offre.IDOffre as id', 'specialite.Nom as spec_ar', 'etablissement.Nom as etab_ar');

        if ($role === 'dfep') {
            $offersQuery->where('etablissement.IDDFEP', $dfepId);
        } elseif (!in_array($role, ['admin', 'central', 'high_admin'])) {
            $offersQuery->where('offre.IDEts_Form', $etabId);
        }

        if ($wilayaId !== '' && in_array($role, ['admin', 'central', 'high_admin'])) {
            $offersQuery->join('dfep', 'etablissement.IDDFEP', '=', 'dfep.IDDFEP')
                        ->where('dfep.IDWilayaa', (int)$wilayaId);
        }
        if ($etablissementId !== '' && in_array($role, ['admin', 'central', 'high_admin', 'dfep'])) {
            $offersQuery->where('offre.IDEts_Form', (int)$etablissementId);
        }

        $offers = $offersQuery->limit(200)->get();

        return view('admin.preinscrit.index', compact(
            'preinscriptions', 'offers', 'search', 'status', 'offreId', 'role',
            'wilayas', 'etablissements', 'modes', 'wilayaId', 'etablissementId', 'modeId'
        ));
    }

    public function show($id)
    {
        try {
            $pre = DB::table('preinscrit as p')
                ->leftJoin('offre as o', 'p.IDOffre', '=', 'o.IDOffre')
                ->leftJoin('specialite as sp', 'o.IDSpecialite', '=', 'sp.IDSpecialite')
                ->leftJoin('etablissement as et', 'o.IDEts_Form', '=', 'et.IDetablissement')
                ->leftJoin('niveau_scol as ns', 'p.IDNiveau_Scol', '=', 'ns.IDNiveau_Scol')
                ->leftJoin('nationalites as nat', 'p.IDNationalitee', '=', 'nat.IDnationalites')
                ->leftJoin('mode_formation as mf', 'o.IDMode_formation', '=', 'mf.IDMode_formation')
                ->leftJoin('identites as idt', 'p.Nin', '=', 'idt.nin')
                ->select(
                    'p.*',
                    'sp.Nom as spec_nom',
                    'et.Nom as etab_nom',
                    'ns.Nom as niveau_nom',
                    'nat.libelleLatin as nationalite_nom',
                    'mf.Nom as mode_nom',
                    'idt.nom_ar as correct_nom_ar',
                    'idt.prenom_ar as correct_prenom_ar'
                )
                ->where('p.IDPreinscrit', $id)
                ->first();

            if (!$pre) {
                return response()->json(['success' => false, 'message' => 'التسجيل غير موجود'], 404);
            }

            return response()->json(['success' => true, 'data' => $pre]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function action(Request $request)
    {
        $id = (int)$request->input('id');
        $decision = $request->input('decision'); // 'approve' or 'reject'
        $remarque = trim($request->input('remarque', ''));

        try {
            $pre = DB::table('preinscrit')->where('IDPreinscrit', $id)->first();
            if (!$pre) {
                session(['flash_error' => 'طلب التسجيل الأولي غير موجود.']);
                return redirect()->back();
            }

            $wilayaId = (int) DB::table('offre')
                ->join('etablissement', 'offre.IDEts_Form', '=', 'etablissement.IDetablissement')
                ->join('dfep', 'etablissement.IDDFEP', '=', 'dfep.IDDFEP')
                ->where('offre.IDOffre', $pre->IDOffre)
                ->value('dfep.IDWilayaa');

            if ($decision === 'approve') {
                if (!\App\Helpers\SovereignLicensingHelper::checkEnrollmentPermission('add', $wilayaId)) {
                    session(['flash_error' => 'إضافة المترشحين الجدد معطل حالياً لهذه الولاية / L\'inscription est désactivée pour cette wilaya.']);
                    return redirect()->back();
                }
                if (!\App\Helpers\SovereignLicensingHelper::checkEnrollmentPermission('edit', $wilayaId)) {
                    session(['flash_error' => 'تعديل ومعالجة قرارات التسجيل معطل حالياً لهذه الولاية / La modification est désactivée.']);
                    return redirect()->back();
                }
            } else {
                if (!\App\Helpers\SovereignLicensingHelper::checkEnrollmentPermission('edit', $wilayaId)) {
                    session(['flash_error' => 'تعديل ومعالجة قرارات التسجيل معطل حالياً لهذه الولاية / La modification est désactivée.']);
                    return redirect()->back();
                }
            }

            if ($decision === 'approve') {
                // Check if candidate already exists by NIN
                $exists = false;
                if ($pre->Nin) {
                    $exists = DB::table('candidat')->where('Nin', $pre->Nin)->exists();
                }
                
                if ($exists) {
                    session(['flash_error' => 'هذا المترشح مسجل بالفعل في جدول المترشحين بالرقم التعريفي الوطني.']);
                    return redirect()->back();
                }

                DB::transaction(function() use ($pre, $id, $remarque) {
                    // Generate new IDCandidat
                    $maxId = (int)DB::table('candidat')->lockForUpdate()->max('IDCandidat');
                    $newId = max(1, $maxId + 1);

                    // Fetch correct name from identites to fix Arabic encoding corruption
                    $correctNom = $pre->Nom;
                    $correctPrenom = $pre->Prenom;
                    if ($pre->Nin) {
                        $ident = DB::table('identites')->where('nin', $pre->Nin)->first();
                        if ($ident && !empty($ident->nom_ar) && !empty($ident->prenom_ar)) {
                            $correctNom = $ident->nom_ar;
                            $correctPrenom = $ident->prenom_ar;
                        }
                    }

                    // Insert into candidat
                    DB::table('candidat')->insert([
                        'IDCandidat' => $newId,
                        'IDOffre' => $pre->IDOffre,
                        'Nom' => $correctNom,
                        'NomFr' => $pre->NomFr ?? '',
                        'Prenom' => $correctPrenom,
                        'PrenomFr' => $pre->PrenomFr ?? '',
                        'DateNais' => $pre->DateNais,
                        'LieuNais' => $pre->LieuNais,
                        'LieuNaisFr' => $pre->LieuNaisFr,
                        'NumIns' => $pre->NumIns ?? ('INSC-' . $newId),
                        'Civ' => $pre->Civ ?? 1,
                        'Tel' => $pre->tel1,
                        'email' => $pre->email,
                        'Nin' => $pre->Nin,
                        'Validation' => 1, // Approved
                        'boursier' => 0,
                        'dateInscr' => now(),
                        'create_time' => now()
                    ]);

                    // Update preinscrit validation status to approved (1)
                    DB::table('preinscrit')->where('IDPreinscrit', $id)->update([
                        'Validation' => 1,
                        'Valide' => 1,
                        'remarque' => $remarque ?: 'تم القبول والتحويل إلى مترشح رسمي'
                    ]);
                });

                session(['flash_success' => 'تم قبول طلب التسجيل الأولي وتحويله إلى مترشح رسمي بنجاح!']);
            } elseif ($decision === 'reject') {
                DB::table('preinscrit')->where('IDPreinscrit', $id)->update([
                    'Validation' => 2, // Rejected
                    'Valide' => 0,
                    'remarque' => $remarque ?: 'تم رفض الطلب'
                ]);
                session(['flash_success' => 'تم رفض طلب التسجيل الأولي بنجاح.']);
            }

        } catch (Exception $e) {
            session(['flash_error' => 'حدث خطأ أثناء معالجة الطلب: ' . $e->getMessage()]);
        }

        return redirect()->back();
    }
}
