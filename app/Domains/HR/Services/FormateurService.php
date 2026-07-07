<?php

namespace App\Domains\HR\Services;

use App\Domains\HR\Repositories\FormateurRepository;

class FormateurService
{
    protected FormateurRepository $repo;

    public function __construct(FormateurRepository $repo)
    {
        $this->repo = $repo;
    }

    /**
     * Compute and return all vacant slots for all formateurs.
     */
    public function getVacantHoursForAll(): array
    {
        $formateurs = $this->repo->findAllFormateurs();
        $result = [];

        foreach ($formateurs as $formateur) {
            $id = (int)$formateur['id'];
            $busy = $this->repo->findBusySlotsByFormateur($id);
            $vacant = $this->calculateVacantSlots($busy);

            $result[] = [
                'formateur_id' => $id,
                'nom_complet'  => trim($formateur['nom'] . ' ' . $formateur['prenom']),
                'email'        => $formateur['email'],
                'etab_id'      => $formateur['etab_id'],
                'vacant_slots' => $vacant
            ];
        }

        return $result;
    }

    /**
     * Compute vacant slots given busy slots.
     */
    private function calculateVacantSlots(array $busySlots): array
    {
        // 20 standard weekly slots
        $standardDays = [
            1 => 'الأحد',
            2 => 'الاثنين',
            3 => 'الثلاثاء',
            4 => 'الأربعاء',
            5 => 'الخميس'
        ];

        $standardHours = [
            ['d' => '08:00', 'f' => '10:00', 'label' => 'الفترة الصباحية الأولى'],
            ['d' => '10:00', 'f' => '12:00', 'label' => 'الفترة الصباحية الثانية'],
            ['d' => '13:00', 'f' => '15:00', 'label' => 'الفترة المسائية الأولى'],
            ['d' => '15:00', 'f' => '17:00', 'label' => 'الفترة المسائية الثانية']
        ];

        // Format busy slots for quick checking: "day_num:start_hour"
        $busyMap = [];
        foreach ($busySlots as $slot) {
            $dayNum = (int)$slot['jour_num'];
            $start = substr(trim($slot['heure_debut']), 0, 5); // get hh:mm
            $busyMap["{$dayNum}:{$start}"] = true;
        }

        $vacant = [];
        foreach ($standardDays as $dayNum => $dayName) {
            foreach ($standardHours as $hourSlot) {
                $start = $hourSlot['d'];
                $end = $hourSlot['f'];
                $key = "{$dayNum}:{$start}";

                if (!isset($busyMap[$key])) {
                    $vacant[] = [
                        'day_num'     => $dayNum,
                        'day_name'    => $dayName,
                        'start_time'  => $start,
                        'end_time'    => $end,
                        'description' => $hourSlot['label']
                    ];
                }
            }
        }

        return $vacant;
    }
}
