<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Equipement extends Model
{
    protected $table      = 'equipements';
    protected $primaryKey = 'IDequipements';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
        'DateInstalation',
        'datereception',
        'Nbr',
        'Obs',
        'IDEts_Form',
        'IDSpecialite',
        'IDequipement_obs',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function etsForm()
    {
        return $this->belongsTo(\EtsForm::class, 'IDEts_Form', 'IDEts_Form');
    }

    public function specialite()
    {
        return $this->belongsTo(\Specialite::class, 'IDSpecialite', 'IDSpecialite');
    }

    public function equipementOb()
    {
        return $this->belongsTo(\EquipementOb::class, 'IDequipement_obs', 'IDequipement_obs');
    }
}