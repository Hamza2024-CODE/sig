<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Activiteencadreurtype extends Model
{
    protected $table      = 'activiteencadreurtype';
    protected $primaryKey = 'IDActiviteEncadreurType';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
    ];
}