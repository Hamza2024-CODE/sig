<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Operation extends Model
{
    protected $table      = 'operations';
    protected $primaryKey = 'IDOperations';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
        'APINI',
        'Num',
        'DateLancementP',
        'DateAchevementP',
        'APACT',
        'DateInscription',
        'APFINAL',
        'TauxPhysique',
        'Tauxfin',
        'MantEngagment',
        'Mantpayement',
        'DateLancementR',
        'DateAchevementR',
        'Obs',
        'IDOperationEtat',
        'IDOperationDecition',
        'IDDFEP',
        'MontantRevaluation',
        'Validation',
        'ValidationMin',
        'IDSous_Programme',
        'IDSousCategorie',
        'IDActions_titre',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function operationEtat()
    {
        return $this->belongsTo(\OperationEtat::class, 'IDOperationEtat', 'IDOperationEtat');
    }

    public function operationDecition()
    {
        return $this->belongsTo(\OperationDecition::class, 'IDOperationDecition', 'IDOperationDecition');
    }

    public function dFEP()
    {
        return $this->belongsTo(\DFEP::class, 'IDDFEP', 'IDDFEP');
    }

    public function sousProgramme()
    {
        return $this->belongsTo(\SousProgramme::class, 'IDSous_Programme', 'IDSous_Programme');
    }

    public function sousCategorie()
    {
        return $this->belongsTo(\SousCategorie::class, 'IDSousCategorie', 'IDSousCategorie');
    }

    public function actionsTitre()
    {
        return $this->belongsTo(\ActionsTitre::class, 'IDActions_titre', 'IDActions_titre');
    }
}