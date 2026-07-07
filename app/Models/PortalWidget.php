<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PortalWidget extends Model
{
    protected $table      = 'portal_widgets';
    protected $primaryKey = 'id';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'portal_layout_id',
        'type',
        'title',
        'grid_x',
        'grid_y',
        'grid_w',
        'grid_h',
        'config',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function portalLayout()
    {
        return $this->belongsTo(\PortalLayout::class, 'portal_layout_id', 'portal_layout_id');
    }
}