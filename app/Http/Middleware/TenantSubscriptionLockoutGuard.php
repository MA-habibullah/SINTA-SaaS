<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Modules\Core\Entities\Tenant;

class TenantSubscriptionLockoutGuard
{
    /**
     * Handle an incoming request and enforce tenant subscription validity.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user() ?? auth()->user();

        if (!$user) {
            return $next($request);
        }

        // 1. Super Admin Bypass (Platform Owner never gets locked out)
        if ((method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) || (isset($user->role) && $user->role === 'super_admin') || (method_exists($user, 'hasRole') && $user->hasRole('super_admin'))) {
            return $next($request);
        }

        $tenantId = session('tenant_id') ?? $user->tenant_id;

        if (!$tenantId || $tenantId === '00000000-0000-0000-0000-000000000000') {
            return $next($request);
        }

        // 2. Whitelist routes during subscription lockout
        $currentPath = trim($request->path(), '/');
        $whitelistPaths = [
            'subscription-expired',
            'logout',
            'login',
            'sekolah/billing',
            'sekolah/billing/pay',
            'sekolah/billing/invoice',
            'super-admin/login',
        ];

        foreach ($whitelistPaths as $white) {
            if ($currentPath === $white || str_starts_with($currentPath, $white . '/')) {
                return $next($request);
            }
        }

        // 3. Check Tenant Subscription Validity
        $tenant = Tenant::find($tenantId);

        if ($tenant && !$tenant->isSubscriptionActive()) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success'            => false,
                    'is_locked'          => true,
                    'subscription_error' => 'Masa aktif langganan sekolah telah berakhir. Silakan lakukan pembayaran perpanjangan paket.',
                    'redirect_url'       => '/subscription-expired',
                ], 402);
            }

            return redirect('/subscription-expired');
        }

        return $next($request);
    }
}
