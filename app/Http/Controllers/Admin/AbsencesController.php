<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Domains\Academic\Services\ApprenantService;

/**
 * AbsencesController (Domain)
 *
 * Thin HTTP adapter for absence tracking.
 * Delegates ALL business logic to ApprenantService.
 * Replaces App\Controllers\Admin\AbsencesController.
 *
 * Routes handled (see config/routes.php):
 *   GET  /dashboard/absences                      → index()
 *   GET  /dashboard/absences/add                  → add()
 *   POST /dashboard/absences/store                → store()
 *   GET  /dashboard/absences/warnings             → warnings()
 *   GET  /dashboard/absences/print-warning/{id}   → printWarning()
 */
class AbsencesController extends Controller
{
    protected ApprenantService $service;

    public function __construct(ApprenantService $service)
    {
        $this->service = $service;
        if (app()->runningInConsole()) { return; }
    }

    /**
     * Dashboard — absence summary statistics + recent list
     */
    public function index(): mixed
    {
        $stats = $this->service->getAbsenceDashboardStats(session('user'));

        return $this->render('admin/absences/index', [
            'title'    => 'مراقبة الحضور والغيابات - SGFEP',
            'tCount'   => $stats['total_trainees'],
            'aCount'   => $stats['total_absences'],
            'wCount'   => $stats['total_warnings'],
            'absences' => $stats['recent_absences'],
        ]);
    }

    /**
     * Absence recording form — list of active trainees
     */
    public function add(): mixed
    {
        $students = $this->service->listForAbsenceRecording(session('user'));

        return $this->render('admin/absences/add', [
            'title'    => 'تسجيل حضور الأفواج - SGFEP',
            'students' => $students,
        ]);
    }

    /**
     * Store absence records for selected trainees
     */
    public function store(): mixed
    {
        if (request()->isMethod('post')) {
            // CSRF guard
            $token = request()->all()['csrf_token'] ?? '';
            if (empty($token) || $token !== (csrf_token() ?? '')) {
                session(['error' => 'رمز التحقق من الأمن غير صالح. يرجى المحاولة مجدداً.']);
                return $this->redirect('/dashboard/absences');
            }

            if (isset(request()->all()['absent_students'])) {
                $absentIds = (array)request()->all()['absent_students'];
                $date      = request()->all()['date_absence'] ?? date('Y-m-d');
                $heure     = request()->all()['heure'] ?? '08:00:00';

                try {
                    $count = $this->service->recordAbsences(session('user'), $absentIds, $date, $heure);
                    session(['success' => "تم تسجيل قائمة الغيابات لليوم بنجاح وتحديث السجلات الكلية للطلبة."]);
                } catch (\Exception $e) {
                    session(['error' => 'حدث خطأ أثناء حفظ الغيابات: ' . $e->getMessage()]);
                }
            } else {
                session(['success' => 'تم تأكيد حضور جميع متربصي الفوج بالكامل لليوم.']);
            }
        }

        return $this->redirect('/dashboard/absences');
    }

    /**
     * Warning list — trainees with 3+ absences
     */
    public function warnings(): mixed
    {
        $warnings = $this->service->getAbsenceWarnings(session('user'));

        return $this->render('admin/absences/warnings', [
            'title'    => 'سجل الإنذارات والإقصاءات - SGFEP',
            'warnings' => $warnings,
        ]);
    }

    /**
     * Render a printable warning letter for a specific trainee
     */
    public function printWarning(string|int $id): mixed
    {
        if (\App\Helpers\SovereignLicensingHelper::getSetting('feature_print_actions_enabled', '1') !== '1') {
            abort(403, 'طباعة الإعذار معطلة من قبل مدير النظام / Printing is disabled by administrator.');
        }

        $apprenantId = (int)$id;

        try {
            $trainee = $this->service->getTraineeForWarningLetter(session('user'), $apprenantId);
        } catch (\Exception $e) {
            session(['error' => $e->getMessage()]);
            return $this->redirect('/dashboard/absences/warnings');
        }

        return $this->render('admin/absences/warning_letter', [
            'title' => 'طباعة ' . $trainee['warning_type'] . ' - SGFEP',
            't'     => $trainee,
            'type'  => $trainee['warning_type'],
            'level' => $trainee['warning_level'],
        ], 'print');
    }
}
