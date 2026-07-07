<?php

namespace App\Domains\Academic\Services;

use App\Core\Database;
use App\Domains\Academic\Repositories\DiplomeRepository;
use App\Domains\Security\AuthorizationService;
use App\Audit\AuditService;
use App\Events\EventDispatcher;
use Exception;
use PDO;

class DiplomeService
{
    protected DiplomeRepository $repository;
    protected AuthorizationService $authService;
    protected AuditService $auditService;

    public function __construct(
        DiplomeRepository $repository,
        AuthorizationService $authService,
        AuditService $auditService
    ) {
        $this->repository = $repository;
        $this->authService = $authService;
        $this->auditService = $auditService;
    }

    /**
     * Generate a graduation diploma for a student securely under policies checks and concurrency locks
     */
    public function generateDiploma(int $stagiaireId, array $currentUser): void
    {
        // 1. General permission authorization check
        $this->authService->authorize($currentUser, 'diploma.generate');

        $roleCode = strtolower($currentUser['role_code'] ?? '');
        $etabId = $currentUser['etablissement_id'] ?? null;
        $dfepId = $currentUser['iddfep'] ?? null;

        $etabFilter = "";
        $params = [$stagiaireId];

        if ($roleCode === 'dfep' && $dfepId) {
            $etabFilter = " AND o.IDEts_Form IN (SELECT IDetablissement FROM etablissement WHERE IDDFEP = ?) ";
            $params[] = $dfepId;
        } elseif (in_array($roleCode, ['etablissement', 'directeur', 'formateur']) && $etabId) {
            $etabFilter = " AND o.IDEts_Form = ? ";
            $params[] = $etabId;
        }

        // 2. Fetch trainee profile details
        $trainee = $this->repository->findTraineeDetails($stagiaireId, $etabFilter, $params);
        if (!$trainee) {
            throw new Exception("متربص غير موجود أو لا تملك الصلاحية للوصول إليه / Trainee not found.");
        }

        // 3. Strict spatial ownership check (checks establishment match for directors/lecturers)
        $this->authService->authorize($currentUser, 'diploma.generate', [
            'stagiaire_id' => $stagiaireId,
            'etablissement_id' => $trainee['etablissement_id']
        ]);

        // 4. Calculate grades and references
        $moyRow = $this->repository->getLatestSemesterAverage($stagiaireId);
        $ssId = ($moyRow && !empty($moyRow['IDapprenant_Section_semstre'])) 
            ? (int)$moyRow['IDapprenant_Section_semstre'] 
            : null;

        $semesters = $this->repository->getAllSemesterAverages($stagiaireId);
        $validAverages = [];
        foreach ($semesters as $sem) {
            $avg = (float)($sem['MoyApr'] ?? 0);
            if ($avg > 0) {
                $validAverages[] = $avg;
            }
        }

        $config = \App\Helpers\GradingConfigHelper::read();
        $gradingService = new \App\Domains\Academic\Services\GradingSystemService();
        $calc = $gradingService->calculateGraduationGpa($validAverages, null, null, false, $config);
        $moyenne = $calc['gpa'] > 0 ? $calc['gpa'] : (($moyRow && $moyRow['MoyApr'] > 0) ? (float)$moyRow['MoyApr'] : 12.50);

        $secSemId = null;
        if (!empty($trainee['section_id'])) {
            $secSemId = $this->repository->getLatestSectionSemester((int)$trainee['section_id']);
        }
        if (empty($secSemId)) {
            $secSemId = null;
        }

        $numDiplome = 'DIPL' . date('Y') . str_pad((string)$trainee['id'], 6, '0', STR_PAD_LEFT);
        
        $mentionId = 1;
        if ($moyenne >= 16) {
            $mentionId = 4;
        } elseif ($moyenne >= 14) {
            $mentionId = 3;
        } elseif ($moyenne >= 12) {
            $mentionId = 2;
        }

        // Self-healing database check: ensure the mention exists, otherwise seed standard ones
        if (!$this->repository->checkMentionExists($mentionId)) {
            $this->repository->seedStandardMentions();
        }

        // 5. Execute write operations inside transactions
        Database::getInstance()->transaction(function (PDO $pdo) use ($stagiaireId, $numDiplome, $moyenne, $mentionId, $ssId, $secSemId, $currentUser) {
            $existing = $this->repository->checkExistingDiplomaForUpdate($pdo, $stagiaireId);

            if ($existing) {
                // Update
                $this->repository->updateDiploma($pdo, $numDiplome, $moyenne, $mentionId, $ssId, $secSemId, $stagiaireId);
                
                // Write Audit record
                $this->auditService->log(
                    $currentUser['username'] ?? 'unknown',
                    'UPDATE',
                    'apprenant_fin',
                    (string)$existing['IDApprenant_Fin'],
                    $existing,
                    ['Numdiplome' => $numDiplome, 'MoyGen' => $moyenne, 'IDmention' => $mentionId]
                );
            } else {
                // Lock read and insert
                $newId = $this->repository->getNextIdWithLock($pdo);
                $this->repository->insertDiploma($pdo, $newId, $stagiaireId, $ssId, $numDiplome, $moyenne, $mentionId, $secSemId);
                
                // Write Audit record
                $this->auditService->log(
                    $currentUser['username'] ?? 'unknown',
                    'CREATE',
                    'apprenant_fin',
                    (string)$newId,
                    null,
                    ['IDApprenant_Fin' => $newId, 'IDapprenant' => $stagiaireId, 'Numdiplome' => $numDiplome]
                );
            }

            // 6. Dispatch Domain Event to listeners
            EventDispatcher::getInstance()->dispatch('DiplomaGenerated', [
                'stagiaire_id' => $stagiaireId,
                'diploma_num' => $numDiplome,
                'average' => $moyenne,
                'generated_by' => $currentUser['username'] ?? 'system'
            ]);
        });
    }
}
