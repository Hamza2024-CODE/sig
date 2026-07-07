<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserRecoveryCode extends Model
{
    public $timestamps = false;
    protected $table = 'user_recovery_codes';

    protected $fillable = [
        'user_id',
        'code_hash',
        'used_at'
    ];

    protected $casts = [
        'used_at' => 'datetime',
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
