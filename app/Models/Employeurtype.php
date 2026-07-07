<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employeurtype extends Model
{
    protected $table      = 'employeurtype';
    protected $primaryKey = 'IDEmployeurType';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
    ];
}