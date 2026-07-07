<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CalTacheBureau extends Model
{
    protected $table      = 'cal_tache_bureau';
    protected $primaryKey = 'id';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'IDBureau',
        'IDCal_Tache',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function bureau()
    {
        return $this->belongsTo(\Bureau::class, 'IDBureau', 'IDBureau');
    }

    public function calTache()
    {
        return $this->belongsTo(\CalTache::class, 'IDCal_Tache', 'IDCal_Tache');
    }
}