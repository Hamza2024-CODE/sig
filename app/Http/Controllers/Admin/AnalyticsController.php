<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Helpers\KpiCalculator;
use Illuminate\Support\Facades\DB;

/**
 * متحكم لوحة المعلومات التحليلية
 * Analytics Dashboard Controller
 */
class AnalyticsController extends Controller
{
    private KpiCalculator $kpiCalculator;

    public function __construct()
    {
        if (app()->runningInConsole()) { return; }
        $this->kpiCalculator = new KpiCalculator();
    }

    /**
     * عرض لوحة المعلومات الرئيسية
     * Display main analytics dashboard
     * GET /dashboard/analytics
     */
    public function index()
    {
        // التحقق من الصلاحيات
        if (!$this->checkPermission('analytics:view')) {
            session(['flash_error' => 'غير مصرح لعرض التحليلات']);
            return $this->redirect('/dashboard');
        }

        // حساب أهم المؤشرات مباشرة من قاعدة البيانات الحقيقية
        try {
            $successRate = $this->kpiCalculator->calculateSuccessRate();
            $failureRate = $this->kpiCalculator->calculateFailureRate();
            $attendanceRate = $this->kpiCalculator->calculateAttendanceRate();
            $dropoutRate = $this->kpiCalculator->calculateDropoutRate();
            $averageGrade = $this->kpiCalculator->calculateAverageGrade();
        } catch (\Exception $e) {
            $successRate = $failureRate = $attendanceRate = $dropoutRate = $averageGrade = 0.0;
        }

        // إحصائيات الطلاب
        $studentStats = $this->getStudentStatistics();

        // بيانات الرسوم البيانية
        $chartData = [
            'gradeDistribution' => $this->getGradeDistribution(),
            'specialtyPerformance' => $this->getSpecialtyPerformance(),
            'statusBreakdown' => $this->getStatusBreakdown(),
        ];

        return $this->render('analytics/dashboard/index', [
            'successRate' => round($successRate, 2),
            'failureRate' => round($failureRate, 2),
            'attendanceRate' => round($attendanceRate, 2),
            'dropoutRate' => round($dropoutRate, 2),
            'averageGrade' => round($averageGrade, 2),
            'studentStats' => $studentStats,
            'alerts' => [],
            'chartData' => $chartData,
            'lastUpdated' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * عرض مؤشرات الأداء الرئيسية (KPIs)
     * Display KPIs
     * GET /dashboard/analytics/kpis
     */
    public function kpis()
    {
        if (!$this->checkPermission('analytics:view')) {
            session(['flash_error' => 'غير مصرح لعرض مؤشرات الأداء الرئيسية']);
            return $this->redirect('/dashboard');
        }

        // جلب جميع KPIs الحالية
        $query = "
            SELECT DISTINCT kpi_type, value, period_end, target_value
            FROM analytics_kpi_snapshots
            WHERE period_end = CURDATE()
            ORDER BY period_end DESC
        ";

        try {
            $kpis = array_map(fn($item) => (array)$item, DB::select($query));
        } catch (\Exception $e) {
            $kpis = [];
        }

        return $this->render('analytics/kpis/index', ['kpis' => $kpis]);
    }

    /**
     * عرض الرسوم البيانية التفاعلية
     * Display interactive charts
     * GET /dashboard/analytics/charts
     */
    public function charts()
    {
        if (!$this->checkPermission('analytics:view')) {
            session(['flash_error' => 'غير مصرح لعرض الرسوم البيانية']);
            return $this->redirect('/dashboard');
        }

        return $this->render('analytics/charts/index', [
            'successTrend' => $this->getSuccessTrend(12),
            'attendanceTrend' => $this->getAttendanceTrend(12),
            'gradeDistribution' => $this->getGradeDistribution(),
            'specialtyPerformance' => $this->getSpecialtyPerformance()
        ]);
    }

    /**
     * عرض التقارير المخصصة
     * Display custom reports
     * GET /dashboard/analytics/reports
     */
    public function reports()
    {
        if (!$this->checkPermission('analytics:reports:view')) {
            session(['flash_error' => 'غير مصرح لعرض التقارير المخصصة']);
            return $this->redirect('/dashboard');
        }

        $query = "
            SELECT * FROM custom_reports
            WHERE created_by = ? OR is_public = TRUE
            ORDER BY created_at DESC
        ";

        try {
            $reports = array_map(fn($item) => (array)$item, DB::select($query, [session('user')['id'] ?? 0]));
        } catch (\Exception $e) {
            $reports = [];
        }

        return $this->render('analytics/reports/index', ['reports' => $reports]);
    }

    /**
     * إنشاء تقرير جديد
     * Create new report
     * POST /dashboard/analytics/reports
     */
    public function storeReport()
    {
        if (!$this->checkPermission('analytics:reports:create')) {
            session(['flash_error' => 'غير مصرح لإنشاء تقرير جديد']);
            return $this->redirect('/dashboard');
        }

        $data = [
            'name' => request()->all()['name'] ?? null,
            'description' => request()->all()['description'] ?? null,
            'report_type' => request()->all()['report_type'] ?? 'general',
            'created_by' => session('user')['id'] ?? 0,
            'filters' => json_encode(request()->all()['filters'] ?? []),
            'metrics' => json_encode(request()->all()['metrics'] ?? []),
            'chart_type' => request()->all()['chart_type'] ?? 'bar',
            'frequency' => request()->all()['frequency'] ?? 'once',
            'is_public' => isset(request()->all()['is_public']) ? 1 : 0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        try {
            $reportId = DB::table('custom_reports')->insertGetId($data);
            if ($reportId) {
                session(['flash_success' => 'تم إنشاء التقرير بنجاح']);
                return $this->redirect('/dashboard/analytics/reports/' . $reportId);
            }
        } catch (\Exception $e) {
            // Do nothing
        }

        session(['flash_error' => 'فشل إنشاء التقرير']);
        return $this->redirect('/dashboard/analytics/reports');
    }

    /**
     * عرض التنبيهات
     * Display alerts
     * GET /dashboard/analytics/alerts
     */
    public function alerts()
    {
        if (!$this->checkPermission('analytics:alerts:view')) {
            session(['flash_error' => 'غير مصرح لعرض التنبيهات']);
            return $this->redirect('/dashboard');
        }

        $query = "
            SELECT * FROM smart_alerts
            WHERE is_active = TRUE
            ORDER BY created_at DESC
        ";

        try {
            $alerts = array_map(fn($item) => (array)$item, DB::select($query));
        } catch (\Exception $e) {
            $alerts = [];
        }

        return $this->render('analytics/alerts/index', ['alerts' => $alerts]);
    }

    // ==================== Helper Methods ====================

    /**
     * إحصائيات الطلاب
     */
    private function getStudentStatistics()
    {
        $query = "
            SELECT
                COUNT(*) as total,
                COUNT(CASE WHEN active = 1 THEN 1 END) as active,
                COUNT(CASE WHEN active = 0 THEN 1 END) as dropout,
                COUNT(CASE WHEN IDEtablissement IS NOT NULL THEN 1 END) as graduated
            FROM apprenant
        ";

        try {
            $res = DB::selectOne($query);
            return $res ? (array)$res : ['total' => 0, 'active' => 0, 'dropout' => 0, 'graduated' => 0];
        } catch (\Exception $e) {
            return ['total' => 0, 'active' => 0, 'dropout' => 0, 'graduated' => 0];
        }
    }

    /**
     * أحدث التنبيهات — من جدول accesuser
     */
    private function getRecentAlerts()
    {
        try {
            return array_map(fn($item) => (array)$item, DB::select("
                SELECT IDaccesuser as id, Date as triggered_at, NomUtilisateur as message,
                       'login' as alert_type, iplocal
                FROM accesuser
                ORDER BY Date DESC, heure DESC
                LIMIT 5
            "));
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * اتجاه معدل النجاح — من apprenant_fin حسب الشهر
     */
    private function getSuccessTrend($months = 6)
    {
        try {
            return array_map(fn($item) => (array)$item, DB::select("
                SELECT
                    DATE_FORMAT(af.DateDiplome, '%Y-%m') as month,
                    COUNT(CASE WHEN af.MoyGen >= 10 THEN 1 END) as success_count,
                    COUNT(*) as total_count,
                    ROUND(COUNT(CASE WHEN af.MoyGen >= 10 THEN 1 END) * 100.0 / NULLIF(COUNT(*), 0), 2) as success_rate
                FROM apprenant_fin af
                WHERE af.DateDiplome >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
                GROUP BY DATE_FORMAT(af.DateDiplome, '%Y-%m')
                ORDER BY month ASC
            ", [$months]));
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * اتجاه معدل الحضور — من apprenant_absence حسب الشهر
     */
    private function getAttendanceTrend($months = 6)
    {
        try {
            return array_map(fn($item) => (array)$item, DB::select("
                SELECT
                    DATE_FORMAT(ab.Date, '%Y-%m') as month,
                    COUNT(*) as absence_count
                FROM apprenant_absence ab
                JOIN apprenant_section_semstre ass ON ab.IDapprenant_Section_semstre = ass.IDapprenant_Section_semstre
                WHERE ab.Date >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
                GROUP BY DATE_FORMAT(ab.Date, '%Y-%m')
                ORDER BY month ASC
            ", [$months]));
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * توزيع الدرجات — من apprenant_section_semstre
     */
    private function getGradeDistribution()
    {
        try {
            return array_map(fn($item) => (array)$item, DB::select("
                SELECT
                    CASE
                        WHEN af.MoyGen >= 18 THEN 'ممتاز (18+)'
                        WHEN af.MoyGen >= 16 THEN 'جيد جداً (16-17.99)'
                        WHEN af.MoyGen >= 14 THEN 'جيد (14-15.99)'
                        WHEN af.MoyGen >= 10 THEN 'مقبول (10-13.99)'
                        ELSE 'ضعيف (<10)'
                    END as grade_range,
                    COUNT(*) as count
                FROM apprenant_fin af
                WHERE af.MoyGen IS NOT NULL AND af.MoyGen > 0
                GROUP BY grade_range
                ORDER BY count DESC
            "));
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * توزيع حسب الحالة — من apprenant.statut
     */
    private function getStatusBreakdown()
    {
        try {
            return array_map(fn($item) => (array)$item, DB::select("
                SELECT statut, COUNT(*) as count
                FROM apprenant
                GROUP BY statut
                ORDER BY count DESC
            "));
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * أداء التخصصات — من specialite + offre + section + apprenant
     */
    private function getSpecialtyPerformance()
    {
        try {
            return array_map(fn($item) => (array)$item, DB::select("
                SELECT
                    sp.Nom as specialty,
                    COUNT(DISTINCT a.IDapprenant) as total_students,
                    AVG(af.MoyGen) as average_grade,
                    COUNT(DISTINCT CASE WHEN a.statut = 'diplome' THEN a.IDapprenant END) as successful
                FROM specialite sp
                LEFT JOIN offre o ON sp.IDSpecialite = o.IDSpecialite
                LEFT JOIN section s ON o.IDOffre = s.IDOffre
                LEFT JOIN apprenant a ON s.IDSection = a.IDSection
                LEFT JOIN apprenant_fin af ON a.IDapprenant = af.IDapprenant
                GROUP BY sp.IDSpecialite, sp.Nom
                ORDER BY total_students DESC
                LIMIT 20
            "));
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * التحقق من الصلاحيات
     */
    private function checkPermission(string $permission): bool
    {
        $role_code = session('user')['role_code'] ?? '';
        if ($role_code === 'admin' || $role_code === 'dfep') {
            return true;
        }

        $permissions = session('user')['permissions'] ?? [];
        return is_array($permissions) && in_array($permission, $permissions);
    }
}
