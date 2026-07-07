<?php

namespace App\Domains\Academic\Controllers;

use App\Controllers\BaseController;
use App\Domains\Academic\Services\CandidatService;

/**
 * CandidatController (Domain)
 *
 * Thin HTTP adapter — delegates ALL business logic to CandidatService.
 * Replaces App\Controllers\Admin\CandidateController.
 *
 * Routes handled (see config/routes.php):
 *   GET  /dashboard/candidates          → index()
 *   POST /dashboard/candidates/action   → action()
 */
class CandidatController extends BaseController
{
    protected CandidatService $service;

    public function __construct(CandidatService $service)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['user'])) {
            header('Location: /login');
            exit;
        }
        $this->service = $service;
    }

    /**
     * List candidats with optional status filter
     */
    public function index(): void
    {
        $statusFilter = $_GET['status'] ?? 'all';
        $user         = $_SESSION['user'];

        try {
            $candidates = $this->service->listCandidats($user, $statusFilter);
        } catch (\Exception $e) {
            $candidates = [];
            $_SESSION['flash_error'] = 'خطأ في تحميل البيانات: ' . $e->getMessage();
        }

        $this->render('admin/candidates', [
            'title'        => 'إدارة ملفات المترشحين - SGFEP',
            'candidates'   => $candidates,
            'statusFilter' => $statusFilter,
        ]);
    }

    /**
     * Process validation/rejection decision on a candidat
     */
    public function action(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $candidatId  = (int)($_POST['pre_inscr_id'] ?? 0);
            $decision    = $_POST['decision'] ?? '';
            $motifRefus  = $_POST['motif_refus'] ?? null;
            $user        = $_SESSION['user'];

            try {
                $this->service->processValidation($user, $candidatId, $decision, $motifRefus);
                $_SESSION['flash_success'] = 'تم معالجة الطلب وتحديث القرار بنجاح.';
            } catch (\Exception $e) {
                $_SESSION['flash_error'] = 'حدث خطأ أثناء معالجة الطلب: ' . $e->getMessage();
            }
        }

        $redirectUrl = $_SERVER['HTTP_REFERER'] ?? '/sig/dashboard/candidates';
        header("Location: $redirectUrl");
        exit;
    }
}
