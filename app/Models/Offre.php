<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Offre extends Model
{
    protected $table      = 'offre';
    protected $primaryKey = 'IDOffre';
    public    $timestamps = false;

    protected $fillable = [
        'IDEts_Form',
        'IDSpecialite',
        'IDMode_formation',
        'NbrInscr',
        'NbrInscrf',
        'DateOuverture',
        'DateFermeture',
        'Statut',
        'IDSession',
        'IDAnnee_Formation',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function etablissement()
    {
        return $this->belongsTo(Etablissement::class, 'IDEts_Form', 'IDetablissement');
    }

    public function specialite()
    {
        return $this->belongsTo(Specialite::class, 'IDSpecialite', 'IDSpecialite');
    }

    public function modeFormation()
    {
        return $this->belongsTo(ModeFormation::class, 'IDMode_formation', 'IDMode_formation');
    }

    public function candidats()
    {
        return $this->hasMany(Candidat::class, 'IDOffre', 'IDOffre');
    }

    public function sections()
    {
        return $this->hasMany(Section::class, 'IDOffre', 'IDOffre');
    }
}
