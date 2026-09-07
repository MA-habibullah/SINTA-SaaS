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

        $isSuperAdmin = method_exists($user, 'isSuperAdmin') ? $user->isSuperAdmin() : (strtolower($user->role?->nama_role ?? '') === 'super_admin');
        
        // Ambil seluruh menu aktif dari PostgreSQL core.menus
        $query = Menu::where('is_active', true)->orderBy('urutan', 'asc')->orderBy('nama_menu', 'asc');
        
        // Jika bukan super admin, filter berdasarkan hak akses role_menu_access jika tabel terisi
        if (!$isSuperAdmin && $user->role_id) {
            $hasRoleAccess = DB::table('core.role_menu_access')->where('role_id', $user->role_id)->exists();
            if ($hasRoleAccess) {
                $accessibleMenuIds = DB::table('core.role_menu_access')
                    ->where('role_id', $user->role_id)
                    ->pluck('menu_id')
                    ->toArray();

                $query->where(function ($q) use ($accessibleMenuIds) {
                    $q->whereIn('id', $accessibleMenuIds)
                      ->orWhereNull('parent_id'); // Biarkan parent tetap muncul jika memiliki anak aktif
                });
            }
        }

        $allMenus = $query->get()->toArray();

        return self::buildTree($allMenus);
    }

    /**
     * Susun hierarki Tree Parent-Children dari raw database records
     */
    private static function buildTree(array $items, ?string $parentId = null): array
    {
        $branch = [];

        foreach ($items as $item) {
            $mParent = !empty($item['parent_id']) ? (string)$item['parent_id'] : null;
            $targetParent = !empty($parentId) ? (string)$parentId : null;

            if ($mParent === $targetParent) {
                $children = self::buildTree($items, (string)$item['id']);
                
                $node = [
                    'id'        => $item['id'],
                    'title'     => $item['nama_menu'],
                    'url'       => $item['url'] ?: '#',
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
}
