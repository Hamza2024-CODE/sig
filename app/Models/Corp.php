<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Corp extends Model
{
    protected $table      = 'corp';
    protected $primaryKey = 'IDCorp';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
        'IDSecteur',
        'IDNomenclatureCorp',
        'DecretCorp',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function secteur()
    {
        return $this->belongsTo(\Secteur::class, 'IDSecteur', 'IDSecteur');
    }

    public function nomenclatureCorp()
    {
        return $this->belongsTo(\NomenclatureCorp::class, 'IDNomenclatureCorp', 'IDNomenclatureCorp');
    }
}