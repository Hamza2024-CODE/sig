<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Equipementexploi extends Model
{
    protected $table      = 'equipementexploi';
    protected $primaryKey = 'IDEquipementExploi';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
    ];
}