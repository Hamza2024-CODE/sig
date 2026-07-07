<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $table      = 'service';
    protected $primaryKey = 'IDService';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
        'IDNature_etsF',
        'IDdirection',
        'CopieDeIDService',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function natureEtsF()
    {
        return $this->belongsTo(\NatureEtsF::class, 'IDNature_etsF', 'IDNature_etsF');
    }

    public function direction()
    {
        return $this->belongsTo(\Direction::class, 'IDdirection', 'IDdirection');
    }
}