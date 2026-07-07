<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Activitetype extends Model
{
    protected $table      = 'activitetype';
    protected $primaryKey = 'IDActiviteType';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
    ];
}