<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DomaineRnfc extends Model
{
    protected $table      = 'domaine_rnfc';
    protected $primaryKey = 'IDdomaine_rnfc';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
        'IDSecteur_rnfc',
        'code',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function secteurRnfc()
    {
        return $this->belongsTo(SecteurRnfc::class, 'IDSecteur_rnfc', 'IDSecteur_rnfc');
    }

    public function sousdomaines()
    {
        return $this->hasMany(SousdomaineRnfc::class, 'IDdomaine_rnfc', 'IDdomaine_rnfc');
    }
}