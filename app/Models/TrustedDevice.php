<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrustedDevice extends Model
{
    public $timestamps = false;
    protected $table = 'trusted_devices';

    protected $fillable = [
        'user_id',
        'user_type',
        'device_fingerprint',
        'device_name',
        'ip_address',
        'user_agent',
        'last_activity',
        'expires_at'
    ];

    protected $casts = [
        'last_activity' => 'datetime',
        'expires_at' => 'datetime'
    ];

    /**
     * Relationship back to User.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'IDUtilisateur');
    }
}
