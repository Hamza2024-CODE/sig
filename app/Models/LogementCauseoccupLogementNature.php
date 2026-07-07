<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LogementCauseoccupLogementNature extends Model
{
    protected $table      = 'logement_causeoccup_logement_nature';
    protected $primaryKey = 'IDLogement_CauseOccup_Logement_Nature';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'IDLogement_Nature',
        'IDLogement_CauseOccup',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function logementNature()
    {
        return $this->belongsTo(\LogementNature::class, 'IDLogement_Nature', 'IDLogement_Nature');
    }

    public function logementCauseOccup()
    {
        return $this->belongsTo(\LogementCauseOccup::class, 'IDLogement_CauseOccup', 'IDLogement_CauseOccup');
    }
}