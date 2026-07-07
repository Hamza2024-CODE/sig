<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EtablissementFonction extends Model
{
    protected $table      = 'etablissement_fonctions';
    protected $primaryKey = 'IDEtablissement_Fonctions';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nbr',
        'NbrF',
        'allo',
        'Occu',
        'vacan',
        'Besoin',
        'IDetablissement',
        'IDFonctions',
        'Obs',
        'Validation',
        'ValidationDfp',
        'IDannee',
        'Indice',
        'indiceinf',
        'indicesup',
        'categorie',
        'Traitementannuel',
        'Primeetindemnites',
        'Depenceannuel',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function etablissement()
    {
        return $this->belongsTo(\Etablissement::class, 'IDetablissement', 'IDetablissement');
    }

    public function fonction()
    {
        return $this->belongsTo(\Fonction::class, 'IDFonctions', 'IDFonctions');
    }

    public function annee()
    {
        return $this->belongsTo(\Annee::class, 'IDannee', 'IDannee');
    }
}