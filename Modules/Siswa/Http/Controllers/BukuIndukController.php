<?php

namespace Modules\Siswa\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Siswa\Entities\Siswa;
use Modules\Siswa\Entities\SiswaOrangTua;
use Modules\Siswa\Entities\SiswaRegistrasi;
use Modules\Siswa\Entities\SiswaFisikKesehatan;
use Modules\Siswa\Entities\SiswaDokumen;
use Modules\Core\Entities\Tenant;
use Modules\Akademik\Entities\Kelas;
use Modules\Akademik\Entities\Jenjang;
use Modules\Akademik\Entities\Jurusan;
use Modules\Akademik\Entities\TahunAjaran;
use Modules\Akademik\Entities\Angkatan;
use Modules\Akademik\Entities\RefKurikulum;
use Modules\Akademik\Entities\MataPelajaran;

class BukuIndukController extends Controller
{
    /**
     * Tampilkan Dashboard Sentral Buku Induk Siswa (6 NavTab)
     */
    public function index(Request $request): InertiaResponse|JsonResponse
    {
        $user = auth()->user();
        $isSuperAdmin = $user && ($user->isSuperAdmin() || in_array($user->role?->nama_role ?? '', ['super_admin', 'superadmin', 'admin']));
        $filterTenantId = $request->input('tenant_id', '');
        $effectiveTenant = $isSuperAdmin && !empty($filterTenantId) ? $filterTenantId : $user?->tenant_id;

        $activeTab = $request->input('tab', 'buku_induk_siswa');
        $validTabs = ['buku_induk_siswa', 'seting_kurikulum', 'input_nilai_rapor', 'cetak_buku_induk', 'riwayat_kepsek', 'arsip_alumni'];
        if (!in_array($activeTab, $validTabs)) {
            $activeTab = 'buku_induk_siswa';
        }

        $search = trim((string)$request->input('search', ''));
        $filterJenjang = $request->filled('jenjang_id') ? (string)$request->input('jenjang_id') : ($request->filled('jenjang') ? (string)$request->input('jenjang') : '');
        $filterKelas = $request->filled('kelas_id') ? (string)$request->input('kelas_id') : ($request->filled('kelas') ? (string)$request->input('kelas') : '');
        $filterStatus = $request->filled('status') ? (string)$request->input('status') : '';
        $perPage = max(5, min(200, (int)$request->input('per_page', 10)));

        // Base Query untuk Siswa
        $query = ($isSuperAdmin ? Siswa::withoutTenant() : Siswa::query())
            ->when($isSuperAdmin && !empty($filterTenantId), fn($q) => $q->where('tenant_id', $filterTenantId))
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('nama_lengkap', 'ILIKE', "%{$search}%")
                        ->orWhere('nisn', 'ILIKE', "%{$search}%")
                        ->orWhere('nis', 'ILIKE', "%{$search}%");
                });
            })
            ->when(!empty($filterKelas), function ($q) use ($filterKelas) {
                $kObj = DB::table('akademik.kelas')->where('id', $filterKelas)->first();
                $namaKelas = $kObj?->nama_kelas ?? $filterKelas;
                $q->where(function ($sub) use ($filterKelas, $namaKelas) {
                    $sub->where('kelas_saat_ini', $namaKelas)
                        ->orWhere('kelas_saat_ini', $filterKelas);
                });
            })
            ->when(!empty($filterJenjang), function ($q) use ($filterJenjang, $filterTenantId) {
                $kQuery = DB::table('akademik.kelas');
                if (!empty($filterTenantId)) {
                    $kQuery->where('tenant_id', $filterTenantId);
                }
                $matchingKelas = $kQuery->where(function($kq) use ($filterJenjang) {
                    $kq->where('id_jenjang', $filterJenjang)
                       ->orWhere('id', $filterJenjang);
                })->pluck('nama_kelas')->toArray();

                if (!empty($matchingKelas)) {
                    $q->whereIn('kelas_saat_ini', $matchingKelas);
                } else {
                    $q->where(function ($sub) use ($filterJenjang) {
                        $sub->where('kelas_saat_ini', 'ILIKE', "%{$filterJenjang}%")
                            ->orWhere('kelas_saat_ini', $filterJenjang);
                    });
                }
            })
            ->when(!empty($filterStatus), function ($q) use ($filterStatus) {
                if (strtolower($filterStatus) === 'aktif') {
                    $q->where(fn($sub) => $sub->where('is_active', true)->orWhere('status_siswa', 'Aktif'));
                } elseif (strtolower($filterStatus) === 'non-aktif' || strtolower($filterStatus) === 'non_aktif') {
                    $q->where('is_active', false);
                } else {
                    $q->where('status_siswa', 'ILIKE', $filterStatus);
                }
            })
            ->where('is_active', true)
            ->orderBy('nama_lengkap', 'asc');

        // Eager load tenant for Super Admin
        if ($isSuperAdmin) {
            $query->with('tenant:id,nama_sekolah');
        }

        $siswaPaginated = $query->paginate($perPage)->withQueryString();

        // Load map for resolving UUIDs to human readable names
        $jurusanMap = DB::table('akademik.jurusan')->pluck('nama_jurusan', 'id')->toArray();
        $kelasMap = DB::table('akademik.kelas')->pluck('nama_kelas', 'id')->toArray();

        // Standardize rows for frontend
        $siswaPaginated->getCollection()->transform(function ($item) use ($jurusanMap, $kelasMap) {
            $item->status = $item->status_siswa ?: ($item->is_active ? 'Aktif' : 'Non-Aktif');
            $item->nama_sekolah = $item->tenant?->nama_sekolah ?? '-';
            
            // Resolve Jurusan if stored as UUID or string
            $rawJurusan = (string)($item->jurusan ?? '');
            $item->nama_jurusan = $jurusanMap[$rawJurusan] ?? ($rawJurusan ?: '-');
            
            // Resolve Kelas if stored as UUID or string
            $rawKelas = (string)($item->kelas_saat_ini ?? '');
            $item->nama_kelas = $kelasMap[$rawKelas] ?? ($rawKelas ?: '-');
            
            // Clean Date Formatting
            if (!empty($item->tanggal_lahir)) {
                $tglStr = substr((string)$item->tanggal_lahir, 0, 10);
                $time = strtotime($tglStr);
                $item->tanggal_lahir_formatted = $time ? date('d-m-Y', $time) : $tglStr;
            } else {
                $item->tanggal_lahir_formatted = '-';
            }
            
            return $item;
        });

        // Auxiliary Filter Options
        $tenants = [];
        if ($isSuperAdmin) {
            $tenants = DB::table('core.tenants')
                ->where('status', 'active')
                ->orderBy('nama_sekolah', 'asc')
                ->get(['id', 'nama_sekolah']);
        }

        $kelasList = DB::table('akademik.kelas')
            ->where('is_active', true)
            ->when($effectiveTenant, fn($q) => $q->where('tenant_id', $effectiveTenant))
            ->orderBy('nama_kelas', 'asc')
            ->get(['id', 'nama_kelas', 'id_jenjang', 'id_jurusan', 'tenant_id']);

        $jenjangList = DB::table('core.jenjang')
            ->where('is_active', true)
            ->when($effectiveTenant, fn($q) => $q->where(fn($sub) => $sub->where('tenant_id', $effectiveTenant)->orWhereNull('tenant_id')))
            ->orderBy('nama_jenjang', 'asc')
            ->get(['id', 'nama_jenjang']);

        $tahunAjaranList = DB::table('akademik.tahun_ajaran')
            ->where('is_active', true)
            ->when($effectiveTenant, fn($q) => $q->where('tenant_id', $effectiveTenant))
            ->orderBy('nama_tahun_ajaran', 'desc')
            ->get(['id', 'nama_tahun_ajaran as tahun_ajaran']);

        $kurikulumList = DB::table('akademik.ref_kurikulum')
            ->where('is_active', true)
            ->when($effectiveTenant, fn($q) => $q->where(fn($sub) => $sub->where('tenant_id', $effectiveTenant)->orWhereNull('tenant_id')))
            ->orderBy('nama_ref_kurikulum', 'asc')
            ->get(['id', 'nama_ref_kurikulum as nama_kurikulum', 'kategori as tipe_penilaian']);

        $bankMapel = DB::table('akademik.mata_pelajaran')
            ->where('is_active', true)
            ->when($effectiveTenant, fn($q) => $q->where('tenant_id', $effectiveTenant))
            ->orderBy('nama_mata_pelajaran', 'asc')
            ->get(['id', 'id as kode_mapel', 'nama_mata_pelajaran as nama_mapel']);

        if ($request->wantsJson()) {
            return response()->json([
                'success'         => true,
                'data'            => $siswaPaginated,
                'tenants'         => $tenants,
                'kelasList'       => $kelasList,
                'jenjangList'     => $jenjangList,
                'tahunAjaranList' => $tahunAjaranList,
                'kurikulumList'   => $kurikulumList,
                'bankMapel'       => $bankMapel,
            ]);
        }

        return Inertia::render('Siswa/BukuInduk/Index', [
            'activeTab'       => $activeTab,
            'siswaList'       => $siswaPaginated,
            'tenants'         => $tenants,
            'kelasList'       => $kelasList,
            'jenjangList'     => $jenjangList,
            'tahunAjaranList' => $tahunAjaranList,
            'kurikulumList'   => $kurikulumList,
            'bankMapel'       => $bankMapel,
            'isSuperAdmin'    => $isSuperAdmin,
            'userRole'        => $user?->role?->nama_role ?? ($isSuperAdmin ? 'super_admin' : 'admin_sekolah'),
            'filters'         => [
                'tab'        => $activeTab,
                'search'     => $search,
                'jenjang_id' => $filterJenjang,
                'kelas_id'   => $filterKelas,
                'status'     => $filterStatus,
                'tenant_id'  => $filterTenantId,
                'per_page'   => $perPage,
            ],
        ]);
    }

    /**
     * API: Ambil Detail Lengkap Profil Siswa (9 Sub-Tab)
     */
    public function fetchDetailApi(string $id): JsonResponse
    {
        $siswa = Siswa::withoutTenant()->find($id);
        if (!$siswa) {
            return response()->json(['success' => false, 'message' => 'Data siswa tidak ditemukan.'], 404);
        }

        $tenantId = $siswa->tenant_id;

        // 1. Resolve Nama Kelas & Sekolah
        $kelas = DB::table('akademik.kelas')->where('nama_kelas', $siswa->kelas_saat_ini)->first();
        $tenant = DB::table('core.tenants')->where('id', $tenantId)->first();
        $siswaData = $siswa->toArray();
        $siswaData['nama_kelas'] = $kelas?->nama_kelas ?? $siswa->kelas_saat_ini ?? '-';
        $siswaData['nama_sekolah'] = $tenant?->nama_sekolah ?? '-';
        $siswaData['tanggal_lahir_formatted'] = !empty($siswa->tanggal_lahir) ? \Carbon\Carbon::parse($siswa->tanggal_lahir)->translatedFormat('d F Y') : '-';

        // 2. Resolve Wilayah (Kelurahan, Kecamatan, Kota, Provinsi)
        if (!empty($siswa->id_kelurahan)) {
            $wilayah = DB::table('core.kelurahan as kl')
                ->join('core.kecamatan as kc', 'kl.id_kecamatan', '=', 'kc.id_kecamatan')
                ->join('core.kota as kt', 'kc.id_kota', '=', 'kt.id_kota')
                ->join('core.provinsi as pr', 'kt.id_provinsi', '=', 'pr.id_provinsi')
                ->where('kl.id_kelurahan', $siswa->id_kelurahan)
                ->first([
                    'kl.nama_kelurahan',
                    'kc.nama_kecamatan',
                    'kt.nama_kota',
                    'pr.nama_provinsi'
                ]);
            if ($wilayah) {
                $siswaData['nama_kelurahan'] = $wilayah->nama_kelurahan;
                $siswaData['nama_kecamatan'] = $wilayah->nama_kecamatan;
                $siswaData['nama_kota']      = $wilayah->nama_kota;
                $siswaData['nama_provinsi']  = $wilayah->nama_provinsi;
            }
        }

        // 3. Orang Tua / Wali
        $ortu = DB::table('siswa.orang_tua')->where('siswa_id', $id)->get();
        $siswaData['orang_tua'] = $ortu;

        // 4. Fisik & Kesehatan (Semester 1-6)
        $fisik = DB::table('siswa.fisik_kesehatan_siswa')->where('siswa_id', $id)->first();
        $siswaData['fisik'] = $fisik;
        $kesehatan = [];
        if ($fisik && !empty($fisik->detail_semester)) {
            $kesehatan = is_string($fisik->detail_semester) ? json_decode($fisik->detail_semester, true) : (array)$fisik->detail_semester;
        }
        $siswaData['kesehatan'] = $kesehatan;

        // 5. Riwayat Kenaikan & Mutasi Kelas
        $riwayatKelas = DB::table('siswa.riwayat_kenaikan_kelas')
            ->where('siswa_id', $id)
            ->orderBy('created_at', 'asc')
            ->get();
        
        $tahunMasuk = $siswa->created_at ? date('Y/Y', strtotime($siswa->created_at)) : '2023/2024';
        $penempatanAwal = [
            'id'                => 'awal',
            'tahun_ajaran'      => $tahunMasuk,
            'nama_kelas_tujuan' => $riwayatKelas->first()?->dari_kelas ?? $siswaData['nama_kelas'],
            'nama_pelaku'       => 'Admin Sistem',
            'jenis_aksi'        => 'penempatan_awal',
            'catatan'           => 'Penempatan Awal Siswa Baru',
            'created_at'        => $siswa->created_at,
        ];
        $allRiwayat = array_merge([$penempatanAwal], $riwayatKelas->toArray());
        $siswaData['riwayat_kelas'] = $allRiwayat;

        // 6. Nilai Rapor
        $nilaiRapor = DB::table('akademik.detail_nilai_rapor as dnr')
            ->leftJoin('akademik.mata_pelajaran as mp', function ($join) {
                $join->on(DB::raw('dnr.mapel_id'), '=', DB::raw('mp.id::text'));
            })
            ->leftJoin('akademik.kelas as k', function ($join) {
                $join->on(DB::raw('dnr.kelas_id'), '=', DB::raw('k.id::text'));
            })
            ->where('dnr.siswa_id', $id)
            ->where('dnr.is_active', true)
            ->orderBy('dnr.tahun_ajaran', 'asc')
            ->orderBy('dnr.semester', 'asc')
            ->get([
                'dnr.*',
                'mp.nama_mata_pelajaran as nama_mapel',
                'mp.kategori as kelompok',
                'k.nama_kelas'
            ]);
        $siswaData['nilai_rapor'] = $nilaiRapor;

        // 7. Prestasi Siswa
        $prestasi = DB::table('kesiswaan.prestasi_siswa as ps')
            ->join('kesiswaan.prestasi_siswa_anggota as psa', 'ps.id', '=', 'psa.id_prestasi')
            ->where('psa.id_siswa', $id)
            ->where('ps.is_active', true)
            ->orderBy('ps.tanggal_lomba', 'desc')
            ->get(['ps.*']);
        $siswaData['prestasi'] = $prestasi;

        // 8. Catatan Pelanggaran / BK
        $pelanggaran = DB::table('bk.catatan_bk')
            ->where('id_siswa', $id)
            ->where('is_active', true)
            ->orderBy('tanggal_konseling', 'desc')
            ->get([
                'id',
                'tanggal_konseling as tanggal_kejadian',
                'nama_catatan_bk as nama_pelanggaran',
                'jenis_kasus as kategori_pelanggaran',
                'catatan as deskripsi',
                'tindak_lanjut',
                'status_kasus',
            ]);
        $siswaData['pelanggaran'] = $pelanggaran;

        // 9. Riwayat Beasiswa
        $beasiswa = DB::table('siswa.riwayat_beasiswa')
            ->where('siswa_id', $id)
            ->orderBy('tahun_mulai', 'desc')
            ->get();
        $beasiswaTransformed = $beasiswa->map(function ($b) {
            $bArr = (array)$b;
            $bArr['tahun_menerima'] = $b->tahun_mulai ? (string)$b->tahun_mulai : ($b->tahun_selesai ? (string)$b->tahun_selesai : '-');
            return $bArr;
        });
        $siswaData['beasiswa'] = $beasiswaTransformed;

        // 10. Tracer Study (Kuliah & Pekerjaan)
        $tracerKuliah = DB::table('tracer.riwayat_kuliah')
            ->where('siswa_id', $id)
            ->orderBy('tahun_masuk', 'desc')
            ->get();
        $tracerPekerjaan = DB::table('tracer.riwayat_pekerjaan')
            ->where('siswa_id', $id)
            ->orderBy('tahun_mulai', 'desc')
            ->get();
        $siswaData['tracer_kuliah']    = $tracerKuliah;
        $siswaData['tracer_pekerjaan'] = $tracerPekerjaan;

        // 11. Dokumen Berkas
        $dokumen = DB::table('siswa.dokumen')->where('siswa_id', $id)->get();
        $siswaData['dokumen'] = $dokumen;

        return response()->json([
            'success' => true,
            'data'    => $siswaData,
        ]);
    }

    /**
     * API: Simpan Riwayat Beasiswa
     */
    public function storeBeasiswa(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'siswa_id'       => 'required|uuid',
            'nama_beasiswa'  => 'required|string|max:255',
            'penyelenggara'  => 'required|string|max:255',
            'tahun_menerima' => 'nullable|string|max:10',
            'tahun_mulai'    => 'nullable|integer',
            'tahun_selesai'  => 'nullable|integer',
            'nominal'        => 'nullable|numeric',
            'keterangan'     => 'nullable|string',
        ]);

        $siswa = Siswa::withoutTenant()->findOrFail($validated['siswa_id']);
        $tahunMulai = !empty($validated['tahun_mulai']) ? (int)$validated['tahun_mulai'] : (!empty($validated['tahun_menerima']) ? (int)$validated['tahun_menerima'] : (int)date('Y'));

        $id = (string) Str::uuid();
        DB::table('siswa.riwayat_beasiswa')->insert([
            'id'             => $id,
            'tenant_id'      => $siswa->tenant_id,
            'siswa_id'       => $validated['siswa_id'],
            'nama_beasiswa'  => $validated['nama_beasiswa'],
            'penyelenggara'  => $validated['penyelenggara'],
            'tahun_mulai'    => $tahunMulai,
            'tahun_selesai'  => !empty($validated['tahun_selesai']) ? (int)$validated['tahun_selesai'] : $tahunMulai,
            'nominal'        => $validated['nominal'] ?? 0,
            'keterangan'     => $validated['keterangan'] ?? null,
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Data beasiswa berhasil ditambahkan.',
        ]);
    }

    /**
     * API: Hapus Riwayat Beasiswa
     */
    public function destroyBeasiswa(string $id): JsonResponse
    {
        DB::table('siswa.riwayat_beasiswa')->where('id', $id)->delete();
        return response()->json([
            'success' => true,
            'message' => 'Data beasiswa berhasil dihapus.',
        ]);
    }

    /**
     * API: Load Pemetaan Kurikulum
     */
    public function loadKurikulum(Request $request): JsonResponse
    {
        $kelasId     = $request->input('kelas_id', '');
        $tahunAjaran = $request->input('tahun_ajaran', '');
        $semester    = $request->input('semester', '');
        $tenantId    = $request->input('tenant_id') ?: auth()->user()?->tenant_id;

        if (empty($kelasId) || empty($tahunAjaran) || empty($semester)) {
            return response()->json([
                'success' => true,
                'groups'  => [],
                'active_kurikulum_id' => '',
                'is_locked' => false,
            ]);
        }

        $existing = DB::table('akademik.pemetaan_mapel')
            ->where('kelas_id', $kelasId)
            ->where('tahun_ajaran', $tahunAjaran)
            ->where('semester', $semester)
            ->when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))
            ->where('is_active', true)
            ->get();

        $activeKurikulum = DB::table('akademik.kelas_kurikulum')
            ->where('kelas_id', $kelasId)
            ->where('tahun_ajaran', $tahunAjaran)
            ->when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))
            ->first();

        // Cek lock kurikulum
        $kunci = DB::table('akademik.kunci_akademik')
            ->where('tahun_ajaran', $tahunAjaran)
            ->where('semester', $semester)
            ->when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))
            ->first();
        $isLocked = $kunci ? (bool)$kunci->is_locked_kurikulum : false;

        // Group by kelompok_id
        $groups = [];
        $grouped = $existing->groupBy('kelompok_id');
        foreach ($grouped as $kelompokId => $items) {
            $groups[] = [
                'kelompok_id' => $kelompokId,
                'mapel_ids'   => $items->pluck('mapel_id')->toArray(),
                'searchQuery' => '',
            ];
        }

        if (empty($groups)) {
            $groups = [
                ['kelompok_id' => 'Kelompok A (Umum)', 'mapel_ids' => [], 'searchQuery' => ''],
                ['kelompok_id' => 'Kelompok B (Kejuruan/Peminatan)', 'mapel_ids' => [], 'searchQuery' => ''],
            ];
        }

        return response()->json([
            'success'             => true,
            'groups'              => $groups,
            'active_kurikulum_id' => $activeKurikulum?->kurikulum_id ?? '',
            'is_locked'           => $isLocked,
        ]);
    }

    /**
     * API: Simpan Pemetaan Kurikulum
     */
    public function saveKurikulum(Request $request): JsonResponse
    {
        $kelasId      = $request->input('kelas_id');
        $tahunAjaran  = $request->input('tahun_ajaran');
        $semester     = $request->input('semester');
        $kurikulumId  = $request->input('kurikulum_id');
        $groups       = $request->input('groups', []);
        $tenantId     = $request->input('tenant_id') ?: auth()->user()?->tenant_id;

        if (empty($kelasId) || empty($tahunAjaran) || empty($semester)) {
            return response()->json(['success' => false, 'message' => 'Parameter tidak lengkap.'], 400);
        }

        DB::beginTransaction();
        try {
            // 1. Non-aktifkan / hapus mapping lama
            DB::table('akademik.pemetaan_mapel')
                ->where('kelas_id', $kelasId)
                ->where('tahun_ajaran', $tahunAjaran)
                ->where('semester', $semester)
                ->when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))
                ->delete();

            // 2. Insert mapping baru
            foreach ($groups as $grp) {
                $kelompokId = $grp['kelompok_id'] ?? 'Kelompok Umum';
                $mapelIds = $grp['mapel_ids'] ?? [];
                foreach ($mapelIds as $mapelId) {
                    DB::table('akademik.pemetaan_mapel')->insert([
                        'id'           => (string) Str::uuid(),
                        'tenant_id'    => $tenantId,
                        'kelas_id'     => $kelasId,
                        'tahun_ajaran' => $tahunAjaran,
                        'semester'     => $semester,
                        'kelompok_id'  => $kelompokId,
                        'mapel_id'     => $mapelId,
                        'is_active'    => true,
                        'created_at'   => now(),
                        'updated_at'   => now(),
                    ]);
                }
            }

            // 3. Simpan relasi kelas kurikulum
            if (!empty($kurikulumId)) {
                DB::table('akademik.kelas_kurikulum')->updateOrInsert(
                    ['kelas_id' => $kelasId, 'tahun_ajaran' => $tahunAjaran, 'tenant_id' => $tenantId],
                    ['kurikulum_id' => $kurikulumId, 'is_active' => true, 'updated_at' => now()]
                );
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Seting kurikulum kelas berhasil disimpan.']);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Gagal menyimpan kurikulum: ' . $e->getMessage()], 500);
        }
    }

    /**
     * API: Salin Kurikulum dari Kelas Sumber ke Kelas Tujuan
     */
    public function copyKurikulum(Request $request): JsonResponse
    {
        $sourceKelasId = $request->input('source_kelas_id');
        $targetKelasId = $request->input('target_kelas_id');
        $tahunAjaran   = $request->input('tahun_ajaran');
        $semester      = $request->input('semester');
        $tenantId      = $request->input('tenant_id') ?: auth()->user()?->tenant_id;

        $sourceMappings = DB::table('akademik.pemetaan_mapel')
            ->where('kelas_id', $sourceKelasId)
            ->where('tahun_ajaran', $tahunAjaran)
            ->where('semester', $semester)
            ->when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))
            ->where('is_active', true)
            ->get();

        if ($sourceMappings->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'Kelas sumber belum memiliki pemetaan kurikulum.'], 400);
        }

        DB::beginTransaction();
        try {
            DB::table('akademik.pemetaan_mapel')
                ->where('kelas_id', $targetKelasId)
                ->where('tahun_ajaran', $tahunAjaran)
                ->where('semester', $semester)
                ->when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))
                ->delete();

            foreach ($sourceMappings as $sm) {
                DB::table('akademik.pemetaan_mapel')->insert([
                    'id'           => (string) Str::uuid(),
                    'tenant_id'    => $tenantId,
                    'kelas_id'     => $targetKelasId,
                    'tahun_ajaran' => $tahunAjaran,
                    'semester'     => $semester,
                    'kelompok_id'  => $sm->kelompok_id,
                    'mapel_id'     => $sm->mapel_id,
                    'is_active'    => true,
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ]);
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Kurikulum berhasil disalin ke kelas tujuan.']);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Gagal menyalin kurikulum: ' . $e->getMessage()], 500);
        }
    }

    /**
     * API: Toggle Lock Kurikulum / Nilai
     */
    public function toggleLock(Request $request): JsonResponse
    {
        $tipe        = $request->input('tipe', 'kurikulum');
        $tahunAjaran = $request->input('tahun_ajaran');
        $semester    = $request->input('semester');
        $tenantId    = $request->input('tenant_id') ?: auth()->user()?->tenant_id;

        $existing = DB::table('akademik.kunci_akademik')
            ->where('tahun_ajaran', $tahunAjaran)
            ->where('semester', $semester)
            ->when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))
            ->first();

        $lockField = $tipe === 'nilai' ? 'is_locked_nilai' : 'is_locked_kurikulum';
        $newLocked = $existing ? !$existing->$lockField : true;

        if ($existing) {
            DB::table('akademik.kunci_akademik')
                ->where('id', $existing->id)
                ->update([
                    $lockField   => $newLocked,
                    'updated_at' => now(),
                ]);
        } else {
            DB::table('akademik.kunci_akademik')->insert([
                'id'                  => (string) Str::uuid(),
                'tenant_id'           => $tenantId,
                'tahun_ajaran'        => $tahunAjaran,
                'semester'            => $semester,
                'is_locked_kurikulum' => $tipe === 'kurikulum' ? $newLocked : false,
                'is_locked_nilai'     => $tipe === 'nilai' ? $newLocked : false,
                'is_active'           => true,
                'created_at'          => now(),
                'updated_at'          => now(),
            ]);
        }

        return response()->json([
            'success'   => true,
            'is_locked' => $newLocked,
            'message'   => "Kunci {$tipe} berhasil diubah menjadi " . ($newLocked ? 'Terkunci' : 'Terbuka') . '.',
        ]);
    }

    /**
     * API: Load Nilai Rapor Grid
     */
    public function loadNilaiRapor(Request $request): JsonResponse
    {
        $kelasId     = $request->input('kelas_id');
        $tahunAjaran = $request->input('tahun_ajaran');
        $semester    = $request->input('semester');
        $tenantId    = $request->input('tenant_id') ?: auth()->user()?->tenant_id;

        if (empty($kelasId) || empty($tahunAjaran) || empty($semester)) {
            return response()->json([
                'success'   => true,
                'students'  => [],
                'subjects'  => [],
                'is_locked' => false,
            ]);
        }

        // 1. Ambil Mapel yang Dipetakan
        $subjects = DB::table('akademik.pemetaan_mapel as pm')
            ->join('akademik.mata_pelajaran as mp', function ($join) {
                $join->on(DB::raw('pm.mapel_id'), '=', DB::raw('mp.id::text'));
            })
            ->where('pm.kelas_id', $kelasId)
            ->where('pm.tahun_ajaran', $tahunAjaran)
            ->where('pm.semester', $semester)
            ->when($tenantId, fn($q) => $q->where('pm.tenant_id', $tenantId))
            ->where('pm.is_active', true)
            ->orderBy('mp.nama_mata_pelajaran', 'asc')
            ->get(['mp.id as mapel_id', 'mp.nama_mata_pelajaran as nama_mapel', 'mp.kategori as kelompok']);

        // 2. Ambil Siswa di Kelas Tersebut
        $kelas = DB::table('akademik.kelas')->where('id', $kelasId)->first();
        $namaKelas = $kelas?->nama_kelas ?? '';

        $students = DB::table('siswa.siswa')
            ->where('is_active', true)
            ->when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))
            ->when($namaKelas !== '', fn($q) => $q->where('kelas_saat_ini', $namaKelas))
            ->orderBy('nama_lengkap', 'asc')
            ->get(['id', 'nama_lengkap', 'nisn', 'nis', 'agama']);

        // 3. Ambil Nilai Siswa
        $grades = DB::table('akademik.detail_nilai_rapor')
            ->where('kelas_id', $kelasId)
            ->where('tahun_ajaran', $tahunAjaran)
            ->where('semester', $semester)
            ->when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))
            ->where('is_active', true)
            ->get();

        $gradeMap = [];
        foreach ($grades as $g) {
            $gradeMap[$g->siswa_id][$g->mapel_id] = [
                'id'          => $g->id,
                'nilai_akhir' => (float)$g->nilai_akhir,
                'predikat'    => $g->predikat,
                'capaian'     => $g->deskripsi_capaian ?? $g->capaian_kompetensi ?? '',
            ];
        }

        $formattedStudents = [];
        foreach ($students as $stu) {
            $stuGrades = $gradeMap[$stu->id] ?? [];
            $totalNilai = 0;
            $countNilai = 0;
            foreach ($stuGrades as $mId => $gData) {
                if (isset($gData['nilai_akhir']) && is_numeric($gData['nilai_akhir'])) {
                    $totalNilai += $gData['nilai_akhir'];
                    $countNilai++;
                }
            }
            $avg = $countNilai > 0 ? round($totalNilai / $countNilai, 1) : '-';

            $formattedStudents[] = [
                'id'           => $stu->id,
                'nama_lengkap' => $stu->nama_lengkap,
                'nisn'         => $stu->nisn,
                'nis'          => $stu->nis,
                'agama'        => $stu->agama,
                'average'      => $avg,
                'grades'       => $stuGrades,
            ];
        }

        $kunci = DB::table('akademik.kunci_akademik')
            ->where('tahun_ajaran', $tahunAjaran)
            ->where('semester', $semester)
            ->when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))
            ->first();
        $isLocked = $kunci ? (bool)$kunci->is_locked_nilai : false;

        $activeKurikulum = DB::table('akademik.kelas_kurikulum as kk')
            ->join('akademik.ref_kurikulum as rk', function ($join) {
                $join->on(DB::raw('kk.kurikulum_id'), '=', DB::raw('rk.id::text'));
            })
            ->where('kk.kelas_id', $kelasId)
            ->where('kk.tahun_ajaran', $tahunAjaran)
            ->when($tenantId, fn($q) => $q->where('kk.tenant_id', $tenantId))
            ->first(['rk.nama_ref_kurikulum as nama_kurikulum']);

        return response()->json([
            'success'        => true,
            'students'       => $formattedStudents,
            'subjects'       => $subjects,
            'is_locked'      => $isLocked,
            'kurikulum_nama' => $activeKurikulum?->nama_kurikulum ?? 'Kurikulum Merdeka',
        ]);
    }

    /**
     * API: Simpan Detail Nilai Rapor Siswa
     */
    public function saveNilaiRapor(Request $request): JsonResponse
    {
        $kelasId     = $request->input('kelas_id');
        $tahunAjaran = $request->input('tahun_ajaran');
        $semester    = $request->input('semester');
        $siswaId     = $request->input('siswa_id');
        $grades      = $request->input('grades', []); // array mapel_id => [nilai_akhir, predikat, capaian]
        $tenantId    = $request->input('tenant_id') ?: auth()->user()?->tenant_id;

        if (empty($kelasId) || empty($tahunAjaran) || empty($semester) || empty($siswaId)) {
            return response()->json(['success' => false, 'message' => 'Parameter input nilai tidak lengkap.'], 400);
        }

        DB::beginTransaction();
        try {
            foreach ($grades as $mapelId => $gradeData) {
                $nilaiAkhir = isset($gradeData['nilai_akhir']) && $gradeData['nilai_akhir'] !== '' ? (float)$gradeData['nilai_akhir'] : null;
                if ($nilaiAkhir !== null) {
                    $predikat = $gradeData['predikat'] ?? ($nilaiAkhir >= 85 ? 'A' : ($nilaiAkhir >= 75 ? 'B' : ($nilaiAkhir >= 60 ? 'C' : 'D')));
                    $capaian  = $gradeData['capaian'] ?? '';

                    DB::table('akademik.detail_nilai_rapor')->updateOrInsert(
                        [
                            'tenant_id'    => $tenantId,
                            'siswa_id'     => $siswaId,
                            'kelas_id'     => $kelasId,
                            'mapel_id'     => $mapelId,
                            'tahun_ajaran' => $tahunAjaran,
                            'semester'     => $semester,
                        ],
                        [
                            'nilai_akhir'        => $nilaiAkhir,
                            'predikat'           => $predikat,
                            'deskripsi_capaian'  => $capaian,
                            'is_active'          => true,
                            'updated_at'         => now(),
                        ]
                    );
                }
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Nilai siswa berhasil disimpan.']);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Gagal menyimpan nilai: ' . $e->getMessage()], 500);
        }
    }

    /**
     * API: Hapus Nilai Siswa
     */
    public function deleteNilaiRapor(Request $request): JsonResponse
    {
        $kelasId     = $request->input('kelas_id');
        $tahunAjaran = $request->input('tahun_ajaran');
        $semester    = $request->input('semester');
        $siswaId     = $request->input('siswa_id');
        $tenantId    = $request->input('tenant_id') ?: auth()->user()?->tenant_id;

        DB::table('akademik.detail_nilai_rapor')
            ->where('kelas_id', $kelasId)
            ->where('tahun_ajaran', $tahunAjaran)
            ->where('semester', $semester)
            ->where('siswa_id', $siswaId)
            ->when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))
            ->delete();

        return response()->json(['success' => true, 'message' => 'Nilai siswa berhasil dihapus.']);
    }

    /**
     * API: Ekspor / Unduh Format Nilai Rapor Sesuai Kurikulum (.xlsx)
     */
    public function exportNilaiExcel(Request $request)
    {
        $kelasId     = $request->input('kelas_id');
        $tahunAjaran = $request->input('tahun_ajaran');
        $semester    = $request->input('semester');
        $tenantId    = $request->input('tenant_id') ?: auth()->user()?->tenant_id;

        if (empty($kelasId) || empty($tahunAjaran) || empty($semester)) {
            abort(400, 'Parameter kelas_id, tahun_ajaran, dan semester wajib diisi.');
        }

        $namaKelas = $kelasId;
        if (\Illuminate\Support\Str::isUuid($kelasId)) {
            $kObj = DB::table('akademik.kelas')->where('id', $kelasId)->first();
            if ($kObj) $namaKelas = $kObj->nama_kelas;
        }

        // 1. Kurikulum Aktif
        $activeKurikulum = DB::table('akademik.kelas_kurikulum as kk')
            ->join('akademik.ref_kurikulum as rk', function ($join) {
                $join->on(DB::raw('kk.kurikulum_id'), '=', DB::raw('rk.id::text'));
            })
            ->where('kk.kelas_id', $kelasId)
            ->where('kk.tahun_ajaran', $tahunAjaran)
            ->when($tenantId, fn($q) => $q->where('kk.tenant_id', $tenantId))
            ->first(['rk.nama_ref_kurikulum as nama_kurikulum']);
        $namaKurikulum = $activeKurikulum?->nama_kurikulum ?? 'Kurikulum Merdeka';
        $isMerdeka = stripos($namaKurikulum, 'merdeka') !== false;

        // 2. Daftar Mapel
        $subjects = DB::table('akademik.pemetaan_mapel as pm')
            ->join('akademik.mata_pelajaran as mp', function ($join) {
                $join->on(DB::raw('pm.mapel_id'), '=', DB::raw('mp.id::text'));
            })
            ->where('pm.kelas_id', $kelasId)
            ->where('pm.tahun_ajaran', $tahunAjaran)
            ->where('pm.semester', $semester)
            ->when($tenantId, fn($q) => $q->where('pm.tenant_id', $tenantId))
            ->where('pm.is_active', true)
            ->where('mp.is_active', true)
            ->orderBy('mp.nama_mata_pelajaran', 'asc')
            ->get(['pm.mapel_id', 'mp.nama_mata_pelajaran as nama_mapel']);

        if ($subjects->isEmpty()) {
            $subjects = DB::table('akademik.mata_pelajaran')
                ->where('is_active', true)
                ->when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))
                ->orderBy('nama_mata_pelajaran', 'asc')
                ->get(['id as mapel_id', 'nama_mata_pelajaran as nama_mapel']);
        }

        // 3. Daftar Siswa
        $students = DB::table('siswa.siswa')
            ->where(function ($q) use ($kelasId, $namaKelas) {
                $q->where('kelas_saat_ini', $namaKelas)
                  ->orWhere('kelas_saat_ini', $kelasId);
            })
            ->when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))
            ->where('is_active', true)
            ->orderBy('nama_lengkap', 'asc')
            ->get();

        // 4. Existing Grades
        $grades = DB::table('akademik.detail_nilai_rapor')
            ->where('kelas_id', $kelasId)
            ->where('tahun_ajaran', $tahunAjaran)
            ->where('semester', $semester)
            ->when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))
            ->where('is_active', true)
            ->get();

        $gradeMap = [];
        foreach ($grades as $g) {
            $gradeMap[$g->siswa_id][$g->mapel_id] = [
                'nilai_akhir' => $g->nilai_akhir,
                'predikat'    => $g->predikat,
                'capaian'     => $g->deskripsi_capaian ?? $g->capaian_kompetensi ?? '',
            ];
        }

        // 5. Generate XLSX
        $dataRows = [];
        $header = ['Siswa ID', 'NISN', 'NIS', 'Nama Lengkap Siswa'];
        foreach ($subjects as $sub) {
            $header[] = "{$sub->nama_mapel} [Nilai_ID:{$sub->mapel_id}]";
            if ($isMerdeka) {
                $header[] = "{$sub->nama_mapel} [Capaian_ID:{$sub->mapel_id}]";
            } else {
                $header[] = "{$sub->nama_mapel} [Predikat_ID:{$sub->mapel_id}]";
            }
        }
        $dataRows[] = $header;

        foreach ($students as $stu) {
            $row = [
                $stu->id,
                $stu->nisn ?? '-',
                $stu->nis ?? '-',
                $stu->nama_lengkap,
            ];

            foreach ($subjects as $sub) {
                $g = $gradeMap[$stu->id][$sub->mapel_id] ?? null;
                $row[] = $g && isset($g['nilai_akhir']) && $g['nilai_akhir'] !== null ? $g['nilai_akhir'] : '';
                if ($isMerdeka) {
                    $row[] = $g['capaian'] ?? '';
                } else {
                    $row[] = $g['predikat'] ?? '';
                }
            }
            $dataRows[] = $row;
        }

        $safeKelas = str_replace(['/', '\\', ' '], '_', $namaKelas);
        $safeTA = str_replace(['/', '\\', ' '], '-', $tahunAjaran);
        $filename = "Format_Nilai_{$safeKelas}_{$safeTA}_{$semester}.xlsx";

        $xlsx = \Shuchkin\SimpleXLSXGen::fromArray($dataRows);
        return response((string) $xlsx, 200, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control'       => 'max-age=0',
        ]);
    }

    /**
     * API: Impor Nilai Rapor dari Excel (.xlsx / .csv)
     */
    public function importNilaiExcel(Request $request): JsonResponse
    {
        $kelasId     = $request->input('kelas_id');
        $tahunAjaran = $request->input('tahun_ajaran');
        $semester    = $request->input('semester');
        $tenantId    = $request->input('tenant_id') ?: auth()->user()?->tenant_id;

        if (empty($kelasId) || empty($tahunAjaran) || empty($semester)) {
            return response()->json(['success' => false, 'message' => 'Parameter kelas_id, tahun_ajaran, dan semester wajib diisi.'], 400);
        }

        // Cek status kunci nilai
        $kunci = DB::table('akademik.kunci_akademik')
            ->where('tahun_ajaran', $tahunAjaran)
            ->where('semester', $semester)
            ->when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))
            ->first();
        if ($kunci && $kunci->is_locked_nilai) {
            return response()->json(['success' => false, 'message' => 'Gagal mengimpor. Input Nilai Rapor untuk periode ini sedang DIKUNCI.'], 403);
        }

        if (!$request->hasFile('file') || !$request->file('file')->isValid()) {
            return response()->json(['success' => false, 'message' => 'Berkas file impor tidak valid atau kosong.'], 400);
        }

        $file = $request->file('file');
        $realPath = $file->getRealPath();
        $ext = strtolower($file->getClientOriginalExtension());

        $rows = [];
        if (in_array($ext, ['xlsx', 'xls']) || ($xlsx = \Shuchkin\SimpleXLSX::parse($realPath))) {
            if (isset($xlsx) && $xlsx) {
                $rows = $xlsx->rows();
            } elseif ($parsed = \Shuchkin\SimpleXLSX::parse($realPath)) {
                $rows = $parsed->rows();
            }
        }

        if (empty($rows)) {
            // Fallback to CSV parser
            $handle = fopen($realPath, 'r');
            if ($handle) {
                $bom = fread($handle, 3);
                if ($bom !== "\xEF\xBB\xBF") {
                    rewind($handle);
                }
                while (($line = fgetcsv($handle, 10000, ',')) !== false) {
                    $rows[] = $line;
                }
                fclose($handle);
            }
        }

        if (empty($rows) || count($rows) < 2) {
            return response()->json(['success' => false, 'message' => 'Berkas Excel kosong atau tidak memiliki baris data.'], 400);
        }

        $header = array_shift($rows);
        if (!$header || count($header) < 4) {
            return response()->json(['success' => false, 'message' => 'Format kolom header file tidak sesuai template.'], 400);
        }

        $colMappings = [];
        foreach ($header as $colIdx => $colName) {
            if ($colIdx < 4) continue;
            if (preg_match('/\[(?:(Nilai|Capaian|Predikat)_ID:)?([^\]]+)\]/i', (string)$colName, $matches)) {
                $type = !empty($matches[1]) ? strtolower($matches[1]) : (stripos((string)$colName, 'capaian') !== false ? 'capaian' : (stripos((string)$colName, 'predikat') !== false ? 'predikat' : 'nilai'));
                $mapelId = trim($matches[2]);
                $colMappings[$colIdx] = [
                    'mapel_id' => $mapelId,
                    'type'     => $type,
                ];
            }
        }

        if (empty($colMappings)) {
            return response()->json(['success' => false, 'message' => 'Tidak ditemukan kolom mata pelajaran yang valid dalam template.'], 400);
        }

        DB::beginTransaction();
        $importedCount = 0;
        $studentCount = 0;

        try {
            foreach ($rows as $row) {
                if (empty(array_filter($row))) continue;
                $siswaId = trim((string)($row[0] ?? ''));
                $nisn = trim((string)($row[1] ?? ''));

                if (!\Illuminate\Support\Str::isUuid($siswaId)) {
                    $stu = DB::table('siswa.siswa')->where('nisn', $nisn)->first();
                    if (!$stu) continue;
                    $siswaId = $stu->id;
                }

                $studentGrades = [];
                foreach ($colMappings as $colIdx => $colInfo) {
                    $val = isset($row[$colIdx]) ? trim((string)$row[$colIdx]) : '';
                    if ($val === '' || strtoupper($val) === 'N/A') continue;
                    $studentGrades[$colInfo['mapel_id']][$colInfo['type']] = $val;
                }

                if (!empty($studentGrades)) {
                    $studentCount++;
                    foreach ($studentGrades as $mapelId => $vals) {
                        $nilaiAkhir = isset($vals['nilai']) && is_numeric($vals['nilai']) ? (float)$vals['nilai'] : null;
                        if ($nilaiAkhir !== null) {
                            $predikat = $vals['predikat'] ?? ($nilaiAkhir >= 85 ? 'A' : ($nilaiAkhir >= 75 ? 'B' : ($nilaiAkhir >= 60 ? 'C' : 'D')));
                            $capaian = $vals['capaian'] ?? '';

                            DB::table('akademik.detail_nilai_rapor')->updateOrInsert(
                                [
                                    'tenant_id'    => $tenantId,
                                    'siswa_id'     => $siswaId,
                                    'kelas_id'     => $kelasId,
                                    'mapel_id'     => $mapelId,
                                    'tahun_ajaran' => $tahunAjaran,
                                    'semester'     => $semester,
                                ],
                                [
                                    'nilai_akhir'        => $nilaiAkhir,
                                    'predikat'           => $predikat,
                                    'deskripsi_capaian'  => $capaian,
                                    'is_active'          => true,
                                    'updated_at'         => now(),
                                ]
                            );
                            $importedCount++;
                        }
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Berhasil mengimpor {$importedCount} nilai mata pelajaran untuk {$studentCount} siswa.",
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Gagal mengimpor nilai: ' . $e->getMessage()], 500);
        }
    }

    /**
     * API: Load Matriks Cetak Buku Induk Multi-Tahun
     */
    public function loadMatrixCetak(Request $request): JsonResponse
    {
        $tahunAjaran = $request->input('tahun_ajaran', '');
        $kelasId     = $request->input('kelas_id', '');
        $status      = $request->input('status', '');
        $tenantId    = $request->input('tenant_id') ?: auth()->user()?->tenant_id;

        $namaKelas = $kelasId;
        if (!empty($kelasId) && \Illuminate\Support\Str::isUuid($kelasId)) {
            $kelas = DB::table('akademik.kelas')->where('id', $kelasId)->first();
            if ($kelas) {
                $namaKelas = $kelas->nama_kelas;
            }
        }

        $students = DB::table('siswa.siswa')
            ->where('is_active', true)
            ->when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))
            ->when(!empty($namaKelas), fn($q) => $q->where(function($sub) use ($namaKelas, $kelasId) {
                $sub->where('kelas_saat_ini', $namaKelas)
                    ->orWhere('kelas_saat_ini', $kelasId);
            }))
            ->when($status !== '', function($q) use ($status) {
                if (strtolower($status) === 'aktif') {
                    $q->where(fn($sub) => $sub->where('is_active', true)->orWhere('status_siswa', 'Aktif'));
                } else {
                    $q->where('status_siswa', 'ILIKE', $status);
                }
            })
            ->orderBy('nama_lengkap', 'asc')
            ->get();

        $studentIds = $students->pluck('id')->toArray();

        // Ambil ketersediaan nilai rapor untuk memeriksa semester ganjil/genap
        $grades = DB::table('akademik.detail_nilai_rapor')
            ->whereIn('siswa_id', $studentIds)
            ->where('is_active', true)
            ->get(['siswa_id', 'tahun_ajaran', 'semester']);

        $gradeAvailability = [];
        foreach ($grades as $g) {
            $gradeAvailability[$g->siswa_id][$g->tahun_ajaran][$g->semester] = true;
        }

        // Ambil riwayat kenaikan kelas
        $riwayatKelas = DB::table('siswa.riwayat_kenaikan_kelas')
            ->whereIn('siswa_id', $studentIds)
            ->orderBy('created_at', 'asc')
            ->get();

        $riwayatMap = [];
        foreach ($riwayatKelas as $rk) {
            $riwayatMap[$rk->siswa_id][] = $rk;
        }

        // Ambil daftar tahun ajaran terdaftar
        $allTA = DB::table('akademik.tahun_ajaran')
            ->when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))
            ->orderBy('nama_tahun_ajaran', 'asc')
            ->pluck('nama_tahun_ajaran')
            ->toArray();

        if (empty($allTA)) {
            $allTA = ['2024/2025', '2025/2026', '2026/2027'];
        }

        $matrix = [];
        $maxYears = 3;

        foreach ($students as $stu) {
            $tahunMasuk = $stu->created_at ? date('Y', strtotime($stu->created_at)) : '2024';
            $stuRiwayat = $riwayatMap[$stu->id] ?? [];
            $years = [];

            for ($yr = 1; $yr <= 3; $yr++) {
                $idxTA = count($allTA) >= 3 ? (count($allTA) - 4 + $yr) : ($yr - 1);
                $startYr = 2023 + $yr;
                $endYr = 2024 + $yr;
                $currTA = $allTA[max(0, min(count($allTA) - 1, $idxTA))] ?? "{$startYr}/{$endYr}";

                $kelasName = '';
                if ($yr === 3) {
                    $kelasName = $stu->kelas_saat_ini ?: 'XII ' . ($stu->jurusan ?: 'Umum') . ' 1';
                } elseif (isset($stuRiwayat[$yr - 1])) {
                    $kelasName = $stuRiwayat[$yr - 1]->ke_kelas ?? $stuRiwayat[$yr - 1]->dari_kelas ?? '';
                }

                if (!$kelasName) {
                    $prefix = $yr === 1 ? 'X ' : ($yr === 2 ? 'XI ' : 'XII ');
                    $kelasName = $prefix . ($stu->jurusan ?: 'Umum') . ' 1';
                }

                $hasGanjil = isset($gradeAvailability[$stu->id][$currTA]['Ganjil']);
                $hasGenap  = isset($gradeAvailability[$stu->id][$currTA]['Genap']);

                $years[] = [
                    'year_num'     => $yr,
                    'nama_kelas'   => $kelasName,
                    'tahun_ajaran' => $currTA,
                    'has_ganjil'   => $hasGanjil,
                    'has_genap'    => $hasGenap,
                ];
            }

            $matrix[] = [
                'id'           => $stu->id,
                'nama_lengkap' => $stu->nama_lengkap,
                'nisn'         => $stu->nisn,
                'nis'          => $stu->nis,
                'tahun_masuk'  => $tahunMasuk,
                'years'        => $years,
            ];
        }

        return response()->json([
            'success'   => true,
            'matrix'    => $matrix,
            'max_years' => $maxYears,
        ]);
    }

    /**
     * API: Riwayat Kepala Sekolah
     */
    public function getRiwayatKepsek(Request $request): JsonResponse
    {
        $tenantId = $request->input('tenant_id') ?: auth()->user()?->tenant_id;

        $data = DB::table('kepegawaian.riwayat_kepala_sekolah as r')
            ->leftJoin('core.tenants as t', 'r.tenant_id', '=', 't.id')
            ->when($tenantId, fn($q) => $q->where('r.tenant_id', $tenantId))
            ->orderBy('r.tanggal_mulai', 'desc')
            ->get([
                'r.*',
                't.nama_sekolah'
            ]);

        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }

    /**
     * API: Simpan / Update Riwayat Kepala Sekolah
     */
    public function storeRiwayatKepsek(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nama_kepsek'     => 'required|string|max:255',
            'nip_kepsek'      => 'nullable|string|max:50',
            'tanggal_mulai'   => 'required|date',
            'tanggal_selesai' => 'nullable|date',
            'status_plt'      => 'nullable|boolean',
            'id'              => 'nullable|uuid',
        ]);

        $tenantId = $request->input('tenant_id') ?: auth()->user()?->tenant_id;
        $id = $validated['id'] ?? (string) Str::uuid();

        DB::table('kepegawaian.riwayat_kepala_sekolah')->updateOrInsert(
            ['id' => $id],
            [
                'tenant_id'       => $tenantId,
                'nama_kepsek'     => $validated['nama_kepsek'],
                'nip_kepsek'      => $validated['nip_kepsek'] ?? null,
                'tanggal_mulai'   => $validated['tanggal_mulai'],
                'tanggal_selesai' => $validated['tanggal_selesai'] ?? null,
                'status_plt'      => !empty($validated['status_plt']) ? 1 : 0,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Riwayat kepala sekolah berhasil disimpan.',
        ]);
    }

    /**
     * API: Hapus Riwayat Kepala Sekolah
     */
    public function destroyRiwayatKepsek(string $id): JsonResponse
    {
        DB::table('kepegawaian.riwayat_kepala_sekolah')->where('id', $id)->delete();
        return response()->json([
            'success' => true,
            'message' => 'Riwayat kepala sekolah berhasil dihapus.',
        ]);
    }

    /**
     * API: Load Direktori Alumni & Dokumen Arsip
     */
    public function loadAlumni(Request $request): JsonResponse
    {
        $tenantId = $request->input('tenant_id') ?: auth()->user()?->tenant_id;
        $search   = trim((string)$request->input('search', ''));
        $page     = (int)$request->input('page', 1);
        $perPage  = 10;

        $query = DB::table('siswa.siswa')
            ->when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))
            ->where(function ($q) {
                $q->where('status_siswa', 'ILIKE', 'lulus')
                  ->orWhere('status_siswa', 'ILIKE', 'alumni')
                  ->orWhere('is_active', true);
            })
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('nama_lengkap', 'ILIKE', "%{$search}%")
                        ->orWhere('nisn', 'ILIKE', "%{$search}%");
                });
            })
            ->orderBy('nama_lengkap', 'asc');

        $total = $query->count();
        $alumni = $query->skip(($page - 1) * $perPage)->take($perPage)->get(['id', 'nama_lengkap', 'nisn', 'nis', 'kelas_saat_ini as nama_kelas']);

        return response()->json([
            'success'      => true,
            'data'         => $alumni,
            'total'        => $total,
            'current_page' => $page,
            'last_page'    => max(1, ceil($total / $perPage)),
        ]);
    }

    /**
     * API: Upload Arsip Dokumen Alumni
     */
    public function uploadAlumniDoc(Request $request): JsonResponse
    {
        $request->validate([
            'siswa_id'      => 'required|uuid',
            'jenis_dokumen' => 'required|string|max:100',
            'keterangan'    => 'nullable|string',
            'berkas'        => 'required|file|max:10240', // max 10MB
        ]);

        $file = $request->file('berkas');
        $siswaId = $request->input('siswa_id');
        $tenantId = $request->input('tenant_id') ?: auth()->user()?->tenant_id;
        $jenis = $request->input('jenis_dokumen');

        $ext = $file->getClientOriginalExtension();
        $filename = "alumni_{$jenis}_{$siswaId}_" . time() . ".{$ext}";
        $path = $file->storeAs('dokumen_siswa', $filename, 'public');
        $url = "/storage/{$path}";

        $id = (string) Str::uuid();
        DB::table('siswa.dokumen')->insert([
            'id'            => $id,
            'tenant_id'     => $tenantId,
            'siswa_id'      => $siswaId,
            'jenis_dokumen' => $jenis,
            'nama_file'     => $filename,
            'url_file'      => $url,
            'keterangan'    => $request->input('keterangan'),
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Dokumen alumni berhasil diunggah ke brankas.',
            'url'     => $url,
        ]);
    }

    /**
     * API: Hapus Dokumen Arsip Alumni
     */
    public function destroyAlumniDoc(string $id): JsonResponse
    {
        DB::table('siswa.dokumen')->where('id', $id)->delete();
        return response()->json([
            'success' => true,
            'message' => 'Dokumen arsip berhasil dihapus.',
        ]);
    }

    /**
     * Ekspor Data Buku Induk ke Excel (.xlsx)
     */
    public function exportExcel(Request $request)
    {
        $tenantId = $request->input('tenant_id') ?: auth()->user()?->tenant_id;
        $filterKelas = $request->input('kelas_id', '');
        $namaKelas = '';
        if (!empty($filterKelas)) {
            $kObj = DB::table('akademik.kelas')->where('id', $filterKelas)->first();
            $namaKelas = $kObj?->nama_kelas ?? $filterKelas;
        }

        $students = DB::table('siswa.siswa')
            ->when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))
            ->when(!empty($namaKelas), fn($q) => $q->where('kelas_saat_ini', $namaKelas))
            ->where('is_active', true)
            ->orderBy('nama_lengkap', 'asc')
            ->get();

        $rows = [
            ['No', 'NISN', 'NIS', 'NIK', 'Nama Lengkap', 'Jenis Kelamin', 'Tempat Lahir', 'Tanggal Lahir', 'Kelas', 'Jurusan', 'Status']
        ];
        foreach ($students as $idx => $s) {
            $rows[] = [
                $idx + 1,
                $s->nisn ?? '-',
                $s->nis ?? '-',
                $s->nik ?? '-',
                $s->nama_lengkap ?? '-',
                $s->jenis_kelamin ?? '-',
                $s->tempat_lahir ?? '-',
                $s->tanggal_lahir ?? '-',
                $s->kelas_saat_ini ?? '-',
                $s->jurusan ?? '-',
                $s->status_siswa ?? 'Aktif',
            ];
        }

        $filename = "Buku_Induk_Siswa_" . date('Ymd_His') . ".xlsx";
        $xlsx = \Shuchkin\SimpleXLSXGen::fromArray($rows);

        return response((string) $xlsx, 200, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control'       => 'max-age=0',
        ]);
    }

    /**
     * Ekspor PDSS SNBP Excel (.xlsx)
     */
    public function exportPdssExcel(Request $request)
    {
        $tenantId = $request->input('tenant_id') ?: auth()->user()?->tenant_id;
        $filterKelas = $request->input('kelas_id', '');
        $namaKelas = '';
        if (!empty($filterKelas)) {
            $kObj = DB::table('akademik.kelas')->where('id', $filterKelas)->first();
            $namaKelas = $kObj?->nama_kelas ?? $filterKelas;
        }

        $students = DB::table('siswa.siswa')
            ->when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))
            ->when(!empty($namaKelas), fn($q) => $q->where('kelas_saat_ini', $namaKelas))
            ->where('is_active', true)
            ->orderBy('nama_lengkap', 'asc')
            ->get();

        $rows = [
            ['No', 'NISN', 'Nama Siswa', 'Jurusan', 'Kelas', 'Kelayakan PDSS', 'Peringkat']
        ];
        foreach ($students as $idx => $s) {
            $rows[] = [
                $idx + 1,
                $s->nisn ?? '-',
                $s->nama_lengkap ?? '-',
                $s->jurusan ?? '-',
                $s->kelas_saat_ini ?? '-',
                'Eligible (Top 40%)',
                $idx + 1,
            ];
        }

        $filename = "PDSS_SNBP_Siswa_" . date('Ymd_His') . ".xlsx";
        $xlsx = \Shuchkin\SimpleXLSXGen::fromArray($rows);

        return response((string) $xlsx, 200, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control'       => 'max-age=0',
        ]);
    }

    /**
     * Helper untuk mengambil data master akademik
     */
    private function getAcademicOptions(?string $tenantId = null): array
    {
        $tenantId = $tenantId ?? session('tenant_id') ?? auth()->user()?->tenant_id;

        $angkatan = DB::table('akademik.angkatan')
            ->where('is_active', true)
            ->when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))
            ->orderBy('nama_angkatan', 'desc')
            ->get(['id', 'nama_angkatan as tahun_angkatan']);

        $tahunAjaran = DB::table('akademik.tahun_ajaran')
            ->where('is_active', true)
            ->when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))
            ->orderBy('nama_tahun_ajaran', 'desc')
            ->get(['id', 'nama_tahun_ajaran as tahun_ajaran']);

        $jenjang = DB::table('core.jenjang')
            ->where('is_active', true)
            ->when($tenantId, fn($q) => $q->where(fn($sub) => $sub->where('tenant_id', $tenantId)->orWhereNull('tenant_id')))
            ->orderBy('nama_jenjang', 'asc')
            ->get(['id', 'nama_jenjang']);

        $jurusan = DB::table('akademik.jurusan')
            ->where('is_active', true)
            ->when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))
            ->orderBy('nama_jurusan', 'asc')
            ->get(['id', 'nama_jurusan']);

        $kelas = DB::table('akademik.kelas')
            ->where('is_active', true)
            ->when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))
            ->orderBy('nama_kelas', 'asc')
            ->get(['id', 'nama_kelas', 'id_jenjang', 'id_jurusan']);

        $pendidikan = DB::table('akademik.pendidikan')
            ->where('is_active', true)
            ->when($tenantId, fn($q) => $q->where(fn($sub) => $sub->where('tenant_id', $tenantId)->orWhereNull('tenant_id')))
            ->orderBy('nama_pendidikan', 'asc')
            ->get(['id', 'nama_pendidikan']);

        return [
            'angkatan'     => $angkatan,
            'tahun_ajaran' => $tahunAjaran,
            'jenjang'      => $jenjang,
            'jurusan'      => $jurusan,
            'kelas'        => $kelas,
            'pendidikan'   => $pendidikan,
        ];
    }

    /**
     * Form Tambah Siswa Baru (Halaman Penuh)
     */
    public function create(Request $request): InertiaResponse
    {
        $tenantId = session('tenant_id') ?? auth()->user()?->tenant_id;
        $academicOptions = $this->getAcademicOptions($tenantId);
        $provinces = DB::table('core.provinsi')->orderBy('nama_provinsi', 'asc')->get(['id_provinsi', 'nama_provinsi']);
        $tenants = DB::table('core.tenants')->orderBy('nama_sekolah')->get(['id', 'nama_sekolah']);

        $emptyKesehatan = [];
        for ($s = 1; $s <= 6; $s++) {
            $emptyKesehatan[$s] = [
                'tinggi_badan' => '',
                'berat_badan'  => '',
                'pendengaran'  => '',
                'pengelihatan' => '',
                'gigi'         => '',
            ];
        }

        $blankSiswa = [
            'id'                       => '',
            'tenant_id'                => $tenantId ?? '',
            'nik'                      => '',
            'no_kk'                    => '',
            'nisn'                     => '',
            'nis'                      => '',
            'password'                 => '',
            'nama_lengkap'             => '',
            'nama_panggilan'           => '',
            'jenis_kelamin'            => 'L',
            'agama'                    => 'Islam',
            'kewarganegaraan'          => 'WNI',
            'bahasa_sehari_hari'       => 'Indonesia',
            'tempat_lahir'             => '',
            'tanggal_lahir'            => '',
            'sekolah_asal'             => '',
            'status'                   => 'Aktif',
            'status_siswa'             => 'Aktif',
            'id_angkatan'              => $academicOptions['angkatan']->first()?->id ?? '',
            'id_tahun_ajaran'          => $academicOptions['tahun_ajaran']->first()?->id ?? '',
            'id_jenjang'               => $academicOptions['jenjang']->first()?->id ?? '',
            'id_jurusan'               => $academicOptions['jurusan']->first()?->id ?? '',
            'id_kelas'                 => $academicOptions['kelas']->first()?->id ?? '',
            'id_pendidikan'            => $academicOptions['pendidikan']->first()?->id ?? '',
            'kelas_saat_ini'           => $academicOptions['kelas']->first()?->nama_kelas ?? '',
            'jurusan'                  => $academicOptions['jurusan']->first()?->nama_jurusan ?? '',
            'angkatan'                 => (int) date('Y'),
            'ukuran_seragam_sekolah'   => '',
            'ukuran_seragam_olahraga'  => '',
            'no_ijazah_sebelumnya'     => '',
            'tanggal_ijazah_sebelumnya'=> '',
            'lama_belajar_sebelumnya'  => 3,
            // Alamat & Kontak
            'alamat_kk'                => '',
            'alamat_domisili'          => '',
            'rt'                       => '001',
            'rw'                       => '001',
            'kode_pos'                 => '',
            'id_provinsi'              => '',
            'id_kota'                  => '',
            'id_kecamatan'             => '',
            'id_kelurahan'             => '',
            'status_tinggal'           => 'Milik Sendiri',
            'tinggal_dengan'           => 'Orang Tua',
            'email'                    => '',
            'no_telepon_rumah'         => '',
            'no_telepon_siswa'         => '',
            'no_telepon_orang_tua'     => '',
            // Fisik & Bantuan
            'tinggi_badan'             => '',
            'berat_badan'              => '',
            'lingkar_kepala'           => '',
            'golongan_darah'           => 'O',
            'anak_ke'                  => 1,
            'jumlah_saudara'           => 0,
            'saudara_tiri'             => 0,
            'saudara_angkat'           => 0,
            'penyakit_yang_diderita'   => '',
            'kelainan_jasmani'         => 'Tidak Ada',
            'jarak_rumah'              => 1000,
            'transportasi'             => 'Motor',
            'status_anak'              => 'Bukan Yatim/Piatu',
            'penerima_kps'             => 0,
            'punya_kip'                => 0,
            'layak_kip'                => 0,
            'no_kip'                   => '',
            'alasan_layak'             => '',
            'kesehatan'                => $emptyKesehatan,
            // Ayah
            'nik_ayah'                 => '',
            'nama_ayah'                => '',
            'id_tempat_lahir_ayah'     => '',
            'tempat_lahir_ayah'        => '',
            'tanggal_lahir_ayah'       => '',
            'kewarganegaraan_ayah'     => 'WNI',
            'status_hidup_ayah'        => 'Hidup',
            'pendidikan_ayah'          => 'SMA',
            'pekerjaan_ayah'           => 'Wiraswasta',
            'penghasilan_ayah'         => 'Rp2.000.000 sampai Rp4.999.999',
            'agama_ayah'               => 'Islam',
            // Ibu
            'nik_ibu'                  => '',
            'nama_ibu'                 => '',
            'id_tempat_lahir_ibu'      => '',
            'tempat_lahir_ibu'         => '',
            'tanggal_lahir_ibu'        => '',
            'kewarganegaraan_ibu'      => 'WNI',
            'status_hidup_ibu'         => 'Hidup',
            'pendidikan_ibu'           => 'SMA',
            'pekerjaan_ibu'            => 'Tidak Bekerja',
            'penghasilan_ibu'          => 'Tidak Berpenghasilan',
            'agama_ibu'                => 'Islam',
            // Wali
            'nik_wali'                 => '',
            'nama_wali'                => '',
            'id_tempat_lahir_wali'     => '',
            'tempat_lahir_wali'        => '',
            'tanggal_lahir_wali'       => '',
            'kewarganegaraan_wali'     => 'WNI',
            'hubungan_wali'            => '',
            'pendidikan_wali'          => '',
            'pekerjaan_wali'           => '',
            'penghasilan_wali'         => '',
            'agama_wali'               => 'Islam',
            // Registrasi & Keluar
            'jenis_pendaftaran'        => 'Siswa Baru',
            'jalur_diterima'           => 'Zonasi',
            'tanggal_masuk'            => date('Y-m-d'),
            'hobi'                     => 'Membaca',
            'paud_formal'              => 1,
            'paud_non_formal'          => 0,
            'sekolah_asal_mutasi'      => '',
            'pindah_dari_tingkat'      => '',
            'pindah_no_surat'          => '',
            'keluar_karena'            => '',
            'tanggal_keluar'           => '',
            'alasan_keluar'            => '',
            'sekolah_tujuan'           => '',
            'nomor_skp'                => '',
            'tingkat_ditinggalkan'     => '',
            'diterima_di_tingkat'      => '',
            'nomor_ijazah_kelulusan'   => '',
            'nomor_skl'                => '',
            'keterangan_setelah_lulus' => '',
            // Dokumen files
            'foto_profil'              => '',
            'berkas_kk'                => '',
            'berkas_akta'              => '',
            'berkas_ijazah_sd'         => '',
            'berkas_ijazah_smp'        => '',
            'berkas_ijazah_sma'        => '',
            'berkas_mutasi_masuk'      => '',
            'berkas_mutasi_keluar'     => '',
            'berkas_kip'               => '',
            'berkas_pernyataan_baru'   => '',
            'berkas_pernyataan_tka'    => '',
        ];

        return Inertia::render('Siswa/Edit', [
            'siswa'           => $blankSiswa,
            'academicOptions' => $academicOptions,
            'provinces'       => $provinces,
            'tenants'         => $tenants,
            'isCreate'        => true,
            'userRole'        => auth()->user()?->role_name ?? 'admin_sekolah',
        ]);
    }

    /**
     * Form Edit Siswa Lengkap (Halaman Penuh)
     */
    public function edit(Request $request, ?string $id = null): InertiaResponse|JsonResponse
    {
        $targetId = $id ?? $request->query('id');
        $siswa = Siswa::withoutTenant()->findOrFail($targetId);

        // Ambil data sub-tabel
        $ortuList = DB::table('siswa.orang_tua')->where('siswa_id', $targetId)->get();
        $registrasi = DB::table('siswa.registrasi')->where('siswa_id', $targetId)->first();
        $fisik = DB::table('siswa.fisik_kesehatan_siswa')->where('siswa_id', $targetId)->first();
        $dokumen = DB::table('siswa.dokumen')->where('siswa_id', $targetId)->get();

        $academicOptions = $this->getAcademicOptions($siswa->tenant_id);
        $provinces = DB::table('core.provinsi')->orderBy('nama_provinsi', 'asc')->get(['id_provinsi', 'nama_provinsi']);
        $tenants = DB::table('core.tenants')->orderBy('nama_sekolah')->get(['id', 'nama_sekolah']);

        // Format Orang Tua
        $ayah = $ortuList->firstWhere('hubungan', 'Ayah');
        $ibu  = $ortuList->firstWhere('hubungan', 'Ibu');
        $wali = $ortuList->firstWhere('hubungan', 'Wali');

        // Format Kesehatan Detail Semester
        $emptyKesehatan = [];
        for ($s = 1; $s <= 6; $s++) {
            $emptyKesehatan[$s] = [
                'tinggi_badan' => '',
                'berat_badan'  => '',
                'pendengaran'  => '',
                'pengelihatan' => '',
                'gigi'         => '',
            ];
        }
        $semDetail = $emptyKesehatan;
        if ($fisik && !empty($fisik->detail_semester)) {
            $parsed = is_string($fisik->detail_semester) ? json_decode($fisik->detail_semester, true) : (array)$fisik->detail_semester;
            if (is_array($parsed)) {
                foreach ($parsed as $k => $v) {
                    if (isset($semDetail[$k]) && is_array($v)) {
                        $semDetail[$k] = array_merge($semDetail[$k], $v);
                    }
                }
            }
        }

        // Format Dokumen
        $docMap = [];
        foreach ($dokumen as $doc) {
            if (!empty($doc->jenis_dokumen)) {
                $docMap[$doc->jenis_dokumen] = $doc->url_file ?: $doc->nama_file;
            }
        }

        // Cari ID lookup akademik jika tersimpan sebagai text
        $idAngkatan = $academicOptions['angkatan']->first(fn($a) => (string)$a->tahun_angkatan === (string)$siswa->angkatan || (string)$a->id === (string)$siswa->angkatan)?->id ?? $academicOptions['angkatan']->first()?->id ?? '';
        $idJurusan  = $academicOptions['jurusan']->first(fn($j) => $j->nama_jurusan === $siswa->jurusan || (string)$j->id === (string)$siswa->jurusan)?->id ?? $academicOptions['jurusan']->first()?->id ?? '';
        $idKelas    = $academicOptions['kelas']->first(fn($k) => $k->nama_kelas === $siswa->kelas_saat_ini || (string)$k->id === (string)$siswa->kelas_saat_ini)?->id ?? $academicOptions['kelas']->first()?->id ?? '';
        $selectedKelas = $academicOptions['kelas']->firstWhere('id', $idKelas);
        $idJenjang  = $selectedKelas?->id_jenjang ?? $academicOptions['jenjang']->first()?->id ?? '';

        $flattened = [
            'id'                       => $siswa->id,
            'tenant_id'                => $siswa->tenant_id,
            'nik'                      => $siswa->nik ?? '',
            'no_kk'                    => $siswa->no_kk ?? '',
            'nisn'                     => $siswa->nisn ?? '',
            'nis'                      => $siswa->nis ?? '',
            'password'                 => '',
            'nama_lengkap'             => $siswa->nama_lengkap ?? '',
            'nama_panggilan'           => $siswa->nama_panggilan ?? '',
            'jenis_kelamin'            => $siswa->jenis_kelamin ?? 'L',
            'agama'                    => $siswa->agama ?? 'Islam',
            'kewarganegaraan'          => $siswa->kewarganegaraan ?? 'WNI',
            'bahasa_sehari_hari'       => $siswa->bahasa_sehari_hari ?? 'Indonesia',
            'tempat_lahir'             => $siswa->tempat_lahir ?? '',
            'tanggal_lahir'            => $siswa->tanggal_lahir ? Str::substr((string)$siswa->tanggal_lahir, 0, 10) : '',
            'sekolah_asal'             => $registrasi?->asal_sekolah ?? $siswa->asal_sekolah ?? '',
            'status'                   => $siswa->status_siswa ?? 'Aktif',
            'status_siswa'             => $siswa->status_siswa ?? 'Aktif',
            'id_angkatan'              => $idAngkatan,
            'id_tahun_ajaran'          => $academicOptions['tahun_ajaran']->first()?->id ?? '',
            'id_jenjang'               => $idJenjang,
            'id_jurusan'               => $idJurusan,
            'id_kelas'                 => $idKelas,
            'id_pendidikan'            => $academicOptions['pendidikan']->first()?->id ?? '',
            'kelas_saat_ini'           => $siswa->kelas_saat_ini ?? '',
            'jurusan'                  => $siswa->jurusan ?? '',
            'angkatan'                 => $siswa->angkatan ?? '',
            'ukuran_seragam_sekolah'   => $siswa->ukuran_seragam_sekolah ?? '',
            'ukuran_seragam_olahraga'  => $siswa->ukuran_seragam_olahraga ?? '',
            'no_ijazah_sebelumnya'     => $registrasi?->no_ijazah_sebelumnya ?? '',
            'tanggal_ijazah_sebelumnya'=> $registrasi?->tanggal_ijazah_sebelumnya ? Str::substr((string)$registrasi->tanggal_ijazah_sebelumnya, 0, 10) : '',
            'lama_belajar_sebelumnya'  => $registrasi?->lama_belajar_sebelumnya ?? 3,

            // Alamat & Kontak
            'alamat_kk'                => $siswa->alamat ?? '',
            'alamat_domisili'          => $siswa->alamat_domisili ?? $siswa->alamat ?? '',
            'rt'                       => $siswa->rt ?? '001',
            'rw'                       => $siswa->rw ?? '001',
            'kode_pos'                 => $siswa->kode_pos ?? '',
            'id_provinsi'              => $siswa->id_provinsi ? (int)$siswa->id_provinsi : '',
            'id_kota'                  => $siswa->id_kota ? (int)$siswa->id_kota : '',
            'id_kecamatan'             => $siswa->id_kecamatan ? (int)$siswa->id_kecamatan : '',
            'id_kelurahan'             => $siswa->id_kelurahan ? (int)$siswa->id_kelurahan : '',
            'status_tinggal'           => $siswa->status_tinggal ?? 'Milik Sendiri',
            'tinggal_dengan'           => $siswa->tinggal_dengan ?? 'Orang Tua',
            'email'                    => $siswa->email ?? '',
            'no_telepon_rumah'         => $siswa->no_telepon_rumah ?? '',
            'no_telepon_siswa'         => $siswa->no_hp ?? '',
            'no_telepon_orang_tua'     => $siswa->no_telepon_orang_tua ?? $ayah?->no_hp ?? $ibu?->no_hp ?? '',

            // Fisik & Bantuan
            'tinggi_badan'             => $fisik?->tinggi_badan ?? '',
            'berat_badan'              => $fisik?->berat_badan ?? '',
            'lingkar_kepala'           => $fisik?->lingkar_kepala ?? '',
            'golongan_darah'           => $fisik?->golongan_darah ?? 'O',
            'anak_ke'                  => $siswa->anak_ke ?? 1,
            'jumlah_saudara'           => $siswa->jumlah_saudara ?? 0,
            'saudara_tiri'             => $siswa->saudara_tiri ?? 0,
            'saudara_angkat'           => $siswa->saudara_angkat ?? 0,
            'penyakit_yang_diderita'   => $fisik?->riwayat_penyakit ?? '',
            'kelainan_jasmani'         => $fisik?->disabilitas ?? 'Tidak Ada',
            'jarak_rumah'              => $siswa->jarak_rumah ?? 1000,
            'transportasi'             => $siswa->transportasi ?? 'Motor',
            'status_anak'              => $siswa->status_anak ?? 'Bukan Yatim/Piatu',
            'penerima_kps'             => $siswa->penerima_kps ? 1 : 0,
            'punya_kip'                => $siswa->punya_kip ? 1 : 0,
            'layak_kip'                => $siswa->layak_kip ? 1 : 0,
            'no_kip'                   => $siswa->no_kip ?? '',
            'alasan_layak'             => $siswa->alasan_layak ?? '',
            'kesehatan'                => $semDetail,

            // Ayah
            'nik_ayah'                 => $ayah?->nik ?? '',
            'nama_ayah'                => $ayah?->nama_lengkap ?? '',
            'id_tempat_lahir_ayah'     => $ayah?->id_tempat_lahir ?? '',
            'tempat_lahir_ayah'        => $ayah?->tempat_lahir ?? '',
            'tanggal_lahir_ayah'       => $ayah?->tanggal_lahir ? Str::substr((string)$ayah->tanggal_lahir, 0, 10) : '',
            'kewarganegaraan_ayah'     => $ayah?->kewarganegaraan ?? 'WNI',
            'status_hidup_ayah'        => $ayah?->status_hidup ?? 'Hidup',
            'pendidikan_ayah'          => $ayah?->pendidikan ?? 'SMA',
            'pekerjaan_ayah'           => $ayah?->pekerjaan ?? '',
            'penghasilan_ayah'         => $ayah?->penghasilan ? (string)$ayah->penghasilan : 'Rp2.000.000 sampai Rp4.999.999',
            'agama_ayah'               => $ayah?->agama ?? 'Islam',

            // Ibu
            'nik_ibu'                  => $ibu?->nik ?? '',
            'nama_ibu'                 => $ibu?->nama_lengkap ?? '',
            'id_tempat_lahir_ibu'      => $ibu?->id_tempat_lahir ?? '',
            'tempat_lahir_ibu'         => $ibu?->tempat_lahir ?? '',
            'tanggal_lahir_ibu'        => $ibu?->tanggal_lahir ? Str::substr((string)$ibu->tanggal_lahir, 0, 10) : '',
            'kewarganegaraan_ibu'      => $ibu?->kewarganegaraan ?? 'WNI',
            'status_hidup_ibu'         => $ibu?->status_hidup ?? 'Hidup',
            'pendidikan_ibu'           => $ibu?->pendidikan ?? 'SMA',
            'pekerjaan_ibu'            => $ibu?->pekerjaan ?? '',
            'penghasilan_ibu'          => $ibu?->penghasilan ? (string)$ibu->penghasilan : 'Tidak Berpenghasilan',
            'agama_ibu'                => $ibu?->agama ?? 'Islam',

            // Wali
            'nik_wali'                 => $wali?->nik ?? '',
            'nama_wali'                => $wali?->nama_lengkap ?? '',
            'id_tempat_lahir_wali'     => $wali?->id_tempat_lahir ?? '',
            'tempat_lahir_wali'        => $wali?->tempat_lahir ?? '',
            'tanggal_lahir_wali'       => $wali?->tanggal_lahir ? Str::substr((string)$wali->tanggal_lahir, 0, 10) : '',
            'kewarganegaraan_wali'     => $wali?->kewarganegaraan ?? 'WNI',
            'hubungan_wali'            => $wali?->hubungan_wali ?? '',
            'pendidikan_wali'          => $wali?->pendidikan ?? '',
            'pekerjaan_wali'           => $wali?->pekerjaan ?? '',
            'penghasilan_wali'         => $wali?->penghasilan ? (string)$wali->penghasilan : '',
            'agama_wali'               => $wali?->agama ?? 'Islam',

            // Registrasi & Keluar
            'jenis_pendaftaran'        => $registrasi?->jenis_pendaftaran ?? 'Siswa Baru',
            'jalur_diterima'           => $registrasi?->jalur_diterima ?? 'Zonasi',
            'tanggal_masuk'            => $registrasi?->tanggal_masuk ? Str::substr((string)$registrasi->tanggal_masuk, 0, 10) : date('Y-m-d'),
            'hobi'                     => $registrasi?->hobi ?? 'Membaca',
            'paud_formal'              => $registrasi?->paud_formal ? 1 : 0,
            'paud_non_formal'          => $registrasi?->paud_non_formal ? 1 : 0,
            'sekolah_asal_mutasi'      => $registrasi?->sekolah_asal_mutasi ?? '',
            'pindah_dari_tingkat'      => $registrasi?->pindah_dari_tingkat ?? '',
            'pindah_no_surat'          => $registrasi?->pindah_no_surat ?? '',
            'keluar_karena'            => $registrasi?->keluar_karena ?? '',
            'tanggal_keluar'           => $registrasi?->tanggal_keluar ? Str::substr((string)$registrasi->tanggal_keluar, 0, 10) : '',
            'alasan_keluar'            => $registrasi?->alasan_keluar ?? '',
            'sekolah_tujuan'           => $registrasi?->sekolah_tujuan ?? '',
            'nomor_skp'                => $registrasi?->nomor_skp ?? '',
            'tingkat_ditinggalkan'     => $registrasi?->tingkat_ditinggalkan ?? '',
            'diterima_di_tingkat'      => $registrasi?->diterima_di_tingkat ?? '',
            'nomor_ijazah_kelulusan'   => $registrasi?->nomor_ijazah_kelulusan ?? '',
            'nomor_skl'                => $registrasi?->nomor_skl ?? '',
            'keterangan_setelah_lulus' => $registrasi?->keterangan_setelah_lulus ?? '',

            // Dokumen files
            'foto_profil'              => $docMap['foto_profil'] ?? $siswa->foto_url ?? '',
            'berkas_kk'                => $docMap['berkas_kk'] ?? '',
            'berkas_akta'              => $docMap['berkas_akta'] ?? '',
            'berkas_ijazah_sd'         => $docMap['berkas_ijazah_sd'] ?? '',
            'berkas_ijazah_smp'        => $docMap['berkas_ijazah_smp'] ?? '',
            'berkas_ijazah_sma'        => $docMap['berkas_ijazah_sma'] ?? '',
            'berkas_mutasi_masuk'      => $docMap['berkas_mutasi_masuk'] ?? '',
            'berkas_mutasi_keluar'     => $docMap['berkas_mutasi_keluar'] ?? '',
            'berkas_kip'               => $docMap['berkas_kip'] ?? '',
            'berkas_pernyataan_baru'   => $docMap['berkas_pernyataan_baru'] ?? '',
            'berkas_pernyataan_tka'    => $docMap['berkas_pernyataan_tka'] ?? '',
        ];

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'data' => $flattened]);
        }

        return Inertia::render('Siswa/Edit', [
            'siswa'           => $flattened,
            'academicOptions' => $academicOptions,
            'provinces'       => $provinces,
            'tenants'         => $tenants,
            'isCreate'        => false,
            'userRole'        => auth()->user()?->role_name ?? 'admin_sekolah',
        ]);
    }

    /**
     * Tampilkan detail lengkap profil siswa
     */
    public function show(string $id): InertiaResponse|JsonResponse
    {
        $siswa = Siswa::withoutTenant()->with(['orangTua', 'registrasi', 'fisikKesehatan', 'dokumen', 'mutasi', 'prestasi'])->findOrFail($id);

        if (request()->wantsJson()) {
            return response()->json(['success' => true, 'data' => $siswa]);
        }

        return Inertia::render('Siswa/BukuInduk/Show', [
            'siswa' => $siswa,
        ]);
    }

    /**
     * Validasi input formulir siswa
     */
    private function validateSiswaRequest(Request $request, ?string $excludeId = null): array
    {
        $currentStep = $request->input('current_step') ? (int) $request->input('current_step') : null;
        $isStepSave = !empty($currentStep);

        $rules = [
            // Step 1: Identitas & Akademik
            'nik'                    => ($isStepSave && $currentStep !== 1 && $excludeId) ? 'nullable|string|max:20' : 'required|string|size:16',
            'no_kk'                  => 'nullable|string|max:20',
            'nisn'                   => ($isStepSave && $currentStep !== 1 && $excludeId) ? 'nullable|string|max:20' : 'required|string|size:10',
            'nis'                    => 'nullable|string|max:50',
            'nama_lengkap'           => ($isStepSave && $currentStep !== 1 && $excludeId) ? 'nullable|string|max:255' : 'required|string|min:3|max:255',
            'nama_panggilan'         => 'nullable|string|max:100',
            'jenis_kelamin'          => ($isStepSave && $currentStep !== 1 && $excludeId) ? 'nullable|string|in:L,P' : 'required|string|in:L,P',
            'tempat_lahir'           => ($isStepSave && $currentStep !== 1 && $excludeId) ? 'nullable|string|max:100' : 'required|string|max:100',
            'tanggal_lahir'          => ($isStepSave && $currentStep !== 1 && $excludeId) ? 'nullable|date' : 'required|date',
            'agama'                  => ($isStepSave && $currentStep !== 1 && $excludeId) ? 'nullable|string|max:50' : 'required|string|max:50',
            'kewarganegaraan'        => ($isStepSave && $currentStep !== 1 && $excludeId) ? 'nullable|string|max:50' : 'required|string|max:50',
            'bahasa_sehari_hari'     => ($isStepSave && $currentStep !== 1 && $excludeId) ? 'nullable|string|max:100' : 'required|string|max:100',
            'status'                 => ($isStepSave && $currentStep !== 1 && $excludeId) ? 'nullable|string|max:50' : 'required|string|max:50',
            'id_angkatan'            => ($isStepSave && $currentStep !== 1 && $excludeId) ? 'nullable|uuid' : 'required|uuid',
            'id_tahun_ajaran'        => ($isStepSave && $currentStep !== 1 && $excludeId) ? 'nullable|uuid' : 'required|uuid',
            'id_jenjang'             => ($isStepSave && $currentStep !== 1 && $excludeId) ? 'nullable|uuid' : 'required|uuid',
            'id_jurusan'             => ($isStepSave && $currentStep !== 1 && $excludeId) ? 'nullable|uuid' : 'required|uuid',
            'id_kelas'               => ($isStepSave && $currentStep !== 1 && $excludeId) ? 'nullable|uuid' : 'required|uuid',
            'id_pendidikan'          => 'nullable|uuid',
            'ukuran_seragam_sekolah' => 'nullable|string|max:20',
            'ukuran_seragam_olahraga'=> 'nullable|string|max:20',
            'no_ijazah_sebelumnya'   => 'nullable|string|max:100',
            'tanggal_ijazah_sebelumnya'=> 'nullable|date',
            'lama_belajar_sebelumnya'=> 'nullable|integer',

            // Step 2: Alamat & Kontak
            'alamat_kk'              => ($isStepSave && $currentStep !== 2) ? 'nullable|string' : 'required|string|min:5',
            'alamat_domisili'        => ($isStepSave && $currentStep !== 2) ? 'nullable|string' : 'required|string|min:5',
            'rt'                     => ($isStepSave && $currentStep !== 2) ? 'nullable|string|max:5' : 'required|string|max:5',
            'rw'                     => ($isStepSave && $currentStep !== 2) ? 'nullable|string|max:5' : 'required|string|max:5',
            'kode_pos'               => ($isStepSave && $currentStep !== 2) ? 'nullable|string|max:10' : 'required|string|digits:5',
            'id_provinsi'            => ($isStepSave && $currentStep !== 2) ? 'nullable|integer' : 'required|integer',
            'id_kota'                => ($isStepSave && $currentStep !== 2) ? 'nullable|integer' : 'required|integer',
            'id_kecamatan'           => ($isStepSave && $currentStep !== 2) ? 'nullable|integer' : 'required|integer',
            'id_kelurahan'           => ($isStepSave && $currentStep !== 2) ? 'nullable|integer' : 'required|integer',
            'status_tinggal'         => ($isStepSave && $currentStep !== 2) ? 'nullable|string|max:50' : 'required|string|max:50',
            'tinggal_dengan'         => ($isStepSave && $currentStep !== 2) ? 'nullable|string|max:50' : 'required|string|max:50',
            'email'                  => ($isStepSave && $currentStep !== 2) ? 'nullable|email|max:150' : 'required|email|max:150',
            'no_telepon_rumah'       => 'nullable|string|max:50',
            'no_telepon_siswa'       => ($isStepSave && $currentStep !== 2) ? 'nullable|string|max:20' : 'required|string|min:10|max:20',
            'no_telepon_orang_tua'   => 'nullable|string|max:50',

            // Step 3: Fisik, Riwayat & Bantuan
            'tinggi_badan'           => ($isStepSave && $currentStep !== 3) ? 'nullable|numeric' : 'required|numeric|min:30|max:250',
            'berat_badan'            => ($isStepSave && $currentStep !== 3) ? 'nullable|numeric' : 'required|numeric|min:5|max:200',
            'lingkar_kepala'         => ($isStepSave && $currentStep !== 3) ? 'nullable|numeric' : 'required|numeric|min:20|max:100',
            'golongan_darah'         => ($isStepSave && $currentStep !== 3) ? 'nullable|string|max:5' : 'required|string|in:A,B,AB,O',
            'anak_ke'                => ($isStepSave && $currentStep !== 3) ? 'nullable|integer' : 'required|integer|min:1',
            'jumlah_saudara'         => ($isStepSave && $currentStep !== 3) ? 'nullable|integer' : 'required|integer|min:0',
            'saudara_tiri'           => 'nullable|integer|min:0',
            'saudara_angkat'         => 'nullable|integer|min:0',
            'penyakit_yang_diderita' => 'nullable|string',
            'kelainan_jasmani'       => ($isStepSave && $currentStep !== 3) ? 'nullable|string|max:255' : 'required|string|max:255',
            'jarak_rumah'            => ($isStepSave && $currentStep !== 3) ? 'nullable|numeric' : 'required|numeric|min:1',
            'transportasi'           => ($isStepSave && $currentStep !== 3) ? 'nullable|string|max:100' : 'required|string|max:100',
            'status_anak'            => 'nullable|string|max:50',
            'penerima_kps'           => 'nullable|boolean',
            'punya_kip'              => 'nullable|boolean',
            'layak_kip'              => 'nullable|boolean',
            'no_kip'                 => 'required_if:punya_kip,1,true|nullable|string|max:100',
            'alasan_layak'           => 'required_if:layak_kip,1,true|nullable|string',

            // Step 4: Orang Tua & Wali
            'nik_ayah'               => ($isStepSave && $currentStep !== 4) ? 'nullable|string|max:20' : 'nullable|string|size:16',
            'nama_ayah'              => ($isStepSave && $currentStep !== 4) ? 'nullable|string|max:255' : 'nullable|string|min:3|max:255',
            'pekerjaan_ayah'         => 'nullable|string|max:100',
            'penghasilan_ayah'       => 'nullable|string|max:100',
            'nik_ibu'                => ($isStepSave && $currentStep !== 4) ? 'nullable|string|max:20' : 'nullable|string|size:16',
            'nama_ibu'               => ($isStepSave && $currentStep !== 4) ? 'nullable|string|max:255' : 'nullable|string|min:3|max:255',
            'pekerjaan_ibu'          => 'nullable|string|max:100',
            'penghasilan_ibu'        => 'nullable|string|max:100',
            'nama_wali'              => 'nullable|string|max:255',
            'nik_wali'               => 'nullable|string|max:20',
            'hubungan_wali'          => 'nullable|string|max:50',

            // Step 5: Registrasi Masuk & Berkas
            'jenis_pendaftaran'      => ($isStepSave && $currentStep !== 5) ? 'nullable|string|max:50' : 'required|string|max:50',
            'jalur_diterima'         => ($isStepSave && $currentStep !== 5) ? 'nullable|string|max:50' : 'required|string|max:50',
            'tanggal_masuk'          => ($isStepSave && $currentStep !== 5) ? 'nullable|date' : 'required|date',
            'hobi'                   => ($isStepSave && $currentStep !== 5) ? 'nullable|string|max:100' : 'required|string|max:100',
            'sekolah_asal_mutasi'    => 'nullable|string|max:255',
            'pindah_dari_tingkat'    => 'nullable|string|max:50',
            'pindah_no_surat'        => 'nullable|string|max:100',
            'keluar_karena'          => 'nullable|string|max:100',
            'tanggal_keluar'         => 'nullable|date',
            'alasan_keluar'          => 'nullable|string',
            'sekolah_tujuan'         => 'nullable|string|max:255',
            'nomor_skp'              => 'nullable|string|max:100',
            'tingkat_ditinggalkan'   => 'nullable|string|max:50',
            'diterima_di_tingkat'    => 'nullable|string|max:50',
            'nomor_ijazah_kelulusan' => 'nullable|string|max:100',
            'nomor_skl'              => 'nullable|string|max:100',
            'keterangan_setelah_lulus'=> 'nullable|string',
        ];

        return $request->validate($rules);
    }

    /**
     * Simpan Siswa Baru
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $tenantId = $request->input('tenant_id') ?: session('tenant_id') ?: auth()->user()?->tenant_id;
        $validated = $this->validateSiswaRequest($request);

        DB::beginTransaction();
        try {
            $siswaId = (string) Str::uuid();

            // 1. Resolve Academic References
            $kelas = DB::table('akademik.kelas')->where('id', $validated['id_kelas'])->first();
            $jurusan = DB::table('akademik.jurusan')->where('id', $validated['id_jurusan'])->first();
            $angkatan = DB::table('akademik.angkatan')->where('id', $validated['id_angkatan'])->first();

            $siswa = Siswa::create([
                'id'                      => $siswaId,
                'tenant_id'               => $tenantId,
                'nik'                     => $validated['nik'],
                'no_kk'                   => $validated['no_kk'] ?? null,
                'nisn'                    => $validated['nisn'],
                'nis'                     => $validated['nis'] ?? null,
                'nama_lengkap'            => $validated['nama_lengkap'],
                'nama_panggilan'          => $validated['nama_panggilan'] ?? null,
                'jenis_kelamin'           => $validated['jenis_kelamin'],
                'tempat_lahir'            => $validated['tempat_lahir'],
                'tanggal_lahir'           => $validated['tanggal_lahir'],
                'agama'                   => $validated['agama'],
                'kewarganegaraan'         => $validated['kewarganegaraan'],
                'bahasa_sehari_hari'      => $validated['bahasa_sehari_hari'],
                'asal_sekolah'            => $validated['sekolah_asal'] ?? null,
                'status_siswa'            => $validated['status'] ?? 'Aktif',
                'is_active'               => true,
                'kelas_saat_ini'          => $kelas?->nama_kelas ?? 'X-1',
                'jurusan'                 => $jurusan?->nama_jurusan ?? 'Umum',
                'angkatan'                => (int)($angkatan?->nama_angkatan ?? date('Y')),
                'ukuran_seragam_sekolah'  => $validated['ukuran_seragam_sekolah'] ?? null,
                'ukuran_seragam_olahraga' => $validated['ukuran_seragam_olahraga'] ?? null,
                'alamat'                  => $validated['alamat_kk'],
                'alamat_domisili'         => $validated['alamat_domisili'],
                'rt'                      => $validated['rt'],
                'rw'                      => $validated['rw'],
                'kode_pos'                => $validated['kode_pos'],
                'id_provinsi'             => $validated['id_provinsi'],
                'id_kota'                 => $validated['id_kota'],
                'id_kecamatan'            => $validated['id_kecamatan'],
                'id_kelurahan'            => $validated['id_kelurahan'],
                'status_tinggal'          => $validated['status_tinggal'],
                'tinggal_dengan'          => $validated['tinggal_dengan'],
                'email'                   => $validated['email'],
                'no_telepon_rumah'        => $validated['no_telepon_rumah'] ?? null,
                'no_hp'                   => $validated['no_telepon_siswa'],
                'no_telepon_orang_tua'    => $validated['no_telepon_orang_tua'] ?? null,
                'anak_ke'                 => $validated['anak_ke'],
                'jumlah_saudara'          => $validated['jumlah_saudara'],
                'saudara_tiri'            => $validated['saudara_tiri'] ?? 0,
                'saudara_angkat'          => $validated['saudara_angkat'] ?? 0,
                'jarak_rumah'             => $validated['jarak_rumah'],
                'transportasi'            => $validated['transportasi'],
                'status_anak'             => $validated['status_anak'] ?? 'Bukan Yatim/Piatu',
                'penerima_kps'            => !empty($validated['penerima_kps']),
                'punya_kip'               => !empty($validated['punya_kip']),
                'layak_kip'               => !empty($validated['layak_kip']),
                'no_kip'                  => $validated['no_kip'] ?? null,
                'alasan_layak'            => $validated['alasan_layak'] ?? null,
            ]);

            // 2. Simpan Orang Tua
            $this->saveOrangTua($siswaId, $tenantId, $validated);

            // 3. Simpan Fisik & Kesehatan
            $this->saveFisikKesehatan($siswaId, $tenantId, $validated);

            // 4. Simpan Registrasi
            $this->saveRegistrasi($siswaId, $tenantId, $validated);

            // 5. Upload Dokumen
            $this->handleUploadedFiles($request, $siswaId, $tenantId, $siswa);

            DB::commit();

            if ($request->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'Data siswa berhasil ditambahkan.', 'data' => $siswa], 201);
            }

            return redirect()->route('menu.buku-induk')
                ->with('success', "Siswa {$siswa->nama_lengkap} berhasil ditambahkan ke Buku Induk.");
        } catch (\Throwable $e) {
            DB::rollBack();
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
            }
            return back()->withErrors(['general' => 'Gagal menambahkan siswa: ' . $e->getMessage()])->withInput();
        }
    }

    /**
     * Update Siswa (Lengkap atau Per-Langkah)
     */
    public function update(Request $request, ?string $id = null): RedirectResponse|JsonResponse
    {
        $targetId = $id ?? $request->input('id') ?? $request->query('id');
        $siswa = Siswa::withoutTenant()->findOrFail($targetId);
        $tenantId = $siswa->tenant_id;

        $currentStep = $request->input('current_step') ? (int) $request->input('current_step') : null;
        $validated = $this->validateSiswaRequest($request, $siswa->id);

        $stepTitles = [
            1 => 'Identitas Pokok & Akademik',
            2 => 'Alamat & Kontak',
            3 => 'Fisik & Kesehatan',
            4 => 'Orang Tua & Wali',
            5 => 'Registrasi & Dokumen',
        ];

        DB::beginTransaction();
        try {
            // 1. Update Identitas Pokok jika Step 1 atau Full Save
            if (!$currentStep || $currentStep === 1) {
                if (isset($validated['nik'])) $siswa->nik = $validated['nik'];
                if (isset($validated['no_kk'])) $siswa->no_kk = $validated['no_kk'];
                if (isset($validated['nisn'])) $siswa->nisn = $validated['nisn'];
                if (isset($validated['nis'])) $siswa->nis = $validated['nis'];
                if (isset($validated['nama_lengkap'])) $siswa->nama_lengkap = $validated['nama_lengkap'];
                if (isset($validated['nama_panggilan'])) $siswa->nama_panggilan = $validated['nama_panggilan'];
                if (isset($validated['jenis_kelamin'])) $siswa->jenis_kelamin = $validated['jenis_kelamin'];
                if (isset($validated['tempat_lahir'])) $siswa->tempat_lahir = $validated['tempat_lahir'];
                if (isset($validated['tanggal_lahir'])) $siswa->tanggal_lahir = $validated['tanggal_lahir'];
                if (isset($validated['agama'])) $siswa->agama = $validated['agama'];
                if (isset($validated['kewarganegaraan'])) $siswa->kewarganegaraan = $validated['kewarganegaraan'];
                if (isset($validated['bahasa_sehari_hari'])) $siswa->bahasa_sehari_hari = $validated['bahasa_sehari_hari'];
                if (isset($validated['status'])) $siswa->status_siswa = $validated['status'];

                if (!empty($validated['id_kelas'])) {
                    $kelas = DB::table('akademik.kelas')->where('id', $validated['id_kelas'])->first();
                    if ($kelas) $siswa->kelas_saat_ini = $kelas->nama_kelas;
                }
                if (!empty($validated['id_jurusan'])) {
                    $jurusan = DB::table('akademik.jurusan')->where('id', $validated['id_jurusan'])->first();
                    if ($jurusan) $siswa->jurusan = $jurusan->nama_jurusan;
                }
                if (!empty($validated['id_angkatan'])) {
                    $angkatan = DB::table('akademik.angkatan')->where('id', $validated['id_angkatan'])->first();
                    if ($angkatan) $siswa->angkatan = (int)$angkatan->nama_angkatan;
                }
                if (isset($validated['ukuran_seragam_sekolah'])) $siswa->ukuran_seragam_sekolah = $validated['ukuran_seragam_sekolah'];
                if (isset($validated['ukuran_seragam_olahraga'])) $siswa->ukuran_seragam_olahraga = $validated['ukuran_seragam_olahraga'];
            }

            // 2. Update Alamat jika Step 2 atau Full Save
            if (!$currentStep || $currentStep === 2) {
                if (isset($validated['alamat_kk'])) $siswa->alamat = $validated['alamat_kk'];
                if (isset($validated['alamat_domisili'])) $siswa->alamat_domisili = $validated['alamat_domisili'];
                if (isset($validated['rt'])) $siswa->rt = $validated['rt'];
                if (isset($validated['rw'])) $siswa->rw = $validated['rw'];
                if (isset($validated['kode_pos'])) $siswa->kode_pos = $validated['kode_pos'];
                if (isset($validated['id_provinsi'])) $siswa->id_provinsi = $validated['id_provinsi'];
                if (isset($validated['id_kota'])) $siswa->id_kota = $validated['id_kota'];
                if (isset($validated['id_kecamatan'])) $siswa->id_kecamatan = $validated['id_kecamatan'];
                if (isset($validated['id_kelurahan'])) $siswa->id_kelurahan = $validated['id_kelurahan'];
                if (isset($validated['status_tinggal'])) $siswa->status_tinggal = $validated['status_tinggal'];
                if (isset($validated['tinggal_dengan'])) $siswa->tinggal_dengan = $validated['tinggal_dengan'];
                if (isset($validated['email'])) $siswa->email = $validated['email'];
                if (isset($validated['no_telepon_rumah'])) $siswa->no_telepon_rumah = $validated['no_telepon_rumah'];
                if (isset($validated['no_telepon_siswa'])) $siswa->no_hp = $validated['no_telepon_siswa'];
                if (isset($validated['no_telepon_orang_tua'])) $siswa->no_telepon_orang_tua = $validated['no_telepon_orang_tua'];
            }

            // 3. Update Fisik & Bantuan jika Step 3 atau Full Save
            if (!$currentStep || $currentStep === 3) {
                if (isset($validated['anak_ke'])) $siswa->anak_ke = $validated['anak_ke'];
                if (isset($validated['jumlah_saudara'])) $siswa->jumlah_saudara = $validated['jumlah_saudara'];
                if (isset($validated['saudara_tiri'])) $siswa->saudara_tiri = $validated['saudara_tiri'];
                if (isset($validated['saudara_angkat'])) $siswa->saudara_angkat = $validated['saudara_angkat'];
                if (isset($validated['jarak_rumah'])) $siswa->jarak_rumah = $validated['jarak_rumah'];
                if (isset($validated['transportasi'])) $siswa->transportasi = $validated['transportasi'];
                if (isset($validated['penerima_kps'])) $siswa->penerima_kps = !empty($validated['penerima_kps']);
                if (isset($validated['punya_kip'])) $siswa->punya_kip = !empty($validated['punya_kip']);
                if (isset($validated['layak_kip'])) $siswa->layak_kip = !empty($validated['layak_kip']);
                if (isset($validated['no_kip'])) $siswa->no_kip = $validated['no_kip'];
                if (isset($validated['alasan_layak'])) $siswa->alasan_layak = $validated['alasan_layak'];

                if (!empty($validated['tinggi_badan']) || !empty($validated['berat_badan'])) {
                    $this->saveFisikKesehatan($siswa->id, $tenantId, $validated);
                }
            }

            // 4. Update Orang Tua jika Step 4 atau Full Save
            if (!$currentStep || $currentStep === 4) {
                $this->saveOrangTua($siswa->id, $tenantId, $validated);
            }

            // 5. Update Registrasi & Dokumen jika Step 5 atau Full Save
            if (!$currentStep || $currentStep === 5) {
                if (!empty($validated['jenis_pendaftaran']) || !empty($validated['tanggal_masuk'])) {
                    $this->saveRegistrasi($siswa->id, $tenantId, $validated);
                }
                $this->handleUploadedFiles($request, $siswa->id, $tenantId, $siswa);
            }

            $siswa->save();

            DB::commit();

            if ($request->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'Data siswa berhasil diperbarui.', 'data' => $siswa]);
            }

            $successMsg = $currentStep 
                ? "Data Langkah {$currentStep} (" . ($stepTitles[$currentStep] ?? '') . ") berhasil diperbarui ke database!"
                : "Seluruh data siswa {$siswa->nama_lengkap} berhasil diperbarui.";

            return redirect()->route('siswa.edit', ['id' => $siswa->id])
                ->with('success', $successMsg);
        } catch (\Throwable $e) {
            DB::rollBack();
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
            }
            return back()->withErrors(['general' => 'Gagal memperbarui data siswa: ' . $e->getMessage()])->withInput();
        }
    }

    /**
     * Helper simpan / update data Orang Tua
     */
    private function saveOrangTua(string $siswaId, string $tenantId, array $data): void
    {
        // 1. Ayah
        if (!empty($data['nama_ayah']) || !empty($data['nik_ayah'])) {
            SiswaOrangTua::withoutTenant()->updateOrCreate(
                ['siswa_id' => $siswaId, 'hubungan' => 'Ayah'],
                [
                    'tenant_id'       => $tenantId,
                    'nama_lengkap'    => $data['nama_ayah'] ?: 'Ayah',
                    'nik'             => $data['nik_ayah'] ?? null,
                    'id_tempat_lahir' => $data['id_tempat_lahir_ayah'] ?? null,
                    'tempat_lahir'    => $data['tempat_lahir_ayah'] ?? null,
                    'tanggal_lahir'   => $data['tanggal_lahir_ayah'] ?? null,
                    'kewarganegaraan' => $data['kewarganegaraan_ayah'] ?? 'WNI',
                    'status_hidup'    => $data['status_hidup_ayah'] ?? 'Hidup',
                    'pendidikan'      => $data['pendidikan_ayah'] ?? null,
                    'pekerjaan'       => $data['pekerjaan_ayah'] ?? null,
                    'penghasilan'     => $this->parsePenghasilan($data['penghasilan_ayah'] ?? null),
                    'agama'           => $data['agama_ayah'] ?? 'Islam',
                    'is_aktif'        => true,
                ]
            );
        }

        // 2. Ibu
        if (!empty($data['nama_ibu']) || !empty($data['nik_ibu'])) {
            SiswaOrangTua::withoutTenant()->updateOrCreate(
                ['siswa_id' => $siswaId, 'hubungan' => 'Ibu'],
                [
                    'tenant_id'       => $tenantId,
                    'nama_lengkap'    => $data['nama_ibu'],
                    'nik'             => $data['nik_ibu'],
                    'id_tempat_lahir' => $data['id_tempat_lahir_ibu'] ?? null,
                    'tempat_lahir'    => $data['tempat_lahir_ibu'] ?? null,
                    'tanggal_lahir'   => $data['tanggal_lahir_ibu'] ?? null,
                    'kewarganegaraan' => $data['kewarganegaraan_ibu'] ?? 'WNI',
                    'status_hidup'    => $data['status_hidup_ibu'] ?? 'Hidup',
                    'pendidikan'      => $data['pendidikan_ibu'] ?? null,
                    'pekerjaan'       => $data['pekerjaan_ibu'] ?? null,
                    'penghasilan'     => $this->parsePenghasilan($data['penghasilan_ibu'] ?? null),
                    'agama'           => $data['agama_ibu'] ?? 'Islam',
                    'is_aktif'        => true,
                ]
            );
        }

        // 3. Wali
        if (!empty($data['nama_wali']) || !empty($data['nik_wali'])) {
            SiswaOrangTua::withoutTenant()->updateOrCreate(
                ['siswa_id' => $siswaId, 'hubungan' => 'Wali'],
                [
                    'tenant_id'       => $tenantId,
                    'nama_lengkap'    => $data['nama_wali'],
                    'nik'             => $data['nik_wali'] ?? null,
                    'id_tempat_lahir' => $data['id_tempat_lahir_wali'] ?? null,
                    'tempat_lahir'    => $data['tempat_lahir_wali'] ?? null,
                    'tanggal_lahir'   => $data['tanggal_lahir_wali'] ?? null,
                    'kewarganegaraan' => $data['kewarganegaraan_wali'] ?? 'WNI',
                    'status_hidup'    => 'Hidup',
                    'hubungan_wali'   => $data['hubungan_wali'] ?? null,
                    'pendidikan'      => $data['pendidikan_wali'] ?? null,
                    'pekerjaan'       => $data['pekerjaan_wali'] ?? null,
                    'penghasilan'     => $this->parsePenghasilan($data['penghasilan_wali'] ?? null),
                    'agama'           => $data['agama_wali'] ?? 'Islam',
                    'is_aktif'        => true,
                ]
            );
        }
    }

    /**
     * Helper simpan / update Fisik & Kesehatan
     */
    private function saveFisikKesehatan(string $siswaId, string $tenantId, array $data): void
    {
        SiswaFisikKesehatan::withoutTenant()->updateOrCreate(
            ['siswa_id' => $siswaId],
            [
                'tenant_id'        => $tenantId,
                'tinggi_badan'     => (int)$data['tinggi_badan'],
                'berat_badan'      => (int)$data['berat_badan'],
                'lingkar_kepala'   => (int)$data['lingkar_kepala'],
                'golongan_darah'   => $data['golongan_darah'] ?? 'O',
                'riwayat_penyakit' => $data['penyakit_yang_diderita'] ?? null,
                'disabilitas'      => $data['kelainan_jasmani'] ?? 'Tidak Ada',
                'detail_semester'  => $data['kesehatan'] ?? null,
            ]
        );
    }

    /**
     * Helper simpan / update Registrasi
     */
    private function saveRegistrasi(string $siswaId, string $tenantId, array $data): void
    {
        SiswaRegistrasi::withoutTenant()->updateOrCreate(
            ['siswa_id' => $siswaId],
            [
                'tenant_id'                => $tenantId,
                'jenis_pendaftaran'        => $data['jenis_pendaftaran'],
                'asal_sekolah'             => $data['sekolah_asal'] ?? null,
                'jalur_diterima'           => $data['jalur_diterima'] ?? 'Zonasi',
                'tanggal_masuk'            => $data['tanggal_masuk'],
                'hobi'                     => $data['hobi'],
                'paud_formal'              => !empty($data['paud_formal']),
                'paud_non_formal'          => !empty($data['paud_non_formal']),
                'no_ijazah_sebelumnya'     => $data['no_ijazah_sebelumnya'] ?? null,
                'tanggal_ijazah_sebelumnya'=> $data['tanggal_ijazah_sebelumnya'] ?? null,
                'lama_belajar_sebelumnya'  => $data['lama_belajar_sebelumnya'] ?? 3,
                'sekolah_asal_mutasi'      => $data['sekolah_asal_mutasi'] ?? null,
                'pindah_dari_tingkat'      => $data['pindah_dari_tingkat'] ?? null,
                'pindah_no_surat'          => $data['pindah_no_surat'] ?? null,
                'keluar_karena'            => $data['keluar_karena'] ?? null,
                'tanggal_keluar'           => $data['tanggal_keluar'] ?? null,
                'alasan_keluar'            => $data['alasan_keluar'] ?? null,
                'sekolah_tujuan'           => $data['sekolah_tujuan'] ?? null,
                'nomor_skp'                => $data['nomor_skp'] ?? null,
                'tingkat_ditinggalkan'     => $data['tingkat_ditinggalkan'] ?? null,
                'diterima_di_tingkat'      => $data['diterima_di_tingkat'] ?? null,
                'nomor_ijazah_kelulusan'   => $data['nomor_ijazah_kelulusan'] ?? null,
                'nomor_skl'                => $data['nomor_skl'] ?? null,
                'keterangan_setelah_lulus' => $data['keterangan_setelah_lulus'] ?? null,
            ]
        );
    }

    /**
     * Helper upload berkas
     */
    private function handleUploadedFiles(Request $request, string $siswaId, string $tenantId, Siswa $siswa): void
    {
        $fileKeys = [
            'foto_profil', 'berkas_kk', 'berkas_akta', 'berkas_ijazah_sd',
            'berkas_ijazah_smp', 'berkas_ijazah_sma', 'berkas_mutasi_masuk',
            'berkas_mutasi_keluar', 'berkas_kip', 'berkas_pernyataan_baru', 'berkas_pernyataan_tka'
        ];

        foreach ($fileKeys as $key) {
            if ($request->hasFile($key)) {
                $file = $request->file($key);
                if ($file && $file->isValid()) {
                    $ext = $file->getClientOriginalExtension();
                    $filename = "{$key}_{$siswaId}_" . time() . ".{$ext}";
                    $path = $file->storeAs('dokumen_siswa', $filename, 'public');
                    $url = "/storage/{$path}";

                    SiswaDokumen::withoutTenant()->updateOrCreate(
                        ['siswa_id' => $siswaId, 'jenis_dokumen' => $key],
                        [
                            'tenant_id' => $tenantId,
                            'nama_file' => $filename,
                            'url_file'  => $url,
                        ]
                    );

                    if ($key === 'foto_profil') {
                        $siswa->update(['foto_url' => $url]);
                    }
                }
            }
        }
    }

    /**
     * Parse nominal penghasilan
     */
    private function parsePenghasilan(?string $penghasilan): int
    {
        if (empty($penghasilan)) return 0;
        if (is_numeric($penghasilan)) return (int)$penghasilan;
        $digits = preg_replace('/[^\d]/', '', $penghasilan);
        if ($digits !== '' && is_numeric($digits) && (int)$digits >= 500000) {
            return (int)$digits;
        }
        $lower = strtolower($penghasilan);
        if (str_contains($lower, 'kurang') || str_contains($lower, '500.000')) return 500000;
        if (str_contains($lower, '1.000.000') || str_contains($lower, '1 juta')) return 1500000;
        if (str_contains($lower, '2.000.000') || str_contains($lower, '2 juta')) return 3500000;
        if (str_contains($lower, '5.000.000') || str_contains($lower, '5 juta')) return 10000000;
        if (str_contains($lower, '20.000.000') || str_contains($lower, 'lebih')) return 25000000;
        return 0;
    }

    /**
     * Wilayah API: Ambil Seluruh Provinsi
     */
    public function getProvinsi(): JsonResponse
    {
        $provinsi = DB::table('core.provinsi')
            ->orderBy('nama_provinsi', 'asc')
            ->get(['id_provinsi', 'nama_provinsi']);

        return response()->json($provinsi);
    }

    /**
     * Wilayah API: Ambil Kota berdasarkan Provinsi
     */
    public function getKota(int $id_provinsi): JsonResponse
    {
        $kota = DB::table('core.kota')
            ->where('id_provinsi', $id_provinsi)
            ->orderBy('nama_kota', 'asc')
            ->get(['id_kota', 'id_provinsi', 'nama_kota']);

        return response()->json($kota);
    }

    /**
     * Wilayah API: Ambil Kecamatan berdasarkan Kota
     */
    public function getKecamatan(int $id_kota): JsonResponse
    {
        $kecamatan = DB::table('core.kecamatan')
            ->where('id_kota', $id_kota)
            ->orderBy('nama_kecamatan', 'asc')
            ->get(['id_kecamatan', 'id_kota', 'nama_kecamatan']);

        return response()->json($kecamatan);
    }

    /**
     * Wilayah API: Ambil Kelurahan berdasarkan Kecamatan
     */
    public function getKelurahan(int $id_kecamatan): JsonResponse
    {
        $kelurahan = DB::table('core.kelurahan')
            ->where('id_kecamatan', $id_kecamatan)
            ->orderBy('nama_kelurahan', 'asc')
            ->get(['id_kelurahan', 'id_kecamatan', 'nama_kelurahan']);

        return response()->json($kelurahan);
    }

    /**
     * Wilayah API: Ambil Seluruh Kota untuk Searchable Dropdown Tempat Lahir
     */
    public function getAllKota(): JsonResponse
    {
        $kota = DB::table('core.kota')
            ->orderBy('nama_kota', 'asc')
            ->get(['id_kota', 'nama_kota']);

        return response()->json($kota);
    }

    /**
     * Hapus / Arsipkan siswa (Soft Delete)
     */
    public function destroy(string $id): RedirectResponse|JsonResponse
    {
        $siswa = Siswa::withoutTenant()->findOrFail($id);
        $siswa->update(['is_active' => false]);

        if (request()->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Data siswa berhasil dipindahkan ke tong sampah.']);
        }

        return redirect()->route('menu.pengguna', ['tab' => 'siswa'])
            ->with('success', 'Data siswa berhasil dipindahkan ke tong sampah.');
    }

    /**
     * Cetak Lembar Buku Induk Resmi Lengkap Peserta Didik (F4 / Folio Standar Kemdikbud / Kemenag)
     */
    public function cetakLembarBukuInduk(Request $request, ?string $id = null)
    {
        $siswaId = $id ?: $request->input('id');
        if (!$siswaId) {
            abort(404, 'Siswa ID wajib disertakan untuk mencetak Buku Induk.');
        }

        $siswa = Siswa::withoutTenant()->find($siswaId);
        if (!$siswa) {
            abort(404, 'Data siswa tidak ditemukan.');
        }

        $tenantId = $siswa->tenant_id;
        $tenant = (array) DB::table('core.tenants')->where('id', $tenantId)->first();

        $siswaData = $siswa->toArray();

        // 1. Resolve Nama Kelas & Wilayah
        $kelas = DB::table('akademik.kelas')->where('nama_kelas', $siswa->kelas_saat_ini)->first();
        $siswaData['nama_kelas'] = $kelas?->nama_kelas ?? $siswa->kelas_saat_ini ?? '-';
        $siswaData['nama_sekolah'] = $tenant['nama_sekolah'] ?? '-';

        if (!empty($siswa->id_kelurahan)) {
            $wilayah = DB::table('core.kelurahan as kl')
                ->join('core.kecamatan as kc', 'kl.id_kecamatan', '=', 'kc.id_kecamatan')
                ->join('core.kota as kt', 'kc.id_kota', '=', 'kt.id_kota')
                ->join('core.provinsi as pr', 'kt.id_provinsi', '=', 'pr.id_provinsi')
                ->where('kl.id_kelurahan', $siswa->id_kelurahan)
                ->first([
                    'kl.nama_kelurahan',
                    'kc.nama_kecamatan',
                    'kt.nama_kota',
                    'pr.nama_provinsi'
                ]);
            if ($wilayah) {
                $siswaData['nama_kelurahan'] = $wilayah->nama_kelurahan;
                $siswaData['nama_kecamatan'] = $wilayah->nama_kecamatan;
                $siswaData['nama_kota']      = $wilayah->nama_kota;
                $siswaData['nama_provinsi']  = $wilayah->nama_provinsi;
            }
        }

        // 2. Orang Tua / Wali
        $siswaData['orang_tua'] = DB::table('siswa.orang_tua')->where('siswa_id', $siswaId)->get()->map(fn($o) => (array)$o)->toArray();

        // 3. Fisik & Kesehatan Semester 1-6
        $fisik = DB::table('siswa.fisik_kesehatan_siswa')->where('siswa_id', $siswaId)->first();
        $siswaData['fisik'] = (array)$fisik;
        $kesehatan = [];
        if ($fisik && !empty($fisik->detail_semester)) {
            $kesehatan = is_string($fisik->detail_semester) ? json_decode($fisik->detail_semester, true) : (array)$fisik->detail_semester;
        }
        $siswaData['kesehatan'] = $kesehatan;

        // 4. Prestasi Siswa
        $siswaData['prestasi'] = DB::table('kesiswaan.prestasi_siswa as ps')
            ->join('kesiswaan.prestasi_siswa_anggota as psa', 'ps.id', '=', 'psa.id_prestasi')
            ->where('psa.id_siswa', $siswaId)
            ->where('ps.is_active', true)
            ->orderBy('ps.tanggal_lomba', 'desc')
            ->get(['ps.*'])
            ->map(fn($p) => (array)$p)
            ->toArray();

        // 5. Beasiswa
        $beasiswa = DB::table('siswa.riwayat_beasiswa')
            ->where('siswa_id', $siswaId)
            ->orderBy('tahun_mulai', 'desc')
            ->get();
        $siswaData['beasiswa'] = $beasiswa->map(function ($b) {
            $bArr = (array)$b;
            $bArr['tahun_menerima'] = $b->tahun_mulai ? (string)$b->tahun_mulai : ($b->tahun_selesai ? (string)$b->tahun_selesai : '-');
            return $bArr;
        })->toArray();

        // 6. Tracer Study (Kuliah & Karir)
        $siswaData['tracer_kuliah'] = DB::table('tracer.riwayat_kuliah')->where('siswa_id', $siswaId)->orderBy('tahun_masuk', 'desc')->get()->map(fn($t) => (array)$t)->toArray();
        $siswaData['tracer_pekerjaan'] = DB::table('tracer.riwayat_pekerjaan')->where('siswa_id', $siswaId)->orderBy('tahun_mulai', 'desc')->get()->map(fn($t) => (array)$t)->toArray();

        // 7. Resolusi Kepala Sekolah pada Tanggal Cetak
        $tanggalCetak = $request->input('tanggal_cetak', date('Y-m-d'));
        $kepsek = DB::table('kepegawaian.riwayat_kepala_sekolah')
            ->where('tenant_id', $tenantId)
            ->where('tanggal_mulai', '<=', $tanggalCetak)
            ->where(function($q) use ($tanggalCetak) {
                $q->whereNull('tanggal_selesai')->orWhere('tanggal_selesai', '>=', $tanggalCetak);
            })
            ->orderBy('tanggal_mulai', 'desc')
            ->first();

        $kepsekArr = $kepsek ? (array)$kepsek : [
            'nama_kepsek' => $tenant['nama_kepsek'] ?? 'Kepala Sekolah',
            'nip_kepsek'  => $tenant['nip_kepsek'] ?? '-',
        ];

        $tanggalCetakFormatted = \Carbon\Carbon::parse($tanggalCetak)->translatedFormat('d F Y');

        return view('siswa::cetak_buku_induk', [
            'siswa'                 => $siswaData,
            'tenant'                => $tenant,
            'kepsek'                => $kepsekArr,
            'tanggalCetakFormatted' => $tanggalCetakFormatted,
        ]);
    }
}

