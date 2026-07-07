<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tache extends Model
{
    protected $table      = 'tache';
    protected $primaryKey = 'IDTache';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'IDDossier',
        'DateD',
        'DateF',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function dossier()
    {
        return $this->belongsTo(\Dossier::class, 'IDDossier', 'IDDossier');
    }
}