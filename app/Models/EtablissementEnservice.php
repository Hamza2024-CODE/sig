<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EtablissementEnservice extends Model
{
    protected $table      = 'etablissement_enservice';
    protected $primaryKey = 'IDEtablissement_Enservice';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
    ];
}