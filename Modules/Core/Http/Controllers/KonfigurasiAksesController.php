<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Modules\Core\Entities\Tenant;

class KonfigurasiAksesController extends Controller
{
    public function index(Request $request): InertiaResponse|JsonResponse
    {
        $user = auth()->user();
        $userRoleName = is_object($user?->role) ? ($user->role->nama_role ?? 'admin_sekolah') : ($user?->role ?? 'admin_sekolah');
        $isSuperAdmin = ($user && ($userRoleName === 'super_admin' || $user->tenant_id === '00000000-0000-0000-0000-000000000000'));

        $tenantsList = [];
        if ($isSuperAdmin) {
            $tenantsList = Tenant::select('id', 'nama_sekolah', 'npsn', 'status', 'bentuk_pendidikan')
                ->where('id', '!=', '00000000-0000-0000-0000-000000000000')
                ->orderBy('nama_sekolah', 'asc')
                ->get();
        }

        $targetTenantId = null;
        if ($isSuperAdmin && $request->filled('tenant_id')) {
            $targetTenantId = $request->input('tenant_id');
        } else {
            $targetTenantId = session('tenant_id') ?? $user?->tenant_id ?? '00000000-0000-0000-0000-000000000000';
        }

        // Roles priority list
        $roles = DB::table('core.roles')
            ->select('id', 'nama_role', 'deskripsi')
            ->orderByRaw("
                CASE nama_role
                    WHEN 'super_admin' THEN 1
                    WHEN 'admin_sekolah' THEN 2
                    WHEN 'kepala_sekolah' THEN 3
                    WHEN 'kurikulum' THEN 4
                    WHEN 'guru' THEN 5
                    WHEN 'wali_kelas' THEN 6
                    WHEN 'bk' THEN 7
                    WHEN 'guru_bk' THEN 8
                    WHEN 'keuangan' THEN 9
                    WHEN 'sarpras' THEN 10
                    WHEN 'perpustakaan' THEN 11
                    WHEN 'kesiswaan' THEN 12
                    WHEN 'humas' THEN 13
                    WHEN 'operator_sekolah' THEN 14
                    WHEN 'karyawan' THEN 15
                    WHEN 'siswa' THEN 16
                    ELSE 99
                END ASC
            ")
            ->orderBy('nama_role', 'asc')
            ->get();

        // Menus structure
        $rawMenus = DB::table('core.menus')
            ->select('id', 'parent_id', 'nama_menu', 'url', 'icon', 'urutan', 'is_active')
            ->orderBy('urutan', 'asc')
            ->orderBy('nama_menu', 'asc')
            ->get();

        // Build parent & child menu tree
        $parents = [];
        $childMap = [];
        foreach ($rawMenus as $m) {
            if (empty($m->parent_id)) {
                $parents[] = $m;
            } else {
                $childMap[$m->parent_id][] = $m;
            }
        }

        $orderedMenus = [];
        foreach ($parents as $p) {
            $orderedMenus[] = [
                'id'        => $p->id,
                'parent_id' => null,
                'nama_menu' => $p->nama_menu,
                'url'       => $p->url,
                'icon'      => $p->icon,
                'urutan'    => $p->urutan,
                'is_child'  => false,
            ];
            if (isset($childMap[$p->id])) {
                foreach ($childMap[$p->id] as $c) {
                    $orderedMenus[] = [
                        'id'        => $c->id,
                        'parent_id' => $p->id,
                        'nama_menu' => $c->nama_menu,
                        'url'       => $c->url,
                        'icon'      => $c->icon,
                        'urutan'    => $c->urutan,
                        'is_child'  => true,
                    ];
                }
            }
        }

        // Fetch access matrix
        $accessMap = $this->getAccessMapForTenant($targetTenantId);

        $selectedTenant = null;
        if ($targetTenantId && $targetTenantId !== '00000000-0000-0000-0000-000000000000') {
            $selectedTenant = Tenant::find($targetTenantId);
        }

        if ($request->wantsJson() && !$request->header('X-Inertia')) {
            return response()->json([
                'success'           => true,
                'roles'             => $roles,
                'menus'             => $orderedMenus,
                'menu_list'         => $orderedMenus,
                'access_map'        => $accessMap,
                'target_tenant_id'  => $targetTenantId,
                'is_super_admin'    => $isSuperAdmin,
                'tenants'           => $tenantsList,
            ]);
        }

        return Inertia::render('Core/Konfigurasi/Akses/Index', [
            'roles'             => $roles,
            'menuList'          => $orderedMenus,
            'accessMap'         => $accessMap,
            'tenantsList'       => $tenantsList,
            'selectedTenantId'  => $targetTenantId,
            'selectedTenant'    => $selectedTenant,
            'userRole'          => $userRoleName,
            'isSuperAdmin'      => $isSuperAdmin,
            'flash' => [
                'success' => session('success'),
                'error'   => session('error'),
            ]
        ]);
    }

    public function fetch(Request $request): JsonResponse
    {
        $tenantId = $request->input('tenant_id') ?: '00000000-0000-0000-0000-000000000000';
        $accessMap = $this->getAccessMapForTenant($tenantId, $isCustom);

        return response()->json([
            'success'    => true,
            'access_map' => $accessMap,
            'is_custom'  => $isCustom,
            'tenant_id'  => $tenantId,
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $user = auth()->user();
        $userRoleName = is_object($user?->role) ? ($user->role->nama_role ?? 'admin_sekolah') : ($user?->role ?? 'admin_sekolah');
        $isSuperAdmin = ($user && ($userRoleName === 'super_admin' || $user->tenant_id === '00000000-0000-0000-0000-000000000000'));

        $targetTenantId = ($isSuperAdmin && $request->filled('target_tenant_id'))
            ? $request->input('target_tenant_id')
            : (session('tenant_id') ?? $user?->tenant_id ?? '00000000-0000-0000-0000-000000000000');

        if (!$targetTenantId) {
            $targetTenantId = '00000000-0000-0000-0000-000000000000';
        }

        $accessInput = $request->input('access', []); // [ roleId => [menuId, menuId...] ]

        // Map parent menus for auto-cascade
        $menuParents = DB::table('core.menus')->whereNotNull('parent_id')->pluck('parent_id', 'id')->toArray();

        DB::transaction(function () use ($targetTenantId, $accessInput, $menuParents) {
            // Delete existing access for target tenant
            DB::table('core.role_menu_access')->where('tenant_id', $targetTenantId)->delete();

            $insertData = [];
            $seen = [];

            foreach ($accessInput as $roleId => $menuIds) {
                if (!is_array($menuIds)) continue;

                foreach ($menuIds as $menuId) {
                    $key = "{$targetTenantId}-{$roleId}-{$menuId}";
                    if (!isset($seen[$key])) {
                        $seen[$key] = true;
                        $insertData[] = [
                            'tenant_id' => $targetTenantId,
                            'role_id'   => $roleId,
                            'menu_id'   => $menuId,
                        ];
                    }

                    // Auto-grant parent menu if child is granted
                    if (isset($menuParents[$menuId])) {
                        $parentId = $menuParents[$menuId];
                        $parentKey = "{$targetTenantId}-{$roleId}-{$parentId}";
                        if (!isset($seen[$parentKey])) {
                            $seen[$parentKey] = true;
                            $insertData[] = [
                                'tenant_id' => $targetTenantId,
                                'role_id'   => $roleId,
                                'menu_id'   => $parentId,
                            ];
                        }
                    }
                }
            }

            if (!empty($insertData)) {
                foreach (array_chunk($insertData, 500) as $chunk) {
                    DB::table('core.role_menu_access')->insert($chunk);
                }
            }
        });

        if ($request->wantsJson() && !$request->header('X-Inertia')) {
            return response()->json([
                'success' => true,
                'message' => 'Matriks hak akses menu berhasil disimpan.',
            ]);
        }

        return back()->with('success', 'Matriks hak akses menu berhasil disimpan dan diterapkan secara real-time.');
    }

    private function getAccessMapForTenant(?string $tenantId, ?bool &$isCustom = false): array
    {
        $isCustom = false;
        if (!$tenantId) {
            $tenantId = '00000000-0000-0000-0000-000000000000';
        }

        $hasTenantRows = DB::table('core.role_menu_access')->where('tenant_id', $tenantId)->exists();

        if ($hasTenantRows) {
            $isCustom = ($tenantId !== '00000000-0000-0000-0000-000000000000');
            $rows = DB::table('core.role_menu_access')->where('tenant_id', $tenantId)->get();
        } else {
            // Fallback to global default
            $rows = DB::table('core.role_menu_access')->where('tenant_id', '00000000-0000-0000-0000-000000000000')->get();
            if ($rows->isEmpty()) {
                // If global is also empty, fetch any existing rows
                $rows = DB::table('core.role_menu_access')->get();
            }
        }

        $accessMap = [];
        foreach ($rows as $r) {
            $accessMap[$r->role_id . '-' . $r->menu_id] = true;
        }

        return $accessMap;
    }
}
