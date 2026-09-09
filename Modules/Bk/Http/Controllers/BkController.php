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
     * Get list of Academic Years
     */
    protected function getTahunAjaranList()
    {
        $list = \Modules\Akademik\Entities\TahunAjaran::withoutTenant()
            ->orderBy('nama_tahun_ajaran', 'desc')
            ->get(['id', 'nama_tahun_ajaran', 'is_active']);

        if ($list->isEmpty()) {
            $list = collect([
                (object)['id' => 'ta_2026', 'nama_tahun_ajaran' => '2026/2027', 'is_active' => true],
                (object)['id' => 'ta_2025', 'nama_tahun_ajaran' => '2025/2026', 'is_active' => false],
                (object)['id' => 'ta_2024', 'nama_tahun_ajaran' => '2024/2025', 'is_active' => false],
                (object)['id' => 'ta_2023', 'nama_tahun_ajaran' => '2023/2024', 'is_active' => false],
                (object)['id' => 'ta_2022', 'nama_tahun_ajaran' => '2022/2023', 'is_active' => false],
            ]);
        }

        return $list;
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

        $tahunAjaranList = $this->getTahunAjaranList();
        $selectedTahunAjaran = $request->input('tahun_ajaran', '');

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

        // Academic Year Filter (e.g. '2025/2026' -> '2025-07-01' to '2026-06-30')
        if ($request->filled('tahun_ajaran') && $selectedTahunAjaran !== 'all') {
            if (preg_match('/^(\d{4})\/(\d{4})$/', $selectedTahunAjaran, $matches)) {
                $taStartDate = "{$matches[1]}-07-01";
                $taEndDate = "{$matches[2]}-06-30";
                $query->whereBetween('tanggal_kejadian', [$taStartDate, $taEndDate]);
                $pointsQuery->whereBetween('tanggal_kejadian', [$taStartDate, $taEndDate]);
                $trendQuery->whereBetween('tanggal_kejadian', [$taStartDate, $taEndDate]);
            }
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
                'success'             => true,
                'pelanggaranList'     => $pelanggaranList,
                'masterList'          => $masterList,
                'kpi'                 => $kpi,
                'topStudents'         => $topStudents,
                'monthlyTrend'        => $monthlyTrend,
                'tenantInfo'          => $tenantInfo,
                'isSuperAdmin'        => $isSuperAdmin,
                'tenants'             => $tenants,
                'tahunAjaranList'     => $tahunAjaranList,
                'selectedTahunAjaran' => $selectedTahunAjaran,
            ]);
        }

        return Inertia::render('Bk/Kedisiplinan/Index', [
            'pelanggaranList'     => $pelanggaranList,
            'masterList'          => $masterList,
            'kpi'                 => $kpi,
            'topStudents'         => $topStudents,
            'monthlyTrend'        => $monthlyTrend,
            'tenantInfo'          => $tenantInfo,
            'isSuperAdmin'        => $isSuperAdmin,
            'tenants'             => $tenants,
            'tahunAjaranList'     => $tahunAjaranList,
            'selectedTahunAjaran' => $selectedTahunAjaran,
            'filters'             => $request->only(['search', 'kategori', 'status_pembinaan', 'start_date', 'end_date', 'per_page', 'tenant_id', 'tahun_ajaran']),
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
     * Pastikan response redirect selalu kembali ke halaman kanonikal yang tepat
     * dengan fallback eksplisit agar tidak pernah terlempar ke /dashboard atau /login
     */
    private function redirectTarget(Request $request, string $status, string $message, string $fallbackPath = '/bk/kedisiplinan'): RedirectResponse|JsonResponse
    {
        if ($request->wantsJson()) {
            return response()->json([
                'success' => $status === 'success',
                'message' => $message,
            ], $status === 'success' ? 200 : 400);
        }

        $referer = $request->header('referer');
        if ($referer) {
            $parsed = parse_url($referer);
            $refererPath = $parsed['path'] ?? '';
            // Hanya izinkan redirect ke referer jika valid dan berada di lingkup /bk
            if (str_starts_with($refererPath, '/bk')) {
                return redirect()->to($referer)->with($status, $message);
            }
        }

        return redirect()->to($fallbackPath)->with($status, $message);
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
            'status_pembinaan'     => 'required|in:Belum Dibina,Dalam Pembinaan,Selesai',
            'keterangan'           => 'nullable|string',
            'snapshot_nama_siswa'  => 'nullable|string|max:255',
            'snapshot_nisn'        => 'nullable|string|max:50',
            'snapshot_nis'         => 'nullable|string|max:50',
            'snapshot_nama_kelas'  => 'nullable|string|max:100',
            'foto_bukti'           => 'nullable|image|mimes:jpeg,png,jpg,webp|max:3072',
        ]);

        $user = $request->user() ?: Auth::user();
        $targetTenantId = $user->tenant_id ?? '00000000-0000-0000-0000-000000000000';

        // Auto snapshot student info
        $siswa = Siswa::withoutTenant()->find($validated['siswa_id']);
        if ($siswa) {
            $targetTenantId = $siswa->tenant_id ?? $targetTenantId;
            $validated['snapshot_nama_siswa'] = !empty($validated['snapshot_nama_siswa']) ? $validated['snapshot_nama_siswa'] : $siswa->nama_lengkap;
            $validated['snapshot_nisn'] = !empty($validated['snapshot_nisn']) ? $validated['snapshot_nisn'] : $siswa->nisn;
            $validated['snapshot_nis'] = !empty($validated['snapshot_nis']) ? $validated['snapshot_nis'] : $siswa->nis;
            $validated['snapshot_nama_kelas'] = !empty($validated['snapshot_nama_kelas']) ? $validated['snapshot_nama_kelas'] : ($siswa->kelas?->nama_kelas ?? 'Siswa Terdaftar');
        }

        if (empty($validated['petugas_pencatat'])) {
            $validated['petugas_pencatat'] = $user->nama ?? $user->name ?? 'Petugas BK';
        }

        $validated['tenant_id'] = $targetTenantId;
        $validated['is_active'] = true;
        $validated['nama_pelanggaran_siswa'] = $validated['nama_pelanggaran'];

        if ($request->hasFile('foto_bukti')) {
            $path = $request->file('foto_bukti')->store('bk/pelanggaran', 'public');
            $validated['foto_bukti'] = $path;
        }

        $pelanggaran = Pelanggaran::create($validated);

        return $this->redirectTarget($request, 'success', 'Catatan pelanggaran berhasil disimpan.', '/bk/kedisiplinan');
    }

    /**
     * Update violation record
     */
    public function updatePelanggaran(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $pelanggaran = Pelanggaran::withoutTenant()->findOrFail($id);

        $validated = $request->validate([
            'nama_pelanggaran'     => 'required|string|max:255',
            'kategori'             => 'required|in:Ringan,Sedang,Berat,Khusus',
            'poin_pelanggaran'     => 'required|integer|min:1|max:100',
            'tanggal_kejadian'     => 'required|date',
            'tindakan_hukuman'     => 'nullable|string',
            'petugas_pencatat'     => 'nullable|string|max:255',
            'status_pembinaan'     => 'required|in:Belum Dibina,Dalam Pembinaan,Selesai',
            'keterangan'           => 'nullable|string',
            'foto_bukti'           => 'nullable|image|mimes:jpeg,png,jpg,webp|max:3072',
        ]);

        $validated['nama_pelanggaran_siswa'] = $validated['nama_pelanggaran'];

        if ($request->hasFile('foto_bukti')) {
            if ($pelanggaran->foto_bukti && Storage::disk('public')->exists($pelanggaran->foto_bukti)) {
                Storage::disk('public')->delete($pelanggaran->foto_bukti);
            }
            $path = $request->file('foto_bukti')->store('bk/pelanggaran', 'public');
            $validated['foto_bukti'] = $path;
        }

        $pelanggaran->update($validated);

        return $this->redirectTarget($request, 'success', 'Data pelanggaran berhasil diperbarui.', '/bk/kedisiplinan');
    }

    /**
     * Delete violation record
     */
    public function deletePelanggaran(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $pelanggaran = Pelanggaran::withoutTenant()->findOrFail($id);
        $pelanggaran->delete();

        return $this->redirectTarget($request, 'success', 'Data pelanggaran berhasil dihapus.', '/bk/kedisiplinan');
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

        return $this->redirectTarget($request, 'success', 'Aturan tata tertib berhasil disimpan.', '/bk/kedisiplinan');
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

        return $this->redirectTarget($request, 'success', 'Aturan tata tertib berhasil diperbarui.', '/bk/kedisiplinan');
    }

    /**
     * Delete Master Pelanggaran Rule
     */
    public function deleteMasterPelanggaran(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $master = MasterPelanggaran::withoutTenant()->findOrFail($id);
        $master->delete();

        return $this->redirectTarget($request, 'success', 'Aturan tata tertib berhasil dihapus.', '/bk/kedisiplinan');
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

        $tahunAjaranList = $this->getTahunAjaranList();
        $selectedTahunAjaran = $request->input('tahun_ajaran', '');

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

        // Academic Year Filter (e.g. '2025/2026' -> '2025-07-01' to '2026-06-30')
        if ($request->filled('tahun_ajaran') && $selectedTahunAjaran !== 'all') {
            if (preg_match('/^(\d{4})\/(\d{4})$/', $selectedTahunAjaran, $matches)) {
                $taStartDate = "{$matches[1]}-07-01";
                $taEndDate = "{$matches[2]}-06-30";
                $query->whereBetween('tanggal_konseling', [$taStartDate, $taEndDate]);
                $kpiQuery->whereBetween('tanggal_konseling', [$taStartDate, $taEndDate]);
                $breakdownQuery->whereBetween('tanggal_konseling', [$taStartDate, $taEndDate]);
                $trendQuery->whereBetween('tanggal_konseling', [$taStartDate, $taEndDate]);
            }
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
                'success'             => true,
                'konselingList'       => $konselingList,
                'kpi'                 => $kpi,
                'kategoriBreakdown'   => $kategoriBreakdown,
                'monthlyTrend'        => $monthlyTrend,
                'tenantInfo'          => $tenantInfo,
                'isSuperAdmin'        => $isSuperAdmin,
                'tenants'             => $tenants,
                'tahunAjaranList'     => $tahunAjaranList,
                'selectedTahunAjaran' => $selectedTahunAjaran,
            ]);
        }

        return Inertia::render('Bk/Layanan/Index', [
            'konselingList'       => $konselingList,
            'kpi'                 => $kpi,
            'kategoriBreakdown'   => $kategoriBreakdown,
            'monthlyTrend'        => $monthlyTrend,
            'tenantInfo'          => $tenantInfo,
            'isSuperAdmin'        => $isSuperAdmin,
            'tenants'             => $tenants,
            'tahunAjaranList'     => $tahunAjaranList,
            'selectedTahunAjaran' => $selectedTahunAjaran,
            'filters'             => $request->only(['search', 'jenis_konseling', 'status_kasus', 'is_rahasia', 'start_date', 'end_date', 'per_page', 'tenant_id', 'tahun_ajaran']),
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

        return $this->redirectTarget($request, 'success', 'Sesi konseling berhasil disimpan.', '/bk/layanan');
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

        return $this->redirectTarget($request, 'success', 'Data konseling berhasil diperbarui.', '/bk/layanan');
    }

    /**
     * Delete counseling record
     */
    public function deleteKonseling(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $konseling = Konseling::withoutTenant()->findOrFail($id);
        $konseling->delete();

        return $this->redirectTarget($request, 'success', 'Catatan konseling berhasil dihapus.', '/bk/layanan');
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
