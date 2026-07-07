<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use OpenApi\Attributes as OA;
use App\Domains\Assets\Services\AssetService;
use Illuminate\Support\Facades\DB;

class AssetApiController extends Controller
{
    protected AssetService $service;

    public function __construct(AssetService $service)
    {
        if (app()->runningInConsole()) { return; }
        $this->service = $service;
    }

    /**
     * POST /api/v1/assets/requests
     * Create a request for new technical equipment or workshop resources.
     */
    #[OA\Post(
        path: "/api/v1/assets/requests",
        summary: "إنشاء طلب جديد للتجهيزات والمعدات البيداغوجية للمؤسسة",
        tags: ["Assets / Equipment"],
        security: [["bearerAuth" => []], ["ApiKeyAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "etablissement_id", type: "integer", description: "معرف المؤسسة (للأدمن)", example: 2005),
                    new OA\Property(property: "item_name", type: "string", description: "اسم التجهيز أو المورد المطلوب", example: "طابعات ثلاثية الأبعاد"),
                    new OA\Property(property: "quantity", type: "integer", description: "الكمية المطلوبة", example: 3),
                    new OA\Property(property: "urgency", type: "string", enum: ["low", "medium", "high"], example: "high")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "تم إنشاء طلب التجهيزات بنجاح",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "status", type: "string", example: "success"),
                        new OA\Property(property: "code", type: "integer", example: 200),
                        new OA\Property(property: "message", type: "string", example: "Equipment request submitted successfully."),
                        new OA\Property(property: "request_id", type: "integer", example: 45)
                    ]
                )
            ),
            new OA\Response(response: 400, description: "طلب غير صالح - مدخلات خاطئة أو المؤسسة غير محددة"),
            new OA\Response(response: 401, description: "غير مصرح بالدخول")
        ]
    )]
    public function requestEquipment(): mixed
    {
        try {
            $user = session('user');
            if (!$user) {
                return $this->json([
                    'status'  => 'error',
                    'code'    => 401,
                    'message' => 'Unauthorized. Authentication required.'
                ], 401);
            }

            // Resolve the etablissement_id associated with the authenticated user
            $etabId = (int)($user['etablissement_id'] ?? 0);

            if ($etabId <= 0) {
                // If user doesn't have an etab_id, check if they passed one in inputs (only for admins/api_clients)
                $role = strtolower($user['role_code'] ?? '');
                if ($role === 'admin' || $role === 'central' || $role === 'api_client') {
                    $etabId = (int)request()->input('etablissement_id', 0);
                }
            }

            if ($etabId <= 0) {
                return $this->json([
                    'status'  => 'error',
                    'code'    => 400,
                    'message' => 'User is not associated with any training establishment.'
                ], 400);
            }

            // Parse input parameters
            $input = request()->all();

            $result = $this->service->requestEquipment($input, $etabId);

            return $this->json([
                'status'  => 'success',
                'code'    => 201,
                'message' => 'Equipment request submitted successfully.',
                'data'    => $result
            ], 201);

        } catch (\Exception $e) {
            error_log('[AssetApiController::requestEquipment] Error: ' . $e->getMessage());

            return $this->json([
                'status'  => 'error',
                'code'    => 500,
                'message' => 'Failed to submit equipment request: ' . $e->getMessage()
            ], 500);
        }
    }
}
