<?php

namespace Modules\Akademik\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Modules\Core\Entities\Tenant;
use Modules\Akademik\Entities\Pendidikan;
use Modules\Akademik\Entities\Jenjang;
use Modules\Akademik\Entities\Jurusan;
use Modules\Akademik\Entities\Kelas;
use Modules\Akademik\Entities\MataPelajaran;
use Modules\Akademik\Entities\ProgramPengajaran;
use Modules\Akademik\Entities\TahunAjaran;
use Modules\Akademik\Entities\Angkatan;
use Modules\Akademik\Entities\RefKurikulum;

class AkademikMasterController extends Controller
{
    /**
     * Map modul tab ke Model Eloquent dan kolom nama/kode
     */
    private function getTabConfig(string $tab): array
    {
        $configs = [
            'pendidikan' => [
                'name'        => 'Pendidikan',
                'model'       => Pendidikan::class,
                'name_field'  => 'nama_pendidikan',
                'code_field'  => 'kategori',
                'search_cols' => ['nama_pendidikan', 'kategori', 'deskripsi'],
            ],
            'jenjang' => [
                'name'        => 'Jenjang',
                'model'       => Jenjang::class,
                'name_field'  => 'nama_jenjang',
                'code_field'  => 'kode_jenjang',
                'search_cols' => ['kode_jenjang', 'nama_jenjang'],
            ],
            'jurusan' => [
                'name'        => 'Jurusan',
                'model'       => Jurusan::class,
                'name_field'  => 'nama_jurusan',
                'code_field'  => 'kategori',
                'search_cols' => ['nama_jurusan', 'kategori', 'deskripsi'],
            ],
            'kelas' => [
                'name'        => 'Kelas',
                'model'       => Kelas::class,
                'name_field'  => 'nama_kelas',
                'code_field'  => 'kode_kelas',
                'search_cols' => ['kode_kelas', 'nama_kelas', 'kategori', 'deskripsi'],
            ],
            'mata_pelajaran' => [
                'name'        => 'Mata Pelajaran',
                'model'       => MataPelajaran::class,
                'name_field'  => 'nama_mata_pelajaran',
                'code_field'  => 'kategori',
                'search_cols' => ['nama_mata_pelajaran', 'kategori', 'deskripsi'],
            ],
            'program_pengajaran' => [
                'name'        => 'Program Pengajaran',
                'model'       => ProgramPengajaran::class,
                'name_field'  => 'nama_program',
                'code_field'  => 'kode_program',
                'search_cols' => ['kode_program', 'nama_program'],
            ],
            'tahun_ajaran' => [
                'name'        => 'Tahun Ajaran',
                'model'       => TahunAjaran::class,
                'name_field'  => 'nama_tahun_ajaran',
                'code_field'  => 'kategori',
                'search_cols' => ['nama_tahun_ajaran', 'kategori', 'deskripsi'],
            ],
            'angkatan' => [
                'name'        => 'Angkatan',
                'model'       => Angkatan::class,
                'name_field'  => 'nama_angkatan',
                'code_field'  => 'kategori',
                'search_cols' => ['nama_angkatan', 'kategori', 'deskripsi'],
            ],
            'kurikulum' => [
                'name'        => 'Kurikulum',
                'model'       => RefKurikulum::class,
                'name_field'  => 'nama_ref_kurikulum',
                'code_field'  => 'kategori',
                'search_cols' => ['nama_ref_kurikulum', 'kategori', 'deskripsi'],
            ],
        ];

        return $configs[$tab] ?? $configs['pendidikan'];
    }

    /**
     * Dashboard Master Data Kelembagaan & Akademik
     */
    public function index(Request $request): InertiaResponse|JsonResponse
    {
        $activeTab       = $request->input('tab', 'pendidikan');
        $validTabs       = ['pendidikan', 'jenjang', 'jurusan', 'kelas', 'mata_pelajaran', 'program_pengajaran', 'tahun_ajaran', 'angkatan', 'kurikulum'];
        if (!in_array($activeTab, $validTabs)) {
            $activeTab = 'pendidikan';
        }

        $user            = auth()->user();
        $isSuperAdmin    = $user && ($user->isSuperAdmin() || in_array($user->role?->nama_role ?? '', ['super_admin', 'superadmin', 'admin']));
        $filterTenantId  = $request->input('tenant_id', '');
        $search          = trim((string)$request->input('search', ''));
        $trashMode       = $request->boolean('trash', false);
        $perPage         = max(5, min(120, (int)$request->input('per_page', 10)));

        $tabConfig       = $this->getTabConfig($activeTab);
        $modelClass      = $tabConfig['model'];

        // Build base query
        if ($isSuperAdmin) {
            $query = $modelClass::withoutTenant();
            if (!empty($filterTenantId)) {
                $query->where('tenant_id', $filterTenantId);
            }
        } else {
            if ($activeTab === 'kurikulum') {
                $tenantId = auth()->user()?->tenant_id;
                $query = $modelClass::withoutTenant()->where(function ($q) use ($tenantId) {
                    $q->where('tenant_id', $tenantId)
                      ->orWhere('tenant_id', '11111111-1111-1111-1111-111111111111')
                      ->orWhereNull('tenant_id');
                });
            } else {
                $query = $modelClass::query();
            }
        }

        // Eager load tenant for Super Admin
        if ($isSuperAdmin && method_exists($modelClass, 'tenant')) {
            $query->with('tenant:id,nama_sekolah');
        }

        // Eager load relasi khusus kelas
        if ($activeTab === 'kelas') {
            $query->with(['jenjang', 'jurusan', 'tenant:id,nama_sekolah']);
            if ($request->filled('jenjang_id')) {
                $query->where('id_jenjang', $request->input('jenjang_id'));
            }
            if ($request->filled('jurusan_id')) {
                $query->where('id_jurusan', $request->input('jurusan_id'));
            }
        }

        // Trash filter
        $query->where('is_active', !$trashMode);

        // Search filter
        if ($search !== '') {
            $query->where(function ($q) use ($tabConfig, $search, $isSuperAdmin) {
                foreach ($tabConfig['search_cols'] as $idx => $col) {
                    if ($idx === 0) {
                        $q->where($col, 'ILIKE', "%{$search}%");
                    } else {
                        $q->orWhere($col, 'ILIKE', "%{$search}%");
                    }
                }
                if ($isSuperAdmin) {
                    $q->orWhereHas('tenant', function ($tq) use ($search) {
                        $tq->where('nama_sekolah', 'ILIKE', "%{$search}%");
                    });
                }
            });
        }

        // Sorting
        if (in_array($activeTab, ['tahun_ajaran', 'angkatan'])) {
            $query->orderBy('created_at', 'desc');
        } elseif ($activeTab === 'kelas') {
            $query->orderBy('nama_kelas', 'asc');
        } elseif ($activeTab === 'jenjang') {
            $query->orderBy('nama_jenjang', 'asc');
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $items = $query->paginate($perPage)->withQueryString();

        // Standardize item attributes for frontend view
        $items->getCollection()->transform(function ($item) use ($activeTab, $tabConfig) {
            $item->active_tab = $activeTab;
            $nameCol = $tabConfig['name_field'];
            $codeCol = $tabConfig['code_field'];

            $item->nama = $item->{$nameCol} ?? ($item->nama ?? '');
            $item->kode = $item->{$codeCol} ?? ($item->kode ?? '');

            if ($activeTab === 'kelas') {
                $item->nama_jenjang = $item->jenjang?->nama_jenjang ?? $item->id_jenjang ?? '-';
                $item->nama_jurusan = $item->jurusan?->nama_jurusan ?? $item->id_jurusan ?? '-';
            } elseif ($activeTab === 'kurikulum') {
                $item->nama_kurikulum = $item->nama_ref_kurikulum ?? $item->nama ?? '';
                $item->tipe_penilaian = $item->kategori ?? 'sederhana';
                $item->is_system = empty($item->tenant_id) || $item->tenant_id === '11111111-1111-1111-1111-111111111111';
            } elseif ($activeTab === 'tahun_ajaran') {
                $item->tahun_ajaran = $item->nama_tahun_ajaran ?? $item->nama ?? '';
            } elseif ($activeTab === 'angkatan') {
                $item->tahun_angkatan = $item->nama_angkatan ?? $item->nama ?? '';
            }

            $item->nama_sekolah = $item->tenant?->nama_sekolah ?? '-';
            return $item;
        });

        // Auxiliary options for Class creation & Tenant filtering
        $tenants = [];
        if ($isSuperAdmin) {
            $tenants = Tenant::orderBy('nama_sekolah', 'asc')->get(['id', 'nama_sekolah']);
        }

        $effectiveTenant = $isSuperAdmin && !empty($filterTenantId) ? $filterTenantId : auth()->user()?->tenant_id;

        $listJenjang = ($isSuperAdmin && empty($filterTenantId) ? Jenjang::withoutTenant() : Jenjang::query())
            ->when($effectiveTenant, fn($q) => $q->where('tenant_id', $effectiveTenant))
            ->where('is_active', true)
            ->orderBy('nama_jenjang', 'asc')
            ->get();

        $listJurusan = ($isSuperAdmin && empty($filterTenantId) ? Jurusan::withoutTenant() : Jurusan::query())
            ->when($effectiveTenant, fn($q) => $q->where('tenant_id', $effectiveTenant))
            ->where('is_active', true)
            ->orderBy('nama_jurusan', 'asc')
            ->get();

        if ($request->wantsJson()) {
            return response()->json([
                'success'   => true,
                'activeTab' => $activeTab,
                'data'      => $items,
                'tenants'   => $tenants,
            ]);
        }

        return Inertia::render('Akademik/Master/Index', [
            'activeTab'       => $activeTab,
            'items'           => $items,
            'tenants'         => $tenants,
            'listJenjang'     => $listJenjang,
            'listJurusan'     => $listJurusan,
            'isSuperAdmin'    => $isSuperAdmin,
            'userRole'        => $user?->role?->nama_role ?? ($user?->isSuperAdmin() ? 'super_admin' : 'admin_sekolah'),
            'filters'         => [
                'tab'        => $activeTab,
                'search'     => $search,
                'tenant_id'  => $filterTenantId,
                'jenjang_id' => $request->input('jenjang_id', ''),
                'jurusan_id' => $request->input('jurusan_id', ''),
                'trash'      => $trashMode,
                'per_page'   => $perPage,
            ],
        ]);
    }

    /**
     * Simpan Data Master Baru
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $tab = $request->input('tab', 'pendidikan');
        $tabConfig = $this->getTabConfig($tab);
        $modelClass = $tabConfig['model'];

        $user = auth()->user();
        $isSuperAdmin = $user && ($user->isSuperAdmin() || in_array($user->role?->nama_role ?? '', ['super_admin', 'superadmin', 'admin']));
        $tenantId = $isSuperAdmin ? ($request->input('tenant_id') ?: $user->tenant_id) : $user->tenant_id;

        $data = [
            'id'        => (string) Str::uuid(),
            'tenant_id' => $tenantId,
            'is_active' => true,
        ];

        if ($tab === 'kelas') {
            $validated = $request->validate([
                'nama_kelas' => 'required|string|max:100',
                'kode_kelas' => 'required|string|max:50',
                'id_jenjang' => 'required|string',
                'id_jurusan' => 'required|string',
            ]);
            $data = array_merge($data, $validated);
        } elseif ($tab === 'tahun_ajaran') {
            $validated = $request->validate([
                'kode' => 'required|string|max:20',
            ]);
            $data['nama_tahun_ajaran'] = $validated['kode'];
            $data['kategori']          = 'Reguler';
        } elseif ($tab === 'angkatan') {
            $validated = $request->validate([
                'kode' => 'required|string|max:10',
            ]);
            $data['nama_angkatan'] = $validated['kode'];
            $data['kategori']      = 'Angkatan';
        } elseif ($tab === 'kurikulum') {
            $validated = $request->validate([
                'nama_kurikulum' => 'required|string|max:100',
                'tipe_penilaian' => 'required|string|in:sederhana,klasik,kompleks',
            ]);
            $data['nama_ref_kurikulum'] = $validated['nama_kurikulum'];
            $data['kategori']           = $validated['tipe_penilaian'];
        } elseif ($tab === 'jenjang') {
            $validated = $request->validate([
                'kode' => 'required|string|max:20',
                'nama' => 'required|string|max:100',
            ]);
            $data['kode_jenjang'] = $validated['kode'];
            $data['nama_jenjang'] = $validated['nama'];
        } elseif ($tab === 'program_pengajaran') {
            $validated = $request->validate([
                'kode' => 'required|string|max:20',
                'nama' => 'required|string|max:100',
            ]);
            $data['kode_program'] = $validated['kode'];
            $data['nama_program'] = $validated['nama'];
        } else {
            $validated = $request->validate([
                'kode' => 'required|string|max:50',
                'nama' => 'required|string|max:255',
            ]);
            $nameCol = $tabConfig['name_field'];
            $codeCol = $tabConfig['code_field'];
            $data[$nameCol] = $validated['nama'];
            $data[$codeCol] = $validated['kode'];
        }

        $created = $modelClass::create($data);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Data {$tabConfig['name']} berhasil ditambahkan.",
                'data'    => $created,
            ], 201);
        }

        return back()->with('success', "Data {$tabConfig['name']} berhasil ditambahkan.");
    }

    /**
     * Update Data Master
     */
    public function update(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $tab = $request->input('tab', 'pendidikan');
        $tabConfig = $this->getTabConfig($tab);
        $modelClass = $tabConfig['model'];

        $item = $modelClass::withoutTenant()->findOrFail($id);

        $updateData = [];

        if ($tab === 'kelas') {
            $validated = $request->validate([
                'nama_kelas' => 'required|string|max:100',
                'kode_kelas' => 'required|string|max:50',
                'id_jenjang' => 'required|string',
                'id_jurusan' => 'required|string',
            ]);
            $updateData = $validated;
        } elseif ($tab === 'tahun_ajaran') {
            $validated = $request->validate([
                'kode' => 'required|string|max:20',
            ]);
            $updateData['nama_tahun_ajaran'] = $validated['kode'];
        } elseif ($tab === 'angkatan') {
            $validated = $request->validate([
                'kode' => 'required|string|max:10',
            ]);
            $updateData['nama_angkatan'] = $validated['kode'];
        } elseif ($tab === 'kurikulum') {
            $validated = $request->validate([
                'nama_kurikulum' => 'required|string|max:100',
                'tipe_penilaian' => 'required|string|in:sederhana,klasik,kompleks',
            ]);
            $updateData['nama_ref_kurikulum'] = $validated['nama_kurikulum'];
            $updateData['kategori']           = $validated['tipe_penilaian'];
        } elseif ($tab === 'jenjang') {
            $validated = $request->validate([
                'kode' => 'required|string|max:20',
                'nama' => 'required|string|max:100',
            ]);
            $updateData['kode_jenjang'] = $validated['kode'];
            $updateData['nama_jenjang'] = $validated['nama'];
        } elseif ($tab === 'program_pengajaran') {
            $validated = $request->validate([
                'kode' => 'required|string|max:20',
                'nama' => 'required|string|max:100',
            ]);
            $updateData['kode_program'] = $validated['kode'];
            $updateData['nama_program'] = $validated['nama'];
        } else {
            $validated = $request->validate([
                'kode' => 'required|string|max:50',
                'nama' => 'required|string|max:255',
            ]);
            $nameCol = $tabConfig['name_field'];
            $codeCol = $tabConfig['code_field'];
            $updateData[$nameCol] = $validated['nama'];
            $updateData[$codeCol] = $validated['kode'];
        }

        $item->update($updateData);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Data {$tabConfig['name']} berhasil diperbarui.",
                'data'    => $item,
            ]);
        }

        return back()->with('success', "Data {$tabConfig['name']} berhasil diperbarui.");
    }

    /**
     * Soft Delete (Pindahkan ke Tong Sampah)
     */
    public function destroy(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $tab = $request->input('tab', 'pendidikan');
        $tabConfig = $this->getTabConfig($tab);
        $modelClass = $tabConfig['model'];

        $item = $modelClass::withoutTenant()->findOrFail($id);
        $item->update(['is_active' => false]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Data {$tabConfig['name']} berhasil dipindahkan ke tong sampah.",
            ]);
        }

        return back()->with('success', "Data {$tabConfig['name']} berhasil dipindahkan ke tong sampah.");
    }

    /**
     * Pulihkan dari Tong Sampah (Restore)
     */
    public function restore(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $tab = $request->input('tab', 'pendidikan');
        $tabConfig = $this->getTabConfig($tab);
        $modelClass = $tabConfig['model'];

        $item = $modelClass::withoutTenant()->findOrFail($id);
        $item->update(['is_active' => true]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Data {$tabConfig['name']} berhasil dipulihkan.",
            ]);
        }

        return back()->with('success', "Data {$tabConfig['name']} berhasil dipulihkan.");
    }

    /**
     * Toggle Switch Status Aktif / Non-Aktif
     */
    public function toggleStatus(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $tab = $request->input('tab', 'pendidikan');
        $tabConfig = $this->getTabConfig($tab);
        $modelClass = $tabConfig['model'];

        $item = $modelClass::withoutTenant()->findOrFail($id);
        $newStatus = !$item->is_active;
        $item->update(['is_active' => $newStatus]);

        $statusLabel = $newStatus ? 'Aktif' : 'Non-Aktif';

        if ($request->wantsJson()) {
            return response()->json([
                'success'   => true,
                'message'   => "Status {$tabConfig['name']} diubah menjadi {$statusLabel}.",
                'is_active' => $newStatus,
            ]);
        }

        return back()->with('success', "Status {$tabConfig['name']} diubah menjadi {$statusLabel}.");
    }

    /**
     * Get Auxiliary Options (Dropdown Jenjang & Jurusan)
     */
    public function options(Request $request): JsonResponse
    {
        $tenantId = $request->input('tenant_id') ?: auth()->user()?->tenant_id;

        $jenjang = Jenjang::withoutTenant()
            ->when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))
            ->where('is_active', true)
            ->orderBy('nama_jenjang', 'asc')
            ->get(['id', 'nama_jenjang as nama', 'kode_jenjang as kode']);

        $jurusan = Jurusan::withoutTenant()
            ->when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))
            ->where('is_active', true)
            ->orderBy('nama_jurusan', 'asc')
            ->get(['id', 'nama_jurusan as nama', 'kategori as kode']);

        return response()->json([
            'success' => true,
            'data'    => [
                'jenjang' => $jenjang,
                'jurusan' => $jurusan,
            ],
        ]);
    }
}
