<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SpecialiteProgrammemode extends Model
{
    protected $table      = 'specialite_programmemode';
    protected $primaryKey = 'IDSpecialite_Programmemode';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
    ];
}