<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NiveauScolEnca extends Model
{
    protected $table      = 'niveau_scol_enca';
    protected $primaryKey = 'IDNiveau_Scol_enca';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'IDCycle_scol',
        'NumOrd',
        'Nom',
        'NomFr',
        'Abr',
        'AbrFr',
        'activee',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function cycleScol()
    {
        return $this->belongsTo(\CycleScol::class, 'IDCycle_scol', 'IDCycle_scol');
    }
}