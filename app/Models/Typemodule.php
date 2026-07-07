<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Typemodule extends Model
{
    protected $table      = 'typemodule';
    protected $primaryKey = 'IDTypeModule';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
    ];
}