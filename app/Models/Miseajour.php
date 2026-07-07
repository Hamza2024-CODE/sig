<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Miseajour extends Model
{
    protected $table      = 'miseajour';
    protected $primaryKey = 'IDMiseAjour';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Date',
        'heure',
        'Nom',
        'Dossier',
        'SousDossier',
        'Version',
        'Cause',
        'dateDernier',
        'tacheMise',
        'Obs',
        'Video',
        'IDDossier',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function dossier()
    {
        return $this->belongsTo(\Dossier::class, 'IDDossier', 'IDDossier');
    }
}