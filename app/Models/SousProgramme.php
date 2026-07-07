<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SousProgramme extends Model
{
    protected $table      = 'sous_programme';
    protected $primaryKey = 'IDSous_Programme';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
        'IDProgramme',
        'Code',
        'NumOrd',
        'CodeComplet',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function programme()
    {
        return $this->belongsTo(\Programme::class, 'IDProgramme', 'IDProgramme');
    }
}