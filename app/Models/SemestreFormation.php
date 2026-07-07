<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SemestreFormation extends Model
{
    protected $table      = 'semestre_formation';
    protected $primaryKey = 'IDSemestre_formation';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'IDAnnee_Formation',
        'Encour',
        'Nom',
        'NomFr',
        'DateD',
        'DateF',
        'NumOrd',
        'CodePanne',
        'CodePsem',
        'NumOrdPanne',
        'Clouture',
        'NomPmois',
        'NomPmoisFr',
        'IDPeriodeencour',
        'Fermee',
        'datedebexamin',
        'datefinexamin',
        'datedebrat',
        'datefinrat',
        'dateresultat',
        'dateresultatrat',
        'DateFPedag',
        'ajoutesection',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function anneeFormation()
    {
        return $this->belongsTo(\AnneeFormation::class, 'IDAnnee_Formation', 'IDAnnee_Formation');
    }

    public function periodeencour()
    {
        return $this->belongsTo(\Periodeencour::class, 'IDPeriodeencour', 'IDPeriodeencour');
    }
}