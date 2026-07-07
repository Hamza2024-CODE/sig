<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActionsTitre extends Model
{
    protected $table      = 'actions_titre';
    protected $primaryKey = 'IDActions_titre';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'IDTitre',
        'AEOuvertes',
        'CPOuvertes',
        'N_visa',
        'Date_de_delivrance_du_visa',
        'IDActions_Annee',
        'IDetablissement',
        'Valide',
        'validedfep',
        'ValideCentral',
        'IDannee',
        'Obs',
        'CPinitiale',
        'CPadditif',
        'CPadditif1',
        'CPplus',
        'Cpmoins',
        'AEAttendus',
        'CPAttendus',
        'IDActions',
        'AERattachement',
        'CPRattachement',
        'Nom',
        'NomFr',
        'NumOrd',
        'IDAction',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function titre()
    {
        return $this->belongsTo(\Titre::class, 'IDTitre', 'IDTitre');
    }

    public function actionsAnnee()
    {
        return $this->belongsTo(\ActionsAnnee::class, 'IDActions_Annee', 'IDActions_Annee');
    }

    public function etablissement()
    {
        return $this->belongsTo(\Etablissement::class, 'IDetablissement', 'IDetablissement');
    }

    public function annee()
    {
        return $this->belongsTo(\Annee::class, 'IDannee', 'IDannee');
    }

    public function action()
    {
        return $this->belongsTo(\Action::class, 'IDActions', 'IDActions');
    }

    public function action()
    {
        return $this->belongsTo(\Action::class, 'IDAction', 'IDAction');
    }
}