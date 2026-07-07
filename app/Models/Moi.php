<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Moi extends Model
{
    protected $table      = 'mois';
    protected $primaryKey = 'IDMois';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'N',
        'Nom',
        'NomFr',
    ];
}