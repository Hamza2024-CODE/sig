<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Domains\Assets\Repositories\AssetRepository;
use App\Models\UserPreferences;
use App\Core\AuditLogger;
use Illuminate\Http\Request;
use Exception;

class PatrimoineController extends Controller
{
    protected AssetRepository $repo;

    public function __construct()
    {
        $this->repo = new AssetRepository();
    }

    public function index(Request $request)
    {
        $user = session('user');
        if (!$user) return redirect('/login');
        
        $etabId = (int)($user['etablissement_id'] ?? 0);
        $roleCode = strtolower($user['role_code'] ?? '');
        $prefs = UserPreferences::forUser($user);
        $tab = $request->get('tab', 'equipements');

        // Role-based scope
        $dfepId = 0;
        if ($roleCode === 'admin') {
            $etabId = (int)$request->get('filter_etablissement', 0);
            $dfepId = (int)$request->get('filter_wilaya', 0);
        } elseif (in_array($roleCode, ['dfep', 'central'])) {
            $dfepId = (int)($user['iddfep'] ?? $user['IDDFEP'] ?? 0);
            $etabId = (int)$request->get('filter_etablissement', 0);
        }

        // Fetch data
        $equipements = $this->repo->findEquipmentsByEtablissement($etabId, $dfepId);
        $vehicules = $this->repo->getVehicules($etabId, $dfepId);
        $locaux = $this->repo->getLocaux($etabId, $dfepId);
        $logements = $this->repo->getLogements($etabId, $dfepId);
        $proprietes = $this->repo->getProprietes();

        // Stats summary
        $stats = [
            'total_equipements' => $this->repo->getEquipmentsCount($etabId, $dfepId),
            'total_vehicules' => $this->repo->getVehiculesCount($etabId, $dfepId),
            'total_locaux' => $this->repo->getLocauxCount($etabId, $dfepId),
            'total_logements' => $this->repo->getLogementsCount($etabId, $dfepId)
        ];

        $finRepo     = new \App\Domains\Finance\Repositories\FinanceRepository();
        $wilayas     = $finRepo->getWilayas();
        $etabsByDfep = $finRepo->getEtabsForFilter();
        $etatTypes   = \Illuminate\Support\Facades\DB::table('idequipement_etattype')->get();

        // New lookup tables for Housing and Locals
        $logementTypes       = \Illuminate\Support\Facades\DB::table('logementtype')->get();
        $logementOccupations = \Illuminate\Support\Facades\DB::table('logementoccupe')->get();
        $logementNatures     = \Illuminate\Support\Facades\DB::table('logement_nature')->get();
        $logementCauses      = \Illuminate\Support\Facades\DB::table('logement_causeoccup')->get();
        $logementJuridiques  = \Illuminate\Support\Facades\DB::table('logementnaturejur')->get();
        
        $localTypes  = \Illuminate\Support\Facades\DB::table('localtype')->get();
        $localStates = \Illuminate\Support\Facades\DB::table('locaux_etatactual')->get();

        return view('admin.patrimoine.index', compact(
            'user', 'etabId', 'dfepId', 'roleCode', 'prefs', 'tab',
            'equipements', 'vehicules', 'locaux', 'logements', 'proprietes', 'stats',
            'wilayas', 'etabsByDfep', 'etatTypes',
            'logementTypes', 'logementOccupations', 'logementNatures', 'logementCauses', 'logementJuridiques',
            'localTypes', 'localStates'
        ));
    }

    public function storeEquipment(Request $request)
    {
        $user = session('user');
        if (!$user) return response()->json(['error' => 'غير مصرح'], 401);
        try {
            $data = $request->only(['designation', 'designation_fr', 'code', 'date_exploitation', 'date_reception', 'description', 'etablissement_id', 'etat_id']);
            $data['etablissement_id'] = $data['etablissement_id'] ?? ($user['etablissement_id'] ?? 0);
            
            $id = $this->repo->insertEquipmentRequest($data);
            AuditLogger::log('CREATE', 'equipement', $id);
            return response()->json(['success' => true, 'id' => $id, 'message' => 'تم إضافة التجهيز بنجاح']);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function storeVehicule(Request $request)
    {
        $user = session('user');
        if (!$user) return response()->json(['error' => 'غير مصرح'], 401);
        try {
            $data = $request->only(['immatriculation', 'annee', 'marque', 'chassis', 'places', 'immatriculation_prec', 'etab_id']);
            $data['etab_id'] = $data['etab_id'] ?? ($user['etablissement_id'] ?? 0);

            $id = $this->repo->insertVehicule($data);
            AuditLogger::log('CREATE', 'vehicule', $id);
            return response()->json(['success' => true, 'id' => $id, 'message' => 'تم إضافة المركبة بنجاح']);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function storeLocal(Request $request)
    {
        $user = session('user');
        if (!$user) return response()->json(['error' => 'غير مصرح'], 401);
        try {
            $data = $request->only(['nom', 'nom_fr', 'places', 'tables', 'chaises', 'etab_id', 'type_id', 'etat_id', 'etage', 'tableaffichage', 'datashow', 'climatiseur']);
            $data['etab_id'] = $data['etab_id'] ?? ($user['etablissement_id'] ?? 0);

            $id = $this->repo->insertLocal($data);
            AuditLogger::log('CREATE', 'locaux', $id);
            return response()->json(['success' => true, 'id' => $id, 'message' => 'تم إضافة المقر البيداغوجي بنجاح']);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function storeLogement(Request $request)
    {
        $user = session('user');
        if (!$user) return response()->json(['error' => 'غير مصرح'], 401);
        try {
            $data = $request->only(['surface', 'interne_externe', 'adresse', 'observation', 'etab_id', 'type_id', 'nature_id', 'occup_id', 'cause_id', 'juridique_id', 'occupant_nom', 'etage', 'structuree', 'gaz', 'electricite', 'eau', 'etat']);
            $data['etab_id'] = $data['etab_id'] ?? ($user['etablissement_id'] ?? 0);

            $id = $this->repo->insertLogement($data);
            AuditLogger::log('CREATE', 'logement', $id);
            return response()->json(['success' => true, 'id' => $id, 'message' => 'تم إضافة السكن الوظيفي بنجاح']);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function updatePhoto(Request $request)
    {
        $user = session('user');
        if (!$user) return response()->json(['error' => 'غير مصرح'], 401);

        $enabled = \App\Helpers\SovereignLicensingHelper::getSetting('patrimoine_media_actions_enabled', '1') === '1';
        if (!$enabled) {
            return response()->json(['error' => 'تم تعطيل تعديل صور الممتلكات من طرف المسؤول.'], 403);
        }

        $request->validate([
            'type'   => 'required|in:equipment,vehicule,logement,candidat,encadrement',
            'id'     => 'required|integer',
            'action' => 'required|in:upload,delete',
            'photo'  => 'nullable|image|max:10240',
        ]);

        $type   = $request->input('type');
        $id     = (int)$request->input('id');
        $action = $request->input('action');

        $dbTable = match ($type) {
            'equipment'   => 'equipement_memo',
            'vehicule'    => 'vehicule_memo',
            'logement'    => 'logement_memo',
            'candidat'    => 'candidat_memo',
            'encadrement' => 'encadremen_memo',
        };

        $fkName = match ($type) {
            'equipment'   => 'IDEquipement',
            'vehicule'    => 'IDVehicule',
            'logement'    => 'IDLogement',
            'candidat'    => 'IDCandidat',
            'encadrement' => 'IDEncadrement',
        };

        $dirName = match ($type) {
            'equipment'   => 'equipement_memo',
            'vehicule'    => 'vehicule_memo',
            'logement'    => 'logement_memo',
            'candidat'    => 'candidat_memo',
            'encadrement' => 'encadremen_memo',
        };

        try {
            if ($action === 'delete') {
                // Delete photo record in DB
                \Illuminate\Support\Facades\DB::table($dbTable)
                    ->where($fkName, $id)
                    ->update(['photo' => null]);

                AuditLogger::log('DELETE_PHOTO', $dbTable, $id);

                return response()->json([
                    'success' => true,
                    'message' => 'تم حذف الصورة بنجاح'
                ]);
            }

            if ($action === 'upload') {
                if (!$request->hasFile('photo')) {
                    return response()->json(['error' => 'يرجى إرسال ملف الصورة'], 400);
                }

                $file = $request->file('photo');
                $destDir = public_path("uploads/hfsql_sync/{$dirName}/photo");

                if (!is_dir($destDir)) {
                    mkdir($destDir, 0755, true);
                }

                $filename = "{$id}.jpg";
                $file->move($destDir, $filename);

                $photoPath = "/uploads/hfsql_sync/{$dirName}/photo/{$filename}";

                $pkName = match ($type) {
                    'equipment'   => 'IDEquipement_memo',
                    'vehicule'    => 'IDVehicule_memo',
                    'logement'    => 'IDLogement_memo',
                    'candidat'    => 'IDCandidat_memo',
                    'encadrement' => 'IDEncadremen_memo',
                };

                $memoRow = \Illuminate\Support\Facades\DB::table($dbTable)->where($fkName, $id)->first();
                if ($memoRow) {
                    \Illuminate\Support\Facades\DB::table($dbTable)
                        ->where($fkName, $id)
                        ->update(['photo' => $photoPath]);
                } else {
                    $newPk = (int)(\Illuminate\Support\Facades\DB::table($dbTable)->max($pkName) ?: 0) + 1;
                    \Illuminate\Support\Facades\DB::table($dbTable)->insert([
                        $pkName => $newPk,
                        $fkName => $id,
                        'photo' => $photoPath
                    ]);
                }

                AuditLogger::log('UPLOAD_PHOTO', $dbTable, $id);

                return response()->json([
                    'success' => true,
                    'photo'   => $photoPath,
                    'message' => 'تم تحديث الصورة بنجاح'
                ]);
            }
        } catch (Exception $e) {
            return response()->json(['error' => 'حدث خطأ: ' . $e->getMessage()], 500);
        }

        return response()->json(['error' => 'إجراء غير معروف'], 400);
    }
}
