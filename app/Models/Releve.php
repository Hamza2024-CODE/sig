<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Releve extends Model
{
    protected $table      = 'releve';
    protected $primaryKey = 'IDReleve';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Codereleve',
        'Numreleve',
        'Datedeb',
        'DateFIn',
        'MontanReleve',
        'Obs',
        'Sujet',
        'Sujetfr',
        'datereleve',
        'Duree',
        'Dureejour',
        'rejet',
        'rejetdate',
        'rejetmotif',
        'IDActions_titre_souscategorie',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function actionsTitreSouscategorie()
    {
        return $this->belongsTo(\ActionsTitreSouscategorie::class, 'IDActions_titre_souscategorie', 'IDActions_titre_souscategorie');
    }
}