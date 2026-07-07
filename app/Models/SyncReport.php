<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SyncReport extends Model
{
    protected $table      = 'sync_reports';
    protected $primaryKey = 'table_name';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'mysql_count',
        'hfsql_count',
        'status',
    ];
}