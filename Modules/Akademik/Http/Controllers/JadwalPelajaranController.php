<?php

namespace Modules\Akademik\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Modules\Akademik\Entities\Kelas;
use Modules\Akademik\Entities\MataPelajaran;
use Modules\Akademik\Entities\PemetaanMapel;
use Modules\Akademik\Entities\TahunAjaran;
use Modules\Core\Entities\Tenant;
use Modules\Core\Entities\User;
use Modules\Core\Services\SecurityPayloadService;
use Shuchkin\SimpleXLSX;
use Shuchkin\SimpleXLSXGen;

class JadwalPelajaranController extends Controller
{
    /**
     * Resolve active tenant ID (with Superadmin switcher support)
     */
    protected function resolveTenantId(Request $request): ?string
    {
        $user = auth()->user();
        $isSuperAdmin = $user && ($user->isSuperAdmin() || in_array($user->role?->nama_role ?? '', ['super_admin', 'superadmin', 'admin']));

        if ($isSuperAdmin && $request->filled('tenant_id')) {
            return $request->input('tenant_id');
        }

        return $user?->tenant_id ?? session('tenant_id');
    }

    /**
     * Halaman Utama Jadwal Pelajaran (Matrix Grid, Table, Beban Guru, Ruangan, & Conflict Inspector)
     */
    public function index(Request $request): InertiaResponse|JsonResponse
    {
        $user = auth()->user();
        $isSuperAdmin = $user && ($user->isSuperAdmin() || in_array($user->role?->nama_role ?? '', ['super_admin', 'superadmin', 'admin']));
        $tenantId = $this->resolveTenantId($request);

        $isInitialSsr = !$request->header('X-Inertia') && !$request->has('async');

        if ($isInitialSsr) {
            return Inertia::render('Akademik/Jadwal/Index', [
                'isSuperAdmin'      => $isSuperAdmin,
                'selectedTenantId'  => $tenantId,
                'tenantsList'       => null,
                'stats'             => null,
                'activeTahunAjaran' => '2026/2027',
                'activeSemester'    => 'Ganjil',
                'tahunAjaranList'   => null,
                'kelasList'         => null,
                'mapelList'         => null,
                'guruList'          => null,
                'ruangList'         => null,
                'daysList'          => null,
                'standardTimeSlots' => null,
                'jadwalTable'       => null,
                'matrixItems'       => null,
                'allPeriodJadwals'  => null,
                'bebanGuruList'     => null,
                'ruangUtilList'     => null,
                'conflictList'      => null,
                'filters'           => null,
            ]);
        }

        // List Tenancy for Superadmin Switcher
        $tenantsList = $isSuperAdmin ? Tenant::orderBy('nama_sekolah')->get(['id', 'nama_sekolah', 'npsn']) : [];

        // Reference Data Filter
        $tahunAjaranList = TahunAjaran::withoutTenant()
            ->when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))
            ->orderBy('nama_tahun_ajaran', 'desc')
            ->pluck('nama_tahun_ajaran')
            ->toArray();

        if (empty($tahunAjaranList)) {
            $tahunAjaranList = ['2026/2027', '2025/2026', '2024/2025'];
        }

        $activeTahunAjaran = $request->input('tahun_ajaran', $tahunAjaranList[0] ?? '2026/2027');
        $activeSemester = $request->input('semester', 'Ganjil');
        $filterKelasId = $request->input('kelas_id', '');
        $filterGuruId = $request->input('guru_id', '');
        $filterHari = $request->input('hari', '');
        $filterRuangan = $request->input('ruangan', '');
        $search = trim((string)$request->input('search', ''));
        $viewMode = $request->input('view_mode', 'grid'); // 'grid' | 'table' | 'beban_guru' | 'ruang_matrix' | 'conflict'

        // Kelas List
        $kelasList = Kelas::withoutTenant()
            ->when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))
            ->orderBy('nama_kelas', 'asc')
            ->get(['id', 'nama_kelas', 'kode_kelas']);

        // Mapel List
        $mapelList = MataPelajaran::withoutTenant()
            ->when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))
            ->orderBy('nama_mata_pelajaran', 'asc')
            ->get(['id', 'nama_mata_pelajaran', 'kategori']);

        // Guru List (Users with role guru or active users)
        $guruList = User::query()
            ->when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))
            ->where('is_active', true)
            ->whereHas('role', function($q) {
                $q->whereIn('nama_role', ['guru', 'wali_kelas', 'guru_bk', 'waka_kurikulum', 'admin_sekolah', 'super_admin']);
            })
            ->orderBy('nama_lengkap', 'asc')
            ->get(['id', 'nama_lengkap', 'nip', 'email']);

        if ($guruList->isEmpty()) {
            $guruList = User::query()
                ->when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))
                ->orderBy('nama_lengkap', 'asc')
                ->get(['id', 'nama_lengkap', 'nip', 'email']);
        }

        // Ruangan List (distinct from sarpras.ruang and existing schedules)
        $ruangFromSarpras = DB::table('sarpras.ruang')
            ->when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))
            ->pluck('nama_ruang')
            ->toArray();

        $ruangFromJadwal = PemetaanMapel::withoutTenant()
            ->when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))
            ->whereNotNull('ruangan')
            ->where('ruangan', '!=', '')
            ->distinct()
            ->pluck('ruangan')
            ->toArray();

        $ruangList = array_values(array_unique(array_filter(array_merge($ruangFromSarpras, $ruangFromJadwal, [
            'R. 101', 'R. 102', 'R. 103', 'R. 104', 'R. 105', 'Lab Komputer 1', 'Lab Komputer 2', 'Lab IPA', 'Lab Bahasa', 'Aula Utama', 'Lapangan Olahraga'
        ]))));
        sort($ruangList);

        // Base Query for Schedules in Active Period
        $baseQuery = PemetaanMapel::withoutTenant()
            ->when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))
            ->where('is_active', true)
            ->where('tahun_ajaran', $activeTahunAjaran)
            ->where('semester', $activeSemester)
            ->with(['kelas', 'mapel', 'guru']);

        $allPeriodJadwals = (clone $baseQuery)->orderBy('hari', 'asc')->orderBy('jam_mulai', 'asc')->get();

        // Detect all conflicts across the period
        $conflictReport = $this->detectAllConflicts($allPeriodJadwals);
        $conflictsByJadwalId = $conflictReport['conflicts_by_id'];

        // Map conflicts back to allPeriodJadwals
        $allPeriodJadwals->transform(function ($item) use ($conflictsByJadwalId) {
            $item->has_conflict = isset($conflictsByJadwalId[$item->id]);
            $item->conflict_reasons = $conflictsByJadwalId[$item->id] ?? [];
            return $item;
        });

        // Compute Statistics
        $totalJadwal = $allPeriodJadwals->count();
        $totalGuru = $allPeriodJadwals->pluck('guru_id')->filter()->unique()->count();
        $totalRuang = $allPeriodJadwals->pluck('ruangan')->filter()->unique()->count();
        $totalJp = (int)$allPeriodJadwals->sum('jam_pelajaran');
        $totalBentrok = count($conflictsByJadwalId);

        $stats = [
            'total_jadwal'  => $totalJadwal,
            'total_guru'    => $totalGuru,
            'total_ruang'   => $totalRuang,
            'total_jp'      => $totalJp,
            'total_bentrok' => $totalBentrok,
        ];

        // Compute Teacher Workload (Peta Beban Mengajar Guru)
        $bebanGuruList = [];
        $jadwalsByGuru = $allPeriodJadwals->groupBy('guru_id');
        foreach ($guruList as $g) {
            $gJadwals = $jadwalsByGuru->get($g->id, collect());
            $sumJp = (int)$gJadwals->sum('jam_pelajaran');
            $distinctClasses = $gJadwals->pluck('kelas.nama_kelas')->filter()->unique()->values()->all();
            $distinctMapel = $gJadwals->pluck('mapel.nama_mata_pelajaran')->filter()->unique()->values()->all();
            $hasGuruConflict = $gJadwals->contains(fn($j) => $j->has_conflict);

            $bebanGuruList[] = [
                'guru_id'            => $g->id,
                'nama_guru'          => $g->nama_lengkap,
                'nip'                => $g->nip ?? '-',
                'total_jp'           => $sumJp,
                'target_jp'          => 24,
                'status_sertifikasi' => $sumJp >= 24 ? 'Terpenuhi (>=24 JP)' : 'Kurang ' . (24 - $sumJp) . ' JP',
                'is_terpenuhi'       => $sumJp >= 24,
                'total_jadwal'       => $gJadwals->count(),
                'list_kelas'         => $distinctClasses,
                'list_mapel'         => $distinctMapel,
                'has_conflict'       => $hasGuruConflict,
            ];
        }
        usort($bebanGuruList, fn($a, $b) => $b['total_jp'] <=> $a['total_jp']);

        // Compute Room Utilization (Peta Utilisasi Ruangan)
        $ruangUtilList = [];
        $jadwalsByRuang = $allPeriodJadwals->groupBy('ruangan');
        foreach ($ruangList as $rName) {
            $rJadwals = $jadwalsByRuang->get($rName, collect());
            $sumJpRuang = (int)$rJadwals->sum('jam_pelajaran');
            $distinctRombel = $rJadwals->pluck('kelas.nama_kelas')->filter()->unique()->values()->all();
            $hasRuangConflict = $rJadwals->contains(fn($j) => $j->has_conflict);

            $ruangUtilList[] = [
                'nama_ruangan'  => $rName,
                'total_jp'      => $sumJpRuang,
                'total_sesi'    => $rJadwals->count(),
                'list_kelas'    => $distinctRombel,
                'has_conflict'  => $hasRuangConflict,
            ];
        }
        usort($ruangUtilList, fn($a, $b) => $b['total_jp'] <=> $a['total_jp']);

        // Filtered Schedule Table Data (Paginator)
        $filteredQuery = PemetaanMapel::withoutTenant()
            ->when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))
            ->where('is_active', true)
            ->where('tahun_ajaran', $activeTahunAjaran)
            ->where('semester', $activeSemester)
            ->when($filterKelasId, fn($q) => $q->where('kelas_id', $filterKelasId))
            ->when($filterGuruId, fn($q) => $q->where('guru_id', $filterGuruId))
            ->when($filterHari, fn($q) => $q->where('hari', $filterHari))
            ->when($filterRuangan, fn($q) => $q->where('ruangan', $filterRuangan))
            ->when($search, function($q) use ($search) {
                $q->where(function($sub) use ($search) {
                    $sub->where('nama_pemetaan_mapel', 'ILIKE', "%{$search}%")
                        ->orWhere('ruangan', 'ILIKE', "%{$search}%")
                        ->orWhere('hari', 'ILIKE', "%{$search}%")
                        ->orWhereHas('mapel', fn($m) => $m->where('nama_mata_pelajaran', 'ILIKE', "%{$search}%"))
                        ->orWhereHas('guru', fn($g) => $g->where('nama_lengkap', 'ILIKE', "%{$search}%"))
                        ->orWhereHas('kelas', fn($k) => $k->where('nama_kelas', 'ILIKE', "%{$search}%"));
                });
            })
            ->with(['kelas', 'mapel', 'guru'])
            ->orderByRaw("CASE hari 
                WHEN 'Senin' THEN 1 
                WHEN 'Selasa' THEN 2 
                WHEN 'Rabu' THEN 3 
                WHEN 'Kamis' THEN 4 
                WHEN 'Jumat' THEN 5 
                WHEN 'Sabtu' THEN 6 
                ELSE 7 END")
            ->orderBy('jam_mulai', 'asc');

        $perPage = max(10, min(200, (int)$request->input('per_page', 25)));
        $jadwalTable = $filteredQuery->paginate($perPage)->withQueryString();

        // Attach conflict info to table items
        $jadwalTable->getCollection()->transform(function ($item) use ($conflictsByJadwalId) {
            $item->has_conflict = isset($conflictsByJadwalId[$item->id]);
            $item->conflict_reasons = $conflictsByJadwalId[$item->id] ?? [];
            return $item;
        });

        // Matrix Grid Data Preparation (Filtered by selected view focus: per kelas, per guru, or per ruang)
        $matrixItems = $allPeriodJadwals;
        if ($filterKelasId) {
            $matrixItems = $matrixItems->where('kelas_id', $filterKelasId)->values();
        } elseif ($filterGuruId) {
            $matrixItems = $matrixItems->where('guru_id', $filterGuruId)->values();
        } elseif ($filterRuangan) {
            $matrixItems = $matrixItems->where('ruangan', $filterRuangan)->values();
        }

        // Standard Schedule Slots for Timetable Matrix (Jam Ke-1 s/d Jam Ke-10)
        $standardTimeSlots = [];
        for ($i = 1; $i <= 10; $i++) {
            $standardTimeSlots[] = [
                'jam_ke' => (string)$i,
                'label'  => 'Jam Ke-' . $i,
            ];
        }

        // Standard Days
        $daysList = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

        $props = [
            'isSuperAdmin'      => $isSuperAdmin,
            'selectedTenantId'  => $tenantId,
            'tenantsList'       => $tenantsList,
            'stats'             => $stats,
            'activeTahunAjaran' => $activeTahunAjaran,
            'activeSemester'    => $activeSemester,
            'tahunAjaranList'   => $tahunAjaranList,
            'kelasList'         => $kelasList,
            'mapelList'         => $mapelList,
            'guruList'          => $guruList,
            'ruangList'         => $ruangList,
            'daysList'          => $daysList,
            'standardTimeSlots' => $standardTimeSlots,
            'jadwalTable'       => $jadwalTable,
            'matrixItems'       => $matrixItems,
            'allPeriodJadwals'  => $allPeriodJadwals,
            'bebanGuruList'     => $bebanGuruList,
            'ruangUtilList'     => $ruangUtilList,
            'conflictList'      => array_values($conflictReport['conflicts_detail']),
            'filters'           => [
                'tenant_id'    => $tenantId,
                'tahun_ajaran' => $activeTahunAjaran,
                'semester'     => $activeSemester,
                'kelas_id'     => $filterKelasId,
                'guru_id'      => $filterGuruId,
                'hari'         => $filterHari,
                'ruangan'      => $filterRuangan,
                'search'       => $search,
                'view_mode'    => $viewMode,
                'per_page'     => $perPage,
            ],
        ];

        if (($request->has('async') || $request->wantsJson()) && !$request->header('X-Inertia')) {
            return response()->json(SecurityPayloadService::sanitize(['success' => true, 'data' => $props]));
        }

        return Inertia::render('Akademik/Jadwal/Index', $props);
    }

    /**
     * Store Single Jadwal Pelajaran (with Anti-Bentrok Conflict Check)
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $tenantId = $this->resolveTenantId($request);

        $validated = $request->validate([
            'tenant_id'           => 'nullable|uuid',
            'tahun_ajaran'        => 'required|string|max:50',
            'semester'            => 'required|string|max:20',
            'kelas_id'            => 'required|string',
            'mapel_id'            => 'required|string',
            'guru_id'             => 'nullable|uuid',
            'hari'                => 'required|string|max:20',
            'jam_ke'              => 'nullable|string|max:50',
            'jam_mulai'           => 'required|string|max:10',
            'jam_selesai'         => 'required|string|max:10',
            'ruangan'             => 'nullable|string|max:100',
            'kkm'                 => 'nullable|numeric|min:0|max:100',
            'jam_pelajaran'       => 'nullable|integer|min:1|max:20',
            'warna_label'         => 'nullable|string|max:20',
            'catatan'             => 'nullable|string|max:500',
            'force_override'      => 'nullable|boolean',
        ]);

        $forceOverride = $request->boolean('force_override', false);

        // Run Conflict Check
        $conflicts = $this->checkScheduleConflict(
            $tenantId,
            null,
            $validated['tahun_ajaran'],
            $validated['semester'],
            $validated['hari'],
            $validated['jam_mulai'],
            $validated['jam_selesai'],
            $validated['kelas_id'],
            $validated['guru_id'] ?? null,
            $validated['ruangan'] ?? null
        );

        if (!empty($conflicts) && !$forceOverride) {
            $msg = 'Terdeteksi bentrok jadwal: ' . implode(' | ', $conflicts);
            if ($request->wantsJson() && !$request->header('X-Inertia')) {
                return response()->json([
                    'success'   => false,
                    'is_conflict' => true,
                    'message'   => $msg,
                    'conflicts' => $conflicts,
                ], 422);
            }
            return back()->withErrors(['conflict' => $msg])->with('error', $msg);
        }

        // Get Names for Display
        $mapel = MataPelajaran::withoutTenant()->find($validated['mapel_id']);
        $kelas = Kelas::withoutTenant()->find($validated['kelas_id']);
        $namaMapel = $mapel?->nama_mata_pelajaran ?? 'Mata Pelajaran';
        $namaKelas = $kelas?->nama_kelas ?? 'Kelas';
        $ruangan = !empty($validated['ruangan']) ? $validated['ruangan'] : $namaKelas;

        $jadwal = PemetaanMapel::create([
            'id'                  => (string)Str::uuid(),
            'tenant_id'           => $tenantId,
            'nama_pemetaan_mapel' => "{$namaMapel} - {$namaKelas} ({$validated['hari']})",
            'tahun_ajaran'        => $validated['tahun_ajaran'],
            'semester'            => $validated['semester'],
            'kelas_id'            => $validated['kelas_id'],
            'mapel_id'            => $validated['mapel_id'],
            'kelompok_id'         => $mapel?->kategori ?? 'Kelompok A (Umum)',
            'guru_id'             => $validated['guru_id'] ?? null,
            'hari'                => $validated['hari'],
            'jam_ke'              => $validated['jam_ke'] ?? null,
            'jam_mulai'           => $validated['jam_mulai'],
            'jam_selesai'         => $validated['jam_selesai'],
            'ruangan'             => $ruangan,
            'kkm'                 => $validated['kkm'] ?? 75,
            'jam_pelajaran'       => $validated['jam_pelajaran'] ?? 2,
            'warna_label'         => $validated['warna_label'] ?? '#3b82f6',
            'catatan'             => $validated['catatan'] ?? null,
            'is_active'           => true,
        ]);

        if ($request->wantsJson() && !$request->header('X-Inertia')) {
            return response()->json([
                'success' => true,
                'message' => "Jadwal pelajaran {$namaMapel} ({$namaKelas}) hari {$validated['hari']} berhasil disimpan.",
                'data'    => $jadwal,
            ], 201);
        }

        return back()->with('success', "Jadwal pelajaran {$namaMapel} ({$namaKelas}) hari {$validated['hari']} berhasil disimpan.");
    }

    /**
     * Update Jadwal Pelajaran (with Anti-Bentrok Conflict Check)
     */
    public function update(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $tenantId = $this->resolveTenantId($request);
        $jadwal = PemetaanMapel::withoutTenant()
            ->when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))
            ->findOrFail($id);

        $validated = $request->validate([
            'tahun_ajaran'        => 'required|string|max:50',
            'semester'            => 'required|string|max:20',
            'kelas_id'            => 'required|string',
            'mapel_id'            => 'required|string',
            'guru_id'             => 'nullable|uuid',
            'hari'                => 'required|string|max:20',
            'jam_ke'              => 'nullable|string|max:50',
            'jam_mulai'           => 'required|string|max:10',
            'jam_selesai'         => 'required|string|max:10',
            'ruangan'             => 'nullable|string|max:100',
            'kkm'                 => 'nullable|numeric|min:0|max:100',
            'jam_pelajaran'       => 'nullable|integer|min:1|max:20',
            'warna_label'         => 'nullable|string|max:20',
            'catatan'             => 'nullable|string|max:500',
            'force_override'      => 'nullable|boolean',
        ]);

        $forceOverride = $request->boolean('force_override', false);

        // Run Conflict Check
        $conflicts = $this->checkScheduleConflict(
            $tenantId,
            $id,
            $validated['tahun_ajaran'],
            $validated['semester'],
            $validated['hari'],
            $validated['jam_mulai'],
            $validated['jam_selesai'],
            $validated['kelas_id'],
            $validated['guru_id'] ?? null,
            $validated['ruangan'] ?? null
        );

        if (!empty($conflicts) && !$forceOverride) {
            $msg = 'Terdeteksi bentrok jadwal: ' . implode(' | ', $conflicts);
            if ($request->wantsJson() && !$request->header('X-Inertia')) {
                return response()->json([
                    'success'     => false,
                    'is_conflict' => true,
                    'message'     => $msg,
                    'conflicts'   => $conflicts,
                ], 422);
            }
            return back()->withErrors(['conflict' => $msg])->with('error', $msg);
        }

        $mapel = MataPelajaran::withoutTenant()->find($validated['mapel_id']);
        $kelas = Kelas::withoutTenant()->find($validated['kelas_id']);
        $namaMapel = $mapel?->nama_mata_pelajaran ?? 'Mata Pelajaran';
        $namaKelas = $kelas?->nama_kelas ?? 'Kelas';
        $ruangan = !empty($validated['ruangan']) ? $validated['ruangan'] : $namaKelas;

        $jadwal->update([
            'nama_pemetaan_mapel' => "{$namaMapel} - {$namaKelas} ({$validated['hari']})",
            'tahun_ajaran'        => $validated['tahun_ajaran'],
            'semester'            => $validated['semester'],
            'kelas_id'            => $validated['kelas_id'],
            'mapel_id'            => $validated['mapel_id'],
            'kelompok_id'         => $mapel?->kategori ?? $jadwal->kelompok_id,
            'guru_id'             => $validated['guru_id'] ?? null,
            'hari'                => $validated['hari'],
            'jam_ke'              => $validated['jam_ke'] ?? null,
            'jam_mulai'           => $validated['jam_mulai'],
            'jam_selesai'         => $validated['jam_selesai'],
            'ruangan'             => $ruangan,
            'kkm'                 => $validated['kkm'] ?? $jadwal->kkm,
            'jam_pelajaran'       => $validated['jam_pelajaran'] ?? $jadwal->jam_pelajaran,
            'warna_label'         => $validated['warna_label'] ?? $jadwal->warna_label,
            'catatan'             => $validated['catatan'] ?? null,
        ]);

        if ($request->wantsJson() && !$request->header('X-Inertia')) {
            return response()->json([
                'success' => true,
                'message' => "Jadwal pelajaran {$namaMapel} ({$namaKelas}) berhasil diperbarui.",
                'data'    => $jadwal,
            ]);
        }

        return back()->with('success', "Jadwal pelajaran {$namaMapel} ({$namaKelas}) berhasil diperbarui.");
    }

    /**
     * Delete Jadwal Pelajaran
     */
    public function destroy(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $tenantId = $this->resolveTenantId($request);
        $jadwal = PemetaanMapel::withoutTenant()
            ->when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))
            ->findOrFail($id);

        $jadwalName = $jadwal->nama_pemetaan_mapel;
        $jadwal->delete();

        if ($request->wantsJson() && !$request->header('X-Inertia')) {
            return response()->json([
                'success' => true,
                'message' => "Jadwal pelajaran {$jadwalName} berhasil dihapus.",
            ]);
        }

        return back()->with('success', "Jadwal pelajaran {$jadwalName} berhasil dihapus.");
    }

    /**
     * Live AJAX Real-time Conflict Checker Endpoint
     */
    public function checkConflict(Request $request): JsonResponse
    {
        $tenantId = $this->resolveTenantId($request);
        $currentId = $request->input('id');
        $tahunAjaran = $request->input('tahun_ajaran', '');
        $semester = $request->input('semester', '');
        $hari = $request->input('hari', '');
        $jamMulai = $request->input('jam_mulai', '');
        $jamSelesai = $request->input('jam_selesai', '');
        $kelasId = $request->input('kelas_id', '');
        $guruId = $request->input('guru_id', '');
        $ruangan = $request->input('ruangan', '');

        if (!$tahunAjaran || !$semester || !$hari || !$jamMulai || !$jamSelesai) {
            return response()->json([
                'success'     => true,
                'has_conflict'=> false,
                'conflicts'   => [],
            ]);
        }

        $conflicts = $this->checkScheduleConflict(
            $tenantId,
            $currentId,
            $tahunAjaran,
            $semester,
            $hari,
            $jamMulai,
            $jamSelesai,
            $kelasId,
            $guruId,
            $ruangan
        );

        return response()->json([
            'success'      => true,
            'has_conflict' => !empty($conflicts),
            'conflicts'    => $conflicts,
        ]);
    }

    /**
     * Download Excel (.xlsx) Template for Batch Schedule Import (Multi-Sheet with Master References)
     */
    public function downloadTemplateJadwal(Request $request)
    {
        $tenantId = $this->resolveTenantId($request);

        // 1. Fetch Master Data for Reference Sheet
        $tahunAjaranList = TahunAjaran::withoutTenant()
            ->when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))
            ->orderBy('nama_tahun_ajaran', 'desc')
            ->get(['id', 'nama_tahun_ajaran']);

        $kelasList = Kelas::withoutTenant()
            ->when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))
            ->orderBy('nama_kelas', 'asc')
            ->get(['id', 'kode_kelas', 'nama_kelas', 'kategori']);

        $mapelList = MataPelajaran::withoutTenant()
            ->when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))
            ->orderBy('nama_mata_pelajaran', 'asc')
            ->get(['id', 'nama_mata_pelajaran', 'kategori']);

        $guruList = User::query()
            ->when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))
            ->where('is_active', true)
            ->whereHas('role', function($q) {
                $q->whereIn('nama_role', ['guru', 'wali_kelas', 'guru_bk', 'waka_kurikulum', 'admin_sekolah', 'super_admin']);
            })
            ->orderBy('nama_lengkap', 'asc')
            ->get(['id', 'nama_lengkap', 'nip', 'email']);

        $ruangList = PemetaanMapel::withoutTenant()
            ->when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))
            ->whereNotNull('ruangan')
            ->where('ruangan', '!=', '')
            ->distinct()
            ->pluck('ruangan')
            ->toArray();
        if (empty($ruangList)) {
            $ruangList = ['R. 101', 'R. 102', 'Lab Komputer 1', 'Lab Komputer 2', 'Lab IPA', 'Lab Bahasa', 'Aula Utama', 'Lapangan Olahraga'];
        }

        $sampleKelas = $kelasList->first()?->nama_kelas ?? 'X IPA 1';
        $sampleMapel1 = $mapelList[0]->nama_mata_pelajaran ?? 'Matematika Wajib';
        $sampleMapel2 = $mapelList[1]->nama_mata_pelajaran ?? 'Bahasa Indonesia';
        $sampleGuru1 = $guruList->first()?->nama_lengkap ?? 'Budi Santoso, M.Pd';
        $sampleGuru2 = $guruList[1]->nama_lengkap ?? 'Siti Aminah, S.Pd';

        // SHEET 1: Form Template Pengisian Jadwal (Tanpa KKM dan Tanpa Kelompok Mapel)
        $sheet1Data = [
            // Row 1: Header Titles
            [
                '<b>Tahun Ajaran</b>',
                '<b>Semester</b>',
                '<b>Kelas (Nama / ID)</b>',
                '<b>Mata Pelajaran (Nama / ID)</b>',
                '<b>Guru Pengampu (Nama / NIP / ID)</b>',
                '<b>Hari</b>',
                '<b>Jam Ke</b>',
                '<b>Jam Mulai</b>',
                '<b>Jam Selesai</b>',
                '<b>Ruangan / Lab</b>',
                '<b>Jam Pelajaran (JP)</b>',
                '<b>Catatan</b>',
            ],
            // Row 2: Sample 1 (Pengisian via Nama)
            [
                '2026/2027',
                'Ganjil',
                $sampleKelas,
                $sampleMapel1,
                $sampleGuru1,
                'Senin',
                '1-2',
                '07:00',
                '08:30',
                'R. 101',
                2,
                'Jadwal reguler jam ke-1 dan 2',
            ],
            // Row 3: Sample 2 (Pengisian via Nama)
            [
                '2026/2027',
                'Ganjil',
                $sampleKelas,
                $sampleMapel2,
                $sampleGuru2,
                'Senin',
                '3-4',
                '08:30',
                '10:00',
                'R. 101',
                2,
                'Jadwal reguler jam ke-3 dan 4',
            ],
            // Row 4: Sample 3 (Pengisian via ID UUID)
            [
                '2026/2027',
                'Ganjil',
                $kelasList->first()?->id ?? $sampleKelas,
                $mapelList[0]->id ?? $sampleMapel1,
                $guruList->first()?->id ?? $sampleGuru1,
                'Selasa',
                '1-2',
                '07:00',
                '08:30',
                'Lab Komputer 1',
                2,
                'Praktikum komputer (contoh input menggunakan ID UUID)',
            ],
        ];

        // SHEET 2: Referensi Master ID & Nama (Lengkap & Terstruktur)
        $sheet2Data = [
            ['<center><b>PANDUAN REFERENSI MASTER DATA UNTUK IMPORT JADWAL PELAJARAN</b></center>'],
            ['<i>Catatan: Anda dapat mengisi kolom Kelas, Mata Pelajaran, dan Guru pada Template menggunakan NAMA LENGKAP maupun ID (UUID/Kode) yang tertera di bawah:</i>'],
            [''],

            // 1. Tahun Ajaran
            ['<b>1. REFERENSI TAHUN AJARAN</b>'],
            ['<b>ID / Nama Tahun Ajaran</b>', '<b>Keterangan</b>'],
        ];

        if ($tahunAjaranList->isNotEmpty()) {
            foreach ($tahunAjaranList as $ta) {
                $sheet2Data[] = [$ta->nama_tahun_ajaran, 'Tahun Ajaran Terdaftar'];
            }
        } else {
            $sheet2Data[] = ['2026/2027', 'Default Tahun Ajaran Aktif'];
            $sheet2Data[] = ['2025/2026', 'Tahun Ajaran Lalu'];
        }

        $sheet2Data[] = [''];
        $sheet2Data[] = ['<b>2. REFERENSI KELAS (ROMBEL)</b>'];
        $sheet2Data[] = ['<b>ID Kelas (UUID)</b>', '<b>Kode Kelas</b>', '<b>Nama Kelas</b>', '<b>Kategori / Tingkat</b>'];
        foreach ($kelasList as $k) {
            $sheet2Data[] = [(string)$k->id, $k->kode_kelas ?? '-', $k->nama_kelas, $k->kategori ? (string)$k->kategori : '-'];
        }

        $sheet2Data[] = [''];
        $sheet2Data[] = ['<b>3. REFERENSI MATA PELAJARAN</b>'];
        $sheet2Data[] = ['<b>ID Mapel (UUID)</b>', '<b>Nama Mata Pelajaran</b>', '<b>Kategori Mapel</b>'];
        foreach ($mapelList as $m) {
            $sheet2Data[] = [(string)$m->id, $m->nama_mata_pelajaran, $m->kategori ?? 'Kelompok A'];
        }

        $sheet2Data[] = [''];
        $sheet2Data[] = ['<b>4. REFERENSI GURU PENGAMPU</b>'];
        $sheet2Data[] = ['<b>ID Guru (UUID)</b>', '<b>Nama Lengkap Guru</b>', '<b>NIP</b>', '<b>Email</b>'];
        foreach ($guruList as $g) {
            $sheet2Data[] = [(string)$g->id, $g->nama_lengkap, $g->nip ?? '-', $g->email ?? '-'];
        }

        $sheet2Data[] = [''];
        $sheet2Data[] = ['<b>5. REFERENSI RUANGAN / LAB</b>'];
        $sheet2Data[] = ['<b>Nama Ruangan</b>', '<b>Keterangan</b>'];
        foreach ($ruangList as $r) {
            $sheet2Data[] = [$r, 'Ruang / Laboratorium Sekolah'];
        }

        $xlsx = SimpleXLSXGen::fromArray($sheet1Data, 'Template Jadwal')
            ->addSheet($sheet2Data, 'Referensi Master Data');

        $filename = 'Template_Import_Jadwal_Pelajaran_SINTA.xlsx';

        return response((string)$xlsx, 200, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Preview Import Excel (.xlsx / .csv) with Comprehensive Conflict & Format Validation
     */
    public function previewImport(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv,txt|max:5120',
        ]);

        $tenantId = $this->resolveTenantId($request);
        $file = $request->file('file');
        $extension = strtolower($file->getClientOriginalExtension());

        $rows = [];
        if ($extension === 'xlsx' || $extension === 'xls') {
            if ($xlsx = SimpleXLSX::parse($file->getRealPath())) {
                $rows = $xlsx->rows(); // Reads first sheet (Template Jadwal)
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal membaca file Excel: ' . SimpleXLSX::parseError(),
                ], 422);
            }
        } else {
            // CSV parsing
            $handle = fopen($file->getRealPath(), 'r');
            while (($data = fgetcsv($handle, 2000, ',')) !== false) {
                $rows[] = $data;
            }
            fclose($handle);
        }

        if (count($rows) < 2) {
            return response()->json([
                'success' => false,
                'message' => 'File Excel kosong atau tidak memiliki data baris jadwal.',
            ], 422);
        }

        // Header mapping
        $header = array_map('trim', array_map('strip_tags', $rows[0]));
        $dataRows = array_slice($rows, 1);

        // Preload maps for fast resolution by Name, Code, or UUID ID
        $kelasList = Kelas::withoutTenant()
            ->when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))
            ->get(['id', 'nama_kelas', 'kode_kelas']);
        $kelasMap = [];
        foreach ($kelasList as $k) {
            $kelasMap[strtolower(trim($k->nama_kelas))] = $k->id;
            $kelasMap[strtolower(trim($k->id))] = $k->id;
            if ($k->kode_kelas) {
                $kelasMap[strtolower(trim($k->kode_kelas))] = $k->id;
            }
        }

        $mapelList = MataPelajaran::withoutTenant()
            ->when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))
            ->get(['id', 'nama_mata_pelajaran', 'kategori']);
        $mapelMap = [];
        foreach ($mapelList as $m) {
            $mapelMap[strtolower(trim($m->nama_mata_pelajaran))] = $m;
            $mapelMap[strtolower(trim($m->id))] = $m;
        }

        $guruList = User::query()
            ->when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))
            ->get(['id', 'nama_lengkap', 'nip', 'email']);
        $guruMap = [];
        foreach ($guruList as $g) {
            $guruMap[strtolower(trim($g->nama_lengkap))] = $g->id;
            $guruMap[strtolower(trim($g->id))] = $g->id;
            if ($g->nip) {
                $guruMap[trim($g->nip)] = $g->id;
            }
            if ($g->email) {
                $guruMap[strtolower(trim($g->email))] = $g->id;
            }
        }

        // Detect if uploaded format has old KKM/Kelompok columns (14 cols) or new streamlined format (12 cols)
        $hasLegacyColumns = false;
        foreach ($header as $hCol) {
            if (stripos($hCol, 'KKM') !== false || stripos($hCol, 'Kelompok') !== false) {
                $hasLegacyColumns = true;
                break;
            }
        }

        $previewRows = [];
        $validCount = 0;
        $conflictCount = 0;
        $errorCount = 0;

        foreach ($dataRows as $idx => $row) {
            // Skip completely empty rows
            if (empty(array_filter($row, fn($v) => trim((string)$v) !== ''))) {
                continue;
            }

            $tahunAjaran = trim((string)($row[0] ?? '2026/2027'));
            $semester = trim((string)($row[1] ?? 'Ganjil'));
            $rawKelas = trim((string)($row[2] ?? ''));
            $rawMapel = trim((string)($row[3] ?? ''));
            $rawGuru = trim((string)($row[4] ?? ''));
            $hari = ucfirst(strtolower(trim((string)($row[5] ?? 'Senin'))));
            $jamKe = trim((string)($row[6] ?? '1-2'));
            $jamMulai = trim((string)($row[7] ?? '07:00'));
            $jamSelesai = trim((string)($row[8] ?? '08:30'));
            $ruangan = trim((string)($row[9] ?? ''));
            $jamPelajaran = (int)($row[10] ?? 2) ?: 2;
            
            if ($hasLegacyColumns) {
                $catatan = trim((string)($row[13] ?? $row[11] ?? ''));
            } else {
                $catatan = trim((string)($row[11] ?? ''));
            }

            // Format validation & mapping
            $errors = [];
            $kelasId = $kelasMap[strtolower($rawKelas)] ?? null;
            if (!$kelasId) {
                $errors[] = "Kelas '{$rawKelas}' tidak terdaftar di sistem";
            }

            $mapelObj = $mapelMap[strtolower($rawMapel)] ?? null;
            $mapelId = $mapelObj?->id ?? null;
            if (!$mapelId) {
                $errors[] = "Mata Pelajaran '{$rawMapel}' tidak ditemukan";
            }

            $guruId = null;
            if ($rawGuru !== '') {
                $guruId = $guruMap[strtolower($rawGuru)] ?? null;
                if (!$guruId) {
                    $errors[] = "Guru '{$rawGuru}' tidak terdaftar di sistem";
                }
            }

            if (!$jamMulai || !$jamSelesai) {
                $errors[] = 'Jam Mulai dan Jam Selesai wajib diisi';
            }

            $conflicts = [];
            $status = 'valid';

            if (!empty($errors)) {
                $status = 'invalid';
                $errorCount++;
            } else {
                // Check conflicts against existing DB
                $conflicts = $this->checkScheduleConflict(
                    $tenantId,
                    null,
                    $tahunAjaran,
                    $semester,
                    $hari,
                    $jamMulai,
                    $jamSelesai,
                    $kelasId,
                    $guruId,
                    $ruangan
                );

                if (!empty($conflicts)) {
                    $status = 'conflict';
                    $conflictCount++;
                } else {
                    $validCount++;
                }
            }

            $previewRows[] = [
                'row_index'      => $idx + 2,
                'status'         => $status, // 'valid' | 'conflict' | 'invalid'
                'tahun_ajaran'   => $tahunAjaran,
                'semester'       => $semester,
                'raw_kelas'      => $rawKelas,
                'kelas_id'       => $kelasId,
                'raw_mapel'      => $rawMapel,
                'mapel_id'       => $mapelId,
                'raw_guru'       => $rawGuru,
                'guru_id'        => $guruId,
                'hari'           => $hari,
                'jam_ke'         => $jamKe,
                'jam_mulai'      => $jamMulai,
                'jam_selesai'    => $jamSelesai,
                'ruangan'        => $ruangan,
                'jam_pelajaran'  => $jamPelajaran,
                'catatan'        => $catatan,
                'errors'         => $errors,
                'conflicts'      => $conflicts,
            ];
        }

        return response()->json([
            'success'        => true,
            'total_rows'     => count($previewRows),
            'valid_count'    => $validCount,
            'conflict_count' => $conflictCount,
            'error_count'    => $errorCount,
            'rows'           => $previewRows,
        ]);
    }

    /**
     * Commit & Store Validated Import Payload into PostgreSQL
     */
    public function importJadwal(Request $request): RedirectResponse|JsonResponse
    {
        $tenantId = $this->resolveTenantId($request);

        $validated = $request->validate([
            'rows'           => 'required|array|min:1',
            'rows.*.tahun_ajaran' => 'required|string',
            'rows.*.semester'     => 'required|string',
            'rows.*.kelas_id'     => 'required|string',
            'rows.*.mapel_id'     => 'required|string',
            'rows.*.guru_id'      => 'nullable|string',
            'rows.*.hari'         => 'required|string',
            'rows.*.jam_ke'       => 'nullable|string',
            'rows.*.jam_mulai'    => 'required|string',
            'rows.*.jam_selesai'  => 'required|string',
            'rows.*.ruangan'      => 'nullable|string',
            'rows.*.jam_pelajaran'=> 'nullable|integer',
            'rows.*.catatan'      => 'nullable|string',
            'skip_conflicts'      => 'nullable|boolean',
        ]);

        $rows = $validated['rows'];
        $skipConflicts = $request->boolean('skip_conflicts', true);
        $importedCount = 0;
        $skippedCount = 0;

        DB::beginTransaction();
        try {
            foreach ($rows as $item) {
                // If skipping conflicts, check one more time
                if ($skipConflicts) {
                    $conflicts = $this->checkScheduleConflict(
                        $tenantId,
                        null,
                        $item['tahun_ajaran'],
                        $item['semester'],
                        $item['hari'],
                        $item['jam_mulai'],
                        $item['jam_selesai'],
                        $item['kelas_id'],
                        $item['guru_id'] ?? null,
                        $item['ruangan'] ?? null
                    );

                    if (!empty($conflicts)) {
                        $skippedCount++;
                        continue;
                    }
                }

                $mapel = MataPelajaran::withoutTenant()->find($item['mapel_id']);
                $kelas = Kelas::withoutTenant()->find($item['kelas_id']);
                $namaMapel = $mapel?->nama_mata_pelajaran ?? 'Mata Pelajaran';
                $namaKelas = $kelas?->nama_kelas ?? 'Kelas';
                $ruangan = !empty($item['ruangan']) ? $item['ruangan'] : $namaKelas;

                PemetaanMapel::create([
                    'id'                  => (string)Str::uuid(),
                    'tenant_id'           => $tenantId,
                    'nama_pemetaan_mapel' => "{$namaMapel} - {$namaKelas} ({$item['hari']})",
                    'tahun_ajaran'        => $item['tahun_ajaran'],
                    'semester'            => $item['semester'],
                    'kelas_id'            => $item['kelas_id'],
                    'mapel_id'            => $item['mapel_id'],
                    'kelompok_id'         => $mapel?->kategori ?? 'Kelompok A (Umum)',
                    'guru_id'             => $item['guru_id'] ?? null,
                    'hari'                => $item['hari'],
                    'jam_ke'              => $item['jam_ke'] ?? null,
                    'jam_mulai'           => $item['jam_mulai'],
                    'jam_selesai'         => $item['jam_selesai'],
                    'ruangan'             => $ruangan,
                    'kkm'                 => 75,
                    'jam_pelajaran'       => $item['jam_pelajaran'] ?? 2,
                    'warna_label'         => '#3b82f6',
                    'catatan'             => $item['catatan'] ?? null,
                    'is_active'           => true,
                ]);

                $importedCount++;
            }

            DB::commit();

            $msg = "Import Berhasil: {$importedCount} jadwal pelajaran berhasil disimpan ke sistem.";
            if ($skippedCount > 0) {
                $msg .= " ({$skippedCount} jadwal dilewati karena terdeteksi bentrok).";
            }

            if ($request->wantsJson() && !$request->header('X-Inertia')) {
                return response()->json([
                    'success'        => true,
                    'message'        => $msg,
                    'imported_count' => $importedCount,
                    'skipped_count'  => $skippedCount,
                ]);
            }

            return back()->with('success', $msg);
        } catch (\Exception $e) {
            DB::rollBack();
            if ($request->wantsJson() && !$request->header('X-Inertia')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal memproses import data jadwal: ' . $e->getMessage(),
                ], 500);
            }
            return back()->with('error', 'Gagal memproses import: ' . $e->getMessage());
        }
    }

    /**
     * Export Filtered Schedules to Formatted Excel (.xlsx) using SimpleXLSXGen
     */
    public function exportJadwal(Request $request)
    {
        $tenantId = $this->resolveTenantId($request);
        $tahunAjaran = $request->input('tahun_ajaran', '');
        $semester = $request->input('semester', '');
        $kelasId = $request->input('kelas_id', '');
        $guruId = $request->input('guru_id', '');
        $ruangan = $request->input('ruangan', '');
        $hari = $request->input('hari', '');

        $query = PemetaanMapel::withoutTenant()
            ->when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))
            ->where('is_active', true)
            ->with(['kelas', 'mapel', 'guru']);

        if ($tahunAjaran) $query->where('tahun_ajaran', $tahunAjaran);
        if ($semester) $query->where('semester', $semester);
        if ($kelasId) $query->where('kelas_id', $kelasId);
        if ($guruId) $query->where('guru_id', $guruId);
        if ($ruangan) $query->where('ruangan', $ruangan);
        if ($hari) $query->where('hari', $hari);

        $items = $query->orderByRaw("CASE hari 
                WHEN 'Senin' THEN 1 
                WHEN 'Selasa' THEN 2 
                WHEN 'Rabu' THEN 3 
                WHEN 'Kamis' THEN 4 
                WHEN 'Jumat' THEN 5 
                WHEN 'Sabtu' THEN 6 
                ELSE 7 END")
            ->orderBy('jam_mulai', 'asc')
            ->get();

        $tenantObj = $tenantId ? Tenant::find($tenantId) : null;
        $namaSekolah = $tenantObj?->nama_sekolah ?? $tenantObj?->nama ?? 'SINTA SaaS Academic';

        $data = [
            // Title Header
            ['<center><b>JADWAL PELAJARAN ' . strtoupper($namaSekolah) . '</b></center>'],
            ['<center><b>Tahun Ajaran: ' . ($tahunAjaran ?: 'Semua') . ' | Semester: ' . ($semester ?: 'Semua') . '</b></center>'],
            [''], // Empty row separator
            // Table Header (Tanpa KKM dan Tanpa Kelompok Mapel)
            [
                '<b>No</b>',
                '<b>Hari</b>',
                '<b>Jam Ke</b>',
                '<b>Waktu Pelajaran</b>',
                '<b>Kelas (Rombel)</b>',
                '<b>Mata Pelajaran</b>',
                '<b>Guru Pengampu</b>',
                '<b>NIP Guru</b>',
                '<b>Ruangan / Lab</b>',
                '<b>Beban (JP)</b>',
                '<b>Catatan</b>',
            ],
        ];

        foreach ($items as $idx => $row) {
            $data[] = [
                $idx + 1,
                $row->hari ?? '-',
                $row->jam_ke ?? '-',
                ($row->jam_mulai ?? '-') . ' - ' . ($row->jam_selesai ?? '-'),
                $row->kelas?->nama_kelas ?? $row->kelas_id ?? '-',
                $row->mapel?->nama_mata_pelajaran ?? '-',
                $row->guru?->nama_lengkap ?? 'Belum Ditentukan',
                $row->guru?->nip ?? '-',
                $row->ruangan ?? '-',
                (int)($row->jam_pelajaran ?? 2),
                $row->catatan ?? '-',
            ];
        }

        $xlsx = SimpleXLSXGen::fromArray($data, 'Jadwal Pelajaran');
        $cleanTa = $tahunAjaran ? str_replace('/', '-', $tahunAjaran) : 'Semua';
        $filename = "Jadwal_Pelajaran_{$cleanTa}_{$semester}.xlsx";

        return response((string)$xlsx, 200, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Copy / Clone Schedule from Previous Semester or Academic Year
     */
    public function copyJadwal(Request $request): RedirectResponse|JsonResponse
    {
        $tenantId = $this->resolveTenantId($request);

        $validated = $request->validate([
            'from_tahun_ajaran' => 'required|string|max:50',
            'from_semester'     => 'required|string|max:20',
            'to_tahun_ajaran'   => 'required|string|max:50',
            'to_semester'       => 'required|string|max:20',
        ]);

        $sourceSchedules = PemetaanMapel::withoutTenant()
            ->when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))
            ->where('tahun_ajaran', $validated['from_tahun_ajaran'])
            ->where('semester', $validated['from_semester'])
            ->where('is_active', true)
            ->get();

        if ($sourceSchedules->isEmpty()) {
            $msg = "Tidak ditemukan data jadwal pada periode sumber ({$validated['from_tahun_ajaran']} - {$validated['from_semester']}).";
            if ($request->wantsJson() && !$request->header('X-Inertia')) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return back()->with('error', $msg);
        }

        $copiedCount = 0;
        DB::beginTransaction();
        try {
            foreach ($sourceSchedules as $src) {
                PemetaanMapel::create([
                    'id'                  => (string)Str::uuid(),
                    'tenant_id'           => $src->tenant_id,
                    'nama_pemetaan_mapel' => $src->nama_pemetaan_mapel,
                    'tahun_ajaran'        => $validated['to_tahun_ajaran'],
                    'semester'            => $validated['to_semester'],
                    'kelas_id'            => $src->kelas_id,
                    'mapel_id'            => $src->mapel_id,
                    'kelompok_id'         => $src->kelompok_id,
                    'guru_id'             => $src->guru_id,
                    'hari'                => $src->hari,
                    'jam_ke'              => $src->jam_ke,
                    'jam_mulai'           => $src->jam_mulai,
                    'jam_selesai'         => $src->jam_selesai,
                    'ruangan'             => $src->ruangan,
                    'kkm'                 => $src->kkm,
                    'jam_pelajaran'       => $src->jam_pelajaran,
                    'warna_label'         => $src->warna_label ?? '#3b82f6',
                    'catatan'             => $src->catatan,
                    'is_active'           => true,
                ]);
                $copiedCount++;
            }

            DB::commit();

            $msg = "Berhasil menyalin {$copiedCount} jadwal pelajaran dari {$validated['from_tahun_ajaran']} ({$validated['from_semester']}) ke {$validated['to_tahun_ajaran']} ({$validated['to_semester']}).";
            if ($request->wantsJson() && !$request->header('X-Inertia')) {
                return response()->json(['success' => true, 'message' => $msg, 'copied_count' => $copiedCount]);
            }

            return back()->with('success', $msg);
        } catch (\Exception $e) {
            DB::rollBack();
            if ($request->wantsJson() && !$request->header('X-Inertia')) {
                return response()->json(['success' => false, 'message' => 'Gagal menyalin jadwal: ' . $e->getMessage()], 500);
            }
            return back()->with('error', 'Gagal menyalin jadwal: ' . $e->getMessage());
        }
    }

    /**
     * Internal Conflict Validator for a single schedule query
     */
    protected function checkScheduleConflict(
        ?string $tenantId,
        ?string $ignoreId,
        string $tahunAjaran,
        string $semester,
        string $hari,
        string $jamMulai,
        string $jamSelesai,
        string $kelasId,
        ?string $guruId,
        ?string $ruangan
    ): array {
        $conflicts = [];

        // Clean time format to HH:MM for string comparison
        $start = substr($jamMulai, 0, 5);
        $end = substr($jamSelesai, 0, 5);

        // Fetch candidate overlapping schedules on the same Day & Period
        $candidates = PemetaanMapel::withoutTenant()
            ->when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))
            ->where('is_active', true)
            ->where('tahun_ajaran', $tahunAjaran)
            ->where('semester', $semester)
            ->where('hari', $hari)
            ->when($ignoreId, fn($q) => $q->where('id', '!=', $ignoreId))
            ->with(['kelas', 'mapel', 'guru'])
            ->get();

        foreach ($candidates as $cand) {
            $candStart = substr((string)$cand->jam_mulai, 0, 5);
            $candEnd = substr((string)$cand->jam_selesai, 0, 5);

            // Check time overlap: (StartA < EndB) and (EndA > StartB)
            if ($candStart && $candEnd && ($start < $candEnd) && ($end > $candStart)) {
                $candKelas = $cand->kelas?->nama_kelas ?? 'Kelas lain';
                $candMapel = $cand->mapel?->nama_mata_pelajaran ?? 'Mapel lain';
                $candGuru = $cand->guru?->nama_lengkap ?? 'Guru';

                // 1. Conflict Guru: Same teacher cannot teach 2 different classes at the same time
                if ($guruId && $cand->guru_id && $guruId === $cand->guru_id && $kelasId !== $cand->kelas_id) {
                    $conflicts[] = "Bentrok Guru: {$candGuru} sudah terjadwal mengajar {$candMapel} di kelas {$candKelas} pada {$hari} pukul {$candStart}-{$candEnd}.";
                }

                // 2. Conflict Ruang: Same room cannot be used by 2 different classes at the same time
                if ($ruangan && $cand->ruangan && strcasecmp(trim($ruangan), trim($cand->ruangan)) === 0 && $kelasId !== $cand->kelas_id) {
                    $conflicts[] = "Bentrok Ruangan: Ruang '{$ruangan}' sedang digunakan oleh {$candKelas} ({$candMapel}) pada {$hari} pukul {$candStart}-{$candEnd}.";
                }

                // 3. Conflict Kelas: Same class cannot have 2 different subjects at the same time
                if ($kelasId === $cand->kelas_id) {
                    $conflicts[] = "Bentrok Kelas: {$candKelas} sudah memiliki jadwal {$candMapel} ({$candGuru}) pada {$hari} pukul {$candStart}-{$candEnd}.";
                }
            }
        }

        return array_unique($conflicts);
    }

    /**
     * Detect All System Conflicts across a given collection of schedules
     */
    protected function detectAllConflicts($schedules): array
    {
        $conflictsById = [];
        $conflictsDetail = [];

        $groupedByDay = $schedules->groupBy('hari');

        foreach ($groupedByDay as $day => $dayItems) {
            $count = $dayItems->count();
            for ($i = 0; $i < $count; $i++) {
                $itemA = $dayItems[$i];
                $startA = substr((string)$itemA->jam_mulai, 0, 5);
                $endA = substr((string)$itemA->jam_selesai, 0, 5);

                for ($j = $i + 1; $j < $count; $j++) {
                    $itemB = $dayItems[$j];
                    $startB = substr((string)$itemB->jam_mulai, 0, 5);
                    $endB = substr((string)$itemB->jam_selesai, 0, 5);

                    // Check time overlap
                    if ($startA && $endA && $startB && $endB && ($startA < $endB) && ($endA > $startB)) {
                        $kelasA = $itemA->kelas?->nama_kelas ?? 'Kelas A';
                        $kelasB = $itemB->kelas?->nama_kelas ?? 'Kelas B';
                        $mapelA = $itemA->mapel?->nama_mata_pelajaran ?? 'Mapel A';
                        $mapelB = $itemB->mapel?->nama_mata_pelajaran ?? 'Mapel B';
                        $guruA = $itemA->guru?->nama_lengkap ?? 'Guru A';
                        $guruB = $itemB->guru?->nama_lengkap ?? 'Guru B';

                        // Conflict Guru
                        if ($itemA->guru_id && $itemB->guru_id && $itemA->guru_id === $itemB->guru_id && $itemA->kelas_id !== $itemB->kelas_id) {
                            $reason = "Bentrok Guru [{$guruA}]: Mengajar di {$kelasA} ({$mapelA}) dan {$kelasB} ({$mapelB}) di waktu bersamaan ({$day}, {$startA}-{$endA} vs {$startB}-{$endB}).";
                            $conflictsById[$itemA->id][] = $reason;
                            $conflictsById[$itemB->id][] = $reason;
                            $conflictsDetail[] = [
                                'type'        => 'guru',
                                'title'       => "Bentrok Guru: {$guruA}",
                                'description' => $reason,
                                'jadwal_a'    => $itemA,
                                'jadwal_b'    => $itemB,
                            ];
                        }

                        // Conflict Ruangan
                        if ($itemA->ruangan && $itemB->ruangan && strcasecmp(trim($itemA->ruangan), trim($itemB->ruangan)) === 0 && $itemA->kelas_id !== $itemB->kelas_id) {
                            $reason = "Bentrok Ruangan [{$itemA->ruangan}]: Digunakan bersamaan oleh {$kelasA} ({$mapelA}) dan {$kelasB} ({$mapelB}) ({$day}, {$startA}-{$endA} vs {$startB}-{$endB}).";
                            $conflictsById[$itemA->id][] = $reason;
                            $conflictsById[$itemB->id][] = $reason;
                            $conflictsDetail[] = [
                                'type'        => 'ruangan',
                                'title'       => "Bentrok Ruang: {$itemA->ruangan}",
                                'description' => $reason,
                                'jadwal_a'    => $itemA,
                                'jadwal_b'    => $itemB,
                            ];
                        }

                        // Conflict Kelas
                        if ($itemA->kelas_id === $itemB->kelas_id) {
                            $reason = "Bentrok Kelas [{$kelasA}]: Terjadwal 2 mapel bersamaan ({$mapelA} dan {$mapelB}) pada {$day}, {$startA}-{$endA}.";
                            $conflictsById[$itemA->id][] = $reason;
                            $conflictsById[$itemB->id][] = $reason;
                            $conflictsDetail[] = [
                                'type'        => 'kelas',
                                'title'       => "Bentrok Kelas: {$kelasA}",
                                'description' => $reason,
                                'jadwal_a'    => $itemA,
                                'jadwal_b'    => $itemB,
                            ];
                        }
                    }
                }
            }
        }

        return [
            'conflicts_by_id'  => $conflictsById,
            'conflicts_detail' => $conflictsDetail,
        ];
    }
}
