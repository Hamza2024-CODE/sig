<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DfepDiplome extends Model
{
    protected $table      = 'dfep_diplome';
    protected $primaryKey = 'IDDfep_diplome';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'numseriediplomedebu',
        'numseriediplomefin',
        'IDDFEP',
        'IDetablissement',
        'Num',
        'Date',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function dFEP()
    {
        return $this->belongsTo(\DFEP::class, 'IDDFEP', 'IDDFEP');
    }

    public function etablissement()
    {
        return $this->belongsTo(\Etablissement::class, 'IDetablissement', 'IDetablissement');
    }
}