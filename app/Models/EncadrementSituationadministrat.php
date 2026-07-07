<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EncadrementSituationadministrat extends Model
{
    protected $table      = 'encadrement_situationadministrat';
    protected $primaryKey = 'IDEncadrement_SituationAdministrat';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'IDSituationAdministrat',
        'IDEncadrement',
        'Datedeb',
        'DatePv',
        'DateFIn',
        'Obs',
        'NumPv',
        'NumOrd',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function situationAdministrat()
    {
        return $this->belongsTo(\SituationAdministrat::class, 'IDSituationAdministrat', 'IDSituationAdministrat');
    }

    public function encadrement()
    {
        return $this->belongsTo(\Encadrement::class, 'IDEncadrement', 'IDEncadrement');
    }
}