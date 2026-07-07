<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Candidat extends Model
{
    protected $table      = 'candidat';
    protected $primaryKey = 'IDCandidat';
    public    $timestamps = false;

    protected $fillable = [
        'Nom',
        'Prenom',
        'NomFr',
        'PrenomFr',
        'Nin',
        'NumIns',
        'dateInscr',
        'IDOffre',
        'IDWilayaR',
        'sexe',
    ];

    protected $casts = [
        'Nin' => \App\Casts\EncryptedNin::class,
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function offre()
    {
        return $this->belongsTo(Offre::class, 'IDOffre', 'IDOffre');
    }

    public function apprenant()
    {
        return $this->hasOne(Apprenant::class, 'IDCandidat', 'IDCandidat');
    }

    public function wilaya()
    {
        return $this->belongsTo(Wilaya::class, 'IDWilayaR', 'IDWilayaa');
    }

    // ── Accessors ─────────────────────────────────────────────────────────────

    public function getNomCompletAttribute(): string
    {
        return trim(($this->Nom ?? '') . ' ' . ($this->Prenom ?? ''));
    }

    public function getNomCompletFrAttribute(): string
    {
        return trim(($this->NomFr ?? '') . ' ' . ($this->PrenomFr ?? ''));
    }
}
