<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Domains\HR\Repositories\RHRepository;
use App\Models\UserPreferences;
use App\Core\AuditLogger;
use Illuminate\Http\Request;
use Exception;

class RHController extends Controller
{
    protected RHRepository $repo;

    public function __construct()
    {
        $this->repo = new RHRepository();
    }

    public function index(Request $request)
    {
        $user = session('user');
        if (!$user) return redirect('/login');

        $etabId = (int)($user['etablissement_id'] ?? 0);
        $roleCode = strtolower($user['role_code'] ?? '');
        $prefs = UserPreferences::forUser($user);
        $tab = $request->get('tab', 'personnel');

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
        $personnel = $this->repo->getPersonnel($etabId, $dfepId);
        foreach ($personnel as &$p) {
            if (!empty($p['nin'])) {
                try {
                    $p['nin'] = \Illuminate\Support\Facades\Crypt::decryptString($p['nin']);
                } catch (\Exception $e) {}
            }
        }
        $recrutements = $this->repo->getRecrutements($etabId, $dfepId);
        $formations = $this->repo->getFormations($etabId, $dfepId);
        $activites = $this->repo->getActivites($etabId, $dfepId);
        $competances = $this->repo->getCompetances($etabId, $dfepId);

        // Reference lists
        $allGrades = $this->repo->getGrades();
        $allFonctions = $this->repo->getFonctions();
        $modesRecr = $this->repo->getModesRecrutement();
        $situationTypes = \Illuminate\Support\Facades\DB::table('idsituationadministrat_type')->get();
        $corpsTypes = \Illuminate\Support\Facades\DB::table('nomenclaturecorp')->orderBy('NumOrd')->get();
        $staffEducationLevels = \Illuminate\Support\Facades\DB::table('niveau_scol_enca')->get();
        $nationalities = \Illuminate\Support\Facades\DB::table('nationalites')->get();

        // Stats summary — use real COUNT(*) to avoid LIMIT distortion
        $stats = [
            'total_staff'      => $this->repo->getPersonnelCount($etabId, $dfepId),
            'total_recrutements' => $this->repo->getRecrutementsCount($etabId, $dfepId),
            'total_formations' => $this->repo->getFormationsCount($etabId, $dfepId),
            'total_activites'  => $this->repo->getActivitesCount($etabId, $dfepId),
            'total_competances' => $this->repo->getCompetancesCount($etabId, $dfepId),
        ];


        $finRepo     = new \App\Domains\Finance\Repositories\FinanceRepository();
        $wilayas     = $finRepo->getWilayas();
        $etabsByDfep = $finRepo->getEtabsForFilter();

        return view('admin.rh-gestion.index', compact(
            'user', 'etabId', 'dfepId', 'roleCode', 'prefs', 'tab',
            'personnel', 'recrutements', 'formations', 'activites', 'competances',
            'allGrades', 'allFonctions', 'modesRecr', 'stats',
            'wilayas', 'etabsByDfep', 'situationTypes',
            'corpsTypes', 'staffEducationLevels', 'nationalities'
        ));

    }

    public function storePersonnel(Request $request)
    {
        $user = session('user');
        if (!$user) return response()->json(['error' => 'غير مصرح'], 401);
        try {
            $data = $request->only(['nom', 'prenom', 'specialite', 'date_recrutement', 'nin', 'photo', 'grade_id', 'fonction_id', 'mode_recrutement_id', 'date_installation', 'etab_id', 'situation_id', 'niveau_scol_id']);
            $data['etab_id'] = $data['etab_id'] ?? ($user['etablissement_id'] ?? 0);

            $id = $this->repo->insertPersonnel($data);
            AuditLogger::log('CREATE', 'encadrement', $id);
            return response()->json(['success' => true, 'id' => $id, 'message' => 'تم إضافة الموظف بنجاح']);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function storeFormation(Request $request)
    {
        $user = session('user');
        if (!$user) return response()->json(['error' => 'غير مصرح'], 401);
        try {
            $data = $request->only(['encadrement_id', 'theme', 'date_debut', 'date_fin', 'etablissement_formation', 'date_attestation']);
            
            $id = $this->repo->insertFormation($data);
            AuditLogger::log('CREATE', 'stageperfectionnemnt', $id);
            return response()->json(['success' => true, 'id' => $id, 'message' => 'تم تسجيل دورة التكوين بنجاح']);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function storeActivite(Request $request)
    {
        $user = session('user');
        if (!$user) return response()->json(['error' => 'غير مصرح'], 401);
        try {
            $data = $request->only(['nom', 'nom_fr', 'date_debut', 'date_fin', 'lieu', 'description', 'etab_id']);
            $data['etab_id'] = $data['etab_id'] ?? ($user['etablissement_id'] ?? 0);

            $id = $this->repo->insertActivite($data);
            AuditLogger::log('CREATE', 'activite', $id);
            return response()->json(['success' => true, 'id' => $id, 'message' => 'تم إضافة النشاط بنجاح']);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function storeCompetance(Request $request)
    {
        $user = session('user');
        if (!$user) return response()->json(['error' => 'غير مصرح'], 401);
        try {
            $data = $request->only(['nom_prenom', 'grade', 'diplome', 'specialite', 'tel', 'date_naissance', 'etab_id']);
            $data['etab_id'] = $data['etab_id'] ?? ($user['etablissement_id'] ?? 0);

            $id = $this->repo->insertCompetance($data);
            AuditLogger::log('CREATE', 'competance', $id);
            return response()->json(['success' => true, 'id' => $id, 'message' => 'تم إضافة المكون بنجاح']);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function updateCompetance(Request $request)
    {
        $user = session('user');
        if (!$user) return response()->json(['error' => 'غير مصرح'], 401);
        try {
            $id = (int)$request->input('id');
            $data = $request->only(['nom_prenom', 'grade', 'diplome', 'specialite', 'tel', 'date_naissance', 'etab_id']);
            $data['etab_id'] = $data['etab_id'] ?? ($user['etablissement_id'] ?? 0);

            $this->repo->updateCompetance($id, $data);
            AuditLogger::log('UPDATE', 'competance', $id);
            return response()->json(['success' => true, 'message' => 'تم تحديث بيانات المكون بنجاح']);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function destroyCompetance(Request $request, $id)
    {
        $user = session('user');
        if (!$user) return response()->json(['error' => 'غير مصرح'], 401);
        try {
            $this->repo->deleteCompetance((int)$id);
            AuditLogger::log('DELETE', 'competance', $id);
            return response()->json(['success' => true, 'message' => 'تم حذف المكون بنجاح']);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
