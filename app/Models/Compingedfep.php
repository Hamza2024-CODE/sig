<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Compingedfep extends Model
{
    protected $table      = 'compingedfep';
    protected $primaryKey = 'IDCompingeDfep';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'nbrPlace',
        'IDsemestre',
        'NbrFamille',
        'IDDFEP',
        'IDDFEPARTICIPE',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function semestre()
    {
        return $this->belongsTo(\Semestre::class, 'IDsemestre', 'IDsemestre');
    }

    public function dFEP()
    {
        return $this->belongsTo(\DFEP::class, 'IDDFEP', 'IDDFEP');
    }

    public function dFEPARTICIPE()
    {
        return $this->belongsTo(\DFEPARTICIPE::class, 'IDDFEPARTICIPE', 'IDDFEPARTICIPE');
    }
}