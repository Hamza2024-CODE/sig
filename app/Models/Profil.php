<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Profil extends Model
{
    protected $table      = 'profil';
    protected $primaryKey = 'IDProfil';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Code',
        'NomUser',
        'MotPass',
        'NomAr',
        'NomFr',
        'activee',
        'IDUtilisateur',
        'DateNais',
        'LieuNais',
        'IDetablissement',
        'IDEncadrement',
        'Prenom',
        'PrenomFr',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function utilisateur()
    {
        return $this->belongsTo(\Utilisateur::class, 'IDUtilisateur', 'IDUtilisateur');
    }

    public function etablissement()
    {
        return $this->belongsTo(\Etablissement::class, 'IDetablissement', 'IDetablissement');
    }

    public function encadrement()
    {
        return $this->belongsTo(\Encadrement::class, 'IDEncadrement', 'IDEncadrement');
    }
}