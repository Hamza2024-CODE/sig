<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Recommandation extends Model
{
    protected $table      = 'recommandations';
    protected $primaryKey = 'IDRecommandations';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
        'IDAtelier',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function atelier()
    {
        return $this->belongsTo(\Atelier::class, 'IDAtelier', 'IDAtelier');
    }
}