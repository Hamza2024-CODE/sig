<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaitreApprenti extends Model
{
    protected $table      = 'maitre_apprenti';
    protected $primaryKey = 'IDMaitre_Apprenti';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'IDBranche',
        'IDEmployeur',
        'Nom',
        'NomFr',
        'DateNais',
        'AdresFr',
        'Adres',
        'Obs',
        'Sp´┐¢cialite',
        'faitstag',
        'Datedeb',
        'DateFIn',
        'Fonctionn',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function branche()
    {
        return $this->belongsTo(\Branche::class, 'IDBranche', 'IDBranche');
    }

    public function employeur()
    {
        return $this->belongsTo(\Employeur::class, 'IDEmployeur', 'IDEmployeur');
    }
}