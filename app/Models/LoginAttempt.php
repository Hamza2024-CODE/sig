<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoginAttempt extends Model
{
    protected $table      = 'login_attempts';
    protected $primaryKey = 'id';
    public    $timestamps = false;

    protected $fillable = [
        'ip_address',
        'username',
        'attempts',
        'last_attempt',
        'locked_until',
    ];

    protected $casts = [
        'last_attempt' => 'datetime',
        'locked_until' => 'datetime',
    ];

    // ── Scopes ───────────────────────────────────────────────────────────────

    public function scopeForIp($query, string $ip)
    {
        return $query->where('ip_address', $ip);
    }

    public function scopeLocked($query)
    {
        return $query->where('locked_until', '>', now());
    }
}
