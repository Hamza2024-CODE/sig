<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SyncJob extends Model
{
    protected $table      = 'sync_jobs';
    protected $primaryKey = 'id';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'job_id',
        'table_name',
        'status',
        'total_rows',
        'synced_rows',
        'last_synced_id',
        'error_message',
        'started_at',
        'finished_at',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function job()
    {
        return $this->belongsTo(\Job::class, 'job_id', 'job_id');
    }

    public function lastSynced()
    {
        return $this->belongsTo(\LastSynced::class, 'last_synced_id', 'last_synced_id');
    }
}