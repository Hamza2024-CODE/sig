<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Activiteinvite extends Model
{
    protected $table      = 'activiteinvite';
    protected $primaryKey = 'IDActiviteInvite';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
        'FonctionGrade',
        'Obs',
        'IDActivite',
        'Prenom',
        'PrenomFr',
        'Tel',
        'Email',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function activite()
    {
        return $this->belongsTo(\Activite::class, 'IDActivite', 'IDActivite');
    }
}