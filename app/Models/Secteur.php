<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Secteur extends Model
{
    protected $table      = 'secteurs';
    protected $primaryKey = 'IDSecteurs';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
    ];
}