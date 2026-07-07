<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Action extends Model
{
    protected $table      = 'actions';
    protected $primaryKey = 'IDActions';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
        'code',
        'IDSous_Programme',
        'NumOrd',
        'CodeComplet',
        'IDActionType',
        'IDDFEP',
        'IDetablissement',
        'Obs',
        'desactive',
        'IDAction',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function sousProgramme()
    {
        return $this->belongsTo(\SousProgramme::class, 'IDSous_Programme', 'IDSous_Programme');
    }

    public function actionType()
    {
        return $this->belongsTo(\ActionType::class, 'IDActionType', 'IDActionType');
    }

    public function dFEP()
    {
        return $this->belongsTo(\DFEP::class, 'IDDFEP', 'IDDFEP');
    }

    public function etablissement()
    {
        return $this->belongsTo(\Etablissement::class, 'IDetablissement', 'IDetablissement');
    }

    public function action()
    {
        return $this->belongsTo(\Action::class, 'IDAction', 'IDAction');
    }
}