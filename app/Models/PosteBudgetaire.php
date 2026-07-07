<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PosteBudgetaire extends Model
{
    protected $table      = 'poste_budgetaire';
    protected $primaryKey = 'IDPoste_Budgetaire';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'IDGrade',
        'IDEtat',
        'IDMode_Promotion',
        'IDMode_Recrutement',
        'dateAllo',
        'IDFonctions',
        'IDannee',
        'IDEtablissement_Fonctions',
        'IDetablissement_Grade',
        'Indice',
        'indiceinf',
        'indicemoy',
        'indicesup',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function grade()
    {
        return $this->belongsTo(\Grade::class, 'IDGrade', 'IDGrade');
    }

    public function etat()
    {
        return $this->belongsTo(\Etat::class, 'IDEtat', 'IDEtat');
    }

    public function modePromotion()
    {
        return $this->belongsTo(\ModePromotion::class, 'IDMode_Promotion', 'IDMode_Promotion');
    }

    public function modeRecrutement()
    {
        return $this->belongsTo(\ModeRecrutement::class, 'IDMode_Recrutement', 'IDMode_Recrutement');
    }

    public function fonction()
    {
        return $this->belongsTo(\Fonction::class, 'IDFonctions', 'IDFonctions');
    }

    public function annee()
    {
        return $this->belongsTo(\Annee::class, 'IDannee', 'IDannee');
    }

    public function etablissementFonction()
    {
        return $this->belongsTo(\EtablissementFonction::class, 'IDEtablissement_Fonctions', 'IDEtablissement_Fonctions');
    }

    public function etablissementGrade()
    {
        return $this->belongsTo(\EtablissementGrade::class, 'IDetablissement_Grade', 'IDetablissement_Grade');
    }
}