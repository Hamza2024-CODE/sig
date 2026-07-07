<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Membrepv extends Model
{
    protected $table      = 'membrepv';
    protected $primaryKey = 'IDMembrePv';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'IDEncadrement',
        'IDFonctions',
        'NumPv',
        'NatuteMem',
        'IDGrade',
        'Validation',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function encadrement()
    {
        return $this->belongsTo(\Encadrement::class, 'IDEncadrement', 'IDEncadrement');
    }

    public function fonction()
    {
        return $this->belongsTo(\Fonction::class, 'IDFonctions', 'IDFonctions');
    }

    public function grade()
    {
        return $this->belongsTo(\Grade::class, 'IDGrade', 'IDGrade');
    }
}