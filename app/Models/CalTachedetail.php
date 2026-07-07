<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CalTachedetail extends Model
{
    protected $table      = 'cal_tachedetail';
    protected $primaryKey = 'IDCal_TacheDetail';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NumOrd',
        'DateD',
        'Pourc',
        'IDCal_Tache',
        'IDService',
        'DateF',
        'NumSemaine',
        'Validation',
        'Obs',
        'IDaxe_tache',
        'IDdirection',
        'Detail',
        'heure',
        'Anne',
        'IDetablissement',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function calTache()
    {
        return $this->belongsTo(\CalTache::class, 'IDCal_Tache', 'IDCal_Tache');
    }

    public function service()
    {
        return $this->belongsTo(\Service::class, 'IDService', 'IDService');
    }

    public function axeTache()
    {
        return $this->belongsTo(\AxeTache::class, 'IDaxe_tache', 'IDaxe_tache');
    }

    public function direction()
    {
        return $this->belongsTo(\Direction::class, 'IDdirection', 'IDdirection');
    }

    public function etablissement()
    {
        return $this->belongsTo(\Etablissement::class, 'IDetablissement', 'IDetablissement');
    }
}