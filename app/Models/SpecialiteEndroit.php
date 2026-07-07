<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SpecialiteEndroit extends Model
{
    protected $table      = 'specialite_endroit';
    protected $primaryKey = 'IDSpecialite_endroit';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
        'code',
        'codefr',
    ];
}