<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use OpenApi\Attributes as OA;
use App\Domains\HR\Services\FormateurService;

class FormateurApiController extends Controller
{
    protected FormateurService $service;

    public function __construct(FormateurService $service)
    {
        if (app()->runningInConsole()) { return; }
        $this->service = $service;
    }

    /**
     * GET /api/v1/hr/formateurs/vacant-hours
     * Returns a JSON list of all formateurs with their vacant slots.
     */
    #[OA\Get(
        path: "/api/v1/hr/formateurs/vacant-hours",
        summary: "جلب الساعات الشاغرة لجميع المكونين والأساتذة",
        tags: ["HR / Formateurs"],
        security: [["bearerAuth" => []], ["ApiKeyAuth" => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: "تم احتساب وجلب الساعات الشاغرة بنجاح",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "status", type: "string", example: "success"),
                        new OA\Property(property: "code", type: "integer", example: 200),
                        new OA\Property(property: "data", type: "array", items: new OA\Items(type: "object"))
                    ]
                )
            ),
            new OA\Response(response: 500, description: "فشل في احتساب الساعات الشاغرة")
        ]
    )]
    public function vacantHours(): mixed
    {
        try {
            $data = $this->service->getVacantHoursForAll();

            return $this->json([
                'status'  => 'success',
                'code'    => 200,
                'message' => 'Vacant hours calculated successfully.',
                'data'    => $data
            ], 200);

        } catch (\Exception $e) {
            error_log('[FormateurApiController::vacantHours] Error: ' . $e->getMessage());

            return $this->json([
                'status'  => 'error',
                'code'    => 500,
                'message' => 'Failed to calculate vacant hours: ' . $e->getMessage()
            ], 500);
        }
    }
}
