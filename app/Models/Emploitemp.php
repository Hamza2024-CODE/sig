<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Emploitemp extends Model
{
    protected $table      = 'emploitemp';
    protected $primaryKey = 'IDEmploiTemp';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'IDsection_semestre_Module',
        'Crenaux',
        'Heured',
        'Heuref',
        'IDLocaux',
        'Jour',
        'Duree',
        'Obs',
        'Groupe',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function sectionSemestreModule()
    {
        return $this->belongsTo(\SectionSemestreModule::class, 'IDsection_semestre_Module', 'IDsection_semestre_Module');
    }

    public function locaux()
    {
        return $this->belongsTo(\Locaux::class, 'IDLocaux', 'IDLocaux');
    }
}