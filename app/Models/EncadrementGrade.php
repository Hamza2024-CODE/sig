<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EncadrementGrade extends Model
{
    protected $table      = 'encadrement_grade';
    protected $primaryKey = 'IDEncadrement_Grade';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'IDGrade',
        'IDEncadrement',
        'dateinstal',
        'dateconf',
        'faitstage',
        'DateFIn',
        'visafp',
        'datevisafp',
        'visacf',
        'datevisacf',
        'Obs',
        'IDSituationAdministrat',
        'Etablissemnt',
        'IDMode_Promotion',
        'NumOrd',
        'IDetablissement',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function grade()
    {
        return $this->belongsTo(\Grade::class, 'IDGrade', 'IDGrade');
    }

    public function encadrement()
    {
        return $this->belongsTo(\Encadrement::class, 'IDEncadrement', 'IDEncadrement');
    }

    public function situationAdministrat()
    {
        return $this->belongsTo(\SituationAdministrat::class, 'IDSituationAdministrat', 'IDSituationAdministrat');
    }

    public function modePromotion()
    {
        return $this->belongsTo(\ModePromotion::class, 'IDMode_Promotion', 'IDMode_Promotion');
    }

    public function etablissement()
    {
        return $this->belongsTo(\Etablissement::class, 'IDetablissement', 'IDetablissement');
    }
}