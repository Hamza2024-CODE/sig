<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vehicule extends Model
{
    protected $table      = 'vehicule';
    protected $primaryKey = 'IDVehicule';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'IDetablissement',
        'IDVehiculesType',
        'Immatriculation',
        'Annepremier',
        'NumDecision',
        'DateDecision',
        'Etatv',
        'kilometrage',
        'marqueCommerce',
        'NumChasse',
        'charge',
        'poistotalcharge',
        'poisutilie',
        'nbrPlace',
        'puissancee',
        'IDVehiculeEnergie',
        'Immatriculationprec',
        'Obs',
        'etablissemntEncour',
        'IDVehiculegenre',
        'IDVehiculesMarque',
        'NIV',
        'typeMarque',
        'Validation',
        'ValidationDfp',
        'NumserieCarte',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function etablissement()
    {
        return $this->belongsTo(\Etablissement::class, 'IDetablissement', 'IDetablissement');
    }

    public function vehiculesType()
    {
        return $this->belongsTo(\VehiculesType::class, 'IDVehiculesType', 'IDVehiculesType');
    }

    public function vehiculeEnergie()
    {
        return $this->belongsTo(\VehiculeEnergie::class, 'IDVehiculeEnergie', 'IDVehiculeEnergie');
    }

    public function vehiculegenre()
    {
        return $this->belongsTo(\Vehiculegenre::class, 'IDVehiculegenre', 'IDVehiculegenre');
    }

    public function vehiculesMarque()
    {
        return $this->belongsTo(\VehiculesMarque::class, 'IDVehiculesMarque', 'IDVehiculesMarque');
    }
}