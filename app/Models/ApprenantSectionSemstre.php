<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApprenantSectionSemstre extends Model
{
    protected $table      = 'apprenant_section_semstre';
    protected $primaryKey = 'IDapprenant_Section_semstre';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'IDSection_Semestre',
        'MoyApr',
        'MoyAvr',
        'IDapprenant',
        'MoyC1',
        'MoyC2',
        'MoyCs',
        'MoyCr',
        'Obs',
        'IDDecision_evals',
        'NoteStage',
        'IDapprenant_Regime',
        'IDmention',
        'CleComp',
        'T1Moy',
        'T2Moy',
        'T3Moy',
        'TotalMoyAvr',
        'TotalMoyApr',
        'NoteMemoire',
        'NoteSoutenance',
        'DateAbdech',
        'IDDecision_evalsAvr',
        'DateTrensfert',
        'Trensfert',
        'MoyMdlTheo',
        'MoyMdlPrat',
        'MoyC',
        'TotalMoyc1',
        'TotalMoyc2',
        'TotalMoycs',
        'TotalMoyc',
        'MoyMdlTheoapr',
        'MoyMdlPratapr',
        'create_time',
        'update_time',
        'data_sync_time',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function sectionSemestre()
    {
        return $this->belongsTo(\SectionSemestre::class, 'IDSection_Semestre', 'IDSection_Semestre');
    }

    public function apprenant()
    {
        return $this->belongsTo(\Apprenant::class, 'IDapprenant', 'IDapprenant');
    }

    public function decisionEval()
    {
        return $this->belongsTo(\DecisionEval::class, 'IDDecision_evals', 'IDDecision_evals');
    }

    public function apprenantRegime()
    {
        return $this->belongsTo(\ApprenantRegime::class, 'IDapprenant_Regime', 'IDapprenant_Regime');
    }

    public function mention()
    {
        return $this->belongsTo(\Mention::class, 'IDmention', 'IDmention');
    }

    public function decisionEvalsAvr()
    {
        return $this->belongsTo(\DecisionEvalsAvr::class, 'IDDecision_evalsAvr', 'IDDecision_evalsAvr');
    }
}