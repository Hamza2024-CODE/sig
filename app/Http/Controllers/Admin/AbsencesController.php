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
        $filters = [
            'section_id'   => request()->query('section_id'),
            'specialite_id'=> request()->query('specialite_id'),
            'trainee_type' => request()->query('trainee_type', 'all'),
            'search'       => request()->query('search'),
        ];

        $data = $this->service->getAttendancePageData(session('user'), $filters);

        return $this->render('admin/absences/index', [
            'title'               => 'مراقبة الحضور والغيابات البيداغوجية - SGFEP',
            'tCount'              => $data['stats']['total_trainees'],
            'aCount'              => $data['stats']['total_absences'],
            'wCount'              => $data['stats']['total_warnings'],
            'absences'            => $data['stats']['recent_absences'],
            'sections'            => $data['sections'],
            'specialites'         => $data['specialites'],
            'trainees'            => $data['trainees'],
            'displayed_new'       => $data['displayed_new'],
            'displayed_continuing'=> $data['displayed_continuing'],
            'filters'             => $data['selected_filters'],
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

            $input = request()->all();
            $date  = $input['date_absence'] ?? date('Y-m-d');
            $heure = $input['heure'] ?? '08:00:00';

            // Support new detailed attendance array format: attendance[apprenant_id] = 'absent'|'justified'|'present'|'late'
            $absentIds = [];
            if (isset($input['attendance']) && is_array($input['attendance'])) {
                foreach ($input['attendance'] as $apprenantId => $status) {
                    if (in_array($status, ['absent', 'justified', '1', 1])) {
                        $absentIds[] = (int)$apprenantId;
                    }
                }
            } elseif (isset($input['absent_students'])) {
                $absentIds = (array)$input['absent_students'];
            }

            if (!empty($absentIds)) {
                try {
                    $count = $this->service->recordAbsences(session('user'), $absentIds, $date, $heure);
                    session(['success' => "تم تسجيل وحفظ الغيابات والحضور لليوم بنجاح لـ ({$count}) متربص."]);
                } catch (\Exception $e) {
                    session(['error' => 'حدث خطأ أثناء حفظ الحضور والغيابات: ' . $e->getMessage()]);
                }
            } else {
                session(['success' => 'تم تأكيد حضور جميع المتربصين بالكامل لليوم المحدد.']);
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
