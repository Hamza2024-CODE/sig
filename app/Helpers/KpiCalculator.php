<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;

/**
 * KpiCalculator — مؤشرات الأداء الرئيسية
 *
 * Laravel 100% Query Builder / DB Facade implementation.
 */
class KpiCalculator
{
    public function __construct($db = null)
    {
    }

    // -------------------------------------------------------------------------
    // Success Rate — Taux de réussite
    // -------------------------------------------------------------------------

    public function calculateSuccessRate($specialteId = null): float
    {
        $query = "
            SELECT
                COUNT(CASE WHEN af.MoyGen >= 10 THEN 1 END) AS success_count,
                COUNT(*) AS total_count
            FROM apprenant_fin af
            JOIN apprenant a ON af.IDapprenant = a.IDapprenant
            JOIN section sec ON a.IDSection = sec.IDSection
            WHERE a.statut = 'actif'
        ";

        if ($specialteId) {
            $joinExtra = " AND sec.IDOffre IN (SELECT IDOffre FROM offre WHERE IDSpecialite = ?)";
            $result = DB::selectOne($query . $joinExtra, [$specialteId]);
        } else {
            $result = DB::selectOne($query);
        }

        if (!$result || $result->total_count == 0) {
            return 0.0;
        }
        return round(($result->success_count / $result->total_count) * 100, 2);
    }

    // -------------------------------------------------------------------------
    // Failure Rate — Taux d'échec
    // -------------------------------------------------------------------------

    public function calculateFailureRate($specialteId = null): float
    {
        return round(100 - $this->calculateSuccessRate($specialteId), 2);
    }

    // -------------------------------------------------------------------------
    // Attendance Rate — Taux de présence
    // -------------------------------------------------------------------------

    public function calculateAttendanceRate($specialteId = null): float
    {
        $since = date('Y-m-d', strtotime('-90 days'));

        // Count active trainees
        $activeCount = (int) DB::table('apprenant')
            ->where('statut', 'actif')
            ->count();

        if ($activeCount === 0) return 0.0;

        // Count absence slots in the period
        $queryAbs = "
            SELECT COUNT(*) AS absence_slots
            FROM apprenant_absence ab
            JOIN apprenant_section_semstre ass ON ab.IDapprenant_Section_semstre = ass.IDapprenant_Section_semstre
            JOIN apprenant a ON ass.IDapprenant = a.IDapprenant
            WHERE a.statut = 'actif' AND ab.Date >= ?
        ";
        try {
            $res = DB::selectOne($queryAbs, [$since]);
            $absentSlots = (int)($res->absence_slots ?? 0);
        } catch (\Exception $e) {
            $absentSlots = 0;
        }

        // Estimate: 90 working days × 2 slots per day per trainee
        $totalSlots   = $activeCount * 90 * 2;
        $presentSlots = max(0, $totalSlots - $absentSlots);

        return round(($presentSlots / $totalSlots) * 100, 2);
    }

    // -------------------------------------------------------------------------
    // Dropout Rate — Taux d'abandon
    // -------------------------------------------------------------------------

    public function calculateDropoutRate($specialteId = null): float
    {
        $query = "
            SELECT
                COUNT(CASE WHEN a.statut = 'abandon' THEN 1 END) AS dropout_count,
                COUNT(*) AS total_count
            FROM apprenant a
        ";

        $result = DB::selectOne($query);
        if (!$result || $result->total_count == 0) {
            return 0.0;
        }
        return round(($result->dropout_count / $result->total_count) * 100, 2);
    }

    // -------------------------------------------------------------------------
    // Average Grade — Moyenne générale
    // -------------------------------------------------------------------------

    public function calculateAverageGrade($specialteId = null): float
    {
        $query = "
            SELECT AVG(af.MoyGen) AS average
            FROM apprenant_fin af
            JOIN apprenant a ON af.IDapprenant = a.IDapprenant
            WHERE a.statut = 'actif' AND af.MoyGen IS NOT NULL AND af.MoyGen > 0
        ";

        $result = DB::selectOne($query);
        return $result && $result->average ? round((float)$result->average, 2) : 0.0;
    }

    // -------------------------------------------------------------------------
    // Total Trainees — Nombre total d'apprenants actifs
    // -------------------------------------------------------------------------

    public function countActiveTrainees($specialteId = null): int
    {
        return (int) DB::table('apprenant')->where('statut', 'actif')->count();
    }

    // -------------------------------------------------------------------------
    // Total Diploma Holders — Nombre de diplômés
    // -------------------------------------------------------------------------

    public function countDiplomes($specialteId = null): int
    {
        $query = DB::table('apprenant_fin as af')
            ->join('apprenant as a', 'af.IDapprenant', '=', 'a.IDapprenant')
            ->join('section as sec', 'a.IDSection', '=', 'sec.IDSection')
            ->whereNotNull('af.Numdiplome')
            ->where('af.Numdiplome', '!=', '');

        if ($specialteId) {
            $query->where('sec.IDSpecialite', $specialteId);
        }

        return (int) $query->count();
    }

    // -------------------------------------------------------------------------
    // Save KPI snapshot
    // -------------------------------------------------------------------------

    public function saveKpi(string $kpiType, float $value, ?string $category = null, ?int $categoryId = null, ?float $targetValue = null): bool
    {
        try {
            // ON DUPLICATE KEY UPDATE in raw SQL syntax is safe for MySQL
            DB::statement("
                INSERT INTO analytics_kpi_snapshots
                    (kpi_type, value, target_value, period_start, period_end, category, category_id, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                ON DUPLICATE KEY UPDATE value = VALUES(value), updated_at = NOW()
            ", [
                $kpiType,
                round($value, 2),
                $targetValue,
                date('Y-m-01'),
                date('Y-m-d'),
                $category,
                $categoryId,
            ]);
            return true;
        } catch (\Exception $e) {
            error_log("KpiCalculator::saveKpi() error: " . $e->getMessage());
            return false;
        }
    }

    // -------------------------------------------------------------------------
    // Calculate and snapshot all KPIs
    // -------------------------------------------------------------------------

    public function calculateAndSaveAllKpis(): bool
    {
        $kpis = [
            ['success_rate',    $this->calculateSuccessRate()],
            ['failure_rate',    $this->calculateFailureRate()],
            ['attendance_rate', $this->calculateAttendanceRate()],
            ['dropout_rate',    $this->calculateDropoutRate()],
            ['average_grade',   $this->calculateAverageGrade()],
            ['total_trainees',  (float)$this->countActiveTrainees()],
            ['total_diplomes',  (float)$this->countDiplomes()],
        ];

        foreach ($kpis as [$type, $value]) {
            $this->saveKpi($type, $value);
        }

        return true;
    }

    // -------------------------------------------------------------------------
    // Retrieve latest KPI snapshot
    // -------------------------------------------------------------------------

    public function getKpi(string $kpiType): ?array
    {
        try {
            $row = DB::selectOne("
                SELECT * FROM analytics_kpi_snapshots
                WHERE kpi_type = ?
                ORDER BY period_end DESC
                LIMIT 1
            ", [$kpiType]);
            return $row ? (array)$row : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    // -------------------------------------------------------------------------
    // Get all latest KPIs as an associative array
    // -------------------------------------------------------------------------

    public function getAllKpis(): array
    {
        $keys = ['success_rate','failure_rate','attendance_rate','dropout_rate','average_grade','total_trainees','total_diplomes'];
        $result = [];
        foreach ($keys as $k) {
            $row = $this->getKpi($k);
            $result[$k] = $row ? (float)$row['value'] : 0.0;
        }
        return $result;
    }
}
