<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConcoursExamenprofessionnel extends Model
{
    protected $table      = 'concours_examenprofessionnel';
    protected $primaryKey = 'IDConcours_ExamenProfessionnel';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'IDetablissement',
        'DateDepoDossier',
        'DateConcour',
        'Obs',
        'IDSecteurs',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function etablissement()
    {
        return $this->belongsTo(\Etablissement::class, 'IDetablissement', 'IDetablissement');
    }

    public function secteur()
    {
        return $this->belongsTo(\Secteur::class, 'IDSecteurs', 'IDSecteurs');
    }
}