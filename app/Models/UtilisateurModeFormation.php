<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UtilisateurModeFormation extends Model
{
    protected $table      = 'utilisateur_mode_formation';
    protected $primaryKey = 'IDUtilisateur_Mode_formation';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'IDMode_formation',
        'IDUtilisateur',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function modeFormation()
    {
        return $this->belongsTo(\ModeFormation::class, 'IDMode_formation', 'IDMode_formation');
    }

    public function utilisateur()
    {
        return $this->belongsTo(\Utilisateur::class, 'IDUtilisateur', 'IDUtilisateur');
    }
}