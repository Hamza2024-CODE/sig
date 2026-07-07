<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Periodeencour extends Model
{
    protected $table      = 'periodeencour';
    protected $primaryKey = 'IDPeriodeencour';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
    ];
}