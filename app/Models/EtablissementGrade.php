<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EtablissementGrade extends Model
{
    protected $table      = 'etablissement_grade';
    protected $primaryKey = 'IDetablissement_Grade';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'IDGrade',
        'IDetablissement',
        'Nbr',
        'NbrF',
        'allo',
        'Occu',
        'vacan',
        'Besoin',
        'Obs',
        'Validation',
        'ValidationDfp',
        'Surplus',
        'vacanpourformation',
        'IDannee',
        'Indice',
        'indiceinf',
        'indicemoy',
        'indicesup',
        'categorie',
        'Traitementannuel',
        'Primeetindemnites',
        'Depenceannuel',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function grade()
    {
        return $this->belongsTo(\Grade::class, 'IDGrade', 'IDGrade');
    }

    public function etablissement()
    {
        return $this->belongsTo(\Etablissement::class, 'IDetablissement', 'IDetablissement');
    }

    public function annee()
    {
        return $this->belongsTo(\Annee::class, 'IDannee', 'IDannee');
    }
}