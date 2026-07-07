<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LogementCauseoccup extends Model
{
    protected $table      = 'logement_causeoccup';
    protected $primaryKey = 'IDLogement_CauseOccup';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
    ];
}