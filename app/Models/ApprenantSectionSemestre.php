<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Pivot: apprenant ↔ section_semestre  (رصد السداسي)
 */
class ApprenantSectionSemestre extends Model
{
    protected $table      = 'apprenant_section_semstre';   // typo original conservé
    protected $primaryKey = 'IDapprenant_Section_semstre';
    public    $timestamps = false;

    protected $fillable = [
        'IDapprenant',
        'IDSection_Semestre',
        'NoteStage',
        'NoteMemoire',
        'NoteSoutenance',
        'Decision',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function apprenant()
    {
        return $this->belongsTo(Apprenant::class, 'IDapprenant', 'IDapprenant');
    }

    public function sectionSemestre()
    {
        return $this->belongsTo(SectionSemestre::class, 'IDSection_Semestre', 'IDSection_Semestre');
    }

    public function noteModules()
    {
        return $this->hasMany(
            ApprenantSectionSemestreModule::class,
            'IDapprenant_Section_semstre',
            'IDapprenant_Section_semstre'
        );
    }
}
