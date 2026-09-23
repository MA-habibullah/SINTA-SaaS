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
     * Memeriksa otorisasi 2-Lapis SINTA:
     * Lapisan 1: Akses Fitur Sekolah / Tenant (core.tenant_menu_access) - Plafon Tertinggi
     * Lapisan 2: Manajemen User & Hak Akses / RBAC (core.role_menu_access) - Hak Peran Pengguna
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        // 1. Jika belum login atau Super Admin, izinkan akses bypass langsung
        if (!$user) {
            return $next($request);
        }

        $roleName = strtolower(is_object($user->role) ? ($user->role->nama_role ?? '') : (string)$user->role);
        $isSuperAdmin = $user->tenant_id === '00000000-0000-0000-0000-000000000000' 
            || $roleName === 'super_admin' 
            || (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin())
            || (method_exists($user, 'hasRole') && $user->hasRole('super_admin'));

        if ($isSuperAdmin) {
            return $next($request);
        }

        $tenantId = session('tenant_id') ?? $user->tenant_id;
        if (empty($tenantId)) {
            abort(403, 'Akses Ditolak: Tenant sekolah tidak teridentifikasi.');
        }

        $rawPath = '/' . ltrim($request->path(), '/');

        // 2. Bypass URL internal, assets, API publik, atau endpoint auth dasar
        if (str_starts_with($rawPath, '/api/') || in_array($rawPath, ['/login', '/logout', '/up', '/dashboard', '/subscription-expired'])) {
            return $next($request);
        }

        try {
            // 3. Cari menu terdekat di tabel core.menus (mencocokkan exact match atau route prefix)
            $menu = DB::table('core.menus')
                ->where('is_active', true)
                ->where(function ($q) use ($rawPath) {
                    $q->where('url', $rawPath)
                      ->orWhereRaw('? LIKE CONCAT(url, "/%")', [$rawPath]);
                })
                ->orderByRaw('LENGTH(url) DESC')
                ->first();

            if (!$menu || empty($menu->url) || $menu->url === '#') {
                return $next($request);
            }

            $menuId = $menu->id;

            // =========================================================================
            // LAPISAN 1: AKSES FITUR SEKOLAH (TENANT FEATURE ENTITLEMENT)
            // =========================================================================
            // Jika Super Admin mengatur pembatasan fitur untuk sekolah ini di core.tenant_menu_access:
            $hasCustomTenantConfig = DB::table('core.tenant_menu_access')
                ->where('tenant_id', $tenantId)
                ->exists();

            if ($hasCustomTenantConfig) {
                $tenantAllowed = DB::table('core.tenant_menu_access')
                    ->where('tenant_id', $tenantId)
                    ->where('menu_id', $menuId)
                    ->exists();

                if (!$tenantAllowed) {
                    abort(403, "403 Fitur Dinonaktifkan: Menu '{$menu->nama_menu}' dinonaktifkan pada paket langganan sekolah Anda.");
                }
            }

            // =========================================================================
            // LAPISAN 2: MANAJEMEN USER & HAK AKSES (ROLE-BASED ACCESS CONTROL / RBAC)
            // =========================================================================
            $roleId = $user->role_id;
            if ($roleId) {
                $roleAllowed = DB::table('core.role_menu_access')
                    ->where('role_id', $roleId)
                    ->where('menu_id', $menuId)
                    ->where(function ($q) use ($tenantId) {
                        $q->where('tenant_id', $tenantId)
                          ->orWhere('tenant_id', '00000000-0000-0000-0000-000000000000');
                    })
                    ->exists();

                if (!$roleAllowed) {
                    abort(403, "403 Akses Ditolak: Peran akun Anda ({$roleName}) tidak memiliki izin untuk membuka fitur '{$menu->nama_menu}'.");
                }

                // =========================================================================
                // LAPISAN 3: GRANULAR NAVTAB RBAC GUARD
                // =========================================================================
                if ($request->filled('tab')) {
                    $tabKey = $request->query('tab');
                    $tabMenu = DB::table('core.menus')
                        ->where('is_active', true)
                        ->where('url', "{$rawPath}?tab={$tabKey}")
                        ->first();

                    if ($tabMenu) {
                        $tabAllowed = DB::table('core.role_menu_access')
                            ->where('role_id', $roleId)
                            ->where('menu_id', $tabMenu->id)
                            ->where(function ($q) use ($tenantId) {
                                $q->where('tenant_id', $tenantId)
                                  ->orWhere('tenant_id', '00000000-0000-0000-0000-000000000000');
                            })
                            ->exists();

                        if (!$tabAllowed) {
                            abort(403, "403 Akses Ditolak: Peran akun Anda ({$roleName}) tidak memiliki izin untuk membuka sub-tab '{$tabMenu->nama_menu}'.");
                        }
                    }
                }
            }

        } catch (\Throwable $e) {
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException) {
                throw $e;
            }
            report($e);
        }

        return $next($request);
    }
}
