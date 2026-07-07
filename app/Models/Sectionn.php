<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sectionn extends Model
{
    protected $table      = 'sectionn';
    protected $primaryKey = 'IDSectionn';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
        'CODE_TITRE',
        'Numorder_s',
        'POURCENTAGE',
        'IDTitre',
        'CODE_SCTN',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function titre()
    {
        return $this->belongsTo(\Titre::class, 'IDTitre', 'IDTitre');
    }
}