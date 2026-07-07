<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SyncLog extends Model
{
    protected $table      = 'sync_logs';
    protected $primaryKey = 'id';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'job_id',
        'level',
        'message',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function job()
    {
        return $this->belongsTo(\Job::class, 'job_id', 'job_id');
    }
}