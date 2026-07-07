<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DashboardWidget extends Model
{
    protected $table      = 'dashboard_widgets';
    protected $primaryKey = 'id';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'dashboard_id',
        'type',
        'title',
        'grid_x',
        'grid_y',
        'grid_w',
        'grid_h',
        'config',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function dashboard()
    {
        return $this->belongsTo(\Dashboard::class, 'dashboard_id', 'dashboard_id');
    }
}