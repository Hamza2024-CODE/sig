<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EtablissementSemestreFormation extends Model
{
    protected $table      = 'etablissement_semestre_formation';
    protected $primaryKey = 'IDEtablissement_semestre_formation';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'nbrsection',
        'IDetablissement',
        'IDSemestre_formation',
        'nbrspecialt',
        'NbrAppren',
        'NbrApprenf',
        'NbrApprenend',
        'NbrApprenetr',
        'Nbrapprenadm',
        'NbrapprenNomAdmi',
        'Nbrapprendemi',
        'Nbrapprenint',
        'NbrEquipement',
        'NbrEquipementfonc',
        'NbrEquipementnonFonc',
        'NbrEmployeur',
        'Obs',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function etablissement()
    {
        return $this->belongsTo(\Etablissement::class, 'IDetablissement', 'IDetablissement');
    }

    public function semestreFormation()
    {
        return $this->belongsTo(\SemestreFormation::class, 'IDSemestre_formation', 'IDSemestre_formation');
    }
}