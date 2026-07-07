<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PartieTache extends Model
{
    protected $table      = 'partie_tache';
    protected $primaryKey = 'IDPartie_tache';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
        'NumOrd',
    ];
}