<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IdsituationadministratType extends Model
{
    protected $table      = 'idsituationadministrat_type';
    protected $primaryKey = 'IDIDSituationAdministrat_type';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
    ];
}