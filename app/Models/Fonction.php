<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Fonction extends Model
{
    protected $table      = 'fonctions';
    protected $primaryKey = 'IDFonctions';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
        'Abr',
        'AbrFr',
        'Activee',
        'posteSup',
        'IDFonctionsNature',
        'NumOrd',
        'IDfonctiontype',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function fonctionsNature()
    {
        return $this->belongsTo(\FonctionsNature::class, 'IDFonctionsNature', 'IDFonctionsNature');
    }

    public function fonctiontype()
    {
        return $this->belongsTo(\Fonctiontype::class, 'IDfonctiontype', 'IDfonctiontype');
    }
}