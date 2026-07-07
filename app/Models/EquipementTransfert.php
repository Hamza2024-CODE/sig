<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EquipementTransfert extends Model
{
    protected $table      = 'equipement_transfert';
    protected $primaryKey = 'IDEquipement_transfert';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'IDEquipement',
        'Motif',
        'Valide',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function equipement()
    {
        return $this->belongsTo(\Equipement::class, 'IDEquipement', 'IDEquipement');
    }
}