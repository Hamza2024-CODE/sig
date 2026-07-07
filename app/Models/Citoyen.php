<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Citoyen extends Model
{
    protected $table      = 'citoyen';
    protected $primaryKey = 'IDcitoyen';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'NOM',
        'PNOM',
        'NOMA',
        'PNOMA',
        'D_N',
        'CODE_GEO',
        'PPERE',
        'NMERE',
        'PMERE',
        'ADRESSE',
        'ETABLISSEMENT',
        'DIPLOME',
        'SPECIALITE',
        'NIVEAU',
        'W_NAIS',
    ];
}