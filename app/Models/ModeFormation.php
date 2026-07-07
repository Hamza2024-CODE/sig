<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModeFormation extends Model
{
    protected $table      = 'mode_formation';
    protected $primaryKey = 'IDMode_formation';
    public    $timestamps = false;

    protected $fillable = [
        'Code',
        'Nom',
        'NomFr',
        'Abr',
        'AbrFr',
        'NumOrd',
        'NomOrd',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function offres()
    {
        return $this->hasMany(Offre::class, 'IDMode_formation', 'IDMode_formation');
    }
}
