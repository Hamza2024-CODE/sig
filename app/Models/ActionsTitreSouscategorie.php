<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActionsTitreSouscategorie extends Model
{
    protected $table      = 'actions_titre_souscategorie';
    protected $primaryKey = 'IDActions_titre_souscategorie';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'AEOuvertes',
        'Nom',
        'IDSousCategorie',
        'CPOuvertes',
        'IDArticlea',
        'Obs',
        'CPinitiale',
        'CPadditif',
        'CPadditif1',
        'CPplus',
        'Cpmoins',
        'AEAttendus',
        'CPAttendus',
        'IDActions_titre',
        'NomFr',
        'NumOrd',
        'code',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function sousCategorie()
    {
        return $this->belongsTo(\SousCategorie::class, 'IDSousCategorie', 'IDSousCategorie');
    }

    public function articlea()
    {
        return $this->belongsTo(\Articlea::class, 'IDArticlea', 'IDArticlea');
    }

    public function actionsTitre()
    {
        return $this->belongsTo(\ActionsTitre::class, 'IDActions_titre', 'IDActions_titre');
    }
}