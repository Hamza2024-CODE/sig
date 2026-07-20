<?php

namespace App\Services\Employee;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * EmployeeScopeService
 *
 * Centralises ALL role-based scope and authorization logic for the employee space.
 * Eliminates the 15+ repeated if/elseif role checks scattered across the controller.
 *
 * Roles and their data access:
 *  - admin / central / secretaire_general / high_admin / ministre : unrestricted
 *  - dfep         : limited to their IDDFEP (wilaya)
 *  - etablissement / directeur : limited to their IDetablissement
 *  - employee     : only their own record
 */
class EmployeeScopeService
{
    /** Roles that bypass wilaya/etab scoping */
    private const GLOBAL_ROLES = ['admin', 'central', 'secretaire_general', 'high_admin', 'ministre'];

    /** Roles that can see NIN and NSS */
    private const HIGH_PRIV_ROLES = ['admin', 'central', 'secretaire_general'];

    /** Roles that can see DateNais, Tel, Adres */
    private const MID_PRIV_ROLES  = ['admin', 'central', 'secretaire_general', 'dfep', 'directeur', 'high_admin', 'etablissement'];

    /** Fields NEVER returned regardless of role */
    private const NEVER_EXPOSE = [
        'MotDePasse', 'password', 'nin_hash',
        'google2fa_secret', 'remember_token', 'Validation', 'ValidationDfp',
    ];

    // ─────────────────────────────────────────────
    //  Scope helpers
    // ─────────────────────────────────────────────

    /**
     * Build the current user's scope array.
     */
    public function getScope(): array
    {
        $user   = session('user') ?? [];
        $role   = strtolower($user['role_code'] ?? 'user');
        $iddfep = (int)($user['iddfep'] ?? $user['IDDFEP'] ?? 0);
        $etabId = (int)($user['etablissement_id'] ?? 0);

        return compact('role', 'iddfep', 'etabId', 'user');
    }

    /**
     * Return the authenticated employee's own ID.
     */
    public function getAuthenticatedEmployeeId(): ?int
    {
        if (function_exists('auth') && auth()->check() && auth()->user()) {
            return auth()->user()->employee_id ?? auth()->user()->id;
        }
        return session('user')['id'] ?? session('user')['employee_id'] ?? null;
    }

    // ─────────────────────────────────────────────
    //  Filter builders (replaces repeated clauses in index())
    // ─────────────────────────────────────────────

    /**
     * Apply role-based scope clauses to a filter array.
     * Returns ['clauses' => [...], 'params' => [...]] ready for PDO.
     */
    public function buildScopeClauses(array $scope): array
    {
        $clauses = ['1=1'];
        $params  = [];

        if ($scope['role'] === 'dfep' && $scope['iddfep']) {
            $clauses[] = '(et.IDDFEP = ? OR et_form.IDDFEP = ?)';
            $params[]  = $scope['iddfep'];
            $params[]  = $scope['iddfep'];
        } elseif (in_array($scope['role'], ['etablissement', 'directeur']) && $scope['etabId']) {
            $clauses[] = '(enc.IDetablissement = ? OR enc.IDEts_Form = ?)';
            $params[]  = $scope['etabId'];
            $params[]  = $scope['etabId'];
        } elseif ($scope['role'] === 'employee') {
            $clauses[] = 'enc.IDEncadrement = ?';
            $params[]  = (int)$this->getAuthenticatedEmployeeId();
        }

        return compact('clauses', 'params');
    }

    /**
     * Apply user-supplied search/filter request params on top of scope clauses.
     */
    public function applyRequestFilters(Request $request, array $clauses, array $params): array
    {
        $search = $request->query('filter_search');
        $wilaya = $request->query('filter_wilaya');
        $type   = $request->query('filter_type');
        $etab   = $request->query('filter_etab');

        if (!empty($search)) {
            $clauses[] = '(enc.Nom LIKE ? OR enc.Prenom LIKE ? OR enc.NomFr LIKE ? OR enc.PrenomFr LIKE ? OR enc.IDEncadrement = ? OR enc.nin = ?)';
            $t = "%$search%";
            array_push($params, $t, $t, $t, $t, (int)$search, $search);
        }

        if (!empty($wilaya)) {
            $clauses[] = '(et.IDDFEP = ? OR et_form.IDDFEP = ?)';
            $params[]  = (int)$wilaya;
            $params[]  = (int)$wilaya;
        }

        if (!empty($type)) {
            $map = [
                'directorate' => '(et.IDNature_etsF = 5 OR et_form.IDNature_etsF = 5)',
                'centre'      => '(et.IDNature_etsF IN (8, 9) OR et_form.IDNature_etsF IN (8, 9))',
                'institute'   => '(et.IDNature_etsF IN (6, 7, 11, 13) OR et_form.IDNature_etsF IN (6, 7, 11, 13))',
                'private'     => '(et.IDNature_etsF = 12 OR et_form.IDNature_etsF = 12)',
            ];
            if (isset($map[$type])) {
                $clauses[] = $map[$type];
            }
        }

        if (!empty($etab)) {
            $clauses[] = '(enc.IDetablissement = ? OR enc.IDEts_Form = ?)';
            $params[]  = (int)$etab;
            $params[]  = (int)$etab;
        }

        return compact('clauses', 'params');
    }

    // ─────────────────────────────────────────────
    //  Access authorisation (replaces scattered 403 checks)
    // ─────────────────────────────────────────────

    /**
     * Verify the requesting user may READ the given employee record.
     * Throws AccessDeniedHttpException on failure (caught by Laravel exception handler).
     */
    public function authorizeRead(array $employee, array $scope): void
    {
        $this->checkAccess($employee, $scope, 'قراءة');
    }

    /**
     * Verify the requesting user may WRITE/UPDATE the given employee record.
     * Also verifies that the requested $id matches the scoped employee.
     */
    public function authorizeWrite(array $existingRecord, int $requestedId, array $scope): void
    {
        if ($scope['role'] === 'employee') {
            if ($requestedId !== (int)$this->getAuthenticatedEmployeeId()) {
                $this->deny('غير مصرح لك بتعديل بيانات موظف آخر');
            }
            return;
        }

        if ($scope['role'] === 'dfep' && $scope['iddfep']) {
            if ((int)($existingRecord['IDDFEP'] ?? 0) !== $scope['iddfep'] && (int)($existingRecord['id_wilaya'] ?? 0) !== $scope['iddfep']) {
                $this->deny('غير مصرح لك بتعديل بيانات هذا الموظف');
            }
            return;
        }

        if (in_array($scope['role'], ['etablissement', 'directeur']) && $scope['etabId']) {
            $empEtab = (int)($existingRecord['IDetablissement'] ?? 0);
            $empFormEtab = (int)($existingRecord['IDEts_Form'] ?? 0);
            if ($empEtab !== $scope['etabId'] && $empFormEtab !== $scope['etabId']) {
                $this->deny('غير مصرح لك بتعديل بيانات هذا الموظف');
            }
        }
    }

    private function checkAccess(array $employee, array $scope, string $action): void
    {
        if (in_array($scope['role'], self::GLOBAL_ROLES)) {
            return; // unrestricted
        }

        if ($scope['role'] === 'employee') {
            $ownId = (int)$this->getAuthenticatedEmployeeId();
            if ((int)($employee['IDEncadrement'] ?? 0) !== $ownId) {
                $this->deny("غير مصرح لك بـ$action بيانات موظف آخر");
            }
            return;
        }

        if ($scope['role'] === 'dfep' && $scope['iddfep']) {
            $empWilaya = (int)($employee['id_wilaya'] ?? 0);
            $empFormWilaya = (int)($employee['form_id_wilaya'] ?? $empWilaya);
            if ($empWilaya !== $scope['iddfep'] && $empFormWilaya !== $scope['iddfep']) {
                $this->deny("غير مصرح لك بـ$action بيانات موظفي ولاية أخرى");
            }
            return;
        }

        if (in_array($scope['role'], ['etablissement', 'directeur']) && $scope['etabId']) {
            $empEtab = (int)($employee['IDetablissement'] ?? 0);
            $empFormEtab = (int)($employee['IDEts_Form'] ?? 0);
            if ($empEtab !== $scope['etabId'] && $empFormEtab !== $scope['etabId']) {
                $this->deny("غير مصرح لك بـ$action بيانات موظف مؤسسة أخرى");
            }
        }
    }

    private function deny(string $message): void
    {
        Log::warning('EmployeeScopeService: access denied', [
            'user'    => session('user.username', 'unknown'),
            'message' => $message,
        ]);
        throw new AccessDeniedHttpException($message);
    }

    // ─────────────────────────────────────────────
    //  Response sanitization (replaces sanitizeEmployeeResponse)
    // ─────────────────────────────────────────────

    /**
     * Strip sensitive fields from the employee array based on the caller's role.
     * MUST be called as the LAST step before returning JSON.
     */
    public function sanitize(array $employee, string $role): array
    {
        // Always remove — never sent to any client
        foreach (self::NEVER_EXPOSE as $field) {
            unset($employee[$field]);
        }

        // NIN + NSS: high-privilege only
        if (!in_array($role, self::HIGH_PRIV_ROLES, true)) {
            unset($employee['nin'], $employee['nss']);
        }

        // Personal contact details: mid-privilege+
        if (!in_array($role, self::MID_PRIV_ROLES, true)) {
            unset(
                $employee['DateNais'],
                $employee['LieuNais'],
                $employee['Tel'],
                $employee['Adres'],
                $employee['numActNaiss']
            );
        }

        return $employee;
    }

    // ─────────────────────────────────────────────
    //  Convenience role checks
    // ─────────────────────────────────────────────

    public function isGlobalRole(string $role): bool
    {
        return in_array($role, self::GLOBAL_ROLES, true);
    }

    public function canSeeSensitiveData(string $role): bool
    {
        return in_array($role, self::HIGH_PRIV_ROLES, true);
    }

    /**
     * Generate a cryptographically secure API key for the session.
     * Stored in session — replace with DB-backed Sanctum token for production.
     */
    public function generateApiKey(): string
    {
        return 'sgfep_' . bin2hex(random_bytes(32)); // 64 hex chars — unpredictable
    }
}
