<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FelicitationsSanctiontype extends Model
{
    protected $table      = 'felicitations_sanctiontype';
    protected $primaryKey = 'IDFelicitations_SanctionType';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
        'Type',
    ];
}