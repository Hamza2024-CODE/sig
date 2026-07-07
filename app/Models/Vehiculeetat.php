<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vehiculeetat extends Model
{
    protected $table      = 'vehiculeetat';
    protected $primaryKey = 'Etatv';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
    ];
}