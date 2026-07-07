<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Etablissement extends Model
{
    protected $table      = 'etablissement';
    protected $primaryKey = 'IDetablissement';
    public    $timestamps = false;

    protected $fillable = [
        'Code',
        'Nom',
        'NomFr',
        'IDDFEP',
        'IDNature_etsF',
        'Adresse',
        'Tel',
        'Email',
        'nomUser',
        'MotDePass',
        'DateDecret',
        'google2fa_secret',
        'mfa_enabled',
        'mfa_enabled_at',
        'smtpmotdepass_enc',   // Mot de passe SMTP chiffré — ISO 27001 A.8.24
        'ip_local_serv_enc',   // IP serveur local chiffré — ISO 27001 A.8.3
    ];

    protected $hidden = [
        'MotDePass',
        'google2fa_secret',
        'smtpmotdepass',        // Legacy — ne pas exposer
        'smtpmotdepass_enc',    // Version chiffrée — ne pas sérialiser
        'ip_local_serv',        // Legacy — ne pas exposer
        'ip_local_serv_enc',    // Version chiffrée — ne pas sérialiser
    ];

    protected $casts = [
        'google2fa_secret'  => 'encrypted',
        'mfa_enabled'       => 'boolean',
        'mfa_enabled_at'    => 'datetime',
        'smtpmotdepass_enc' => 'encrypted',   // AES-256-CBC via APP_KEY
        'ip_local_serv_enc' => 'encrypted',   // AES-256-CBC via APP_KEY
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    /**
     * Retourne le mot de passe SMTP déchiffré.
     * Utilise la colonne chiffrée si disponible, sinon fallback sur l'ancienne.
     */
    public function getSmtpPasswordAttribute(): ?string
    {
        if (!empty($this->attributes['smtpmotdepass_enc'])) {
            try {
                return decrypt($this->attributes['smtpmotdepass_enc']);
            } catch (\Exception $e) {
                \Log::error('[SECURITY] Failed to decrypt SMTP password for ets#' . $this->getKey());
            }
        }
        // Fallback : ancienne colonne en clair (pendant la période de migration)
        return $this->attributes['smtpmotdepass'] ?? null;
    }

    /**
     * Retourne l'IP du serveur local déchiffrée.
     */
    public function getServerIpAttribute(): ?string
    {
        if (!empty($this->attributes['ip_local_serv_enc'])) {
            try {
                return decrypt($this->attributes['ip_local_serv_enc']);
            } catch (\Exception $e) {}
        }
        return $this->attributes['ip_local_serv'] ?? null;
    }

    public function dfep()
    {
        return $this->belongsTo(Dfep::class, 'IDDFEP', 'IDDFEP');
    }

    public function offres()
    {
        return $this->hasMany(Offre::class, 'IDEts_Form', 'IDetablissement');
    }

    public function encadrements()
    {
        return $this->hasMany(Encadrement::class, 'IDetablissement', 'IDetablissement');
    }

    // ── Scopes ───────────────────────────────────────────────────────────────

    public function scopeByWilaya($query, int $wilayaId)
    {
        return $query->where('IDDFEP', $wilayaId);
    }
}
