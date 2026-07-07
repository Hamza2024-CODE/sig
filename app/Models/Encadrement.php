<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Casts\EncryptedNin;
use App\Casts\EncryptedDateNais;

class Encadrement extends Model
{
    protected $table      = 'encadrement';
    protected $primaryKey = 'IDEncadrement';
    public    $timestamps = false;

    protected $fillable = [
        'Nom',
        'Prenom',
        'nin',
        'DateNais',
        'Email',
        'MotDePass',
        'IDetablissement',
        'Grade',
        'Fonction',
        'create_time',
        'google2fa_secret',
        'mfa_enabled',
        'mfa_enabled_at'
    ];

    protected $hidden = ['MotDePass', 'google2fa_secret'];

    protected $casts = [
        'nin'              => EncryptedNin::class,      // Chiffrement AES-256 — RGPD Art.9 | ISO 27001 A.8.11
        'DateNais'         => EncryptedDateNais::class, // Chiffrement AES-256 — RGPD Art.9 | ISO 27001 A.8.11
        'google2fa_secret' => 'encrypted',
        'mfa_enabled'      => 'boolean',
        'mfa_enabled_at'   => 'datetime',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function etablissement()
    {
        return $this->belongsTo(Etablissement::class, 'IDetablissement', 'IDetablissement');
    }

    public function modulesEnseignes()
    {
        return $this->hasMany(SectionSemestreModule::class, 'IDEncadrement', 'IDEncadrement');
    }

    // ── Accessors ─────────────────────────────────────────────────────────────

    public function getNomCompletAttribute(): string
    {
        return trim(($this->Nom ?? '') . ' ' . ($this->Prenom ?? ''));
    }
}
