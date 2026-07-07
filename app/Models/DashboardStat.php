<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DashboardStat extends Model
{
    protected $table      = 'dashboard_stats';
    protected $primaryKey = 'stat_key';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'stat_value',
        'stat_group',
        'stat_label',
        'last_updated',
    ];
}