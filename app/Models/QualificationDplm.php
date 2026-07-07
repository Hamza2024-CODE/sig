<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QualificationDplm extends Model
{
    protected $table      = 'qualification_dplm';
    protected $primaryKey = 'IDqualification_dplm';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
        'Abr',
        'AbrFr',
        'code',
        'NumOrd',
        'AgeMAx',
        'AgeMin',
    ];
}