<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Apprenantcna extends Model
{
    protected $table      = 'apprenantcnas';
    protected $primaryKey = 'IDapprenantcnas';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'nom',
        'pnom',
        'noma',
        'pnoma',
        'datenais',
        'CODE_GEO',
        'ppere',
        'nmere',
        'pmere',
        'Adresse',
        'etablissment',
        'diplome',
        'specialite',
        'niveau',
        'wnais',
        'nin',
        'nss',
        'nssetablissement',
    ];

    protected $casts = [
        'nin' => \App\Casts\EncryptedNin::class,
    ];
}