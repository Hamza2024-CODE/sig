<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EquipementEtablissement extends Model
{
    protected $table      = 'equipement_etablissement';
    protected $primaryKey = 'IDEquipement_Etablissement';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'DatePvTransfert',
        'DatePvReception',
        'DateMiseExploitation',
        'DatefinExploitation',
        'IDetablissement',
        'IDEquipement',
        'NumPvReception',
        'NumPvTransfert',
        'DateInstalation',
        'Encour',
        'IDetablissement2',
        'Obs',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function etablissement()
    {
        return $this->belongsTo(\Etablissement::class, 'IDetablissement', 'IDetablissement');
    }

    public function equipement()
    {
        return $this->belongsTo(\Equipement::class, 'IDEquipement', 'IDEquipement');
    }

    public function etablissement2()
    {
        return $this->belongsTo(\Etablissement2::class, 'IDetablissement2', 'IDetablissement2');
    }
}