<?php

namespace App\Domains\Reporting\Controllers;

use App\Controllers\BaseController;
use App\Domains\Reporting\Services\ReportingService;

/**
 * ReportController
 *
 * Single thin HTTP adapter for ALL export and print operations.
 * Delegates entirely to ReportingService — zero SQL here.
 *
 * Routes (see config/routes.php):
 *   GET /dashboard/export/{type}         → export()   (CSV download)
 *   GET /dashboard/print/{type}          → print()    (browser HTML print)
 *
 * Query params forwarded to ReportingService as $filters:
 *   ?status=pending|approved|rejected    (for candidats)
 *   ?session_id=N                        (for offres)
 */
class ReportController extends BaseController
{
    protected ReportingService $reportingService;

    public function __construct(ReportingService $reportingService)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['user'])) {
            header('Location: /login');
            exit;
        }
        $this->reportingService = $reportingService;
    }

    /**
     * Stream a CSV export for the given report type.
     *
     * GET /dashboard/export/{type}
     *   type = apprenants | candidats | offres | absences
     */
    public function export(string $type): void
    {
        $user    = $_SESSION['user'];
        $filters = $this->extractFilters();

        try {
            // After this call the response is fully streamed — nothing to return
            $this->reportingService->generate($type, 'csv', $user, $filters);
        } catch (\Exception $e) {
            // Headers may have been partially sent — log and bail
            error_log('[ReportController::export] ' . $e->getMessage());
            // If headers not yet sent, redirect with error
            if (!headers_sent()) {
                $_SESSION['flash_error'] = 'خطأ في إنشاء التقرير: ' . $e->getMessage();
                header('Location: /dashboard');
            }
        }
        exit;
    }

    /**
     * Stream an HTML print view for the given report type.
     *
     * GET /dashboard/print/{type}
     *   type = apprenants | candidats | offres | absences
     */
    public function printReport(string $type): void
    {
        $user    = $_SESSION['user'];
        $filters = $this->extractFilters();

        try {
            $this->reportingService->generate($type, 'print', $user, $filters);
        } catch (\Exception $e) {
            error_log('[ReportController::printReport] ' . $e->getMessage());
            if (!headers_sent()) {
                $_SESSION['flash_error'] = 'خطأ في إنشاء التقرير: ' . $e->getMessage();
                header('Location: /dashboard');
            }
        }
        exit;
    }

    /**
     * Stream a PDF view for the given report type.
     *
     * GET /dashboard/pdf/{type}
     *   type = apprenants | candidats | offres | absences
     */
    public function pdfReport(string $type): void
    {
        $user    = $_SESSION['user'];
        $filters = $this->extractFilters();

        try {
            $this->reportingService->generate($type, 'pdf', $user, $filters);
        } catch (\Exception $e) {
            error_log('[ReportController::pdfReport] ' . $e->getMessage());
            if (!headers_sent()) {
                $_SESSION['flash_error'] = 'خطأ في إنشاء ملف PDF للتقرير: ' . $e->getMessage();
                header('Location: /dashboard');
            }
        }
        exit;
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Extract safe filter parameters from the HTTP GET query string.
     */
    private function extractFilters(): array
    {
        return [
            'status'     => $_GET['status']     ?? 'all',
            'session_id' => (int)($_GET['session_id'] ?? 0) ?: null,
            'etab_id'    => (int)($_GET['etab_id']    ?? 0) ?: null,
            'mode'       => $_GET['mode']        ?? null,
        ];
    }
}
