<?php

namespace App\Domains\Academic\Models;

class Offre
{
    public int $id;
    public int $sessionId;
    public int $specialiteId;
    public int $modeFormationId;
    public ?string $dateDebut;
    public ?string $dateFin;
    public int $capacite;
    public int $etablissementId;
    public int $valide;
    public int $validDfp;
    public int $valideCentral;
    public ?string $dateSelection;
    public ?string $dateVisiteMedical;
    public ?string $dateVisiteAtelier;
    public int $nbrGroupe;
    public int $nbrInscr;
    public ?string $obsDfep;
    public ?string $obsCentral;

    public function __construct(array $data)
    {
        $this->id = (int)($data['id'] ?? 0);
        $this->sessionId = (int)($data['session_id'] ?? 0);
        $this->specialiteId = (int)($data['specialite_id'] ?? 0);
        $this->modeFormationId = (int)($data['mode_formation_id'] ?? 1);
        $this->dateDebut = !empty($data['date_debut']) ? $data['date_debut'] : null;
        $this->dateFin = !empty($data['date_fin']) ? $data['date_fin'] : null;
        $this->capacite = (int)($data['capacite'] ?? 0);
        $this->etablissementId = (int)($data['etablissement_id'] ?? 0);
        $this->valide = (int)($data['valide'] ?? 0);
        $this->validDfp = (int)($data['valid_dfp'] ?? 0);
        $this->valideCentral = (int)($data['valide_central'] ?? 0);
        $this->dateSelection = !empty($data['date_selection']) ? $data['date_selection'] : null;
        $this->dateVisiteMedical = !empty($data['date_visite_medical']) ? $data['date_visite_medical'] : null;
        $this->dateVisiteAtelier = !empty($data['date_visite_atelier']) ? $data['date_visite_atelier'] : null;
        $this->nbrGroupe = (int)($data['nbr_groupe'] ?? 0);
        $this->nbrInscr = (int)($data['nbr_inscr'] ?? 0);
        $this->obsDfep = !empty($data['obs_dfep']) ? $data['obs_dfep'] : null;
        $this->obsCentral = !empty($data['obs_central']) ? $data['obs_central'] : null;
    }
}
