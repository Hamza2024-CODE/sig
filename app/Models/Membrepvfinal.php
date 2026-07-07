<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Membrepvfinal extends Model
{
    protected $table      = 'membrepvfinal';
    protected $primaryKey = 'IDMembrepvfinal';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'NomFonction',
        'NomPrenom',
        'QualiteMembre',
        'NumOrd',
        'IDSection',
        'IDEncadrement',
        'IDGrade',
        'IDFonctions',
        'Validation',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function section()
    {
        return $this->belongsTo(\Section::class, 'IDSection', 'IDSection');
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