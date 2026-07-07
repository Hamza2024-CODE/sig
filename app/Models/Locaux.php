<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Locaux extends Model
{
    protected $table      = 'locaux';
    protected $primaryKey = 'IDLocaux';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
        'IDTypeLocal',
        'NomOrd',
        'Sp´┐¢cialiteAr',
        'Sp´┐¢cialiteFr',
        'nbrPlace',
        'nbrTable',
        'NbrChaise',
        'Obs',
        'IDetablissement',
        'tableaffichage',
        'datashow',
        'climatiseur',
        'etage',
        'EtatActual',
        'Validation',
        'ValidationDfp',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function typeLocal()
    {
        return $this->belongsTo(\TypeLocal::class, 'IDTypeLocal', 'IDTypeLocal');
    }

    public function etablissement()
    {
        return $this->belongsTo(\Etablissement::class, 'IDetablissement', 'IDetablissement');
    }
}