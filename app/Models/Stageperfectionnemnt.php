<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Stageperfectionnemnt extends Model
{
    protected $table      = 'stageperfectionnemnt';
    protected $primaryKey = 'IDStagePerfectionnemnt';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'IDEncadrement',
        'theme',
        'themeFr',
        'DateD',
        'DateF',
        'Obs',
        'EtablissemntFormarion',
        'NumAttestation',
        'DateAttestation',
        'typeStage',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function encadrement()
    {
        return $this->belongsTo(\Encadrement::class, 'IDEncadrement', 'IDEncadrement');
    }
}