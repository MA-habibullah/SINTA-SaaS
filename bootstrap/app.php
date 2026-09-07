<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            \Modules\Core\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias([
            'tenant.guard'   => \Modules\Core\Http\Middleware\TenantRouteGuard::class,
            'storage.guard'  => \Modules\Core\Http\Middleware\TenantStorageGuard::class,
            'role'           => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission'     => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_perm'   => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Logging central & JSON format exception handling
    })->create();
