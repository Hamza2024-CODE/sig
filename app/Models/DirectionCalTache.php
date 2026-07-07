<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DirectionCalTache extends Model
{
    protected $table      = 'direction_cal_tache';
    protected $primaryKey = 'IDdirection_IDCal_Tache';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'IDCal_Tache',
        'IDdirection',
        'Nom',
        'Charger',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function calTache()
    {
        return $this->belongsTo(\CalTache::class, 'IDCal_Tache', 'IDCal_Tache');
    }

    public function direction()
    {
        return $this->belongsTo(\Direction::class, 'IDdirection', 'IDdirection');
    }
}