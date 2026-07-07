<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Chapitrea extends Model
{
    protected $table      = 'chapitrea';
    protected $primaryKey = 'IDChapitrea';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'CODE_CHPTR',
        'Nom',
        'NomFr',
        'NumOrd',
        'CONTENU_SECT',
        'NUMORD_C',
        'POURCENTAGE',
        'IDSectionn',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function sectionn()
    {
        return $this->belongsTo(\Sectionn::class, 'IDSectionn', 'IDSectionn');
    }
}