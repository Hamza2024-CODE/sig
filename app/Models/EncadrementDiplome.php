<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EncadrementDiplome extends Model
{
    protected $table      = 'encadrement_diplome';
    protected $primaryKey = 'IDEncadrement_Diplome';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'DateD',
        'DateF',
        'IDDiplome',
        'Duree',
        'EtablissemntFormarion',
        'NumDiplome',
        'DateDiplome',
        'IDEncadrement',
        'NomSpecialite',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function diplome()
    {
        return $this->belongsTo(\Diplome::class, 'IDDiplome', 'IDDiplome');
    }

    public function encadrement()
    {
        return $this->belongsTo(\Encadrement::class, 'IDEncadrement', 'IDEncadrement');
    }
}