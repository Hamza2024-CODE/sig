<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EtablissementCreattransf extends Model
{
    protected $table      = 'etablissement_creattransf';
    protected $primaryKey = 'IDetablissement_CreatTransf';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
    ];
}