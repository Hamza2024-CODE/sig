<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TakwinSetting extends Model
{
    protected $table      = 'takwin_settings';
    protected $primaryKey = 'id';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'api_url',
        'api_token',
        'sync_enabled',
        'last_sync_status',
        'last_sync_message',
        'last_sync_at',
        'diploma_bg_url',
        'diploma_border_color',
        'diploma_watermark_url',
        'diploma_primary_color',
    ];
}