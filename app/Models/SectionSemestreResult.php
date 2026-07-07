<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SectionSemestreResult extends Model
{
    protected $table      = 'section_semestre_results';
    protected $primaryKey = 'id';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'IDSection_Semestre',
        'moyenne_generale',
        'is_admis_general',
        'triggered_by',
        'calculated_at',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function sectionSemestre()
    {
        return $this->belongsTo(\SectionSemestre::class, 'IDSection_Semestre', 'IDSection_Semestre');
    }
}