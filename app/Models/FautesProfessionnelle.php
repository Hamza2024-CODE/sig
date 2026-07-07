<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FautesProfessionnelle extends Model
{
    protected $table      = 'fautes_professionnelles';
    protected $primaryKey = 'IDFautes_Professionnelles';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
        'NumOrd',
        'IDfautes_professionnelles_Degre',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function fautesProfessionnellesDegre()
    {
        return $this->belongsTo(\FautesProfessionnellesDegre::class, 'IDfautes_professionnelles_Degre', 'IDfautes_professionnelles_Degre');
    }
}