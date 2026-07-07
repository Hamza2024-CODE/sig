<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AxeTache extends Model
{
    protected $table      = 'axe_tache';
    protected $primaryKey = 'IDaxe_tache';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
        'IDaxe_principale',
        'NumOrd',
        'Pourc',
        'IDdirection',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function axePrincipale()
    {
        return $this->belongsTo(\AxePrincipale::class, 'IDaxe_principale', 'IDaxe_principale');
    }

    public function direction()
    {
        return $this->belongsTo(\Direction::class, 'IDdirection', 'IDdirection');
    }
}