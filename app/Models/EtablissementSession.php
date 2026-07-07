<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EtablissementSession extends Model
{
    protected $table      = 'etablissement_session';
    protected $primaryKey = 'IDetablissement_Session';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'IDSession',
        'IDetablissement',
        'Nbroffre',
        'DansoffreNouv',
        'nbrPlace',
        'nbrinscri',
        'NbrInscrif',
        'Nbrinco',
        'nbrincof',
        'nbrinscrietr',
        'nbrincoetr',
        'nbrinsctiendi',
        'nbrincoendi',
        'nbrspecial',
        'NbrPreinsc',
        'Obs',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function session()
    {
        return $this->belongsTo(\Session::class, 'IDSession', 'IDSession');
    }

    public function etablissement()
    {
        return $this->belongsTo(\Etablissement::class, 'IDetablissement', 'IDetablissement');
    }
}