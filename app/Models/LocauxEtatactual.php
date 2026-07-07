<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LocauxEtatactual extends Model
{
    protected $table      = 'locaux_etatactual';
    protected $primaryKey = 'EtatActual';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
        'NumOrd',
    ];
}