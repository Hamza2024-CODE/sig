<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EtablisementDetail extends Model
{
    protected $table      = 'etablisement_detail';
    protected $primaryKey = 'IDDetail_Etablisement';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'IDetablissement',
        'nbrsalle',
        'nbrLabo',
        'biblioteque',
        'internat',
        'EtatActual',
        'surface',
        'EspaceSport',
        'nbrAttelier',
        'SalleSpecialis',
        'Emph',
        'Baio',
        'resto',
        'ExploitInternat',
        'nbrDemi',
        'Latitude',
        'Longitude',
        'surfaceNBatiexploi',
        'NbrLogementAsOc',
        'NbrLogementFoOC',
        'NbrLogmentAsvide',
        'NbrLogmentFovide',
        'Nbrvehicule',
        'Nbrcare',
        'NbrCamion',
        'NbrEmployeur',
        'NbrEmployeurTech',
        'NbrEmployeurComm',
        'NbrEmployeurCont',
        'NbrEmployeurVac',
        'NbrEmployeurDetadu',
        'NbrEmployeurdetaau',
        'NbrEquipementfonc',
        'NbrEquipementnonFonc',
        'Nbrambulance',
        'Existelogmentass',
        'ExisteAuto',
        'ExisteEquipement',
        'NbrLogmentTheor',
        'NbrAutoTheor',
        'ExisteSurfaceNB',
        'Validation',
        'ExistelogmentFonc',
        'Nbrposteoccupe',
        'Nbrposteallouer',
        'Nbrpostevacant',
        'NbrposteSuperallouer',
        'NbrFonctionsuperallouer',
        'NbrposteSuperVacant',
        'NbrFonctionsuperVacant',
        'NbrposteSuperoccupe',
        'NbrFonctionsuperoccupe',
        'NbrFonctionsuperbesoin',
        'Nbrpostebesoin',
        'NbrposteSuperbesoin',
        'NbrEmployeurOuvriersconducappar',
        'NbrEmployeurautre',
        'Existetel',
        'Existefax',
        'Existeapprnant',
        'Existedecret',
        'Existenomchahid',
        'nbrterainpedagogique',
        'existepvrecoulmentequipement',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function etablissement()
    {
        return $this->belongsTo(\Etablissement::class, 'IDetablissement', 'IDetablissement');
    }
}