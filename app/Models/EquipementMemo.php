<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EquipementMemo extends Model
{
    protected $table      = 'equipement_memo';
    protected $primaryKey = 'IDEquipement_memo';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'photo',
        'IDEquipement',
        'Num',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function equipement()
    {
        return $this->belongsTo(\Equipement::class, 'IDEquipement', 'IDEquipement');
    }
}