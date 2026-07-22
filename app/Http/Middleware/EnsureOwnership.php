<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Database\ScopedQuery;

class EnsureOwnership
{
    /**
     * Resolves the target resource using the Scoped Query Layer.
     * Guarantees IDOR protection.
     */
    public function handle(Request $request, Closure $next, string $modelClass, string $parameterName = 'id')
    {
        $resourceId = $request->route($parameterName) ?? $request->input($parameterName);

        if ($resourceId) {
            // Enforces database query scoping. Throws ModelNotFoundException (404) if scope mismatch.
            $model = ScopedQuery::for($modelClass)->findOrFail($resourceId);

            // Inject the loaded instance into request attributes to optimize Controller queries
            $request->attributes->set($parameterName . '_model', $model);
        }

        return $next($request);
    }
}
