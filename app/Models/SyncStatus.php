<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SyncStatus extends Model
{
    protected $table      = 'sync_status';
    protected $primaryKey = 'table_name';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'is_ready',
    ];
}