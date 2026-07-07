<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Citoyenec extends Model
{
    protected $table      = 'citoyenec';
    protected $primaryKey = 'IDcitoyen';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'nin',
        'nss',
        'nacte',
        'ncn',
        'IDCandidat',
        'Nom',
        'Prenom',
        'NomFr',
        'prenomFr',
        'DateNais',
        'Civ',
        'Presume',
        'NomPere',
        'PrenomPere',
        'NomMere',
        'PrenomMere',
        'NbrFille',
        'ClassFrere',
        'lieuNais',
        'lieunaisFr',
        'communen',
        'wilayanais',
        'communeresi',
        'wilayares',
        'Adres',
        'AdresFr',
        'Tel1',
        'Tel2',
        'mail',
        'titeur',
        'groupesung',
        'titeuradres',
        'titeurfonction',
        'perefonction',
        'merefonction',
        'endicapecas',
        'situfam',
        'situmilit',
        'numwassit',
        'NompereFr',
        'NomMereFr',
        'PrenomMereFr',
        'PrenomPereFr',
        'Obs',
    ];

    protected $casts = [
        'nin' => \App\Casts\EncryptedNin::class,
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function candidat()
    {
        return $this->belongsTo(\Candidat::class, 'IDCandidat', 'IDCandidat');
    }
}