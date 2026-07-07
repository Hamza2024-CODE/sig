<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Statuset extends Model
{
    protected $table      = 'statusets';
    protected $primaryKey = 'IDStatusEts';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
    ];
}