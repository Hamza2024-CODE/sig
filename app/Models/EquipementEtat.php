<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EquipementEtat extends Model
{
    protected $table      = 'equipement_etat';
    protected $primaryKey = 'IDequipement_etat';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
        'IDIDequipement_etatType',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function iDequipementEtatType()
    {
        return $this->belongsTo(\IDequipementEtatType::class, 'IDIDequipement_etatType', 'IDIDequipement_etatType');
    }
}