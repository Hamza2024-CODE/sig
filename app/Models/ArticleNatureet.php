<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArticleNatureet extends Model
{
    protected $table      = 'article_natureets';
    protected $primaryKey = 'IDArticle_natureets';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'IDNature_etsF',
        'IDArticlea',
        'Numord',
        'Nom',
        'NomFr',
        'Obs',
        'code',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function natureEtsF()
    {
        return $this->belongsTo(\NatureEtsF::class, 'IDNature_etsF', 'IDNature_etsF');
    }

    public function articlea()
    {
        return $this->belongsTo(\Articlea::class, 'IDArticlea', 'IDArticlea');
    }
}