<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Emailreponce extends Model
{
    protected $table      = 'emailreponce';
    protected $primaryKey = 'IDemailreponce';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'reponce',
        'Date',
        'heure',
        'IDemail',
        'Obs',
        'IDetablissement',
        'IDetablissement_dest',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function email()
    {
        return $this->belongsTo(\Email::class, 'IDemail', 'IDemail');
    }

    public function etablissement()
    {
        return $this->belongsTo(\Etablissement::class, 'IDetablissement', 'IDetablissement');
    }

    public function etablissementDest()
    {
        return $this->belongsTo(\EtablissementDest::class, 'IDetablissement_dest', 'IDetablissement_dest');
    }
}