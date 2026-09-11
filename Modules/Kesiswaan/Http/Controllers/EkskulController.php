<?php

namespace Modules\Kesiswaan\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Modules\Core\Entities\Tenant;
use Modules\Core\Entities\User;
use Modules\Siswa\Entities\Siswa;
use Modules\Akademik\Entities\TahunAjaran;
use Modules\Akademik\Entities\Kelas;
use Modules\Kesiswaan\Entities\MasterEkskul;
use Modules\Kesiswaan\Entities\AnggotaEkskul;
use Modules\Kesiswaan\Entities\PembinaEkskul;
use Modules\Kesiswaan\Entities\JurnalEkskul;
use Modules\Kesiswaan\Entities\NilaiEkskul;
use Modules\Kesiswaan\Entities\PrestasiSiswa;
use Modules\Kesiswaan\Entities\PrestasiSiswaAnggota;
use Modules\Kesiswaan\Entities\KunciEkskul;

class EkskulController extends Controller
{
    private function checkIsSuperAdmin(): bool
    {
        $user = Auth::user();
        if (!$user) return false;
        
        $userRole = is_object($user->role) ? ($user->role->nama_role ?? '') : ($user->role ?? '');
        return ($user->isSuperAdmin() 
            || $userRole === 'super_admin' 
            || $user->tenant_id === '00000000-0000-0000-0000-000000000000' 
            || session('role') === 'super_admin' 
            || session('tenant_id') === '00000000-0000-0000-0000-000000000000');
    }

    public function index(Request $request): InertiaResponse|JsonResponse
    {
        $isSuperAdmin = $this->checkIsSuperAdmin();
        $selectedTenantId = $request->input('tenant_id', '');
        $activeTab = $request->input('tab', 'ekskul');
        $search = $request->input('search', '');
        $perPage = (int) $request->input('per_page', 15);

        // Academic period selection
        $optTaq = ($isSuperAdmin ? TahunAjaran::withoutTenant() : TahunAjaran::query())->orderBy('nama_tahun_ajaran', 'desc');
        if ($isSuperAdmin && $selectedTenantId && $selectedTenantId !== 'all') {
            $optTaq->where('tenant_id', $selectedTenantId);
        }
        $allTahunAjaran = $optTaq->get(['id', 'nama_tahun_ajaran', 'nama_tahun_ajaran as tahun', 'is_active', 'tenant_id']);
        if ($allTahunAjaran->isEmpty()) {
            $allTahunAjaran = ($isSuperAdmin ? TahunAjaran::withoutTenant() : TahunAjaran::query())->orderBy('nama_tahun_ajaran', 'desc')->get(['id', 'nama_tahun_ajaran', 'nama_tahun_ajaran as tahun', 'is_active', 'tenant_id']);
        }
        $activeTa = $allTahunAjaran->firstWhere('is_active', true) ?? $allTahunAjaran->first();

        $selectedTahunAjaranId = $request->input('tahun_ajaran_id', $activeTa?->id ?? '');
        $selectedSemester = $request->input('semester', 'Ganjil');

        // Contextual filters per tab
        $filterKategori     = $request->input('kategori', '');
        $filterStatus       = $request->input('status', '');
        $filterEkskulId     = $request->input('ekskul_id', '');
        $filterJabatan      = $request->input('jabatan', '');
        $filterPembinaId    = $request->input('pembina_id', '');
        $filterPredikat     = $request->input('predikat', '');
        $filterTingkat      = $request->input('tingkat', '');
        $filterJuara        = $request->input('juara', '');
        $filterJenisKelamin = $request->input('jenis_kelamin', '');

        $tenants = [];
        if ($isSuperAdmin) {
            $tenants = Tenant::select('id', 'nama_sekolah', 'npsn')
                ->where('id', '!=', '00000000-0000-0000-0000-000000000000')
                ->orderBy('nama_sekolah', 'asc')
                ->get();
        }

        // Apply tenant scope helper
        $applyTenant = function ($query, $tenantCol = 'tenant_id') use ($isSuperAdmin, $selectedTenantId) {
            if ($isSuperAdmin) {
                if ($selectedTenantId && $selectedTenantId !== 'all') {
                    $query->where($tenantCol, $selectedTenantId);
                }
            }
            return $query;
        };

        // 1. MASTER EKSKUL QUERY
        $ekskulQ = ($isSuperAdmin ? MasterEkskul::withoutTenant() : MasterEkskul::query())
            ->with([
                'pembina' => function ($q) use ($isSuperAdmin) {
                    if ($isSuperAdmin) $q->withoutGlobalScope('tenant_isolation');
                    $q->select('id', 'nama_pembina', 'no_hp', 'email');
                },
                'tenant:id,nama_sekolah,npsn'
            ])
            ->withCount(['anggota' => function ($q) use ($selectedTahunAjaranId, $selectedSemester, $isSuperAdmin) {
                if ($isSuperAdmin) $q->withoutGlobalScope('tenant_isolation');
                if ($selectedTahunAjaranId) $q->where('tahun_ajaran_id', $selectedTahunAjaranId);
                if ($selectedSemester) $q->where('semester', $selectedSemester);
            }])
            ->orderBy('nama_ekskul', 'asc');
        $applyTenant($ekskulQ, 'kesiswaan.master_ekskul.tenant_id');
        if ($activeTab === 'ekskul') {
            if ($search) {
                $ekskulQ->where(function ($q) use ($search) {
                    $q->where('nama_ekskul', 'ILIKE', "%{$search}%")
                      ->orWhere('kategori', 'ILIKE', "%{$search}%")
                      ->orWhere('tempat_latihan', 'ILIKE', "%{$search}%");
                });
            }
            if ($filterKategori) {
                $ekskulQ->where('kategori', $filterKategori);
            }
            if ($filterStatus !== '') {
                $ekskulQ->where('is_active', $filterStatus === '1' || $filterStatus === 'true');
            }
        }
        $ekskulList = $ekskulQ->paginate($perPage, ['*'], 'ekskul_page')->withQueryString();

        // 2. ANGGOTA EKSKUL QUERY (Filtered by Tahun Ajaran & Semester)
        $anggotaQ = ($isSuperAdmin ? AnggotaEkskul::withoutTenant() : AnggotaEkskul::query())
            ->with([
                'ekskul' => function ($q) use ($isSuperAdmin) {
                    if ($isSuperAdmin) $q->withoutGlobalScope('tenant_isolation');
                    $q->select('id', 'nama_ekskul', 'kategori');
                },
                'siswa' => function ($q) use ($isSuperAdmin) {
                    if ($isSuperAdmin) $q->withoutGlobalScope('tenant_isolation');
                    $q->select('id', 'nama_lengkap', 'nisn', 'nis', 'jenis_kelamin', 'kelas_saat_ini');
                },
                'tenant:id,nama_sekolah,npsn'
            ])
            ->orderBy('created_at', 'desc');
        $applyTenant($anggotaQ, 'kesiswaan.anggota_ekskul.tenant_id');

        if ($selectedTahunAjaranId) {
            $anggotaQ->where('tahun_ajaran_id', $selectedTahunAjaranId);
        }
        if ($selectedSemester) {
            $anggotaQ->where('semester', $selectedSemester);
        }

        if ($activeTab === 'anggota') {
            if ($search) {
                $anggotaQ->where(function ($q) use ($search, $isSuperAdmin) {
                    $q->where('jabatan', 'ILIKE', "%{$search}%")
                      ->orWhere('status_keanggotaan', 'ILIKE', "%{$search}%")
                      ->orWhereHas('siswa', function ($sq) use ($search, $isSuperAdmin) {
                          if ($isSuperAdmin) $sq->withoutGlobalScope('tenant_isolation');
                          $sq->where('nama_lengkap', 'ILIKE', "%{$search}%")
                            ->orWhere('nisn', 'ILIKE', "%{$search}%");
                      })
                      ->orWhereHas('ekskul', function ($eq) use ($search, $isSuperAdmin) {
                          if ($isSuperAdmin) $eq->withoutGlobalScope('tenant_isolation');
                          $eq->where('nama_ekskul', 'ILIKE', "%{$search}%");
                      });
                });
            }
            if ($filterEkskulId) {
                $anggotaQ->where('ekskul_id', $filterEkskulId);
            }
            if ($filterJabatan) {
                $anggotaQ->where('jabatan', $filterJabatan);
            }
            if ($filterStatus) {
                $anggotaQ->where('status_keanggotaan', $filterStatus);
            }
        }
        $anggotaList = $anggotaQ->paginate($perPage, ['*'], 'anggota_page')->withQueryString();

        // 3. PEMBINA EKSKUL QUERY
        $pembinaQ = ($isSuperAdmin ? PembinaEkskul::withoutTenant() : PembinaEkskul::query())
            ->with([
                'ekskul' => function ($q) use ($isSuperAdmin) {
                    if ($isSuperAdmin) $q->withoutGlobalScope('tenant_isolation');
                    $q->select('id', 'nama_ekskul', 'pembina_id');
                },
                'guru' => function ($q) use ($isSuperAdmin) {
                    if ($isSuperAdmin) $q->withoutGlobalScope('tenant_isolation');
                    $q->select('id', 'nama_lengkap', 'username', 'email');
                },
                'tenant:id,nama_sekolah,npsn'
            ])
            ->orderBy('nama_pembina', 'asc');
        $applyTenant($pembinaQ, 'kesiswaan.data_pembina.tenant_id');
        if ($activeTab === 'pembina') {
            if ($search) {
                $pembinaQ->where(function ($q) use ($search) {
                    $q->where('nama_pembina', 'ILIKE', "%{$search}%")
                      ->orWhere('nip', 'ILIKE', "%{$search}%")
                      ->orWhere('kategori_pembina', 'ILIKE', "%{$search}%");
                });
            }
            if ($filterKategori) {
                $pembinaQ->where('kategori_pembina', $filterKategori);
            }
            if ($filterJenisKelamin) {
                $pembinaQ->where('jenis_kelamin', $filterJenisKelamin);
            }
        }
        $pembinaList = $pembinaQ->paginate($perPage, ['*'], 'pembina_page')->withQueryString();

        // 4. JURNAL KEGIATAN QUERY (Filtered by Tahun Ajaran & Semester)
        $jurnalQ = ($isSuperAdmin ? JurnalEkskul::withoutTenant() : JurnalEkskul::query())
            ->with([
                'ekskul' => function ($q) use ($isSuperAdmin) {
                    if ($isSuperAdmin) $q->withoutGlobalScope('tenant_isolation');
                    $q->select('id', 'nama_ekskul');
                },
                'pembina' => function ($q) use ($isSuperAdmin) {
                    if ($isSuperAdmin) $q->withoutGlobalScope('tenant_isolation');
                    $q->select('id', 'nama_pembina');
                },
                'tenant:id,nama_sekolah,npsn'
            ])
            ->orderBy('tanggal_kegiatan', 'desc');
        $applyTenant($jurnalQ, 'kesiswaan.jurnal_ekskul.tenant_id');

        if ($selectedTahunAjaranId) {
            $jurnalQ->where('tahun_ajaran_id', $selectedTahunAjaranId);
        }
        if ($selectedSemester) {
            $jurnalQ->where('semester', $selectedSemester);
        }

        if ($activeTab === 'jurnal') {
            if ($search) {
                $jurnalQ->where(function ($q) use ($search, $isSuperAdmin) {
                    $q->where('materi_kegiatan', 'ILIKE', "%{$search}%")
                      ->orWhere('lokasi', 'ILIKE', "%{$search}%")
                      ->orWhereHas('ekskul', function ($eq) use ($search, $isSuperAdmin) {
                          if ($isSuperAdmin) $eq->withoutGlobalScope('tenant_isolation');
                          $eq->where('nama_ekskul', 'ILIKE', "%{$search}%");
                      });
                });
            }
            if ($filterEkskulId) {
                $jurnalQ->where('ekskul_id', $filterEkskulId);
            }
            if ($filterPembinaId) {
                $jurnalQ->where('pembina_id', $filterPembinaId);
            }
        }
        $jurnalList = $jurnalQ->paginate($perPage, ['*'], 'jurnal_page')->withQueryString();

        // 5. PENILAIAN EKSKUL QUERY (Filtered by Tahun Ajaran & Semester)
        $nilaiQ = ($isSuperAdmin ? NilaiEkskul::withoutTenant() : NilaiEkskul::query())
            ->with([
                'ekskul' => function ($q) use ($isSuperAdmin) {
                    if ($isSuperAdmin) $q->withoutGlobalScope('tenant_isolation');
                    $q->select('id', 'nama_ekskul');
                },
                'siswa' => function ($q) use ($isSuperAdmin) {
                    if ($isSuperAdmin) $q->withoutGlobalScope('tenant_isolation');
                    $q->select('id', 'nama_lengkap', 'nisn', 'nis');
                },
                'tenant:id,nama_sekolah,npsn'
            ])
            ->orderBy('created_at', 'desc');
        $applyTenant($nilaiQ, 'kesiswaan.nilai_ekskul.tenant_id');

        if ($selectedTahunAjaranId) {
            $nilaiQ->where('tahun_ajaran_id', $selectedTahunAjaranId);
        }
        if ($selectedSemester) {
            $nilaiQ->where('semester', $selectedSemester);
        }

        if ($activeTab === 'nilai') {
            if ($search) {
                $nilaiQ->where(function ($q) use ($search, $isSuperAdmin) {
                    $q->where('predikat', 'ILIKE', "%{$search}%")
                      ->orWhere('keterangan', 'ILIKE', "%{$search}%")
                      ->orWhereHas('siswa', function ($sq) use ($search, $isSuperAdmin) {
                          if ($isSuperAdmin) $sq->withoutGlobalScope('tenant_isolation');
                          $sq->where('nama_lengkap', 'ILIKE', "%{$search}%");
                      });
                });
            }
            if ($filterEkskulId) {
                $nilaiQ->where('ekskul_id', $filterEkskulId);
            }
            if ($filterPredikat) {
                $nilaiQ->where('predikat', $filterPredikat);
            }
        }
        $nilaiList = $nilaiQ->paginate($perPage, ['*'], 'nilai_page')->withQueryString();

        // 6. PRESTASI SISWA QUERY
        $prestasiQ = ($isSuperAdmin ? PrestasiSiswa::withoutTenant() : PrestasiSiswa::query())
            ->with([
                'anggota.siswa' => function ($q) use ($isSuperAdmin) {
                    if ($isSuperAdmin) $q->withoutGlobalScope('tenant_isolation');
                    $q->select('id', 'nama_lengkap', 'nisn');
                },
                'tenant:id,nama_sekolah,npsn'
            ])
            ->orderBy('tanggal_lomba', 'desc');
        $applyTenant($prestasiQ, 'kesiswaan.prestasi_siswa.tenant_id');
        if ($activeTab === 'prestasi') {
            if ($search) {
                $prestasiQ->where(function ($q) use ($search) {
                    $q->where('nama_lomba', 'ILIKE', "%{$search}%")
                      ->orWhere('bidang_lomba', 'ILIKE', "%{$search}%")
                      ->orWhere('juara', 'ILIKE', "%{$search}%")
                      ->orWhere('tingkat_kejuaraan', 'ILIKE', "%{$search}%")
                      ->orWhere('penyelenggara', 'ILIKE', "%{$search}%");
                });
            }
            if ($filterKategori) {
                $prestasiQ->where('bidang_lomba', $filterKategori);
            }
            if ($filterTingkat) {
                $prestasiQ->where('tingkat_kejuaraan', $filterTingkat);
            }
            if ($filterJuara) {
                $prestasiQ->where('juara', $filterJuara);
            }
        }
        $prestasiList = $prestasiQ->paginate($perPage, ['*'], 'prestasi_page')->withQueryString();

        // 7. REFERENCE OPTIONS FOR DROPDOWNS & MODALS
        $optEkskulQ = ($isSuperAdmin ? MasterEkskul::withoutTenant() : MasterEkskul::query())->orderBy('nama_ekskul');
        $applyTenant($optEkskulQ);
        $allEkskul = $optEkskulQ->get(['id', 'nama_ekskul', 'kategori', 'tenant_id']);

        $optPembinaQ = ($isSuperAdmin ? PembinaEkskul::withoutTenant() : PembinaEkskul::query())->orderBy('nama_pembina');
        $applyTenant($optPembinaQ);
        $allPembina = $optPembinaQ->get(['id', 'nama_pembina', 'tenant_id']);

        $optKelasQ = ($isSuperAdmin ? Kelas::withoutTenant() : Kelas::query())->orderBy('nama_kelas', 'asc');
        $applyTenant($optKelasQ);
        $allKelas = $optKelasQ->get(['id', 'nama_kelas', 'kode_kelas', 'tenant_id']);

        $optSiswaQ = ($isSuperAdmin ? Siswa::withoutTenant() : Siswa::query())->orderBy('nama_lengkap');
        $applyTenant($optSiswaQ);
        $allSiswa = $optSiswaQ->limit(500)->get(['id', 'nama_lengkap', 'nisn', 'nis', 'kelas_saat_ini', 'tenant_id']);

        // 8. STATISTICS METRICS (Contextual with Selected Academic Period)
        $statEkskul = ($isSuperAdmin ? MasterEkskul::withoutTenant() : MasterEkskul::query());
        $statAnggota = ($isSuperAdmin ? AnggotaEkskul::withoutTenant() : AnggotaEkskul::query());
        $statPembina = ($isSuperAdmin ? PembinaEkskul::withoutTenant() : PembinaEkskul::query());
        $statJurnal = ($isSuperAdmin ? JurnalEkskul::withoutTenant() : JurnalEkskul::query());
        $statPrestasi = ($isSuperAdmin ? PrestasiSiswa::withoutTenant() : PrestasiSiswa::query());

        $applyTenant($statEkskul);
        $applyTenant($statAnggota);
        $applyTenant($statPembina);
        $applyTenant($statJurnal);
        $applyTenant($statPrestasi);

        if ($selectedTahunAjaranId) {
            $statAnggota->where('tahun_ajaran_id', $selectedTahunAjaranId);
            $statJurnal->where('tahun_ajaran_id', $selectedTahunAjaranId);
        }
        if ($selectedSemester) {
            $statAnggota->where('semester', $selectedSemester);
            $statJurnal->where('semester', $selectedSemester);
        }

        $stats = [
            'total_ekskul'   => $statEkskul->count(),
            'total_anggota'  => $statAnggota->count(),
            'total_pembina'  => $statPembina->count(),
            'total_jurnal'   => $statJurnal->count(),
            'total_prestasi' => $statPrestasi->count(),
        ];

        $payload = [
            'ekskulList'    => $ekskulList,
            'anggotaList'   => $anggotaList,
            'pembinaList'   => $pembinaList,
            'jurnalList'    => $jurnalList,
            'nilaiList'     => $nilaiList,
            'prestasiList'  => $prestasiList,
            'allEkskul'     => $allEkskul,
            'allPembina'    => $allPembina,
            'allKelas'      => $allKelas,
            'allSiswa'      => $allSiswa,
            'allTahunAjaran'=> $allTahunAjaran,
            'stats'         => $stats,
            'tenants'       => $tenants,
            'isSuperAdmin'  => $isSuperAdmin,
            'filters'       => [
                'tab'             => $activeTab,
                'search'          => $search,
                'tenant_id'       => $selectedTenantId,
                'tahun_ajaran_id' => $selectedTahunAjaranId,
                'semester'        => $selectedSemester,
                'per_page'        => $perPage,
                'kategori'        => $filterKategori,
                'status'          => $filterStatus,
                'ekskul_id'       => $filterEkskulId,
                'jabatan'         => $filterJabatan,
                'pembina_id'      => $filterPembinaId,
                'predikat'        => $filterPredikat,
                'tingkat'         => $filterTingkat,
                'juara'           => $filterJuara,
                'jenis_kelamin'   => $filterJenisKelamin,
            ],
        ];

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'data' => $payload]);
        }

        return Inertia::render('Kesiswaan/Ekskul/Index', $payload);
    }

    // ─────────────────────────────────────────
    // 1. MASTER EKSKUL MUTATIONS
    // ─────────────────────────────────────────
    public function storeEkskul(Request $request): RedirectResponse|JsonResponse
    {
        $user = Auth::user();
        $isSuperAdmin = $this->checkIsSuperAdmin();

        $validated = $request->validate([
            'nama_ekskul'    => 'required|string|max:255',
            'kategori'       => 'required|string|max:100',
            'deskripsi'      => 'nullable|string',
            'pembina_id'     => 'nullable|uuid',
            'hari_latihan'   => 'nullable|string|max:50',
            'jam_mulai'      => 'nullable|string|max:20',
            'jam_selesai'    => 'nullable|string|max:20',
            'tempat_latihan' => 'nullable|string|max:255',
            'kuota_maksimal' => 'nullable|integer|min:0',
            'tenant_id'      => 'nullable|uuid',
            'is_active'      => 'boolean',
        ]);

        $tenantId = ($isSuperAdmin && !empty($validated['tenant_id']))
            ? $validated['tenant_id']
            : ($user?->tenant_id ?? session('tenant_id') ?? Tenant::where('id', '!=', '00000000-0000-0000-0000-000000000000')->value('id') ?? Tenant::first()?->id);

        $ekskul = MasterEkskul::create([
            'id'                 => (string) Str::uuid(),
            'tenant_id'          => $tenantId,
            'nama_ekskul'        => $validated['nama_ekskul'],
            'nama_master_ekskul' => $validated['nama_ekskul'],
            'kategori'           => $validated['kategori'],
            'deskripsi'          => $validated['deskripsi'] ?? null,
            'pembina_id'         => $validated['pembina_id'] ?? null,
            'hari_latihan'       => $validated['hari_latihan'] ?? null,
            'jam_mulai'          => $validated['jam_mulai'] ?? null,
            'jam_selesai'        => $validated['jam_selesai'] ?? null,
            'tempat_latihan'     => $validated['tempat_latihan'] ?? null,
            'kuota_maksimal'     => $validated['kuota_maksimal'] ?? 50,
            'is_active'          => $validated['is_active'] ?? true,
        ]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Ekstrakurikuler berhasil ditambahkan.', 'data' => $ekskul], 201);
        }

        return back()->with('success', 'Ekstrakurikuler berhasil ditambahkan.');
    }

    public function updateEkskul(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $isSuperAdmin = $this->checkIsSuperAdmin();
        $ekskul = ($isSuperAdmin ? MasterEkskul::withoutTenant() : MasterEkskul::query())->findOrFail($id);

        $validated = $request->validate([
            'nama_ekskul'    => 'required|string|max:255',
            'kategori'       => 'required|string|max:100',
            'deskripsi'      => 'nullable|string',
            'pembina_id'     => 'nullable|uuid',
            'hari_latihan'   => 'nullable|string|max:50',
            'jam_mulai'      => 'nullable|string|max:20',
            'jam_selesai'    => 'nullable|string|max:20',
            'tempat_latihan' => 'nullable|string|max:255',
            'kuota_maksimal' => 'nullable|integer|min:0',
            'tenant_id'      => 'nullable|uuid',
            'is_active'      => 'boolean',
        ]);

        $updateData = [
            'nama_ekskul'        => $validated['nama_ekskul'],
            'nama_master_ekskul' => $validated['nama_ekskul'],
            'kategori'           => $validated['kategori'],
            'deskripsi'          => $validated['deskripsi'] ?? null,
            'pembina_id'         => $validated['pembina_id'] ?? null,
            'hari_latihan'       => $validated['hari_latihan'] ?? null,
            'jam_mulai'          => $validated['jam_mulai'] ?? null,
            'jam_selesai'        => $validated['jam_selesai'] ?? null,
            'tempat_latihan'     => $validated['tempat_latihan'] ?? null,
            'kuota_maksimal'     => $validated['kuota_maksimal'] ?? 50,
            'is_active'          => $validated['is_active'] ?? true,
        ];

        if ($isSuperAdmin && !empty($validated['tenant_id'])) {
            $updateData['tenant_id'] = $validated['tenant_id'];
        }

        $ekskul->update($updateData);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Ekstrakurikuler berhasil diperbarui.', 'data' => $ekskul->fresh()]);
        }

        return back()->with('success', 'Ekstrakurikuler berhasil diperbarui.');
    }

    public function destroyEkskul(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $isSuperAdmin = $this->checkIsSuperAdmin();
        $ekskul = ($isSuperAdmin ? MasterEkskul::withoutTenant() : MasterEkskul::query())->findOrFail($id);
        $ekskul->delete();

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Ekstrakurikuler berhasil dihapus.']);
        }

        return back()->with('success', 'Ekstrakurikuler berhasil dihapus.');
    }

    // ─────────────────────────────────────────
    // 2. ANGGOTA EKSKUL MUTATIONS & BATCH TOOLS
    // ─────────────────────────────────────────
    public function getSiswaByKelas(Request $request): JsonResponse
    {
        $isSuperAdmin = $this->checkIsSuperAdmin();
        $kelasId = $request->input('kelas_id');
        $ekskulId = $request->input('ekskul_id');
        $tahunAjaranId = $request->input('tahun_ajaran_id');
        $semester = $request->input('semester', 'Ganjil');
        $tenantId = $request->input('tenant_id');

        if (!$kelasId) {
            return response()->json([
                'success' => true,
                'data'    => [],
            ]);
        }

        $kelas = ($isSuperAdmin ? Kelas::withoutTenant() : Kelas::query())->find($kelasId);
        $namaKelas = $kelas ? $kelas->nama_kelas : $kelasId;

        $siswaQ = ($isSuperAdmin ? Siswa::withoutTenant() : Siswa::query())
            ->where('is_active', true)
            ->where(function ($q) use ($kelasId, $namaKelas) {
                $q->where('kelas_saat_ini', $kelasId)
                  ->orWhere('kelas_saat_ini', $namaKelas)
                  ->orWhere('kelas_saat_ini', 'ILIKE', "%{$namaKelas}%");
            })
            ->orderBy('nama_lengkap', 'asc');

        if ($isSuperAdmin && $tenantId && $tenantId !== 'all') {
            $siswaQ->where('tenant_id', $tenantId);
        } elseif ($kelas && $kelas->tenant_id) {
            $siswaQ->where('tenant_id', $kelas->tenant_id);
        }

        $siswaList = $siswaQ->get(['id', 'nama_lengkap', 'nisn', 'nis', 'jenis_kelamin', 'kelas_saat_ini', 'tenant_id']);

        $existingSiswaIds = [];
        if ($ekskulId && $tahunAjaranId) {
            $existingQ = ($isSuperAdmin ? AnggotaEkskul::withoutTenant() : AnggotaEkskul::query())
                ->where('ekskul_id', $ekskulId)
                ->where('tahun_ajaran_id', $tahunAjaranId)
                ->where('semester', $semester);
            $existingSiswaIds = $existingQ->pluck('siswa_id')->toArray();
        }

        $result = $siswaList->map(function ($s) use ($existingSiswaIds) {
            return [
                'id'                => $s->id,
                'nama_lengkap'      => $s->nama_lengkap,
                'nisn'              => $s->nisn,
                'nis'               => $s->nis,
                'jenis_kelamin'     => $s->jenis_kelamin,
                'kelas_saat_ini'    => $s->kelas_saat_ini,
                'is_already_member' => in_array($s->id, $existingSiswaIds),
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => $result,
        ]);
    }

    public function storeAnggotaBatch(Request $request): RedirectResponse|JsonResponse
    {
        $user = Auth::user();
        $isSuperAdmin = $this->checkIsSuperAdmin();

        $validated = $request->validate([
            'ekskul_id'          => 'required|uuid',
            'tahun_ajaran_id'    => 'required|string',
            'semester'           => 'required|string|in:Ganjil,Genap',
            'siswa_ids'          => 'required|array|min:1',
            'siswa_ids.*'        => 'required|uuid',
            'jabatan'            => 'nullable|string|max:50',
            'status_keanggotaan' => 'nullable|string|max:50',
            'tanggal_bergabung'  => 'nullable|date',
            'tenant_id'          => 'nullable|uuid',
        ]);

        $tenantId = ($isSuperAdmin && !empty($validated['tenant_id']))
            ? $validated['tenant_id']
            : ($user?->tenant_id ?? session('tenant_id') ?? Tenant::where('id', '!=', '00000000-0000-0000-0000-000000000000')->value('id') ?? Tenant::first()?->id);

        $insertedCount = 0;
        $updatedCount = 0;

        foreach ($validated['siswa_ids'] as $siswaId) {
            $existing = AnggotaEkskul::withoutTenant()
                ->where('ekskul_id', $validated['ekskul_id'])
                ->where('siswa_id', $siswaId)
                ->where('tahun_ajaran_id', $validated['tahun_ajaran_id'])
                ->where('semester', $validated['semester'])
                ->first();

            if ($existing) {
                $existing->update([
                    'jabatan'            => $validated['jabatan'] ?? $existing->jabatan ?? 'Anggota',
                    'status_keanggotaan' => $validated['status_keanggotaan'] ?? 'Aktif',
                    'is_active'          => true,
                ]);
                $updatedCount++;
            } else {
                AnggotaEkskul::create([
                    'id'                 => (string) Str::uuid(),
                    'tenant_id'          => $tenantId,
                    'ekskul_id'          => $validated['ekskul_id'],
                    'siswa_id'           => $siswaId,
                    'tahun_ajaran_id'    => $validated['tahun_ajaran_id'],
                    'semester'           => $validated['semester'],
                    'jabatan'            => $validated['jabatan'] ?? 'Anggota',
                    'tanggal_bergabung'  => $validated['tanggal_bergabung'] ?? date('Y-m-d'),
                    'status_keanggotaan' => $validated['status_keanggotaan'] ?? 'Aktif',
                    'is_active'          => true,
                ]);
                $insertedCount++;
            }
        }

        $msg = "Berhasil memproses keanggotaan ({$insertedCount} baru didaftarkan, {$updatedCount} diperbarui).";

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => $msg, 'inserted' => $insertedCount, 'updated' => $updatedCount], 201);
        }

        return back()->with('success', $msg);
    }

    public function copyAnggota(Request $request): RedirectResponse|JsonResponse
    {
        $user = Auth::user();
        $isSuperAdmin = $this->checkIsSuperAdmin();

        $validated = $request->validate([
            'ekskul_id'            => 'required|uuid',
            'from_tahun_ajaran_id' => 'required|string',
            'from_semester'        => 'required|string|in:Ganjil,Genap',
            'to_tahun_ajaran_id'   => 'required|string',
            'to_semester'          => 'required|string|in:Ganjil,Genap',
            'tenant_id'            => 'nullable|uuid',
        ]);

        $sourceQ = ($isSuperAdmin ? AnggotaEkskul::withoutTenant() : AnggotaEkskul::query())
            ->where('ekskul_id', $validated['ekskul_id'])
            ->where('tahun_ajaran_id', $validated['from_tahun_ajaran_id'])
            ->where('semester', $validated['from_semester'])
            ->where('status_keanggotaan', 'Aktif');

        $sourceMembers = $sourceQ->get();

        if ($sourceMembers->isEmpty()) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Tidak ditemukan data anggota aktif pada periode sumber.'], 422);
            }
            return back()->with('error', 'Tidak ditemukan data anggota aktif pada periode sumber.');
        }

        $tenantId = ($isSuperAdmin && !empty($validated['tenant_id']))
            ? $validated['tenant_id']
            : ($user?->tenant_id ?? session('tenant_id') ?? Tenant::where('id', '!=', '00000000-0000-0000-0000-000000000000')->value('id') ?? Tenant::first()?->id);

        $copiedCount = 0;
        foreach ($sourceMembers as $m) {
            $exists = AnggotaEkskul::withoutTenant()
                ->where('ekskul_id', $validated['ekskul_id'])
                ->where('siswa_id', $m->siswa_id)
                ->where('tahun_ajaran_id', $validated['to_tahun_ajaran_id'])
                ->where('semester', $validated['to_semester'])
                ->exists();

            if (!$exists) {
                AnggotaEkskul::create([
                    'id'                 => (string) Str::uuid(),
                    'tenant_id'          => $m->tenant_id ?: $tenantId,
                    'ekskul_id'          => $m->ekskul_id,
                    'siswa_id'           => $m->siswa_id,
                    'tahun_ajaran_id'    => $validated['to_tahun_ajaran_id'],
                    'semester'           => $validated['to_semester'],
                    'jabatan'            => $m->jabatan ?: 'Anggota',
                    'nomor_anggota'      => $m->nomor_anggota,
                    'tanggal_bergabung'  => date('Y-m-d'),
                    'status_keanggotaan' => 'Aktif',
                    'catatan'            => 'Salinan dari periode sebelumnya',
                    'is_active'          => true,
                ]);
                $copiedCount++;
            }
        }

        $msg = "Berhasil menyalin {$copiedCount} anggota ekstrakurikuler ke periode aktif saat ini.";

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => $msg, 'copied' => $copiedCount]);
        }

        return back()->with('success', $msg);
    }

    public function storeAnggota(Request $request): RedirectResponse|JsonResponse
    {
        $user = Auth::user();
        $isSuperAdmin = $this->checkIsSuperAdmin();

        $validated = $request->validate([
            'ekskul_id'          => 'required|uuid',
            'siswa_id'           => 'required|uuid',
            'tahun_ajaran_id'    => 'nullable|string',
            'semester'           => 'nullable|string|in:Ganjil,Genap',
            'jabatan'            => 'nullable|string|max:50',
            'nomor_anggota'      => 'nullable|string|max:50',
            'tanggal_bergabung'  => 'nullable|date',
            'status_keanggotaan' => 'nullable|string|max:50',
            'catatan'            => 'nullable|string',
            'tenant_id'          => 'nullable|uuid',
        ]);

        $tenantId = ($isSuperAdmin && !empty($validated['tenant_id']))
            ? $validated['tenant_id']
            : ($user?->tenant_id ?? session('tenant_id') ?? Tenant::where('id', '!=', '00000000-0000-0000-0000-000000000000')->value('id') ?? Tenant::first()?->id);

        $anggota = AnggotaEkskul::create([
            'id'                 => (string) Str::uuid(),
            'tenant_id'          => $tenantId,
            'ekskul_id'          => $validated['ekskul_id'],
            'siswa_id'           => $validated['siswa_id'],
            'tahun_ajaran_id'    => $validated['tahun_ajaran_id'] ?? $request->input('tahun_ajaran_id', ''),
            'semester'           => $validated['semester'] ?? $request->input('semester', 'Ganjil'),
            'jabatan'            => $validated['jabatan'] ?? 'Anggota',
            'nomor_anggota'      => $validated['nomor_anggota'] ?? null,
            'tanggal_bergabung'  => $validated['tanggal_bergabung'] ?? date('Y-m-d'),
            'status_keanggotaan' => $validated['status_keanggotaan'] ?? 'Aktif',
            'catatan'            => $validated['catatan'] ?? null,
            'is_active'          => true,
        ]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Anggota ekstrakurikuler berhasil ditambahkan.', 'data' => $anggota], 201);
        }

        return back()->with('success', 'Anggota ekstrakurikuler berhasil ditambahkan.');
    }

    public function updateAnggota(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $isSuperAdmin = $this->checkIsSuperAdmin();
        $anggota = ($isSuperAdmin ? AnggotaEkskul::withoutTenant() : AnggotaEkskul::query())->findOrFail($id);

        $validated = $request->validate([
            'ekskul_id'          => 'required|uuid',
            'siswa_id'           => 'required|uuid',
            'tahun_ajaran_id'    => 'nullable|string',
            'semester'           => 'nullable|string|in:Ganjil,Genap',
            'jabatan'            => 'nullable|string|max:50',
            'nomor_anggota'      => 'nullable|string|max:50',
            'tanggal_bergabung'  => 'nullable|date',
            'status_keanggotaan' => 'nullable|string|max:50',
            'catatan'            => 'nullable|string',
            'is_active'          => 'boolean',
        ]);

        $anggota->update($validated);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Data anggota berhasil diperbarui.', 'data' => $anggota->fresh()]);
        }

        return back()->with('success', 'Data anggota berhasil diperbarui.');
    }

    public function destroyAnggota(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $isSuperAdmin = $this->checkIsSuperAdmin();
        $anggota = ($isSuperAdmin ? AnggotaEkskul::withoutTenant() : AnggotaEkskul::query())->findOrFail($id);
        $anggota->delete();

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Anggota berhasil dihapus dari ekstrakurikuler.']);
        }

        return back()->with('success', 'Anggota berhasil dihapus dari ekstrakurikuler.');
    }

    // ─────────────────────────────────────────
    // 3. DATA PEMBINA MUTATIONS
    // ─────────────────────────────────────────
    public function storePembina(Request $request): RedirectResponse|JsonResponse
    {
        $user = Auth::user();
        $isSuperAdmin = $this->checkIsSuperAdmin();

        $validated = $request->validate([
            'nama_pembina'     => 'required|string|max:255',
            'guru_id'          => 'nullable|uuid',
            'nip'              => 'nullable|string|max:50',
            'jenis_kelamin'    => 'nullable|string|max:10',
            'no_hp'            => 'nullable|string|max:50',
            'email'            => 'nullable|email|max:100',
            'kategori_pembina' => 'nullable|string|max:50',
            'tenant_id'        => 'nullable|uuid',
            // User login credentials
            'username'         => 'nullable|string|max:100',
            'password'         => 'nullable|string|min:6',
        ]);

        $tenantId = ($isSuperAdmin && !empty($validated['tenant_id']))
            ? $validated['tenant_id']
            : ($user?->tenant_id ?? session('tenant_id') ?? Tenant::where('id', '!=', '00000000-0000-0000-0000-000000000000')->value('id') ?? Tenant::first()?->id);

        $guruId = $validated['guru_id'] ?? null;

        // Kelola pembuatan akun login Pembina jika username diisi
        if (!empty($validated['username'])) {
            $existingUser = User::where('username', $validated['username'])->first();
            if ($existingUser && (!$guruId || $existingUser->id !== $guruId)) {
                if ($request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => 'Username sudah digunakan oleh akun lain.'], 422);
                }
                return back()->with('error', 'Username sudah digunakan oleh akun lain.');
            }

            $roleId = DB::table('core.roles')->where('nama_role', 'pembina_ekskul')->value('id')
                ?? DB::table('core.roles')->where('nama_role', 'guru')->value('id')
                ?? '0e0a33b3-a715-49d1-b9d3-d43b4512f510';

            if ($guruId) {
                $userAccount = User::find($guruId);
                if ($userAccount) {
                    $userData = [
                        'nama_lengkap' => $validated['nama_pembina'],
                        'username'     => $validated['username'],
                        'email'        => $validated['email'] ?? $userAccount->email,
                    ];
                    if (!empty($validated['password'])) {
                        $userData['password_hash'] = Hash::make($validated['password']);
                    }
                    $userAccount->update($userData);
                }
            } else {
                $newUser = User::create([
                    'id'            => (string) Str::uuid(),
                    'tenant_id'     => $tenantId,
                    'role_id'       => $roleId,
                    'nama_lengkap'  => $validated['nama_pembina'],
                    'username'      => $validated['username'],
                    'email'         => $validated['email'] ?? ($validated['username'] . '@sekolah.id'),
                    'password_hash' => Hash::make($validated['password'] ?? 'password123'),
                    'is_active'     => true,
                ]);
                $guruId = $newUser->id;
            }
        }

        $pembina = PembinaEkskul::create([
            'id'                => (string) Str::uuid(),
            'tenant_id'         => $tenantId,
            'nama_pembina'      => $validated['nama_pembina'],
            'nama_data_pembina' => $validated['nama_pembina'],
            'guru_id'           => $guruId,
            'nip'               => $validated['nip'] ?? null,
            'jenis_kelamin'     => $validated['jenis_kelamin'] ?? null,
            'no_hp'             => $validated['no_hp'] ?? null,
            'email'             => $validated['email'] ?? null,
            'kategori_pembina'  => $validated['kategori_pembina'] ?? 'Guru Internal',
            'is_active'         => true,
        ]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Pembina ekstrakurikuler berhasil ditambahkan.', 'data' => $pembina], 201);
        }

        return back()->with('success', 'Pembina ekstrakurikuler berhasil ditambahkan.');
    }

    public function updatePembina(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $isSuperAdmin = $this->checkIsSuperAdmin();
        $pembina = ($isSuperAdmin ? PembinaEkskul::withoutTenant() : PembinaEkskul::query())->findOrFail($id);

        $validated = $request->validate([
            'nama_pembina'     => 'required|string|max:255',
            'guru_id'          => 'nullable|uuid',
            'nip'              => 'nullable|string|max:50',
            'jenis_kelamin'    => 'nullable|string|max:10',
            'no_hp'            => 'nullable|string|max:50',
            'email'            => 'nullable|email|max:100',
            'kategori_pembina' => 'nullable|string|max:50',
            'is_active'        => 'boolean',
            // User login credentials
            'username'         => 'nullable|string|max:100',
            'password'         => 'nullable|string|min:6',
        ]);

        $guruId = $validated['guru_id'] ?? $pembina->guru_id;

        // Kelola Akun Login Pembina saat update
        if (!empty($validated['username'])) {
            $existingUser = User::where('username', $validated['username'])->first();
            if ($existingUser && (!$guruId || $existingUser->id !== $guruId)) {
                if ($request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => 'Username sudah digunakan oleh akun lain.'], 422);
                }
                return back()->with('error', 'Username sudah digunakan oleh akun lain.');
            }

            if ($guruId) {
                $userAccount = User::find($guruId);
                if ($userAccount) {
                    $userData = [
                        'nama_lengkap' => $validated['nama_pembina'],
                        'username'     => $validated['username'],
                        'email'        => $validated['email'] ?? $userAccount->email,
                    ];
                    if (!empty($validated['password'])) {
                        $userData['password_hash'] = Hash::make($validated['password']);
                    }
                    if (isset($validated['is_active'])) {
                        $userData['is_active'] = $validated['is_active'];
                    }
                    $userAccount->update($userData);
                }
            } else {
                $roleId = DB::table('core.roles')->where('nama_role', 'pembina_ekskul')->value('id')
                    ?? DB::table('core.roles')->where('nama_role', 'guru')->value('id')
                    ?? '0e0a33b3-a715-49d1-b9d3-d43b4512f510';

                $newUser = User::create([
                    'id'            => (string) Str::uuid(),
                    'tenant_id'     => $pembina->tenant_id,
                    'role_id'       => $roleId,
                    'nama_lengkap'  => $validated['nama_pembina'],
                    'username'      => $validated['username'],
                    'email'         => $validated['email'] ?? ($validated['username'] . '@sekolah.id'),
                    'password_hash' => Hash::make($validated['password'] ?? 'password123'),
                    'is_active'     => $validated['is_active'] ?? true,
                ]);
                $guruId = $newUser->id;
            }
        } elseif (!empty($validated['password']) && $guruId) {
            $userAccount = User::find($guruId);
            if ($userAccount) {
                $userAccount->update(['password_hash' => Hash::make($validated['password'])]);
            }
        }

        $pembina->update([
            'nama_pembina'      => $validated['nama_pembina'],
            'nama_data_pembina' => $validated['nama_pembina'],
            'guru_id'           => $guruId,
            'nip'               => $validated['nip'] ?? null,
            'jenis_kelamin'     => $validated['jenis_kelamin'] ?? null,
            'no_hp'             => $validated['no_hp'] ?? null,
            'email'             => $validated['email'] ?? null,
            'kategori_pembina'  => $validated['kategori_pembina'] ?? 'Guru Internal',
            'is_active'         => $validated['is_active'] ?? true,
        ]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Data pembina berhasil diperbarui.', 'data' => $pembina->fresh()]);
        }

        return back()->with('success', 'Data pembina berhasil diperbarui.');
    }

    public function destroyPembina(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $isSuperAdmin = $this->checkIsSuperAdmin();
        $pembina = ($isSuperAdmin ? PembinaEkskul::withoutTenant() : PembinaEkskul::query())->findOrFail($id);
        $pembina->delete();

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Data pembina berhasil dihapus.']);
        }

        return back()->with('success', 'Data pembina berhasil dihapus.');
    }

    // ─────────────────────────────────────────
    // 4. JURNAL KEGIATAN MUTATIONS
    // ─────────────────────────────────────────
    public function storeJurnal(Request $request): RedirectResponse|JsonResponse
    {
        $user = Auth::user();
        $isSuperAdmin = $this->checkIsSuperAdmin();

        $validated = $request->validate([
            'ekskul_id'         => 'required|uuid',
            'pembina_id'        => 'nullable|uuid',
            'tanggal_kegiatan'  => 'required|date',
            'jam_mulai'         => 'nullable|string|max:20',
            'jam_selesai'       => 'nullable|string|max:20',
            'materi_kegiatan'   => 'required|string',
            'lokasi'            => 'nullable|string|max:255',
            'jumlah_hadir'      => 'nullable|integer|min:0',
            'jumlah_absen'      => 'nullable|integer|min:0',
            'catatan_evaluasi'  => 'nullable|string',
            'foto'              => 'nullable|file|mimes:jpg,jpeg,png,webp|max:5120',
            'tenant_id'         => 'nullable|uuid',
        ]);

        $tenantId = ($isSuperAdmin && !empty($validated['tenant_id']))
            ? $validated['tenant_id']
            : ($user?->tenant_id ?? session('tenant_id') ?? Tenant::where('id', '!=', '00000000-0000-0000-0000-000000000000')->value('id') ?? Tenant::first()?->id);

        $fotoPath = null;
        if ($request->hasFile('foto')) {
            $fotoPath = Storage::url($request->file('foto')->store('kesiswaan/jurnal', 'public'));
        }

        $jurnal = JurnalEkskul::create([
            'id'               => (string) Str::uuid(),
            'tenant_id'        => $tenantId,
            'ekskul_id'        => $validated['ekskul_id'],
            'pembina_id'       => $validated['pembina_id'] ?? null,
            'tanggal_kegiatan' => $validated['tanggal_kegiatan'],
            'jam_mulai'        => $validated['jam_mulai'] ?? null,
            'jam_selesai'      => $validated['jam_selesai'] ?? null,
            'materi_kegiatan'  => $validated['materi_kegiatan'],
            'lokasi'           => $validated['lokasi'] ?? 'Area Latihan Sekolah',
            'jumlah_hadir'     => $validated['jumlah_hadir'] ?? 0,
            'jumlah_absen'     => $validated['jumlah_absen'] ?? 0,
            'foto_kegiatan'    => $fotoPath,
            'catatan_evaluasi' => $validated['catatan_evaluasi'] ?? null,
            'is_active'        => true,
        ]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Jurnal kegiatan berhasil dicatat.', 'data' => $jurnal], 201);
        }

        return back()->with('success', 'Jurnal kegiatan berhasil dicatat.');
    }

    public function updateJurnal(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $isSuperAdmin = $this->checkIsSuperAdmin();
        $jurnal = ($isSuperAdmin ? JurnalEkskul::withoutTenant() : JurnalEkskul::query())->findOrFail($id);

        $validated = $request->validate([
            'ekskul_id'         => 'required|uuid',
            'pembina_id'        => 'nullable|uuid',
            'tanggal_kegiatan'  => 'required|date',
            'jam_mulai'         => 'nullable|string|max:20',
            'jam_selesai'       => 'nullable|string|max:20',
            'materi_kegiatan'   => 'required|string',
            'lokasi'            => 'nullable|string|max:255',
            'jumlah_hadir'      => 'nullable|integer|min:0',
            'jumlah_absen'      => 'nullable|integer|min:0',
            'catatan_evaluasi'  => 'nullable|string',
            'foto'              => 'nullable|file|mimes:jpg,jpeg,png,webp|max:5120',
            'delete_foto'       => 'nullable|string',
        ]);

        $updateData = [
            'ekskul_id'        => $validated['ekskul_id'],
            'pembina_id'       => $validated['pembina_id'] ?? null,
            'tanggal_kegiatan' => $validated['tanggal_kegiatan'],
            'jam_mulai'        => $validated['jam_mulai'] ?? null,
            'jam_selesai'      => $validated['jam_selesai'] ?? null,
            'materi_kegiatan'  => $validated['materi_kegiatan'],
            'lokasi'           => $validated['lokasi'] ?? 'Area Latihan Sekolah',
            'jumlah_hadir'     => $validated['jumlah_hadir'] ?? 0,
            'jumlah_absen'     => $validated['jumlah_absen'] ?? 0,
            'catatan_evaluasi' => $validated['catatan_evaluasi'] ?? null,
        ];

        if ($request->hasFile('foto')) {
            if ($jurnal->foto_kegiatan) {
                $oldPath = str_replace('/storage/', '', $jurnal->foto_kegiatan);
                Storage::disk('public')->delete($oldPath);
            }
            $updateData['foto_kegiatan'] = Storage::url($request->file('foto')->store('kesiswaan/jurnal', 'public'));
        } elseif ($request->input('delete_foto') === '1') {
            if ($jurnal->foto_kegiatan) {
                $oldPath = str_replace('/storage/', '', $jurnal->foto_kegiatan);
                Storage::disk('public')->delete($oldPath);
            }
            $updateData['foto_kegiatan'] = null;
        }

        $jurnal->update($updateData);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Jurnal kegiatan berhasil diperbarui.', 'data' => $jurnal->fresh()]);
        }

        return back()->with('success', 'Jurnal kegiatan berhasil diperbarui.');
    }

    public function destroyJurnal(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $isSuperAdmin = $this->checkIsSuperAdmin();
        $jurnal = ($isSuperAdmin ? JurnalEkskul::withoutTenant() : JurnalEkskul::query())->findOrFail($id);

        if ($jurnal->foto_kegiatan) {
            $oldPath = str_replace('/storage/', '', $jurnal->foto_kegiatan);
            Storage::disk('public')->delete($oldPath);
        }

        $jurnal->delete();

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Jurnal kegiatan berhasil dihapus.']);
        }

        return back()->with('success', 'Jurnal kegiatan berhasil dihapus.');
    }

    // ─────────────────────────────────────────
    // 5. PENILAIAN EKSKUL MUTATIONS
    // ─────────────────────────────────────────
    public function storeNilai(Request $request): RedirectResponse|JsonResponse
    {
        $user = Auth::user();
        $isSuperAdmin = $this->checkIsSuperAdmin();

        $validated = $request->validate([
            'ekskul_id'       => 'required|uuid',
            'siswa_id'        => 'required|uuid',
            'tahun_ajaran_id' => 'nullable|string',
            'semester'        => 'nullable|string',
            'predikat'        => 'required|in:A,B,C,D',
            'nilai_angka'     => 'nullable|numeric|min:0|max:100',
            'keterangan'      => 'nullable|string',
            'tenant_id'       => 'nullable|uuid',
        ]);

        $tenantId = ($isSuperAdmin && !empty($validated['tenant_id']))
            ? $validated['tenant_id']
            : ($user?->tenant_id ?? session('tenant_id') ?? Tenant::where('id', '!=', '00000000-0000-0000-0000-000000000000')->value('id') ?? Tenant::first()?->id);

        $nilai = NilaiEkskul::updateOrCreate(
            [
                'tenant_id'       => $tenantId,
                'ekskul_id'       => $validated['ekskul_id'],
                'siswa_id'        => $validated['siswa_id'],
                'semester'        => $validated['semester'] ?? 'Ganjil',
            ],
            [
                'id'              => (string) Str::uuid(),
                'tahun_ajaran_id' => $validated['tahun_ajaran_id'] ?? null,
                'predikat'        => $validated['predikat'],
                'nilai_angka'     => $validated['nilai_angka'] ?? null,
                'keterangan'      => $validated['keterangan'] ?? null,
                'is_locked'       => false,
                'is_active'       => true,
            ]
        );

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Nilai ekstrakurikuler berhasil disimpan.', 'data' => $nilai], 201);
        }

        return back()->with('success', 'Nilai ekstrakurikuler berhasil disimpan.');
    }

    public function destroyNilai(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $isSuperAdmin = $this->checkIsSuperAdmin();
        $nilai = ($isSuperAdmin ? NilaiEkskul::withoutTenant() : NilaiEkskul::query())->findOrFail($id);
        $nilai->delete();

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Nilai berhasil dihapus.']);
        }

        return back()->with('success', 'Nilai berhasil dihapus.');
    }

    // ─────────────────────────────────────────
    // 6. PRESTASI SISWA MUTATIONS
    // ─────────────────────────────────────────
    public function storePrestasi(Request $request): RedirectResponse|JsonResponse
    {
        $user = Auth::user();
        $isSuperAdmin = $this->checkIsSuperAdmin();

        $validated = $request->validate([
            'nama_lomba'        => 'required|string|max:255',
            'bidang_lomba'      => 'required|string|max:100',
            'juara'             => 'required|string|max:100',
            'tingkat_kejuaraan' => 'required|string|max:100',
            'penyelenggara'     => 'nullable|string|max:255',
            'tempat_lomba'      => 'nullable|string|max:255',
            'tanggal_lomba'     => 'nullable|date',
            'guru_pendamping'   => 'nullable|string|max:255',
            'poin_prestasi'     => 'nullable|integer|min:0',
            'nomor_sertifikat'  => 'nullable|string|max:100',
            'deskripsi'         => 'nullable|string',
            'siswa_ids'         => 'nullable|array',
            'foto_bukti'        => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:10240',
            'tenant_id'         => 'nullable|uuid',
        ]);

        $tenantId = ($isSuperAdmin && !empty($validated['tenant_id']))
            ? $validated['tenant_id']
            : ($user?->tenant_id ?? session('tenant_id') ?? Tenant::where('id', '!=', '00000000-0000-0000-0000-000000000000')->value('id') ?? Tenant::first()?->id);

        $fotoBuktiPath = null;
        if ($request->hasFile('foto_bukti')) {
            $fotoBuktiPath = Storage::url($request->file('foto_bukti')->store('kesiswaan/prestasi', 'public'));
        }

        $prestasi = PrestasiSiswa::create([
            'id'                  => (string) Str::uuid(),
            'tenant_id'           => $tenantId,
            'nama_prestasi_siswa' => $validated['nama_lomba'],
            'nama_lomba'          => $validated['nama_lomba'],
            'bidang_lomba'        => $validated['bidang_lomba'],
            'juara'               => $validated['juara'],
            'tingkat_kejuaraan'   => $validated['tingkat_kejuaraan'],
            'penyelenggara'       => $validated['penyelenggara'] ?? null,
            'tempat_lomba'        => $validated['tempat_lomba'] ?? null,
            'tanggal_lomba'       => $validated['tanggal_lomba'] ?? date('Y-m-d'),
            'guru_pendamping'     => $validated['guru_pendamping'] ?? null,
            'poin_prestasi'       => $validated['poin_prestasi'] ?? 50,
            'nomor_sertifikat'    => $validated['nomor_sertifikat'] ?? null,
            'deskripsi'           => $validated['deskripsi'] ?? null,
            'foto_bukti_prestasi' => $fotoBuktiPath,
            'is_active'           => true,
        ]);

        // Attach anggota siswa
        if (!empty($validated['siswa_ids'])) {
            foreach ($validated['siswa_ids'] as $sId) {
                PrestasiSiswaAnggota::create([
                    'id'          => (string) Str::uuid(),
                    'id_prestasi' => $prestasi->id,
                    'id_siswa'    => $sId,
                    'created_at'  => now(),
                ]);
            }
        }

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Portofolio prestasi berhasil dicatat.', 'data' => $prestasi], 201);
        }

        return back()->with('success', 'Portofolio prestasi berhasil dicatat.');
    }

    public function updatePrestasi(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $isSuperAdmin = $this->checkIsSuperAdmin();
        $prestasi = ($isSuperAdmin ? PrestasiSiswa::withoutTenant() : PrestasiSiswa::query())->findOrFail($id);

        $validated = $request->validate([
            'nama_lomba'        => 'required|string|max:255',
            'bidang_lomba'      => 'required|string|max:100',
            'juara'             => 'required|string|max:100',
            'tingkat_kejuaraan' => 'required|string|max:100',
            'penyelenggara'     => 'nullable|string|max:255',
            'tempat_lomba'      => 'nullable|string|max:255',
            'tanggal_lomba'     => 'nullable|date',
            'guru_pendamping'   => 'nullable|string|max:255',
            'poin_prestasi'     => 'nullable|integer|min:0',
            'nomor_sertifikat'  => 'nullable|string|max:100',
            'deskripsi'         => 'nullable|string',
            'foto_bukti'        => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:10240',
            'delete_foto'       => 'nullable|string',
        ]);

        $updateData = [
            'nama_prestasi_siswa' => $validated['nama_lomba'],
            'nama_lomba'          => $validated['nama_lomba'],
            'bidang_lomba'        => $validated['bidang_lomba'],
            'juara'               => $validated['juara'],
            'tingkat_kejuaraan'   => $validated['tingkat_kejuaraan'],
            'penyelenggara'       => $validated['penyelenggara'] ?? null,
            'tempat_lomba'        => $validated['tempat_lomba'] ?? null,
            'tanggal_lomba'       => $validated['tanggal_lomba'] ?? null,
            'guru_pendamping'     => $validated['guru_pendamping'] ?? null,
            'poin_prestasi'       => $validated['poin_prestasi'] ?? 50,
            'nomor_sertifikat'    => $validated['nomor_sertifikat'] ?? null,
            'deskripsi'           => $validated['deskripsi'] ?? null,
        ];

        if ($request->hasFile('foto_bukti')) {
            if ($prestasi->foto_bukti_prestasi) {
                $oldPath = str_replace('/storage/', '', $prestasi->foto_bukti_prestasi);
                Storage::disk('public')->delete($oldPath);
            }
            $updateData['foto_bukti_prestasi'] = Storage::url($request->file('foto_bukti')->store('kesiswaan/prestasi', 'public'));
        } elseif ($request->input('delete_foto') === '1') {
            if ($prestasi->foto_bukti_prestasi) {
                $oldPath = str_replace('/storage/', '', $prestasi->foto_bukti_prestasi);
                Storage::disk('public')->delete($oldPath);
            }
            $updateData['foto_bukti_prestasi'] = null;
        }

        $prestasi->update($updateData);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Portofolio prestasi berhasil diperbarui.', 'data' => $prestasi->fresh()]);
        }

        return back()->with('success', 'Portofolio prestasi berhasil diperbarui.');
    }

    public function destroyPrestasi(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $isSuperAdmin = $this->checkIsSuperAdmin();
        $prestasi = ($isSuperAdmin ? PrestasiSiswa::withoutTenant() : PrestasiSiswa::query())->findOrFail($id);

        if ($prestasi->foto_bukti_prestasi) {
            $oldPath = str_replace('/storage/', '', $prestasi->foto_bukti_prestasi);
            Storage::disk('public')->delete($oldPath);
        }

        // Remove linked members
        PrestasiSiswaAnggota::where('id_prestasi', $id)->delete();
        $prestasi->delete();

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Data prestasi berhasil dihapus.']);
        }

        return back()->with('success', 'Data prestasi berhasil dihapus.');
    }
}
