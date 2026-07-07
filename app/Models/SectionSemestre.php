<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SectionSemestre extends Model
{
    protected $table      = 'section_semestre';
    protected $primaryKey = 'IDSection_Semestre';
    public    $timestamps = false;

    protected $fillable = [
        'IDSection',
        'NumSem',
        'DateD',
        'DateF',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function section()
    {
        return $this->belongsTo(Section::class, 'IDSection', 'IDSection');
    }

    public function modules()
    {
        return $this->hasMany(SectionSemestreModule::class, 'IDSection_Semestre', 'IDSection_Semestre');
    }

    public function apprenantSemestres()
    {
        return $this->hasMany(ApprenantSectionSemestre::class, 'IDSection_Semestre', 'IDSection_Semestre');
    }
}
