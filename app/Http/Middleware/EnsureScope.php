<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Security\AuthorizationService;
use App\Security\ScopeResolver;
use Illuminate\Auth\Access\AuthorizationException;

class EnsureScope
{
    private ScopeResolver $scopeResolver;
    private AuthorizationService $auth;

    public function __construct(ScopeResolver $scopeResolver, AuthorizationService $auth)
    {
        $this->scopeResolver = $scopeResolver;
        $this->auth = $auth;
    }

    /**
     * Intercepts write requests to ensure targeted input establishments fall within allowed scopes.
     */
    public function handle(Request $request, Closure $next)
    {
        $user = $this->auth->user();

        if (!$user) {
            throw new AuthorizationException('يجب تسجيل الدخول أولاً. / Session context required.');
        }

        if ($user->isAdmin()) {
            return $next($request);
        }

        $allowedEtabs = $this->scopeResolver->resolveAllowedEtablissements($user);

        $etabKeys = ['etablissement_id', 'IDetablissement', 'IDEts_Form', 'IDEts_FormM', 'IDets_Form', 'etab_id'];
        foreach ($etabKeys as $key) {
            if ($request->has($key)) {
                $etabVal = (int)$request->input($key);
                if ($etabVal > 0 && !in_array($etabVal, $allowedEtabs)) {
                    app(\App\Security\SecurityAuditLogger::class)->log(
                        'SCOPE_VIOLATION',
                        "Unauthorized attempt to target establishment ID '{$etabVal}' via parameter '{$key}'",
                        'critical',
                        ['key' => $key, 'value' => $etabVal]
                    );
                    throw new AuthorizationException('غير مصرح لك بالوصول أو إدخال بيانات لهذه المؤسسة. / Scope mismatch.');
                }
            }
        }

        return $next($request);
    }
}
