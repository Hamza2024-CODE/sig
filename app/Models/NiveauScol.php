<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NiveauScol extends Model
{
    protected $table      = 'niveau_scol';
    protected $primaryKey = 'IDNiveau_Scol';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'IDCycle_scol',
        'NumOrd',
        'Nom',
        'NomFr',
        'Abr',
        'AbrFr',
        'iDMihnati',
        'activee',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function cycleScol()
    {
        return $this->belongsTo(\CycleScol::class, 'IDCycle_scol', 'IDCycle_scol');
    }
}