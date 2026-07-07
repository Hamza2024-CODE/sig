<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $table = 'utilisateur';
    protected $primaryKey = 'IDUtilisateur';
    public $timestamps = false;

    protected $fillable = [
        'NomUser',
        'MotPass',
        'Nom',
        'admin',
        'IDNature',
        'google2fa_secret',
        'mfa_enabled',
        'mfa_enabled_at'
    ];

    protected $hidden = [
        'MotPass',
        'google2fa_secret'
    ];

    protected $casts = [
        'google2fa_secret' => 'encrypted',
        'mfa_enabled' => 'boolean',
        'mfa_enabled_at' => 'datetime'
    ];

    /**
     * Maps legacy password field to Laravel auth
     */
    public function getAuthPassword()
    {
        return $this->MotPass;
    }

    /**
     * Relationships
     */
    public function securityLogs()
    {
        return $this->hasMany(SecurityLog::class, 'user_id', 'IDUtilisateur');
    }

    public function trustedDevices()
    {
        return $this->hasMany(TrustedDevice::class, 'user_id', 'IDUtilisateur');
    }

    public function recoveryCodes()
    {
        return $this->hasMany(UserRecoveryCode::class, 'user_id', 'IDUtilisateur');
    }
}