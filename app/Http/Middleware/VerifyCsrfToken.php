<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        'api/*',
        'sig/api/*',
        'login/get-employee-code',
        'sig/login/get-employee-code',
    ];

    /**
     * Get the CSRF token from the request.
     *
     * Overridden to accept both Laravel's standard '_token' parameter and
     * the legacy 'csrf_token' parameter used in some of our blade views/legacy forms.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    protected function getTokenFromRequest($request)
    {
        $token = $request->input('_token') ?: $request->input('csrf_token');

        if (! $token) {
            return parent::getTokenFromRequest($request);
        }

        return $token;
    }
}
