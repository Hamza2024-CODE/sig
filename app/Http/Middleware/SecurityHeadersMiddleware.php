<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * SecurityHeadersMiddleware
 * Protects against XSS, Clickjacking, MIME-sniffing
 * Standards: OWASP Security Headers / ISO 27001
 */
class SecurityHeadersMiddleware
{
    protected array $allowedCdnDomains = [
        'cdn.jsdelivr.net',
        'cdnjs.cloudflare.com',
        'fonts.googleapis.com',
        'fonts.gstatic.com',
        'unpkg.com',
        '*.basemaps.cartocdn.com',
        'tile.openstreetmap.org',
    ];

    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        if (!method_exists($response, 'header')) { return $response; }

        $isProduction = app()->environment('production');

        // 1. Clickjacking protection
        $response->header('X-Frame-Options', 'SAMEORIGIN');
        // 2. MIME-type sniffing
        $response->header('X-Content-Type-Options', 'nosniff');
        // 3. XSS filter (older browsers)
        $response->header('X-XSS-Protection', '1; mode=block');
        // 4. Referrer leakage
        $response->header('Referrer-Policy', 'strict-origin-when-cross-origin');
        // 5. Browser APIs restriction
        $response->header('Permissions-Policy', 'camera=(), microphone=(), geolocation=(self), payment=()');
        // 6. HSTS (only in production with SSL)
        if ($isProduction) {
            $response->header('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }
        // 7. CSP
        $cdn = implode(' ', $this->allowedCdnDomains);
        $self = "'self'";
        $cspDirectives = [
            "default-src {$self}",
            "script-src {$self} 'unsafe-inline' 'unsafe-eval' {$cdn}",
            "style-src {$self} 'unsafe-inline' {$cdn} fonts.googleapis.com",
            "font-src {$self} data: fonts.gstatic.com {$cdn}",
            // السماح لـ http: أيضاً خلال فترة الانتقال (upgrade-insecure-requests ترفعه لـ https تلقائياً)
            "img-src {$self} data: blob: http: https: {$cdn} *.openstreetmap.org *.cartocdn.com",
            "connect-src {$self} {$cdn}",
            "manifest-src {$self}",
            "worker-src {$self} blob:",
            "object-src 'none'",
            "frame-src 'none'",
            "base-uri {$self}",
            // form-action يسمح صراحةً بـ https: لأن PHP خلف proxy قد يرى HTTP
            "form-action {$self} https:",
            "upgrade-insecure-requests",
        ];
        $csp = implode('; ', $cspDirectives);
        // Report-Only in development, Enforce in production
        if (!$isProduction) {
            $response->header('Content-Security-Policy-Report-Only', $csp);
        } else {
            $response->header('Content-Security-Policy', $csp);
        }
        // 8. Remove info leak headers
        $response->headers->remove('X-Powered-By');
        $response->headers->remove('Server');
        // 9. Prevent caching for sensitive dashboard pages
        if ($request->is('*/dashboard*') || $request->is('*/security*')) {
            $response->header('Cache-Control', 'no-store, no-cache, must-revalidate, private');
            $response->header('Pragma', 'no-cache');
        }
        return $response;
    }
}