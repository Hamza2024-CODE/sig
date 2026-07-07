<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Observationaprenant extends Model
{
    protected $table      = 'observationaprenants';
    protected $primaryKey = 'IDObservationAprenants';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Obs',
        'Date',
        'IDapprenant',
        'Secret',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function apprenant()
    {
        return $this->belongsTo(\Apprenant::class, 'IDapprenant', 'IDapprenant');
    }
}