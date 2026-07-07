<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Logementnaturejur extends Model
{
    protected $table      = 'logementnaturejur';
    protected $primaryKey = 'SituationJur';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
    ];
}