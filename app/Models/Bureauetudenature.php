<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bureauetudenature extends Model
{
    protected $table      = 'bureauetudenature';
    protected $primaryKey = 'IDBureauEtudeNature';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
    ];
}