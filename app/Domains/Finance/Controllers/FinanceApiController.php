<?php

namespace App\Domains\Finance\Controllers;

use App\Controllers\BaseController;
use App\Domains\Finance\Services\BudgetService;

class FinanceApiController extends BaseController
{
    protected BudgetService $service;

    public function __construct(BudgetService $service)
    {
        $this->service = $service;
    }

    /**
     * GET /api/v1/finance/reports/budget
     * Returns the encrypted budget report data using AES-256-CBC.
     */
    public function budgetReport(): void
    {
        try {
            $encryptedPayload = $this->service->getEncryptedBudgetReport();

            $this->json([
                'status'    => 'success',
                'code'      => 200,
                'message'   => 'Budget report generated and encrypted successfully (AES-256-CBC).',
                'payload'   => $encryptedPayload,
                'algorithm' => 'AES-256-CBC'
            ], 200);

        } catch (\Exception $e) {
            error_log('[FinanceApiController::budgetReport] Error: ' . $e->getMessage());

            $this->json([
                'status'  => 'error',
                'code'    => 500,
                'message' => 'Failed to generate budget report: ' . $e->getMessage()
            ], 500);
        }
    }
}
