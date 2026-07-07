<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EtsForm extends Model
{
    protected $table      = 'ets_form';
    protected $primaryKey = 'IDEts_Form';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'IDDFEP',
        'Nom',
        'nomFr',
        'IDMihnati',
        'code',
        'CodeDecret',
        'CodeMihnati',
        'IDNature_etsF',
        'ip_Publique',
        'ip_local_serv',
        'IDCommunn',
        'Encour',
        'PublPrive',
        'activee',
        'TYPE_EPA',
        'Obs',
        'IDetablissement',
        'Annex',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function dFEP()
    {
        return $this->belongsTo(\DFEP::class, 'IDDFEP', 'IDDFEP');
    }

    public function mihnati()
    {
        return $this->belongsTo(\Mihnati::class, 'IDMihnati', 'IDMihnati');
    }

    public function natureEtsF()
    {
        return $this->belongsTo(\NatureEtsF::class, 'IDNature_etsF', 'IDNature_etsF');
    }

    public function communn()
    {
        return $this->belongsTo(\Communn::class, 'IDCommunn', 'IDCommunn');
    }

    public function etablissement()
    {
        return $this->belongsTo(\Etablissement::class, 'IDetablissement', 'IDetablissement');
    }
}