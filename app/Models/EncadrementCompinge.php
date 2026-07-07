<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EncadrementCompinge extends Model
{
    protected $table      = 'encadrement_compinge';
    protected $primaryKey = 'id';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'IDcompinge',
        'IDEncadrement',
        'IDCompingePeriode',
        'Obs',
        'Decision',
        'salleChembre',
        'NumSalleChambre',
        'etage',
        'NbrFamille',
        'DureePri',
        'IDCompingeDfep',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function compinge()
    {
        return $this->belongsTo(\Compinge::class, 'IDcompinge', 'IDcompinge');
    }

    public function encadrement()
    {
        return $this->belongsTo(\Encadrement::class, 'IDEncadrement', 'IDEncadrement');
    }

    public function compingePeriode()
    {
        return $this->belongsTo(\CompingePeriode::class, 'IDCompingePeriode', 'IDCompingePeriode');
    }

    public function compingeDfep()
    {
        return $this->belongsTo(\CompingeDfep::class, 'IDCompingeDfep', 'IDCompingeDfep');
    }
}