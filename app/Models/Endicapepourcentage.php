<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Endicapepourcentage extends Model
{
    protected $table      = 'endicapepourcentage';
    protected $primaryKey = 'IDEndicapePourcentage';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
    ];
}