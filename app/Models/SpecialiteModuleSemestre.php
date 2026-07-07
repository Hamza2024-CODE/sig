<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SpecialiteModuleSemestre extends Model
{
    protected $table      = 'specialite_module_semestre';
    protected $primaryKey = 'IDSpecialite_Module_semestre';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'NumSem',
        'IDSpecialite_Module',
        'Obs',
        'Nbrhhebd',
        'Nbrhsem',
        'Nbrcours',
        'Nbrtdtp',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function specialiteModule()
    {
        return $this->belongsTo(\SpecialiteModule::class, 'IDSpecialite_Module', 'IDSpecialite_Module');
    }
}