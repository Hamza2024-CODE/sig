<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Preinscrit extends Model
{
    protected $table      = 'preinscrit';
    protected $primaryKey = 'IDPreinscrit';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'IDOffre',
        'Nom',
        'NomFr',
        'Prenom',
        'PrenomFr',
        'DateNais',
        'LieuNaisFr',
        'LieuNais',
        'NumIns',
        'Presume',
        'IDDecision_Insc',
        'IDNiveau_Scol',
        'PrenomPereFr',
        'PrenomPere',
        'NomMereFr',
        'IdMihnati',
        'PrenomMereFr',
        'NbrAnneExp',
        'NomMere',
        'Civ',
        'PrenomMere',
        'IDqualification_dplm',
        'GroupeExa',
        'Valide',
        'IDEts_Scolaire',
        'IDFiliere',
        'dateInscr',
        'IDNationalitee',
        'IDCandidatN',
        'endicape',
        'Adres',
        'AdresFr',
        'Obs',
        'Nationalite',
        'Validation',
        'ValidationDfp',
        'Nss',
        'IdMihnati1',
        'DerogationAge',
        'IDCommunn',
        'IDWilayaa',
        'IDCommunR',
        'IDWilayaR',
        'sansParant',
        'Nin',
        'NumActeNais',
        'email',
        'tel1',
        'crypted_id',
        'Premier',
        'Password',
        'photo_path',
        'ValidationAfterResult',
        'certscol_path',
        'emailvalide',
        'telvalide',
        'CodeEtab_men',
        'annescolaire',
        'numcertificatscolaire',
        'matcertificatscolaire',
        'datecertificatscolaire',
        'datesortirscolaire',
        'filscolaire',
        'nbtasdjilcertificatscolaire',
        'path_inscript',
        'nature_men',
        'onefd_men',
        'contratpdf_path',
        'Nom_employeur',
        'disable_account',
        'diplomecert_path',
        'langueformation',
        'IDSpecialite',
        'IDWlylibre',
        'IDSession',
        'numwassit',
        'actnaispdf_path',
        'tawir',
        'path_chomage',
        'remarque',
        'motif_anem',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function offre()
    {
        return $this->belongsTo(\Offre::class, 'IDOffre', 'IDOffre');
    }

    public function decisionInsc()
    {
        return $this->belongsTo(\DecisionInsc::class, 'IDDecision_Insc', 'IDDecision_Insc');
    }

    public function niveauScol()
    {
        return $this->belongsTo(\NiveauScol::class, 'IDNiveau_Scol', 'IDNiveau_Scol');
    }

    public function qualificationDplm()
    {
        return $this->belongsTo(\QualificationDplm::class, 'IDqualification_dplm', 'IDqualification_dplm');
    }

    public function etsScolaire()
    {
        return $this->belongsTo(\EtsScolaire::class, 'IDEts_Scolaire', 'IDEts_Scolaire');
    }

    public function filiere()
    {
        return $this->belongsTo(\Filiere::class, 'IDFiliere', 'IDFiliere');
    }

    public function nationalitee()
    {
        return $this->belongsTo(\Nationalitee::class, 'IDNationalitee', 'IDNationalitee');
    }

    public function candidatN()
    {
        return $this->belongsTo(\CandidatN::class, 'IDCandidatN', 'IDCandidatN');
    }

    public function communn()
    {
        return $this->belongsTo(\Communn::class, 'IDCommunn', 'IDCommunn');
    }

    public function wilayaa()
    {
        return $this->belongsTo(\Wilayaa::class, 'IDWilayaa', 'IDWilayaa');
    }

    public function communR()
    {
        return $this->belongsTo(\CommunR::class, 'IDCommunR', 'IDCommunR');
    }

    public function wilayaR()
    {
        return $this->belongsTo(\WilayaR::class, 'IDWilayaR', 'IDWilayaR');
    }

    public function crypted()
    {
        return $this->belongsTo(\Crypted::class, 'crypted_id', 'crypted_id');
    }

    public function specialite()
    {
        return $this->belongsTo(\Specialite::class, 'IDSpecialite', 'IDSpecialite');
    }

    public function wlylibre()
    {
        return $this->belongsTo(\Wlylibre::class, 'IDWlylibre', 'IDWlylibre');
    }

    public function session()
    {
        return $this->belongsTo(\Session::class, 'IDSession', 'IDSession');
    }
}