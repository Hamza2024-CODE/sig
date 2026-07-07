<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OffreSpecialite extends Model
{
    protected $table      = 'offre_specialite';
    protected $primaryKey = 'IDOffre_Specialite';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'IDBranche',
        'IDNiveau_Fp',
        'IDqualification_dplm',
        'Nom',
        'NomFr',
        'NbrSem',
        'IDNiveau_Scol',
        'NbrAnne',
        'CodeSpec',
        'IDNomenclature',
        'dureeM',
        'IDNomenclature_Mode',
        'DureeH',
        'activee',
        'conditionsArabe',
        'conditionsLatin',
        'Obs',
        'IDSpecialite',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function branche()
    {
        return $this->belongsTo(\Branche::class, 'IDBranche', 'IDBranche');
    }

    public function niveauFp()
    {
        return $this->belongsTo(\NiveauFp::class, 'IDNiveau_Fp', 'IDNiveau_Fp');
    }

    public function qualificationDplm()
    {
        return $this->belongsTo(\QualificationDplm::class, 'IDqualification_dplm', 'IDqualification_dplm');
    }

    public function niveauScol()
    {
        return $this->belongsTo(\NiveauScol::class, 'IDNiveau_Scol', 'IDNiveau_Scol');
    }

    public function nomenclature()
    {
        return $this->belongsTo(\Nomenclature::class, 'IDNomenclature', 'IDNomenclature');
    }

    public function nomenclatureMode()
    {
        return $this->belongsTo(\NomenclatureMode::class, 'IDNomenclature_Mode', 'IDNomenclature_Mode');
    }

    public function specialite()
    {
        return $this->belongsTo(\Specialite::class, 'IDSpecialite', 'IDSpecialite');
    }
}