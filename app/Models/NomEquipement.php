<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NomEquipement extends Model
{
    protected $table      = 'nom_equipement';
    protected $primaryKey = 'IDNom_Equipement';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
        'IDSpecialite',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function specialite()
    {
        return $this->belongsTo(\Specialite::class, 'IDSpecialite', 'IDSpecialite');
    }
}