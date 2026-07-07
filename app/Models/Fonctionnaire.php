<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Fonctionnaire extends Model
{
    protected $table      = 'fonctionnaire';
    protected $primaryKey = 'id';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'CODE_FONCTR',
        'NOM_FNCTR',
        'NOM_FR_FNCTR',
        'PRN_FNCTR',
        'PRN_FR_FNCTR',
        'DAT_Nais_FNCTR',
        'LIEU_NAIS_FNCTR',
        'Sexe_FC',
        'Adresse_FC',
        'Adresse_fr',
        'TeL_FC',
        'CODE_FONC',
        'LieuNaisFr',
        'CodeNinSco',
        'CodeGrd',
        'NumCompt',
        'CleCompte',
        'CODE_TPCMPT',
        'CodesitAdm',
        'echl',
        'nNss',
        'dDateRecr',
        'dDateconf',
        'NumOrd',
        'NNumCompt',
        'CODE_ETS',
        'IDEts_Financier',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function etsFinancier()
    {
        return $this->belongsTo(\EtsFinancier::class, 'IDEts_Financier', 'IDEts_Financier');
    }
}