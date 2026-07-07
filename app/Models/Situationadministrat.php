<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Situationadministrat extends Model
{
    protected $table      = 'situationadministrat';
    protected $primaryKey = 'IDSituationAdministrat';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
        'NumOrd',
        'IDIDSituationAdministrat_type',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function iDSituationAdministratType()
    {
        return $this->belongsTo(\IDSituationAdministratType::class, 'IDIDSituationAdministrat_type', 'IDIDSituationAdministrat_type');
    }
}