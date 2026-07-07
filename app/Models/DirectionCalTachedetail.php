<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DirectionCalTachedetail extends Model
{
    protected $table      = 'direction_cal_tachedetail';
    protected $primaryKey = 'IDdirection_IDCal_TacheDetail';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'IDCal_TacheDetail',
        'IDdirection',
        'Nom',
        'Charger',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function calTacheDetail()
    {
        return $this->belongsTo(\CalTacheDetail::class, 'IDCal_TacheDetail', 'IDCal_TacheDetail');
    }

    public function direction()
    {
        return $this->belongsTo(\Direction::class, 'IDdirection', 'IDdirection');
    }
}