<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bureauetude extends Model
{
    protected $table      = 'bureauetude';
    protected $primaryKey = 'IDBureauEtude';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
        'NIF',
        'Tel',
        'Fax',
        'IDBureauEtudeNature',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function bureauEtudeNature()
    {
        return $this->belongsTo(\BureauEtudeNature::class, 'IDBureauEtudeNature', 'IDBureauEtudeNature');
    }
}