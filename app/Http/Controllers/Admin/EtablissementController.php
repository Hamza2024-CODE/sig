<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Core\AuditLogger;
use Exception;

class EtablissementController extends Controller
{
    /**
     * Show the establishment profile.
     */
    public function show(Request $request)
    {
        $user = session('user');
        if (!$user) {
            return redirect('/login');
        }

        $roleCode = strtolower($user['role_code'] ?? '');
        $isAdminOrCentral = in_array($roleCode, ['admin', 'central', 'high_admin', 'secretaire_general', 'ministre']);
        
        $etab = null;
        $wilayas = [];
        $etablissements = [];
        $selectedEtabId = null;

        if ($isAdminOrCentral) {
            // Fetch all wilayas and etablissements for dropdown selection
            $wilayas = DB::table('wilaya')->orderBy('Num', 'ASC')->get();
            $etablissements = DB::table('etablissement')
                ->select('IDetablissement', 'Nom', 'IDDFEP')
                ->whereNotNull('Nom')
                ->where('Nom', '!=', '')
                ->orderBy('Nom', 'ASC')
                ->get();

            // Determine which establishment to show
            $selectedEtabId = (int)$request->get('etab_id', 0);
            if ($selectedEtabId <= 0) {
                $selectedEtabId = (int)($user['etablissement_id'] ?? 0);
            }
            if ($selectedEtabId <= 0 && count($etablissements) > 0) {
                $selectedEtabId = (int)$etablissements[0]->IDetablissement;
            }

            $etab = DB::table('etablissement')
                ->where('IDetablissement', $selectedEtabId)
                ->first();
        } elseif ($roleCode === 'dfep') {
            $dfepId = $user['iddfep'] ?? $user['IDDFEP'] ?? 0;
            $etab = DB::table('etablissement')
                ->where('IDNature_etsF', 5)
                ->where('IDDFEP', $dfepId)
                ->first();
        } else {
            // profile_etab_id stores the correct account IDetablissement. Falls back to id or etablissement_id.
            $etabId = $user['profile_etab_id'] ?? $user['id'] ?? $user['etablissement_id'] ?? 0;
            $etab = DB::table('etablissement')
                ->where('IDetablissement', $etabId)
                ->first();
        }

        if (!$etab) {
            return redirect()->back()->with('error', 'لا يمكن العثور على سجل المؤسسة المطلوبة.');
        }

        $etab = (array)$etab;

        return view('admin.etablissement.show', [
            'title' => 'ملف المؤسسة / Profil de l\'Établissement',
            'etab' => $etab,
            'roleCode' => $roleCode,
            'user' => $user,
            'isAdminOrCentral' => $isAdminOrCentral,
            'wilayas' => $wilayas,
            'etablissements' => $etablissements,
            'selectedEtabId' => $selectedEtabId
        ]);
    }

    /**
     * Update the establishment profile.
     */
    public function update(Request $request)
    {
        $user = session('user');
        if (!$user) {
            return response()->json(['error' => 'غير مصرح'], 401);
        }

        $request->validate([
            'nom' => 'required|string|max:255',
            'nom_fr' => 'nullable|string|max:255',
            'code' => 'nullable|string|max:100',
            'adres' => 'nullable|string|max:255',
            'obs' => 'nullable|string',
            'etab_id' => 'nullable|integer',
        ]);

        $roleCode = strtolower($user['role_code'] ?? '');
        $isAdminOrCentral = in_array($roleCode, ['admin', 'central', 'high_admin', 'secretaire_general', 'ministre']);
        
        try {
            $updateData = [
                'Nom' => trim($request->input('nom')),
                'NomFr' => trim($request->input('nom_fr')),
                'Code' => trim($request->input('code')),
                'Adres' => trim($request->input('adres')),
                'Obs' => trim($request->input('obs')),
            ];

            if ($isAdminOrCentral) {
                $etabId = (int)$request->input('etab_id');
                if ($etabId <= 0) {
                    return response()->json(['error' => 'معرف المؤسسة غير صالح'], 400);
                }

                DB::table('etablissement')
                    ->where('IDetablissement', $etabId)
                    ->update($updateData);
                
                $etab = DB::table('etablissement')
                    ->where('IDetablissement', $etabId)
                    ->first();
            } elseif ($roleCode === 'dfep') {
                $dfepId = $user['iddfep'] ?? $user['IDDFEP'] ?? 0;
                DB::table('etablissement')
                    ->where('IDNature_etsF', 5)
                    ->where('IDDFEP', $dfepId)
                    ->update($updateData);
                
                $etab = DB::table('etablissement')
                    ->where('IDNature_etsF', 5)
                    ->where('IDDFEP', $dfepId)
                    ->first();
            } else {
                // profile_etab_id stores the correct account IDetablissement. Falls back to id or etablissement_id.
                $etabId = $user['profile_etab_id'] ?? $user['id'] ?? $user['etablissement_id'] ?? 0;
                DB::table('etablissement')
                    ->where('IDetablissement', $etabId)
                    ->update($updateData);
 
                $etab = DB::table('etablissement')
                    ->where('IDetablissement', $etabId)
                    ->first();
            }

            if ($etab) {
                AuditLogger::log('UPDATE', 'etablissement', $etab->IDetablissement);
            }

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث بيانات ملف المؤسسة بنجاح.'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'حدث خطأ أثناء التحديث: ' . $e->getMessage()
            ], 500);
        }
    }
}
