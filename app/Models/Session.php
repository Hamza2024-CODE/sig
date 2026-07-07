<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Session extends Model
{
    protected $table      = 'session';
    protected $primaryKey = 'IDSession';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
        'DateD',
        'CodeMihnati',
        'Encour',
        'CodePsem',
        'Clouture',
        'IDSemestre_formation',
        'CodePanne',
        'DateDInscr',
        'DateFInscr',
        'JourDeSelection',
        'DateConcour',
        'DateAffResulta',
        'Obs',
        'Code',
        'Fermee',
        'ouvertoffre',
        'ouvertinscription',
        'NomsessIonnat',
        'Nomfrsessionnat',
        'DatedebInscrsessionnat',
        'DatefinInscrsessionnat',
        'Datedexamsessionnat',
        'Datefinexamsessionnat',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function semestreFormation()
    {
        return $this->belongsTo(\SemestreFormation::class, 'IDSemestre_formation', 'IDSemestre_formation');
    }
}