<?php

namespace App\Domains\Academic\Controllers;

use App\Controllers\BaseController;
use App\Domains\Academic\Services\DiplomeService;
use Exception;

class DiplomeController extends BaseController
{
    protected DiplomeService $service;

    public function __construct(DiplomeService $service)
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
     * Generate graduation diploma and issue records
     */
    public function generate(string|int $stagiaire_id)
    {
        try {
            $this->service->generateDiploma((int)$stagiaire_id, $_SESSION['user']);
            $_SESSION['flash_success'] = 'تم إصدار شهادة التخرج الرسمية بنجاح / Diplôme généré avec succès';
        } catch (Exception $e) {
            $_SESSION['flash_error'] = $e->getMessage();
        }

        header('Location: ' . url('dashboard/diplomes'));
        exit;
    }
}
