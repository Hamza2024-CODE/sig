<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SpecialiteUnitesdecompetence extends Model
{
    protected $table      = 'specialite_unitesdecompetences';
    protected $primaryKey = 'IDSpecialiteUnitesdeCompetences';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'code',
        'Nom',
        'NomFr',
        'NomEn',
    ];
}