<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LogementNature extends Model
{
    protected $table      = 'logement_nature';
    protected $primaryKey = 'IDLogement_Nature';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
    ];
}