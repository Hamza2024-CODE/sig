<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EncadrementLien extends Model
{
    protected $table      = 'encadrement_lien';
    protected $primaryKey = 'IDEncadrement_lien';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Lien',
        'IDEncadrement',
        'Obs',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function encadrement()
    {
        return $this->belongsTo(\Encadrement::class, 'IDEncadrement', 'IDEncadrement');
    }
}