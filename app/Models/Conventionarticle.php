<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Conventionarticle extends Model
{
    protected $table      = 'conventionarticle';
    protected $primaryKey = 'IDConventionArticle';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'numArticle',
        'contenu',
        'IDConvention',
        'Titre',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function convention()
    {
        return $this->belongsTo(\Convention::class, 'IDConvention', 'IDConvention');
    }
}