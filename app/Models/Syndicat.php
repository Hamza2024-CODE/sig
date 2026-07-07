<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Syndicat extends Model
{
    protected $table      = 'syndicat';
    protected $primaryKey = 'IDsyndicat';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
    ];
}