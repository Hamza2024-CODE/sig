<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Security\ScopeResolver;
use App\Security\OwnershipResolver;
use App\Security\PermissionResolver;
use App\Security\PolicyResolver;
use App\Security\SecurityAuditLogger;
use App\Security\AuthorizationService;

class SecurityServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ScopeResolver::class, function ($app) {
            return new ScopeResolver();
        });

        $this->app->singleton(OwnershipResolver::class, function ($app) {
            return new OwnershipResolver($app->make(ScopeResolver::class));
        });

        $this->app->singleton(PermissionResolver::class, function ($app) {
            return new PermissionResolver();
        });

        $this->app->singleton(PolicyResolver::class, function ($app) {
            return new PolicyResolver(
                $app->make(PermissionResolver::class),
                $app->make(OwnershipResolver::class)
            );
        });

        $this->app->singleton(SecurityAuditLogger::class, function ($app) {
            return new SecurityAuditLogger();
        });

        $this->app->singleton(AuthorizationService::class, function ($app) {
            return new AuthorizationService(
                $app->make(PolicyResolver::class),
                $app->make(SecurityAuditLogger::class)
            );
        });
    }

    public function boot(): void
    {
        //
    }
}
