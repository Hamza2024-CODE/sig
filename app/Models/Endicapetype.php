<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Endicapetype extends Model
{
    protected $table      = 'endicapetype';
    protected $primaryKey = 'IDEndicapetype';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
    ];
}