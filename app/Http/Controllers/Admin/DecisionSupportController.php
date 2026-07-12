<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DecisionEngine;
use App\Models\UserPreferences;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DecisionSupportController extends Controller
{
    protected DecisionEngine $engine;

    public function __construct()
    {
        $this->engine = new DecisionEngine();
    }

    public function index(Request $request)
    {
        $user = session('user');
        if (!$user) return redirect('/login');

        $roleCode = strtolower($user['role_code'] ?? '');

        // ── Restrict access: DSS is for admin/minister/central only ──
        $allowedRoles = ['admin', 'ministre', 'central', 'high_admin', 'secretaire_general'];
        if (!in_array($roleCode, $allowedRoles)) {
            return redirect()->route('dashboard')->with('error', 'ليس لديك صلاحية الوصول إلى نظام دعم القرار.');
        }

        $prefs = UserPreferences::forUser($user);

        // 1. Determine Scope based on user role
        $etabId = (int)($user['etablissement_id'] ?? 0);
        $dfepId = 0;

        if (in_array($roleCode, ['admin', 'central', 'ministre'])) {
            // Admin/Ministre can filter globally
            $dfepId = (int)$request->get('filter_wilaya', 0);
            $etabId = (int)$request->get('filter_etablissement', 0);
            
            // If they filtered by wilaya but not etablissement, we scope to that wilaya
            if ($dfepId > 0 && $etabId === 0) {
                // We'll calculate aggregated metrics dynamically for the selected Wilaya
                // For simplicity, if they filter by Wilaya, we pass the first establishment or aggregate them.
            }
        } else {
            // central/high_admin — scoped to their direction only, no etab
            $etabId = 0;
            $dfepId = 0;
        }

        // 2. Fetch scoped metrics, alerts, and recommendations
        $directionCode = strtoupper($user['direction_code'] ?? $user['username'] ?? '');
        $roleArg = $roleCode;
        if ($roleCode === 'central' && $directionCode === 'DFM') {
            $roleArg = 'dir_finance';
        } elseif ($roleCode === 'central' && in_array($directionCode, ['DRH', 'DRHINST', 'DRHCENTRE', 'DRHPB', 'DRHT'])) {
            $roleArg = 'dir_rh';
        } elseif ($roleCode === 'central' && in_array($directionCode, ['DOSFP', 'DEP'])) {
            $roleArg = 'dir_edu';
        }

        $scopedData = $this->engine->getMetricsForRole($roleArg, $etabId > 0 ? $etabId : null);
        $alerts = $this->engine->getAlerts($etabId > 0 ? $etabId : null);
        $recommendations = $this->engine->getAiRecommendations($roleArg, $etabId > 0 ? $etabId : null);

        // 3. Lookups for filters
        $wilayas = DB::table('wilaya')->orderBy('Nom', 'asc')->get();
        
        $etabsQuery = DB::table('etablissement');
        if ($dfepId > 0) {
            $etabsQuery->where('IDDFEP', $dfepId);
        }
        $etablissements = $etabsQuery->orderBy('Nom', 'asc')->get();

        // 4. Drilldown list for region comparison
        // Let's get top 10 establishments with their success rates and budgets for comparison
        $drilldownItems = [];
        if (in_array($roleCode, ['admin', 'central', 'ministre', 'dfep'])) {
            $compQuery = DB::table('etablissement as e')
                ->leftJoin('dfep as d', 'e.IDDFEP', '=', 'd.IDDFEP')
                ->leftJoin('wilaya as w', 'd.IDWilayaa', '=', 'w.IDWilayaa');
            
            if ($dfepId > 0) {
                $compQuery->where('e.IDDFEP', $dfepId);
            }

            $compEtabs = $compQuery->select('e.IDetablissement', 'e.Nom as etab_nom', 'w.Nom as wilaya_nom')
                ->limit(15)
                ->get();

            foreach ($compEtabs as $ce) {
                $ceKpi = $this->engine->calculateLiveKpis($ce->IDetablissement);
                $drilldownItems[] = [
                    'etab_id' => $ce->IDetablissement,
                    'etab_name' => $ce->etab_nom,
                    'wilaya_name' => $ce->wilaya_nom,
                    'success_rate' => $ceKpi['success_rate'],
                    'budget' => $ceKpi['budget_allocation'],
                    'spending' => $ceKpi['total_spending'],
                    'absorption' => $ceKpi['budget_absorption_rate'],
                    'student_teacher_ratio' => $ceKpi['student_teacher_ratio']
                ];
            }
        }

        // 5. Check if migration has been executed, to warn user if kpi_snapshots or depenses is missing
        $hasDepenses = false;
        try {
            DB::table('depenses')->limit(1)->first();
            $hasDepenses = true;
        } catch (\Exception $e) {
            $hasDepenses = false;
        }
        $migrationsPending = !Schema::hasTable('kpi_snapshots') || !$hasDepenses;

        return view('admin.dss.index', compact(
            'user', 'roleCode', 'prefs', 'scopedData', 'alerts', 'recommendations',
            'wilayas', 'etablissements', 'drilldownItems', 'migrationsPending',
            'dfepId', 'etabId'
        ));
    }

    /**
     * AJAX endpoint for detailed establishment drill-down.
     */
    public function drilldown(Request $request)
    {
        $user = session('user');
        if (!$user) return response()->json(['error' => 'غير مصرح'], 401);

        $roleCode = strtolower($user['role_code'] ?? '');
        $allowedRoles = ['admin', 'ministre', 'central', 'high_admin', 'secretaire_general'];
        if (!in_array($roleCode, $allowedRoles)) {
            return response()->json(['error' => 'ليس لديك صلاحية الوصول'], 403);
        }

        $etabId = (int)$request->get('etab_id', 0);
        if ($etabId <= 0) {
            return response()->json(['error' => 'معرف المؤسسة غير صالح'], 400);
        }

        $etab = DB::table('etablissement')->where('IDetablissement', $etabId)->first();
        if (!$etab) {
            return response()->json(['error' => 'المؤسسة غير موجودة'], 404);
        }

        $kpis = $this->engine->calculateLiveKpis($etabId);
        $trends = [
            'success_rate' => $this->engine->getForecasting('success_rate', $etabId),
            'total_spending' => $this->engine->getForecasting('total_spending', $etabId),
            'budget_absorption_rate' => $this->engine->getForecasting('budget_absorption_rate', $etabId),
        ];

        return response()->json([
            'etab_name' => $etab->Nom,
            'kpis' => $kpis,
            'trends' => $trends
        ]);
    }
}
