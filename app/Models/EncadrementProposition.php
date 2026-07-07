<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EncadrementProposition extends Model
{
    protected $table      = 'encadrement_proposition';
    protected $primaryKey = 'IDEncadrement_Proposition';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'IDEncadrement',
        'IDFonctions',
        'ok',
        'Obs',
        'IDEts_Form',
        'Obs1',
        'IDDFEP',
        'NumOrd',
        'IDEncadrement_ProposiLieu',
        'NatureEts',
        'ObsMfep',
        'convoquer',
        'Dateconvoquer',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function encadrement()
    {
        return $this->belongsTo(\Encadrement::class, 'IDEncadrement', 'IDEncadrement');
    }

    public function fonction()
    {
        return $this->belongsTo(\Fonction::class, 'IDFonctions', 'IDFonctions');
    }

    public function etsForm()
    {
        return $this->belongsTo(\EtsForm::class, 'IDEts_Form', 'IDEts_Form');
    }

    public function dFEP()
    {
        return $this->belongsTo(\DFEP::class, 'IDDFEP', 'IDDFEP');
    }

    public function encadrementProposiLieu()
    {
        return $this->belongsTo(\EncadrementProposiLieu::class, 'IDEncadrement_ProposiLieu', 'IDEncadrement_ProposiLieu');
    }
}