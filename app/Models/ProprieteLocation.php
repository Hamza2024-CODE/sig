<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProprieteLocation extends Model
{
    protected $table      = 'propriete_location';
    protected $primaryKey = 'IDPropriete_Location';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
    ];
}