<?php

namespace App\Domains\Academic\Controllers;

use App\Controllers\BaseController;
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
class AbsencesController extends BaseController
{
    protected ApprenantService $service;

    public function __construct(ApprenantService $service)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['user'])) {
            $this->redirect('/login');
            exit;
        }
        $this->service = $service;
    }

    /**
     * Dashboard — absence summary statistics + recent list
     */
    public function index(): void
    {
        $stats = $this->service->getAbsenceDashboardStats($_SESSION['user']);

        $this->render('admin/absences/index', [
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
    public function add(): void
    {
        $students = $this->service->listForAbsenceRecording($_SESSION['user']);

        $this->render('admin/absences/add', [
            'title'    => 'تسجيل حضور الأفواج - SGFEP',
            'students' => $students,
        ]);
    }

    /**
     * Store absence records for selected trainees
     */
    public function store(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // CSRF guard
            $token = $_POST['csrf_token'] ?? '';
            if (empty($token) || $token !== ($_SESSION['csrf_token'] ?? '')) {
                $_SESSION['error'] = 'رمز التحقق من الأمن غير صالح. يرجى المحاولة مجدداً.';
                $this->redirect('/dashboard/absences');
                return;
            }

            if (isset($_POST['absent_students'])) {
                $absentIds = (array)$_POST['absent_students'];
                $date      = $_POST['date_absence'] ?? date('Y-m-d');
                $heure     = $_POST['heure'] ?? '08:00:00';

                try {
                    $count = $this->service->recordAbsences($_SESSION['user'], $absentIds, $date, $heure);
                    $_SESSION['success'] = "تم تسجيل قائمة الغيابات لليوم بنجاح وتحديث السجلات الكلية للطلبة.";
                } catch (\Exception $e) {
                    $_SESSION['error'] = 'حدث خطأ أثناء حفظ الغيابات: ' . $e->getMessage();
                }
            } else {
                $_SESSION['success'] = 'تم تأكيد حضور جميع متربصي الفوج بالكامل لليوم.';
            }
        }

        $this->redirect('/dashboard/absences');
    }

    /**
     * Warning list — trainees with 3+ absences
     */
    public function warnings(): void
    {
        $warnings = $this->service->getAbsenceWarnings($_SESSION['user']);

        $this->render('admin/absences/warnings', [
            'title'    => 'سجل الإنذارات والإقصاءات - SGFEP',
            'warnings' => $warnings,
        ]);
    }

    /**
     * Render a printable warning letter for a specific trainee
     */
    public function printWarning(string|int $id): void
    {
        $apprenantId = (int)$id;

        try {
            $trainee = $this->service->getTraineeForWarningLetter($_SESSION['user'], $apprenantId);
        } catch (\Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            $this->redirect('/dashboard/absences/warnings');
            return;
        }

        $this->render('admin/absences/warning_letter', [
            'title' => 'طباعة ' . $trainee['warning_type'] . ' - SGFEP',
            't'     => $trainee,
            'type'  => $trainee['warning_type'],
            'level' => $trainee['warning_level'],
        ], 'print');
    }
}
