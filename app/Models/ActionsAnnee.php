<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActionsAnnee extends Model
{
    protected $table      = 'actions_annee';
    protected $primaryKey = 'IDActions_Annee';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'IDannee',
        'N_Bordereau_de_transmission',
        'Date_de_depot',
        'N_visa',
        'Date_de_delivrance_du_visa',
        'Obs',
        'IDActions',
        'IDetablissement',
        'N_ficheavis',
        'Date_ficheavis',
        'Avis_ficheavis',
        'N_Fnotification_dpice',
        'Date_Fnotification_dpce',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function annee()
    {
        return $this->belongsTo(\Annee::class, 'IDannee', 'IDannee');
    }

    public function action()
    {
        return $this->belongsTo(\Action::class, 'IDActions', 'IDActions');
    }

    public function etablissement()
    {
        return $this->belongsTo(\Etablissement::class, 'IDetablissement', 'IDetablissement');
    }
}