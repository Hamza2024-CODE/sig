<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SpecialiteModuleObjectif extends Model
{
    protected $table      = 'specialite_module_objectifs';
    protected $primaryKey = 'IDSpecialite_Module_Objectifs';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'objectifs_intermediaires',
        'objectifs_intermediairesar',
        'Criteres_particuliers_de_performance',
        'Criteres_particuliers_de_performancear',
        'elements_de_contenu',
        'elements_de_contenuar',
        'IDSpecialite_Module',
        'objectifs_intermediairesen',
        'Criteres_particuliers_de_performanceen',
        'elements_de_contenuen',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function specialiteModule()
    {
        return $this->belongsTo(\SpecialiteModule::class, 'IDSpecialite_Module', 'IDSpecialite_Module');
    }
}