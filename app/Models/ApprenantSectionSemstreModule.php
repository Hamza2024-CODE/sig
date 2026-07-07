<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApprenantSectionSemstreModule extends Model
{
    protected $table      = 'apprenant_section_semstre_module';
    protected $primaryKey = 'IDApprenant_Section_semstre_module';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'NoteC1',
        'NoteC2',
        'NoteCs',
        'NoteR',
        'MoyAvr',
        'MoyApr',
        'IDapprenant_Section_semstre',
        'IDsection_semestre_Module',
        'IDModule',
        'Obs',
        'IDDecision_Eval_Mdl',
        'MoyAvr_coef',
        'MoyApr_coef',
        'absc1',
        'absc2',
        'abscs',
        'abscr',
        'IDDecision_Eval_MdlAvr',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function apprenantSectionSemstre()
    {
        return $this->belongsTo(\ApprenantSectionSemstre::class, 'IDapprenant_Section_semstre', 'IDapprenant_Section_semstre');
    }

    public function sectionSemestreModule()
    {
        return $this->belongsTo(\SectionSemestreModule::class, 'IDsection_semestre_Module', 'IDsection_semestre_Module');
    }

    public function module()
    {
        return $this->belongsTo(\Module::class, 'IDModule', 'IDModule');
    }

    public function decisionEvalMdl()
    {
        return $this->belongsTo(\DecisionEvalMdl::class, 'IDDecision_Eval_Mdl', 'IDDecision_Eval_Mdl');
    }

    public function decisionEvalMdlAvr()
    {
        return $this->belongsTo(\DecisionEvalMdlAvr::class, 'IDDecision_Eval_MdlAvr', 'IDDecision_Eval_MdlAvr');
    }
}