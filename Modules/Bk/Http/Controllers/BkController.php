<?php

namespace Modules\Bk\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\Bk\Entities\Pelanggaran;
use Modules\Bk\Entities\MasterPelanggaran;
use Modules\Bk\Entities\Konseling;
use Modules\Siswa\Entities\Siswa;
use Modules\Core\Entities\Tenant;

class BkController extends Controller
{
    /**
     * Check if user is Super Admin across platform
     */
    protected function checkIsSuperAdmin($user): bool
    {
        if (!$user) return false;
        return (
            !empty($user->is_super_admin) ||
            ($user->role && (
                (is_object($user->role) && in_array(strtolower($user->role->nama_role ?? ''), ['super_admin', 'superadmin', 'super admin'])) ||
                (is_string($user->role) && in_array(strtolower($user->role), ['super_admin', 'superadmin', 'super admin']))
            )) ||
            ($user->tenant_id === '00000000-0000-0000-0000-000000000000') ||
            (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) ||
            (method_exists($user, 'hasRole') && $user->hasRole('super_admin'))
        );
    }

    /**
     * Display Kedisiplinan & Pelanggaran Siswa Page
     */
    public function kedisiplinan(Request $request): InertiaResponse|JsonResponse
    {
        $user = $request->user() ?: Auth::user();
        $isSuperAdmin = $this->checkIsSuperAdmin($user);

        $tenants = [];
        $selectedTenantId = $request->input('tenant_id');

        if ($isSuperAdmin) {
            $tenants = Tenant::select('id', 'nama_sekolah', 'npsn')
                ->orderBy('nama_sekolah', 'asc')
                ->get();

            if ($request->filled('tenant_id') && $selectedTenantId !== 'all' && $selectedTenantId !== '') {
                $query = Pelanggaran::withoutTenant()->where('bk.pelanggaran_siswa.tenant_id', $selectedTenantId);
                $pointsQuery = Pelanggaran::withoutTenant()->where('bk.pelanggaran_siswa.tenant_id', $selectedTenantId);
                $trendQuery = Pelanggaran::withoutTenant()->where('bk.pelanggaran_siswa.tenant_id', $selectedTenantId);
                $masterQuery = MasterPelanggaran::withoutTenant()->where(function ($mq) use ($selectedTenantId) {
                    $mq->where('tenant_id', $selectedTenantId)->orWhereNull('tenant_id');
                });
                $tenant = Tenant::find($selectedTenantId);
            } else {
                $query = Pelanggaran::withoutTenant();
                $pointsQuery = Pelanggaran::withoutTenant();
                $trendQuery = Pelanggaran::withoutTenant();
                $masterQuery = MasterPelanggaran::withoutTenant();
                $tenant = Tenant::first();
            }
        } else {
            $tenantId = $user->tenant_id ?? null;
            $query = Pelanggaran::query();
            $pointsQuery = Pelanggaran::query();
            $trendQuery = Pelanggaran::query();
            $masterQuery = MasterPelanggaran::query();
            $tenant = Tenant::find($tenantId);
        }

        // Query Pelanggaran with Search & Filters
        $query->with(['siswa', 'masterPelanggaran'])
            ->orderBy('tanggal_kejadian', 'desc')
            ->orderBy('created_at', 'desc');

        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('nama_pelanggaran', 'ILIKE', "%{$search}%")
                  ->orWhere('snapshot_nama_siswa', 'ILIKE', "%{$search}%")
                  ->orWhere('snapshot_nisn', 'ILIKE', "%{$search}%")
                  ->orWhere('snapshot_nama_kelas', 'ILIKE', "%{$search}%")
                  ->orWhere('petugas_pencatat', 'ILIKE', "%{$search}%")
                  ->orWhere('keterangan', 'ILIKE', "%{$search}%")
                  ->orWhereHas('siswa', function ($sq) use ($search) {
                      $sq->where('nama_lengkap', 'ILIKE', "%{$search}%")
                         ->orWhere('nisn', 'ILIKE', "%{$search}%");
                  });
            });
        }

        if ($request->filled('kategori') && in_array($request->input('kategori'), ['Ringan', 'Sedang', 'Berat', 'Khusus'])) {
            $query->where('kategori', $request->input('kategori'));
        }

        if ($request->filled('status_pembinaan') && in_array($request->input('status_pembinaan'), ['Belum Dibina', 'Dalam Pembinaan', 'Selesai'])) {
            $query->where('status_pembinaan', $request->input('status_pembinaan'));
        }

        if ($request->filled('start_date')) {
            $query->whereDate('tanggal_kejadian', '>=', $request->input('start_date'));
        }

        if ($request->filled('end_date')) {
            $query->whereDate('tanggal_kejadian', '<=', $request->input('end_date'));
        }

        $perPage = (int) $request->input('per_page', 15);
        if ($perPage < 5) $perPage = 5;
        if ($perPage > 100) $perPage = 100;

        $pelanggaranList = $query->paginate($perPage)->withQueryString();

        // Master Rules List
        $masterList = $masterQuery->orderBy('kategori', 'asc')
            ->orderBy('nama_pelanggaran', 'asc')
            ->get();

        // 1. KPI Counters per student point threshold
        $allStudentPoints = $pointsQuery->select('siswa_id', DB::raw('SUM(poin_pelanggaran) as total_poin'), DB::raw('COUNT(id) as total_kasus'))
            ->whereNotNull('siswa_id')
            ->groupBy('siswa_id')
            ->get();

        $totalKasus = (clone $pointsQuery)->count();
        $totalSiswaMelanggar = $allStudentPoints->count();
        $waliKelasCount = 0; // Poin 1 - 24
        $sp1Count = 0;        // Poin 25 - 49
        $sp2Count = 0;        // Poin 50 - 74
        $sp3Count = 0;        // Poin >= 75

        foreach ($allStudentPoints as $sp) {
            $pts = (int) $sp->total_poin;
            if ($pts >= 75) {
                $sp3Count++;
            } elseif ($pts >= 50) {
                $sp2Count++;
            } elseif ($pts >= 25) {
                $sp1Count++;
            } elseif ($pts > 0) {
                $waliKelasCount++;
            }
        }

        $kpi = [
            'total_kasus'           => $totalKasus,
            'total_siswa_melanggar' => $totalSiswaMelanggar,
            'wali_kelas'            => $waliKelasCount,
            'sp1_bk'                => $sp1Count,
            'sp2_skorsing'          => $sp2Count,
            'sp3_do'                => $sp3Count,
        ];

        // 2. Siswa Butuh Pembinaan (Top Violators by Accumulated Points)
        $topStudentsRaw = (clone $pointsQuery)->with('siswa')
            ->select(
                'siswa_id',
                'snapshot_nama_siswa',
                'snapshot_nisn',
                'snapshot_nama_kelas',
                DB::raw('SUM(poin_pelanggaran) as total_poin'),
                DB::raw('COUNT(id) as jumlah_kasus'),
                DB::raw('MAX(tanggal_kejadian) as kejadian_terakhir')
            )
            ->whereNotNull('siswa_id')
            ->groupBy('siswa_id', 'snapshot_nama_siswa', 'snapshot_nisn', 'snapshot_nama_kelas')
            ->orderByDesc('total_poin')
            ->limit(10)
            ->get();

        $topStudents = $topStudentsRaw->map(function ($item) {
            $pts = (int) $item->total_poin;
            $tingkat = 'Bimbingan Wali Kelas';
            $badgeColor = 'bg-blue-50 text-blue-700 border-blue-200';

            if ($pts >= 75) {
                $tingkat = 'SP 3 / Panggilan Ortu / DO';
                $badgeColor = 'bg-rose-50 text-rose-700 border-rose-200';
            } elseif ($pts >= 50) {
                $tingkat = 'SP 2 / Skorsing';
                $badgeColor = 'bg-amber-50 text-amber-700 border-amber-200';
            } elseif ($pts >= 25) {
                $tingkat = 'SP 1 / Panggilan BK';
                $badgeColor = 'bg-orange-50 text-orange-700 border-orange-200';
            }

            return [
                'siswa_id'          => $item->siswa_id,
                'nama_siswa'        => $item->snapshot_nama_siswa ?? $item->siswa?->nama_lengkap ?? 'Siswa',
                'nisn'              => $item->snapshot_nisn ?? $item->siswa?->nisn ?? '-',
                'nama_kelas'        => $item->snapshot_nama_kelas ?? 'Kelas Siswa',
                'total_poin'        => $pts,
                'jumlah_kasus'      => (int) $item->jumlah_kasus,
                'kejadian_terakhir' => $item->kejadian_terakhir,
                'tingkat_sanksi'    => $tingkat,
                'badge_color'       => $badgeColor,
            ];
        });

        // 3. Tren Pelanggaran 6 Bulan Terakhir
        $monthlyTrend = $trendQuery->select(
                DB::raw("TO_CHAR(tanggal_kejadian, 'YYYY-MM') as periode"),
                DB::raw("COUNT(id) as total")
            )
            ->whereNotNull('tanggal_kejadian')
            ->groupBy(DB::raw("TO_CHAR(tanggal_kejadian, 'YYYY-MM')"))
            ->orderBy(DB::raw("TO_CHAR(tanggal_kejadian, 'YYYY-MM')"), 'desc')
            ->limit(6)
            ->get()
            ->reverse()
            ->values();

        $tenantInfo = [
            'nama_sekolah'   => $tenant?->nama_sekolah ?? 'SMA / SMK Negeri SINTA',
            'alamat'         => $tenant?->alamat ?? 'Jl. Pendidikan No. 10',
            'kabupaten_kota' => $tenant?->kabupaten_kota ?? 'Kota',
            'provinsi'       => $tenant?->provinsi ?? 'Provinsi',
            'telepon'        => $tenant?->telepon ?? '(021) 555-1234',
            'email'          => $tenant?->email ?? 'bk@sekolah.sch.id',
            'nama_kepsek'    => $tenant?->nama_kepsek ?? 'Kepala Sekolah',
            'nip_kepsek'     => $tenant?->nip_kepsek ?? '-',
        ];

        if ($request->wantsJson()) {
            return response()->json([
                'success'         => true,
                'pelanggaranList' => $pelanggaranList,
                'masterList'      => $masterList,
                'kpi'             => $kpi,
                'topStudents'     => $topStudents,
                'monthlyTrend'    => $monthlyTrend,
                'tenantInfo'      => $tenantInfo,
                'isSuperAdmin'    => $isSuperAdmin,
                'tenants'         => $tenants,
            ]);
        }

        return Inertia::render('Bk/Kedisiplinan/Index', [
            'pelanggaranList' => $pelanggaranList,
            'masterList'      => $masterList,
            'kpi'             => $kpi,
            'topStudents'     => $topStudents,
            'monthlyTrend'    => $monthlyTrend,
            'tenantInfo'      => $tenantInfo,
            'isSuperAdmin'    => $isSuperAdmin,
            'tenants'         => $tenants,
            'filters'         => $request->only(['search', 'kategori', 'status_pembinaan', 'start_date', 'end_date', 'per_page', 'tenant_id']),
        ]);
    }

    /**
     * Alias for general BK index
     */
    public function index(Request $request): InertiaResponse|JsonResponse
    {
        return $this->kedisiplinan($request);
    }

    /**
     * Store new student violation record
     */
    public function storePelanggaran(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'siswa_id'             => ['required', 'uuid', \Illuminate\Validation\Rule::exists(Siswa::class, 'id')],
            'pelanggaran_id'       => ['nullable', 'uuid', \Illuminate\Validation\Rule::exists(MasterPelanggaran::class, 'id')],
            'nama_pelanggaran'     => 'required|string|max:255',
            'kategori'             => 'required|in:Ringan,Sedang,Berat,Khusus',
            'poin_pelanggaran'     => 'required|integer|min:1|max:100',
            'tanggal_kejadian'     => 'required|date',
            'tindakan_hukuman'     => 'nullable|string',
            'petugas_pencatat'     => 'nullable|string|max:255',
            'keterangan'           => 'nullable|string',
            'status_pembinaan'     => 'nullable|in:Belum Dibina,Dalam Pembinaan,Selesai',
            'foto_bukti'           => 'nullable|image|max:3072',
        ]);

        // Student snapshot info
        $siswa = Siswa::withoutTenant()->find($validated['siswa_id']);
        if ($siswa) {
            $validated['snapshot_nama_siswa'] = $siswa->nama_lengkap;
            $validated['snapshot_nisn']       = $siswa->nisn;
            $validated['snapshot_nis']        = $siswa->nis;
            $validated['snapshot_nama_kelas'] = 'Kelas ' . ($siswa->tingkat ?? 'X');
            if (empty($validated['tenant_id'])) {
                $validated['tenant_id'] = $siswa->tenant_id;
            }
        }

        $validated['nama_pelanggaran_siswa'] = $validated['nama_pelanggaran'];
        $validated['deskripsi'] = $validated['keterangan'] ?? $validated['nama_pelanggaran'];
        $validated['status_pembinaan'] = $validated['status_pembinaan'] ?? 'Belum Dibina';

        // File upload handling
        if ($request->hasFile('foto_bukti')) {
            $path = $request->file('foto_bukti')->store('pelanggaran', 'public');
            $validated['foto_bukti'] = '/storage/' . $path;
        }

        $pelanggaran = Pelanggaran::create($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Laporan pelanggaran siswa berhasil dicatat.',
                'data'    => $pelanggaran,
            ], 201);
        }

        return back()->with('success', 'Laporan pelanggaran siswa berhasil dicatat.');
    }

    /**
     * Update violation record
     */
    public function updatePelanggaran(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $pelanggaran = Pelanggaran::withoutTenant()->findOrFail($id);

        $validated = $request->validate([
            'nama_pelanggaran' => 'required|string|max:255',
            'kategori'         => 'required|in:Ringan,Sedang,Berat,Khusus',
            'poin_pelanggaran' => 'required|integer|min:1|max:100',
            'tanggal_kejadian' => 'required|date',
            'tindakan_hukuman' => 'nullable|string',
            'petugas_pencatat' => 'nullable|string|max:255',
            'keterangan'       => 'nullable|string',
            'status_pembinaan' => 'required|in:Belum Dibina,Dalam Pembinaan,Selesai',
            'foto_bukti'       => 'nullable|image|max:3072',
        ]);

        $validated['nama_pelanggaran_siswa'] = $validated['nama_pelanggaran'];
        $validated['deskripsi'] = $validated['keterangan'] ?? $validated['nama_pelanggaran'];

        if ($request->hasFile('foto_bukti')) {
            $path = $request->file('foto_bukti')->store('pelanggaran', 'public');
            $validated['foto_bukti'] = '/storage/' . $path;
        }

        $pelanggaran->update($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Data pelanggaran berhasil diperbarui.',
                'data'    => $pelanggaran,
            ]);
        }

        return back()->with('success', 'Data pelanggaran berhasil diperbarui.');
    }

    /**
     * Delete violation record
     */
    public function deletePelanggaran(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $pelanggaran = Pelanggaran::withoutTenant()->findOrFail($id);
        $pelanggaran->delete();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Data pelanggaran berhasil dihapus.',
            ]);
        }

        return back()->with('success', 'Data pelanggaran berhasil dihapus.');
    }

    /**
     * Store Master Pelanggaran Rule
     */
    public function storeMasterPelanggaran(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'nama_pelanggaran' => 'required|string|max:255',
            'kategori'         => 'required|in:Ringan,Sedang,Berat,Khusus',
            'bobot_poin'       => 'required|integer|min:1|max:100',
            'deskripsi'        => 'nullable|string',
        ]);

        $validated['nama_master_pelanggaran'] = $validated['nama_pelanggaran'];
        $validated['is_active'] = true;

        $master = MasterPelanggaran::create($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Aturan tata tertib berhasil disimpan.',
                'data'    => $master,
            ], 201);
        }

        return back()->with('success', 'Aturan tata tertib berhasil disimpan.');
    }

    /**
     * Update Master Pelanggaran Rule
     */
    public function updateMasterPelanggaran(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $master = MasterPelanggaran::withoutTenant()->findOrFail($id);

        $validated = $request->validate([
            'nama_pelanggaran' => 'required|string|max:255',
            'kategori'         => 'required|in:Ringan,Sedang,Berat,Khusus',
            'bobot_poin'       => 'required|integer|min:1|max:100',
            'deskripsi'        => 'nullable|string',
            'is_active'        => 'boolean',
        ]);

        $validated['nama_master_pelanggaran'] = $validated['nama_pelanggaran'];
        $master->update($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Aturan tata tertib berhasil diperbarui.',
                'data'    => $master,
            ]);
        }

        return back()->with('success', 'Aturan tata tertib berhasil diperbarui.');
    }

    /**
     * Delete Master Pelanggaran Rule
     */
    public function deleteMasterPelanggaran(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $master = MasterPelanggaran::withoutTenant()->findOrFail($id);
        $master->delete();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Aturan tata tertib berhasil dihapus.',
            ]);
        }

        return back()->with('success', 'Aturan tata tertib berhasil dihapus.');
    }

    /**
     * Live search Siswa for violation input autocomplete
     */
    public function searchSiswa(Request $request): JsonResponse
    {
        $user = $request->user() ?: Auth::user();
        $isSuperAdmin = $this->checkIsSuperAdmin($user);
        $q = trim($request->input('q', ''));
        if (strlen($q) < 2) {
            return response()->json(['success' => true, 'data' => []]);
        }

        $query = $isSuperAdmin ? Siswa::withoutTenant() : Siswa::query();

        if ($request->filled('tenant_id') && $request->input('tenant_id') !== 'all') {
            $query->where('siswa.tenant_id', $request->input('tenant_id'));
        }

        $students = $query->where(function ($sq) use ($q) {
                $sq->where('nama_lengkap', 'ILIKE', "%{$q}%")
                   ->orWhere('nisn', 'ILIKE', "%{$q}%")
                   ->orWhere('nis', 'ILIKE', "%{$q}%");
            })
            ->select('id', 'tenant_id', 'nama_lengkap', 'nisn', 'nis', 'jenis_kelamin')
            ->limit(20)
            ->get()
            ->map(function ($s) {
                return [
                    'id'           => $s->id,
                    'tenant_id'    => $s->tenant_id,
                    'nama_lengkap' => $s->nama_lengkap,
                    'nisn'         => $s->nisn ?? '-',
                    'nis'          => $s->nis ?? '-',
                    'nama_kelas'   => 'Siswa Aktif',
                ];
            });


        return response()->json(['success' => true, 'data' => $students]);
    }

    /**
     * Display Layanan & Bimbingan Konseling (BK) Page
     */
    public function layanan(Request $request): InertiaResponse|JsonResponse
    {
        $user = $request->user() ?: Auth::user();
        $isSuperAdmin = $this->checkIsSuperAdmin($user);
        $tenants = [];
        $selectedTenantId = $request->input('tenant_id');

        if ($isSuperAdmin) {
            $tenants = Tenant::select('id', 'nama_sekolah', 'npsn')
                ->orderBy('nama_sekolah', 'asc')
                ->get();

            if ($request->filled('tenant_id') && $selectedTenantId !== 'all' && $selectedTenantId !== '') {
                $query = Konseling::withoutTenant()->where('bk.catatan_bk.tenant_id', $selectedTenantId);
                $kpiQuery = Konseling::withoutTenant()->where('bk.catatan_bk.tenant_id', $selectedTenantId);
                $breakdownQuery = Konseling::withoutTenant()->where('bk.catatan_bk.tenant_id', $selectedTenantId);
                $trendQuery = Konseling::withoutTenant()->where('bk.catatan_bk.tenant_id', $selectedTenantId);
                $tenant = Tenant::find($selectedTenantId);
            } else {
                $query = Konseling::withoutTenant();
                $kpiQuery = Konseling::withoutTenant();
                $breakdownQuery = Konseling::withoutTenant();
                $trendQuery = Konseling::withoutTenant();
                $tenant = Tenant::first();
            }
        } else {
            $tenantId = $user->tenant_id ?? null;
            $query = Konseling::query();
            $kpiQuery = Konseling::query();
            $breakdownQuery = Konseling::query();
            $trendQuery = Konseling::query();
            $tenant = Tenant::find($tenantId);
        }

        $query->with('siswa')
            ->orderBy('tanggal_konseling', 'desc')
            ->orderBy('created_at', 'desc');

        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('topik_masalah', 'ILIKE', "%{$search}%")
                  ->orWhere('snapshot_nama_siswa', 'ILIKE', "%{$search}%")
                  ->orWhere('snapshot_nisn', 'ILIKE', "%{$search}%")
                  ->orWhere('snapshot_nama_kelas', 'ILIKE', "%{$search}%")
                  ->orWhere('guru_bk_nama', 'ILIKE', "%{$search}%")
                  ->orWhere('ringkasan_konseling', 'ILIKE', "%{$search}%")
                  ->orWhere('solusi_tindak_lanjut', 'ILIKE', "%{$search}%")
                  ->orWhereHas('siswa', function ($sq) use ($search) {
                      $sq->where('nama_lengkap', 'ILIKE', "%{$search}%")
                         ->orWhere('nisn', 'ILIKE', "%{$search}%");
                  });
            });
        }

        if ($request->filled('jenis_konseling') && in_array($request->input('jenis_konseling'), ['Pribadi', 'Sosial', 'Belajar', 'Karier', 'Kedisiplinan'])) {
            $query->where('jenis_konseling', $request->input('jenis_konseling'));
        }

        if ($request->filled('status_kasus') && in_array($request->input('status_kasus'), ['Terbuka', 'Dalam Pendampingan', 'Selesai'])) {
            $query->where('status_kasus', $request->input('status_kasus'));
        }

        if ($request->filled('is_rahasia') && in_array($request->input('is_rahasia'), ['0', '1', 0, 1])) {
            $query->where('is_rahasia', (int) $request->input('is_rahasia'));
        }

        if ($request->filled('start_date')) {
            $query->whereDate('tanggal_konseling', '>=', $request->input('start_date'));
        }

        if ($request->filled('end_date')) {
            $query->whereDate('tanggal_konseling', '<=', $request->input('end_date'));
        }

        $perPage = (int) $request->input('per_page', 15);
        if ($perPage < 5) $perPage = 5;
        if ($perPage > 100) $perPage = 100;

        $konselingList = $query->paginate($perPage)->withQueryString();

        // KPI Counters
        $totalKonseling = (clone $kpiQuery)->count();
        $kasusTerbuka = (clone $kpiQuery)->where('status_kasus', 'Terbuka')->count();
        $dalamPendampingan = (clone $kpiQuery)->where('status_kasus', 'Dalam Pendampingan')->count();
        $kasusSelesai = (clone $kpiQuery)->where('status_kasus', 'Selesai')->count();
        $konselingRahasia = (clone $kpiQuery)->where('is_rahasia', 1)->count();
        $totalSiswaDibina = (clone $kpiQuery)->whereNotNull('siswa_id')->distinct('siswa_id')->count('siswa_id');

        $kpi = [
            'total_konseling'     => $totalKonseling,
            'kasus_terbuka'       => $kasusTerbuka,
            'dalam_pendampingan'  => $dalamPendampingan,
            'kasus_selesai'       => $kasusSelesai,
            'konseling_rahasia'   => $konselingRahasia,
            'total_siswa_dibina'  => $totalSiswaDibina,
        ];

        // Category breakdown
        $kategoriBreakdown = $breakdownQuery->select('jenis_konseling', DB::raw('COUNT(id) as total'))
            ->whereNotNull('jenis_konseling')
            ->groupBy('jenis_konseling')
            ->get();

        // Monthly Trend
        $monthlyTrend = $trendQuery->select(
                DB::raw("TO_CHAR(tanggal_konseling, 'YYYY-MM') as periode"),
                DB::raw("COUNT(id) as total")
            )
            ->whereNotNull('tanggal_konseling')
            ->groupBy(DB::raw("TO_CHAR(tanggal_konseling, 'YYYY-MM')"))
            ->orderBy(DB::raw("TO_CHAR(tanggal_konseling, 'YYYY-MM')"), 'desc')
            ->limit(6)
            ->get()
            ->reverse()
            ->values();

        $tenantInfo = [
            'nama_sekolah'   => $tenant?->nama_sekolah ?? 'SMA / SMK Negeri SINTA',
            'alamat'         => $tenant?->alamat ?? 'Jl. Pendidikan No. 10',
            'kabupaten_kota' => $tenant?->kabupaten_kota ?? 'Kota',
            'provinsi'       => $tenant?->provinsi ?? 'Provinsi',
            'telepon'        => $tenant?->telepon ?? '(021) 555-1234',
            'email'          => $tenant?->email ?? 'bk@sekolah.sch.id',
            'nama_kepsek'    => $tenant?->nama_kepsek ?? 'Kepala Sekolah',
            'nip_kepsek'     => $tenant?->nip_kepsek ?? '-',
        ];

        if ($request->wantsJson()) {
            return response()->json([
                'success'           => true,
                'konselingList'     => $konselingList,
                'kpi'               => $kpi,
                'kategoriBreakdown' => $kategoriBreakdown,
                'monthlyTrend'      => $monthlyTrend,
                'tenantInfo'        => $tenantInfo,
                'isSuperAdmin'      => $isSuperAdmin,
                'tenants'           => $tenants,
            ]);
        }

        return Inertia::render('Bk/Layanan/Index', [
            'konselingList'     => $konselingList,
            'kpi'               => $kpi,
            'kategoriBreakdown' => $kategoriBreakdown,
            'monthlyTrend'      => $monthlyTrend,
            'tenantInfo'        => $tenantInfo,
            'isSuperAdmin'      => $isSuperAdmin,
            'tenants'           => $tenants,
            'filters'           => $request->only(['search', 'jenis_konseling', 'status_kasus', 'is_rahasia', 'start_date', 'end_date', 'per_page', 'tenant_id']),
        ]);
    }

    /**
     * Store counseling record
     */
    public function storeKonseling(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'siswa_id'             => ['required', 'uuid', \Illuminate\Validation\Rule::exists(Siswa::class, 'id')],
            'tanggal_konseling'    => 'required|date',
            'jenis_konseling'      => 'required|in:Pribadi,Sosial,Belajar,Karier,Kedisiplinan',
            'topik_masalah'        => 'required|string|max:255',
            'ringkasan_konseling'  => 'nullable|string',
            'solusi_tindak_lanjut' => 'nullable|string',
            'status_kasus'         => 'required|in:Terbuka,Dalam Pendampingan,Selesai',
            'is_rahasia'           => 'nullable|boolean',
            'guru_bk_nama'         => 'nullable|string|max:255',
            'foto_bukti'           => 'nullable|image|max:3072',
        ]);

        $siswa = Siswa::withoutTenant()->find($validated['siswa_id']);
        if ($siswa) {
            $validated['id_siswa']             = $siswa->id;
            $validated['snapshot_nama_siswa']  = $siswa->nama_lengkap;
            $validated['snapshot_nisn']        = $siswa->nisn;
            $validated['snapshot_nis']         = $siswa->nis;
            $validated['snapshot_nama_kelas']  = 'Kelas ' . ($siswa->tingkat ?? 'X');
            if (empty($validated['tenant_id'])) {
                $validated['tenant_id'] = $siswa->tenant_id;
            }
        }

        $validated['nama_catatan_bk'] = $validated['topik_masalah'];
        $validated['jenis_kasus']     = $validated['jenis_konseling'];
        $validated['catatan']         = $validated['ringkasan_konseling'] ?? '';
        $validated['tindak_lanjut']   = $validated['solusi_tindak_lanjut'] ?? '';
        $validated['is_rahasia']      = $request->boolean('is_rahasia') ? 1 : 0;
        $validated['is_active']       = true;

        if ($request->hasFile('foto_bukti')) {
            $path = $request->file('foto_bukti')->store('konseling', 'public');
            $validated['foto_panggilan'] = '/storage/' . $path;
        }

        $konseling = Konseling::create($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Sesi konseling berhasil dicatat.',
                'data'    => $konseling,
            ], 201);
        }

        return back()->with('success', 'Sesi konseling berhasil disimpan.');
    }

    /**
     * Update counseling record
     */
    public function updateKonseling(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $konseling = Konseling::withoutTenant()->findOrFail($id);

        $validated = $request->validate([
            'tanggal_konseling'    => 'required|date',
            'jenis_konseling'      => 'required|in:Pribadi,Sosial,Belajar,Karier,Kedisiplinan',
            'topik_masalah'        => 'required|string|max:255',
            'ringkasan_konseling'  => 'nullable|string',
            'solusi_tindak_lanjut' => 'nullable|string',
            'status_kasus'         => 'required|in:Terbuka,Dalam Pendampingan,Selesai',
            'is_rahasia'           => 'nullable|boolean',
            'guru_bk_nama'         => 'nullable|string|max:255',
            'foto_bukti'           => 'nullable|image|max:3072',
        ]);

        $validated['nama_catatan_bk'] = $validated['topik_masalah'];
        $validated['jenis_kasus']     = $validated['jenis_konseling'];
        $validated['catatan']         = $validated['ringkasan_konseling'] ?? '';
        $validated['tindak_lanjut']   = $validated['solusi_tindak_lanjut'] ?? '';
        $validated['is_rahasia']      = $request->boolean('is_rahasia') ? 1 : 0;

        if ($request->hasFile('foto_bukti')) {
            $path = $request->file('foto_bukti')->store('konseling', 'public');
            $validated['foto_panggilan'] = '/storage/' . $path;
        }

        $konseling->update($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Data konseling berhasil diperbarui.',
                'data'    => $konseling,
            ]);
        }

        return back()->with('success', 'Data konseling berhasil diperbarui.');
    }

    /**
     * Delete counseling record
     */
    public function deleteKonseling(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $konseling = Konseling::withoutTenant()->findOrFail($id);
        $konseling->delete();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Catatan konseling berhasil dihapus.',
            ]);
        }

        return back()->with('success', 'Catatan konseling berhasil dihapus.');
    }

    /**
     * Quick status update
     */
    public function updateStatusKonseling(Request $request, string $id): JsonResponse
    {
        $konseling = Konseling::withoutTenant()->findOrFail($id);
        $status = $request->input('status_kasus');

        if (!in_array($status, ['Terbuka', 'Dalam Pendampingan', 'Selesai'])) {
            return response()->json(['success' => false, 'message' => 'Status tidak valid.'], 400);
        }

        $konseling->update(['status_kasus' => $status]);

        return response()->json([
            'success' => true,
            'message' => "Status kasus berhasil diubah menjadi {$status}.",
            'data'    => $konseling,
        ]);
    }
}
