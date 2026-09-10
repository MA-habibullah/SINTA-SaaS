<?php

namespace Modules\Tracer\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Modules\Tracer\Entities\RiwayatKuliah;
use Modules\Tracer\Entities\RiwayatPekerjaan;
use Modules\Tracer\Entities\Alumni;
use Modules\Siswa\Entities\Siswa;
use Shuchkin\SimpleXLSXGen;

class TracerController extends Controller
{
    /**
     * Display Unified Tracer Study & Alumni Portal
     */
    public function index(Request $request): InertiaResponse|JsonResponse
    {
        $user = auth()->user();
        $isSuperAdmin = $user && ($user->isSuperAdmin() || in_array($user->role?->nama_role ?? '', ['super_admin', 'superadmin', 'admin']));
        $filterTenantId = $request->input('tenant_id', '');
        $effectiveTenant = $isSuperAdmin && !empty($filterTenantId) ? $filterTenantId : $user?->tenant_id;

        $search = trim((string) $request->input('search', ''));
        $tab = $request->input('tab', 'kuliah'); // 'tracking', 'kuliah', 'pekerjaan'
        $statusKuliah = $request->input('status_kuliah');
        $statusKerja = $request->input('status_kerja');
        $tahun = $request->input('tahun');
        $perPage = (int) $request->input('per_page', 15);
        if (!in_array($perPage, [10, 15, 25, 50, 100])) {
            $perPage = 15;
        }

        // 1. Data Riwayat Kuliah Query
        $kuliahQuery = ($isSuperAdmin ? RiwayatKuliah::withoutTenant() : RiwayatKuliah::query())
            ->with(['siswa:id,nama_lengkap,nisn,nis,jurusan,kelas_saat_ini'])
            ->when($isSuperAdmin && !empty($filterTenantId), fn($q) => $q->where('tenant_id', $filterTenantId))
            ->orderBy('tahun_masuk', 'desc')
            ->orderBy('created_at', 'desc');

        if ($isSuperAdmin) {
            $kuliahQuery->with('tenant:id,nama_sekolah');
        }

        if ($search) {
            $kuliahQuery->where(function ($q) use ($search) {
                $q->where('nama_alumni', 'ILIKE', "%{$search}%")
                  ->orWhere('nisn', 'ILIKE', "%{$search}%")
                  ->orWhere('nama_kampus', 'ILIKE', "%{$search}%")
                  ->orWhere('nama_prodi', 'ILIKE', "%{$search}%")
                  ->orWhereHas('siswa', function ($sq) use ($search) {
                      $sq->where('nama_lengkap', 'ILIKE', "%{$search}%")
                         ->orWhere('nisn', 'ILIKE', "%{$search}%");
                  });
            });
        }
        if ($statusKuliah) {
            $kuliahQuery->where('status_kuliah', $statusKuliah);
        }
        if ($tahun) {
            $kuliahQuery->where('tahun_masuk', $tahun);
        }
        $riwayatKuliah = $kuliahQuery->paginate($perPage)->appends($request->query());

        // 2. Data Riwayat Pekerjaan Query
        $pekerjaanQuery = ($isSuperAdmin ? RiwayatPekerjaan::withoutTenant() : RiwayatPekerjaan::query())
            ->with(['siswa:id,nama_lengkap,nisn,nis,jurusan,kelas_saat_ini'])
            ->when($isSuperAdmin && !empty($filterTenantId), fn($q) => $q->where('tenant_id', $filterTenantId))
            ->orderBy('tahun_mulai', 'desc')
            ->orderBy('created_at', 'desc');

        if ($isSuperAdmin) {
            $pekerjaanQuery->with('tenant:id,nama_sekolah');
        }

        if ($search) {
            $pekerjaanQuery->where(function ($q) use ($search) {
                $q->where('nama_alumni', 'ILIKE', "%{$search}%")
                  ->orWhere('nisn', 'ILIKE', "%{$search}%")
                  ->orWhere('nama_perusahaan', 'ILIKE', "%{$search}%")
                  ->orWhere('posisi_jabatan', 'ILIKE', "%{$search}%")
                  ->orWhereHas('siswa', function ($sq) use ($search) {
                      $sq->where('nama_lengkap', 'ILIKE', "%{$search}%")
                         ->orWhere('nisn', 'ILIKE', "%{$search}%");
                  });
            });
        }
        if ($statusKerja) {
            $pekerjaanQuery->where('status_kerja', $statusKerja);
        }
        if ($tahun) {
            $pekerjaanQuery->where('tahun_mulai', $tahun);
        }
        $riwayatPekerjaan = $pekerjaanQuery->paginate($perPage)->appends($request->query());

        // 3. Data Tracking Alumni (Direktori Alumni Terdaftar & Luar Sistem)
        $alumniQuery = ($isSuperAdmin ? Siswa::withoutTenant() : Siswa::query())
            ->when($isSuperAdmin && !empty($filterTenantId), fn($q) => $q->where('tenant_id', $filterTenantId))
            ->where(function ($q) {
                $q->where('is_active', false)
                  ->orWhere('kelas_saat_ini', 'ILIKE', '%alumni%')
                  ->orWhere('kelas_saat_ini', 'ILIKE', '%lulus%')
                  ->orWhere('kelas_saat_ini', 'ILIKE', '%XII%');
            })
            ->orderBy('nama_lengkap', 'asc');

        if ($isSuperAdmin) {
            $alumniQuery->with('tenant:id,nama_sekolah');
        }

        if ($search) {
            $alumniQuery->where(function ($q) use ($search) {
                $q->where('nama_lengkap', 'ILIKE', "%{$search}%")
                  ->orWhere('nisn', 'ILIKE', "%{$search}%")
                  ->orWhere('nis', 'ILIKE', "%{$search}%")
                  ->orWhere('jurusan', 'ILIKE', "%{$search}%");
            });
        }
        $alumniTracking = $alumniQuery->paginate($perPage)->appends($request->query());

        // 4. Rekapitulasi Metrik Summary
        $baseKuliahCount = $isSuperAdmin ? RiwayatKuliah::withoutTenant() : RiwayatKuliah::query();
        $basePekerjaanCount = $isSuperAdmin ? RiwayatPekerjaan::withoutTenant() : RiwayatPekerjaan::query();
        $baseSiswaCount = $isSuperAdmin ? Siswa::withoutTenant() : Siswa::query();

        if ($isSuperAdmin && !empty($filterTenantId)) {
            $baseKuliahCount->where('tenant_id', $filterTenantId);
            $basePekerjaanCount->where('tenant_id', $filterTenantId);
            $baseSiswaCount->where('tenant_id', $filterTenantId);
        }

        $totalKuliah = (clone $baseKuliahCount)->count();
        $totalKerjaTetap = (clone $basePekerjaanCount)->whereIn('status_kerja', ['Tetap', 'Kontrak', 'Magang'])->count();
        $totalWirausaha = (clone $basePekerjaanCount)->where('status_kerja', 'Wirausaha')->count();
        $totalSiswaLulus = (clone $baseSiswaCount)->where(function ($q) {
            $q->where('is_active', false)->orWhere('kelas_saat_ini', 'ILIKE', '%alumni%');
        })->count();
        $totalResponden = max($totalSiswaLulus, $totalKuliah + $totalKerjaTetap + $totalWirausaha);
        $totalBelumTerdata = max(0, $totalResponden - ($totalKuliah + $totalKerjaTetap + $totalWirausaha));

        // 5. Master Data Pendukung
        $masterJalur = DB::table('bk.master_jalur_masuk')
            ->where('is_active', true)
            ->select('id', 'nama_jalur', 'kategori')
            ->orderBy('nama_jalur', 'asc')
            ->get();

        if ($masterJalur->isEmpty()) {
            $masterJalur = DB::table('pdss.master_jalur_masuk')
                ->select('id', 'nama_jalur', 'kategori')
                ->orderBy('nama_jalur', 'asc')
                ->get();
        }

        // Ambil daftar kampus untuk dropdown
        $masterKampus = DB::table('pdss.master_kampus')
            ->select('id', 'nama_kampus', 'jenis_kampus', 'kota')
            ->orderBy('nama_kampus', 'asc')
            ->limit(300)
            ->get();

        // Daftar tahun untuk filter
        $listTahun = range((int) date('Y') + 1, 2018);

        // Daftar tenant sekolah (Khusus Super Admin)
        $tenants = [];
        if ($isSuperAdmin) {
            $tenants = DB::table('core.tenants')
                ->where('status', 'active')
                ->orderBy('nama_sekolah', 'asc')
                ->get(['id', 'nama_sekolah', 'npsn']);
        }

        $payload = [
            'riwayatKuliah'     => $riwayatKuliah,
            'riwayatPekerjaan'  => $riwayatPekerjaan,
            'alumniTracking'    => $alumniTracking,
            'masterJalur'       => $masterJalur,
            'masterKampus'      => $masterKampus,
            'listTahun'         => $listTahun,
            'isSuperAdmin'      => $isSuperAdmin,
            'tenants'           => $tenants,
            'selectedTenant'    => $filterTenantId,
            'metrics'           => [
                'totalResponden'    => $totalResponden,
                'totalKuliah'       => $totalKuliah,
                'totalBekerja'      => $totalKerjaTetap,
                'totalWirausaha'    => $totalWirausaha,
                'totalBelumTerdata' => $totalBelumTerdata,
            ],
            'filters'           => [
                'search'        => $search,
                'tab'           => $tab,
                'status_kuliah' => $statusKuliah,
                'status_kerja'  => $statusKerja,
                'tahun'         => $tahun,
                'per_page'      => $perPage,
                'tenant_id'     => $filterTenantId,
            ],
        ];

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data'    => $payload,
            ]);
        }

        return Inertia::render('Tracer/Index', $payload);
    }

    /**
     * Live Search Siswa Alumni (Autocomplete)
     */
    public function searchSiswaAlumni(Request $request): JsonResponse
    {
        $user = auth()->user();
        $isSuperAdmin = $user && ($user->isSuperAdmin() || in_array($user->role?->nama_role ?? '', ['super_admin', 'superadmin', 'admin']));
        $tenantId = $request->input('tenant_id', '');
        $query = trim((string) $request->input('q', ''));

        if (empty($query)) {
            return response()->json(['success' => true, 'data' => []]);
        }

        $studentQuery = ($isSuperAdmin ? Siswa::withoutTenant() : Siswa::query())
            ->when($isSuperAdmin && !empty($tenantId), fn($q) => $q->where('tenant_id', $tenantId))
            ->where(function ($q) use ($query) {
                $q->where('nama_lengkap', 'ILIKE', "%{$query}%")
                  ->orWhere('nisn', 'ILIKE', "%{$query}%")
                  ->orWhere('nis', 'ILIKE', "%{$query}%");
            })
            ->select('id', 'tenant_id', 'nama_lengkap', 'nisn', 'nis', 'jurusan', 'kelas_saat_ini', 'jenis_kelamin')
            ->orderBy('nama_lengkap', 'asc')
            ->limit(20);

        if ($isSuperAdmin) {
            $studentQuery->with('tenant:id,nama_sekolah');
        }

        $results = $studentQuery->get();

        return response()->json([
            'success' => true,
            'data'    => $results,
        ]);
    }

    /**
     * Get Program Studi by Kampus ID
     */
    public function getProdiByKampus($kampusId): JsonResponse
    {
        $prodis = DB::table('pdss.master_kampus_prodi')
            ->where('kampus_id', $kampusId)
            ->where('is_active', true)
            ->select('id', 'program_studi', 'nama_prodi', 'jenjang', 'fakultas')
            ->orderBy('program_studi', 'asc')
            ->get()
            ->map(function ($p) {
                return [
                    'id'            => $p->id,
                    'program_studi' => $p->program_studi ?: $p->nama_prodi,
                    'jenjang'       => $p->jenjang ?: 'S1',
                    'fakultas'      => $p->fakultas ?: '-',
                ];
            });

        return response()->json([
            'success' => true,
            'data'    => $prodis,
        ]);
    }

    /**
     * Store Riwayat Kuliah
     */
    public function storeKuliah(Request $request): RedirectResponse|JsonResponse
    {
        $user = auth()->user();
        $isSuperAdmin = $user && ($user->isSuperAdmin() || in_array($user->role?->nama_role ?? '', ['super_admin', 'superadmin', 'admin']));

        $isManual = filter_var($request->input('is_manual'), FILTER_VALIDATE_BOOLEAN);
        $isKampusSwasta = filter_var($request->input('is_kampus_swasta'), FILTER_VALIDATE_BOOLEAN);

        $rules = [
            'is_manual'        => 'boolean',
            'is_kampus_swasta' => 'boolean',
            'tahun_masuk'      => 'required|integer|min:1990|max:' . (date('Y') + 1),
            'tahun_lulus'      => 'nullable|integer|min:1990|max:' . (date('Y') + 10),
            'status_kuliah'    => 'required|string|in:Aktif,Lulus,Drop',
            'jenjang'          => 'nullable|string|max:10',
            'fakultas'         => 'nullable|string|max:150',
            'jalur_masuk_id'   => 'nullable|string|max:100',
            'tenant_id'        => 'nullable|uuid',
        ];

        if ($isManual) {
            $rules['nama_alumni'] = 'required|string|max:255';
            $rules['nisn']        = 'nullable|string|max:50';
        } else {
            $rules['siswa_id']    = 'required|uuid|exists:siswa.siswa,id';
        }

        if ($isKampusSwasta) {
            $rules['nama_kampus'] = 'required|string|max:255';
            $rules['nama_prodi']  = 'required|string|max:255';
        } else {
            $rules['kampus_id']   = 'required|uuid|exists:pdss.master_kampus,id';
            $rules['prodi_id']    = 'required|uuid|exists:pdss.master_kampus_prodi,id';
        }

        $validated = $request->validate($rules);

        // Resolve nama siswa jika dari sistem
        if (!$isManual && !empty($validated['siswa_id'])) {
            $siswa = Siswa::withoutTenant()->find($validated['siswa_id']);
            if ($siswa) {
                $validated['nama_alumni'] = $siswa->nama_lengkap;
                $validated['nisn'] = $siswa->nisn;
                if (empty($validated['tenant_id'])) {
                    $validated['tenant_id'] = $siswa->tenant_id;
                }
            }
        }

        if (empty($validated['tenant_id'])) {
            $validated['tenant_id'] = session('tenant_id') ?? $user?->tenant_id;
        }

        // Resolve nama kampus & prodi jika dari master
        if (!$isKampusSwasta && !empty($validated['kampus_id'])) {
            $kampus = DB::table('pdss.master_kampus')->where('id', $validated['kampus_id'])->first();
            $prodi = DB::table('pdss.master_kampus_prodi')->where('id', $validated['prodi_id'])->first();
            if ($kampus) {
                $validated['nama_kampus'] = $kampus->nama_kampus;
            }
            if ($prodi) {
                $validated['nama_prodi'] = $prodi->program_studi ?: $prodi->nama_prodi;
                if (empty($validated['jenjang']) && !empty($prodi->jenjang)) {
                    $validated['jenjang'] = $prodi->jenjang;
                }
                if (empty($validated['fakultas']) && !empty($prodi->fakultas)) {
                    $validated['fakultas'] = $prodi->fakultas;
                }
            }
        }

        // Resolve nama jalur masuk
        if (!empty($validated['jalur_masuk_id'])) {
            $jalur = DB::table('bk.master_jalur_masuk')->where('id', $validated['jalur_masuk_id'])->first();
            if (!$jalur) {
                $jalur = DB::table('pdss.master_jalur_masuk')->where('id', $validated['jalur_masuk_id'])->first();
            }
            if ($jalur) {
                $validated['jalur_masuk'] = $jalur->nama_jalur ?? $jalur->nama_master_jalur_masuk ?? null;
            }
        }

        $item = RiwayatKuliah::create($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Riwayat kuliah alumni berhasil disimpan.',
                'data'    => $item,
            ], 201);
        }

        return back()->with('success', 'Riwayat kuliah alumni berhasil disimpan.');
    }

    /**
     * Update Riwayat Kuliah
     */
    public function updateKuliah(Request $request, $id): RedirectResponse|JsonResponse
    {
        $item = RiwayatKuliah::withoutTenant()->findOrFail($id);

        $isManual = filter_var($request->input('is_manual', $item->is_manual), FILTER_VALIDATE_BOOLEAN);
        $isKampusSwasta = filter_var($request->input('is_kampus_swasta', $item->is_kampus_swasta), FILTER_VALIDATE_BOOLEAN);

        $rules = [
            'is_manual'        => 'boolean',
            'is_kampus_swasta' => 'boolean',
            'tahun_masuk'      => 'required|integer|min:1990|max:' . (date('Y') + 1),
            'tahun_lulus'      => 'nullable|integer|min:1990|max:' . (date('Y') + 10),
            'status_kuliah'    => 'required|string|in:Aktif,Lulus,Drop',
            'jenjang'          => 'nullable|string|max:10',
            'fakultas'         => 'nullable|string|max:150',
            'jalur_masuk_id'   => 'nullable|string|max:100',
        ];

        if ($isManual) {
            $rules['nama_alumni'] = 'required|string|max:255';
            $rules['nisn']        = 'nullable|string|max:50';
        }

        if ($isKampusSwasta) {
            $rules['nama_kampus'] = 'required|string|max:255';
            $rules['nama_prodi']  = 'required|string|max:255';
        } else {
            $rules['kampus_id']   = 'required|uuid|exists:pdss.master_kampus,id';
            $rules['prodi_id']    = 'required|uuid|exists:pdss.master_kampus_prodi,id';
        }

        $validated = $request->validate($rules);

        // Resolve nama kampus & prodi jika dari master
        if (!$isKampusSwasta && !empty($validated['kampus_id'])) {
            $kampus = DB::table('pdss.master_kampus')->where('id', $validated['kampus_id'])->first();
            $prodi = DB::table('pdss.master_kampus_prodi')->where('id', $validated['prodi_id'])->first();
            if ($kampus) {
                $validated['nama_kampus'] = $kampus->nama_kampus;
            }
            if ($prodi) {
                $validated['nama_prodi'] = $prodi->program_studi ?: $prodi->nama_prodi;
                if (empty($validated['jenjang']) && !empty($prodi->jenjang)) {
                    $validated['jenjang'] = $prodi->jenjang;
                }
                if (empty($validated['fakultas']) && !empty($prodi->fakultas)) {
                    $validated['fakultas'] = $prodi->fakultas;
                }
            }
        }

        // Resolve nama jalur masuk
        if (!empty($validated['jalur_masuk_id'])) {
            $jalur = DB::table('bk.master_jalur_masuk')->where('id', $validated['jalur_masuk_id'])->first();
            if (!$jalur) {
                $jalur = DB::table('pdss.master_jalur_masuk')->where('id', $validated['jalur_masuk_id'])->first();
            }
            if ($jalur) {
                $validated['jalur_masuk'] = $jalur->nama_jalur ?? $jalur->nama_master_jalur_masuk ?? null;
            }
        }

        $item->update($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Riwayat kuliah alumni berhasil diperbarui.',
                'data'    => $item,
            ]);
        }

        return back()->with('success', 'Riwayat kuliah alumni berhasil diperbarui.');
    }

    /**
     * Delete Riwayat Kuliah
     */
    public function destroyKuliah(Request $request, $id): RedirectResponse|JsonResponse
    {
        $item = RiwayatKuliah::withoutTenant()->findOrFail($id);
        $item->delete();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Riwayat kuliah alumni berhasil dihapus.',
            ]);
        }

        return back()->with('success', 'Riwayat kuliah alumni berhasil dihapus.');
    }

    /**
     * Store Riwayat Pekerjaan
     */
    public function storePekerjaan(Request $request): RedirectResponse|JsonResponse
    {
        $user = auth()->user();
        $isSuperAdmin = $user && ($user->isSuperAdmin() || in_array($user->role?->nama_role ?? '', ['super_admin', 'superadmin', 'admin']));

        $isManual = filter_var($request->input('is_manual'), FILTER_VALIDATE_BOOLEAN);

        $rules = [
            'is_manual'          => 'boolean',
            'nama_perusahaan'    => 'required|string|max:255',
            'posisi_jabatan'     => 'required|string|max:255',
            'jenis_instansi'     => 'nullable|string|max:100',
            'pendapatan_bulanan' => 'nullable|string|max:100',
            'status_kerja'       => 'required|string|in:Tetap,Kontrak,Magang,Wirausaha',
            'tahun_mulai'        => 'required|integer|min:1990|max:' . (date('Y') + 1),
            'tahun_selesai'      => 'nullable|integer|min:1990|max:' . (date('Y') + 10),
            'tenant_id'          => 'nullable|uuid',
        ];

        if ($isManual) {
            $rules['nama_alumni'] = 'required|string|max:255';
            $rules['nisn']        = 'nullable|string|max:50';
        } else {
            $rules['siswa_id']    = 'required|uuid|exists:siswa.siswa,id';
        }

        $validated = $request->validate($rules);
        $validated['posisi'] = $validated['posisi_jabatan'];

        // Resolve nama siswa jika dari sistem
        if (!$isManual && !empty($validated['siswa_id'])) {
            $siswa = Siswa::withoutTenant()->find($validated['siswa_id']);
            if ($siswa) {
                $validated['nama_alumni'] = $siswa->nama_lengkap;
                $validated['nisn'] = $siswa->nisn;
                if (empty($validated['tenant_id'])) {
                    $validated['tenant_id'] = $siswa->tenant_id;
                }
            }
        }

        if (empty($validated['tenant_id'])) {
            $validated['tenant_id'] = session('tenant_id') ?? $user?->tenant_id;
        }

        $item = RiwayatPekerjaan::create($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Riwayat pekerjaan alumni berhasil disimpan.',
                'data'    => $item,
            ], 201);
        }

        return back()->with('success', 'Riwayat pekerjaan alumni berhasil disimpan.');
    }

    /**
     * Update Riwayat Pekerjaan
     */
    public function updatePekerjaan(Request $request, $id): RedirectResponse|JsonResponse
    {
        $item = RiwayatPekerjaan::withoutTenant()->findOrFail($id);

        $isManual = filter_var($request->input('is_manual', $item->is_manual), FILTER_VALIDATE_BOOLEAN);

        $rules = [
            'is_manual'          => 'boolean',
            'nama_perusahaan'    => 'required|string|max:255',
            'posisi_jabatan'     => 'required|string|max:255',
            'jenis_instansi'     => 'nullable|string|max:100',
            'pendapatan_bulanan' => 'nullable|string|max:100',
            'status_kerja'       => 'required|string|in:Tetap,Kontrak,Magang,Wirausaha',
            'tahun_mulai'        => 'required|integer|min:1990|max:' . (date('Y') + 1),
            'tahun_selesai'      => 'nullable|integer|min:1990|max:' . (date('Y') + 10),
        ];

        if ($isManual) {
            $rules['nama_alumni'] = 'required|string|max:255';
            $rules['nisn']        = 'nullable|string|max:50';
        }

        $validated = $request->validate($rules);
        $validated['posisi'] = $validated['posisi_jabatan'];

        $item->update($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Riwayat pekerjaan alumni berhasil diperbarui.',
                'data'    => $item,
            ]);
        }

        return back()->with('success', 'Riwayat pekerjaan alumni berhasil diperbarui.');
    }

    /**
     * Delete Riwayat Pekerjaan
     */
    public function destroyPekerjaan(Request $request, $id): RedirectResponse|JsonResponse
    {
        $item = RiwayatPekerjaan::withoutTenant()->findOrFail($id);
        $item->delete();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Riwayat pekerjaan alumni berhasil dihapus.',
            ]);
        }

        return back()->with('success', 'Riwayat pekerjaan alumni berhasil dihapus.');
    }

    /**
     * Export Excel Rekapan Tracer Study
     */
    public function exportExcel(Request $request)
    {
        $user = auth()->user();
        $isSuperAdmin = $user && ($user->isSuperAdmin() || in_array($user->role?->nama_role ?? '', ['super_admin', 'superadmin', 'admin']));
        $filterTenantId = $request->input('tenant_id', '');

        $tab = $request->input('tab', 'kuliah');
        $fileName = 'rekap_tracer_' . $tab . '_' . date('Ymd_His') . '.xlsx';

        if ($tab === 'kuliah') {
            $headers = ['No', 'Nama Alumni', 'NISN'];
            if ($isSuperAdmin) $headers[] = 'Nama Sekolah';
            $headers = array_merge($headers, ['Asal / Sistem', 'Perguruan Tinggi', 'Program Studi', 'Jenjang', 'Fakultas', 'Jalur Masuk', 'Tahun Masuk', 'Tahun Lulus', 'Status Kuliah']);
            $rows = [$headers];

            $kuliahQuery = ($isSuperAdmin ? RiwayatKuliah::withoutTenant() : RiwayatKuliah::query())
                ->with(['siswa', 'tenant'])
                ->when($isSuperAdmin && !empty($filterTenantId), fn($q) => $q->where('tenant_id', $filterTenantId))
                ->orderBy('tahun_masuk', 'desc');

            $items = $kuliahQuery->get();
            $no = 1;
            foreach ($items as $item) {
                $nama = $item->is_manual ? $item->nama_alumni : ($item->siswa?->nama_lengkap ?: $item->nama_alumni);
                $nisn = $item->is_manual ? $item->nisn : ($item->siswa?->nisn ?: $item->nisn);
                
                $row = [$no++, $nama ?: '-', $nisn ?: '-'];
                if ($isSuperAdmin) $row[] = $item->tenant?->nama_sekolah ?: '-';
                $row = array_merge($row, [
                    $item->is_manual ? 'Luar Sistem' : 'Siswa Sistem',
                    $item->nama_kampus ?: '-',
                    $item->nama_prodi ?: '-',
                    $item->jenjang ?: 'S1',
                    $item->fakultas ?: '-',
                    $item->jalur_masuk ?: '-',
                    $item->tahun_masuk ?: '-',
                    $item->tahun_lulus ?: 'Aktif',
                    $item->status_kuliah ?: 'Aktif',
                ]);
                $rows[] = $row;
            }
        } elseif ($tab === 'pekerjaan') {
            $headers = ['No', 'Nama Alumni', 'NISN'];
            if ($isSuperAdmin) $headers[] = 'Nama Sekolah';
            $headers = array_merge($headers, ['Asal / Sistem', 'Perusahaan / Instansi', 'Posisi / Jabatan', 'Jenis Instansi', 'Rentang Pendapatan', 'Tahun Mulai', 'Tahun Selesai', 'Status Kerja']);
            $rows = [$headers];

            $pekerjaanQuery = ($isSuperAdmin ? RiwayatPekerjaan::withoutTenant() : RiwayatPekerjaan::query())
                ->with(['siswa', 'tenant'])
                ->when($isSuperAdmin && !empty($filterTenantId), fn($q) => $q->where('tenant_id', $filterTenantId))
                ->orderBy('tahun_mulai', 'desc');

            $items = $pekerjaanQuery->get();
            $no = 1;
            foreach ($items as $item) {
                $nama = $item->is_manual ? $item->nama_alumni : ($item->siswa?->nama_lengkap ?: $item->nama_alumni);
                $nisn = $item->is_manual ? $item->nisn : ($item->siswa?->nisn ?: $item->nisn);

                $row = [$no++, $nama ?: '-', $nisn ?: '-'];
                if ($isSuperAdmin) $row[] = $item->tenant?->nama_sekolah ?: '-';
                $row = array_merge($row, [
                    $item->is_manual ? 'Luar Sistem' : 'Siswa Sistem',
                    $item->nama_perusahaan ?: '-',
                    $item->posisi_jabatan ?: '-',
                    $item->jenis_instansi ?: '-',
                    $item->pendapatan_bulanan ?: '-',
                    $item->tahun_mulai ?: '-',
                    $item->tahun_selesai ?: 'Sekarang',
                    $item->status_kerja ?: '-',
                ]);
                $rows[] = $row;
            }
        } else {
            $headers = ['No', 'Nama Alumni', 'NISN', 'NIS'];
            if ($isSuperAdmin) $headers[] = 'Nama Sekolah';
            $headers = array_merge($headers, ['Jurusan Asal', 'Kelas Terakhir', 'Status Kesiswaan']);
            $rows = [$headers];

            $siswaQuery = ($isSuperAdmin ? Siswa::withoutTenant() : Siswa::query())
                ->with('tenant')
                ->when($isSuperAdmin && !empty($filterTenantId), fn($q) => $q->where('tenant_id', $filterTenantId))
                ->where(function ($q) {
                    $q->where('is_active', false)
                      ->orWhere('kelas_saat_ini', 'ILIKE', '%alumni%')
                      ->orWhere('kelas_saat_ini', 'ILIKE', '%lulus%')
                      ->orWhere('kelas_saat_ini', 'ILIKE', '%XII%');
                })->orderBy('nama_lengkap', 'asc');

            $items = $siswaQuery->get();
            $no = 1;
            foreach ($items as $item) {
                $row = [$no++, $item->nama_lengkap ?: '-', $item->nisn ?: '-', $item->nis ?: '-'];
                if ($isSuperAdmin) $row[] = $item->tenant?->nama_sekolah ?: '-';
                $row = array_merge($row, [
                    $item->jurusan ?: '-',
                    $item->kelas_saat_ini ?: '-',
                    $item->is_active ? 'Aktif' : 'Lulus / Alumni',
                ]);
                $rows[] = $row;
            }
        }

        $xlsx = SimpleXLSXGen::fromArray($rows);

        return response((string) $xlsx, 200, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
            'Cache-Control'       => 'max-age=0',
        ]);
    }
}
