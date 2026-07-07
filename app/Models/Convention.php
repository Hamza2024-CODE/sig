<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Convention extends Model
{
    protected $table      = 'convention';
    protected $primaryKey = 'IDConvention';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Sujet',
        'IDetablissement',
        'Num',
        'DateDebut',
        'DateFIn',
        'IDconventionType',
        'institution_contractante',
        'IDEmployeur',
        'representant_Etablissement',
        'repr´┐¢sentant_institution',
        'institution_contractante1',
        'repr´┐¢sentant_institution1',
        'ref´┐¢rence_juridique',
        'Avant_propos',
        'IDConventionEtat',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function etablissement()
    {
        return $this->belongsTo(\Etablissement::class, 'IDetablissement', 'IDetablissement');
    }

    public function conventionType()
    {
        return $this->belongsTo(\ConventionType::class, 'IDconventionType', 'IDconventionType');
    }

    public function employeur()
    {
        return $this->belongsTo(\Employeur::class, 'IDEmployeur', 'IDEmployeur');
    }

    public function conventionEtat()
    {
        return $this->belongsTo(\ConventionEtat::class, 'IDConventionEtat', 'IDConventionEtat');
    }
}