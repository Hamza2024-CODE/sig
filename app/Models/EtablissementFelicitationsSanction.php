<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EtablissementFelicitationsSanction extends Model
{
    protected $table      = 'etablissement_felicitations_sanction';
    protected $primaryKey = 'IDEtablissement_Sanction';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'IDFelicitations_SanctionType',
        'IDetablissement',
        'Num',
        'Motif',
        'Date',
        'affiche',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function felicitationsSanctionType()
    {
        return $this->belongsTo(\FelicitationsSanctionType::class, 'IDFelicitations_SanctionType', 'IDFelicitations_SanctionType');
    }

    public function etablissement()
    {
        return $this->belongsTo(\Etablissement::class, 'IDetablissement', 'IDetablissement');
    }
}