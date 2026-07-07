<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CalTache extends Model
{
    protected $table      = 'cal_tache';
    protected $primaryKey = 'IDCal_Tache';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'Detail',
        'IDRealise',
        'DateD',
        'DateF',
        'IDdirection',
        'Obs',
        'IDaxe_tache',
        'Validation',
        'IDetablissement',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function realise()
    {
        return $this->belongsTo(\Realise::class, 'IDRealise', 'IDRealise');
    }

    public function direction()
    {
        return $this->belongsTo(\Direction::class, 'IDdirection', 'IDdirection');
    }

    public function axeTache()
    {
        return $this->belongsTo(\AxeTache::class, 'IDaxe_tache', 'IDaxe_tache');
    }

    public function etablissement()
    {
        return $this->belongsTo(\Etablissement::class, 'IDetablissement', 'IDetablissement');
    }
}