<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Logementtype extends Model
{
    protected $table      = 'logementtype';
    protected $primaryKey = 'IDLogementType';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
    ];
}