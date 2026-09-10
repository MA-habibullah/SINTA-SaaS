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
use Modules\Pdss\Entities\KampusProdiRiwayat;
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

        if ($request->filled('tenant_id')) {
            $tenantId = ($selectedTenantId === 'all') ? null : $selectedTenantId;
        } elseif ($isSuperAdmin) {
            // Hilangkan "Pusat Kendali SaaS (Global)" dari dropdown pilihan sekolah
            $tenants = Tenant::select('id', 'nama_sekolah', 'npsn')
                ->where('npsn', '!=', 'PLATFORM')
                ->where('id', '!=', '00000000-0000-0000-0000-000000000000')
                ->where('nama_sekolah', 'NOT ILIKE', '%Pusat Kendali%')
                ->orderBy('nama_sekolah', 'asc')
                ->get();

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
        } else {
            $tenantId = $user->tenant_id ?? session('tenant_id');
        }

        if ($isSuperAdmin && empty($tenants)) {
            $tenants = Tenant::select('id', 'nama_sekolah', 'npsn')
                ->where('npsn', '!=', 'PLATFORM')
                ->where('id', '!=', '00000000-0000-0000-0000-000000000000')
                ->where('nama_sekolah', 'NOT ILIKE', '%Pusat Kendali%')
                ->orderBy('nama_sekolah', 'asc')
                ->get();
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
        $kuotaPersen = $request->input('kuota_persen');
        $useERapor = $request->boolean('use_erapor');

        // 1. Hitung Ranking SNBP & Eligible Data berdasarkan Riwayat Kelas pada Tahun Ajaran tersebut
        $rankingData = $rankingService->hitungRankingSnbp(
            $tenantId, 
            $jurusan, 
            $angkatan, 
            $isSuperAdmin, 
            $namaTahunAjaran, 
            $noSimulasi,
            $kuotaPersen ? (int)$kuotaPersen : null,
            $useERapor
        );

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

        // 4. Master Kampus & Prodi SNPMB Ringkasan (Katalog Global)
        $masterKampusQuery = MasterKampus::withoutTenant();
        if ($request->filled('search_kampus')) {
            $sk = trim($request->input('search_kampus'));
            $masterKampusQuery->where(function($q) use ($sk) {
                $q->where('nama_kampus', 'ILIKE', "%{$sk}%")
                  ->orWhere('kota', 'ILIKE', "%{$sk}%")
                  ->orWhere('kota_kampus', 'ILIKE', "%{$sk}%")
                  ->orWhere('provinsi', 'ILIKE', "%{$sk}%")
                  ->orWhere('alamat', 'ILIKE', "%{$sk}%")
                  ->orWhere('alamat_kampus', 'ILIKE', "%{$sk}%")
                  ->orWhere('kode_ptn', 'ILIKE', "%{$sk}%")
                  ->orWhereHas('prodi', function($qp) use ($sk) {
                      $qp->where('nama_prodi', 'ILIKE', "%{$sk}%")
                         ->orWhere('program_studi', 'ILIKE', "%{$sk}%")
                         ->orWhere('fakultas', 'ILIKE', "%{$sk}%")
                         ->orWhere('kode_prodi', 'ILIKE', "%{$sk}%");
                  });
            });
        }
        if ($request->filled('jenis_kampus') && $request->input('jenis_kampus') !== 'all') {
            $masterKampusQuery->where('jenis', $request->input('jenis_kampus'));
        }

        $masterKampusList = $masterKampusQuery->withCount('prodi')
            ->orderBy('nama_kampus', 'asc')
            ->paginate(15, ['*'], 'page_kampus')
            ->withQueryString();


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

        // 6. Dynamic Mapel Resolution per Tahun Ajaran, Jenjang/Tingkat, Rumpun/Jurusan, dan Siswa Kelas 12
        $jurusanMapel = $request->input('jurusan_mapel', '');
        $kelasMapel = $request->input('kelas_mapel', '');
        $tingkatMapel = $request->input('tingkat_mapel', '');

        $siswaKelas12Ids = collect($rankingData['ranking_list'] ?? [])->pluck('siswa_id')->filter()->toArray();

        if (empty($siswaKelas12Ids)) {
            $pdssMapels = collect([]);
        } else {
            // 1. Resolve Kelas object jika dipilih
            $kelasObj = null;
            if (!empty($kelasMapel) && $kelasMapel !== 'all') {
                $kelasObj = DB::table('akademik.kelas')
                    ->where('tenant_id', $tenantId)
                    ->where(function($q) use ($kelasMapel) {
                        if (\Illuminate\Support\Str::isUuid($kelasMapel)) {
                            $q->where('id', $kelasMapel);
                        }
                        $q->orWhere('nama_kelas', $kelasMapel);
                    })
                    ->first();
            }

            // 2. Normalisasi Tingkat: X, XI, XII
            $tingkatNormalized = '';
            if (!empty($tingkatMapel) && $tingkatMapel !== 'all') {
                $tUpper = strtoupper(trim($tingkatMapel));
                if (in_array($tUpper, ['10', 'X', 'KELAS X', 'KELAS 10'])) $tingkatNormalized = 'X';
                elseif (in_array($tUpper, ['11', 'XI', 'KELAS XI', 'KELAS 11'])) $tingkatNormalized = 'XI';
                elseif (in_array($tUpper, ['12', 'XII', 'KELAS XII', 'KELAS 12'])) $tingkatNormalized = 'XII';
            }

            if ($kelasObj) {
                $namaK = strtoupper($kelasObj->nama_kelas);
                if (str_starts_with($namaK, 'X ') || str_starts_with($namaK, '10 ')) {
                    $tingkatNormalized = 'X';
                } elseif (str_starts_with($namaK, 'XI ') || str_starts_with($namaK, '11 ')) {
                    $tingkatNormalized = 'XI';
                } elseif (str_starts_with($namaK, 'XII ') || str_starts_with($namaK, '12 ')) {
                    $tingkatNormalized = 'XII';
                }

                if (empty($jurusanMapel) || $jurusanMapel === 'all') {
                    if (str_contains($namaK, 'IPA') || str_contains($namaK, 'MIPA')) {
                        $jurusanMapel = 'MIPA';
                    } elseif (str_contains($namaK, 'IPS') || str_contains($namaK, 'SOS')) {
                        $jurusanMapel = 'IPS';
                    } elseif (str_contains($namaK, 'RPL') || str_contains($namaK, 'TKJ') || str_contains($namaK, 'DKV') || str_contains($namaK, 'SMK')) {
                        $jurusanMapel = 'SMK';
                    }
                }
            }

            // 3. Cari Mapel Riil dari Rapor Siswa Kelas 12 yang Terdata di Tahun Ajaran Ini
            $raporQuery = DB::table('akademik.detail_nilai_rapor')
                ->where('tenant_id', $tenantId)
                ->whereIn('siswa_id', $siswaKelas12Ids);

            if ($kelasObj) {
                $raporQuery->where(function($q) use ($kelasObj) {
                    $q->where('kelas_id', (string)$kelasObj->id)
                      ->orWhere('kelas_id', $kelasObj->nama_kelas);
                });
            }

            $raporMapelIds = $raporQuery->pluck('mapel_id')->map(fn($id) => (string)$id)->unique()->filter()->toArray();

            // 4. Kueri Master Mapel yang Benar-benar Terdaftar di Database Tenant Ini
            $mapelsQuery = DB::table('akademik.mata_pelajaran');
            if ($tenantId) {
                $mapelsQuery->where('akademik.mata_pelajaran.tenant_id', $tenantId);
            }

            if (!empty($raporMapelIds)) {
                $mapelsQuery->whereIn(DB::raw('akademik.mata_pelajaran.id::text'), $raporMapelIds);
            }

            $rawMapels = $mapelsQuery->leftJoin('pdss.pdss_config_mapel', function ($join) use ($tenantId, $tahunAjaranId) {
                $join->on(DB::raw('akademik.mata_pelajaran.id::text'), '=', DB::raw('pdss.pdss_config_mapel.mapel_id::text'));
                if ($tenantId) {
                    $join->where('pdss.pdss_config_mapel.tenant_id', '=', $tenantId);
                }
                if ($tahunAjaranId) {
                    $join->where(function($q) use ($tahunAjaranId) {
                        $q->where('pdss.pdss_config_mapel.tahun_ajaran_id', '=', $tahunAjaranId)
                          ->orWhereNull('pdss.pdss_config_mapel.tahun_ajaran_id');
                    });
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
            ->orderBy('akademik.mata_pelajaran.nama_mata_pelajaran', 'asc')
            ->get();

            // 5. Normalisasi Mapel Agama: Collapse seluruh varian agama menjadi 1 entri umum
            $hasReligion = false;
            $religionSample = null;
            $filteredMapels = [];

            foreach ($rawMapels as $m) {
                $mName = strtolower($m->nama_mata_pelajaran);
                if (str_contains($mName, 'agama') || str_contains($mName, 'pendidikan agama')) {
                    if (!$hasReligion) {
                        $hasReligion = true;
                        $religionSample = clone $m;
                        $religionSample->nama_mata_pelajaran = 'Pendidikan Agama dan Budi Pekerti';
                        $religionSample->deskripsi = 'Nilai Pendidikan Agama disesuaikan dengan agama masing-masing siswa (1 nilai)';
                        $religionSample->kategori = 'Muatan Nasional / Wajib';
                        $filteredMapels[] = $religionSample;
                    }
                } else {
                    $filteredMapels[] = $m;
                }
            }

            $pdssMapels = collect($filteredMapels);
        }

        // 7. Daftar Kelas Rombel untuk Filter (Tersaring per Tenant)
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
                'tingkat_mapel'   => $tingkatMapel,
                'search_kampus'   => $request->input('search_kampus', ''),
                'jenis_kampus'    => $request->input('jenis_kampus', 'all'),
                'kuota_persen'    => $kuotaPersen ?? $rankingData['kuota_persen'] ?? 40,
                'use_erapor'      => $useERapor,
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
        $tingkatMapel = $request->input('tingkat_mapel');
        $jurusan = $request->input('jurusan');
        $tahunAjaran = $request->input('tahun_ajaran');
        $taModel = $this->resolveTahunAjaran($tahunAjaran);
        $tahunAjaranId = $taModel?->id;

        // Resolve Kelas Object
        $kelasObj = null;
        if (!empty($kelasId) && $kelasId !== 'all') {
            $kelasObj = DB::table('akademik.kelas')
                ->where('tenant_id', $tenantId)
                ->where(function($q) use ($kelasId) {
                    if (\Illuminate\Support\Str::isUuid($kelasId)) {
                        $q->where('id', $kelasId);
                    }
                    $q->orWhere('nama_kelas', $kelasId);
                })
                ->first();
        }

        // 1. Scan detail_nilai_rapor untuk tenant yang bersangkutan
        $query = DB::table('akademik.detail_nilai_rapor')->where('tenant_id', $tenantId);
        if ($kelasObj) {
            $query->where(function($q) use ($kelasObj) {
                $q->where('kelas_id', (string)$kelasObj->id)
                  ->orWhere('kelas_id', $kelasObj->nama_kelas);
            });
        } elseif (!empty($tingkatMapel) && $tingkatMapel !== 'all') {
            $tNorm = in_array(strtoupper($tingkatMapel), ['10', 'X']) ? 'X' : (in_array(strtoupper($tingkatMapel), ['11', 'XI']) ? 'XI' : (in_array(strtoupper($tingkatMapel), ['12', 'XII']) ? 'XII' : ''));
            if ($tNorm) {
                $query->where(function($q) use ($tNorm) {
                    $q->where('kelas_id', 'ILIKE', "{$tNorm} %")
                      ->orWhere('kelas_id', 'ILIKE', "KELAS {$tNorm}%")
                      ->orWhereIn('kelas_id', function($sub) use ($tNorm) {
                          $sub->select(DB::raw('id::text'))->from('akademik.kelas')->where('nama_kelas', 'ILIKE', "{$tNorm} %");
                      });
                });
            }
        }
        if ($tahunAjaran && $tahunAjaran !== 'all') {
            $query->where('tahun_ajaran', $tahunAjaran);
        }

        $rawGrades = $query->select(
            DB::raw('mapel_id::text as mapel_id'),
            'semester',
            'tahun_ajaran',
            DB::raw('count(*) as count_nilai')
        )
        ->groupBy(DB::raw('mapel_id::text'), 'semester', 'tahun_ajaran')
        ->get();

        $allMapels = DB::table('akademik.mata_pelajaran')->where('tenant_id', $tenantId)->get();

        $updatedMapels = [];
        $totalDetected = 0;

        foreach ($allMapels as $mapel) {
            $mapelGrades = $rawGrades->where('mapel_id', (string)$mapel->id);

            $hasSem1 = $mapelGrades->contains(fn($r) => in_array((string)$r->semester, ['1', 'Ganjil', 'sem_1']));
            $hasSem2 = $mapelGrades->contains(fn($r) => in_array((string)$r->semester, ['2', 'Genap', 'sem_2']));
            $hasSem3 = $mapelGrades->contains(fn($r) => in_array((string)$r->semester, ['3', 'Ganjil', 'sem_3']));
            $hasSem4 = $mapelGrades->contains(fn($r) => in_array((string)$r->semester, ['4', 'Genap', 'sem_4']));
            $hasSem5 = $mapelGrades->contains(fn($r) => in_array((string)$r->semester, ['5', 'Ganjil', 'sem_5']));
            $hasSem6 = $mapelGrades->contains(fn($r) => in_array((string)$r->semester, ['6', 'Genap', 'sem_6']));

            // If subject is National/Wajib or detected in grades
            $isWajib = stripos($mapel->kategori ?? '', 'Wajib') !== false || 
                       stripos($mapel->nama_mata_pelajaran, 'Wajib') !== false || 
                       stripos($mapel->kategori ?? '', 'Nasional') !== false;

            $hasGrades = $mapelGrades->isNotEmpty();

            $sem1 = $hasSem1 || ($isWajib && empty($tingkatMapel)) || ($isWajib && in_array($tingkatMapel, ['10', 'X']));
            $sem2 = $hasSem2 || ($isWajib && empty($tingkatMapel)) || ($isWajib && in_array($tingkatMapel, ['10', 'X']));
            $sem3 = $hasSem3 || ($isWajib && empty($tingkatMapel)) || ($isWajib && in_array($tingkatMapel, ['11', 'XI']));
            $sem4 = $hasSem4 || ($isWajib && empty($tingkatMapel)) || ($isWajib && in_array($tingkatMapel, ['11', 'XI']));
            $sem5 = $hasSem5 || ($isWajib && empty($tingkatMapel)) || ($isWajib && in_array($tingkatMapel, ['12', 'XII']));
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
                'id'                  => $mapel->id,
                'nama_mata_pelajaran' => $mapel->nama_mata_pelajaran,
                'kategori'            => $mapel->kategori,
                'deskripsi'           => $mapel->deskripsi,
                'sem_1'               => $sem1,
                'sem_2'               => $sem2,
                'sem_3'               => $sem3,
                'sem_4'               => $sem4,
                'sem_5'               => $sem5,
                'sem_6'               => $sem6,
            ];

            if ($sem1 || $sem2 || $sem3 || $sem4 || $sem5) {
                $totalDetected++;
            }
        }

        $contextLabel = $kelasObj ? "Kelas {$kelasObj->nama_kelas}" : (!empty($tingkatMapel) ? "Tingkat {$tingkatMapel}" : "Semua Kelas");

        return response()->json([
            'success' => true,
            'message' => "Berhasil mendeteksi {$totalDetected} mata pelajaran dari rapor siswa ({$contextLabel}) untuk T.A. " . ($taModel?->nama_tahun_ajaran ?? 'aktif') . ".",
            'data' => [
                'total_detected'       => $totalDetected,
                'total_scanned_grades' => $rawGrades->sum('count_nilai'),
                'mapels'               => $updatedMapels,
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
     * Ambil Daftar Program Studi Lengkap Milik Kampus Tertentu
     */
    public function getProdiByKampus(Request $request, string $kampusId): JsonResponse
    {
        $kampus = MasterKampus::find($kampusId);
        if (!$kampus) {
            return response()->json(['success' => false, 'message' => 'Kampus tidak ditemukan.'], 404);
        }

        $prodis = MasterKampusProdi::where('kampus_id', $kampusId)
            ->with(['riwayat' => function($q) {
                $q->orderBy('tahun', 'desc');
            }])
            ->orderBy('nama_prodi', 'asc')
            ->get(['id', 'nama_prodi', 'program_studi', 'jenjang', 'daya_tampung_sekarang', 'jenis_portofolio', 'fakultas', 'kode_prodi']);

        // Format mapping riwayat per tahun untuk akses instan di template Vue
        $formattedProdis = $prodis->map(function($p) {
            $riwayatMap = [];
            foreach ($p->riwayat as $r) {
                $riwayatMap[$r->tahun] = [
                    'tahun'            => $r->tahun,
                    'daya_tampung'     => $r->daya_tampung,
                    'jumlah_pendaftar' => $r->jumlah_pendaftar,
                    'diterima'         => $r->diterima,
                    'keketatan'        => $r->keketatan,
                ];
            }
            $pArray = $p->toArray();
            $pArray['riwayat_map'] = $riwayatMap;
            return $pArray;
        });

        return response()->json([
            'success' => true,
            'kampus'  => [
                'id'          => $kampus->id,
                'nama_kampus' => $kampus->nama_kampus,
                'jenis'       => $kampus->jenis,
                'akreditasi'  => $kampus->akreditasi,
                'kota'        => $kampus->kota,
                'provinsi'    => $kampus->provinsi,
                'alamat'      => $kampus->alamat,
                'web'         => $kampus->web,
                'kode_ptn'    => $kampus->kode_ptn,
            ],
            'data'    => $formattedProdis,
        ]);
    }


    /**
     * Tambah Data Master Kampus PTN Baru
     */
    public function storeKampus(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'nama_kampus'   => 'required|string|max:255',
            'jenis'         => 'nullable|string|max:100',
            'akreditasi'    => 'nullable|string|max:50',
            'kota'          => 'nullable|string|max:100',
            'provinsi'      => 'nullable|string|max:100',
            'alamat'        => 'nullable|string',
            'web'           => 'nullable|string|max:255',
            'kode_ptn'      => 'nullable|string|max:50',
        ]);

        $kampus = MasterKampus::create([
            'nama_kampus'   => strtoupper(trim($validated['nama_kampus'])),
            'jenis'         => $validated['jenis'] ?? 'Negeri',
            'jenis_kampus'  => $validated['jenis'] ?? 'Negeri',
            'akreditasi'    => $validated['akreditasi'] ?? 'A',
            'kota'          => $validated['kota'] ?? null,
            'kota_kampus'   => $validated['kota'] ?? null,
            'provinsi'      => $validated['provinsi'] ?? null,
            'alamat'        => $validated['alamat'] ?? null,
            'alamat_kampus' => $validated['alamat'] ?? null,
            'web'           => $validated['web'] ?? null,
            'kode_ptn'      => $validated['kode_ptn'] ?? null,
        ]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Master Kampus PTN berhasil ditambahkan.', 'data' => $kampus], 201);
        }

        return $this->redirectTarget($request, 'success', 'Master Kampus PTN ' . $kampus->nama_kampus . ' berhasil ditambahkan.');
    }

    /**
     * Perbarui Data Master Kampus PTN
     */
    public function updateKampus(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $kampus = MasterKampus::find($id);
        if (!$kampus) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Kampus PTN tidak ditemukan.'], 404);
            }
            return $this->redirectTarget($request, 'error', 'Kampus PTN tidak ditemukan.');
        }

        $validated = $request->validate([
            'nama_kampus'   => 'required|string|max:255',
            'jenis'         => 'nullable|string|max:100',
            'akreditasi'    => 'nullable|string|max:50',
            'kota'          => 'nullable|string|max:100',
            'provinsi'      => 'nullable|string|max:100',
            'alamat'        => 'nullable|string',
            'web'           => 'nullable|string|max:255',
            'kode_ptn'      => 'nullable|string|max:50',
        ]);

        $kampus->update([
            'nama_kampus'   => strtoupper(trim($validated['nama_kampus'])),
            'jenis'         => $validated['jenis'] ?? $kampus->jenis,
            'jenis_kampus'  => $validated['jenis'] ?? $kampus->jenis_kampus,
            'akreditasi'    => $validated['akreditasi'] ?? $kampus->akreditasi,
            'kota'          => $validated['kota'] ?? $kampus->kota,
            'kota_kampus'   => $validated['kota'] ?? $kampus->kota_kampus,
            'provinsi'      => $validated['provinsi'] ?? $kampus->provinsi,
            'alamat'        => $validated['alamat'] ?? $kampus->alamat,
            'alamat_kampus' => $validated['alamat'] ?? $kampus->alamat_kampus,
            'web'           => $validated['web'] ?? $kampus->web,
            'kode_ptn'      => $validated['kode_ptn'] ?? $kampus->kode_ptn,
        ]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Data Kampus PTN berhasil diperbarui.', 'data' => $kampus]);
        }

        return $this->redirectTarget($request, 'success', 'Data Kampus PTN ' . $kampus->nama_kampus . ' berhasil diperbarui.');
    }

    /**
     * Hapus Data Master Kampus PTN beserta Program Studinya
     */
    public function destroyKampus(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $kampus = MasterKampus::find($id);
        if (!$kampus) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Kampus PTN tidak ditemukan.'], 404);
            }
            return $this->redirectTarget($request, 'error', 'Kampus PTN tidak ditemukan.');
        }

        $nama = $kampus->nama_kampus;

        // Hapus prodi dan riwayat terkait
        $prodiIds = MasterKampusProdi::where('kampus_id', $id)->pluck('id');
        if ($prodiIds->isNotEmpty()) {
            KampusProdiRiwayat::whereIn('prodi_id', $prodiIds)->delete();
            MasterKampusProdi::whereIn('id', $prodiIds)->delete();
        }

        $kampus->delete();

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => "Kampus PTN {$nama} berhasil dihapus."]);
        }

        return $this->redirectTarget($request, 'success', "Kampus PTN {$nama} berhasil dihapus.");
    }

    /**
     * Tambah Program Studi Baru di Bawah Kampus Tertentu
     */
    public function storeProdi(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'kampus_id'              => 'required|string',
            'nama_prodi'             => 'required|string|max:255',
            'jenjang'                => 'required|string',
            'fakultas'               => 'nullable|string|max:255',
            'daya_tampung_sekarang'  => 'nullable|integer|min:0',
            'jenis_portofolio'       => 'nullable|string|max:255',
            'kode_prodi'             => 'nullable|string|max:50',
        ]);

        $prodi = MasterKampusProdi::create([
            'kampus_id'              => $validated['kampus_id'],
            'nama_prodi'             => strtoupper(trim($validated['nama_prodi'])),
            'program_studi'          => strtoupper(trim($validated['nama_prodi'])),
            'nama_master_kampus_prodi'=> strtoupper(trim($validated['nama_prodi'])),
            'jenjang'                => $validated['jenjang'],
            'fakultas'               => $validated['fakultas'] ?? null,
            'daya_tampung_sekarang'  => $validated['daya_tampung_sekarang'] ?? 0,
            'jenis_portofolio'       => $validated['jenis_portofolio'] ?? 'Tidak Ada',
            'kode_prodi'             => $validated['kode_prodi'] ?? null,
            'is_active'              => true,
        ]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Program studi berhasil ditambahkan.', 'data' => $prodi], 201);
        }

        return $this->redirectTarget($request, 'success', 'Program studi ' . $prodi->nama_prodi . ' berhasil ditambahkan.');
    }

    /**
     * Perbarui Program Studi
     */
    public function updateProdi(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $prodi = MasterKampusProdi::find($id);
        if (!$prodi) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Program studi tidak ditemukan.'], 404);
            }
            return $this->redirectTarget($request, 'error', 'Program studi tidak ditemukan.');
        }

        $validated = $request->validate([
            'nama_prodi'             => 'required|string|max:255',
            'jenjang'                => 'required|string',
            'fakultas'               => 'nullable|string|max:255',
            'daya_tampung_sekarang'  => 'nullable|integer|min:0',
            'jenis_portofolio'       => 'nullable|string|max:255',
            'kode_prodi'             => 'nullable|string|max:50',
        ]);

        $prodi->update([
            'nama_prodi'             => strtoupper(trim($validated['nama_prodi'])),
            'program_studi'          => strtoupper(trim($validated['nama_prodi'])),
            'nama_master_kampus_prodi'=> strtoupper(trim($validated['nama_prodi'])),
            'jenjang'                => $validated['jenjang'],
            'fakultas'               => $validated['fakultas'] ?? $prodi->fakultas,
            'daya_tampung_sekarang'  => $validated['daya_tampung_sekarang'] ?? $prodi->daya_tampung_sekarang,
            'jenis_portofolio'       => $validated['jenis_portofolio'] ?? $prodi->jenis_portofolio,
            'kode_prodi'             => $validated['kode_prodi'] ?? $prodi->kode_prodi,
        ]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Program studi berhasil diperbarui.', 'data' => $prodi]);
        }

        return $this->redirectTarget($request, 'success', 'Program studi ' . $prodi->nama_prodi . ' berhasil diperbarui.');
    }

    /**
     * Hapus Program Studi
     */
    public function destroyProdi(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $prodi = MasterKampusProdi::find($id);
        if (!$prodi) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Program studi tidak ditemukan.'], 404);
            }
            return $this->redirectTarget($request, 'error', 'Program studi tidak ditemukan.');
        }

        $nama = $prodi->nama_prodi;
        KampusProdiRiwayat::where('prodi_id', $id)->delete();
        $prodi->delete();

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => "Program studi {$nama} berhasil dihapus."]);
        }

        return $this->redirectTarget($request, 'success', "Program studi {$nama} berhasil dihapus.");
    }

    /**
     * API Ambil Detail Program Studi & Analisis Riwayat Seleksi 5 Tahun Terakhir (2021-2025)
     */
    public function getProdiRiwayat(Request $request, string $prodiId): JsonResponse
    {
        $prodi = MasterKampusProdi::with('kampus')->find($prodiId);
        if (!$prodi) {
            return response()->json(['success' => false, 'message' => 'Program studi tidak ditemukan.'], 404);
        }

        $riwayat = KampusProdiRiwayat::where('prodi_id', $prodiId)
            ->orWhere(function($q) use ($prodi) {
                if ($prodi->id_prodi) {
                    $q->where('id_prodi', $prodi->id_prodi);
                }
                if ($prodi->kode_prodi) {
                    $q->orWhere('kode_prodi', $prodi->kode_prodi);
                }
            })
            ->orderBy('tahun', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'prodi'   => $prodi,
            'kampus'  => $prodi->kampus,
            'riwayat' => $riwayat,
        ]);
    }

    /**
     * Ekspor Seluruh Master Katalog Kampus PTN & Program Studi SNPMB (CSV UTF-8 Excel Ready)
     */
    public function exportMasterKampus(Request $request)
    {
        $query = MasterKampusProdi::query()
            ->join('pdss.master_kampus as k', 'k.id', '=', 'pdss.master_kampus_prodi.kampus_id')
            ->select([
                'pdss.master_kampus_prodi.id as prodi_id',
                'pdss.master_kampus_prodi.kode_prodi',
                'pdss.master_kampus_prodi.nama_prodi',
                'pdss.master_kampus_prodi.jenjang',
                'pdss.master_kampus_prodi.fakultas',
                'pdss.master_kampus_prodi.daya_tampung_sekarang',
                'pdss.master_kampus_prodi.jenis_portofolio',
                'k.id as kampus_id',
                'k.id_ptn',
                'k.kode_ptn',
                'k.nama_kampus',
                'k.jenis as jenis_kampus',
                'k.akreditasi as akreditasi_kampus',
                'k.kota as kota_kampus',
                'k.provinsi as provinsi_kampus',
                'k.alamat as alamat_kampus',
                'k.web as website_kampus',
            ])
            ->orderBy('k.nama_kampus', 'asc')
            ->orderBy('pdss.master_kampus_prodi.jenjang', 'asc')
            ->orderBy('pdss.master_kampus_prodi.nama_prodi', 'asc');

        if ($request->filled('jenis_kampus') && $request->input('jenis_kampus') !== 'all') {
            $query->where('k.jenis', $request->input('jenis_kampus'));
        }

        if ($request->filled('search')) {
            $s = trim($request->input('search'));
            $query->where(function($q) use ($s) {
                $q->where('k.nama_kampus', 'ILIKE', "%{$s}%")
                  ->orWhere('pdss.master_kampus_prodi.nama_prodi', 'ILIKE', "%{$s}%")
                  ->orWhere('pdss.master_kampus_prodi.fakultas', 'ILIKE', "%{$s}%")
                  ->orWhere('k.kota', 'ILIKE', "%{$s}%")
                  ->orWhere('k.provinsi', 'ILIKE', "%{$s}%");
            });
        }

        $records = $query->get();

        // Ambil riwayat keketatan per prodi_id
        $prodiIds = $records->pluck('prodi_id')->toArray();
        $riwayatGrouped = DB::table('pdss.kampus_prodi_riwayat')
            ->whereIn('prodi_id', $prodiIds)
            ->get()
            ->groupBy('prodi_id');

        $filename = 'master_direktori_kampus_prodi_snpmb_' . date('Ymd_His');

        $rows = [
            [
                'NO',
                'KODE PTN',
                'NAMA KAMPUS PTN',
                'JENIS KAMPUS',
                'AKREDITASI',
                'KOTA',
                'PROVINSI',
                'ALAMAT',
                'WEBSITE RESMI',
                'KODE PRODI',
                'NAMA PROGRAM STUDI',
                'JENJANG',
                'FAKULTAS',
                'DAYA TAMPUNG 2025',
                'PORTOFOLIO',
                'KEKETATAN 2025',
                'PEMINAT 2025',
                'DITERIMA 2025',
                'KEKETATAN 2024',
                'PEMINAT 2024',
                'DITERIMA 2024',
                'KEKETATAN 2023',
                'PEMINAT 2023',
                'DITERIMA 2023',
                'KEKETATAN 2022',
                'PEMINAT 2022',
                'DITERIMA 2022',
                'KEKETATAN 2021',
                'PEMINAT 2021',
                'DITERIMA 2021',
            ]
        ];

        $no = 1;
        foreach ($records as $r) {
            $riwayatProdi = ($riwayatGrouped->get($r->prodi_id) ?? collect([]))->keyBy('tahun');

            $r2025 = $riwayatProdi->get(2025);
            $r2024 = $riwayatProdi->get(2024);
            $r2023 = $riwayatProdi->get(2023);
            $r2022 = $riwayatProdi->get(2022);
            $r2021 = $riwayatProdi->get(2021);

            $rows[] = [
                $no++,
                $r->kode_ptn ?? $r->id_ptn ?? '-',
                $r->nama_kampus,
                $r->jenis_kampus ?? 'Negeri',
                $r->akreditasi_kampus ?? 'A',
                $r->kota_kampus ?? '-',
                $r->provinsi_kampus ?? '-',
                $r->alamat_kampus ?? '-',
                $r->website_kampus ?? '-',
                $r->kode_prodi ?? '-',
                $r->nama_prodi,
                $r->jenjang ?? 'S1',
                $r->fakultas ?? '-',
                (int)($r->daya_tampung_sekarang ?? 0),
                $r->jenis_portofolio ?? 'Tidak Ada',
                $r2025->keketatan ?? '-',
                (int)($r2025->jumlah_pendaftar ?? 0),
                (int)($r2025->diterima ?? 0),
                $r2024->keketatan ?? '-',
                (int)($r2024->jumlah_pendaftar ?? 0),
                (int)($r2024->diterima ?? 0),
                $r2023->keketatan ?? '-',
                (int)($r2023->jumlah_pendaftar ?? 0),
                (int)($r2023->diterima ?? 0),
                $r2022->keketatan ?? '-',
                (int)($r2022->jumlah_pendaftar ?? 0),
                (int)($r2022->diterima ?? 0),
                $r2021->keketatan ?? '-',
                (int)($r2021->jumlah_pendaftar ?? 0),
                (int)($r2021->diterima ?? 0),
            ];
        }

        if ($request->input('format') === 'csv') {
            return response()->streamDownload(function () use ($rows) {
                $output = fopen('php://output', 'w');
                fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
                foreach ($rows as $row) {
                    fputcsv($output, $row);
                }
                fclose($output);
            }, "{$filename}.csv", [
                'Content-Type'        => 'text/csv; charset=UTF-8',
                'Content-Disposition' => "attachment; filename=\"{$filename}.csv\"",
            ]);
        }

        $xlsx = \Shuchkin\SimpleXLSXGen::fromArray($rows);
        return response((string) $xlsx, 200, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$filename}.xlsx\"",
            'Cache-Control'       => 'max-age=0',
        ]);
    }

    /**
     * Unduh Template Excel (.XLSX) untuk Impor Master Kampus PTN & Prodi SNPMB
     */
    public function downloadTemplateKampus(?Request $request = null)
    {
        $filename = 'template_import_kampus_prodi_snpmb';

        $templateRows = [
            [
                'kode_ptn',
                'nama_kampus',
                'jenis_kampus',
                'akreditasi',
                'kota',
                'provinsi',
                'web',
                'kode_prodi',
                'nama_prodi',
                'jenjang',
                'fakultas',
                'daya_tampung',
                'jenis_portofolio',
                'keketatan_2025',
                'peminat_2025',
                'diterima_2025',
                'keketatan_2024',
                'peminat_2024',
                'diterima_2024',
            ],
            [
                '1321',
                'UNIVERSITAS INDONESIA',
                'PTN Akademik',
                'A',
                'Kota Depok',
                'Jawa Barat',
                'https://ui.ac.id',
                '13211001',
                'ILMU KOMPUTER',
                'S1',
                'Fakultas Ilmu Komputer',
                60,
                'Tidak Ada',
                '3.85%',
                1550,
                60,
                '4.12%',
                1455,
                60,
            ],
            [
                '1362',
                'POLITEKNIK ELEKTRONIKA NEGERI SURABAYA',
                'Politeknik',
                'A',
                'Kota Surabaya',
                'Jawa Timur',
                'https://pens.ac.id',
                '13622001',
                'TEKNIK INFORMATIKA',
                'D4',
                'Departemen Teknik Informatika',
                30,
                'Tidak Ada',
                '6.25%',
                480,
                30,
                '6.50%',
                460,
                30,
            ]
        ];

        if ($request?->input('format') === 'csv') {
            return response()->streamDownload(function () use ($templateRows) {
                $output = fopen('php://output', 'w');
                fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
                foreach ($templateRows as $row) {
                    fputcsv($output, $row);
                }
                fclose($output);
            }, "{$filename}.csv", [
                'Content-Type'        => 'text/csv; charset=UTF-8',
                'Content-Disposition' => "attachment; filename=\"{$filename}.csv\"",
            ]);
        }

        $xlsx = \Shuchkin\SimpleXLSXGen::fromArray($templateRows);
        return response((string) $xlsx, 200, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$filename}.xlsx\"",
            'Cache-Control'       => 'max-age=0',
        ]);
    }

    /**
     * Impor Master Kampus PTN & Program Studi dari Excel (.xlsx) / CSV (Anti-Duplikasi / Strict Upsert)
     * Mendukung format XLSX murni, snpmb_snbp_ptn.csv, snpmb_snbp_prodi.csv, snpmb_snbp_historis_peminat.csv, maupun format gabungan direktori.
     */
    public function importMasterKampus(Request $request): RedirectResponse|JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'file_csv'   => 'nullable|file|max:20480',
            'file_excel' => 'nullable|file|max:20480',
        ]);

        if ($validator->fails()) {
            $msg = $validator->errors()->first() ?: 'File spreadsheet tidak valid.';
            return $this->redirectTarget($request, 'error', $msg);
        }

        $file = $request->file('file_csv') ?: $request->file('file_excel');
        if (!$file) {
            return $this->redirectTarget($request, 'error', 'Silakan pilih berkas spreadsheet (.xlsx / .csv).');
        }

        $realPath = $file->getRealPath();
        $ext = strtolower($file->getClientOriginalExtension());

        $allRows = [];

        // 1. Coba baca via SimpleXLSX parser jika format xlsx/xls
        if (in_array($ext, ['xlsx', 'xls']) || ($xlsxParsed = \Shuchkin\SimpleXLSX::parse($realPath))) {
            if (isset($xlsxParsed) && $xlsxParsed) {
                $allRows = $xlsxParsed->rows();
            }
        }

        // 2. Fallback ke CSV parser jika bukan XLSX atau XLSX kosong
        if (empty($allRows)) {
            $handle = fopen($realPath, 'r');
            if ($handle) {
                $bom = fread($handle, 3);
                if ($bom !== "\xEF\xBB\xBF") {
                    rewind($handle);
                }
                $firstLine = fgets($handle);
                rewind($handle);
                if ($bom === "\xEF\xBB\xBF") {
                    fseek($handle, 3);
                }
                $delimiter = ',';
                if (substr_count($firstLine, ';') > substr_count($firstLine, ',')) {
                    $delimiter = ';';
                } elseif (substr_count($firstLine, "\t") > substr_count($firstLine, ',')) {
                    $delimiter = "\t";
                }
                while (($row = fgetcsv($handle, 4000, $delimiter)) !== false) {
                    if (!empty($row)) {
                        $allRows[] = $row;
                    }
                }
                fclose($handle);
            }
        }

        if (empty($allRows) || count($allRows) < 2) {
            return $this->redirectTarget($request, 'error', 'Berkas spreadsheet kosong atau tidak memuat baris data.');
        }

        $header = array_shift($allRows);

        // 3. Normalisasi Header Map
        $headerMap = [];
        foreach ($header as $idx => $col) {
            $cleanCol = strtolower(trim(str_replace(['"', "'", ' ', '﻿', "\xEF\xBB\xBF"], ['', '', '_', '', ''], $col)));
            $headerMap[$cleanCol] = $idx;
        }

        $isPtnOnlyFormat       = isset($headerMap['nama_ptn']) || (isset($headerMap['id_ptn']) && !isset($headerMap['nama_prodi']) && !isset($headerMap['id_prodi']));
        $isProdiOnlyFormat     = isset($headerMap['id_prodi']) && isset($headerMap['nama_prodi']) && !isset($headerMap['nama_ptn']) && !isset($headerMap['nama_kampus']);
        $isHistorisOnlyFormat  = isset($headerMap['peminat']) && isset($headerMap['tahun']) && (isset($headerMap['kode_prodi']) || isset($headerMap['id_prodi']));

        $newKampus = 0;
        $updatedKampus = 0;
        $newProdi = 0;
        $updatedProdi = 0;
        $syncedRiwayat = 0;

        DB::beginTransaction();
        try {
            foreach ($allRows as $row) {
                if (empty($row) || count($row) < 2) continue;

                // FORMAT 1: CSV Khusus Master PTN (snpmb_snbp_ptn.csv)
                if ($isPtnOnlyFormat) {
                    $idPtn    = trim($row[$headerMap['id_ptn'] ?? 0] ?? '');
                    $kodePtn  = trim($row[$headerMap['kode_ptn'] ?? 1] ?? '');
                    $namaPtn  = trim($row[$headerMap['nama_ptn'] ?? 2] ?? '');
                    $web      = trim($row[$headerMap['web'] ?? 3] ?? '');
                    $jenis    = trim($row[$headerMap['jenis'] ?? 4] ?? 'PTN Akademik');
                    $alamat   = trim($row[$headerMap['alamat'] ?? 5] ?? '');
                    $provinsi = trim($row[$headerMap['provinsi1'] ?? ($headerMap['provinsi'] ?? 6)] ?? '');
                    $kota     = trim($row[$headerMap['kota'] ?? 8] ?? '');

                    if (empty($namaPtn)) continue;

                    $kampus = MasterKampus::where(function($q) use ($idPtn, $kodePtn, $namaPtn) {
                        if ($idPtn) $q->orWhere('id_ptn', $idPtn);
                        if ($kodePtn) $q->orWhere('kode_ptn', $kodePtn);
                        $q->orWhere('nama_kampus', 'ILIKE', $namaPtn);
                    })->first();

                    if ($kampus) {
                        $kampus->update([
                            'id_ptn'        => $idPtn ?: $kampus->id_ptn,
                            'kode_ptn'      => $kodePtn ?: $kampus->kode_ptn,
                            'nama_kampus'   => strtoupper($namaPtn),
                            'web'           => $web ?: $kampus->web,
                            'jenis'         => $jenis ?: $kampus->jenis,
                            'alamat'        => $alamat ?: $kampus->alamat,
                            'alamat_kampus' => $alamat ?: $kampus->alamat_kampus,
                            'provinsi'      => $provinsi ?: $kampus->provinsi,
                            'kota'          => $kota ?: $kampus->kota,
                            'kota_kampus'   => $kota ?: $kampus->kota_kampus,
                        ]);
                        $updatedKampus++;
                    } else {
                        MasterKampus::create([
                            'id'            => (string)\Illuminate\Support\Str::uuid(),
                            'tenant_id'     => null,
                            'id_ptn'        => $idPtn ?: null,
                            'kode_ptn'      => $kodePtn ?: null,
                            'nama_kampus'   => strtoupper($namaPtn),
                            'web'           => $web ?: null,
                            'jenis'         => $jenis ?: 'PTN Akademik',
                            'akreditasi'    => 'A',
                            'alamat'        => $alamat ?: null,
                            'alamat_kampus' => $alamat ?: null,
                            'provinsi'      => $provinsi ?: null,
                            'kota'          => $kota ?: null,
                            'kota_kampus'   => $kota ?: null,
                        ]);
                        $newKampus++;
                    }
                    continue;
                }

                // FORMAT 2: CSV Khusus Master Prodi (snpmb_snbp_prodi.csv)
                if ($isProdiOnlyFormat) {
                    $idProdi      = trim($row[$headerMap['id_prodi'] ?? 0] ?? '');
                    $kodeProdi    = trim($row[$headerMap['kode_prodi'] ?? 1] ?? '');
                    $idPtn        = trim($row[$headerMap['id_ptn'] ?? 2] ?? '');
                    $namaProdi    = trim($row[$headerMap['nama_prodi'] ?? 3] ?? '');
                    $jenjang      = trim($row[$headerMap['jenjang'] ?? 4] ?? 'S1');
                    $dayaTampung  = (int)(trim($row[$headerMap['daya_tampung_sekarang'] ?? ($headerMap['daya_tampung'] ?? 5)] ?? 0));
                    $portofolio   = trim($row[$headerMap['jenis_portofolio'] ?? 6] ?? 'Tidak Ada');

                    if (empty($namaProdi)) continue;

                    $kampus = MasterKampus::where('id_ptn', $idPtn)->orWhere('kode_ptn', $idPtn)->first();
                    if (!$kampus) continue;

                    $prodi = MasterKampusProdi::where('kampus_id', $kampus->id)
                        ->where(function($q) use ($kodeProdi, $namaProdi, $jenjang) {
                            if ($kodeProdi) $q->where('kode_prodi', $kodeProdi);
                            $q->orWhere(function($sub) use ($namaProdi, $jenjang) {
                                $sub->where('nama_prodi', 'ILIKE', $namaProdi)
                                    ->where('jenjang', $jenjang);
                            });
                        })->first();

                    if ($prodi) {
                        $prodi->update([
                            'kode_prodi'             => $kodeProdi ?: $prodi->kode_prodi,
                            'nama_prodi'             => strtoupper($namaProdi),
                            'program_studi'          => strtoupper($namaProdi),
                            'jenjang'                => $jenjang,
                            'daya_tampung_sekarang'  => $dayaTampung > 0 ? $dayaTampung : $prodi->daya_tampung_sekarang,
                            'jenis_portofolio'       => $portofolio ?: $prodi->jenis_portofolio,
                        ]);
                        $updatedProdi++;
                    } else {
                        MasterKampusProdi::create([
                            'id'                    => (string)\Illuminate\Support\Str::uuid(),
                            'tenant_id'             => null,
                            'kampus_id'             => $kampus->id,
                            'kode_prodi'            => $kodeProdi ?: null,
                            'nama_prodi'            => strtoupper($namaProdi),
                            'program_studi'         => strtoupper($namaProdi),
                            'jenjang'               => $jenjang,
                            'daya_tampung_sekarang' => $dayaTampung,
                            'jenis_portofolio'      => $portofolio ?: 'Tidak Ada',
                            'is_active'             => true,
                        ]);
                        $newProdi++;
                    }
                    continue;
                }

                // FORMAT 3: CSV Khusus Historis Keketatan (snpmb_snbp_historis_peminat.csv)
                if ($isHistorisOnlyFormat) {
                    $idProdi      = trim($row[$headerMap['id_prodi'] ?? 0] ?? '');
                    $kodeProdi    = trim($row[$headerMap['kode_prodi'] ?? 1] ?? '');
                    $tahun        = (int)(trim($row[$headerMap['tahun'] ?? 2] ?? 2025));
                    $dayaTampung  = (int)(trim($row[$headerMap['daya_tampung'] ?? 3] ?? 0));
                    $peminat      = (int)(trim($row[$headerMap['peminat'] ?? 4] ?? 0));
                    $diterima     = (int)(trim($row[$headerMap['diterima'] ?? 5] ?? 0));
                    $keketatan    = trim($row[$headerMap['keketatan'] ?? 6] ?? '0.00%');

                    $prodi = MasterKampusProdi::where(function($q) use ($kodeProdi, $idProdi) {
                        if ($kodeProdi) $q->orWhere('kode_prodi', $kodeProdi);
                    })->first();

                    if ($prodi) {
                        DB::table('pdss.kampus_prodi_riwayat')->updateOrInsert(
                            ['prodi_id' => $prodi->id, 'tahun' => $tahun],
                            [
                                'daya_tampung'     => $dayaTampung,
                                'jumlah_pendaftar' => $peminat,
                                'diterima'         => $diterima,
                                'keketatan'        => $keketatan,
                                'updated_at'       => now(),
                            ]
                        );
                        $syncedRiwayat++;
                    }
                    continue;
                }

                // FORMAT 4: Format Gabungan Direktori PTN, Prodi, & Riwayat (Template / Ekspor SINTA)
                $namaKampus = trim($row[$headerMap['nama_kampus_ptn'] ?? ($headerMap['nama_kampus'] ?? 1)] ?? '');
                $namaProdi  = trim($row[$headerMap['nama_program_studi'] ?? ($headerMap['nama_prodi'] ?? ($headerMap['program_studi'] ?? 8))] ?? '');
                $kodePtn    = trim($row[$headerMap['kode_ptn'] ?? 0] ?? '');
                $kodeProdi  = trim($row[$headerMap['kode_prodi'] ?? 7] ?? '');

                if (empty($namaKampus)) continue;

                // 1. Upsert Kampus PTN
                $kampus = MasterKampus::where(function($q) use ($kodePtn, $namaKampus) {
                    if ($kodePtn && $kodePtn !== '-') $q->where('kode_ptn', $kodePtn);
                    $q->orWhere('nama_kampus', 'ILIKE', $namaKampus);
                })->first();

                $jenis      = trim($row[$headerMap['jenis_kampus'] ?? ($headerMap['jenis'] ?? 2)] ?? 'Negeri') ?: 'Negeri';
                $akreditasi = trim($row[$headerMap['akreditasi'] ?? 3] ?? 'A') ?: 'A';
                $kota       = trim($row[$headerMap['kota'] ?? 4] ?? '') ?: null;
                $provinsi   = trim($row[$headerMap['provinsi'] ?? 5] ?? '') ?: null;
                $alamat     = trim($row[$headerMap['alamat'] ?? 6] ?? '') ?: null;
                $web        = trim($row[$headerMap['website_resmi'] ?? ($headerMap['web'] ?? 7)] ?? '') ?: null;

                if ($kampus) {
                    $kampus->update([
                        'nama_kampus'   => strtoupper($namaKampus),
                        'kode_ptn'      => ($kodePtn && $kodePtn !== '-') ? $kodePtn : $kampus->kode_ptn,
                        'jenis'         => $jenis,
                        'akreditasi'    => $akreditasi,
                        'kota'          => $kota ?: $kampus->kota,
                        'kota_kampus'   => $kota ?: $kampus->kota_kampus,
                        'provinsi'      => $provinsi ?: $kampus->provinsi,
                        'alamat'        => $alamat ?: $kampus->alamat,
                        'web'           => $web ?: $kampus->web,
                    ]);
                    $updatedKampus++;
                } else {
                    $kampus = MasterKampus::create([
                        'id'            => (string)\Illuminate\Support\Str::uuid(),
                        'tenant_id'     => null,
                        'kode_ptn'      => ($kodePtn && $kodePtn !== '-') ? $kodePtn : null,
                        'nama_kampus'   => strtoupper($namaKampus),
                        'jenis'         => $jenis,
                        'akreditasi'    => $akreditasi,
                        'kota'          => $kota,
                        'kota_kampus'   => $kota,
                        'provinsi'      => $provinsi,
                        'alamat'        => $alamat,
                        'web'           => $web,
                    ]);
                    $newKampus++;
                }

                // 2. Upsert Program Studi
                if (!empty($namaProdi)) {
                    $jenjang      = trim($row[$headerMap['jenjang'] ?? 9] ?? 'S1') ?: 'S1';
                    $fakultas     = trim($row[$headerMap['fakultas'] ?? 10] ?? '') ?: null;
                    $dayaTampung  = (int)(trim($row[$headerMap['daya_tampung_2025'] ?? ($headerMap['daya_tampung'] ?? 11)] ?? 0));
                    $portofolio   = trim($row[$headerMap['portofolio'] ?? ($headerMap['jenis_portofolio'] ?? 12)] ?? 'Tidak Ada') ?: 'Tidak Ada';

                    $prodi = MasterKampusProdi::where('kampus_id', $kampus->id)
                        ->where(function($q) use ($kodeProdi, $namaProdi, $jenjang) {
                            if ($kodeProdi && $kodeProdi !== '-') $q->where('kode_prodi', $kodeProdi);
                            $q->orWhere(function($sub) use ($namaProdi, $jenjang) {
                                $sub->where('nama_prodi', 'ILIKE', $namaProdi)
                                    ->where('jenjang', $jenjang);
                            });
                        })->first();

                    if ($prodi) {
                        $prodi->update([
                            'kode_prodi'             => ($kodeProdi && $kodeProdi !== '-') ? $kodeProdi : $prodi->kode_prodi,
                            'nama_prodi'             => strtoupper($namaProdi),
                            'program_studi'          => strtoupper($namaProdi),
                            'jenjang'                => $jenjang,
                            'fakultas'               => $fakultas ?: $prodi->fakultas,
                            'daya_tampung_sekarang'  => $dayaTampung > 0 ? $dayaTampung : $prodi->daya_tampung_sekarang,
                            'jenis_portofolio'       => $portofolio ?: $prodi->jenis_portofolio,
                        ]);
                        $updatedProdi++;
                    } else {
                        $prodi = MasterKampusProdi::create([
                            'id'                    => (string)\Illuminate\Support\Str::uuid(),
                            'tenant_id'             => null,
                            'kampus_id'             => $kampus->id,
                            'kode_prodi'            => ($kodeProdi && $kodeProdi !== '-') ? $kodeProdi : null,
                            'nama_prodi'            => strtoupper($namaProdi),
                            'program_studi'         => strtoupper($namaProdi),
                            'jenjang'               => $jenjang,
                            'fakultas'              => $fakultas,
                            'daya_tampung_sekarang' => $dayaTampung,
                            'jenis_portofolio'      => $portofolio,
                            'is_active'             => true,
                        ]);
                        $newProdi++;
                    }

                    // 3. Upsert Riwayat Keketatan 2021-2025 jika ada di kolom CSV
                    $years = [2025, 2024, 2023, 2022, 2021];
                    foreach ($years as $yr) {
                        $keketatanKey = "keketatan_{$yr}";
                        $peminatKey   = "peminat_{$yr}";
                        $diterimaKey  = "diterima_{$yr}";

                        if (isset($headerMap[$keketatanKey]) || isset($headerMap[$peminatKey])) {
                            $kVal = trim($row[$headerMap[$keketatanKey] ?? -1] ?? '');
                            $pVal = (int)(trim($row[$headerMap[$peminatKey] ?? -1] ?? 0));
                            $dVal = (int)(trim($row[$headerMap[$diterimaKey] ?? -1] ?? 0));

                            if ($kVal || $pVal > 0 || $dVal > 0) {
                                DB::table('pdss.kampus_prodi_riwayat')->updateOrInsert(
                                    ['prodi_id' => $prodi->id, 'tahun' => $yr],
                                    [
                                        'daya_tampung'     => $dVal > 0 ? $dVal : $dayaTampung,
                                        'jumlah_pendaftar' => $pVal,
                                        'diterima'         => $dVal,
                                        'keketatan'        => $kVal ?: ($pVal > 0 ? number_format(($dVal / $pVal) * 100, 2) . '%' : '0.00%'),
                                        'updated_at'       => now(),
                                    ]
                                );
                                $syncedRiwayat++;
                            }
                        }
                    }
                }
            }

            DB::commit();

            $msg = "Impor data berhasil diproses! [PTN: {$newKampus} baru, {$updatedKampus} diperbarui | Prodi: {$newProdi} baru, {$updatedProdi} diperbarui | Riwayat: {$syncedRiwayat} rekaman disinkronkan]. Data terjamin bebas duplikasi.";

            if ($request->wantsJson()) {
                return response()->json(['success' => true, 'message' => $msg]);
            }
            return $this->redirectTarget($request, 'success', $msg);
        } catch (\Throwable $e) {
            DB::rollBack();
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Gagal mengimpor data: ' . $e->getMessage()], 500);
            }
            return $this->redirectTarget($request, 'error', 'Gagal mengimpor data: ' . $e->getMessage());
        }
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
     * Reset Seluruh Override Kelayakan Siswa ke Kalkulasi Murni Nilai Rapor & Kuota
     */
    public function resetAllEligible(Request $request): RedirectResponse|JsonResponse
    {
        $user = $request->user() ?: Auth::user();
        $tenantId = $this->resolveActiveTenantId($request, $user);

        $taInput = $request->input('tahun_ajaran');
        $taModel = $this->resolveTahunAjaran($taInput);
        $tahunAjaranId = $taModel?->id;

        $query = KesiapanSiswa::withoutTenant()->where('tenant_id', $tenantId);
        if ($tahunAjaranId) {
            $query->where(function($q) use ($tahunAjaranId) {
                $q->where('tahun_ajaran_id', $tahunAjaranId)
                  ->orWhereNull('tahun_ajaran_id');
            });
        }

        $affected = $query->update([
            'is_eligible_final' => null,
            'catatan_override'  => null,
            'updated_at'        => now(),
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Seluruh status kelayakan siswa berhasil direset ke kalkulasi otomatis kuota & nilai rapor.',
                'affected'=> $affected
            ]);
        }

        return $this->redirectTarget($request, 'success', 'Seluruh status kelayakan siswa berhasil direset ke kalkulasi otomatis kuota & nilai rapor.', $tenantId, $taInput);
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
     * Batalkan Pengunduran Diri Siswa (Pulihkan Status Siswa ke Peringkat Asli)
     */
    public function batalkanPengunduranDiri(Request $request): RedirectResponse|JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'siswa_id'        => 'required|string',
            'tahun_ajaran'    => 'nullable|string',
            'tahun_ajaran_id' => 'nullable|string',
            'tenant_id'       => 'nullable|string',
        ]);

        if ($validator->fails()) {
            $msg = $validator->errors()->first() ?: 'Data siswa tidak valid.';
            return $this->redirectTarget($request, 'error', $msg);
        }

        $validated = $validator->validated();
        $user = $request->user() ?: Auth::user();
        $tenantId = $this->resolveActiveTenantId($request, $user, $validated['siswa_id']);

        $taModel = $this->resolveTahunAjaran($validated['tahun_ajaran'] ?? null);
        $tahunAjaranId = $validated['tahun_ajaran_id'] ?? ($taModel?->id);

        // Hapus data surat pengunduran diri
        $pQuery = PengunduranDiri::withoutTenant()->where('siswa_id', $validated['siswa_id']);
        if ($tenantId) {
            $pQuery->where('tenant_id', $tenantId);
        }
        $pQuery->delete();

        // Kembalikan status kesiapan siswa
        $kQuery = KesiapanSiswa::withoutTenant()->where('siswa_id', $validated['siswa_id']);
        if ($tenantId) {
            $kQuery->where('tenant_id', $tenantId);
        }
        $kQuery->update([
            'status_pengunduran_diri' => false,
            'pengunduran_diri_id'     => null,
            'is_eligible_final'       => null,
            'catatan_override'        => null,
            'updated_at'              => now(),
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Pengunduran diri berhasil dibatalkan. Siswa telah dipulihkan ke peringkat pemeringkatan semula.',
            ]);
        }

        return $this->redirectTarget($request, 'success', 'Pengunduran diri berhasil dibatalkan. Siswa telah dipulihkan ke peringkat pemeringkatan semula.', $tenantId, $validated['tahun_ajaran'] ?? null);
    }

    /**
     * Ambil Detail Nilai Rapor Semester 1 s.d. 5 Siswa untuk Modal Transkrip Audit
     */
    public function getDetailNilaiRaporSiswa(Request $request, string $siswaId): JsonResponse
    {
        $user = $request->user() ?: Auth::user();
        $siswa = Siswa::withoutTenant()->where('id', $siswaId)->first();

        if (!$siswa) {
            return response()->json(['success' => false, 'message' => 'Data siswa tidak ditemukan.'], 404);
        }

        $tenantId = $siswa->tenant_id ?: $this->resolveActiveTenantId($request, $user);

        // Ambil riwayat kelas siswa
        $anggotaKelas = DB::table('siswa.anggota_kelas as ak')
            ->join('akademik.kelas as k', 'ak.kelas_id', '=', 'k.id')
            ->where('ak.siswa_id', $siswaId)
            ->where('ak.is_aktif', true)
            ->select('k.nama_kelas', 'ak.tahun_ajaran')
            ->first();

        // Ambil seluruh nilai detail rapor siswa
        $rawGrades = DB::table('akademik.detail_nilai_rapor as dnr')
            ->leftJoin('akademik.mata_pelajaran as mp', DB::raw('mp.id::text'), '=', DB::raw('dnr.mapel_id::text'))
            ->where('dnr.siswa_id', (string)$siswaId)
            ->select(
                'dnr.id',
                'dnr.mapel_id',
                'mp.nama_mata_pelajaran',
                'mp.kategori as kelompok',
                'dnr.semester',
                'dnr.nilai_akhir',
                'dnr.predikat'
            )
            ->get();

        // Daftar mapel unik yang diambil siswa
        $groupedMapel = [];
        $semesterTotals = ['1' => [], '2' => [], '3' => [], '4' => [], '5' => []];

        foreach ($rawGrades as $g) {
            $mId = $g->mapel_id ?: $g->nama_mata_pelajaran;
            $mName = $g->nama_mata_pelajaran ?: 'Mata Pelajaran';
            $kelompok = $g->kelompok ?: 'Wajib';
            $sem = (string)$g->semester;
            $score = (float)$g->nilai_akhir;

            // Normalisasi semester: jika "Ganjil" -> 1, "Genap" -> 2
            if ($sem === 'Ganjil') $sem = '1';
            elseif ($sem === 'Genap') $sem = '2';

            if (!isset($groupedMapel[$mId])) {
                $groupedMapel[$mId] = [
                    'mapel_id'   => $mId,
                    'nama_mapel' => $mName,
                    'kelompok'   => $kelompok,
                    'sem_1'      => null,
                    'sem_2'      => null,
                    'sem_3'      => null,
                    'sem_4'      => null,
                    'sem_5'      => null,
                ];
            }

            if (in_array($sem, ['1', '2', '3', '4', '5']) && $score > 0) {
                $groupedMapel[$mId]['sem_' . $sem] = round($score, 1);
                $semesterTotals[$sem][] = $score;
            }
        }

        // Hitung rata-rata per mapel across semester
        $mapelList = [];
        $totalSemesterAverages = [];

        foreach ($groupedMapel as $mId => $item) {
            $scores = [];
            for ($s = 1; $s <= 5; $s++) {
                if (!is_null($item['sem_' . $s])) {
                    $scores[] = $item['sem_' . $s];
                }
            }
            $item['rata_rata_mapel'] = count($scores) > 0 ? round(array_sum($scores) / count($scores), 2) : 0.00;
            $mapelList[] = $item;
        }

        // Urutkan mapel: Wajib di atas, lalu Peminatan
        usort($mapelList, function($a, $b) {
            return strcmp($a['kelompok'], $b['kelompok']) ?: strcmp($a['nama_mapel'], $b['nama_mapel']);
        });

        // Hitung rata-rata tiap semester
        $semesterAverages = [];
        for ($s = 1; $s <= 5; $s++) {
            $semStr = (string)$s;
            if (!empty($semesterTotals[$semStr])) {
                $avg = round(array_sum($semesterTotals[$semStr]) / count($semesterTotals[$semStr]), 2);
                $semesterAverages['sem_' . $s] = $avg;
                $totalSemesterAverages[] = $avg;
            } else {
                $semesterAverages['sem_' . $s] = null;
            }
        }

        $akumulatifRataRata = count($totalSemesterAverages) > 0 ? round(array_sum($totalSemesterAverages) / count($totalSemesterAverages), 2) : 0.00;

        return response()->json([
            'success' => true,
            'data'    => [
                'student' => [
                    'id'           => $siswa->id,
                    'nama_lengkap' => $siswa->nama_lengkap,
                    'nisn'         => $siswa->nisn ?: '-',
                    'nis'          => $siswa->nis ?: '-',
                    'jurusan'      => $siswa->jurusan ?: 'MIPA',
                    'kelas'        => $anggotaKelas?->nama_kelas ?? $siswa->kelas_saat_ini ?? 'XII',
                    'tahun_ajaran' => $anggotaKelas?->tahun_ajaran ?? '2026/2027',
                ],
                'mapels'               => $mapelList,
                'semester_averages'    => $semesterAverages,
                'rata_rata_akumulatif' => $akumulatifRataRata,
                'total_nilai_terisi'   => $rawGrades->count(),
            ],
        ]);
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

    /**
     * Unduh / Ekspor Data Rekap Nilai Rapor Buku Induk Siswa (Multi-Filter)
     * Filter: Tahun Ajaran, Jenjang/Tingkat, Semester, dan Kelas/Rombel
     */
    public function exportNilai(Request $request)
    {
        $user = $request->user() ?: Auth::user();
        $tenantId = $this->resolveActiveTenantId($request, $user);

        $tahunAjaran = $request->input('tahun_ajaran');
        $jenjang = $request->input('jenjang', 'all');
        $semester = $request->input('semester', 'all');
        $kelasId = $request->input('kelas_id', 'all');

        $query = DB::table('akademik.detail_nilai_rapor as dnr')
            ->join('siswa.siswa as s', DB::raw('s.id::text'), '=', DB::raw('dnr.siswa_id::text'))
            ->leftJoin('akademik.mata_pelajaran as mp', DB::raw('mp.id::text'), '=', DB::raw('dnr.mapel_id::text'))
            ->leftJoin('akademik.kelas as k', function($j) {
                $j->on(DB::raw('k.id::text'), '=', DB::raw('dnr.kelas_id::text'))
                  ->orOn('k.nama_kelas', '=', 'dnr.kelas_id');
            })
            ->where('dnr.tenant_id', $tenantId);

        if (!empty($tahunAjaran) && $tahunAjaran !== 'all') {
            $query->where('dnr.tahun_ajaran', $tahunAjaran);
        }

        if (!empty($semester) && $semester !== 'all') {
            $query->where('dnr.semester', $semester);
        }

        if (!empty($kelasId) && $kelasId !== 'all') {
            $query->where(function($q) use ($kelasId) {
                $q->where(DB::raw('dnr.kelas_id::text'), $kelasId)
                  ->orWhere(DB::raw('k.id::text'), $kelasId)
                  ->orWhere('k.nama_kelas', $kelasId);
            });
        }

        if (!empty($jenjang) && $jenjang !== 'all') {
            $jUpper = strtoupper(trim($jenjang));
            $query->where(function($q) use ($jUpper) {
                $q->where('k.nama_kelas', 'ILIKE', "{$jUpper} %")
                  ->orWhere('k.nama_kelas', 'ILIKE', "KELAS {$jUpper}%")
                  ->orWhere('s.kelas_saat_ini', 'ILIKE', "{$jUpper} %")
                  ->orWhere('dnr.kelas_id', 'ILIKE', "{$jUpper} %");
            });
        }

        $records = $query->select(
            's.nisn',
            's.nis',
            's.nama_lengkap as nama_siswa',
            's.agama',
            DB::raw("COALESCE(k.nama_kelas, dnr.kelas_id, s.kelas_saat_ini, '-') as nama_kelas"),
            'dnr.tahun_ajaran',
            'dnr.semester',
            DB::raw("COALESCE(mp.nama_mata_pelajaran, dnr.nama_detail_nilai_rapor, '-') as nama_mata_pelajaran"),
            DB::raw("COALESCE(mp.kategori, dnr.kategori, 'Muatan Umum') as kategori_mapel"),
            'dnr.nilai_akhir',
            'dnr.predikat',
            'dnr.capaian_kompetensi'
        )
        ->orderBy('nama_kelas', 'asc')
        ->orderBy('s.nama_lengkap', 'asc')
        ->orderBy('dnr.semester', 'asc')
        ->orderBy('nama_mata_pelajaran', 'asc')
        ->get();

        $filename = 'rekap_nilai_rapor_pdss_' . date('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($records) {
            $output = fopen('php://output', 'w');
            // Write UTF-8 BOM for Microsoft Excel compatibility
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

            // Header kolom
            fputcsv($output, [
                'NO',
                'NISN',
                'NIS',
                'NAMA SISWA',
                'AGAMA SISWA',
                'KELAS / ROMBEL',
                'TAHUN AJARAN',
                'SEMESTER',
                'MATA PELAJARAN',
                'KATEGORI MAPEL',
                'NILAI AKHIR',
                'PREDIKAT',
                'CAPAIAN KOMPETENSI / CATATAN'
            ]);

            $no = 1;
            foreach ($records as $r) {
                fputcsv($output, [
                    $no++,
                    trim($r->nisn) ?: '-',
                    trim($r->nis) ?: '-',
                    $r->nama_siswa,
                    $r->agama ?: '-',
                    $r->nama_kelas,
                    $r->tahun_ajaran,
                    $r->semester,
                    $r->nama_mata_pelajaran,
                    $r->kategori_mapel,
                    $r->nilai_akhir !== null ? number_format((float)$r->nilai_akhir, 2, '.', '') : '-',
                    $r->predikat ?: '-',
                    $r->capaian_kompetensi ?: '-'
                ]);
            }

            fclose($output);
        }, $filename, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}


