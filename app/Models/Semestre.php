<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Semestre extends Model
{
    protected $table      = 'semestre';
    protected $primaryKey = 'IDsemestre';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'IDannee',
        'Nom',
        'NomFr',
        'Dated',
        'Datef',
        'Code',
        'NumOrd',
        'Encour',
        'NumOrdPanne',
        'Clouture',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function annee()
    {
        return $this->belongsTo(\Annee::class, 'IDannee', 'IDannee');
    }
}