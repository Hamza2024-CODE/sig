<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class IdentityController extends Controller
{
    private const PER_PAGE = 30;

    public function index(Request $request)
    {
        $user = session('user');
        if (!$user) return redirect('/login');

        // Check if role is authorized (admin, central, high_admin, dfep, etablissement, directeur, employee)
        $role = strtolower($user['role_code'] ?? '');
        if (!in_array($role, ['admin', 'central', 'high_admin', 'dfep', 'etablissement', 'directeur', 'employee'])) {
            abort(403, 'غير مصرح للوصول لهذه الصفحة');
        }

        $search = trim($request->input('search', ''));
        $filterRole = $request->input('role', ''); // 'employee', 'trainee', 'unregistered', ''
        $sexe = $request->input('sexe', '');

        // 1. Build Query for identites
        $query = DB::table('identites');

        if ($search !== '') {
            $query->where(function($q) use ($search) {
                $q->where('nin', 'LIKE', "%{$search}%")
                  ->orWhere('nom', 'LIKE', "%{$search}%")
                  ->orWhere('prenom', 'LIKE', "%{$search}%")
                  ->orWhere('nom_ar', 'LIKE', "%{$search}%")
                  ->orWhere('prenom_ar', 'LIKE', "%{$search}%");
            });
        }

        if ($sexe !== '') {
            $query->where('sexe', $sexe);
        }

        // Apply role filter in SQL using whereExists/whereNotExists
        if ($filterRole === 'employee') {
            $query->whereExists(function ($q) {
                $q->select(DB::raw(1))
                  ->from('encadrement')
                  ->whereRaw('encadrement.nin = identites.nin');
            });
        } elseif ($filterRole === 'trainee') {
            $query->whereExists(function ($q) {
                $q->select(DB::raw(1))
                  ->from('candidat')
                  ->whereRaw('candidat.Nin = identites.nin');
            });
        } elseif ($filterRole === 'unregistered') {
            $query->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                  ->from('encadrement')
                  ->whereRaw('encadrement.nin = identites.nin');
            })->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                  ->from('candidat')
                  ->whereRaw('candidat.Nin = identites.nin');
            });
        }

        // Apply pagination
        $identitiesPaginated = $query->paginate(self::PER_PAGE)->withQueryString();

        $items = [];
        foreach ($identitiesPaginated->items() as $item) {
            $nin = $item->nin;

            // Check if Employee
            $employee = DB::table('encadrement as e')
                ->select('e.IDEncadrement', 'e.Nom', 'e.Prenom', 'et.Nom as etab_nom')
                ->leftJoin('etablissement as et', 'e.IDetablissement', '=', 'et.IDetablissement')
                ->where('e.nin', $nin)
                ->first();

            // Check if Trainee
            $trainee = DB::table('apprenant as a')
                ->select('a.IDapprenant', 'c.Nom', 'c.Prenom', 'sp.Nom as spec_nom', 'et.Nom as etab_nom')
                ->join('candidat as c', 'a.IDCandidat', '=', 'c.IDCandidat')
                ->leftJoin('section as s', 'a.IDSection', '=', 's.IDSection')
                ->leftJoin('offre as o', 's.IDOffre', '=', 'o.IDOffre')
                ->leftJoin('specialite as sp', 'o.IDSpecialite', '=', 'sp.IDSpecialite')
                ->leftJoin('etablissement as et', 'o.IDEts_Form', '=', 'et.IDetablissement')
                ->where('c.Nin', $nin)
                ->first();

            $status = 'unregistered';
            $details = null;

            if ($employee) {
                $status = 'employee';
                $details = [
                    'role_label' => 'موظف / مؤطر',
                    'etab' => $employee->etab_nom ?: 'غير محدد',
                    'link' => '/sig/dashboard/rh-gestion?tab=personnel'
                ];
            } elseif ($trainee) {
                $status = 'trainee';
                $details = [
                    'role_label' => 'متربص / متمهن',
                    'etab' => $trainee->etab_nom ?: 'غير محدد',
                    'spec' => $trainee->spec_nom ?: 'غير محدد',
                    'link' => '/sig/dashboard/encadrement'
                ];
            } else {
                $details = [
                    'role_label' => 'غير مسجل بالقطاع',
                    'etab' => '—'
                ];
            }

            $items[] = [
                'identity' => $item,
                'status' => $status,
                'details' => $details
            ];
        }

        return view('admin.identities.index', compact('identitiesPaginated', 'items', 'search', 'filterRole', 'sexe'));
    }

    public function show($nin)
    {
        $user = session('user');
        if (!$user) return response()->json(['error' => 'غير مصرح'], 401);

        try {
            $id = DB::table('identites')->where('nin', $nin)->first();
            $legacy = DB::table('identite')->where('nin', $nin)->first();

            if (!$id && !$legacy) {
                return response()->json(['success' => false, 'message' => 'الهوية غير موجودة بالسجل'], 404);
            }

            if (!$id) {
                // Map legacy to clean structure if not in identites
                $id = (object)[
                    'nin' => $legacy->nin,
                    'nom' => $legacy->nom_f,
                    'prenom' => $legacy->pren_f,
                    'nom_ar' => $legacy->nom_a,
                    'prenom_ar' => $legacy->pren_a,
                    'date_naissance' => $legacy->d_nais,
                    'presume' => $legacy->Presume,
                    'Date_presume' => '',
                    'commune_naissance' => $legacy->codecomm,
                    'pays_naissance' => 'DZ',
                    'sexe' => $legacy->sexe,
                    'numero_acte_naissance' => $legacy->acteN,
                    'date_deces' => $legacy->isDied ? 'متوفى' : null
                ];
            }

            // Extract additional fields from legacy table if available
            $extra = [
                'pren_pere' => $legacy ? $legacy->pren_pere : null,
                'nom_mere' => $legacy ? $legacy->nom_mere : null,
                'pren_mere' => $legacy ? $legacy->pren_mere : null,
                'epoux' => $legacy ? $legacy->epoux : null,
                'epouses' => $legacy ? $legacy->epouses : null,
                'lieu_nais' => $legacy ? $legacy->lieu_nais : null,
                'isDied' => $legacy ? $legacy->isDied : null,
                'isMaried' => $legacy ? $legacy->isMaried : null,
            ];

            // Get historical connections
            $employee = DB::table('encadrement as e')
                ->select('e.IDEncadrement', 'e.Nom', 'e.Prenom', 'et.Nom as etab_nom')
                ->leftJoin('etablissement as et', 'e.IDetablissement', '=', 'et.IDetablissement')
                ->where('e.nin', $nin)
                ->first();

            $trainee = DB::table('apprenant as a')
                ->select('a.IDapprenant', 'c.Nom', 'c.Prenom', 'sp.Nom as spec_nom', 'et.Nom as etab_nom')
                ->join('candidat as c', 'a.IDCandidat', '=', 'c.IDCandidat')
                ->leftJoin('section as s', 'a.IDSection', '=', 's.IDSection')
                ->leftJoin('offre as o', 's.IDOffre', '=', 'o.IDOffre')
                ->leftJoin('specialite as sp', 'o.IDSpecialite', '=', 'sp.IDSpecialite')
                ->leftJoin('etablissement as et', 'o.IDEts_Form', '=', 'et.IDetablissement')
                ->where('c.Nin', $nin)
                ->first();

            return response()->json([
                'success' => true,
                'data' => $id,
                'extra' => $extra,
                'employee' => $employee,
                'trainee' => $trainee
            ]);

        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
