<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Accesuser extends Model
{
    protected $table      = 'accesuser';
    protected $primaryKey = 'IDaccesuser';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'IDEncadrement',
        'IDetablissement',
        'Date',
        'heure',
        'iplocal',
        'ippublique',
        'mac1',
        'mac2',
        'utilisateurbdd',
        'Latitude',
        'Longitude',
        'utilisateurprevelege',
        'nompc',
        'Obs',
        'versionexe',
        'nbressai',
        'accesouinon',
        'MotDePass',
        'NomUtilisateur',
        'numseriedisque',
        'partitiondisque',
        'windows',
        'NomPrenom',
        'detailacces',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function encadrement()
    {
        return $this->belongsTo(\Encadrement::class, 'IDEncadrement', 'IDEncadrement');
    }

    public function etablissement()
    {
        return $this->belongsTo(\Etablissement::class, 'IDetablissement', 'IDetablissement');
    }
}