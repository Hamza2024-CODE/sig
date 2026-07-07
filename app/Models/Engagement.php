<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Engagement extends Model
{
    protected $table      = 'engagement';
    protected $primaryKey = 'IDEngagement';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'NFicheengagment',
        'Dateficheengagment',
        'NumVisacb',
        'DateVisacb',
        'Montant',
        'Obs',
        'IDannee',
        'Datesignature',
        'Sujet',
        'Sujetfr',
        'IDEngagementtype',
        'Cumulengagment',
        'Soldenouv',
        'rejet',
        'rejetmotif',
        'rejetdate',
        'IDActions_titre_souscategorie',
        'IDActions_titre',
        'ini',
        'Datedebo',
        'AeouverteModifier',
        'Soldeinitiale',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function annee()
    {
        return $this->belongsTo(\Annee::class, 'IDannee', 'IDannee');
    }

    public function engagementtype()
    {
        return $this->belongsTo(\Engagementtype::class, 'IDEngagementtype', 'IDEngagementtype');
    }

    public function actionsTitreSouscategorie()
    {
        return $this->belongsTo(\ActionsTitreSouscategorie::class, 'IDActions_titre_souscategorie', 'IDActions_titre_souscategorie');
    }

    public function actionsTitre()
    {
        return $this->belongsTo(\ActionsTitre::class, 'IDActions_titre', 'IDActions_titre');
    }
}