<?php

namespace App\Security;

use Illuminate\Auth\Access\AuthorizationException;

class AuthorizationService
{
    private PolicyResolver $policyResolver;
    private SecurityAuditLogger $auditLogger;
    private ?UserContext $userContext = null;

    public function __construct(PolicyResolver $policyResolver, SecurityAuditLogger $auditLogger)
    {
        $this->policyResolver = $policyResolver;
        $this->auditLogger = $auditLogger;
        $this->userContext = UserContext::fromSession();
    }

    /**
     * Retrieves the current immutable UserContext.
     */
    public function user(): ?UserContext
    {
        return $this->userContext;
    }

    /**
     * Checks if the UserContext is authorized for the action. Returns boolean.
     */
    public function check(string $action, $resource = null): bool
    {
        if (!$this->userContext) {
            return false;
        }
        return $this->policyResolver->check($this->userContext, $action, $resource);
    }

    /**
     * Authorizes the action. Logs security events and throws an exception on failure.
     *
     * @throws AuthorizationException
     */
    public function authorize(string $action, $resource = null): void
    {
        if (!$this->userContext) {
            $this->auditLogger->log('UNAUTHENTICATED_ACCESS', "Guest attempted '{$action}' without active session", 'critical');
            throw new AuthorizationException('يجب تسجيل الدخول للوصول لهذه الصفحة. / Session required.');
        }

        if (!$this->policyResolver->check($this->userContext, $action, $resource)) {
            if ($resource !== null) {
                $type = get_class($resource);
                $id = $resource->id ?? $resource->IDetablissement ?? $resource->IDapprenant ?? $resource->IDCandidat ?? 'N/A';
                $this->auditLogger->logIdorAttempt($type, $id, $action);
            } else {
                $this->auditLogger->logPermissionDenied($action);
            }

            throw new AuthorizationException('غير مصرح لك بالقيام بهذا الإجراء. / Unauthorized.');
        }
    }
}
