<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bureau extends Model
{
    protected $table      = 'bureau';
    protected $primaryKey = 'IDBureau';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
        'IDService',
        'Obs',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function service()
    {
        return $this->belongsTo(\Service::class, 'IDService', 'IDService');
    }
}