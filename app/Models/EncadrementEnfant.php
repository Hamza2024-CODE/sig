<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EncadrementEnfant extends Model
{
    protected $table      = 'encadrement_enfant';
    protected $primaryKey = 'IDEncadrement_enfant';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'IDEncadrement',
        'Nom',
        'Prenom',
        'DateNais',
        'Presume',
        'Civ',
        'IDNiveau_Scol_enca',
        'lieuNaiss',
        'IDSitfamille',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function encadrement()
    {
        return $this->belongsTo(\Encadrement::class, 'IDEncadrement', 'IDEncadrement');
    }

    public function niveauScolEnca()
    {
        return $this->belongsTo(\NiveauScolEnca::class, 'IDNiveau_Scol_enca', 'IDNiveau_Scol_enca');
    }

    public function sitfamille()
    {
        return $this->belongsTo(\Sitfamille::class, 'IDSitfamille', 'IDSitfamille');
    }
}