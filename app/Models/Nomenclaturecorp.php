<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Nomenclaturecorp extends Model
{
    protected $table      = 'nomenclaturecorp';
    protected $primaryKey = 'IDNomenclatureCorp';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'nomFr',
        'NumOrd',
        'IDSecteur',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function secteur()
    {
        return $this->belongsTo(\Secteur::class, 'IDSecteur', 'IDSecteur');
    }
}