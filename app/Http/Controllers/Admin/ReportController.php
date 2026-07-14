<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Domains\Reporting\Services\ReportingService;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    protected ReportingService $reportingService;

    /** Max records per export — prevents OOM and timeout on 2.8M row tables */
    private const MAX_ROWS = 10000;

    public function __construct(ReportingService $reportingService)
    {
        $this->reportingService = $reportingService;

        $this->middleware(function ($request, $next) {
            $user = session('user');
            $role = strtolower($user['role_code'] ?? '');
            if (!in_array($role, ['admin', 'ministre', 'central'])) {
                abort(403, 'غير مصرح لك بالوصول إلى هذه الصفحة');
            }
            return $next($request);
        });
    }

    /**
     * Dynamic Report Builder UI — list all available reports.
     */
    public function index(): mixed
    {
        $user = session('user');

        // Dynamically compute quick stats for the builder cards
        $quickStats = [];
        $statsEnabled = \App\Helpers\SovereignLicensingHelper::getSetting('feature_complex_stats_enabled', '1') === '1';
        if ($statsEnabled) {
            try {
                $quickStats['apprenants']  = DB::table('apprenant')->count();
                $quickStats['formateurs']  = DB::table('encadrement')->whereRaw("EtatActual = 1")->count();
                $quickStats['offres']      = DB::table('offre')->count();
                $quickStats['sections']    = DB::table('section')->count();
            } catch (\Exception $e) {
                $quickStats = ['apprenants' => 0, 'formateurs' => 0, 'offres' => 0, 'sections' => 0];
            }
        } else {
            $quickStats = ['apprenants' => 0, 'formateurs' => 0, 'offres' => 0, 'sections' => 0];
        }

        // Available report types with metadata
        $reportTypes = [
            [
                'key'         => 'apprenants',
                'title'       => 'تقرير المتربصين',
                'description' => 'قائمة شاملة بالمتربصين النشطين مع بياناتهم الأساسية والتخصصات (أول ' . number_format(self::MAX_ROWS) . ' سجل)',
                'icon'        => 'fa-user-graduate',
                'color'       => '#6366f1',
                'count'       => $quickStats['apprenants'],
                'label'       => 'متربص',
            ],
            [
                'key'         => 'candidats',
                'title'       => 'تقرير المترشحين للتكوين',
                'description' => 'قائمة المترشحين في مرحلة ما قبل التسجيل (أول ' . number_format(self::MAX_ROWS) . ' سجل)',
                'icon'        => 'fa-user-plus',
                'color'       => '#06b6d4',
                'count'       => null,
                'label'       => null,
            ],
            [
                'key'         => 'formateurs',
                'title'       => 'تقرير المكوّنين والأساتذة',
                'description' => 'قائمة المكونين النشطين مع رتبهم ومؤسساتهم',
                'icon'        => 'fa-chalkboard-user',
                'color'       => '#10b981',
                'count'       => $quickStats['formateurs'],
                'label'       => 'مكوّن',
            ],
            [
                'key'         => 'offres',
                'title'       => 'تقرير العروض التكوينية',
                'description' => 'ملخص العروض التكوينية المفتوحة والمعتمدة بالتخصصات',
                'icon'        => 'fa-briefcase',
                'color'       => '#f59e0b',
                'count'       => $quickStats['offres'],
                'label'       => 'عرض',
            ],
            [
                'key'         => 'sections',
                'title'       => 'تقرير الأفواج والأقسام',
                'description' => 'تقرير تفصيلي للأقسام والأفواج بالأعداد والمستويات',
                'icon'        => 'fa-layer-group',
                'color'       => '#3b82f6',
                'count'       => $quickStats['sections'],
                'label'       => 'قسم',
            ],
            [
                'key'         => 'absences',
                'title'       => 'تقرير الغيابات',
                'description' => 'إحصائيات الغيابات المسجلة حسب الفوج والمادة',
                'icon'        => 'fa-calendar-xmark',
                'color'       => '#ef4444',
                'count'       => null,
                'label'       => null,
            ],
            [
                'key'         => 'workflow',
                'title'       => 'تقرير طلبات الموارد البشرية',
                'description' => 'ملخص طلبات الإجازات والترقيات والتحويلات وحالاتها',
                'icon'        => 'fa-diagram-project',
                'color'       => '#8b5cf6',
                'count'       => null,
                'label'       => null,
            ],
            [
                'key'         => 'specialites_encours',
                'title'       => 'تقرير التخصصات في طور التكوين',
                'description' => 'قائمة التخصصات النشطة حاليا في طور التكوين مع عدد المتربصين والعتاد',
                'icon'        => 'fa-graduation-cap',
                'color'       => '#a855f7',
                'count'       => null,
                'label'       => null,
            ],
        ];

        return view('admin.reports.index', compact('reportTypes', 'quickStats', 'user'));
    }

    /**
     * Stream a CSV export for the given report type.
     */
    public function export(string $type): mixed
    {
        if (\App\Helpers\SovereignLicensingHelper::getSetting('feature_print_actions_enabled', '1') !== '1') {
            abort(403, 'تصدير التقارير معطل من قبل مدير النظام / Export is disabled by administrator.');
        }

        set_time_limit(300); // 5 minutes for large exports
        ini_set('memory_limit', '256M');

        $user    = session('user');
        $filters = $this->extractFilters();
        $filters['max_rows'] = null; // Unlimited for CSV since it streams page-by-page memory-efficiently (O(1) RAM)

        try {
            $this->reportingService->generate($type, 'csv', $user, $filters);
        } catch (\Exception $e) {
            error_log('[ReportController::export] ' . $e->getMessage());
            if (!headers_sent()) {
                session()->flash('flash_error', 'خطأ في إنشاء التقرير: ' . $e->getMessage());
                return redirect()->to('/dashboard/reports');
            }
        }
        return response('');
    }

    /**
     * Stream an HTML print view for the given report type.
     */
    public function printReport(string $type): mixed
    {
        if (\App\Helpers\SovereignLicensingHelper::getSetting('feature_print_actions_enabled', '1') !== '1') {
            abort(403, 'طباعة التقارير معطلة من قبل مدير النظام / Printing is disabled by administrator.');
        }

        set_time_limit(300);
        ini_set('memory_limit', '256M');

        $user    = session('user');
        $filters = $this->extractFilters();
        $filters['max_rows'] = 5000; // HTML print rendering in browser is limited to 5k rows for browser tab performance safety

        try {
            $this->reportingService->generate($type, 'print', $user, $filters);
        } catch (\Exception $e) {
            error_log('[ReportController::printReport] ' . $e->getMessage());
            if (!headers_sent()) {
                session()->flash('flash_error', 'خطأ في إنشاء التقرير: ' . $e->getMessage());
                return redirect()->to('/dashboard/reports');
            }
        }
        return response('');
    }

    /**
     * Stream a PDF view for the given report type.
     */
    public function pdfReport(string $type): mixed
    {
        if (\App\Helpers\SovereignLicensingHelper::getSetting('feature_print_actions_enabled', '1') !== '1') {
            abort(403, 'تحميل التقارير كـ PDF معطل من قبل مدير النظام / PDF download is disabled.');
        }

        @set_time_limit(600);
        @ini_set('memory_limit', '-1');

        $user    = session('user');
        $filters = $this->extractFilters();
        $filters['max_rows'] = min(self::MAX_ROWS, 1500); // PDF is heavier — tighter limit

        try {
            $this->reportingService->generate($type, 'pdf', $user, $filters);
        } catch (\Exception $e) {
            error_log('[ReportController::pdfReport] ' . $e->getMessage());
            if (!headers_sent()) {
                session()->flash('flash_error', 'خطأ في إنشاء ملف PDF للتقرير: ' . $e->getMessage());
                return redirect()->to('/dashboard/reports');
            }
        }
        return response('');
    }

    /**
     * Extract safe filter parameters from the HTTP GET query string.
     */
    private function extractFilters(): array
    {
        return [
            'status'     => request()->query('status', 'all'),
            'session_id' => (int)request()->query('session_id', 0) ?: null,
            'etab_id'    => (int)request()->query('etab_id', 0) ?: null,
            'mode'       => request()->query('mode', null),
        ];
    }
}
