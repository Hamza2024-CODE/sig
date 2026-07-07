<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employeur extends Model
{
    protected $table      = 'employeur';
    protected $primaryKey = 'IDEmployeur';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'IDEmployeurType',
        'Nom',
        'NomFr',
        'Adrs',
        'AdrsFr',
        'NRC',
        'NbrEmploy',
        'NIF',
        'NIS',
        'RSS',
        'Tel',
        'Tel2',
        'Fax',
        'Fax2',
        'Obs',
        'IDWilayaa',
        'IDCommunn',
        'ActiviteIni',
        'Validation',
        'ValidationDfp',
        'IDDFEP',
        'IDSecteurs',
        'IDEmployeur_Nature',
        'Latitude',
        'Longitude',
        'TelMobile',
        'TelMobile2',
        'Email',
        'siteweb',
        'IDBranche',
        'NomGerant',
        'PrenomGerant',
        'listeNoire',
        'Nationalite',
        'Nbrconvention',
        'NbrPosteApp',
        'NbrApprenantPlacer',
        'Nbrapprentiencour',
        'NbrMaitreApprenti',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function employeurType()
    {
        return $this->belongsTo(\EmployeurType::class, 'IDEmployeurType', 'IDEmployeurType');
    }

    public function wilayaa()
    {
        return $this->belongsTo(\Wilayaa::class, 'IDWilayaa', 'IDWilayaa');
    }

    public function communn()
    {
        return $this->belongsTo(\Communn::class, 'IDCommunn', 'IDCommunn');
    }

    public function dFEP()
    {
        return $this->belongsTo(\DFEP::class, 'IDDFEP', 'IDDFEP');
    }

    public function secteur()
    {
        return $this->belongsTo(\Secteur::class, 'IDSecteurs', 'IDSecteurs');
    }

    public function employeurNature()
    {
        return $this->belongsTo(\EmployeurNature::class, 'IDEmployeur_Nature', 'IDEmployeur_Nature');
    }

    public function branche()
    {
        return $this->belongsTo(\Branche::class, 'IDBranche', 'IDBranche');
    }
}