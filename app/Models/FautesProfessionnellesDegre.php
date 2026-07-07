<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FautesProfessionnellesDegre extends Model
{
    protected $table      = 'fautes_professionnelles_degre';
    protected $primaryKey = 'IDfautes_professionnelles_Degre';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
        'NumOrd',
    ];
}