<?php

namespace App\Domains\Academic\Models;

/**
 * Candidat — Value Object wrapping a WinDev `candidat` table row.
 *
 * WINDEV legacy columns (schema frozen — DO NOT add/remove columns):
 *   IDCandidat, Nom, Prenom, NomFr, PrenomFr, Nin, Civ, Tel,
 *   NumIns, Validation, Obs, IDOffre, dateInscr
 */
class Candidat
{
    public int    $id;
    public string $nomAr;
    public string $prenomAr;
    public string $nomFr;
    public string $prenomFr;
    public ?string $nin;
    public int    $civ;         // 1 = ذكر, 2 = أنثى
    public ?string $telephone;
    public ?string $numeroInscription;
    public int    $validation;  // 0 = قيد الانتظار, 1 = مقبول, 2 = مرفوض
    public ?string $motifRefus;
    public int    $offreId;
    public ?string $dateInscription;

    // Joined read-only fields
    public ?string $specialiteAr  = null;
    public ?string $modeFormation = null;

    public function __construct(array $data)
    {
        $this->id                = (int)($data['candidat_id']         ?? $data['IDCandidat']  ?? 0);
        $this->nomAr             = (string)($data['nom_ar']           ?? $data['Nom']         ?? '');
        $this->prenomAr          = (string)($data['prenom_ar']        ?? $data['Prenom']      ?? '');
        $this->nomFr             = (string)($data['nom_fr']           ?? $data['NomFr']       ?? '');
        $this->prenomFr          = (string)($data['prenom_fr']        ?? $data['PrenomFr']    ?? '');
        $this->nin               = $data['nin']                       ?? $data['Nin']         ?? null;
        $this->civ               = (int)($data['sexe']                ?? $data['Civ']         ?? 1);
        $this->telephone         = $data['telephone']                 ?? $data['Tel']         ?? null;
        $this->numeroInscription = $data['numero_inscription']        ?? $data['NumIns']      ?? null;
        $this->validation        = (int)($data['Validation']          ?? 0);
        $this->motifRefus        = $data['motif_refus']               ?? $data['Obs']         ?? null;
        $this->offreId           = (int)($data['IDOffre']             ?? 0);
        $this->dateInscription   = $data['dateInscr']                 ?? null;

        // Joined enrichment
        $this->specialiteAr  = $data['specialite_ar']  ?? null;
        $this->modeFormation = $data['mode_formation']  ?? null;
    }

    /** Map integer Validation to human-readable decision label */
    public function decisionLabel(): string
    {
        return match ($this->validation) {
            1       => 'مقبول',
            2       => 'غير مقبول',
            default => 'قيد الانتظار',
        };
    }

    /** Map Civ integer to Arabic gender label */
    public function sexeLabel(): string
    {
        return $this->civ === 1 ? 'ذكر' : 'أنثى';
    }
}
