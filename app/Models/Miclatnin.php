<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Miclatnin extends Model
{
    protected $table      = 'miclatnin';
    protected $primaryKey = 'IDmiclat';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'nin',
        'codecomm',
        'acteN',
        'car',
        'annee',
        'nom_a',
        'pren_a',
        'd_nais',
        'h_nais',
        'lieu_nais',
        'pren_pere',
        'nom_mere',
        'pren_mere',
        'sexe',
        'nom_f',
        'pren_f',
        'presume',
        'isMarried',
        'isDied',
        'epoux',
        'epouses',
    ];
}