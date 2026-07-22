<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Security\AuthorizationService;

class EnsurePermission
{
    private AuthorizationService $auth;

    public function __construct(AuthorizationService $auth)
    {
        $this->auth = $auth;
    }

    public function handle(Request $request, Closure $next, string $action)
    {
        $this->auth->authorize($action);

        return $next($request);
    }
}
