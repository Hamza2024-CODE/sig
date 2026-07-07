<?php

namespace App\Domains\Assets\Controllers;

use App\Controllers\BaseController;
use App\Domains\Assets\Services\AssetService;
use App\Core\Router;
use App\Core\Database;
use PDO;

class AssetApiController extends BaseController
{
    protected AssetService $service;

    public function __construct(AssetService $service)
    {
        $this->service = $service;
    }

    /**
     * POST /api/v1/assets/requests
     * Create a request for new technical equipment or workshop resources.
     */
    public function requestEquipment(): void
    {
        try {
            $user = Router::$authenticatedUser;
            if (!$user) {
                $this->json([
                    'status'  => 'error',
                    'code'    => 401,
                    'message' => 'Unauthorized. Authentication required.'
                ], 401);
                return;
            }

            // Resolve the etablissement_id associated with the authenticated user
            $etabId = (int)($user['etablissement_id'] ?? 0);

            if ($etabId <= 0) {
                // If user doesn't have an etab_id, check if they passed one in inputs (only for admins/api_clients)
                $role = strtolower($user['role_code'] ?? '');
                if ($role === 'admin' || $role === 'central' || $role === 'api_client') {
                    $etabId = (int)($_POST['etablissement_id'] ?? ($_GET['etablissement_id'] ?? 0));
                }
            }

            if ($etabId <= 0) {
                $this->json([
                    'status'  => 'error',
                    'code'    => 400,
                    'message' => 'User is not associated with any training establishment.'
                ], 400);
                return;
            }

            // Parse input parameters (from POST body or JSON payload)
            $input = $_POST;
            if (empty($input)) {
                $input = json_decode(file_get_contents('php://input'), true) ?? [];
            }

            $result = $this->service->requestEquipment($input, $etabId);

            $this->json([
                'status'  => 'success',
                'code'    => 201,
                'message' => 'Equipment request submitted successfully.',
                'data'    => $result
            ], 201);

        } catch (\Exception $e) {
            error_log('[AssetApiController::requestEquipment] Error: ' . $e->getMessage());

            $this->json([
                'status'  => 'error',
                'code'    => 500,
                'message' => 'Failed to submit equipment request: ' . $e->getMessage()
            ], 500);
        }
    }
}
