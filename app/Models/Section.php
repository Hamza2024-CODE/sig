<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Section extends Model
{
    protected $table      = 'section';
    protected $primaryKey = 'IDSection';
    public    $timestamps = false;

    protected $fillable = [
        'IDOffre',
        'Code',
        'Intitule',
        'IDAnnee_Formation',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function offre()
    {
        return $this->belongsTo(Offre::class, 'IDOffre', 'IDOffre');
    }

    public function semestres()
    {
        return $this->hasMany(SectionSemestre::class, 'IDSection', 'IDSection');
    }

    public function apprenants()
    {
        return $this->hasMany(Apprenant::class, 'IDSection', 'IDSection');
    }
}
