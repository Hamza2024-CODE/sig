<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class GenerateGraduatesStatsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sgfep:generate-graduates-stats 
                            {cacheKey : Cache key to store statistics} 
                            {role : User role} 
                            {dfepId : User DFEP ID} 
                            {etabId : User Etablissement ID} 
                            {filtersJson : JSON-encoded filters}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Compute and cache graduates statistics in the background to avoid web timeouts';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $cacheKey = $this->argument('cacheKey');
        $role = strtolower($this->argument('role'));
        $dfepId = (int)$this->argument('dfepId');
        $etabId = (int)$this->argument('etabId');
        $filters = json_decode($this->argument('filtersJson'), true) ?? [];

        $this->info("Starting statistics calculation for cache key: {$cacheKey}");
        Log::info("[StatsBackground] Calculating statistics for {$cacheKey} (Role: {$role})");

        $filterWilaya = (int)($filters['filter_wilaya'] ?? 0);
        $filterEtab   = (int)($filters['filter_etab'] ?? 0);
        $filterMode   = (int)($filters['filter_mode'] ?? 0);
        $filterAnnee  = (int)($filters['filter_annee'] ?? 0);
        $filterSpec   = (int)($filters['filter_spec'] ?? 0);

        $isAdmin = in_array($role, ['admin', 'ministre', 'secretaire_general', 'central', 'high_admin']);
        $isDfep  = ($role === 'dfep' && $dfepId > 0);
        $isEtab  = (!$isAdmin && !$isDfep && $etabId > 0);

        // Build filter conditions
        $where = [];
        $params = [];

        if ($isAdmin) {
            if ($filterWilaya > 0) {
                $where[] = "e.IDDFEP = ?";
                $params[] = $filterWilaya;
            }
            if ($filterEtab > 0) {
                $where[] = "o.IDEts_Form = ?";
                $params[] = $filterEtab;
            }
        } elseif ($isDfep) {
            $where[] = "e.IDDFEP = ?";
            $params[] = $dfepId;
            if ($filterEtab > 0) {
                $where[] = "o.IDEts_Form = ?";
                $params[] = $filterEtab;
            }
        } elseif ($isEtab) {
            $where[] = "o.IDEts_Form = ?";
            $params[] = $etabId;
        } else {
            $where[] = "1=0";
        }

        if ($filterMode > 0) {
            $where[] = "o.IDMode_formation = ?";
            $params[] = $filterMode;
        }
        if ($filterAnnee > 0) {
            $where[] = "o.IDAnnee_Formation = ?";
            $params[] = $filterAnnee;
        }
        if ($filterSpec > 0) {
            $where[] = "o.IDSpecialite = ?";
            $params[] = $filterSpec;
        }

        $whereSQL = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        // 1. KPIs
        $kpiQuery = "
            SELECT 
                COUNT(*) as total_graduates,
                COUNT(CASE WHEN f.Numdiplome IS NOT NULL AND f.Numdiplome != '' THEN 1 END) as issued_diplomas,
                COUNT(CASE WHEN f.Numdiplome IS NULL OR f.Numdiplome = '' THEN 1 END) as pending_diplomas
            FROM apprenant_fin f
            JOIN apprenant a ON f.IDapprenant = a.IDapprenant
            JOIN section s ON a.IDSection = s.IDSection
            JOIN offre o ON s.IDOffre = o.IDOffre
            JOIN etablissement e ON o.IDEts_Form = e.IDetablissement
            {$whereSQL}
        ";
        try {
            $kpi = (array)DB::selectOne($kpiQuery, $params);
        } catch (\Throwable $e) {
            Log::error("[StatsBackground] KPI error: " . $e->getMessage());
            $kpi = ['total_graduates' => 0, 'issued_diplomas' => 0, 'pending_diplomas' => 0];
        }

        // 2. Graduates by Wilaya (Admin only)
        $wilayaStats = [];
        if ($isAdmin) {
            $wilayaQuery = "
                SELECT w.Nom as wilaya_nom, w.IDWilayaa as id, COUNT(*) as count
                FROM apprenant_fin f
                JOIN apprenant a ON f.IDapprenant = a.IDapprenant
                JOIN section s ON a.IDSection = s.IDSection
                JOIN offre o ON s.IDOffre = o.IDOffre
                JOIN etablissement e ON o.IDEts_Form = e.IDetablissement
                JOIN wilaya w ON e.IDDFEP = w.IDWilayaa
                {$whereSQL}
                GROUP BY w.IDWilayaa, w.Nom
                ORDER BY count DESC
            ";
            try {
                $wilayaStats = array_map(fn($r) => (array)$r, DB::select($wilayaQuery, $params));
            } catch (\Throwable $e) {
                Log::error("[StatsBackground] Wilaya error: " . $e->getMessage());
            }
        }

        // 3. Graduates by Year
        $yearQuery = "
            SELECT YEAR(f.DateDiplome) as year_name, COUNT(*) as count
            FROM apprenant_fin f
            JOIN apprenant a ON f.IDapprenant = a.IDapprenant
            JOIN section s ON a.IDSection = s.IDSection
            JOIN offre o ON s.IDOffre = o.IDOffre
            JOIN etablissement e ON o.IDEts_Form = e.IDetablissement
            {$whereSQL} AND f.DateDiplome IS NOT NULL AND f.DateDiplome > '2010-01-01'
            GROUP BY YEAR(f.DateDiplome)
            ORDER BY year_name ASC
        ";
        try {
            $yearStats = array_map(fn($r) => (array)$r, DB::select($yearQuery, $params));
        } catch (\Throwable $e) {
            Log::error("[StatsBackground] Year error: " . $e->getMessage());
            $yearStats = [];
        }

        // 4. Graduates by Mode
        $modeQuery = "
            SELECT m.Nom as mode_nom, COUNT(*) as count
            FROM apprenant_fin f
            JOIN apprenant a ON f.IDapprenant = a.IDapprenant
            JOIN section s ON a.IDSection = s.IDSection
            JOIN offre o ON s.IDOffre = o.IDOffre
            JOIN etablissement e ON o.IDEts_Form = e.IDetablissement
            JOIN mode_formation m ON o.IDMode_formation = m.IDMode_formation
            {$whereSQL}
            GROUP BY m.IDMode_formation, m.Nom
            ORDER BY count DESC
        ";
        try {
            $modeStats = array_map(fn($r) => (array)$r, DB::select($modeQuery, $params));
        } catch (\Throwable $e) {
            Log::error("[StatsBackground] Mode error: " . $e->getMessage());
            $modeStats = [];
        }

        // 5. Graduates by Branch
        $branchQuery = "
            SELECT b.Nom as branch_nom, COUNT(*) as count
            FROM apprenant_fin f
            JOIN apprenant a ON f.IDapprenant = a.IDapprenant
            JOIN section s ON a.IDSection = s.IDSection
            JOIN offre o ON s.IDOffre = o.IDOffre
            JOIN etablissement e ON o.IDEts_Form = e.IDetablissement
            JOIN specialite sp ON o.IDSpecialite = sp.IDSpecialite
            JOIN branche b ON sp.IDBranche = b.IDBranche
            {$whereSQL}
            GROUP BY b.IDBranche, b.Nom
            ORDER BY count DESC
        ";
        try {
            $branchStats = array_map(fn($r) => (array)$r, DB::select($branchQuery, $params));
        } catch (\Throwable $e) {
            Log::error("[StatsBackground] Branch error: " . $e->getMessage());
            $branchStats = [];
        }

        // 6. Top 15 Institutions (Admin/DFEP)
        $etabStats = [];
        if ($isAdmin || $isDfep) {
            $etabQuery = "
                SELECT e.Nom as etab_nom, COUNT(*) as count
                FROM apprenant_fin f
                JOIN apprenant a ON f.IDapprenant = a.IDapprenant
                JOIN section s ON a.IDSection = s.IDSection
                JOIN offre o ON s.IDOffre = o.IDOffre
                JOIN etablissement e ON o.IDEts_Form = e.IDetablissement
                {$whereSQL}
                GROUP BY e.IDetablissement, e.Nom
                ORDER BY count DESC
                LIMIT 15
            ";
            try {
                $etabStats = array_map(fn($r) => (array)$r, DB::select($etabQuery, $params));
            } catch (\Throwable $e) {
                Log::error("[StatsBackground] Etab error: " . $e->getMessage());
            }
        }

        // 7. Top 10 Specialties
        $specStats = [];
        $specQuery = "
            SELECT sp.Nom as spec_nom, COUNT(*) as count
            FROM apprenant_fin f
            JOIN apprenant a ON f.IDapprenant = a.IDapprenant
            JOIN section s ON a.IDSection = s.IDSection
            JOIN offre o ON s.IDOffre = o.IDOffre
            JOIN specialite sp ON o.IDSpecialite = sp.IDSpecialite
            JOIN etablissement e ON o.IDEts_Form = e.IDetablissement
            {$whereSQL}
            GROUP BY sp.IDSpecialite, sp.Nom
            ORDER BY count DESC
            LIMIT 10
        ";
        try {
            $specStats = array_map(fn($r) => (array)$r, DB::select($specQuery, $params));
        } catch (\Throwable $e) {
            Log::error("[StatsBackground] Spec error: " . $e->getMessage());
        }

        // Store stats in Cache for 2 hours (7200 seconds)
        $data = [
            'kpi' => $kpi,
            'wilayaStats' => $wilayaStats,
            'yearStats' => $yearStats,
            'modeStats' => $modeStats,
            'branchStats' => $branchStats,
            'etabStats' => $etabStats,
            'specStats' => $specStats,
        ];

        Cache::put($cacheKey, $data, 7200);
        Cache::forget('generating_stats_' . $cacheKey);

        $this->info("Completed statistics calculation for: {$cacheKey}");
        Log::info("[StatsBackground] Calculation complete and cached for {$cacheKey}");

        return Command::SUCCESS;
    }
}
