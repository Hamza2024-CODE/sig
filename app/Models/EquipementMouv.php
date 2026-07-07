<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EquipementMouv extends Model
{
    protected $table      = 'equipement_mouv';
    protected $primaryKey = 'IDequipement_interv';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'nomFr',
        'Date',
        'IDType_Interv',
        'IDEquipement',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function typeInterv()
    {
        return $this->belongsTo(\TypeInterv::class, 'IDType_Interv', 'IDType_Interv');
    }

    public function equipement()
    {
        return $this->belongsTo(\Equipement::class, 'IDEquipement', 'IDEquipement');
    }
}