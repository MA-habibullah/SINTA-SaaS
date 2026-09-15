<?php

namespace Modules\Akademik\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
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
use Modules\Akademik\Entities\PemetaanMapel;
use Modules\Core\Services\SecurityPayloadService;

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
            'pemetaan_mapel' => [
                'name'        => 'Jadwal & Pemetaan Pengampu',
                'model'       => PemetaanMapel::class,
                'name_field'  => 'nama_pemetaan_mapel',
                'code_field'  => 'kelompok_id',
                'search_cols' => ['nama_pemetaan_mapel', 'kelompok_id', 'tahun_ajaran', 'semester'],
            ],
        ];

        return $configs[$tab] ?? $configs['pendidikan'];
    }

    /**
     * Dashboard Master Data Kelembagaan & Akademik
     */
    public function index(Request $request): InertiaResponse|JsonResponse
    {
        $defaultTab = 'pendidikan';
        if ($request->is('*kelas*')) {
            $defaultTab = 'kelas';
        } elseif ($request->is('*mapel*')) {
            $defaultTab = 'mata_pelajaran';
        } elseif ($request->is('*jadwal*')) {
            $defaultTab = 'pemetaan_mapel';
        }

        $activeTab       = $request->input('tab', $defaultTab);
        $validTabs       = ['pendidikan', 'jenjang', 'jurusan', 'kelas', 'mata_pelajaran', 'program_pengajaran', 'tahun_ajaran', 'angkatan', 'kurikulum', 'pemetaan_mapel'];
        if (!in_array($activeTab, $validTabs)) {
            $activeTab = $defaultTab;
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

        // Eager load relasi khusus kelas & pemetaan_mapel
        $filterTahunAjaran = $request->input('tahun_ajaran', '');
        $filterSemester    = $request->input('semester', '');
        $filterKelasId     = $request->input('kelas_id', '');
        $filterHari        = $request->input('hari', '');
        $filterRuangan     = $request->input('ruangan', '');

        if ($activeTab === 'kelas') {
            $query->with(['jenjang', 'jurusan', 'tenant:id,nama_sekolah']);
            if ($request->filled('jenjang_id')) {
                $query->where('id_jenjang', $request->input('jenjang_id'));
            }
            if ($request->filled('jurusan_id')) {
                $query->where('id_jurusan', $request->input('jurusan_id'));
            }
        } elseif ($activeTab === 'pemetaan_mapel') {
            $query->with(['kelas', 'mapel', 'guru', 'tenant:id,nama_sekolah']);
            if (!empty($filterTahunAjaran)) {
                $query->where('tahun_ajaran', $filterTahunAjaran);
            }
            if (!empty($filterSemester)) {
                $query->where('semester', $filterSemester);
            }
            if (!empty($filterKelasId)) {
                $query->where('kelas_id', $filterKelasId);
            }
            if (!empty($filterHari) && $filterHari !== 'Semua') {
                $query->where('hari', $filterHari);
            }
            if (!empty($filterRuangan) && $filterRuangan !== 'Semua') {
                $query->where('ruangan', $filterRuangan);
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
        if (in_array($activeTab, ['tahun_ajaran', 'angkatan', 'pemetaan_mapel'])) {
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
            } elseif ($activeTab === 'pemetaan_mapel') {
                $item->nama_kelas = $item->kelas?->nama_kelas ?? $item->kelas_id ?? '-';
                $item->nama_mapel = $item->mapel?->nama_mata_pelajaran ?? $item->kelompok_id ?? '-';
                $item->nama_guru  = $item->guru?->nama_lengkap ?? '-';
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

        $listKelas = ($isSuperAdmin && empty($filterTenantId) ? Kelas::withoutTenant() : Kelas::query())
            ->when($effectiveTenant, fn($q) => $q->where('tenant_id', $effectiveTenant))
            ->where('is_active', true)
            ->orderBy('nama_kelas', 'asc')
            ->get(['id', 'nama_kelas', 'kode_kelas']);

        $listMapel = ($isSuperAdmin && empty($filterTenantId) ? MataPelajaran::withoutTenant() : MataPelajaran::query())
            ->when($effectiveTenant, fn($q) => $q->where('tenant_id', $effectiveTenant))
            ->where('is_active', true)
            ->orderBy('nama_mata_pelajaran', 'asc')
            ->get(['id', 'nama_mata_pelajaran', 'kategori']);

        $listGuru = DB::table('core.users')
            ->when($effectiveTenant, fn($q) => $q->where('tenant_id', $effectiveTenant))
            ->where('is_active', true)
            ->orderBy('nama_lengkap', 'asc')
            ->get(['id', 'nama_lengkap', 'email']);

        $dbTahunAjaran = ($isSuperAdmin && empty($filterTenantId) ? TahunAjaran::withoutTenant() : TahunAjaran::query())
            ->when($effectiveTenant, fn($q) => $q->where('tenant_id', $effectiveTenant))
            ->where('is_active', true)
            ->pluck('nama_tahun_ajaran')
            ->toArray();

        $pmTahunAjaran = PemetaanMapel::withoutTenant()
            ->when($effectiveTenant, fn($q) => $q->where('tenant_id', $effectiveTenant))
            ->whereNotNull('tahun_ajaran')
            ->distinct()
            ->pluck('tahun_ajaran')
            ->toArray();

        // Standard dynamic fallback range (current year +/- 3)
        $currentYear = (int) date('Y');
        $standardTA = [
            ($currentYear + 1) . '/' . ($currentYear + 2),
            $currentYear . '/' . ($currentYear + 1),
            ($currentYear - 1) . '/' . $currentYear,
            ($currentYear - 2) . '/' . ($currentYear - 1),
            ($currentYear - 3) . '/' . ($currentYear - 2),
        ];

        $combinedTA = array_values(array_unique(array_filter(array_merge($dbTahunAjaran, $pmTahunAjaran, $standardTA))));
        rsort($combinedTA);

        $listTahunAjaran = array_map(function ($ta) {
            return [
                'id'   => $ta,
                'nama' => $ta,
            ];
        }, $combinedTA);

        // List Ruangan & Jadwal Statistics for UI Dashboard
        $listRuangan = PemetaanMapel::withoutTenant()
            ->when($effectiveTenant, fn($q) => $q->where('tenant_id', $effectiveTenant))
            ->whereNotNull('ruangan')
            ->where('ruangan', '!=', '')
            ->distinct()
            ->orderBy('ruangan', 'asc')
            ->pluck('ruangan')
            ->toArray();

        $jadwalStats = [
            'total_jadwal' => 0,
            'total_guru'   => 0,
            'total_ruang'  => 0,
            'total_jp'     => 0,
        ];

        if ($activeTab === 'pemetaan_mapel') {
            $baseJadwalQuery = PemetaanMapel::withoutTenant()
                ->when($effectiveTenant, fn($q) => $q->where('tenant_id', $effectiveTenant))
                ->where('is_active', true);
            if (!empty($filterTahunAjaran)) {
                $baseJadwalQuery->where('tahun_ajaran', $filterTahunAjaran);
            }
            if (!empty($filterSemester)) {
                $baseJadwalQuery->where('semester', $filterSemester);
            }

            $jadwalStats['total_jadwal'] = (clone $baseJadwalQuery)->count();
            $jadwalStats['total_guru']   = (clone $baseJadwalQuery)->distinct()->whereNotNull('guru_id')->count('guru_id');
            $jadwalStats['total_ruang']  = (clone $baseJadwalQuery)->distinct()->whereNotNull('ruangan')->where('ruangan', '!=', '')->count('ruangan');
            $jadwalStats['total_jp']     = (int) (clone $baseJadwalQuery)->sum('jam_pelajaran');
        }

        // Zero-SSR Data Exposure Protection (Anti-Scraping / View Source Zero Leakage)
        $isInitialSsr = !$request->header('X-Inertia') && !$request->has('async');

        if ($isInitialSsr) {
            return Inertia::render('Akademik/Master/Index', [
                'activeTab'       => $activeTab,
                'items'           => null,
                'tenants'         => null,
                'listJenjang'     => null,
                'listJurusan'     => null,
                'listKelas'       => null,
                'listMapel'       => null,
                'listGuru'        => null,
                'listTahunAjaran' => null,
                'listRuangan'     => null,
                'jadwalStats'     => null,
                'isSuperAdmin'    => $isSuperAdmin,
                'userRole'        => $user?->role?->nama_role ?? ($user?->isSuperAdmin() ? 'super_admin' : 'admin_sekolah'),
                'filters'         => [
                    'tab'          => $activeTab,
                    'search'       => $search,
                    'tenant_id'    => $filterTenantId,
                    'jenjang_id'   => $request->input('jenjang_id', ''),
                    'jurusan_id'   => $request->input('jurusan_id', ''),
                    'tahun_ajaran' => $filterTahunAjaran,
                    'semester'     => $filterSemester,
                    'kelas_id'     => $filterKelasId,
                    'hari'         => $filterHari,
                    'ruangan'      => $filterRuangan,
                    'trash'        => $trashMode,
                    'per_page'     => $perPage,
                ],
            ]);
        }

        // 1. Explicit Async API Request (On-Demand Client Fetch via Axios)
        if ($request->has('async') && !$request->header('X-Inertia')) {
            return response()->json([
                'success'         => true,
                'activeTab'       => $activeTab,
                'data'            => SecurityPayloadService::sanitize($items, $isSuperAdmin ? [] : ['tenant_id']),
                'tenants'         => $isSuperAdmin ? $tenants : [],
                'listJenjang'     => $listJenjang,
                'listJurusan'     => $listJurusan,
                'listKelas'       => $listKelas,
                'listMapel'       => $listMapel,
                'listGuru'        => $listGuru,
                'listTahunAjaran' => $listTahunAjaran,
                'listRuangan'     => $listRuangan,
                'jadwalStats'     => $jadwalStats,
            ]);
        }

        // 2. Inertia Web Response (SPA navigation)
        return Inertia::render('Akademik/Master/Index', [
            'activeTab'       => $activeTab,
            'items'           => SecurityPayloadService::sanitize($items, $isSuperAdmin ? [] : ['tenant_id']),
            'tenants'         => $isSuperAdmin ? $tenants : [],
            'listJenjang'     => $listJenjang,
            'listJurusan'     => $listJurusan,
            'listKelas'       => $listKelas,
            'listMapel'       => $listMapel,
            'listGuru'        => $listGuru,
            'listTahunAjaran' => $listTahunAjaran,
            'listRuangan'     => $listRuangan,
            'jadwalStats'     => $jadwalStats,
            'isSuperAdmin'    => $isSuperAdmin,
            'userRole'        => $user?->role?->nama_role ?? ($user?->isSuperAdmin() ? 'super_admin' : 'admin_sekolah'),
            'filters'         => [
                'tab'          => $activeTab,
                'search'       => $search,
                'tenant_id'    => $filterTenantId,
                'jenjang_id'   => $request->input('jenjang_id', ''),
                'jurusan_id'   => $request->input('jurusan_id', ''),
                'tahun_ajaran' => $filterTahunAjaran,
                'semester'     => $filterSemester,
                'kelas_id'     => $filterKelasId,
                'hari'         => $filterHari,
                'ruangan'      => $filterRuangan,
                'trash'        => $trashMode,
                'per_page'     => $perPage,
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
        } elseif ($tab === 'pemetaan_mapel') {
            $validated = $request->validate([
                'kelas_id'      => 'required|string',
                'mapel_id'      => 'required|string',
                'guru_id'       => 'required|uuid',
                'tahun_ajaran'  => 'required|string|max:20',
                'semester'      => 'required|string|max:10',
                'kelompok_id'   => 'nullable|string|max:100',
                'kkm'           => 'nullable|numeric|min:0|max:100',
                'jam_pelajaran' => 'nullable|integer|min:1|max:10',
                'hari'          => 'nullable|string|max:20',
                'jam_mulai'     => 'nullable|string|max:10',
                'jam_selesai'   => 'nullable|string|max:10',
                'ruangan'       => 'nullable|string|max:100',
            ]);
            $data = array_merge($data, $validated);
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

        $item = $modelClass::create($data);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Data {$tabConfig['name']} berhasil ditambahkan.",
                'data'    => $item,
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
        } elseif ($tab === 'pemetaan_mapel') {
            $validated = $request->validate([
                'kelas_id'      => 'required|string',
                'mapel_id'      => 'required|string',
                'guru_id'       => 'required|uuid',
                'tahun_ajaran'  => 'required|string|max:20',
                'semester'      => 'required|string|max:10',
                'kelompok_id'   => 'nullable|string|max:100',
                'kkm'           => 'nullable|numeric|min:0|max:100',
                'jam_pelajaran' => 'nullable|integer|min:1|max:10',
                'hari'          => 'nullable|string|max:20',
                'jam_mulai'     => 'nullable|string|max:10',
                'jam_selesai'   => 'nullable|string|max:10',
                'ruangan'       => 'nullable|string|max:100',
            ]);
            $updateData = $validated;
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
     * Download Format Template CSV / Excel untuk Import Jadwal & Pemetaan Mapel
     */
    public function downloadTemplateJadwal(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="Template_Import_Jadwal_Pelajaran.csv"',
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');
            fputs($handle, "\xEF\xBB\xBF");

            // Header kolom template
            fputcsv($handle, [
                'Tahun Ajaran',
                'Semester',
                'Nama Kelas',
                'Nama Mata Pelajaran',
                'Nama Guru Pengampu',
                'Kelompok Mapel',
                'KKM',
                'Jam Pelajaran',
                'Hari',
                'Jam Mulai',
                'Jam Selesai',
                'Ruangan',
            ]);

            // Sample rows
            fputcsv($handle, [
                '2026/2027',
                'Ganjil',
                'X IPA 1',
                'Matematika Wajib',
                'Budi Administrator',
                'Kelompok A (Umum)',
                '75',
                '2',
                'Senin',
                '07:30',
                '09:00',
                'R. 101',
            ]);
            fputcsv($handle, [
                '2026/2027',
                'Ganjil',
                'X IPA 1',
                'Bahasa Indonesia',
                'Budi Administrator',
                'Kelompok A (Umum)',
                '75',
                '2',
                'Selasa',
                '09:15',
                '10:45',
                'R. 101',
            ]);

            fclose($handle);
        }, 'Template_Import_Jadwal_Pelajaran.csv', $headers);
    }

    /**
     * Ekspor Data Jadwal Pelajaran Aktif ke File CSV / Excel
     */
    public function exportJadwal(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $user         = auth()->user();
        $isSuperAdmin = $user && ($user->isSuperAdmin() || in_array($user->role?->nama_role ?? '', ['super_admin', 'superadmin', 'admin']));
        $tenantId     = $isSuperAdmin ? ($request->input('tenant_id') ?: $user->tenant_id) : $user->tenant_id;
        $tahunAjaran  = $request->input('tahun_ajaran', '');
        $semester     = $request->input('semester', '');
        $kelasId      = $request->input('kelas_id', '');

        $query = PemetaanMapel::withoutTenant()
            ->when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))
            ->where('is_active', true)
            ->with(['kelas', 'mapel', 'guru']);

        if (!empty($tahunAjaran)) {
            $query->where('tahun_ajaran', $tahunAjaran);
        }
        if (!empty($semester)) {
            $query->where('semester', $semester);
        }
        if (!empty($kelasId)) {
            $query->where('kelas_id', $kelasId);
        }

        $items = $query->orderBy('tahun_ajaran', 'desc')
            ->orderBy('semester', 'asc')
            ->orderBy('kelas_id', 'asc')
            ->get();

        $filename = 'Jadwal_Pelajaran_' . ($tahunAjaran ? str_replace('/', '-', $tahunAjaran) : 'Semua') . '_' . ($semester ?: 'Semua') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        return response()->streamDownload(function () use ($items) {
            $handle = fopen('php://output', 'w');
            fputs($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'Tahun Ajaran',
                'Semester',
                'Nama Kelas',
                'Nama Mata Pelajaran',
                'Nama Guru Pengampu',
                'Kelompok Mapel',
                'KKM',
                'Jam Pelajaran',
                'Hari',
                'Jam Mulai',
                'Jam Selesai',
                'Ruangan',
            ]);

            foreach ($items as $row) {
                fputcsv($handle, [
                    $row->tahun_ajaran ?? '',
                    $row->semester ?? '',
                    $row->kelas?->nama_kelas ?? $row->kelas_id ?? '',
                    $row->mapel?->nama_mata_pelajaran ?? $row->kelompok_id ?? '',
                    $row->guru?->nama_lengkap ?? '',
                    $row->kelompok_id ?? 'Kelompok A (Umum)',
                    $row->kkm ?? 75,
                    $row->jam_pelajaran ?? 2,
                    $row->hari ?? '',
                    $row->jam_mulai ?? '',
                    $row->jam_selesai ?? '',
                    $row->ruangan ?? '',
                ]);
            }

            fclose($handle);
        }, $filename, $headers);
    }

    /**
     * Import Data Jadwal Pelajaran dari CSV / Excel File
     */
    public function importJadwal(Request $request): RedirectResponse|JsonResponse
    {
        $request->validate([
            'file'          => 'required|file|mimes:csv,txt|max:5120',
            'tahun_ajaran'  => 'nullable|string|max:20',
            'semester'      => 'nullable|string|max:10',
            'mode'          => 'nullable|string|in:append,replace',
        ]);

        $user         = auth()->user();
        $isSuperAdmin = $user && ($user->isSuperAdmin() || in_array($user->role?->nama_role ?? '', ['super_admin', 'superadmin', 'admin']));
        $tenantId     = $isSuperAdmin ? ($request->input('tenant_id') ?: $user->tenant_id) : $user->tenant_id;

        $targetTA     = $request->input('tahun_ajaran');
        $targetSem    = $request->input('semester');
        $mode         = $request->input('mode', 'append');

        // Preload master lookup dictionaries for fast O(1) matching
        $kelasList = Kelas::withoutTenant()->where('tenant_id', $tenantId)->get(['id', 'nama_kelas', 'kode_kelas']);
        $mapelList = MataPelajaran::withoutTenant()->where('tenant_id', $tenantId)->get(['id', 'nama_mata_pelajaran', 'kategori']);
        $guruList  = DB::table('core.users')->where('tenant_id', $tenantId)->get(['id', 'nama_lengkap', 'email']);

        $file = $request->file('file');
        $path = $file->getRealPath();

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (empty($lines) || count($lines) <= 1) {
            $msg = 'File CSV kosong atau tidak memiliki baris data.';
            return $request->wantsJson() ? response()->json(['success' => false, 'message' => $msg], 422) : back()->with('error', $msg);
        }

        // Auto-detect delimiter (, or ;) based on first line
        $firstLine = $lines[0];
        $delimiter = substr_count($firstLine, ';') > substr_count($firstLine, ',') ? ';' : ',';

        $rows = array_map(fn($line) => str_getcsv($line, $delimiter), $lines);

        // Header check
        $header = array_shift($rows);
        if (isset($header[0])) {
            $header[0] = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $header[0]);
        }

        // Optional: If mode == replace, delete existing records for this TA & Semester
        if ($mode === 'replace' && !empty($targetTA) && !empty($targetSem)) {
            PemetaanMapel::withoutTenant()
                ->where('tenant_id', $tenantId)
                ->where('tahun_ajaran', $targetTA)
                ->where('semester', $targetSem)
                ->delete();
        }

        $imported = 0;

        DB::beginTransaction();
        try {
            foreach ($rows as $index => $row) {
                if (empty($row) || count($row) < 3 || empty(trim($row[0] ?? ''))) {
                    continue;
                }

                $rowTA      = !empty($targetTA) ? $targetTA : trim($row[0] ?? '2026/2027');
                $rowSem     = !empty($targetSem) ? $targetSem : trim($row[1] ?? 'Ganjil');
                $namaKelas  = trim($row[2] ?? '');
                $namaMapel  = trim($row[3] ?? '');
                $namaGuru   = trim($row[4] ?? '');
                $kelompok   = trim($row[5] ?? 'Kelompok A (Umum)');
                $kkm        = is_numeric($row[6] ?? null) ? (float)$row[6] : 75.0;
                $jp         = is_numeric($row[7] ?? null) ? (int)$row[7] : 2;
                $hari       = trim($row[8] ?? '');
                $jamMulai   = trim($row[9] ?? '');
                $jamSelesai = trim($row[10] ?? '');
                $ruangan    = trim($row[11] ?? '');

                if (empty($namaKelas) || empty($namaMapel)) {
                    continue;
                }

                // 1. Resolve Kelas
                $kelasObj = $kelasList->first(function ($k) use ($namaKelas) {
                    return strcasecmp($k->nama_kelas, $namaKelas) === 0 || strcasecmp($k->kode_kelas, $namaKelas) === 0 || $k->id === $namaKelas;
                });
                if (!$kelasObj) {
                    $kelasObj = Kelas::create([
                        'id'          => (string) Str::uuid(),
                        'tenant_id'   => $tenantId,
                        'nama_kelas'  => $namaKelas,
                        'kode_kelas'  => strtoupper(Str::slug($namaKelas)),
                        'id_jenjang'  => 'SMA',
                        'id_jurusan'  => 'Umum',
                        'is_active'   => true,
                    ]);
                    $kelasList->push($kelasObj);
                }

                // 2. Resolve Mata Pelajaran
                $mapelObj = $mapelList->first(function ($m) use ($namaMapel) {
                    return strcasecmp($m->nama_mata_pelajaran, $namaMapel) === 0 || $m->id === $namaMapel;
                });
                if (!$mapelObj) {
                    $mapelObj = MataPelajaran::create([
                        'id'                   => (string) Str::uuid(),
                        'tenant_id'            => $tenantId,
                        'nama_mata_pelajaran'  => $namaMapel,
                        'kategori'             => $kelompok ?: 'Umum',
                        'is_active'            => true,
                    ]);
                    $mapelList->push($mapelObj);
                }

                // 3. Resolve Guru
                $guruObj = $guruList->first(function ($g) use ($namaGuru) {
                    return strcasecmp($g->nama_lengkap, $namaGuru) === 0 || strcasecmp($g->email, $namaGuru) === 0 || $g->id === $namaGuru;
                });
                $guruId = $guruObj ? $guruObj->id : $user->id;

                // 4. Upsert Pemetaan Mapel
                PemetaanMapel::updateOrCreate(
                    [
                        'tenant_id'    => $tenantId,
                        'tahun_ajaran' => $rowTA,
                        'semester'     => $rowSem,
                        'kelas_id'     => (string)$kelasObj->id,
                        'mapel_id'     => (string)$mapelObj->id,
                    ],
                    [
                        'id'            => (string) Str::uuid(),
                        'guru_id'       => $guruId,
                        'kelompok_id'   => $kelompok ?: 'Kelompok A (Umum)',
                        'kkm'           => $kkm,
                        'jam_pelajaran' => $jp,
                        'hari'          => $hari,
                        'jam_mulai'     => $jamMulai,
                        'jam_selesai'   => $jamSelesai,
                        'ruangan'       => $ruangan,
                        'is_active'     => true,
                    ]
                );

                $imported++;
            }

            DB::commit();

            $msg = "Import Jadwal Pelajaran Berhasil: {$imported} data jadwal berhasil diproses dan disimpan.";
            if ($request->wantsJson()) {
                return response()->json(['success' => true, 'message' => $msg, 'imported' => $imported]);
            }
            return back()->with('success', $msg);

        } catch (\Exception $e) {
            DB::rollBack();
            $errMsg = "Gagal memproses file import: " . $e->getMessage();
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $errMsg], 422);
            }
            return back()->with('error', $errMsg);
        }
    }

    /**
     * Salin / Kloning Jadwal dari Semester / Tahun Ajaran Sebelumnya
     */
    public function copyJadwal(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'from_tahun_ajaran' => 'required|string|max:20',
            'from_semester'     => 'required|string|max:10',
            'to_tahun_ajaran'   => 'required|string|max:20',
            'to_semester'       => 'required|string|max:10',
            'mode'              => 'nullable|string|in:append,replace',
        ]);

        if ($validated['from_tahun_ajaran'] === $validated['to_tahun_ajaran'] && $validated['from_semester'] === $validated['to_semester']) {
            $msg = "Periode sumber dan periode target tidak boleh sama.";
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return back()->with('error', $msg);
        }

        $user         = auth()->user();
        $isSuperAdmin = $user && ($user->isSuperAdmin() || in_array($user->role?->nama_role ?? '', ['super_admin', 'superadmin', 'admin']));
        $tenantId     = $isSuperAdmin ? ($request->input('tenant_id') ?: $user->tenant_id) : $user->tenant_id;

        $sourceItems = PemetaanMapel::withoutTenant()
            ->where('tenant_id', $tenantId)
            ->where('tahun_ajaran', $validated['from_tahun_ajaran'])
            ->where('semester', $validated['from_semester'])
            ->where('is_active', true)
            ->get();

        if ($sourceItems->isEmpty()) {
            $msg = "Tidak ditemukan data jadwal pada periode sumber ({$validated['from_tahun_ajaran']} - {$validated['from_semester']}).";
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return back()->with('error', $msg);
        }

        if (($validated['mode'] ?? 'append') === 'replace') {
            PemetaanMapel::withoutTenant()
                ->where('tenant_id', $tenantId)
                ->where('tahun_ajaran', $validated['to_tahun_ajaran'])
                ->where('semester', $validated['to_semester'])
                ->delete();
        }

        $copied = 0;
        foreach ($sourceItems as $src) {
            PemetaanMapel::updateOrCreate(
                [
                    'tenant_id'    => $tenantId,
                    'tahun_ajaran' => $validated['to_tahun_ajaran'],
                    'semester'     => $validated['to_semester'],
                    'kelas_id'     => (string)$src->kelas_id,
                    'mapel_id'     => (string)$src->mapel_id,
                ],
                [
                    'id'            => (string) Str::uuid(),
                    'guru_id'       => $src->guru_id,
                    'kelompok_id'   => $src->kelompok_id,
                    'kkm'           => $src->kkm,
                    'jam_pelajaran' => $src->jam_pelajaran,
                    'hari'          => $src->hari,
                    'jam_mulai'     => $src->jam_mulai,
                    'jam_selesai'   => $src->jam_selesai,
                    'ruangan'       => $src->ruangan,
                    'is_active'     => true,
                ]
            );
            $copied++;
        }

        $msg = "Berhasil menyalin {$copied} jadwal pelajaran dari {$validated['from_tahun_ajaran']} ({$validated['from_semester']}) ke {$validated['to_tahun_ajaran']} ({$validated['to_semester']}).";
        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => $msg, 'copied' => $copied]);
        }
        return back()->with('success', $msg);
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
