<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SyncQueue extends Model
{
    protected $table      = 'sync_queue';
    protected $primaryKey = 'id';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'job_id',
        'table_name',
        'sync_type',
        'filter_id',
        'status',
        'priority',
        'started_at',
        'finished_at',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function job()
    {
        return $this->belongsTo(\Job::class, 'job_id', 'job_id');
    }

    public function filter()
    {
        return $this->belongsTo(\Filter::class, 'filter_id', 'filter_id');
    }
}