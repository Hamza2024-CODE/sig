<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Questionnaireperiode extends Model
{
    protected $table      = 'questionnaireperiode';
    protected $primaryKey = 'IDQuestionnairePeriode';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'IDSection_Semestre',
        'DureeTotalPe',
        'DureeTotalProp',
        'Dureehebdpe',
        'Dureehebdprop',
        'DureeStagehebdo',
        'PeriodeStage',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function sectionSemestre()
    {
        return $this->belongsTo(\SectionSemestre::class, 'IDSection_Semestre', 'IDSection_Semestre');
    }
}