<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EquipementOb extends Model
{
    protected $table      = 'equipement_obs';
    protected $primaryKey = 'IDequipement_obs';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
        'Date',
    ];
}