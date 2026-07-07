<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Activite extends Model
{
    protected $table      = 'activite';
    protected $primaryKey = 'IDActivite';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
        'DateDeb',
        'DateFIn',
        'Duree',
        'IDActiviteType',
        'Description',
        'logo',
        'Lieu',
        'objectif',
        'Obs',
        'IDetablissement',
        'Encour',
        'Latitude',
        'Longitude',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function activiteType()
    {
        return $this->belongsTo(\ActiviteType::class, 'IDActiviteType', 'IDActiviteType');
    }

    public function etablissement()
    {
        return $this->belongsTo(\Etablissement::class, 'IDetablissement', 'IDetablissement');
    }
}