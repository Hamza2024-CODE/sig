<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnalyticsKpiSnapshot extends Model
{
    protected $table      = 'analytics_kpi_snapshots';
    protected $primaryKey = 'id';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'kpi_type',
        'value',
        'target_value',
        'period_start',
        'period_end',
        'category',
        'category_id',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function category()
    {
        return $this->belongsTo(\Category::class, 'category_id', 'category_id');
    }
}