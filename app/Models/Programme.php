<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Programme extends Model
{
    protected $table      = 'programme';
    protected $primaryKey = 'IDProgramme';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
        'Code',
        'IDPortefeuille',
        'NumOrd',
        'CodeComplet',
        'anneaplication',
        'desactive',
        'Obs',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function portefeuille()
    {
        return $this->belongsTo(\Portefeuille::class, 'IDPortefeuille', 'IDPortefeuille');
    }
}