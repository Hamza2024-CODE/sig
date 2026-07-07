<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SessionModeFormation extends Model
{
    protected $table      = 'session_mode_formation';
    protected $primaryKey = 'IDsession_Mode_formation';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'IDSession',
        'IDMode_formation',
        'Fermee',
        'ouvertoffre',
        'ouvertinscription',
        'Cloturee',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function session()
    {
        return $this->belongsTo(\Session::class, 'IDSession', 'IDSession');
    }

    public function modeFormation()
    {
        return $this->belongsTo(\ModeFormation::class, 'IDMode_formation', 'IDMode_formation');
    }
}