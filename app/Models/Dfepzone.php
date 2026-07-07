<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Dfepzone extends Model
{
    protected $table      = 'dfepzone';
    protected $primaryKey = 'IDDfepzone';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
    ];
}