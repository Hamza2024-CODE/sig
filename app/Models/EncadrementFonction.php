<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EncadrementFonction extends Model
{
    protected $table      = 'encadrement_fonctions';
    protected $primaryKey = 'IDEncadrement_Fonctions';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'IDFonctions',
        'IDEncadrement',
        'Dateinstal',
        'DateFIn',
        'NumOrd',
        'DateDecision',
        'NumDection',
        'IDetablissement',
        'IDSituationAdministrat',
        'visafp',
        'datevisafp',
        'visacf',
        'datevisacf',
        'Obs',
        'NumDecret',
        'DateDecret',
        'NumJournal',
        'DateJournal',
        'AnneJournal',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function fonction()
    {
        return $this->belongsTo(\Fonction::class, 'IDFonctions', 'IDFonctions');
    }

    public function encadrement()
    {
        return $this->belongsTo(\Encadrement::class, 'IDEncadrement', 'IDEncadrement');
    }

    public function etablissement()
    {
        return $this->belongsTo(\Etablissement::class, 'IDetablissement', 'IDetablissement');
    }

    public function situationAdministrat()
    {
        return $this->belongsTo(\SituationAdministrat::class, 'IDSituationAdministrat', 'IDSituationAdministrat');
    }
}