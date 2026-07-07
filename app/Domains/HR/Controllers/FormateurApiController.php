<?php

namespace App\Domains\HR\Controllers;

use App\Controllers\BaseController;
use App\Domains\HR\Services\FormateurService;

class FormateurApiController extends BaseController
{
    protected FormateurService $service;

    public function __construct(FormateurService $service)
    {
        $this->service = $service;
    }

    /**
     * GET /api/v1/hr/formateurs/vacant-hours
     * Returns a JSON list of all formateurs with their vacant slots.
     */
    public function vacantHours(): void
    {
        try {
            $data = $this->service->getVacantHoursForAll();

            $this->json([
                'status'  => 'success',
                'code'    => 200,
                'message' => 'Vacant hours calculated successfully.',
                'data'    => $data
            ], 200);

        } catch (\Exception $e) {
            error_log('[FormateurApiController::vacantHours] Error: ' . $e->getMessage());

            $this->json([
                'status'  => 'error',
                'code'    => 500,
                'message' => 'Failed to calculate vacant hours: ' . $e->getMessage()
            ], 500);
        }
    }
}
