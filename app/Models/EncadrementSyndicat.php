<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EncadrementSyndicat extends Model
{
    protected $table      = 'encadrement_syndicat';
    protected $primaryKey = 'IDEncadrement_syndicat';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'IDsyndicat',
        'IDEncadrement',
        'Datedebut',
        'DateFIn',
        'Ncarte',
        'Dcarte',
        'poste',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function syndicat()
    {
        return $this->belongsTo(\Syndicat::class, 'IDsyndicat', 'IDsyndicat');
    }

    public function encadrement()
    {
        return $this->belongsTo(\Encadrement::class, 'IDEncadrement', 'IDEncadrement');
    }
}