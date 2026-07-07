<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SpecialiteModuletype extends Model
{
    protected $table      = 'specialite_moduletype';
    protected $primaryKey = 'IDspecialite_moduleType';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
        'code',
        'codefr',
    ];
}