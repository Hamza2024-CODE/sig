<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Competance extends Model
{
    protected $table      = 'competance';
    protected $primaryKey = 'IDCompetance';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'etablissemnt',
        'nomprn',
        'DateNais',
        'IDDFEP',
        'IDEtablissement',
        'dateinsctal',
        'dateconf',
        'liuenais',
        'grade',
        'Obs',
        'sitfam',
        'experiance',
        'perfection',
        'Tel',
        'Wilaya',
        'diplome',
        'specialite',
        'sp´┐¢cialiteEns',
        'IDGrade',
        'Presume',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function dFEP()
    {
        return $this->belongsTo(\DFEP::class, 'IDDFEP', 'IDDFEP');
    }

    public function etablissement()
    {
        return $this->belongsTo(\Etablissement::class, 'IDEtablissement', 'IDEtablissement');
    }

    public function grade()
    {
        return $this->belongsTo(\Grade::class, 'IDGrade', 'IDGrade');
    }
}