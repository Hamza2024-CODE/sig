<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Atelier extends Model
{
    protected $table      = 'atelier';
    protected $primaryKey = 'IDAtelier';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
        'IDActivite',
        'Num',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function activite()
    {
        return $this->belongsTo(\Activite::class, 'IDActivite', 'IDActivite');
    }
}