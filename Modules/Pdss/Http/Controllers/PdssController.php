<?php

namespace Modules\Pdss\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Modules\Core\Entities\Tenant;
use Modules\Akademik\Entities\Jurusan;
use Modules\Akademik\Entities\TahunAjaran;
use Modules\Siswa\Entities\Siswa;
use Modules\Pdss\Entities\MasterKampus;
use Modules\Pdss\Entities\MasterKampusProdi;
use Modules\Pdss\Entities\PilihanKampus;
use Modules\Pdss\Entities\KesiapanSiswa;
use Modules\Pdss\Entities\PengunduranDiri;
use Modules\Pdss\Entities\PdssLock;
use Modules\Pdss\Services\PdssRankingService;

class PdssController extends Controller
{
    /**
     * Cek apakah pengguna saat ini bertindak sebagai Super Admin Platform
     */
    private function checkIsSuperAdmin($user): bool
    {
        if (!$user) return false;
        
        $roleName = strtolower(trim((string)($user->role ?? $user->peran ?? $user->role_id ?? '')));
        if (in_array($roleName, ['super_admin', 'superadmin', 'administrator', 'root'])) {
            return true;
        }

        if (method_exists($user, 'hasRole') && ($user->hasRole('super_admin') || $user->hasRole('superadmin'))) {
            return true;
        }

        return false;
    }

    /**
     * Helper resolusi Tahun Ajaran Model & ID
     */
    private function resolveTahunAjaran(?string $inputTa)
    {
        if (!$inputTa) {
            return TahunAjaran::withoutTenant()->where('is_active', true)->first()
                ?? TahunAjaran::withoutTenant()->orderBy('nama_tahun_ajaran', 'desc')->first();
        }

        return TahunAjaran::withoutTenant()->where('nama_tahun_ajaran', $inputTa)->first()
            ?? TahunAjaran::withoutTenant()->where('id', $inputTa)->first()
            ?? TahunAjaran::withoutTenant()->where('is_active', true)->first();
    }

    /**
     * Helper resolusi Tenant Aktif yang tepat untuk Super Admin dan Regular User
     */
    private function resolveActiveTenantId(Request $request, $user, ?string $siswaId = null): string
    {
        $isSuperAdmin = $this->checkIsSuperAdmin($user);

        if ($isSuperAdmin) {
            $reqTenant = $request->input('tenant_id');
            if ($reqTenant && $reqTenant !== 'all' && $reqTenant !== '00000000-0000-0000-0000-000000000000') {
                return $reqTenant;
            }

            // Fallback: periksa URL referer jika ada parameter tenant_id
            $referer = $request->header('referer');
            if ($referer) {
                $parsed = parse_url($referer);
                if (!empty($parsed['query'])) {
                    parse_str($parsed['query'], $queryParams);
                    if (!empty($queryParams['tenant_id']) && $queryParams['tenant_id'] !== '00000000-0000-0000-0000-000000000000' && $queryParams['tenant_id'] !== 'all') {
                        return $queryParams['tenant_id'];
                    }
                }
            }

            if ($siswaId) {
                $siswaTenant = Siswa::withoutTenant()->where('id', $siswaId)->value('tenant_id');
                if ($siswaTenant && $siswaTenant !== '00000000-0000-0000-0000-000000000000') {
                    return $siswaTenant;
                }
            }

            $sessTenant = session('tenant_id');
            if ($sessTenant && $sessTenant !== '00000000-0000-0000-0000-000000000000') {
                return $sessTenant;
            }

            // Cari sekolah yang memiliki siswa kelas 12 riil
            $tenantWithStudents = DB::table('siswa.anggota_kelas as ak')
                ->where('ak.tenant_id', '!=', '00000000-0000-0000-0000-000000000000')
                ->value('ak.tenant_id');

            if (!$tenantWithStudents) {
                $tenantWithStudents = Siswa::withoutTenant()
                    ->where('tenant_id', '!=', '00000000-0000-0000-0000-000000000000')
                    ->where(function ($q) {
                        $q->where('kelas_saat_ini', 'ILIKE', '%XII%')
                          ->orWhere('kelas_saat_ini', 'ILIKE', '%12%');
                    })
                    ->value('tenant_id');
            }

            return $tenantWithStudents ?: Tenant::where('npsn', '!=', 'PLATFORM')
                ->where('id', '!=', '00000000-0000-0000-0000-000000000000')
                ->value('id') ?: '11111111-1111-1111-1111-111111111111';
        }

        return $user->tenant_id ?? session('tenant_id') ?? '11111111-1111-1111-1111-111111111111';
    }

    /**
     * Pastikan response redirect selalu tetap berada di rute kanonikal /bk/akademik
     * dengan membawa parameter filter tenant_id dan tahun_ajaran agar konteks tidak hilang
     */
    private function redirectTarget(Request $request, string $status, string $message, ?string $tenantId = null, ?string $tahunAjaran = null): RedirectResponse|JsonResponse
    {
        if ($request->wantsJson()) {
            return response()->json([
                'success' => $status === 'success',
                'message' => $message,
            ]);
        }

        $activeTenant = $tenantId ?? $request->input('tenant_id');
        $activeTa = $tahunAjaran ?? $request->input('tahun_ajaran');

        if (empty($activeTenant) || $activeTenant === '00000000-0000-0000-0000-000000000000') {
            $referer = $request->header('referer');
            if ($referer) {
                $parsed = parse_url($referer);
                if (!empty($parsed['query'])) {
                    parse_str($parsed['query'], $queryParams);
                    if (!empty($queryParams['tenant_id']) && $queryParams['tenant_id'] !== '00000000-0000-0000-0000-000000000000') {
                        $activeTenant = $queryParams['tenant_id'];
                    }
                    if (empty($activeTa) && !empty($queryParams['tahun_ajaran'])) {
                        $activeTa = $queryParams['tahun_ajaran'];
                    }
                }
            }
        }

        $query = [];
        if ($activeTenant && $activeTenant !== 'all' && $activeTenant !== '00000000-0000-0000-0000-000000000000') {
            $query['tenant_id'] = $activeTenant;
        }
        if ($activeTa) {
            $query['tahun_ajaran'] = $activeTa;
        }

        $targetUrl = '/bk/akademik' . (!empty($query) ? ('?' . http_build_query($query)) : '');

        return redirect()->to($targetUrl)->with($status, $message);
    }

    /**
     * Display Main PDSS & Kesiapan Akademik SNBP Page
     * Terintegrasi Penuh dengan Filter Tahun Ajaran & Riwayat Kelas 12
     */
    public function index(Request $request, PdssRankingService $rankingService): InertiaResponse|JsonResponse
    {
        $user = $request->user() ?: Auth::user();
        $isSuperAdmin = $this->checkIsSuperAdmin($user);
        $tenants = [];
        $selectedTenantId = $request->input('tenant_id');

        if ($isSuperAdmin) {
            // Hilangkan "Pusat Kendali SaaS (Global)" dari dropdown pilihan sekolah
            $tenants = Tenant::select('id', 'nama_sekolah', 'npsn')
                ->where('npsn', '!=', 'PLATFORM')
                ->where('id', '!=', '00000000-0000-0000-0000-000000000000')
                ->where('nama_sekolah', 'NOT ILIKE', '%Pusat Kendali%')
                ->orderBy('nama_sekolah', 'asc')
                ->get();

            if ($request->filled('tenant_id')) {
                if ($selectedTenantId === 'all') {
                    $tenantId = null;
                } else {
                    $tenantId = $selectedTenantId;
                }
            } else {
                $tenantWithStudents = DB::table('siswa.anggota_kelas as ak')
                    ->where('ak.tenant_id', '!=', '00000000-0000-0000-0000-000000000000')
                    ->value('ak.tenant_id');

                if (!$tenantWithStudents) {
                    $tenantWithStudents = Siswa::withoutTenant()
                        ->where('tenant_id', '!=', '00000000-0000-0000-0000-000000000000')
                        ->where(function ($q) {
                            $q->where('kelas_saat_ini', 'ILIKE', '%XII%')
                              ->orWhere('kelas_saat_ini', 'ILIKE', '%12%');
                        })
                        ->value('tenant_id');
                }

                $tenantId = $tenantWithStudents ?: ($tenants->first()?->id ?? null);
            }
        } else {
            $tenantId = $user->tenant_id ?? session('tenant_id');
        }

        // Filter parameters
        $jurusanList = Jurusan::where('is_active', true)->get(['id', 'nama_jurusan']);
        if ($jurusanList->isEmpty()) {
            $jurusanList = collect([
                (object)['id' => 'mipa', 'nama_jurusan' => 'MIPA / IPA'],
                (object)['id' => 'ips', 'nama_jurusan' => 'IPS / Sosial'],
                (object)['id' => 'bahasa', 'nama_jurusan' => 'Bahasa & Budaya'],
                (object)['id' => 'vokasi', 'nama_jurusan' => 'Kejuruan / Vokasi'],
            ]);
        }

        // Tahun Ajaran List
        $tahunAjaranList = TahunAjaran::withoutTenant()
            ->orderBy('nama_tahun_ajaran', 'desc')
            ->get(['id', 'nama_tahun_ajaran', 'is_active']);

        if ($tahunAjaranList->isEmpty()) {
            $tahunAjaranList = collect([
                (object)['id' => 'ta1', 'nama_tahun_ajaran' => '2026/2027', 'is_active' => true],
                (object)['id' => 'ta2', 'nama_tahun_ajaran' => '2025/2026', 'is_active' => false],
                (object)['id' => 'ta3', 'nama_tahun_ajaran' => '2024/2025', 'is_active' => false],
                (object)['id' => 'ta4', 'nama_tahun_ajaran' => '2023/2024', 'is_active' => false],
            ]);
        }

        $defaultTahunAjaran = $tahunAjaranList->firstWhere('is_active', true)?->nama_tahun_ajaran ?? '2026/2027';
        $tahunAjaran = $request->input('tahun_ajaran', $defaultTahunAjaran);
        $taModel = $this->resolveTahunAjaran($tahunAjaran);
        $tahunAjaranId = $taModel?->id;
        $namaTahunAjaran = $taModel?->nama_tahun_ajaran ?? $tahunAjaran;

        $jurusan = $request->input('jurusan', '');
        $angkatan = $request->input('angkatan', '');
        $search = trim($request->input('search', ''));
        $statusFilter = $request->input('status_eligible', '');
        $noSimulasi = (int)$request->input('no_simulasi', 1);
        if (!in_array($noSimulasi, [1, 2, 3])) {
            $noSimulasi = 1;
        }

        // 1. Hitung Ranking SNBP & Eligible Data berdasarkan Riwayat Kelas pada Tahun Ajaran tersebut
        $rankingData = $rankingService->hitungRankingSnbp($tenantId, $jurusan, $angkatan, $isSuperAdmin, $namaTahunAjaran, $noSimulasi);

        // Filter search & status pada ranking_list jika ada
        if (!empty($search)) {
            $rankingData['ranking_list'] = array_values(array_filter($rankingData['ranking_list'], function ($item) use ($search) {
                return (stripos($item['nama_lengkap'], $search) !== false) ||
                       (stripos($item['nisn'], $search) !== false) ||
                       (stripos($item['nis'], $search) !== false);
            }));
        }

        if (!empty($statusFilter)) {
            $rankingData['ranking_list'] = array_values(array_filter($rankingData['ranking_list'], function ($item) use ($statusFilter) {
                if ($statusFilter === 'eligible') return $item['is_eligible'] === true;
                if ($statusFilter === 'not_eligible') return $item['is_eligible'] === false && !$item['status_pengunduran_diri'];
                if ($statusFilter === 'resigned') return $item['status_pengunduran_diri'] === true;
                if ($statusFilter === 'promoted') return !empty($item['is_cadangan_promosi']);
                return true;
            }));
        }

        // 2. Deteksi Tabrakan Pilihan (Collision Matrix) pada nomor simulasi & tahun ajaran ini
        $collisions = $rankingService->deteksiTabrakanPilihan($tenantId, $isSuperAdmin, $noSimulasi, $tahunAjaranId);

        // 3. Status 5-Step Workflow Lock per tahun ajaran
        $workflowStatus = $rankingService->getWorkflowStatus($tenantId, $isSuperAdmin, $tahunAjaranId);

        // 4. Master Kampus & Prodi SNPMB Ringkasan
        $masterKampusQuery = MasterKampus::query();
        if ($request->filled('search_kampus')) {
            $sk = trim($request->input('search_kampus'));
            $masterKampusQuery->where('nama_kampus', 'ILIKE', "%{$sk}%")
                              ->orWhere('kota', 'ILIKE', "%{$sk}%")
                              ->orWhere('provinsi', 'ILIKE', "%{$sk}%");
        }
        $masterKampusList = $masterKampusQuery->withCount('prodi')
            ->orderBy('nama_kampus', 'asc')
            ->paginate(15, ['*'], 'page_kampus');

        // 5. Daftar Pengunduran Diri (Langkah 3) per tahun ajaran
        $pengunduranDiriQuery = PengunduranDiri::withoutTenant();
        if ($tenantId) {
            $pengunduranDiriQuery->where('tenant_id', $tenantId);
        }
        if ($tahunAjaranId) {
            $pengunduranDiriQuery->where(function($q) use ($tahunAjaranId) {
                $q->where('tahun_ajaran_id', $tahunAjaranId)
                  ->orWhereNull('tahun_ajaran_id');
            });
        }
        $pengunduranDiriList = $pengunduranDiriQuery->with('siswa')
            ->orderBy('created_at', 'desc')
            ->get();

        // 6. Daftar Mapel Kurikulum & Config PDSS (Langkah 1) terisolasi per tahun ajaran
        $jurusanMapel = $request->input('jurusan_mapel', '');
        $kelasMapel = $request->input('kelas_mapel', '');

        // Auto-seed template mapel kurikulum nasional jika sekolah belum memiliki data mapel
        if ($tenantId && DB::table('akademik.mata_pelajaran')->where('tenant_id', $tenantId)->count() === 0) {
            $templateMapels = DB::table('akademik.mata_pelajaran')->whereNotNull('nama_mata_pelajaran')->limit(14)->get();
            if ($templateMapels->isNotEmpty()) {
                foreach ($templateMapels as $tm) {
                    DB::table('akademik.mata_pelajaran')->insert([
                        'id'                  => (string)\Illuminate\Support\Str::uuid(),
                        'tenant_id'           => $tenantId,
                        'nama_mata_pelajaran' => $tm->nama_mata_pelajaran,
                        'kategori'            => $tm->kategori ?? 'Muatan Nasional / Wajib',
                        'deskripsi'           => $tm->deskripsi ?? 'Mata Pelajaran Kurikulum Standar Nasional',
                        'is_active'           => true,
                        'created_at'          => now(),
                        'updated_at'          => now(),
                    ]);
                }
            }
        }

        $mapelsQuery = DB::table('akademik.mata_pelajaran');
        if ($tenantId) {
            $mapelsQuery->where('akademik.mata_pelajaran.tenant_id', $tenantId);
        }

        if (!empty($jurusanMapel) && $jurusanMapel !== 'all') {
            if ($jurusanMapel === 'MIPA') {
                $mapelsQuery->where(function($q) {
                    $q->where('akademik.mata_pelajaran.kategori', 'ILIKE', '%Wajib%')
                      ->orWhere('akademik.mata_pelajaran.kategori', 'ILIKE', '%Nasional%')
                      ->orWhere('akademik.mata_pelajaran.kategori', 'ILIKE', '%MIPA%');
                });
            } elseif ($jurusanMapel === 'IPS') {
                $mapelsQuery->where(function($q) {
                    $q->where('akademik.mata_pelajaran.kategori', 'ILIKE', '%Wajib%')
                      ->orWhere('akademik.mata_pelajaran.kategori', 'ILIKE', '%Nasional%')
                      ->orWhere('akademik.mata_pelajaran.kategori', 'ILIKE', '%IPS%');
                });
            }
        }

        $pdssMapels = $mapelsQuery->leftJoin('pdss.pdss_config_mapel', function ($join) use ($tenantId, $tahunAjaranId) {
            $join->on('akademik.mata_pelajaran.id', '=', 'pdss.pdss_config_mapel.mapel_id');
            if ($tenantId) {
                $join->where('pdss.pdss_config_mapel.tenant_id', '=', $tenantId);
            }
            if ($tahunAjaranId) {
                $join->where('pdss.pdss_config_mapel.tahun_ajaran_id', '=', $tahunAjaranId);
            } else {
                $join->whereNull('pdss.pdss_config_mapel.tahun_ajaran_id');
            }
        })
        ->select(
            'akademik.mata_pelajaran.id',
            'akademik.mata_pelajaran.nama_mata_pelajaran',
            'akademik.mata_pelajaran.kategori',
            'akademik.mata_pelajaran.deskripsi',
            DB::raw('COALESCE(pdss.pdss_config_mapel.sem_1, true) as sem_1'),
            DB::raw('COALESCE(pdss.pdss_config_mapel.sem_2, true) as sem_2'),
            DB::raw('COALESCE(pdss.pdss_config_mapel.sem_3, true) as sem_3'),
            DB::raw('COALESCE(pdss.pdss_config_mapel.sem_4, true) as sem_4'),
            DB::raw('COALESCE(pdss.pdss_config_mapel.sem_5, true) as sem_5'),
            DB::raw('COALESCE(pdss.pdss_config_mapel.sem_6, false) as sem_6')
        )
        ->distinct('akademik.mata_pelajaran.nama_mata_pelajaran')
        ->orderBy('akademik.mata_pelajaran.nama_mata_pelajaran', 'asc')
        ->get();

        // 7. Daftar Kelas Rombel untuk Filter
        $kelasList = DB::table('akademik.kelas')
            ->when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))
            ->select('id', 'nama_kelas')
            ->orderBy('nama_kelas', 'asc')
            ->get();

        // 8. Ringkasan Status 3 Simulasi (Langkah 4: Simulasi 1, 2, 3 Permanen) per tahun ajaran
        $simulasiQuery = PilihanKampus::withoutTenant();
        if ($tenantId) {
            $simulasiQuery->where('tenant_id', $tenantId);
        }
        if ($tahunAjaranId) {
            $simulasiQuery->where(function($q) use ($tahunAjaranId) {
                $q->where('tahun_ajaran_id', $tahunAjaranId)
                  ->orWhereNull('tahun_ajaran_id');
            });
        }
        $sim1Count = (clone $simulasiQuery)->where('no_simulasi', 1)->distinct('siswa_id')->count('siswa_id');
        $sim2Count = (clone $simulasiQuery)->where('no_simulasi', 2)->distinct('siswa_id')->count('siswa_id');
        $sim3Count = (clone $simulasiQuery)->where('no_simulasi', 3)->distinct('siswa_id')->count('siswa_id');
        $isSim3Permanen = (clone $simulasiQuery)->where('no_simulasi', 3)->where('status', 'Permanen')->exists();

        $simulasiStats = [
            1 => [
                'no'              => 1,
                'nama'            => 'Simulasi 1: Draf Awal Pilihan Siswa',
                'deskripsi'       => 'Pengisian draf pilihan kampus & deteksi tabrakan pertama',
                'total_siswa_isi' => $sim1Count,
                'status_label'    => 'Draf Awal',
                'is_active'       => ($noSimulasi === 1),
            ],
            2 => [
                'no'              => 2,
                'nama'            => 'Simulasi 2: Rasionalisasi Jurusan & Evaluasi BK',
                'deskripsi'       => 'Perbaikan pilihan yang tabrakan setelah bimbingan konseling',
                'total_siswa_isi' => $sim2Count,
                'status_label'    => 'Rasionalisasi',
                'is_active'       => ($noSimulasi === 2),
            ],
            3 => [
                'no'              => 3,
                'nama'            => 'Simulasi 3: Penetapan Pilihan Final & Permanen',
                'deskripsi'       => 'Pilihan final yang dikunci permanen siap diunggah ke Portal SNPMB PDSS',
                'total_siswa_isi' => $sim3Count,
                'status_label'    => $isSim3Permanen ? 'Terkunci Permanen' : 'Siap Finalisasi',
                'is_permanen'     => $isSim3Permanen,
                'is_active'       => ($noSimulasi === 3),
            ],
        ];

        // 9. Tenant info & stats
        $tenantObj = Tenant::find($tenantId);
        $tenantInfo = [
            'id'                 => $tenantId,
            'nama_sekolah'       => $tenantObj?->nama_sekolah ?? 'SMA / SMK Negeri SINTA',
            'npsn'               => $tenantObj?->npsn ?? '20101010',
            'akreditasi'         => $rankingData['akreditasi_sekolah'] ?? 'A (Unggul)',
            'kuota_persen'       => $rankingData['kuota_persen'] ?? 40,
            'total_siswa'        => $rankingData['total_siswa'] ?? 0,
            'kuota_eligible'     => $rankingData['kuota_eligible'] ?? 0,
            'active_eligible'    => $rankingData['active_eligible'] ?? 0,
            'total_pengunduran'  => $rankingData['total_pengunduran'] ?? 0,
            'promoted_count'     => $rankingData['promoted_count'] ?? 0,
            'tahun_ajaran'       => $namaTahunAjaran,
            'tahun_ajaran_id'    => $tahunAjaranId,
        ];

        $responseData = [
            'rankingData'         => $rankingData,
            'jurusanList'         => $jurusanList,
            'kelasList'           => $kelasList,
            'tahunAjaranList'     => $tahunAjaranList,
            'pdssMapels'          => $pdssMapels,
            'collisions'          => $collisions,
            'workflowStatus'      => $workflowStatus,
            'simulasiStats'       => $simulasiStats,
            'masterKampusList'    => $masterKampusList,
            'pengunduranDiriList' => $pengunduranDiriList,
            'tenantInfo'          => $tenantInfo,
            'isSuperAdmin'        => $isSuperAdmin,
            'tenants'             => $tenants,
            'filters'             => [
                'jurusan'         => $jurusan,
                'tahun_ajaran'    => $namaTahunAjaran,
                'tahun_ajaran_id' => $tahunAjaranId,
                'angkatan'        => $angkatan,
                'status_eligible' => $statusFilter,
                'search'          => $search,
                'tenant_id'       => $selectedTenantId ?? $tenantId ?? '',
                'no_simulasi'     => $noSimulasi,
                'jurusan_mapel'   => $jurusanMapel,
                'kelas_mapel'     => $kelasMapel,
            ],
        ];

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data'    => $responseData,
            ]);
        }

        return Inertia::render('Pdss/Index', $responseData);
    }

    /**
     * Simpan / Perbarui Pilihan Kampus & Program Studi Siswa pada Simulasi Tertentu
     */
    public function simpanPilihan(Request $request): RedirectResponse|JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'siswa_id'        => 'required|string',
            'no_pilihan'      => 'required|integer|in:1,2',
            'kampus_id'       => 'required|string',
            'prodi_id'        => 'required|string',
            'no_simulasi'     => 'nullable|integer|in:1,2,3',
            'tahun_ajaran'    => 'nullable|string',
            'tahun_ajaran_id' => 'nullable|string',
            'status'          => 'nullable|string',
        ]);

        if ($validator->fails()) {
            $msg = $validator->errors()->first() ?: 'Data pilihan prodi tidak valid.';
            return $this->redirectTarget($request, 'error', $msg);
        }

        $validated = $validator->validated();

        $user = $request->user() ?: Auth::user();
        $tenantId = $this->resolveActiveTenantId($request, $user, $validated['siswa_id']);
        $noSimulasi = $validated['no_simulasi'] ?? 1;

        $taModel = $this->resolveTahunAjaran($validated['tahun_ajaran'] ?? null);
        $tahunAjaranId = $validated['tahun_ajaran_id'] ?? ($taModel?->id);

        $pilihan = PilihanKampus::withoutTenant()->updateOrCreate(
            [
                'tenant_id'   => $tenantId,
                'siswa_id'    => $validated['siswa_id'],
                'no_pilihan'  => $validated['no_pilihan'],
                'no_simulasi' => $noSimulasi,
            ],
            [
                'tahun_ajaran_id' => $tahunAjaranId,
                'kampus_id'       => $validated['kampus_id'],
                'prodi_id'        => $validated['prodi_id'],
                'status'          => $validated['status'] ?? ($noSimulasi === 3 ? 'Final' : ($noSimulasi === 2 ? 'Rasionalisasi' : 'Draft')),
            ]
        );

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => "Pilihan jurusan Simulasi {$noSimulasi} berhasil disimpan.", 'data' => $pilihan]);
        }

        return $this->redirectTarget($request, 'success', "Pilihan jurusan Simulasi {$noSimulasi} berhasil disimpan.", $tenantId, $validated['tahun_ajaran'] ?? null);
    }

    /**
     * Salin Data Pilihan dari Satu Simulasi ke Simulasi Berikutnya
     */
    public function salinSimulasi(Request $request): RedirectResponse|JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'from_simulasi'   => 'required|integer|in:1,2',
            'to_simulasi'     => 'required|integer|in:2,3',
            'tahun_ajaran'    => 'nullable|string',
            'tahun_ajaran_id' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            $msg = $validator->errors()->first() ?: 'Parameter salin simulasi tidak valid.';
            return $this->redirectTarget($request, 'error', $msg);
        }

        $validated = $validator->validated();

        $user = $request->user() ?: Auth::user();
        $tenantId = $this->resolveActiveTenantId($request, $user);

        $taModel = $this->resolveTahunAjaran($validated['tahun_ajaran'] ?? null);
        $tahunAjaranId = $validated['tahun_ajaran_id'] ?? ($taModel?->id);

        $sourceQuery = PilihanKampus::withoutTenant()->where('tenant_id', $tenantId)
            ->where('no_simulasi', $validated['from_simulasi']);

        if ($tahunAjaranId) {
            $sourceQuery->where(function($q) use ($tahunAjaranId) {
                $q->where('tahun_ajaran_id', $tahunAjaranId)
                  ->orWhereNull('tahun_ajaran_id');
            });
        }

        $sourcePilihans = $sourceQuery->get();

        if ($sourcePilihans->isEmpty()) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => "Belum ada data pilihan di Simulasi {$validated['from_simulasi']} untuk disalin."], 422);
            }
            return $this->redirectTarget($request, 'error', "Belum ada data pilihan di Simulasi {$validated['from_simulasi']} untuk disalin.", $tenantId, $validated['tahun_ajaran'] ?? null);
        }

        $copiedCount = 0;
        foreach ($sourcePilihans as $sp) {
            PilihanKampus::withoutTenant()->updateOrCreate(
                [
                    'tenant_id'   => $tenantId,
                    'siswa_id'    => $sp->siswa_id,
                    'no_pilihan'  => $sp->no_pilihan,
                    'no_simulasi' => $validated['to_simulasi'],
                ],
                [
                    'tahun_ajaran_id' => $tahunAjaranId,
                    'kampus_id'       => $sp->kampus_id,
                    'prodi_id'        => $sp->prodi_id,
                    'status'          => ($validated['to_simulasi'] === 3) ? 'Rasionalisasi Final' : 'Rasionalisasi',
                ]
            );
            $copiedCount++;
        }

        $msg = "Berhasil menyalin {$copiedCount} pilihan dari Simulasi {$validated['from_simulasi']} ke Simulasi {$validated['to_simulasi']}.";
        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => $msg]);
        }

        return $this->redirectTarget($request, 'success', $msg, $tenantId, $validated['tahun_ajaran'] ?? null);
    }

    /**
     * Kunci Simulasi 3 Sebagai Pilihan Permanen Final SNBP
     */
    public function kunciPermanenSimulasi(Request $request): RedirectResponse|JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'tahun_ajaran'    => 'nullable|string',
            'tahun_ajaran_id' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            $msg = $validator->errors()->first() ?: 'Parameter kunci permanen tidak valid.';
            return $this->redirectTarget($request, 'error', $msg);
        }

        $validated = $validator->validated();

        $user = $request->user() ?: Auth::user();
        $tenantId = $this->resolveActiveTenantId($request, $user);

        $taModel = $this->resolveTahunAjaran($validated['tahun_ajaran'] ?? null);
        $tahunAjaranId = $validated['tahun_ajaran_id'] ?? ($taModel?->id);

        // Set status pilihan di Simulasi 3 menjadi Permanen
        $updateQuery = PilihanKampus::withoutTenant()->where('tenant_id', $tenantId)
            ->where('no_simulasi', 3);
        if ($tahunAjaranId) {
            $updateQuery->where(function($q) use ($tahunAjaranId) {
                $q->where('tahun_ajaran_id', $tahunAjaranId)
                  ->orWhereNull('tahun_ajaran_id');
            });
        }
        $affected = $updateQuery->update(['status' => 'Permanen']);

        // Kunci workflow step 4
        $lockMatch = ['tenant_id' => $tenantId, 'step' => 4];
        if ($tahunAjaranId) {
            $lockMatch['tahun_ajaran_id'] = $tahunAjaranId;
        }

        PdssLock::withoutTenant()->updateOrCreate(
            $lockMatch,
            [
                'tahun_ajaran_id' => $tahunAjaranId,
                'is_locked'       => true,
                'locked_by'       => $user->nama_lengkap ?? $user->username ?? 'Guru BK / Admin',
                'locked_at'       => now(),
            ]
        );

        $msg = "Simulasi 3 telah berhasil dikunci secara Permanen ({$affected} data pilihan terkunci). Data siap diproses ke Portal SNPMB PDSS.";
        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => $msg]);
        }

        return $this->redirectTarget($request, 'success', $msg, $tenantId, $validated['tahun_ajaran'] ?? null);
    }

    /**
     * Simpan Konfigurasi Pilihan Mapel PDSS Per Semester (Langkah 1)
     */
    public function simpanConfigMapel(Request $request): RedirectResponse|JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'mapels'          => 'required|array|min:1',
            'mapels.*.id'     => 'required|string',
            'mapels.*.sem_1'  => 'nullable',
            'mapels.*.sem_2'  => 'nullable',
            'mapels.*.sem_3'  => 'nullable',
            'mapels.*.sem_4'  => 'nullable',
            'mapels.*.sem_5'  => 'nullable',
            'mapels.*.sem_6'  => 'nullable',
            'tahun_ajaran'    => 'nullable|string',
            'tahun_ajaran_id' => 'nullable|string',
            'tenant_id'       => 'nullable|string',
        ], [
            'mapels.required' => 'Daftar mata pelajaran belum dipilih atau masih kosong.',
            'mapels.min'      => 'Pilih minimal 1 mata pelajaran untuk disimpan.',
        ]);

        if ($validator->fails()) {
            $msg = $validator->errors()->first() ?: 'Daftar pilihan mata pelajaran tidak boleh kosong.';
            return $this->redirectTarget($request, 'error', $msg);
        }

        $validated = $validator->validated();

        $user = $request->user() ?: Auth::user();
        $tenantId = $this->resolveActiveTenantId($request, $user);

        $taModel = $this->resolveTahunAjaran($validated['tahun_ajaran'] ?? null);
        $tahunAjaranId = $validated['tahun_ajaran_id'] ?? ($taModel?->id);

        $toBool = function ($val, bool $default = true): bool {
            if (is_null($val)) return $default;
            return filter_var($val, FILTER_VALIDATE_BOOLEAN);
        };

        foreach ($validated['mapels'] as $m) {
            $matchCriteria = [
                'tenant_id' => $tenantId,
                'mapel_id'  => $m['id'],
            ];
            if ($tahunAjaranId) {
                $matchCriteria['tahun_ajaran_id'] = $tahunAjaranId;
            }

            \Modules\Pdss\Entities\PdssConfigMapel::withoutTenant()->updateOrCreate(
                $matchCriteria,
                [
                    'tahun_ajaran_id' => $tahunAjaranId,
                    'sem_1'           => $toBool($m['sem_1'] ?? null, true),
                    'sem_2'           => $toBool($m['sem_2'] ?? null, true),
                    'sem_3'           => $toBool($m['sem_3'] ?? null, true),
                    'sem_4'           => $toBool($m['sem_4'] ?? null, true),
                    'sem_5'           => $toBool($m['sem_5'] ?? null, true),
                    'sem_6'           => $toBool($m['sem_6'] ?? null, false),
                    'is_active'       => true,
                ]
            );
        }

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Konfigurasi mata pelajaran PDSS berhasil disimpan.']);
        }

        return $this->redirectTarget($request, 'success', 'Konfigurasi mata pelajaran PDSS berhasil disimpan.', $tenantId, $validated['tahun_ajaran'] ?? null);
    }

    /**
     * Deteksi Otomatis Mapel PDSS dari Riwayat Rapor Siswa Riil (Auto-Detect from Grades)
     */
    public function autoDetectMapelFromRapor(Request $request): JsonResponse
    {
        $user = $request->user() ?: Auth::user();
        $tenantId = $this->resolveActiveTenantId($request, $user);

        $kelasId = $request->input('kelas_id');
        $jurusan = $request->input('jurusan');
        $tahunAjaran = $request->input('tahun_ajaran');
        $taModel = $this->resolveTahunAjaran($tahunAjaran);
        $tahunAjaranId = $taModel?->id;

        // 1. Scan detail_nilai_rapor untuk tenant yang bersangkutan
        $query = DB::table('akademik.detail_nilai_rapor')->where('tenant_id', $tenantId);
        if ($kelasId && $kelasId !== 'all') {
            $query->where('kelas_id', $kelasId);
        }
        if ($tahunAjaran) {
            $query->where('tahun_ajaran', $tahunAjaran);
        }

        $rawGrades = $query->select('mapel_id', 'semester', 'tahun_ajaran', DB::raw('count(*) as count_nilai'))
            ->groupBy('mapel_id', 'semester', 'tahun_ajaran')
            ->get();

        $allMapels = DB::table('akademik.mata_pelajaran')->where('tenant_id', $tenantId)->get();

        $updatedMapels = [];
        $totalDetected = 0;

        foreach ($allMapels as $mapel) {
            $mapelGrades = $rawGrades->where('mapel_id', (string)$mapel->id);

            $hasSem1 = $mapelGrades->contains(fn($r) => in_array($r->semester, ['1', 'Ganjil', 'sem_1']));
            $hasSem2 = $mapelGrades->contains(fn($r) => in_array($r->semester, ['2', 'Genap', 'sem_2']));
            $hasSem3 = $mapelGrades->contains(fn($r) => in_array($r->semester, ['3', 'Ganjil', 'sem_3']));
            $hasSem4 = $mapelGrades->contains(fn($r) => in_array($r->semester, ['4', 'Genap', 'sem_4']));
            $hasSem5 = $mapelGrades->contains(fn($r) => in_array($r->semester, ['5', 'Ganjil', 'sem_5']));
            $hasSem6 = $mapelGrades->contains(fn($r) => in_array($r->semester, ['6', 'Genap', 'sem_6']));

            // If subject is National/Wajib or detected in grades
            $isWajib = stripos($mapel->kategori ?? '', 'Wajib') !== false || 
                       stripos($mapel->nama_mata_pelajaran, 'Wajib') !== false || 
                       stripos($mapel->kategori ?? '', 'Nasional') !== false;

            $sem1 = $hasSem1 || $isWajib;
            $sem2 = $hasSem2 || $isWajib;
            $sem3 = $hasSem3 || $isWajib;
            $sem4 = $hasSem4 || $isWajib;
            $sem5 = $hasSem5 || $isWajib;
            $sem6 = $hasSem6;

            $matchCriteria = [
                'tenant_id' => $tenantId,
                'mapel_id'  => $mapel->id,
            ];
            if ($tahunAjaranId) {
                $matchCriteria['tahun_ajaran_id'] = $tahunAjaranId;
            }

            \Modules\Pdss\Entities\PdssConfigMapel::withoutTenant()->updateOrCreate(
                $matchCriteria,
                [
                    'tahun_ajaran_id' => $tahunAjaranId,
                    'sem_1'           => $sem1,
                    'sem_2'           => $sem2,
                    'sem_3'           => $sem3,
                    'sem_4'           => $sem4,
                    'sem_5'           => $sem5,
                    'sem_6'           => $sem6,
                    'is_active'       => true,
                ]
            );

            $updatedMapels[] = [
                'id' => $mapel->id,
                'nama_mata_pelajaran' => $mapel->nama_mata_pelajaran,
                'kategori' => $mapel->kategori,
                'deskripsi' => $mapel->deskripsi,
                'sem_1' => $sem1,
                'sem_2' => $sem2,
                'sem_3' => $sem3,
                'sem_4' => $sem4,
                'sem_5' => $sem5,
                'sem_6' => $sem6,
            ];

            if ($sem1 || $sem2 || $sem3 || $sem4 || $sem5) {
                $totalDetected++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Berhasil mendeteksi {$totalDetected} mata pelajaran dari rapor siswa untuk T.A. " . ($taModel?->nama_tahun_ajaran ?? 'aktif') . ".",
            'data' => [
                'total_detected' => $totalDetected,
                'total_scanned_grades' => $rawGrades->sum('count_nilai'),
                'mapels' => $updatedMapels,
            ]
        ]);
    }

    /**
     * Live Autocomplete Master Prodi PTN
     */
    public function searchProdi(Request $request): JsonResponse
    {
        $keyword = trim($request->input('q', ''));
        $jenjang = $request->input('jenjang', '');

        $query = MasterKampusProdi::with('kampus')
            ->where(function ($q) use ($keyword) {
                $q->where('nama_prodi', 'ILIKE', "%{$keyword}%")
                  ->orWhere('program_studi', 'ILIKE', "%{$keyword}%")
                  ->orWhereHas('kampus', function ($kq) use ($keyword) {
                      $kq->where('nama_kampus', 'ILIKE', "%{$keyword}%");
                  });
            });

        if ($jenjang && $jenjang !== 'all') {
            $query->where('jenjang', $jenjang);
        }

        $results = $query->limit(25)->get()->map(function ($item) {
            return [
                'id'            => $item->id,
                'prodi_id'      => $item->id,
                'kampus_id'     => $item->kampus_id,
                'nama_prodi'    => $item->nama_prodi ?: $item->program_studi,
                'nama_kampus'   => $item->kampus?->nama_kampus ?? 'Universitas Negeri',
                'jenjang'       => $item->jenjang ?: 'S1',
                'daya_tampung'  => $item->daya_tampung_sekarang ?? 50,
                'peminat_tahun_lalu' => $item->peminat_tahun_lalu ?? 0,
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => $results,
        ]);
    }

    /**
     * Override Kelayakan Siswa (Eligible / Non-Eligible oleh Guru BK)
     */
    public function overrideEligible(Request $request): RedirectResponse|JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'siswa_id'        => 'required|string',
            'is_eligible'     => 'required|boolean',
            'catatan'         => 'nullable|string|max:500',
            'tahun_ajaran'    => 'nullable|string',
            'tahun_ajaran_id' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            $msg = $validator->errors()->first() ?: 'Data override eligibilitas tidak valid.';
            return $this->redirectTarget($request, 'error', $msg);
        }

        $validated = $validator->validated();

        $user = $request->user() ?: Auth::user();
        $tenantId = $this->resolveActiveTenantId($request, $user, $validated['siswa_id']);

        $taModel = $this->resolveTahunAjaran($validated['tahun_ajaran'] ?? null);
        $tahunAjaranId = $validated['tahun_ajaran_id'] ?? ($taModel?->id);

        $kesiapan = KesiapanSiswa::withoutTenant()->updateOrCreate(
            [
                'tenant_id' => $tenantId,
                'siswa_id'  => $validated['siswa_id'],
            ],
            [
                'tahun_ajaran_id'   => $tahunAjaranId,
                'is_eligible_final' => (bool)$validated['is_eligible'],
                'catatan_override'  => $validated['catatan'] ?? 'Diubah oleh ' . ($user->nama_lengkap ?? 'Guru BK'),
            ]
        );

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Status eligibilitas siswa berhasil diperbarui.', 'data' => $kesiapan]);
        }

        return $this->redirectTarget($request, 'success', 'Status eligibilitas siswa berhasil diperbarui.', $tenantId, $validated['tahun_ajaran'] ?? null);
    }

    /**
     * Catat Pengunduran Diri Siswa Eligible & Otomatis Naikkan Siswa Pengganti (Langkah 3)
     */
    public function simpanPengunduranDiri(Request $request): RedirectResponse|JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'siswa_id'        => 'required|string',
            'nomor_surat'     => 'required|string|max:100',
            'tanggal_surat'   => 'required|date',
            'alasan'          => 'required|string',
            'berkas'          => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'tahun_ajaran'    => 'nullable|string',
            'tahun_ajaran_id' => 'nullable|string',
            'tenant_id'       => 'nullable|string',
        ]);

        if ($validator->fails()) {
            $msg = $validator->errors()->first() ?: 'Data pengunduran diri tidak lengkap atau berkas tidak valid.';
            return $this->redirectTarget($request, 'error', $msg);
        }

        $validated = $validator->validated();

        $user = $request->user() ?: Auth::user();
        $tenantId = $this->resolveActiveTenantId($request, $user, $validated['siswa_id']);

        $taModel = $this->resolveTahunAjaran($validated['tahun_ajaran'] ?? null);
        $tahunAjaranId = $validated['tahun_ajaran_id'] ?? ($taModel?->id);

        $path = null;
        $fileName = null;
        $fileSize = null;
        $mime = null;

        if ($request->hasFile('berkas')) {
            $file = $request->file('berkas');
            $fileName = $file->getClientOriginalName();
            $fileSize = $file->getSize();
            $mime = $file->getClientMimeType();
            $path = $file->store('pdss/pengunduran_diri', 'public');
        }

        $pengunduran = PengunduranDiri::withoutTenant()->create([
            'tenant_id'         => $tenantId,
            'siswa_id'          => $validated['siswa_id'],
            'tahun_ajaran_id'   => $tahunAjaranId,
            'nomor_surat'       => $validated['nomor_surat'],
            'tanggal_surat'     => $validated['tanggal_surat'],
            'alasan'            => $validated['alasan'],
            'nama_file'         => $fileName,
            'path_file'         => $path,
            'ukuran_file'       => $fileSize,
            'mime_type'         => $mime,
            'status_verifikasi' => 'Terverifikasi',
            'created_by'        => $user->id ?? null,
        ]);

        // Tandai siswa mengundurkan diri
        KesiapanSiswa::withoutTenant()->updateOrCreate(
            [
                'tenant_id' => $tenantId,
                'siswa_id'  => $validated['siswa_id'],
            ],
            [
                'tahun_ajaran_id'         => $tahunAjaranId,
                'status_pengunduran_diri' => true,
                'pengunduran_diri_id'     => $pengunduran->id,
                'is_eligible_final'       => false,
                'catatan_override'        => 'Mengundurkan Diri (No: ' . $validated['nomor_surat'] . '). Kuota dialihkan ke siswa cadangan.',
            ]
        );

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Pengunduran diri berhasil dicatat. Kuota eligible otomatis dialihkan ke siswa peringkat cadangan berikutnya.',
                'data'    => $pengunduran,
            ]);
        }

        return $this->redirectTarget($request, 'success', 'Pengunduran diri berhasil dicatat. Kuota eligible otomatis dialihkan ke siswa peringkat cadangan berikutnya.', $tenantId, $validated['tahun_ajaran'] ?? null);
    }

    /**
     * Kunci / Buka Status Tahapan Workflow PDSS
     */
    public function lockStep(Request $request): RedirectResponse|JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'step'            => 'required|integer|min:1|max:5',
            'is_locked'       => 'required|boolean',
            'tahun_ajaran'    => 'nullable|string',
            'tahun_ajaran_id' => 'nullable|string',
            'tenant_id'       => 'nullable|string',
        ]);

        if ($validator->fails()) {
            $msg = $validator->errors()->first() ?: 'Parameter gembok alur tidak valid.';
            return $this->redirectTarget($request, 'error', $msg);
        }

        $validated = $validator->validated();

        $user = $request->user() ?: Auth::user();
        $tenantId = $this->resolveActiveTenantId($request, $user);

        $taModel = $this->resolveTahunAjaran($validated['tahun_ajaran'] ?? null);
        $tahunAjaranId = $validated['tahun_ajaran_id'] ?? ($taModel?->id);

        $lockMatch = [
            'tenant_id' => $tenantId,
            'step'      => $validated['step'],
        ];
        if ($tahunAjaranId) {
            $lockMatch['tahun_ajaran_id'] = $tahunAjaranId;
        }

        $lock = PdssLock::withoutTenant()->updateOrCreate(
            $lockMatch,
            [
                'tahun_ajaran_id' => $tahunAjaranId,
                'is_locked'       => $validated['is_locked'],
                'locked_by'       => $validated['is_locked'] ? ($user->nama_lengkap ?? $user->username ?? 'Guru BK / Admin') : null,
                'locked_at'       => $validated['is_locked'] ? now() : null,
            ]
        );

        $actionText = $validated['is_locked'] ? 'dikunci' : 'dibuka kembali';
        $message = "Tahap {$validated['step']} berhasil {$actionText} untuk T.A. " . ($taModel?->nama_tahun_ajaran ?? 'aktif') . ".";

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => $message, 'data' => $lock]);
        }

        return $this->redirectTarget($request, 'success', $message, $tenantId, $validated['tahun_ajaran'] ?? null);
    }
}
