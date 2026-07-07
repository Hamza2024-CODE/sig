<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Trimestre extends Model
{
    protected $table      = 'trimestre';
    protected $primaryKey = 'IDTrimestre';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'IDsemestre',
        'Nom',
        'NomFr',
        'Dated',
        'DateF',
        'Code',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function semestre()
    {
        return $this->belongsTo(\Semestre::class, 'IDsemestre', 'IDsemestre');
    }
}