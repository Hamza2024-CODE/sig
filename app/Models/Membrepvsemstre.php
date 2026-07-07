<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Membrepvsemstre extends Model
{
    protected $table      = 'membrepvsemstre';
    protected $primaryKey = 'IDMembrepvsemstre';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'IDSection_Semestre',
        'NomFonction',
        'NomPrenom',
        'QualiteMembre',
        'NumOrd',
        'IDEncadrement',
        'IDGrade',
        'IDFonctions',
        'Validation',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function sectionSemestre()
    {
        return $this->belongsTo(\SectionSemestre::class, 'IDSection_Semestre', 'IDSection_Semestre');
    }

    public function encadrement()
    {
        return $this->belongsTo(\Encadrement::class, 'IDEncadrement', 'IDEncadrement');
    }

    public function grade()
    {
        return $this->belongsTo(\Grade::class, 'IDGrade', 'IDGrade');
    }

    public function fonction()
    {
        return $this->belongsTo(\Fonction::class, 'IDFonctions', 'IDFonctions');
    }
}