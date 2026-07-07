<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Logement extends Model
{
    protected $table      = 'logement';
    protected $primaryKey = 'IDLogement';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'IDetablissement',
        'surface',
        'IDLogementType',
        'IDLogement_Nature',
        'InterneExterne',
        'Occup´┐¢',
        'IDLogement_CauseOccup',
        'Obs',
        'SituationJur',
        'Adres',
        'NomPrnEmployeur',
        'NumOperation',
        'Nomoperation',
        'numLot',
        'nomLot',
        'etage',
        'structuree',
        'gaz',
        'electricit´┐¢',
        'eau',
        'ETAT',
        'Tel',
        'Validation',
        'ValidationDfp',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function etablissement()
    {
        return $this->belongsTo(\Etablissement::class, 'IDetablissement', 'IDetablissement');
    }

    public function logementType()
    {
        return $this->belongsTo(\LogementType::class, 'IDLogementType', 'IDLogementType');
    }

    public function logementNature()
    {
        return $this->belongsTo(\LogementNature::class, 'IDLogement_Nature', 'IDLogement_Nature');
    }

    public function logementCauseOccup()
    {
        return $this->belongsTo(\LogementCauseOccup::class, 'IDLogement_CauseOccup', 'IDLogement_CauseOccup');
    }
}