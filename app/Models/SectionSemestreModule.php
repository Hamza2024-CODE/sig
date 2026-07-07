<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SectionSemestreModule extends Model
{
    protected $table      = 'section_semestre_module';
    protected $primaryKey = 'IDsection_semestre_Module';
    public    $timestamps = false;

    protected $fillable = [
        'IDSection_Semestre',
        'IDModule',
        'NomMdl',
        'NomFrMdl',
        'coef',
        'IDEncadrement',
        'type_module',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function sectionSemestre()
    {
        return $this->belongsTo(SectionSemestre::class, 'IDSection_Semestre', 'IDSection_Semestre');
    }

    public function encadrement()
    {
        return $this->belongsTo(Encadrement::class, 'IDEncadrement', 'IDEncadrement');
    }

    public function notes()
    {
        return $this->hasMany(ApprenantSectionSemestreModule::class, 'IDsection_semestre_Module', 'IDsection_semestre_Module');
    }
}
