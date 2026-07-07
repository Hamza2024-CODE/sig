<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Compingeperiode extends Model
{
    protected $table      = 'compingeperiode';
    protected $primaryKey = 'IDCompingePeriode';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Datedeb',
        'DateFIn',
        'Duree',
        'dateSortir',
        'crenaux',
        'Obs',
        'IDsemestre',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function semestre()
    {
        return $this->belongsTo(\Semestre::class, 'IDsemestre', 'IDsemestre');
    }
}