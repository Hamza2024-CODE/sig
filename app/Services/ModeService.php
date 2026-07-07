<?php

namespace App\Services;

class ModeService
{
    /**
     * Get all training mode translations.
     *
     * @return array
     */
    public static function getTranslations(): array
    {
        return [
            'residentiel' => [
                'ar' => 'حضوري',
                'fr' => 'Résidentiel'
            ],
            'apprentissage' => [
                'ar' => 'تمهين',
                'fr' => 'Apprentissage'
            ],
            'cours_soir' => [
                'ar' => 'دروس مسائية',
                'fr' => 'Cours du Soir'
            ],
            'formation_distance' => [
                'ar' => 'تكوين عن بعد',
                'fr' => 'Formation à Distance'
            ],
            'passerelle' => [
                'ar' => 'معابر',
                'fr' => 'Passerelle'
            ]
        ];
    }

    /**
     * Get label for a specific training mode.
     *
     * @param string $mode
     * @return array
     */
    public static function getLabels(string $mode): array
    {
        $translations = self::getTranslations();
        return $translations[strtolower($mode)] ?? [
            'ar' => $mode,
            'fr' => $mode
        ];
    }
}
