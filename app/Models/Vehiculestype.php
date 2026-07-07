<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vehiculestype extends Model
{
    protected $table      = 'vehiculestype';
    protected $primaryKey = 'IDVehiculesType';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
    ];
}