<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Fonctionsnature extends Model
{
    protected $table      = 'fonctionsnature';
    protected $primaryKey = 'IDFonctionsNature';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
    ];
}