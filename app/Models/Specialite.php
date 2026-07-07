<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Specialite extends Model
{
    protected $table      = 'specialite';
    protected $primaryKey = 'IDSpecialite';
    public    $timestamps = false;

    protected $fillable = [
        'CodeSpec',
        'Nom',
        'NomFr',
        'IDBranche',
        'Duree',
        'Niveau',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function branche()
    {
        return $this->belongsTo(Branche::class, 'IDBranche', 'IDBranche');
    }

    public function offres()
    {
        return $this->hasMany(Offre::class, 'IDSpecialite', 'IDSpecialite');
    }

    public function sousdomaine()
    {
        return $this->belongsTo(SousdomaineRnfc::class, 'IDsousdomaine_rnfc', 'IDsousdomaine_rnfc');
    }
}
