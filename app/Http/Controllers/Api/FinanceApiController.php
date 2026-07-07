<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use OpenApi\Attributes as OA;
use App\Domains\Finance\Services\BudgetService;

class FinanceApiController extends Controller
{
    protected BudgetService $service;

    public function __construct(BudgetService $service)
    {
        if (app()->runningInConsole()) { return; }
        $this->service = $service;
    }

    /**
     * GET /api/v1/finance/reports/budget
     * Returns the encrypted budget report data using AES-256-CBC.
     */
    #[OA\Get(
        path: "/api/v1/finance/reports/budget",
        summary: "جلب تقرير الميزانية المشفر بنظام AES-256-CBC",
        tags: ["Finance"],
        security: [["bearerAuth" => []], ["ApiKeyAuth" => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: "تم توليد وتشفير تقرير الميزانية بنجاح",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "status", type: "string", example: "success"),
                        new OA\Property(property: "code", type: "integer", example: 200),
                        new OA\Property(property: "message", type: "string", example: "Budget report generated and encrypted successfully (AES-256-CBC)."),
                        new OA\Property(property: "payload", type: "string", example: "eyJpdiI6Ik9H..."),
                        new OA\Property(property: "algorithm", type: "string", example: "AES-256-CBC")
                    ]
                )
            ),
            new OA\Response(response: 500, description: "فشل في توليد تقرير الميزانية")
        ]
    )]
    public function budgetReport(): mixed
    {
        try {
            $encryptedPayload = $this->service->getEncryptedBudgetReport();

            return $this->json([
                'status'    => 'success',
                'code'      => 200,
                'message'   => 'Budget report generated and encrypted successfully (AES-256-CBC).',
                'payload'   => $encryptedPayload,
                'algorithm' => 'AES-256-CBC'
            ], 200);

        } catch (\Exception $e) {
            error_log('[FinanceApiController::budgetReport] Error: ' . $e->getMessage());

            return $this->json([
                'status'  => 'error',
                'code'    => 500,
                'message' => 'Failed to generate budget report: ' . $e->getMessage()
            ], 500);
        }
    }
}
