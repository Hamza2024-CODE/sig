<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Offert extends Model
{
    protected $table      = 'offert';
    protected $primaryKey = 'IDOffert';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
        'NRS',
        'NIF',
        'NomResponsable',
        'NbrEmploy',
    ];
}