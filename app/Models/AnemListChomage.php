<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnemListChomage extends Model
{
    protected $table      = 'anem_list_chomage';
    protected $primaryKey = 'id';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'NumeroWassit',
        'NIN',
        'NomFr',
        'PrenomFr',
        'DateNaissance',
        'Etablissement',
        'CodeEtablissement',
        'wilaya',
        'codewilaya',
    ];

    protected $casts = [
        'NIN' => \App\Casts\EncryptedNin::class,
    ];
}