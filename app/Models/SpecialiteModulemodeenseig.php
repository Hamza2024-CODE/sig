<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SpecialiteModulemodeenseig extends Model
{
    protected $table      = 'specialite_modulemodeenseig';
    protected $primaryKey = 'IDSpecialite_ModuleModeenseig';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
    ];
}