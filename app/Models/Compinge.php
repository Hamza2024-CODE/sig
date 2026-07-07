<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Compinge extends Model
{
    protected $table      = 'compinge';
    protected $primaryKey = 'IDcompinge';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'NbrFamille4',
        'nbrPlace',
        'IDDFEP',
        'IDetablissement',
        'NbrFamille6',
        'NbrChambre',
        'CapaciteChambre',
        'nbrsalle',
        'CapaciteSalle',
        'EspaceSport',
        'EspaceSportMtico',
        'foyer',
        'sanitaire',
        'nbesanitaire',
        'IDsemestre',
        'Obs',
        'Datedeb',
        'DateFIn',
        'Duree',
        'TelPermanance',
        'NomPrenomPerman',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function dFEP()
    {
        return $this->belongsTo(\DFEP::class, 'IDDFEP', 'IDDFEP');
    }

    public function etablissement()
    {
        return $this->belongsTo(\Etablissement::class, 'IDetablissement', 'IDetablissement');
    }

    public function semestre()
    {
        return $this->belongsTo(\Semestre::class, 'IDsemestre', 'IDsemestre');
    }
}