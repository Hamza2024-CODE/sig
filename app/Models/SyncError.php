<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SyncError extends Model
{
    protected $table      = 'sync_errors';
    protected $primaryKey = 'id';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'job_id',
        'table_name',
        'record_id',
        'error_message',
        'payload',
        'status',
        'retry_count',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function job()
    {
        return $this->belongsTo(\Job::class, 'job_id', 'job_id');
    }

    public function record()
    {
        return $this->belongsTo(\Record::class, 'record_id', 'record_id');
    }
}