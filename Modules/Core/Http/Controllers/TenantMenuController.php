<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Modules\Core\Entities\Tenant;
use Modules\Core\Entities\Menu;
use Modules\Core\Entities\TenantMenuAccess;
use Modules\Core\Entities\User;

class TenantMenuController extends Controller
{
    /**
     * Tampilkan Halaman Pengaturan Akses Fitur Sekolah (Tenant)
     * GET /super-admin/tenant-menus
     */
    public function index(Request $request): InertiaResponse|JsonResponse
    {
        $user = Auth::user();
        if (!$this->checkIsSuperAdmin($user)) {
            abort(403, 'Akses ditolak. Anda tidak memiliki wewenang Super Admin untuk mengelola fitur tenant.');
        }

        $tenants = Tenant::select('id', 'nama_sekolah', 'npsn', 'subdomain')
            ->orderBy('nama_sekolah', 'asc')
            ->get();

        $menus = Menu::select('id', 'parent_id', 'nama_menu', 'url', 'icon', 'urutan', 'is_active')
            ->orderBy('urutan', 'asc')
            ->get();

        $selectedTenantId = (string)$request->query('tenant_id', '');
        if (empty($selectedTenantId) && $tenants->isNotEmpty()) {
            $selectedTenantId = (string)$tenants->first()->id;
        }

        $checkedMenuIds = [];
        if (!empty($selectedTenantId)) {
            $checkedMenuIds = TenantMenuAccess::where('tenant_id', $selectedTenantId)
                ->pluck('menu_id')
                ->map(fn($id) => (string)$id)
                ->toArray();
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success'          => true,
                'tenants'          => $tenants,
                'menus'            => $menus,
                'selectedTenantId' => $selectedTenantId,
                'checkedMenuIds'   => $checkedMenuIds,
            ]);
        }

        return Inertia::render('Core/TenantMenus/Index', [
            'tenantsList'      => $tenants,
            'menuList'         => $menus,
            'selectedTenantId' => $selectedTenantId,
            'checkedMenuIds'   => $checkedMenuIds,
        ]);
    }

    /**
     * API: Ambil data pemetaan menu untuk tenant tertentu
     * GET /super-admin/tenant-menus/fetch
     */
    public function fetch(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (!$this->checkIsSuperAdmin($user)) {
            return response()->json(['success' => false, 'error' => 'Akses ditolak.'], 403);
        }

        $tenantId = $request->query('tenant_id');

        $tenants = Tenant::select('id', 'nama_sekolah', 'npsn', 'subdomain')
            ->orderBy('nama_sekolah', 'asc')
            ->get();

        $menus = Menu::select('id', 'parent_id', 'nama_menu', 'url', 'icon', 'urutan', 'is_active')
            ->orderBy('urutan', 'asc')
            ->get();

        $checkedMenuIds = [];
        if (!empty($tenantId)) {
            $checkedMenuIds = TenantMenuAccess::where('tenant_id', $tenantId)
                ->pluck('menu_id')
                ->map(fn($id) => (string)$id)
                ->toArray();
        }

        return response()->json([
            'success'        => true,
            'tenants'        => $tenants,
            'menus'          => $menus,
            'checkedMenuIds' => $checkedMenuIds,
        ]);
    }

    /**
     * API: Simpan hak akses menu untuk tenant terpilih
     * POST /super-admin/tenant-menus/save
     */
    public function save(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (!$this->checkIsSuperAdmin($user)) {
            return response()->json(['success' => false, 'error' => 'Akses ditolak.'], 403);
        }

        $validated = $request->validate([
            'tenant_id' => 'required|uuid',
            'menu_ids'  => 'nullable|array',
            'menu_ids.*'=> 'uuid',
        ]);

        $tenantId = $validated['tenant_id'];
        $menuIds = $validated['menu_ids'] ?? [];

        // Pastikan tenant ada di database
        $tenantExists = Tenant::where('id', $tenantId)->exists();
        if (!$tenantExists) {
            return response()->json(['success' => false, 'error' => 'Tenant sekolah tidak ditemukan.'], 404);
        }

        try {
            DB::transaction(function () use ($tenantId, $menuIds) {
                // 1. Hapus konfigurasi lama tenant
                DB::table('core.tenant_menu_access')->where('tenant_id', $tenantId)->delete();

                // 2. Simpan konfigurasi baru
                if (!empty($menuIds)) {
                    $insertData = [];
                    $now = now();
                    foreach ($menuIds as $menuId) {
                        $insertData[] = [
                            'id'         => Str::uuid()->toString(),
                            'tenant_id'  => $tenantId,
                            'menu_id'    => $menuId,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }

                    // Insert in chunks of 50
                    foreach (array_chunk($insertData, 50) as $chunk) {
                        DB::table('core.tenant_menu_access')->insert($chunk);
                    }
                }
            });

            return response()->json([
                'success'        => true,
                'message'        => 'Akses fitur sekolah berhasil disimpan.',
                'checkedMenuIds' => $menuIds,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error'   => 'Gagal menyimpan akses menu sekolah: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Helper untuk memeriksa hak akses Super Admin
     */
    private function checkIsSuperAdmin(?User $user): bool
    {
        if (!$user) return false;
        if ($user->username === 'superadmin' || $user->username === 'qa_superadmin') return true;
        if ($user->tenant_id === '00000000-0000-0000-0000-000000000000') return true;
        if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) return true;
        if ($user->role && $user->role->nama_role === 'super_admin') return true;
        return false;
    }
}
