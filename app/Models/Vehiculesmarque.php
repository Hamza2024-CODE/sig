<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vehiculesmarque extends Model
{
    protected $table      = 'vehiculesmarque';
    protected $primaryKey = 'IDVehiculesMarque';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
        'Nationn',
    ];
}