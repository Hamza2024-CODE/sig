<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EtsScolaire extends Model
{
    protected $table      = 'ets_scolaire';
    protected $primaryKey = 'IDEts_Scolaire';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'IDCommunn',
        'IDNature_EtsS',
        'NomEtab',
        'NomFr',
        'Tel',
        'CodeEtab',
        'adresse',
        'Codecommune',
        'cycle',
        'Codewilaya',
        'publprivesout',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function communn()
    {
        return $this->belongsTo(\Communn::class, 'IDCommunn', 'IDCommunn');
    }

    public function natureEtsS()
    {
        return $this->belongsTo(\NatureEtsS::class, 'IDNature_EtsS', 'IDNature_EtsS');
    }
}