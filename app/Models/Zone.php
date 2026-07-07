<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Zone extends Model
{
    protected $table      = 'zone';
    protected $primaryKey = 'IDZone';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
        'IDDFEP',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function dFEP()
    {
        return $this->belongsTo(\DFEP::class, 'IDDFEP', 'IDDFEP');
    }
}