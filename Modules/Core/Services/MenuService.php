<?php

namespace Modules\Core\Services;

use Illuminate\Support\Facades\DB;
use Modules\Core\Entities\Menu;

class MenuService
{
    /**
     * Dapatkan hierarki menu dinamis berdasarkan role & tenant pengguna aktif dari database
     */
    public static function getMenusForUser($user, $tenantId = null): array
    {
        if (!$user) {
            return [];
        }

        $roleName = strtolower($user->role?->nama_role ?? '');
        $isSuperAdmin = method_exists($user, 'isSuperAdmin') ? $user->isSuperAdmin() : ($roleName === 'super_admin');
        $effectiveTenantId = $tenantId ?? $user->tenant_id;

        // Query dasar seluruh menu aktif diurutkan berdasarkan urutan database (filter keluar tab internal dari sidebar)
        $query = Menu::where('is_active', true)
            ->where('id', 'NOT LIKE', '22%')
            ->where('url', 'NOT LIKE', '%?tab=%')
            ->orderBy('urutan', 'asc')
            ->orderBy('nama_menu', 'asc');

        // 1. Context Super Admin (God Mode: Mengakses 100% Seluruh Menu Platform & Operasional Sekolah)
        if ($isSuperAdmin) {
            // Super Admin dapat melihat seluruh menu Platform & Operasional Sekolah (kecuali menu duplikat khusus self-service siswa 30000000- jika sudah ada versi operasional)
            $query->where('id', 'NOT LIKE', '3%');
        } 
        // 2. Context Siswa & Orang Tua (Portal Self-Service)
        elseif (in_array($roleName, ['siswa', 'orang_tua'])) {
            $accessibleMenuIds = DB::table('core.role_menu_access')
                ->where('role_id', $user->role_id)
                ->pluck('menu_id')
                ->toArray();

            if (!empty($accessibleMenuIds)) {
                $parentIds = Menu::whereIn('id', $accessibleMenuIds)->whereNotNull('parent_id')->pluck('parent_id')->filter()->unique()->toArray();
                $allAccessibleIds = array_unique(array_merge($accessibleMenuIds, $parentIds));
                $query->whereIn('id', $allAccessibleIds);
            } else {
                $query->where('id', 'LIKE', '3%'); // Fallback prefix ID Portal Siswa
            }
        } 
        // 3. Context Operasional Sekolah (Admin / Guru / Staf)
        else {
            $roleAccessQuery = DB::table('core.role_menu_access')->where('role_id', $user->role_id);
            if ($effectiveTenantId) {
                $hasTenantAccess = (clone $roleAccessQuery)->where('tenant_id', $effectiveTenantId)->exists();
                if ($hasTenantAccess) {
                    $roleAccessQuery->where('tenant_id', $effectiveTenantId);
                }
            }

            $accessibleMenuIds = $roleAccessQuery->pluck('menu_id')->toArray();

            // Cek jika tenant memiliki konfigurasi pembatasan modul kustom (core.tenant_menu_access)
            if ($effectiveTenantId) {
                $tenantCustomMenus = DB::table('core.tenant_menu_access')
                    ->where('tenant_id', $effectiveTenantId)
                    ->pluck('menu_id')
                    ->toArray();

                if (!empty($tenantCustomMenus)) {
                    $accessibleMenuIds = array_intersect($accessibleMenuIds, $tenantCustomMenus);
                }
            }

            if (!empty($accessibleMenuIds)) {
                $parentIds = Menu::whereIn('id', $accessibleMenuIds)->whereNotNull('parent_id')->pluck('parent_id')->filter()->unique()->toArray();
                $allAccessibleIds = array_unique(array_merge($accessibleMenuIds, $parentIds));
                $query->whereIn('id', $allAccessibleIds);
            } else {
                $query->where('id', 'LIKE', '2%'); // Fallback prefix ID Operasional Sekolah
            }
        }

        $allMenus = $query->get()->toArray();

        return self::buildTree($allMenus, null, $roleName);
    }

    /**
     * Susun hierarki Tree Parent-Children dari raw database records
     * Mengeliminasi parent kosong yang tidak memiliki submenu aktif
     */
    private static function buildTree(array $items, ?string $parentId = null, ?string $roleName = null): array
    {
        $branch = [];

        foreach ($items as $item) {
            $mParent = !empty($item['parent_id']) ? (string)$item['parent_id'] : null;
            $targetParent = !empty($parentId) ? (string)$parentId : null;

            if ($mParent === $targetParent) {
                $children = self::buildTree($items, (string)$item['id'], $roleName);
                
                $hasValidUrl = !empty($item['url']) && $item['url'] !== '#';
                $hasChildren = count($children) > 0;

                // Jika parent root tidak memiliki URL langsung dan tidak memiliki anak aktif, lewati
                if (!$hasValidUrl && !$hasChildren && empty($parentId)) {
                    continue;
                }

                $url = $item['url'] ?: '#';
                
                // Adaptasi cerdas URL Dashboard berdasarkan Role
                if ($url === '/admin/dashboard') {
                    if (in_array($roleName, ['guru', 'wali_kelas', 'pendidik'])) {
                        $url = '/guru/dashboard';
                    } elseif (in_array($roleName, ['siswa', 'orang_tua'])) {
                        $url = '/siswa/dashboard';
                    }
                    // Super Admin tetap mempertahankan '/admin/dashboard' untuk inspeksi dashboard operasional sekolah
                }

                $node = [
                    'id'        => $item['id'],
                    'title'     => $item['nama_menu'],
                    'url'       => $url,
                    'icon'      => $item['icon'] ?: 'bi bi-circle',
                    'urutan'    => (int)($item['urutan'] ?? 999),
                    'children'  => $children,
                ];

                $branch[] = $node;
            }
        }

        // Urutkan berdasarkan kolom urutan database
        usort($branch, function ($a, $b) {
            return $a['urutan'] <=> $b['urutan'];
        });

        return $branch;
    }

    /**
     * Dapatkan daftar key tab yang diizinkan untuk rute/halaman tertentu berdasarkan role & tenant pengguna aktif
     */
    public static function getAllowedTabsForRoute($user, string $routePath, ?string $tenantId = null): array
    {
        if (!$user) {
            return [];
        }

        $roleName = strtolower(is_object($user->role) ? ($user->role->nama_role ?? '') : (string)($user->role ?? ''));
        $isSuperAdmin = method_exists($user, 'isSuperAdmin') ? $user->isSuperAdmin() : ($roleName === 'super_admin');

        // Clean route path (e.g. /sarpras, /kepegawaian, /smk)
        $cleanPath = '/' . ltrim(parse_url($routePath, PHP_URL_PATH), '/');

        // Find tab menu records for this route
        $tabMenus = DB::table('core.menus')
            ->where('is_active', true)
            ->where(function ($q) use ($cleanPath) {
                $q->where('url', 'LIKE', "{$cleanPath}?tab=%")
                  ->orWhere('id', 'LIKE', '22%');
            })
            ->get();

        // Filter tabs specifically matching $cleanPath in URL
        $matchingTabs = [];
        foreach ($tabMenus as $tm) {
            $menuPath = parse_url($tm->url, PHP_URL_PATH);
            if ($menuPath === $cleanPath) {
                parse_str(parse_url($tm->url, PHP_URL_QUERY) ?? '', $queryParams);
                $tabKey = $queryParams['tab'] ?? null;
                if ($tabKey) {
                    $matchingTabs[$tm->id] = $tabKey;
                }
            }
        }

        if (empty($matchingTabs)) {
            // No tab restrictions defined in DB for this route -> allow all
            return [];
        }

        if ($isSuperAdmin) {
            return array_values($matchingTabs);
        }

        $effectiveTenantId = $tenantId ?? session('tenant_id') ?? $user->tenant_id ?? '00000000-0000-0000-0000-000000000000';

        $roleAccessQuery = DB::table('core.role_menu_access')
            ->where('role_id', $user->role_id)
            ->whereIn('menu_id', array_keys($matchingTabs));

        if ($effectiveTenantId && $effectiveTenantId !== '00000000-0000-0000-0000-000000000000') {
            $hasTenantAccess = (clone $roleAccessQuery)->where('tenant_id', $effectiveTenantId)->exists();
            if ($hasTenantAccess) {
                $roleAccessQuery->where('tenant_id', $effectiveTenantId);
            } else {
                $roleAccessQuery->where('tenant_id', '00000000-0000-0000-0000-000000000000');
            }
        } else {
            $roleAccessQuery->where('tenant_id', '00000000-0000-0000-0000-000000000000');
        }

        $allowedMenuIds = $roleAccessQuery->pluck('menu_id')->toArray();

        $allowedTabs = [];
        foreach ($allowedMenuIds as $mId) {
            if (isset($matchingTabs[$mId])) {
                $allowedTabs[] = $matchingTabs[$mId];
            }
        }

        return array_values(array_unique($allowedTabs));
    }
}
