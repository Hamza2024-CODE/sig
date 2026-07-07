<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Notes par module pour un apprenant dans un semestre
 */
class ApprenantSectionSemestreModule extends Model
{
    protected $table      = 'apprenant_section_semstre_module';
    protected $primaryKey = 'IDapprenant_section_semestre_module';
    public    $timestamps = false;

    protected $fillable = [
        'IDapprenant_Section_semstre',
        'IDsection_semestre_Module',
        'NoteC1',
        'NoteC2',
        'NoteCs',
        'NoteR',
        'absc1',
        'absc2',
        'abscs',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function apprenantSemestre()
    {
        return $this->belongsTo(
            ApprenantSectionSemestre::class,
            'IDapprenant_Section_semstre',
            'IDapprenant_Section_semstre'
        );
    }

    public function module()
    {
        return $this->belongsTo(
            SectionSemestreModule::class,
            'IDsection_semestre_Module',
            'IDsection_semestre_Module'
        );
    }
}
