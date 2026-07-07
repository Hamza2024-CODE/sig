<?php

namespace App\Domains\Academic\Controllers;

use App\Controllers\BaseController;
use App\Domains\Academic\Services\OffresService;
use Exception;

class OffresController extends BaseController
{
    protected OffresService $service;

    public function __construct(OffresService $service)
    {
        $this->service = $service;
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['user'])) {
            header('Location: /login');
            exit;
        }
    }

    /**
     * Dashboard view of training offers list and statistics
     */
    public function index()
    {
        try {
            $data = $this->service->getDashboardData($_SESSION['user'], $_GET);
            
            $this->render('admin/offres/index', [
                'title'          => 'عروض التكوين - SGFEP',
                'stats'          => $data['stats'],
                'dispositifs'    => $data['dispositifs'],
                'filieres'       => $data['filieres'],
                'offres_detail'  => $data['offres_detail'],
                'wilaya_name'    => $data['wilaya_name'],
                'specialites'    => $data['specialites'],
                'etablissements' => $data['etablissements'],
                'sessions'       => $data['sessions'],
                'role_code'      => $_SESSION['user']['role_code'] ?? '',
                'etab_id'        => (int)($_SESSION['user']['etablissement_id'] ?? 0),
                'dfep_id'        => (int)($_SESSION['user']['iddfep'] ?? 0),
            ]);
        } catch (Exception $e) {
            $_SESSION['flash_error'] = $e->getMessage();
            $this->redirect('/dashboard');
        }
    }

    /**
     * Validation board for regional (DFEP) and central administrators
     */
    public function validation()
    {
        try {
            $data = $this->service->getValidationDashboardData($_SESSION['user']);
            
            $this->render('admin/offres/validation', [
                'title'            => 'المصادقة على عروض التكوين - SGFEP',
                'pending_offres'   => $data['pending_offres'],
                'processed_offres' => $data['processed_offres'],
                'stats'            => $data['stats'],
                'wilaya_name'      => $data['wilaya_name'],
                'role_code'        => $_SESSION['user']['role_code'] ?? '',
                'sessions'         => $data['sessions'],
                'etablissements'   => $data['etablissements']
            ]);
        } catch (Exception $e) {
            $_SESSION['flash_error'] = $e->getMessage();
            $this->redirect('/dashboard');
        }
    }

    /**
     * Store new training offer
     */
    public function storeOffre()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $this->service->createOffer($_POST, $_SESSION['user']);
                $_SESSION['flash_success'] = 'تم إدراج عرض التكوين الجديد بنجاح / Offre de formation ajoutée';
            } catch (Exception $e) {
                $_SESSION['flash_error'] = 'حدث خطأ أثناء حفظ عرض التكوين: ' . $e->getMessage();
            }
        }
        $redirectUrl = $_SERVER['HTTP_REFERER'] ?? '/sig/dashboard/offres';
        $this->redirect($redirectUrl);
    }

    /**
     * Update an existing training offer
     */
    public function updateOffre()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $this->service->updateOffer($_POST, $_SESSION['user']);
                $_SESSION['flash_success'] = 'تم تحديث عرض التكوين بنجاح / Offre de formation modifiée';
            } catch (Exception $e) {
                $_SESSION['flash_error'] = 'حدث خطأ أثناء تحديث عرض التكوين: ' . $e->getMessage();
            }
        }
        $redirectUrl = $_SERVER['HTTP_REFERER'] ?? '/sig/dashboard/offres';
        $this->redirect($redirectUrl);
    }

    /**
     * Delete training offer
     */
    public function deleteOffre(string|int $id)
    {
        try {
            $this->service->deleteOffer((int)$id, $_SESSION['user']);
            $_SESSION['flash_success'] = 'تم حذف عرض التكوين بنجاح / Offre de formation supprimée';
        } catch (Exception $e) {
            $_SESSION['flash_error'] = 'حدث خطأ أثناء حذف عرض التكوين: ' . $e->getMessage();
        }
        $redirectUrl = $_SERVER['HTTP_REFERER'] ?? '/sig/dashboard/offres';
        $this->redirect($redirectUrl);
    }

    /**
     * Submit offer to Regional Direction (DFEP)
     */
    public function soumettreOffre()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $this->service->submitOffer((int)$_POST['id'], $_SESSION['user']);
                $_SESSION['flash_success'] = 'تم تقديم عرض التكوين للمديرية الولائية بنجاح / Offre soumise à la direction';
            } catch (Exception $e) {
                $_SESSION['flash_error'] = 'حدث خطأ أثناء تقديم العرض: ' . $e->getMessage();
            }
        }
        $redirectUrl = $_SERVER['HTTP_REFERER'] ?? '/sig/dashboard/offres';
        $this->redirect($redirectUrl);
    }

    /**
     * Validate/Reject offer by Regional Direction (DFEP)
     */
    public function validerDirection()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (int)$_POST['id'];
            $action = $_POST['action'] ?? '';
            $motif = ($action === 'rejeter') ? ($_POST['motif_rejet'] ?? '') : null;
            try {
                $this->service->validateDirection($id, $action, $motif, $_SESSION['user']);
                $_SESSION['flash_success'] = ($action === 'approuver')
                    ? 'تمت المصادقة الولائية على عرض التكوين / Offre validée par la wilaya'
                    : 'تم رفض عرض التكوين ولائيا / Offre rejetée par la wilaya';
            } catch (Exception $e) {
                $_SESSION['flash_error'] = $e->getMessage();
            }
        }
        $redirectUrl = $_SERVER['HTTP_REFERER'] ?? '/sig/dashboard/offres';
        $this->redirect($redirectUrl);
    }

    /**
     * Validate/Reject offer by Central Administration
     */
    public function validerCentrale()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (int)$_POST['id'];
            $action = $_POST['action'] ?? '';
            $motif = ($action === 'rejeter') ? ($_POST['motif_rejet'] ?? '') : null;
            try {
                $this->service->validateCentral($id, $action, $motif, $_SESSION['user']);
                $_SESSION['flash_success'] = ($action === 'approuver')
                    ? 'تم القبول النهائي والمصادقة المركزية على عرض التكوين بنجاح / Offre approuvée par la centrale'
                    : 'تم رفض عرض التكوين مركزيا / Offre rejetée par la centrale';
            } catch (Exception $e) {
                $_SESSION['flash_error'] = $e->getMessage();
            }
        }
        $redirectUrl = $_SERVER['HTTP_REFERER'] ?? '/sig/dashboard/offres';
        $this->redirect($redirectUrl);
    }

    /**
     * Render handbook training offers print list
     */
    public function printOffres()
    {
        try {
            $offres = $this->service->getPrintOffres();
        } catch (Exception $e) {
            $offres = [];
        }

        $this->render('admin/offres/print', [
            'title' => 'طباعة دليل عروض التكوين - SGFEP',
            'offres' => $offres
        ], 'print');
    }
}
