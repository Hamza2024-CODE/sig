<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DecretEtat extends Model
{
    protected $table      = 'decret_etat';
    protected $primaryKey = 'IDDecret_Etat';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
    ];
}