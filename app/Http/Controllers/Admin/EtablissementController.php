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
        $dfepId = $user['iddfep'] ?? $user['IDDFEP'] ?? 0;
        
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
        } elseif ($roleCode === 'dfep' && $dfepId > 0) {
            // DFEP: Can view/manage all establishments in their Wilaya
            $etablissements = DB::table('etablissement')
                ->select('IDetablissement', 'Nom', 'IDDFEP')
                ->where('IDDFEP', $dfepId)
                ->whereNotNull('Nom')
                ->where('Nom', '!=', '')
                ->orderBy('Nom', 'ASC')
                ->get();

            $selectedEtabId = (int)$request->get('etab_id', 0);
            if ($selectedEtabId <= 0) {
                // Fall back to DFEP's own profile establishment
                $myEtab = DB::table('etablissement')
                    ->where('IDNature_etsF', 5)
                    ->where('IDDFEP', $dfepId)
                    ->first();
                $selectedEtabId = $myEtab ? (int)$myEtab->IDetablissement : 0;
            }

            $etab = DB::table('etablissement')
                ->where('IDetablissement', $selectedEtabId)
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

        // Fetch public establishments in the same Wilaya to use for linking if the establishment is private
        $publicEtabs = [];
        if ((int)($etab['PublPrive'] ?? 0) === 1) {
            $publicEtabs = DB::table('etablissement')
                ->where('IDDFEP', $etab['IDDFEP'])
                ->where('PublPrive', 0)
                ->select('IDetablissement as id', 'Nom as nom_ar')
                ->orderBy('Nom', 'ASC')
                ->get();
        }

        return view('admin.etablissement.show', [
            'title' => 'ملف المؤسسة / Profil de l\'Établissement',
            'etab' => $etab,
            'roleCode' => $roleCode,
            'user' => $user,
            'isAdminOrCentral' => $isAdminOrCentral,
            'isAdminOrCentralOrDfep' => ($isAdminOrCentral || $roleCode === 'dfep'),
            'wilayas' => $wilayas,
            'etablissements' => $etablissements,
            'selectedEtabId' => $selectedEtabId,
            'publicEtabs' => $publicEtabs
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
            'parent_etab_id' => 'nullable|integer',
            'ratache_cfpa_id' => 'nullable|integer',
            'ratache_insfp_id' => 'nullable|integer',
        ]);

        $roleCode = strtolower($user['role_code'] ?? '');
        $isAdminOrCentral = in_array($roleCode, ['admin', 'central', 'high_admin', 'secretaire_general', 'ministre']);
        $dfepId = $user['iddfep'] ?? $user['IDDFEP'] ?? 0;
        
        try {
            $updateData = [
                'Nom' => trim($request->input('nom')),
                'NomFr' => trim($request->input('nom_fr')),
                'Code' => trim($request->input('code')),
                'Adres' => trim($request->input('adres')),
                'Obs' => trim($request->input('obs')),
            ];

            // Determine if the user has permission to manage the selected establishment
            $canManage = false;
            $targetEtabId = 0;

            if ($isAdminOrCentral) {
                $targetEtabId = (int)$request->input('etab_id');
                $canManage = ($targetEtabId > 0);
            } elseif ($roleCode === 'dfep' && $dfepId > 0) {
                $targetEtabId = (int)$request->input('etab_id');
                // Ensure the establishment is in the DFEP's Wilaya
                $targetEtab = DB::table('etablissement')->where('IDetablissement', $targetEtabId)->first();
                if ($targetEtab && (int)$targetEtab->IDDFEP === $dfepId) {
                    $canManage = true;
                }
            } else {
                $targetEtabId = $user['profile_etab_id'] ?? $user['id'] ?? $user['etablissement_id'] ?? 0;
                $canManage = ($targetEtabId > 0 && $targetEtabId === (int)$request->input('etab_id', $targetEtabId));
            }

            if (!$canManage || $targetEtabId <= 0) {
                return response()->json(['error' => 'غير مصرح لك بتعديل هذه المؤسسة.'], 403);
            }

            // Retrieve current establishment to check if it's private
            $etab = DB::table('etablissement')->where('IDetablissement', $targetEtabId)->first();
            if (!$etab) {
                return response()->json(['error' => 'المؤسسة غير موجودة.'], 404);
            }

            // If private, allow updating supervising public centers (DFEP / Admin only)
            if ((int)$etab->PublPrive === 1 && ($isAdminOrCentral || $roleCode === 'dfep')) {
                $parentId = (int)$request->input('parent_etab_id', 0);
                $cfpaId = (int)$request->input('ratache_cfpa_id', 0);
                $insfpId = (int)$request->input('ratache_insfp_id', 0);

                $updateData['IDEts_Form'] = $parentId;
                $updateData['DeIDetablissementRatache'] = $cfpaId;
                $updateData['DeIDetablissementRatacheInsfp'] = $insfpId;

                // Dynamically cascade to update existing offers and sections supervising center (IDEts_FormM)
                $supervisingId = $insfpId > 0 ? $insfpId : ($cfpaId > 0 ? $cfpaId : $parentId);
                if ($supervisingId > 0) {
                    DB::table('offre')
                        ->where('IDEts_Form', $targetEtabId)
                        ->update(['IDEts_FormM' => $supervisingId]);
                    
                    DB::table('section')
                        ->where('IDEts_Form', $targetEtabId)
                        ->update(['IDEts_FormM' => $supervisingId]);
                }
            }

            DB::table('etablissement')
                ->where('IDetablissement', $targetEtabId)
                ->update($updateData);
            
            // Log update
            AuditLogger::log('UPDATE', 'etablissement', $targetEtabId);

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
