<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SpecialiteModeformation extends Model
{
    protected $table      = 'specialite_modeformation';
    protected $primaryKey = 'IDSpecialite_modeformation';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'obligatoire',
        'IDSpecialite',
        'IDMode_formation',
        'Obs',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function specialite()
    {
        return $this->belongsTo(\Specialite::class, 'IDSpecialite', 'IDSpecialite');
    }

    public function modeFormation()
    {
        return $this->belongsTo(\ModeFormation::class, 'IDMode_formation', 'IDMode_formation');
    }
}