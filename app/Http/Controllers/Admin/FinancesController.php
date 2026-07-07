<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Domains\Finance\Repositories\FinanceRepository;
use App\Models\UserPreferences;
use App\Core\AuditLogger;
use Illuminate\Http\Request;
use Exception;

/**
 * FinancesController — Full CRUD for financial management
 * Covers: etablissement_grade (رتب/أجور), programme, sous_programme, fournisseur, budget, operations, bourse, articlea
 */
class FinancesController extends Controller
{
    protected FinanceRepository $repo;

    public function __construct()
    {
        $this->repo = new FinanceRepository();
    }

    // =========================================================================
    // MAIN INDEX — Dashboard financier
    // =========================================================================

    public function index(Request $request)
    {
        @set_time_limit(300);
        $user    = session('user');
        if (!$user) return redirect('/login');
        $roleCode = strtolower($user['role_code'] ?? '');
        $prefs   = UserPreferences::forUser($user);

        // Filters & pagination
        $page    = (int)$request->get('page', 1);
        $perPage = $prefs->items_per_page ?? 25;
        $tab     = $request->get('tab', 'grades');
        $filters = [
            'categorie' => $request->get('categorie', ''),
            'annee'     => $request->get('annee', ''),
            'search'    => $request->get('search', ''),
        ];

        // ── Role-based scope resolution ────────────────────────────────────────
        // admin              → etabId=0, dfepId=0  (all data, no filter)
        // dfep / central     → etabId=0, dfepId=X  (wilaya scope)
        // directeur / etab   → etabId=X, dfepId=0  (single establishment)
        $etabId = 0;
        $dfepId = 0;

        if ($roleCode === 'admin') {
            $etabId = (int)$request->get('filter_etablissement', 0);
            $dfepId = (int)$request->get('filter_wilaya', 0);
        } elseif (in_array($roleCode, ['dfep', 'central'])) {
            $dfepId = (int)($user['iddfep'] ?? $user['IDDFEP'] ?? 0);
            $etabId = (int)$request->get('filter_etablissement', 0);
        } else {
            $etabId = (int)($user['etablissement_id'] ?? 0);
        }

        $stats       = $this->repo->getStatsBudget($etabId, $dfepId);
        $bourseStats = $this->repo->getBoursesStats($etabId, $dfepId);
        $opStats     = $this->repo->getOperationsStats($etabId, $dfepId);
        $budgetStats = $this->repo->getBudgetsStats($etabId, $dfepId);
        $stockCount  = $this->repo->getStocksStats();
        $progCount   = $this->repo->getProgrammesStats();

        $grades      = $this->repo->getGradesEtablissement($etabId, $page, $perPage, $filters, $dfepId);
        $programmes  = $this->repo->getProgrammes();
        $sousprog    = $this->repo->getSousProgrammes();
        $fournisseurs= $this->repo->getFournisseurs(
            $request->get('search_fournisseur', ''), $page, $perPage
        );
        $allGrades   = $this->repo->getAllGrades();
        $modesRecr   = $this->repo->getModesRecrutement();
        $annees      = $this->repo->getDistinctAnnees();

        // Extended tabs data
        $budgets      = $this->repo->getBudgets($etabId, $dfepId);
        $operations   = $this->repo->getOperations($etabId, $dfepId);
        $bourses      = $this->repo->getBourses($etabId, $dfepId);
        $stocks       = $this->repo->getStocks();
        $etabDetails  = $this->repo->getEtablissementDetails($etabId);
        $apprenants   = $this->repo->getApprenantsList($etabId, $dfepId);
        $bourseWilayaStats = \Illuminate\Support\Facades\Cache::remember('sgfep:kpi:bourse_wilaya_stats:' . $dfepId . '_' . $etabId, 900, function() use ($dfepId, $etabId) {
            return $this->repo->getBoursesWilayaStats($dfepId, $etabId);
        });
        $employeeWilayaStats = \Illuminate\Support\Facades\Cache::remember('sgfep:kpi:employee_wilaya_stats:' . $dfepId . '_' . $etabId, 900, function() use ($dfepId, $etabId) {
            return $this->repo->getEmployeesWilayaStats($dfepId, $etabId);
        });

        $wilayas     = $this->repo->getWilayas();
        $etabsByDfep = $this->repo->getEtabsForFilter();
        $indemnities = \Illuminate\Support\Facades\DB::table('indimnite_ret')->orderBy('CODE_INDM')->get();

        $userDfepName = null;
        if ($dfepId > 0) {
            $userDfepName = \Illuminate\Support\Facades\DB::table('dfep')->where('IDDFEP', $dfepId)->value('Nom');
        }

        return view('admin.finances.index', compact(
            'user', 'etabId', 'dfepId', 'roleCode', 'prefs',
            'stats', 'bourseStats', 'opStats', 'budgetStats', 'stockCount', 'progCount',
            'grades', 'programmes', 'sousprog', 'fournisseurs',
            'allGrades', 'modesRecr', 'annees',
            'tab', 'filters', 'page', 'perPage',
            'budgets', 'operations', 'bourses', 'stocks', 'etabDetails', 'apprenants',
            'wilayas', 'etabsByDfep', 'indemnities', 'bourseWilayaStats', 'employeeWilayaStats', 'userDfepName'
        ));
    }

    // =========================================================================
    // GRADE CRUD
    // =========================================================================

    public function storeGrade(Request $request)
    {
        $user = session('user');
        if (!$user) return response()->json(['error' => 'غير مصرح'], 401);
        try {
            $data = $request->only(['grade_id','etab_id','nbr','nbr_f','occupe','vacant','besoin','surplus','categorie','annee','indice','indice_inf','indice_moy','indice_sup','traitement','primes','depense']);
            $data['etab_id'] = $data['etab_id'] ?? ($user['etablissement_id'] ?? 0);
            $id = $this->repo->insertGrade($data);
            AuditLogger::log('CREATE', 'etablissement_grade', $id);
            return response()->json(['success' => true, 'id' => $id, 'message' => 'تم إضافة المنصب المالي بنجاح']);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function updateGrade(Request $request)
    {
        $user = session('user');
        if (!$user) return response()->json(['error' => 'غير مصرح'], 401);
        try {
            $id   = (int)$request->get('id');
            $data = $request->only(['nbr','nbr_f','occupe','vacant','besoin','surplus','categorie','annee','indice','indice_inf','indice_moy','indice_sup','traitement','primes','depense']);
            $this->repo->updateGrade($id, $data);
            AuditLogger::log('UPDATE', 'etablissement_grade', $id);
            return response()->json(['success' => true, 'message' => 'تم تحديث المنصب المالي بنجاح']);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function deleteGrade(int $id)
    {
        $user = session('user');
        if (!$user) return response()->json(['error' => 'غير مصرح'], 401);
        try {
            $this->repo->deleteGrade($id);
            AuditLogger::log('DELETE', 'etablissement_grade', $id);
            return response()->json(['success' => true, 'message' => 'تم حذف المنصب المالي']);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // PROGRAMME CRUD
    // =========================================================================

    public function storeProgramme(Request $request)
    {
        $user = session('user');
        if (!$user) return response()->json(['error' => 'غير مصرح'], 401);
        try {
            $data = $request->only(['nom','nom_fr','code','code_complet','annee','obs']);
            $id   = $this->repo->insertProgramme($data);
            AuditLogger::log('CREATE', 'programme', $id);
            return response()->json(['success' => true, 'id' => $id, 'message' => 'تم إضافة البرنامج بنجاح']);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function updateProgramme(Request $request)
    {
        $user = session('user');
        if (!$user) return response()->json(['error' => 'غير مصرح'], 401);
        try {
            $id   = (int)$request->get('id');
            $data = $request->only(['nom','nom_fr','code','code_complet','annee','obs']);
            $this->repo->updateProgramme($id, $data);
            AuditLogger::log('UPDATE', 'programme', $id);
            return response()->json(['success' => true, 'message' => 'تم تحديث البرنامج بنجاح']);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function deleteProgramme(int $id)
    {
        $user = session('user');
        if (!$user) return response()->json(['error' => 'غير مصرح'], 401);
        try {
            $this->repo->deleteProgramme($id);
            AuditLogger::log('DELETE', 'programme', $id);
            return response()->json(['success' => true, 'message' => 'تم حذف البرنامج']);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // SOUS-PROGRAMME CRUD
    // =========================================================================

    public function storeSousProgramme(Request $request)
    {
        $user = session('user');
        if (!$user) return response()->json(['error' => 'غير مصرح'], 401);
        try {
            $data = $request->only(['nom','nom_fr','code','code_complet','programme_id']);
            $id   = $this->repo->insertSousProgramme($data);
            AuditLogger::log('CREATE', 'sous_programme', $id);
            return response()->json(['success' => true, 'id' => $id]);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function updateSousProgramme(Request $request)
    {
        $user = session('user');
        if (!$user) return response()->json(['error' => 'غير مصرح'], 401);
        try {
            $id   = (int)$request->get('id');
            $data = $request->only(['nom','nom_fr','code','code_complet']);
            $this->repo->updateSousProgramme($id, $data);
            AuditLogger::log('UPDATE', 'sous_programme', $id);
            return response()->json(['success' => true]);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function deleteSousProgramme(int $id)
    {
        $user = session('user');
        if (!$user) return response()->json(['error' => 'غير مصرح'], 401);
        try {
            $this->repo->deleteSousProgramme($id);
            AuditLogger::log('DELETE', 'sous_programme', $id);
            return response()->json(['success' => true]);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // FOURNISSEUR CRUD
    // =========================================================================

    public function storeFournisseur(Request $request)
    {
        $user = session('user');
        if (!$user) return response()->json(['error' => 'غير مصرح'], 401);
        try {
            $data = $request->only(['nom','nom_fr']);
            $id   = $this->repo->insertFournisseur($data);
            AuditLogger::log('CREATE', 'fournisseur', $id);
            return response()->json(['success' => true, 'id' => $id, 'message' => 'تم إضافة المورد بنجاح']);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function updateFournisseur(Request $request)
    {
        $user = session('user');
        if (!$user) return response()->json(['error' => 'غير مصرح'], 401);
        try {
            $id   = (int)$request->get('id');
            $data = $request->only(['nom','nom_fr']);
            $this->repo->updateFournisseur($id, $data);
            AuditLogger::log('UPDATE', 'fournisseur', $id);
            return response()->json(['success' => true, 'message' => 'تم تحديث المورد']);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function deleteFournisseur(int $id)
    {
        $user = session('user');
        if (!$user) return response()->json(['error' => 'غير مصرح'], 401);
        try {
            $this->repo->deleteFournisseur($id);
            AuditLogger::log('DELETE', 'fournisseur', $id);
            return response()->json(['success' => true, 'message' => 'تم حذف المورد']);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // EXTENDED BUDGET, OPERATION, BOURSE & STOCK ACTIONS
    // =========================================================================

    public function storeBudget(Request $request)
    {
        $user = session('user');
        if (!$user) return response()->json(['error' => 'غير مصرح'], 401);
        try {
            $data = $request->only(['nom', 'nom_fr', 'annee', 'code', 'ae', 'cp', 'etab_id']);
            $data['etab_id'] = $data['etab_id'] ?? ($user['etablissement_id'] ?? 0);
            $id = $this->repo->insertBudget($data);
            AuditLogger::log('CREATE', 'budget', $id);
            return response()->json(['success' => true, 'id' => $id, 'message' => 'تم إضافة الميزانية بنجاح']);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function storeOperation(Request $request)
    {
        $user = session('user');
        if (!$user) return response()->json(['error' => 'غير مصرح'], 401);
        try {
            $data = $request->only(['nom', 'nom_fr', 'ap_initiale', 'numero', 'date_inscription', 'taux_physique', 'taux_financier', 'montant_engagement', 'montant_paiement', 'observation', 'etab_id']);
            $data['etab_id'] = $data['etab_id'] ?? ($user['etablissement_id'] ?? 0);
            $id = $this->repo->insertOperation($data);
            AuditLogger::log('CREATE', 'operations', $id);
            return response()->json(['success' => true, 'id' => $id, 'message' => 'تم إضافة العملية الاستثمارية بنجاح']);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function storeBourse(Request $request)
    {
        $user = session('user');
        if (!$user) return response()->json(['error' => 'غير مصرح'], 401);
        try {
            $data = $request->only(['montant', 'duree_payee', 'apprenant_id']);
            $id = $this->repo->insertBourse($data);
            AuditLogger::log('CREATE', 'bourse', $id);
            return response()->json(['success' => true, 'id' => $id, 'message' => 'تم تسجيل المنحة بنجاح']);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function storeStock(Request $request)
    {
        $user = session('user');
        if (!$user) return response()->json(['error' => 'غير مصرح'], 401);
        try {
            $data = $request->only(['nom', 'nom_fr', 'code', 'contenu', 'num_ord']);
            $id = $this->repo->insertStock($data);
            AuditLogger::log('CREATE', 'articlea', $id);
            return response()->json(['success' => true, 'id' => $id, 'message' => 'تم إضافة المادة للمخزن بنجاح']);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function updateProfile(Request $request)
    {
        $user = session('user');
        if (!$user) return response()->json(['error' => 'غير مصرح'], 401);
        try {
            $etabId = $user['etablissement_id'] ?? 0;
            $data = $request->only(['nom', 'nom_fr', 'code', 'obs', 'adresse']);
            $this->repo->updateEtablissementDetails($etabId, $data);
            AuditLogger::log('UPDATE', 'etablissement', $etabId);
            return response()->json(['success' => true, 'message' => 'تم تحديث ملف المؤسسة بنجاح']);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // PRINT — PDF/window.print()
    // =========================================================================

    public function printGrades(Request $request)
    {
        if (\App\Helpers\SovereignLicensingHelper::getSetting('feature_print_actions_enabled', '1') !== '1') {
            abort(403, 'طباعة الدرجات الماليّة معطلة من قبل مدير النظام / Printing is disabled.');
        }

        $user   = session('user');
        if (!$user) return redirect('/login');
        $etabId = (int)($user['etablissement_id'] ?? 0);
        if (in_array(strtolower($user['role_code'] ?? ''), ['admin','dfep','central'])) {
            $etabId = (int)$request->get('filter_etablissement', $etabId);
        }
        $grades = $this->repo->getGradesEtablissement($etabId, 1, 9999);
        $stats  = $this->repo->getStatsBudget($etabId);
        return view('admin.finances.print_grades', compact('grades', 'stats', 'user', 'etabId'));
    }

    public function printProgrammes(Request $request)
    {
        if (\App\Helpers\SovereignLicensingHelper::getSetting('feature_print_actions_enabled', '1') !== '1') {
            abort(403, 'طباعة البرامج معطلة من قبل مدير النظام / Printing is disabled.');
        }

        $user        = session('user');
        if (!$user) return redirect('/login');
        $programmes  = $this->repo->getProgrammes();
        $sousprog    = $this->repo->getSousProgrammes();
        return view('admin.finances.print_programmes', compact('programmes', 'sousprog', 'user'));
    }
}
