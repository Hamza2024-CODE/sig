<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LicenseKey extends Model
{
    protected $table      = 'license_keys';
    protected $primaryKey = 'id';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'license_key',
        'ets_id',
        'user_id',
        'activated_at',
        'expires_at',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function et()
    {
        return $this->belongsTo(\Et::class, 'ets_id', 'ets_id');
    }

    public function user()
    {
        return $this->belongsTo(\User::class, 'user_id', 'user_id');
    }
}