<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Session\TokenMismatchException;
use Inertia\Inertia;

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
        // Handle CSRF Token Mismatch (419) -> Redirect Back with Flash Message
        $exceptions->render(function (TokenMismatchException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Sesi Anda telah kedaluwarsa. Silakan refresh halaman.'], 419);
            }
            return redirect()->back()->with('error', 'Sesi formulir telah kedaluwarsa. Silakan coba kembali.');
        });

        // Handle HTTP Exceptions (403 Forbidden, 404 Not Found)
        $exceptions->render(function (HttpException $e, $request) {
            $status = $e->getStatusCode();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'error'   => $e->getMessage() ?: "HTTP Error {$status}",
                    'code'    => $status,
                ], $status);
            }
        });
    })->create();
