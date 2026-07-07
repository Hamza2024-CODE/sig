<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EncadrementFautesProfessionnelle extends Model
{
    protected $table      = 'encadrement_fautes_professionnelles';
    protected $primaryKey = 'IDEncadrement_Fautes_Professionnelles';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'NumDecision',
        'DateDecision',
        'Cause',
        'IDFautes_Professionnelles',
        'IDEncadrement',
        'DateRecoure',
        'NumDeciRecoure',
        'DateDeciRecoure',
        'Rehabilitation',
        'NumDeciRehab',
        'DateDeciRehab',
        'IDFautes_ProfessionnellesRehab',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function fautesProfessionnelle()
    {
        return $this->belongsTo(\FautesProfessionnelle::class, 'IDFautes_Professionnelles', 'IDFautes_Professionnelles');
    }

    public function encadrement()
    {
        return $this->belongsTo(\Encadrement::class, 'IDEncadrement', 'IDEncadrement');
    }

    public function fautesProfessionnellesRehab()
    {
        return $this->belongsTo(\FautesProfessionnellesRehab::class, 'IDFautes_ProfessionnellesRehab', 'IDFautes_ProfessionnellesRehab');
    }
}