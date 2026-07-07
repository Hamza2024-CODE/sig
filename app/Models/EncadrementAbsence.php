<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EncadrementAbsence extends Model
{
    protected $table      = 'encadrement_absence';
    protected $primaryKey = 'IDEncadrement_Absence';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'IDEncadrement',
        'Type',
        'Date',
        'matinsoir',
        'Obs',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function encadrement()
    {
        return $this->belongsTo(\Encadrement::class, 'IDEncadrement', 'IDEncadrement');
    }
}