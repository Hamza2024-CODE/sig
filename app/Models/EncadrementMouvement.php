<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EncadrementMouvement extends Model
{
    protected $table      = 'encadrement_mouvement';
    protected $primaryKey = 'IDEncadrement_Mouvement';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'IDEncadrement_ProposiLieu',
        'NumOrd',
        'Obs_Dfep',
        'Obs_Encadr',
        'IDEncadrement',
        'ok',
        'IDDFEP',
        'IDetablissement',
        'Cause',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function encadrementProposiLieu()
    {
        return $this->belongsTo(\EncadrementProposiLieu::class, 'IDEncadrement_ProposiLieu', 'IDEncadrement_ProposiLieu');
    }

    public function encadrement()
    {
        return $this->belongsTo(\Encadrement::class, 'IDEncadrement', 'IDEncadrement');
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