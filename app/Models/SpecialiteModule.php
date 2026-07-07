<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SpecialiteModule extends Model
{
    protected $table      = 'specialite_module';
    protected $primaryKey = 'IDSpecialite_Module';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Coef',
        'Ne',
        'NbrH',
        'NumSem',
        'IDSpecialite',
        'IDUniteModulaire',
        'Nom',
        'NomFr',
        'code',
        'IDspecialite_moduleType',
        'comportement_attendu',
        'Conditions_de_realisation_a_partir_de',
        'Conditions_de_realisation_a_laide_de',
        'Criteres_generaux_de_performance',
        'IDSpecialite_Programme',
        'comportement_attenduar',
        'conditions_de_realisation_a_partir_dear',
        'conditions_de_realisation_a_laide_dear',
        'criteres_generaux_de_performancear',
        'NumOrd',
        'Obs',
        'IDModule_ModeEnseigne',
        'codear',
        'NomEn',
        'comportement_attenduen',
        'conditions_de_realisation_a_partir_deen',
        'conditions_de_realisation_a_laide_deen',
        'criteres_generaux_de_performanceen',
        'NbrSem',
        'IDSpecialite_endroit',
        'NbrHTheo',
        'NbrHPrat',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function specialite()
    {
        return $this->belongsTo(\Specialite::class, 'IDSpecialite', 'IDSpecialite');
    }

    public function uniteModulaire()
    {
        return $this->belongsTo(\UniteModulaire::class, 'IDUniteModulaire', 'IDUniteModulaire');
    }

    public function specialiteModuleType()
    {
        return $this->belongsTo(\SpecialiteModuleType::class, 'IDspecialite_moduleType', 'IDspecialite_moduleType');
    }

    public function specialiteProgramme()
    {
        return $this->belongsTo(\SpecialiteProgramme::class, 'IDSpecialite_Programme', 'IDSpecialite_Programme');
    }

    public function moduleModeEnseigne()
    {
        return $this->belongsTo(\ModuleModeEnseigne::class, 'IDModule_ModeEnseigne', 'IDModule_ModeEnseigne');
    }

    public function specialiteEndroit()
    {
        return $this->belongsTo(\SpecialiteEndroit::class, 'IDSpecialite_endroit', 'IDSpecialite_endroit');
    }
}