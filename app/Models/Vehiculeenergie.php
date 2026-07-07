<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vehiculeenergie extends Model
{
    protected $table      = 'vehiculeenergie';
    protected $primaryKey = 'IDVehiculeEnergie';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
    ];
}