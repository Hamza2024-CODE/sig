<?php

namespace App\Security;

class UserContext
{
    public readonly int $id;
    public readonly string $username;
    public readonly string $nomComplet;
    public readonly int $etablissementId;
    public readonly int $dfepId;
    public readonly int $wilayaId;
    public readonly string $roleCode;
    public readonly array $permissions;
    public readonly array $rawContext;

    public function __construct(array $sessionUser)
    {
        $this->id = (int)($sessionUser['id'] ?? 0);
        $this->username = (string)($sessionUser['username'] ?? '');
        $this->nomComplet = (string)($sessionUser['nom_complet'] ?? '');
        $this->etablissementId = (int)($sessionUser['etablissement_id'] ?? $sessionUser['IDEts_Form'] ?? 0);
        $this->dfepId = (int)($sessionUser['iddfep'] ?? $sessionUser['IDDFEP'] ?? 0);
        $this->wilayaId = (int)($sessionUser['wilaya_id'] ?? $sessionUser['IDWilayaa'] ?? 0);
        $this->roleCode = strtolower((string)($sessionUser['role_code'] ?? ''));
        $this->permissions = (array)($sessionUser['permissions'] ?? []);
        $this->rawContext = $sessionUser;
    }

    public static function fromSession(): ?self
    {
        $sessionUser = session('user');
        if (!$sessionUser) {
            return null;
        }
        return new self($sessionUser);
    }

    public function isAdmin(): bool
    {
        return $this->roleCode === 'admin';
    }

    public function isDfep(): bool
    {
        return $this->roleCode === 'dfep';
    }

    public function isEtablissement(): bool
    {
        return in_array($this->roleCode, ['etablissement', 'directeur']);
    }
}
