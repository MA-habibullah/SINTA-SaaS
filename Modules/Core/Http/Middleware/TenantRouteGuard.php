<?php

namespace Modules\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class TenantRouteGuard
{
    /**
     * Memeriksa apakah sekolah (tenant_id) dan role user aktif diizinkan mengakses menu/fitur tertentu.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        // 1. Jika belum login atau Super Admin, izinkan akses langsung
        if (!$user || $user->hasRole('super_admin')) {
            return $next($request);
        }

        $tenantId = session('tenant_id') ?? $user->tenant_id;
        if (empty($tenantId)) {
            abort(403, 'Akses Ditolak: Tenant tidak teridentifikasi.');
        }

        $path = '/' . ltrim($request->path(), '/');

        // 2. Bypass URL internal, assets, atau auth endpoints
        if (str_starts_with($path, '/api/') || in_array($path, ['/login', '/logout', '/up', '/dashboard'])) {
            return $next($request);
        }

        try {
            // 3. Ambil menu ID dari core.menus
            $menuId = DB::table('core.menus')
                ->where('url', $path)
                ->where('is_active', true)
                ->value('id');

            if (!$menuId) {
                return $next($request);
            }

            // 4. Periksa apakah menu aktif untuk tenant ini di core.tenant_menu_access
            $tenantAllowed = DB::table('core.tenant_menu_access')
                ->where('tenant_id', $tenantId)
                ->where('menu_id', $menuId)
                ->exists();

            if (!$tenantAllowed) {
                abort(403, '403 Fitur Belum Aktif: Menu ini dinonaktifkan untuk sekolah Anda.');
            }

            // 5. Periksa apakah role user ini memiliki izin di core.role_menu_access
            $roles = $user->getRoleNames()->toArray();
            if (!empty($roles)) {
                $roleAllowed = DB::table('core.role_menu_access as rma')
                    ->join('core.roles as r', 'rma.role_id', '=', 'r.id')
                    ->where('rma.menu_id', $menuId)
                    ->where(function ($q) use ($tenantId) {
                        $q->where('rma.tenant_id', $tenantId)
                          ->orWhere('rma.tenant_id', 'e8b1d4c2-9f3a-4e78-b125-6c7d8e9f0a12');
                    })
                    ->whereIn('r.nama_role', $roles)
                    ->exists();

                if (!$roleAllowed) {
                    abort(403, '403 Akses Ditolak: Peran akun Anda tidak memiliki izin mengakses fitur ini.');
                }
            }

        } catch (\Throwable $e) {
            report($e);
        }

        return $next($request);
    }
}
