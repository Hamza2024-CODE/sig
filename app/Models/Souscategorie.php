<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Souscategorie extends Model
{
    protected $table      = 'souscategorie';
    protected $primaryKey = 'IDSousCategorie';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
        'Code',
        'IDCategorie',
        'desactive',
        'NumOrd',
        'CopieDeIDSousCategorie',
        'Soussouscategorie',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function categorie()
    {
        return $this->belongsTo(\Categorie::class, 'IDCategorie', 'IDCategorie');
    }
}