<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Localtype extends Model
{
    protected $table      = 'localtype';
    protected $primaryKey = 'IDTypeLocal';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
    ];
}