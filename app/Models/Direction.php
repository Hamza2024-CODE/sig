<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Direction extends Model
{
    protected $table      = 'direction';
    protected $primaryKey = 'IDdirection';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'IDNature',
        'Nom',
        'NomFr',
        'IDNature_etsF',
        'CopieDeIDdirection',
        'NumOrd',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function nature()
    {
        return $this->belongsTo(\Nature::class, 'IDNature', 'IDNature');
    }

    public function natureEtsF()
    {
        return $this->belongsTo(\NatureEtsF::class, 'IDNature_etsF', 'IDNature_etsF');
    }
}