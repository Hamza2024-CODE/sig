<?php

namespace App\Domains\Academic\Models;

/**
 * Apprenant — Value Object wrapping a WinDev `apprenant` table row.
 *
 * WINDEV legacy columns (schema frozen — DO NOT add/remove columns):
 *   IDapprenant, IDCandidat, IDSection, Nccp, Valide, active
 *
 * Joined from:
 *   candidat (Nom, Prenom, NomFr, PrenomFr, Civ, Nin)
 *   section → offre → specialite (Nom)
 */
class Apprenant
{
    public int    $id;
    public int    $candidatId;
    public int    $sectionId;
    public string $matricule;   // Nccp
    public int    $valide;
    public int    $active;

    // Read-only joined enrichments
    public ?string $nomAr       = null;
    public ?string $prenomAr    = null;
    public ?string $nomFr       = null;
    public ?string $prenomFr    = null;
    public int     $civ         = 1;   // 1 = ذكر, 2 = أنثى
    public ?string $nin         = null;
    public ?string $specialiteAr = null;
    public ?string $etabNom     = null;
    public ?string $offreMode   = null;

    public function __construct(array $data)
    {
        $this->id          = (int)($data['id']          ?? $data['IDapprenant']   ?? 0);
        $this->candidatId  = (int)($data['candidat_id'] ?? $data['IDCandidat']    ?? 0);
        $this->sectionId   = (int)($data['section_id']  ?? $data['IDSection']     ?? 0);
        $this->matricule   = (string)($data['matricule'] ?? $data['Nccp']          ?? '');
        $this->valide      = (int)($data['Valide']       ?? $data['valide']        ?? 0);
        $this->active      = (int)($data['active']       ?? 1);

        // Joined fields
        $this->nomAr        = $data['nom_ar']        ?? null;
        $this->prenomAr     = $data['prenom_ar']     ?? null;
        $this->nomFr        = $data['nom_fr']        ?? null;
        $this->prenomFr     = $data['prenom_fr']     ?? null;
        $this->civ          = (int)($data['civ']     ?? $data['Civ'] ?? 1);
        $this->nin          = $data['nin']           ?? null;
        $this->specialiteAr = $data['specialite_ar'] ?? null;
        $this->etabNom      = $data['etab_nom']      ?? null;
        $this->offreMode    = $data['offre_mode']    ?? null;
    }

    public function sexeLabel(): string
    {
        return $this->civ === 1 ? 'ذكر' : 'أنثى';
    }

    public function statutLabel(): string
    {
        return $this->valide === 1 ? 'نشط' : 'غير نشط';
    }
}
