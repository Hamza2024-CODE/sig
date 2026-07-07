<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SessionDfep extends Model
{
    protected $table      = 'session_dfep';
    protected $primaryKey = 'IDSession_DFEP';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'IDDFEP',
        'IDSession',
        'Encour',
        'Clouture',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function dFEP()
    {
        return $this->belongsTo(\DFEP::class, 'IDDFEP', 'IDDFEP');
    }

    public function session()
    {
        return $this->belongsTo(\Session::class, 'IDSession', 'IDSession');
    }
}