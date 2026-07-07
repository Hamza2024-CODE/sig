<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SecurityLog extends Model
{
    public $timestamps = false;
    protected $table = 'security_logs';

    protected $fillable = [
        'user_id',
        'event_type',
        'severity',
        'description',
        'ip_address',
        'user_agent',
        'metadata'
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime'
    ];

    /**
     * Relationship back to User.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'IDUtilisateur');
    }
}
