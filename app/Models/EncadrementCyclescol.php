<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EncadrementCyclescol extends Model
{
    protected $table      = 'encadrement_cyclescol';
    protected $primaryKey = 'IDEncadrement_CycleScol';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'IDEncadrement',
        'IDCycle_scol',
        'NomEtablissement',
        'Du',
        'Au',
        'Obs',
        'IDWilayaa',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function encadrement()
    {
        return $this->belongsTo(\Encadrement::class, 'IDEncadrement', 'IDEncadrement');
    }

    public function cycleScol()
    {
        return $this->belongsTo(\CycleScol::class, 'IDCycle_scol', 'IDCycle_scol');
    }

    public function wilayaa()
    {
        return $this->belongsTo(\Wilayaa::class, 'IDWilayaa', 'IDWilayaa');
    }
}