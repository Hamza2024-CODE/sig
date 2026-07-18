<?php

namespace App\Domains\Academic\Services;

use App\Core\Database;
use App\Domains\Academic\Repositories\CandidatRepository;
use App\Domains\Security\AuthorizationService;
use App\Audit\AuditService;
use App\Events\EventDispatcher;
use Exception;
use PDO;

/**
 * CandidatService
 *
 * Business logic for candidate (pre-inscription) validation workflow.
 * All SQL delegation goes through CandidatRepository.
 * Business rules are preserved from legacy CandidateController.
 */
class CandidatService
{
    protected CandidatRepository  $repository;
    protected AuthorizationService $authService;
    protected AuditService        $auditService;

    public function __construct(
        CandidatRepository  $repository,
        AuthorizationService $authService,
        AuditService        $auditService
    ) {
        $this->repository  = $repository;
        $this->authService = $authService;
        $this->auditService = $auditService;
    }

    // ─── Queries ──────────────────────────────────────────────────────────────

    /**
     * Build the role-scoped WHERE clause and delegate to the repository.
     * Business rules preserved from legacy CandidateController::index()
     */
    public function listCandidats(array $user, string $statusFilter = 'all', array $filters = []): array
    {
        [$extraWhere, $params] = $this->buildRoleFilter($user);

        // Dynamic filters (Search, Wilaya, Etablissement, Mode, Offre)
        if (!empty($filters['search'])) {
            $extraWhere .= " AND (c.Nom LIKE ? OR c.Prenom LIKE ? OR c.Nin LIKE ?)";
            $likeVal = '%' . trim($filters['search']) . '%';
            $params[] = $likeVal;
            $params[] = $likeVal;
            $params[] = $likeVal;
        }

        if (!empty($filters['wilaya_id'])) {
            $extraWhere .= " AND o.IDEts_Form IN (SELECT IDetablissement FROM etablissement WHERE IDDFEP IN (SELECT IDDFEP FROM dfep WHERE IDWilayaa = ?))";
            $params[] = (int)$filters['wilaya_id'];
        }

        if (!empty($filters['etablissement_id'])) {
            $extraWhere .= " AND o.IDEts_Form = ?";
            $params[] = (int)$filters['etablissement_id'];
        }

        if (!empty($filters['mode_id'])) {
            $extraWhere .= " AND o.IDMode_formation = ?";
            $params[] = (int)$filters['mode_id'];
        }

        if (!empty($filters['offre_id'])) {
            $extraWhere .= " AND c.IDOffre = ?";
            $params[] = (int)$filters['offre_id'];
        }

        $rows = $this->repository->findAllFiltered($extraWhere, $params, $statusFilter);

        // Map WinDev Validation integer to human-readable labels (legacy mapping)
        foreach ($rows as &$c) {
            $c['decision']      = ($c['Validation'] == 1) ? 'مقبول'
                                : (($c['Validation'] == 2) ? 'غير مقبول' : 'قيد الانتظار');
            $c['statut_dossier'] = ($c['Validation'] == 1) ? 'complet' : 'incomplet';
            $c['sexe']           = ($c['sexe'] == 1) ? 'ذكر' : 'أنثى';
            // Document paths are managed externally (takwin.dz API)
            $c['cert_scolarite_path'] = null;
            $c['cert_medical_path']   = null;
            $c['acte_naissance_path'] = null;
            $c['photo_path']          = null;
            $c['diplome_vise']        = 'BTS';
        }

        return $rows;
    }

    // ─── Commands ─────────────────────────────────────────────────────────────

    /**
     * Process a validation decision for a candidat.
     * Business rules preserved exactly from legacy CandidateController::action()
     *
     * @throws Exception
     */
    public function processValidation(
        array  $user,
        int    $candidatId,
        string $decision,      // 'مقبول' or 'غير مقبول'
        ?string $motifRefus
    ): void {
        $this->authService->authorize($user, 'inscriptions.validate');

        $validationStatus = ($decision === 'مقبول') ? 1 : 2;

        Database::getInstance()->transaction(function (PDO $pdo) use (
            $candidatId, $validationStatus, $motifRefus, $user
        ) {
            // 1. Update candidat Validation status
            $this->repository->updateValidation($pdo, $candidatId, $validationStatus, $motifRefus);

            $before = ['IDCandidat' => $candidatId, 'Validation' => null];

            // 2. If approved → auto-insert in apprenant table (if not already there)
            if ($validationStatus === 1) {
                if (!$this->repository->existsApprenant($pdo, $candidatId)) {
                    // Generate unique matricule (legacy logic preserved)
                    $matricule = 'M' . date('Y') . str_pad((string)rand(1000, 9999), 4, '0', STR_PAD_LEFT);
                    $this->repository->insertApprenant($pdo, $candidatId, $matricule);

                    $this->auditService->log(
                        $user['username'] ?? 'unknown',
                        'CREATE',
                        'apprenant',
                        (string)$candidatId,
                        null,
                        ['IDCandidat' => $candidatId, 'Nccp' => $matricule]
                    );
                }
            }

            // 3. Audit the candidat update
            $this->auditService->log(
                $user['username'] ?? 'unknown',
                'UPDATE',
                'candidat',
                (string)$candidatId,
                $before,
                ['Validation' => $validationStatus, 'Obs' => $motifRefus]
            );

            // 4. Domain event
            EventDispatcher::getInstance()->dispatch('CandidatValidated', [
                'candidat_id' => $candidatId,
                'status'      => $validationStatus,
                'validated_by' => $user['username'] ?? 'system',
            ]);
        });
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Build a role-scoped SQL WHERE fragment + params array.
     * Logic preserved exactly from legacy CandidateController::index()
     */
    public function buildRoleFilter(array $user): array
    {
        $roleCode = strtolower($user['role_code'] ?? '');
        $dfepId   = $user['iddfep'] ?? null;
        $etabId   = $user['etablissement_id'] ?? null;

        $extraWhere = '';
        $params     = [];

        if ($roleCode === 'dfep' && $dfepId) {
            $extraWhere = " AND o.IDEts_Form IN (SELECT IDetablissement FROM etablissement WHERE IDDFEP = ?)";
            $params[]   = $dfepId;
        } elseif (in_array($roleCode, ['etablissement', 'directeur', 'formateur']) && $etabId) {
            $extraWhere = " AND o.IDEts_Form = ?";
            $params[]   = $etabId;
        }

        if ((int)($user['IDMode_formation'] ?? 0) === 10) {
            $extraWhere .= " AND o.IDMode_formation = 10";
        } elseif (strtolower($user['username'] ?? '') === 'sdtpp') {
            $extraWhere .= " AND o.IDMode_formation != 10";
        }

        return [$extraWhere, $params];
    }
}
