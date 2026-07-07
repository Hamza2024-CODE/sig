<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pvsemstriel extends Model
{
    protected $table      = 'pvsemstriel';
    protected $primaryKey = 'IDPV';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'IDSection_Semestre',
        'NumPv',
        'DatePv',
        'Obs',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function sectionSemestre()
    {
        return $this->belongsTo(\SectionSemestre::class, 'IDSection_Semestre', 'IDSection_Semestre');
    }
}