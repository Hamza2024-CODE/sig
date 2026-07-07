<?php

return [
    /*
    |--------------------------------------------------------------------------
    | ERP Table Metadata Mapping
    |--------------------------------------------------------------------------
    |
    | Maps operational database tables in SGFEP to their respective domains:
    | - pedagogical: Student enrollment, specialities, grades, sections
    | - financial: Expenses, budget accounts, payroll, facilities, rentals
    | - hr: Personnel, grades, functions, attendance logs, disciplinary actions
    |
    */

    'domains' => [
        'pedagogical' => [
            'apprenant',
            'candidat',
            'section',
            'offre',
            'specialite',
            'specialites',
            'diplome',
            'rnfc_specialite',
        ],
        
        'financial' => [
            'budget',
            'depense',
            'facture',
            'logement',
            'logementtype',
            'masse_salariale',
            'salaries',
        ],
        
        'hr' => [
            'encadrement',
            'grade',
            'fonctions',
            'absences',
            'discipline',
            'utilisateur',
            'utilisateur_role',
        ],
    ],
    
    'stale_thresholds' => [
        'absences' => 7, // Alert if no new absences recorded in 7 days
        'discipline' => 15,
        'apprenant' => 30,
        'sync_jobs' => 1,
    ]
];
