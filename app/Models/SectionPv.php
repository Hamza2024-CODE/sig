<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SectionPv extends Model
{
    protected $table      = 'section_pv';
    protected $primaryKey = 'IDSection_Pv';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Num',
        'Date',
        'IDSection',
        'Obs',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function section()
    {
        return $this->belongsTo(\Section::class, 'IDSection', 'IDSection');
    }
}