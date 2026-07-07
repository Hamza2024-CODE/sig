<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EquipementComposantEtat extends Model
{
    protected $table      = 'equipement_composant_etat';
    protected $primaryKey = 'IDequipement_composant_Etat';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
    ];
}