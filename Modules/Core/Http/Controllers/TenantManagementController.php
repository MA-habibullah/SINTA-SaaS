<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Modules\Core\Entities\Tenant;
use Modules\Core\Entities\Menu;
use Modules\Core\Entities\User;

class TenantManagementController extends Controller
{
    /**
     * Tampilkan Halaman Utama Kelola Sekolah (SaaS Tenant Management)
     * GET /super-admin/tenants
     */
    public function index(Request $request): InertiaResponse|JsonResponse
    {
        $user = Auth::user();
        if (!$this->checkIsSuperAdmin($user)) {
            abort(403, 'Akses ditolak. Anda tidak memiliki wewenang Super Admin untuk mengelola instansi sekolah (tenant).');
        }

        $search = trim((string)$request->input('search', ''));
        $status = trim((string)$request->input('status', ''));
        $paket = trim((string)$request->input('paket', ''));
        $sinkronisasi = trim((string)$request->input('sinkronisasi', ''));

        $query = Tenant::query()
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('nama_sekolah', 'ILIKE', "%{$search}%")
                        ->orWhere('npsn', 'ILIKE', "%{$search}%")
                        ->orWhere('subdomain', 'ILIKE', "%{$search}%")
                        ->orWhere('pic_nama', 'ILIKE', "%{$search}%")
                        ->orWhere('pic_email', 'ILIKE', "%{$search}%")
                        ->orWhere('custom_domain', 'ILIKE', "%{$search}%");
                });
            })
            ->when($status !== '', function ($q) use ($status) {
                if ($status === 'pending') {
                    $q->whereIn('status', ['pending', 'pending_approval', 'menunggu']);
                } elseif ($status === 'trial') {
                    $q->where('trial_ends_at', '>=', now());
                } else {
                    $q->where('status', $status);
                }
            })
            ->when($paket !== '', function ($q) use ($paket) {
                $q->where('paket_aktif', $paket);
            })
            ->when($sinkronisasi !== '', function ($q) use ($sinkronisasi) {
                $q->where('status_sinkronisasi', $sinkronisasi);
            })
            ->orderByRaw("CASE WHEN status IN ('pending_approval', 'pending', 'menunggu') THEN 0 WHEN id = '00000000-0000-0000-0000-000000000000' THEN 1 ELSE 2 END")
            ->orderBy('created_at', 'desc');

        // Statistik Keseluruhan
        $allTenants = Tenant::all();
        $stats = [
            'totalTenants'     => $allTenants->count(),
            'pendingTenants'   => $allTenants->filter(fn($t) => in_array(strtolower((string)$t->status), ['pending', 'pending_approval', 'menunggu'], true))->count(),
            'activeTenants'    => $allTenants->filter(fn($t) => in_array(strtolower((string)$t->status), ['active', 'aktif'], true))->count(),
            'trialTenants'     => $allTenants->filter(fn($t) => $t->trial_ends_at && $t->trial_ends_at->isFuture())->count(),
            'suspendedTenants' => $allTenants->filter(fn($t) => in_array(strtolower((string)$t->status), ['suspended', 'inactive', 'nonaktif'], true))->count(),
            'rejectedTenants'  => $allTenants->filter(fn($t) => in_array(strtolower((string)$t->status), ['rejected', 'ditolak'], true))->count(),
            'syncedTenants'    => $allTenants->where('status_sinkronisasi', 'Tersinkronisasi')->count(),
        ];

        // Daftar Seluruh Menu Sistem untuk Matrix Akses
        $menusList = Menu::select('id', 'nama_menu', 'url', 'icon', 'parent_id', 'urutan', 'is_active')
            ->orderBy('urutan', 'asc')
            ->get();

        // Ambil pemetaan hak akses menu per tenant
        $tenantIds = $query->pluck('id')->toArray();
        $allMenuAccess = DB::table('core.tenant_menu_access')
            ->whereIn('tenant_id', $tenantIds)
            ->select('tenant_id', 'menu_id')
            ->get()
            ->groupBy('tenant_id');

        $tenantsList = $query->get()->map(function ($t) use ($allMenuAccess) {
            $allowedMenuIds = isset($allMenuAccess[$t->id]) 
                ? $allMenuAccess[$t->id]->pluck('menu_id')->map(fn($id) => (string)$id)->toArray() 
                : [];

            return [
                'id'                    => (string)$t->id,
                'nama_sekolah'          => $t->nama_sekolah,
                'npsn'                  => $t->npsn,
                'subdomain'             => $t->subdomain,
                'domain'                => $t->custom_domain ?: '',
                'custom_domain'         => $t->custom_domain ?: '',
                'paket_aktif'           => $t->paket_aktif ?: 'Premium SaaS',
                'status_sinkronisasi'   => $t->status_sinkronisasi ?: 'Tersinkronisasi',
                'status'                => $t->status ?: 'active',
                'storage_limit_mb'      => (int)($t->storage_limit_mb ?: 1024),
                'max_siswa_limit'       => (int)($t->max_siswa_limit ?: 1000),
                'max_staff_limit'       => (int)($t->max_staff_limit ?: 100),
                'enable_bk'             => (int)($t->enable_bk ?? 1),
                'enable_tracer'         => (int)($t->enable_tracer ?? 1),
                'enable_ppdb'           => (int)($t->enable_ppdb ?? 1),
                'enable_perpustakaan'   => (int)($t->enable_perpustakaan ?? 1),
                'enable_keuangan'       => (int)($t->enable_keuangan ?? 1),
                'enable_pdss'           => (int)($t->enable_pdss ?? 1),
                'enable_smk'            => (int)($t->enable_smk ?? 1),
                'enable_sarpras'        => (int)($t->enable_sarpras ?? 1),
                'enable_persuratan'     => (int)($t->enable_persuratan ?? 1),
                'cms_landing_enabled'   => (bool)($t->cms_landing_enabled ?? true),
                'bentuk_pendidikan'     => $t->bentuk_pendidikan ?: 'SMA',
                'status_sekolah'        => $t->status_sekolah ?: 'Negeri',
                'kabupaten_kota'        => $t->kabupaten_kota ?: '',
                'provinsi'              => $t->provinsi ?: '',
                'telepon'               => $t->telepon ?: '',
                'email'                 => $t->email ?: '',
                'pic_nama'              => $t->pic_nama ?: '',
                'pic_jabatan'           => $t->pic_jabatan ?: '',
                'pic_telepon'           => $t->pic_telepon ?: '',
                'pic_email'             => $t->pic_email ?: '',
                'trial_ends_at'         => $t->trial_ends_at ? $t->trial_ends_at->format('Y-m-d H:i:s') : null,
                'trial_ends_at_human'   => $t->trial_ends_at ? $t->trial_ends_at->translatedFormat('d M Y') : null,
                'trial_duration_months' => (int)($t->trial_duration_months ?: 3),
                'subscription_type'     => $t->subscription_type ?: 'Free Trial',
                'remaining_trial_days'  => $t->remainingTrialDays(),
                'is_trial_active'       => $t->isTrialActive(),
                'is_pending_approval'   => $t->isPendingApproval(),
                'is_rejected'           => $t->isRejected(),
                'rejection_reason'      => $t->rejection_reason ?: '',
                'approved_at'           => $t->approved_at ? $t->approved_at->format('Y-m-d H:i:s') : null,
                'allowed_menu_ids'      => $allowedMenuIds,
                'created_at'            => $t->created_at ? $t->created_at->format('Y-m-d H:i:s') : null,
            ];
        });

        if ($request->wantsJson()) {
            return response()->json([
                'success'     => true,
                'data'        => $tenantsList,
                'stats'       => $stats,
                'menusList'   => $menusList,
                'filters'     => [
                    'search'       => $search,
                    'status'       => $status,
                    'paket'        => $paket,
                    'sinkronisasi' => $sinkronisasi,
                ],
            ]);
        }

        return Inertia::render('Core/Tenant/Index', [
            'tenantsList' => $tenantsList,
            'stats'       => $stats,
            'menusList'   => $menusList,
            'filters'     => [
                'search'       => $search,
                'status'       => $status,
                'paket'        => $paket,
                'sinkronisasi' => $sinkronisasi,
            ],
        ]);
    }

    /**
     * Simpan Data Sekolah Baru atau Perbarui yang Sudah Ada (Add / Edit)
     * POST /super-admin/tenants/simpan
     */
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $user = Auth::user();
        if (!$this->checkIsSuperAdmin($user)) {
            return response()->json(['success' => false, 'error' => 'Akses ditolak.'], 403);
        }

        $tenantId = $request->input('id');

        $rules = [
            'nama_sekolah'        => 'required|string|max:255',
            'npsn'                => 'required|string|max:20',
            'subdomain'           => 'required|string|max:100',
            'domain'              => 'nullable|string|max:255',
            'custom_domain'       => 'nullable|string|max:255',
            'paket_aktif'         => 'required|string|in:Basic,Pro,Premium SaaS,Enterprise SaaS',
            'status'              => 'required|string|in:active,inactive,suspended',
            'status_sinkronisasi' => 'required|string|in:Tersinkronisasi,Menunggu,Gagal',
            'storage_limit_mb'    => 'required|integer|min:10',
            'max_siswa_limit'     => 'required|integer|min:0',
            'max_staff_limit'     => 'required|integer|min:0',
            'enable_bk'           => 'nullable|in:0,1,true,false',
            'enable_tracer'       => 'nullable|in:0,1,true,false',
            'enable_ppdb'         => 'nullable|in:0,1,true,false',
            'enable_perpustakaan' => 'nullable|in:0,1,true,false',
            'enable_keuangan'     => 'nullable|in:0,1,true,false',
            'enable_pdss'         => 'nullable|in:0,1,true,false',
            'enable_smk'          => 'nullable|in:0,1,true,false',
            'enable_sarpras'      => 'nullable|in:0,1,true,false',
            'enable_persuratan'   => 'nullable|in:0,1,true,false',
            'cms_landing_enabled' => 'nullable|boolean',
            'bentuk_pendidikan'   => 'nullable|string|max:50',
            'status_sekolah'      => 'nullable|string|max:50',
        ];

        $validated = $request->validate($rules);

        // Sanitize subdomain (lowercase, remove invalid characters)
        $subdomain = Str::slug($validated['subdomain']);
        if (empty($subdomain)) {
            return response()->json(['success' => false, 'error' => 'Subdomain tidak valid.'], 422);
        }

        // Cek Keunikan Subdomain
        $existingSubdomain = Tenant::where('subdomain', $subdomain)
            ->when(!empty($tenantId), fn($q) => $q->where('id', '!=', $tenantId))
            ->exists();
        if ($existingSubdomain) {
            return response()->json([
                'success' => false,
                'error'   => "Subdomain '{$subdomain}' sudah digunakan oleh sekolah lain. Silakan pilih subdomain lain.",
                'errors'  => ['subdomain' => ["Subdomain '{$subdomain}' sudah digunakan."]]
            ], 422);
        }

        // Cek Keunikan NPSN
        $npsn = trim($validated['npsn']);
        $existingNpsn = Tenant::where('npsn', $npsn)
            ->when(!empty($tenantId), fn($q) => $q->where('id', '!=', $tenantId))
            ->exists();
        if ($existingNpsn && strtoupper($npsn) !== 'PLATFORM') {
            return response()->json([
                'success' => false,
                'error'   => "NPSN '{$npsn}' sudah terdaftar pada database sekolah lain.",
                'errors'  => ['npsn' => ["NPSN '{$npsn}' sudah terdaftar."]]
            ], 422);
        }

        $customDomain = $validated['domain'] ?? ($validated['custom_domain'] ?? null);

        $payload = [
            'nama_sekolah'        => trim($validated['nama_sekolah']),
            'npsn'                => $npsn,
            'subdomain'           => $subdomain,
            'custom_domain'       => $customDomain ? trim($customDomain) : null,
            'paket_aktif'         => $validated['paket_aktif'],
            'status'              => $validated['status'],
            'status_sinkronisasi' => $validated['status_sinkronisasi'],
            'storage_limit_mb'    => (int)$validated['storage_limit_mb'],
            'max_siswa_limit'     => (int)$validated['max_siswa_limit'],
            'max_staff_limit'     => (int)$validated['max_staff_limit'],
            'enable_bk'           => in_array($validated['enable_bk'] ?? 1, [1, '1', true, 'true'], true) ? 1 : 0,
            'enable_tracer'       => in_array($validated['enable_tracer'] ?? 1, [1, '1', true, 'true'], true) ? 1 : 0,
            'enable_ppdb'         => in_array($validated['enable_ppdb'] ?? 1, [1, '1', true, 'true'], true) ? 1 : 0,
            'enable_perpustakaan' => in_array($validated['enable_perpustakaan'] ?? 1, [1, '1', true, 'true'], true) ? 1 : 0,
            'enable_keuangan'     => in_array($validated['enable_keuangan'] ?? 1, [1, '1', true, 'true'], true) ? 1 : 0,
            'enable_pdss'         => in_array($validated['enable_pdss'] ?? 1, [1, '1', true, 'true'], true) ? 1 : 0,
            'enable_smk'          => in_array($validated['enable_smk'] ?? 1, [1, '1', true, 'true'], true) ? 1 : 0,
            'enable_sarpras'      => in_array($validated['enable_sarpras'] ?? 1, [1, '1', true, 'true'], true) ? 1 : 0,
            'enable_persuratan'   => in_array($validated['enable_persuratan'] ?? 1, [1, '1', true, 'true'], true) ? 1 : 0,
            'cms_landing_enabled' => in_array($validated['cms_landing_enabled'] ?? true, [1, '1', true, 'true'], true),
            'bentuk_pendidikan'   => $validated['bentuk_pendidikan'] ?? 'SMA',
            'status_sekolah'      => $validated['status_sekolah'] ?? 'Negeri',
        ];

        try {
            if (!empty($tenantId)) {
                // Update Tenant
                $tenant = Tenant::findOrFail($tenantId);
                $tenant->update($payload);
                $message = "Data instansi sekolah '{$tenant->nama_sekolah}' berhasil diperbarui.";
            } else {
                // Create Tenant
                $newId = Str::uuid()->toString();
                $payload['id'] = $newId;
                $tenant = Tenant::create($payload);

                // Auto-seed default active menus into core.tenant_menu_access for new tenant
                $activeMenus = Menu::select('id')->get();
                $menuAccessData = [];
                $now = now();
                foreach ($activeMenus as $m) {
                    $menuAccessData[] = [
                        'id'         => Str::uuid()->toString(),
                        'tenant_id'  => $newId,
                        'menu_id'    => $m->id,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
                foreach (array_chunk($menuAccessData, 50) as $chunk) {
                    DB::table('core.tenant_menu_access')->insert($chunk);
                }

                $message = "Sekolah baru '{$tenant->nama_sekolah}' berhasil didaftarkan ke platform SaaS.";
            }

            if ($request->wantsJson() || $request->ajax() || $request->expectsJson() || $request->isJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'data'    => $tenant,
                ]);
            }

            return back()->with('success', $message);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error'   => 'Terjadi kesalahan sistem saat menyimpan data sekolah: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Ubah Cepat Status Akses Sekolah (Active / Inactive / Suspended)
     * POST /super-admin/tenants/toggle-status
     */
    public function toggleStatus(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (!$this->checkIsSuperAdmin($user)) {
            return response()->json(['success' => false, 'error' => 'Akses ditolak.'], 403);
        }

        $validated = $request->validate([
            'id'     => 'required|uuid',
            'status' => 'required|string|in:active,inactive,suspended',
        ]);

        $tenant = Tenant::find($validated['id']);
        if (!$tenant) {
            return response()->json(['success' => false, 'error' => 'Instansi sekolah tidak ditemukan.'], 404);
        }

        if ($tenant->id === '00000000-0000-0000-0000-000000000000' && $validated['status'] !== 'active') {
            return response()->json(['success' => false, 'error' => 'Tenant Pusat Kendali SaaS Global tidak dapat dinonaktifkan.'], 422);
        }

        $tenant->status = $validated['status'];
        $tenant->save();

        return response()->json([
            'success' => true,
            'message' => "Status akses sekolah '{$tenant->nama_sekolah}' berhasil diubah menjadi '{$validated['status']}'.",
            'status'  => $tenant->status,
        ]);
    }

    /**
     * Hapus Sekolah (Tenant)
     * POST /super-admin/tenants/hapus atau DELETE /super-admin/tenants/{id}
     */
    public function destroy(Request $request, ?string $id = null): JsonResponse|RedirectResponse
    {
        $user = Auth::user();
        if (!$this->checkIsSuperAdmin($user)) {
            return response()->json(['success' => false, 'error' => 'Akses ditolak.'], 403);
        }

        $targetId = $id ?: $request->input('id');
        if (!$targetId) {
            return response()->json(['success' => false, 'error' => 'ID Sekolah tidak valid.'], 400);
        }

        if ($targetId === '00000000-0000-0000-0000-000000000000') {
            return response()->json(['success' => false, 'error' => 'Tenant Pusat Kendali SaaS Global tidak dapat dihapus.'], 422);
        }

        $tenant = Tenant::find($targetId);
        if (!$tenant) {
            return response()->json(['success' => false, 'error' => 'Sekolah tidak ditemukan.'], 404);
        }

        $namaSekolah = $tenant->nama_sekolah;

        try {
            DB::transaction(function () use ($tenant, $targetId) {
                // Hapus relasi menu access
                DB::table('core.tenant_menu_access')->where('tenant_id', $targetId)->delete();
                // Hapus tenant
                $tenant->delete();
            });

            if ($request->wantsJson() || $request->ajax() || $request->expectsJson() || $request->isJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "Sekolah '{$namaSekolah}' berhasil dihapus dari sistem SaaS.",
                ]);
            }

            return back()->with('success', "Sekolah '{$namaSekolah}' berhasil dihapus.");
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error'   => 'Gagal menghapus sekolah: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Setujui Pendaftaran Sekolah Baru (Approve Tenant & Assign Free Trial + Menus)
     * POST /super-admin/tenants/{id}/approve
     */
    public function approve(Request $request, string $id): JsonResponse|RedirectResponse
    {
        $user = Auth::user();
        if (!$this->checkIsSuperAdmin($user)) {
            return response()->json(['success' => false, 'error' => 'Akses ditolak.'], 403);
        }

        $tenant = Tenant::findOrFail($id);

        $validated = $request->validate([
            'trial_duration_months' => 'required|integer|min:1|max:36',
            'custom_trial_ends_at'  => 'nullable|date',
            'subscription_type'     => 'nullable|string|max:50',
            'allowed_menus'         => 'nullable|array',
            'allowed_menus.*'       => 'string',
            'enable_bk'             => 'nullable|in:0,1,true,false',
            'enable_tracer'         => 'nullable|in:0,1,true,false',
            'enable_ppdb'           => 'nullable|in:0,1,true,false',
            'enable_perpustakaan'   => 'nullable|in:0,1,true,false',
            'enable_keuangan'       => 'nullable|in:0,1,true,false',
            'enable_pdss'           => 'nullable|in:0,1,true,false',
            'enable_smk'            => 'nullable|in:0,1,true,false',
            'enable_sarpras'        => 'nullable|in:0,1,true,false',
            'enable_persuratan'     => 'nullable|in:0,1,true,false',
            'storage_limit_mb'      => 'nullable|integer|min:10',
            'max_siswa_limit'       => 'nullable|integer|min:0',
        ]);

        $trialMonths = (int) $validated['trial_duration_months'];
        $trialEndsAt = !empty($validated['custom_trial_ends_at'])
            ? \Carbon\Carbon::parse($validated['custom_trial_ends_at'])
            : now()->addMonths($trialMonths);

        $subType = $validated['subscription_type'] ?? "Free Trial {$trialMonths} Bulan";

        try {
            DB::transaction(function () use ($tenant, $validated, $trialMonths, $trialEndsAt, $subType, $user) {
                // 1. Update Status Tenant menjadi Active & Atur Masa Trial + Modul
                $tenant->update([
                    'status'                => 'active',
                    'paket_aktif'           => $subType,
                    'subscription_type'     => $subType,
                    'trial_duration_months' => $trialMonths,
                    'trial_ends_at'         => $trialEndsAt,
                    'approved_at'           => now(),
                    'approved_by'           => $user?->id,
                    'rejection_reason'      => null,
                    'storage_limit_mb'      => (int)($validated['storage_limit_mb'] ?? $tenant->storage_limit_mb ?? 1024),
                    'max_siswa_limit'       => (int)($validated['max_siswa_limit'] ?? $tenant->max_siswa_limit ?? 1000),
                    'enable_bk'             => in_array($validated['enable_bk'] ?? 1, [1, '1', true, 'true'], true) ? 1 : 0,
                    'enable_tracer'         => in_array($validated['enable_tracer'] ?? 1, [1, '1', true, 'true'], true) ? 1 : 0,
                    'enable_ppdb'           => in_array($validated['enable_ppdb'] ?? 1, [1, '1', true, 'true'], true) ? 1 : 0,
                    'enable_perpustakaan'   => in_array($validated['enable_perpustakaan'] ?? 1, [1, '1', true, 'true'], true) ? 1 : 0,
                    'enable_keuangan'       => in_array($validated['enable_keuangan'] ?? 1, [1, '1', true, 'true'], true) ? 1 : 0,
                    'enable_pdss'           => in_array($validated['enable_pdss'] ?? 1, [1, '1', true, 'true'], true) ? 1 : 0,
                    'enable_smk'            => in_array($validated['enable_smk'] ?? ($tenant->bentuk_pendidikan === 'SMK' ? 1 : 0), [1, '1', true, 'true'], true) ? 1 : 0,
                    'enable_sarpras'        => in_array($validated['enable_sarpras'] ?? 1, [1, '1', true, 'true'], true) ? 1 : 0,
                    'enable_persuratan'     => in_array($validated['enable_persuratan'] ?? 1, [1, '1', true, 'true'], true) ? 1 : 0,
                ]);

                // 2. Sinkronisasi Hak Akses Menu core.tenant_menu_access
                $allowedMenus = $validated['allowed_menus'] ?? null;
                if ($allowedMenus === null || empty($allowedMenus)) {
                    // Default: berikan seluruh active menus
                    $allowedMenus = Menu::where('is_active', true)->pluck('id')->toArray();
                }

                DB::table('core.tenant_menu_access')->where('tenant_id', $tenant->id)->delete();
                $now = now();
                $insertData = [];
                foreach ($allowedMenus as $menuId) {
                    $insertData[] = [
                        'id'         => Str::uuid()->toString(),
                        'tenant_id'  => $tenant->id,
                        'menu_id'    => $menuId,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
                foreach (array_chunk($insertData, 50) as $chunk) {
                    DB::table('core.tenant_menu_access')->insert($chunk);
                }

                // 3. Aktifkan User Administrator Sekolah
                User::where('tenant_id', $tenant->id)->update(['is_active' => true]);
            });

            $message = "Pendaftaran sekolah '{$tenant->nama_sekolah}' berhasil disetujui! Masa uji coba gratis ({$subType}) aktif hingga " . $trialEndsAt->translatedFormat('d F Y') . ".";

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'data'    => $tenant->fresh(),
                ]);
            }

            return back()->with('success', $message);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error'   => 'Gagal menyetujui pendaftaran sekolah: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Tolak Pendaftaran Sekolah Baru
     * POST /super-admin/tenants/{id}/reject
     */
    public function reject(Request $request, string $id): JsonResponse|RedirectResponse
    {
        $user = Auth::user();
        if (!$this->checkIsSuperAdmin($user)) {
            return response()->json(['success' => false, 'error' => 'Akses ditolak.'], 403);
        }

        $tenant = Tenant::findOrFail($id);

        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:1000',
        ], [
            'rejection_reason.required' => 'Alasan penolakan wajib diisi untuk pemberitahuan ke pihak sekolah.',
        ]);

        try {
            $tenant->update([
                'status'           => 'rejected',
                'rejection_reason' => trim($validated['rejection_reason']),
            ]);

            // Nonaktifkan user terkait
            User::where('tenant_id', $tenant->id)->update(['is_active' => false]);

            $message = "Pendaftaran sekolah '{$tenant->nama_sekolah}' telah ditolak.";

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'data'    => $tenant,
                ]);
            }

            return back()->with('success', $message);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error'   => 'Gagal menolak pendaftaran sekolah: ' . $e->getMessage(),
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
