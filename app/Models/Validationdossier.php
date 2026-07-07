<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Validationdossier extends Model
{
    protected $table      = 'validationdossier';
    protected $primaryKey = 'IDValidationDossier';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Obs',
        'DateValid',
        'IDDecision',
        'IDTache',
        'IDDFEP',
        'IDetablissement',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function decision()
    {
        return $this->belongsTo(\Decision::class, 'IDDecision', 'IDDecision');
    }

    public function tache()
    {
        return $this->belongsTo(\Tache::class, 'IDTache', 'IDTache');
    }

    public function dFEP()
    {
        return $this->belongsTo(\DFEP::class, 'IDDFEP', 'IDDFEP');
    }

    public function etablissement()
    {
        return $this->belongsTo(\Etablissement::class, 'IDetablissement', 'IDetablissement');
    }
}