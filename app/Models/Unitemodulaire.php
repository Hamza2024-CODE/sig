<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Unitemodulaire extends Model
{
    protected $table      = 'unitemodulaire';
    protected $primaryKey = 'IDUniteModulaire';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
        'TypeUM',
        'IDSpecialite',
        'NbrH',
        'IDSpecialite_Programme',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function specialite()
    {
        return $this->belongsTo(\Specialite::class, 'IDSpecialite', 'IDSpecialite');
    }

    public function specialiteProgramme()
    {
        return $this->belongsTo(\SpecialiteProgramme::class, 'IDSpecialite_Programme', 'IDSpecialite_Programme');
    }
}