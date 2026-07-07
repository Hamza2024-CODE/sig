<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EtablissementEtat extends Model
{
    protected $table      = 'etablissement_etat';
    protected $primaryKey = 'EtatActual';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
        'NumOrd',
        'IDEtablissement_Enservice',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function etablissementEnservice()
    {
        return $this->belongsTo(\EtablissementEnservice::class, 'IDEtablissement_Enservice', 'IDEtablissement_Enservice');
    }
}