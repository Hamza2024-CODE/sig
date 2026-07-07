<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SecteurRnfc extends Model
{
    protected $table      = 'secteur_rnfc';
    protected $primaryKey = 'IDSecteur_rnfc';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
        'code',
        'IDclassification_rnfc',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function classificationRnfc()
    {
        return $this->belongsTo(ClassificationRnfc::class, 'IDclassification_rnfc', 'IDclassification_rnfc');
    }

    public function domaines()
    {
        return $this->hasMany(DomaineRnfc::class, 'IDSecteur_rnfc', 'IDSecteur_rnfc');
    }
}