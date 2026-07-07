<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EquipementComposant extends Model
{
    protected $table      = 'equipement_composant';
    protected $primaryKey = 'IDequipement_composant';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'nomFr',
        'Nbr',
        'IDEquipement',
        'IDequipement_composant_Etat',
        'NumInventaire',
        'NumInventaireAu',
        'NumOrd',
        'Obs',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function equipement()
    {
        return $this->belongsTo(\Equipement::class, 'IDEquipement', 'IDEquipement');
    }

    public function equipementComposantEtat()
    {
        return $this->belongsTo(\EquipementComposantEtat::class, 'IDequipement_composant_Etat', 'IDequipement_composant_Etat');
    }
}