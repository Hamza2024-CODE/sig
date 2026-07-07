<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChapitreNatureet extends Model
{
    protected $table      = 'chapitre_natureets';
    protected $primaryKey = 'IDChapitre_natureets';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'IDChapitrea',
        'IDNature_etsF',
        'NumOrd',
        'Nom',
        'NomFr',
        'Obs',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function chapitrea()
    {
        return $this->belongsTo(\Chapitrea::class, 'IDChapitrea', 'IDChapitrea');
    }

    public function natureEtsF()
    {
        return $this->belongsTo(\NatureEtsF::class, 'IDNature_etsF', 'IDNature_etsF');
    }
}