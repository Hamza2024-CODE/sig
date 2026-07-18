<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // ✅ إجبار HTTPS بناءً على APP_URL في .env — الحل الأكيد خلف أي reverse proxy
        // هذا يجعل url() و asset() يولدون HTTPS بغض النظر عن ما يراه PHP داخلياً
        $configuredUrl = config('app.url', '');
        if (str_starts_with($configuredUrl, 'https://')) {
            URL::forceScheme('https');
        }


        // ✅ منع lazy loading في بيئة التطوير → يكشف N+1 فوراً
        if ($this->app->environment('local', 'testing')) {
            Model::preventLazyLoading(true);
        }

        // ✅ منع الإتلاف الصامت للبيانات (إذا حاول الكود وضع حقل غير موجود)
        Model::preventSilentlyDiscardingAttributes(
            $this->app->environment('local', 'testing')
        );

        // ✅ Paginator يستخدم Bootstrap CSS (متوافق مع الـ Blade views الحالية)
        Paginator::useBootstrap();

        // ✅ Cache Warming في الإنتاج — يُشغَّل مرة واحدة عند boot
        //    البيانات المرجعية (L1) تُحمَّل من DB إذا لم تكن في الكاش
        //    Subsequent requests → يقرأون من RAM مباشرة
        if ($this->app->environment('production', 'development')) {
            try {
                \App\Services\ReferenceCache::wilayas();          // يُبني الكاش إذا فارغ
                \App\Services\ReferenceCache::modesFormation();   // أنماط التكوين
                \App\Services\ReferenceCache::anneesFormation();  // السنوات التكوينية
            } catch (\Throwable $e) {
                // لا يوقف البوت — يُكتفى بالتسجيل
                Log::warning('[AppServiceProvider] Cache warm skipped: ' . $e->getMessage());
            }
        }

        // ✅ Register Security events & listeners
        \Illuminate\Support\Facades\Event::listen(
            \App\Events\SecurityEventTriggered::class,
            \App\Listeners\LogSecurityActivity::class
        );

        // ✅ Centralized Gates for RBAC (OWASP / ISO 27001)
        \Illuminate\Support\Facades\Gate::define('admin', function ($user = null) {
            $sessionUser = session('user');
            return strtolower($sessionUser['role_code'] ?? '') === 'admin';
        });

        \Illuminate\Support\Facades\Gate::define('finance', function ($user = null) {
            $sessionUser = session('user');
            return in_array(strtolower($sessionUser['role_code'] ?? ''), ['admin', 'finance']);
        });

        \Illuminate\Support\Facades\Gate::define('hr', function ($user = null) {
            $sessionUser = session('user');
            return in_array(strtolower($sessionUser['role_code'] ?? ''), ['admin', 'hr', 'drh']);
        });

        \Illuminate\Support\Facades\Gate::define('central', function ($user = null) {
            $sessionUser = session('user');
            return in_array(strtolower($sessionUser['role_code'] ?? ''), ['admin', 'central', 'high_admin', 'secretaire_general', 'ministre']);
        });

        \Illuminate\Support\Facades\Gate::define('dfep', function ($user = null) {
            $sessionUser = session('user');
            return in_array(strtolower($sessionUser['role_code'] ?? ''), ['admin', 'dfep', 'central', 'high_admin']);
        });

        \Illuminate\Support\Facades\Gate::define('etablissement', function ($user = null) {
            $sessionUser = session('user');
            return in_array(strtolower($sessionUser['role_code'] ?? ''), ['admin', 'etablissement', 'directeur']);
        });
    }
}