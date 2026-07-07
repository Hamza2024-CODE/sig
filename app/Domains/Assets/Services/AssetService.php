<?php

namespace App\Domains\Assets\Services;

use App\Domains\Assets\Repositories\AssetRepository;
use Exception;

class AssetService
{
    protected AssetRepository $repo;

    public function __construct(AssetRepository $repo)
    {
        $this->repo = $repo;
    }

    /**
     * Get all equipments for a specific establishment.
     */
    public function getEquipments(int $etabId): array
    {
        if ($etabId <= 0) {
            throw new Exception("معرف المؤسسة التكوينية غير صالح / Identifiant de l'établissement invalide.");
        }
        return $this->repo->findEquipmentsByEtablissement($etabId);
    }

    /**
     * Request/Register new equipment or workshop asset.
     */
    public function requestEquipment(array $input, int $etabId): array
    {
        $designation = trim($input['designation'] ?? '');
        $specialiteId = (int)($input['specialite_id'] ?? 0);
        $description = trim($input['description'] ?? '');

        if (empty($designation)) {
            throw new Exception("تسمية العتاد/التجهيز مطلوبة / Désignation de l'équipement requise.");
        }

        if ($specialiteId <= 0) {
            throw new Exception("التخصص المرتبط بالعتاد مطلوب / Spécialité associée requise.");
        }

        if ($etabId <= 0) {
            throw new Exception("المؤسسة التكوينية غير معروفة / Établissement non identifié.");
        }

        $data = [
            'designation'       => $designation,
            'specialite_id'     => $specialiteId,
            'etablissement_id'  => $etabId,
            'date_installation' => date('Y-m-d'),
            'description'       => !empty($description) ? $description : 'طلب عتاد عبر تطبيق الهاتف PWA'
        ];

        $requestId = $this->repo->insertEquipmentRequest($data);

        return [
            'request_id'   => $requestId,
            'designation'  => $designation,
            'requested_at' => $data['date_installation'],
            'status'       => 'en_attente'
        ];
    }
}
