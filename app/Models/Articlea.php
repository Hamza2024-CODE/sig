<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Articlea extends Model
{
    protected $table      = 'articlea';
    protected $primaryKey = 'IDArticlea';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'code',
        'Nom',
        'NomFr',
        'CODE_CHPTR',
        'CONTENU_ART',
        'Numord',
        'POURCENTAGE',
        'TYPE_IND',
        'Anne_FINAn',
        'CODE_DECRT',
        'LESDECRET',
        'NC_ElemBudj',
        'IDSousCategorie',
        'IDChapitrea',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function sousCategorie()
    {
        return $this->belongsTo(\SousCategorie::class, 'IDSousCategorie', 'IDSousCategorie');
    }

    public function chapitrea()
    {
        return $this->belongsTo(\Chapitrea::class, 'IDChapitrea', 'IDChapitrea');
    }
}