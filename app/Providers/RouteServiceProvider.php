<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/dashboard';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     *
     * @return void
     */
    public function boot()
    {
        $this->configureRateLimiting();

        $this->routes(function () {
            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }

    /**
     * Configure the rate limiters for the application.
     *
     * @return void
     */
    protected function configureRateLimiting()
    {
        RateLimiter::for('api', function (Request $request) {
            // Check authenticated user merged in JwtAuthMiddleware
            $authUser = $request->get('authenticated_user');
            
            if ($authUser) {
                $role = $authUser['role_code'] ?? '';
                
                // Admin role has unlimited access
                if ($role === 'admin') {
                    return Limit::none();
                }
                
                // External API Clients
                if ($role === 'api_client') {
                    $clientName = strtolower($authUser['nom_complet'] ?? '');
                    // Give high priority government or ministerial clients 500 requests per minute
                    if (str_contains($clientName, 'mihnati') || str_contains($clientName, 'ministry') || str_contains($clientName, 'gov') || str_contains($clientName, 'takwin')) {
                        return Limit::perMinute(500)->by($authUser['id']);
                    }
                    return Limit::perMinute(100)->by($authUser['id']);
                }
                
                return Limit::perMinute(60)->by($authUser['id']);
            }
            
            // Fallback for unauthenticated requests
            return Limit::perMinute(60)->by($request->ip());
        });

        RateLimiter::for('login', function (Request $request) {
            $username = (string)$request->input('username');
            return Limit::perMinute(5)->by($username . $request->ip())->response(function (Request $request, array $headers) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'لقد تجاوزت الحد المسموح به من محاولات تسجيل الدخول. يرجى الانتظار دقيقة واحدة قبل المحاولة مجدداً.'
                    ], 429, $headers);
                }
                return redirect()->back()
                    ->withInput($request->only('username', 'login_type'))
                    ->with('flash_error', 'لقد تجاوزت الحد المسموح به من محاولات تسجيل الدخول. يرجى المحاولة بعد دقيقة واحدة. / Trop de tentatives.');
            });
        });

        // Strict rate limit for password reset requests (prevents enumeration & abuse)
        RateLimiter::for('password-reset', function (Request $request) {
            $username = (string)$request->input('username', $request->input('user_id', ''));
            return Limit::perMinutes(10, 3)->by($username . $request->ip())->response(function (Request $request, array $headers) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'تم تجاوز الحد المسموح به لطلبات استعادة كلمة المرور. يرجى المحاولة لاحقاً.'
                    ], 429, $headers);
                }
                return redirect()->back()->with('flash_error', 'تم تجاوز حد طلبات استعادة كلمة المرور. يرجى الانتظار 10 دقائق.');
            });
        });

        // SSE stream endpoint limiter (prevent resource exhaustion)
        RateLimiter::for('sse', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });
    }
}
