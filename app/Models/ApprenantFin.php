<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApprenantFin extends Model
{
    protected $table      = 'apprenant_fin';
    protected $primaryKey = 'IDApprenant_Fin';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'MoyGen',
        'TMoy',
        'IDmention',
        'Ops',
        'IDapprenant_Section_semstre',
        'IDDecision_evalf',
        'IDSection_Semestre',
        'IDapprenant',
        'NumAttestationPro',
        'DateAttestationPro',
        'Numdiplome',
        'DateDiplome',
        'numSerieDiplome',
        'DateAttestaionLiv',
        'DateDiplomeLiv',
        'NumPvFin',
        'DatePvFin',
        'Retarde',
        'MoyFinForm',
        'Moygenmdltheo',
        'Moygenmdlprat',
        'urlauth',
        'numSerieDiplomeduplica',
        'numdiplomeduplica',
        'DateDiplomeduplica',
        'DateDiplomeduplucaLiv',
        'MotifDiplomedupluca',
        'casduplica',
        'create_time',
        'update_time',
        'data_sync_time',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function mention()
    {
        return $this->belongsTo(\Mention::class, 'IDmention', 'IDmention');
    }

    public function apprenantSectionSemstre()
    {
        return $this->belongsTo(\ApprenantSectionSemstre::class, 'IDapprenant_Section_semstre', 'IDapprenant_Section_semstre');
    }

    public function decisionEvalf()
    {
        return $this->belongsTo(\DecisionEvalf::class, 'IDDecision_evalf', 'IDDecision_evalf');
    }

    public function sectionSemestre()
    {
        return $this->belongsTo(\SectionSemestre::class, 'IDSection_Semestre', 'IDSection_Semestre');
    }

    public function apprenant()
    {
        return $this->belongsTo(\Apprenant::class, 'IDapprenant', 'IDapprenant');
    }
}