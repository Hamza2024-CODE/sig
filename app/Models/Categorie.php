<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Categorie extends Model
{
    protected $table      = 'categorie';
    protected $primaryKey = 'IDCategorie';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
        'Code',
        'IDTitre',
        'NumOrd',
        'desactive',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function titre()
    {
        return $this->belongsTo(\Titre::class, 'IDTitre', 'IDTitre');
    }
}