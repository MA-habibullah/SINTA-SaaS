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
use Modules\Akademik\Entities\NilaiRapor;
use Modules\Akademik\Entities\PemetaanMapel;
use Modules\Akademik\Entities\TahunAjaran;
use Modules\Akademik\Jobs\BulkPrintRaporJob;
use Modules\Core\Entities\SekolahIdentitas;
use Modules\Core\Entities\Tenant;
use Modules\Core\Services\SecurityPayloadService;
use Modules\Siswa\Entities\Siswa;
use Shuchkin\SimpleXLSXGen;

class RaporController extends Controller
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
     * Bespoke Hub Pencetakan Rapor & Hasil Belajar
     */
    public function index(Request $request): InertiaResponse|JsonResponse
    {
        $user = auth()->user();
        $isSuperAdmin = $user && ($user->isSuperAdmin() || in_array($user->role?->nama_role ?? '', ['super_admin', 'superadmin', 'admin']));
        $tenantId = $this->resolveTenantId($request);

        // Standardisasi Zero-SSR Pure Client Hydration
        $isInitialSsr = !$request->header('X-Inertia') && !$request->has('async');

        if ($isInitialSsr) {
            $tenants = [];
            if ($isSuperAdmin) {
                $tenants = Tenant::where(function ($q) {
                        $q->whereIn('status', ['active', 'Aktif', 'aktif'])->orWhereNull('status');
                    })
                    ->orderBy('nama_sekolah', 'asc')
                    ->get(['id', 'nama_sekolah', 'npsn'])
                    ->map(fn($t) => [
                        'id'       => $t->id,
                        'nama'     => $t->nama_sekolah,
                        'subLabel' => "NPSN: " . ($t->npsn ?? '-'),
                    ]);
            }

            return Inertia::render('Akademik/Rapor/Index', [
                'kelasList'       => null,
                'tahunAjaranList' => null,
                'students'        => null,
                'stats'           => null,
                'subjects'        => null,
                'filters'         => null,
                'tenants'         => $tenants,
                'activeTenantId'  => $tenantId,
            ]);
        }

        // 1. Ambil Master Tahun Ajaran & Kelas
        $tahunAjaranQuery = TahunAjaran::withoutTenant();
        if ($tenantId) {
            $tahunAjaranQuery->where('tenant_id', $tenantId);
        }
        $tahunAjaranList = $tahunAjaranQuery->orderBy('nama_tahun_ajaran', 'desc')->get(['id', 'nama_tahun_ajaran', 'is_active']);

        $kelasQuery = Kelas::withoutTenant()->with(['jenjang', 'jurusan']);
        if ($tenantId) {
            $kelasQuery->where('tenant_id', $tenantId);
        }
        $kelasList = $kelasQuery->orderBy('nama_kelas', 'asc')->get(['id', 'kode_kelas', 'nama_kelas', 'id_jenjang', 'id_jurusan', 'is_active']);

        // Default Filter
        $activeTa = $tahunAjaranList->firstWhere('is_active', true) ?? $tahunAjaranList->first();
        $tahunAjaranId = $request->input('tahun_ajaran_id', $activeTa?->id);
        $tahunAjaranNama = $tahunAjaranList->firstWhere('id', $tahunAjaranId)?->nama_tahun_ajaran ?? ($activeTa?->nama_tahun_ajaran ?? date('Y') . '/' . (date('Y') + 1));
        $semester = $request->input('semester', 'Ganjil');
        $kelasId = $request->input('kelas_id', $kelasList->first()?->id);
        $search = trim((string)$request->input('search', ''));
        $filterStatus = $request->input('status', 'semua'); // 'semua', 'siap_cetak', 'belum_lengkap'

        // 2. Resolve Selected Class
        $selectedKelas = $kelasList->firstWhere('id', $kelasId);
        $namaKelas = $selectedKelas?->nama_kelas ?? $kelasId;

        $studentsData = [];
        $subjectsList = [];
        $stats = [
            'total_siswa'     => 0,
            'siap_cetak'      => 0,
            'belum_lengkap'   => 0,
            'rata_rata_kelas' => 0,
            'total_mapel'     => 0,
        ];

        if (!empty($namaKelas)) {
            // Ambil Daftar Mapel Rombel (dari PemetaanMapel atau Master Mapel)
            $pemetaanQuery = PemetaanMapel::withoutTenant()->with('mapel');
            if ($tenantId) {
                $pemetaanQuery->where('tenant_id', $tenantId);
            }
            if ($selectedKelas) {
                $pemetaanQuery->where('kelas_id', $selectedKelas->id);
            }
            if (!empty($tahunAjaranNama)) {
                $pemetaanQuery->where('tahun_ajaran', $tahunAjaranNama);
            }
            if (!empty($semester)) {
                $pemetaanQuery->where('semester', $semester);
            }
            $pemetaanItems = $pemetaanQuery->get();

            if ($pemetaanItems->isNotEmpty()) {
                $subjectsList = $pemetaanItems->map(function ($p) {
                    return [
                        'id'         => $p->mata_pelajaran_id ?? $p->mapel?->id,
                        'nama_mapel' => $p->mapel?->nama_mata_pelajaran ?? $p->nama_pemetaan_mapel ?? 'Mata Pelajaran',
                        'kode_mapel' => $p->mapel?->kode_mata_pelajaran ?? '-',
                        'kkm'        => $p->kkm ?? 75,
                        'guru_nama'  => $p->guru?->name ?? '-',
                    ];
                })->unique('id')->values()->all();
            } else {
                // Fallback ke master mapel aktif
                $mapelQuery = MataPelajaran::withoutTenant()->where('is_active', true);
                if ($tenantId) {
                    $mapelQuery->where('tenant_id', $tenantId);
                }
                $subjectsList = $mapelQuery->orderBy('nama_mata_pelajaran', 'asc')->get(['id', 'nama_mata_pelajaran', 'kategori'])->map(fn($m) => [
                    'id'         => $m->id,
                    'nama_mapel' => $m->nama_mata_pelajaran,
                    'kode_mapel' => $m->kategori ?? 'MP',
                    'kkm'        => 75,
                    'guru_nama'  => '-',
                ])->all();
            }

            $totalMapelCount = count($subjectsList);
            $stats['total_mapel'] = $totalMapelCount;

            // Ambil Siswa Rombel
            $siswaQuery = Siswa::withoutTenant()
                ->where(function ($q) use ($selectedKelas, $namaKelas) {
                    $q->where('kelas_saat_ini', $namaKelas);
                    if ($selectedKelas) {
                        $q->orWhere('kelas_saat_ini', $selectedKelas->id);
                    }
                })
                ->where(function ($q) {
                    $q->where('status_siswa', 'Aktif')->orWhere('is_active', true);
                });

            if ($tenantId) {
                $siswaQuery->where('tenant_id', $tenantId);
            }

            if (!empty($search)) {
                $siswaQuery->where(function ($q) use ($search) {
                    $q->where('nama_lengkap', 'ILIKE', "%{$search}%")
                        ->orWhere('nisn', 'ILIKE', "%{$search}%")
                        ->orWhere('nis', 'ILIKE', "%{$search}%");
                });
            }

            $siswaList = $siswaQuery->orderBy('nama_lengkap', 'asc')->get();
            $siswaIds = $siswaList->pluck('id')->all();

            // Ambil Nilai Rapor Siswa
            $nilaiList = NilaiRapor::withoutTenant()
                ->with('mataPelajaran')
                ->whereIn('siswa_id', $siswaIds)
                ->where('semester', $semester)
                ->get();

            $nilaiGrouped = $nilaiList->groupBy('siswa_id');

            $totalClassScore = 0;
            $gradedStudentsCount = 0;

            foreach ($siswaList as $siswa) {
                $studentGrades = $nilaiGrouped->get($siswa->id, collect());
                $gradedMapelCount = $studentGrades->unique('mata_pelajaran_id')->count();

                $sumScore = $studentGrades->sum('nilai_akhir');
                $avgScore = $gradedMapelCount > 0 ? round($sumScore / $gradedMapelCount, 1) : 0;

                if ($avgScore > 0) {
                    $totalClassScore += $avgScore;
                    $gradedStudentsCount++;
                }

                $isLengkap = ($totalMapelCount > 0 && $gradedMapelCount >= $totalMapelCount) || ($gradedMapelCount > 0 && $gradedMapelCount >= max(1, $totalMapelCount));
                $progressPercent = $totalMapelCount > 0 ? min(100, round(($gradedMapelCount / $totalMapelCount) * 100)) : 0;

                // Filter status
                if ($filterStatus === 'siap_cetak' && !$isLengkap) {
                    continue;
                }
                if ($filterStatus === 'belum_lengkap' && $isLengkap) {
                    continue;
                }

                if ($isLengkap) {
                    $stats['siap_cetak']++;
                } else {
                    $stats['belum_lengkap']++;
                }

                $studentsData[] = [
                    'id'               => $siswa->id,
                    'nisn'             => $siswa->nisn ?? '-',
                    'nis'              => $siswa->nis ?? '-',
                    'nama_lengkap'     => $siswa->nama_lengkap,
                    'jenis_kelamin'    => $siswa->jenis_kelamin ?? '-',
                    'kelas_saat_ini'   => $siswa->kelas_saat_ini ?? $namaKelas,
                    'mapel_terisi'     => $gradedMapelCount,
                    'total_mapel'      => $totalMapelCount,
                    'progress_percent' => $progressPercent,
                    'rata_rata_nilai'  => $avgScore,
                    'is_lengkap'       => $isLengkap,
                    'grades'           => $studentGrades->map(fn($g) => [
                        'mata_pelajaran_id' => $g->mata_pelajaran_id,
                        'nama_mapel'        => $g->mataPelajaran?->nama_mata_pelajaran ?? '-',
                        'nilai_akhir'       => (float)$g->nilai_akhir,
                        'capaian_tinggi'    => $g->capaian_kompetensi_tinggi,
                        'capaian_rendah'    => $g->capaian_kompetensi_rendah,
                    ])->values()->all(),
                ];
            }

            $stats['total_siswa'] = count($studentsData);
            $stats['rata_rata_kelas'] = $gradedStudentsCount > 0 ? round($totalClassScore / $gradedStudentsCount, 1) : 0;
        }

        // Response Data
        $responseData = [
            'kelasList' => $kelasList->map(fn($k) => [
                'id'       => $k->id,
                'nama'     => $k->nama_kelas,
                'subLabel' => ($k->jenjang?->nama_jenjang ?? '') . ' ' . ($k->jurusan?->nama_jurusan ?? ''),
            ]),
            'tahunAjaranList' => $tahunAjaranList->map(fn($t) => [
                'id'       => $t->id,
                'nama'     => $t->nama_tahun_ajaran,
                'subLabel' => $t->is_active ? 'Tahun Ajaran Aktif' : '',
            ]),
            'students' => $studentsData,
            'subjects' => $subjectsList,
            'stats'    => $stats,
            'filters'  => [
                'tenant_id'       => $tenantId,
                'tahun_ajaran_id' => $tahunAjaranId,
                'tahun_ajaran'    => $tahunAjaranNama,
                'semester'        => $semester,
                'kelas_id'        => $kelasId,
                'nama_kelas'      => $namaKelas,
                'search'          => $search,
                'status'          => $filterStatus,
            ],
            'activeTenantId' => $tenantId,
        ];

        if (($request->has('async') || $request->wantsJson()) && !$request->header('X-Inertia')) {
            return response()->json([
                'success' => true,
                'data'    => SecurityPayloadService::sanitize($responseData),
            ]);
        }

        return Inertia::render('Akademik/Rapor/Index', [
            'kelasList'       => $responseData['kelasList'],
            'tahunAjaranList' => $responseData['tahunAjaranList'],
            'students'        => $responseData['students'],
            'subjects'        => $responseData['subjects'],
            'stats'           => $responseData['stats'],
            'filters'         => $responseData['filters'],
            'activeTenantId'  => $tenantId,
        ]);
    }

    /**
     * Preview / Cetak HTML Rapor Siswa Satuan (Laporan Capaian Belajar)
     */
    public function previewHtml(string $siswaId, Request $request)
    {
        $semester = $request->input('semester', 'Ganjil');
        $tahunAjaran = $request->input('tahun_ajaran', '');

        $siswa = Siswa::withoutTenant()->with(['orangTua'])->find($siswaId);
        if (!$siswa) {
            abort(404, 'Data siswa tidak ditemukan.');
        }

        $identitas = Tenant::find($siswa->tenant_id) ?? SekolahIdentitas::withoutTenant()->where('tenant_id', $siswa->tenant_id)->first();

        $nilaiQuery = NilaiRapor::withoutTenant()
            ->with('mataPelajaran')
            ->where('siswa_id', $siswaId)
            ->where('semester', $semester);

        $nilaiList = $nilaiQuery->get();

        return view('akademik::rapor_cetak', [
            'siswa'        => $siswa,
            'identitas'    => $identitas,
            'nilaiList'    => $nilaiList,
            'semester'     => $semester,
            'tahunAjaran'  => $tahunAjaran ?: ($siswa->tahun_masuk ?? date('Y') . '/' . (date('Y') + 1)),
            'tempat'       => $request->input('tempat', $identitas?->kabupaten_kota ?? 'Jakarta'),
            'tanggal'      => $request->input('tanggal', date('d F Y')),
            'waliKelas'    => $request->input('wali_kelas', 'Wali Kelas'),
        ]);
    }

    /**
     * Preview / Cetak HTML Lembar Identitas Peserta Didik (Buku Induk A4)
     */
    public function previewIdentitas(string $siswaId, Request $request)
    {
        $siswa = Siswa::withoutTenant()->with(['orangTua'])->find($siswaId);
        if (!$siswa) {
            abort(404, 'Data siswa tidak ditemukan.');
        }

        $identitas = Tenant::find($siswa->tenant_id) ?? SekolahIdentitas::withoutTenant()->where('tenant_id', $siswa->tenant_id)->first();

        return view('akademik::identitas_peserta_didik', [
            'siswa'     => $siswa,
            'identitas' => $identitas,
            'tempat'    => $request->input('tempat', $identitas?->kabupaten_kota ?? 'Jakarta'),
            'tanggal'   => $request->input('tanggal', date('d F Y')),
        ]);
    }

    /**
     * Preview / Cetak HTML Rapor Massal Satu Kelas (Multi-Page Print Ready)
     */
    public function previewHtmlKelas(Request $request)
    {
        $kelasId = $request->input('kelas_id');
        $semester = $request->input('semester', 'Ganjil');
        $tenantId = $this->resolveTenantId($request);

        if (!$kelasId) {
            return response("<h3 style='font-family:sans-serif; text-align:center; margin-top:50px;'>Silakan pilih kelas terlebih dahulu.</h3>", 400);
        }

        $namaKelas = $kelasId;
        if (Str::isUuid($kelasId)) {
            $k = Kelas::withoutTenant()->find($kelasId);
            if ($k) {
                $namaKelas = $k->nama_kelas;
            }
        }

        $siswaQuery = Siswa::withoutTenant()
            ->with(['orangTua'])
            ->where(function ($q) use ($kelasId, $namaKelas) {
                $q->where('kelas_saat_ini', $namaKelas)->orWhere('kelas_saat_ini', $kelasId);
            })
            ->where(function ($q) {
                $q->where('status_siswa', 'Aktif')->orWhere('is_active', true);
            });

        if ($tenantId) {
            $siswaQuery->where('tenant_id', $tenantId);
        }

        $siswaList = $siswaQuery->orderBy('nama_lengkap', 'asc')->get();

        if ($siswaList->isEmpty()) {
            return response("<h3 style='font-family:sans-serif; text-align:center; margin-top:50px;'>Tidak ada siswa aktif ditemukan pada kelas {$namaKelas}.</h3>", 200, ['Content-Type' => 'text/html']);
        }

        $identitas = Tenant::find($siswaList->first()->tenant_id) ?? SekolahIdentitas::withoutTenant()->where('tenant_id', $siswaList->first()->tenant_id)->first();

        // Ambil nilai seluruh siswa
        $siswaIds = $siswaList->pluck('id')->all();
        $nilaiGrouped = NilaiRapor::withoutTenant()
            ->with('mataPelajaran')
            ->whereIn('siswa_id', $siswaIds)
            ->where('semester', $semester)
            ->get()
            ->groupBy('siswa_id');

        return view('akademik::rapor_cetak', [
            'siswaList'    => $siswaList,
            'nilaiGrouped' => $nilaiGrouped,
            'identitas'    => $identitas,
            'semester'     => $semester,
            'namaKelas'    => $namaKelas,
            'tempat'       => $request->input('tempat', $identitas?->kabupaten_kota ?? 'Jakarta'),
            'tanggal'      => $request->input('tanggal', date('d F Y')),
            'waliKelas'    => $request->input('wali_kelas', 'Wali Kelas'),
        ]);
    }

    /**
     * Preview / Cetak HTML Lembar Identitas Massal Satu Kelas
     */
    public function previewIdentitasKelas(Request $request)
    {
        $kelasId = $request->input('kelas_id');
        $tenantId = $this->resolveTenantId($request);

        if (!$kelasId) {
            return response("<h3 style='font-family:sans-serif; text-align:center; margin-top:50px;'>Silakan pilih kelas terlebih dahulu.</h3>", 400);
        }

        $namaKelas = $kelasId;
        if (Str::isUuid($kelasId)) {
            $k = Kelas::withoutTenant()->find($kelasId);
            if ($k) {
                $namaKelas = $k->nama_kelas;
            }
        }

        $siswaQuery = Siswa::withoutTenant()
            ->with(['orangTua'])
            ->where(function ($q) use ($kelasId, $namaKelas) {
                $q->where('kelas_saat_ini', $namaKelas)->orWhere('kelas_saat_ini', $kelasId);
            })
            ->where(function ($q) {
                $q->where('status_siswa', 'Aktif')->orWhere('is_active', true);
            });

        if ($tenantId) {
            $siswaQuery->where('tenant_id', $tenantId);
        }

        $siswaList = $siswaQuery->orderBy('nama_lengkap', 'asc')->get();

        if ($siswaList->isEmpty()) {
            return response("<h3 style='font-family:sans-serif; text-align:center; margin-top:50px;'>Tidak ada siswa ditemukan pada kelas {$namaKelas}.</h3>", 200, ['Content-Type' => 'text/html']);
        }

        $identitas = Tenant::find($siswaList->first()->tenant_id) ?? SekolahIdentitas::withoutTenant()->where('tenant_id', $siswaList->first()->tenant_id)->first();

        return view('akademik::identitas_peserta_didik', [
            'siswa'     => $siswaList->first(),
            'siswaList' => $siswaList,
            'identitas' => $identitas,
            'tempat'    => $request->input('tempat', $identitas?->kabupaten_kota ?? 'Jakarta'),
            'tanggal'   => $request->input('tanggal', date('d F Y')),
        ]);
    }

    /**
     * Ekspor Ledger Nilai Rapor Satu Kelas (Excel .xlsx)
     */
    public function exportLedger(Request $request)
    {
        $kelasId = $request->input('kelas_id');
        $semester = $request->input('semester', 'Ganjil');
        $tahunAjaranId = $request->input('tahun_ajaran_id');
        $tenantId = $this->resolveTenantId($request);

        $selectedKelas = Kelas::withoutTenant()->find($kelasId);
        $namaKelas = $selectedKelas?->nama_kelas ?? 'Semua_Kelas';

        $siswaQuery = Siswa::withoutTenant()
            ->where(function ($q) use ($selectedKelas, $namaKelas, $kelasId) {
                $q->where('kelas_saat_ini', $namaKelas);
                if ($selectedKelas) {
                    $q->orWhere('kelas_saat_ini', $selectedKelas->id);
                } elseif ($kelasId) {
                    $q->orWhere('kelas_saat_ini', $kelasId);
                }
            })
            ->where(function ($q) {
                $q->where('status_siswa', 'Aktif')->orWhere('is_active', true);
            });

        if ($tenantId) {
            $siswaQuery->where('tenant_id', $tenantId);
        }

        $siswaList = $siswaQuery->orderBy('nama_lengkap', 'asc')->get();

        // Ambil Mapel
        $mapelQuery = MataPelajaran::withoutTenant()->where('is_active', true);
        if ($tenantId) {
            $mapelQuery->where('tenant_id', $tenantId);
        }
        $mapelList = $mapelQuery->orderBy('nama_mata_pelajaran', 'asc')->get(['id', 'nama_mata_pelajaran', 'kategori']);

        // Ambil Nilai
        $siswaIds = $siswaList->pluck('id')->all();
        $nilaiGrouped = NilaiRapor::withoutTenant()
            ->whereIn('siswa_id', $siswaIds)
            ->where('semester', $semester)
            ->get()
            ->groupBy('siswa_id');

        $rows = [];
        // Header
        $header = ['No', 'NISN', 'NIS', 'Nama Siswa', 'L/P'];
        foreach ($mapelList as $m) {
            $header[] = $m->nama_mata_pelajaran . ' (' . ($m->kategori ?? 'MP') . ')';
        }
        $header[] = 'Total Nilai';
        $header[] = 'Rata-Rata';
        $rows[] = $header;

        // Data Rows
        foreach ($siswaList as $idx => $s) {
            $studentGrades = $nilaiGrouped->get($s->id, collect())->keyBy('mata_pelajaran_id');
            $row = [
                $idx + 1,
                $s->nisn ?? '-',
                $s->nis ?? '-',
                $s->nama_lengkap,
                $s->jenis_kelamin ? substr($s->jenis_kelamin, 0, 1) : '-',
            ];

            $totalScore = 0;
            $countFilled = 0;
            foreach ($mapelList as $m) {
                $g = $studentGrades->get($m->id);
                if ($g && $g->nilai_akhir !== null) {
                    $score = (float)$g->nilai_akhir;
                    $row[] = $score;
                    $totalScore += $score;
                    $countFilled++;
                } else {
                    $row[] = '-';
                }
            }

            $avg = $countFilled > 0 ? round($totalScore / $countFilled, 1) : 0;
            $row[] = $totalScore;
            $row[] = $avg;
            $rows[] = $row;
        }

        $xlsx = SimpleXLSXGen::fromArray($rows, 'Ledger Nilai');
        $fileName = 'Ledger_Nilai_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $namaKelas) . "_{$semester}.xlsx";

        return response((string)$xlsx, 200, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ]);
    }

    /**
     * Dispatch Bulk Cetak Rapor ke Worker Queue
     */
    public function bulkQueue(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'kelas_id'        => 'required|string',
            'tahun_ajaran_id' => 'nullable|string',
            'semester'        => 'required|string|in:Ganjil,Genap',
        ]);

        $tenantId = $this->resolveTenantId($request) ?? session('tenant_id');
        $userId = auth()->id() ?? 'system';

        // Resolve Tahun Ajaran ID if empty
        $taId = $validated['tahun_ajaran_id'] ?? null;
        if (!$taId || !Str::isUuid($taId)) {
            $activeTa = TahunAjaran::withoutTenant()->where('tenant_id', $tenantId)->where('is_active', true)->first();
            $taId = $activeTa?->id ?? (string)Str::uuid();
        }

        BulkPrintRaporJob::dispatch(
            $tenantId,
            $validated['kelas_id'],
            $taId,
            $validated['semester'],
            $userId
        );

        if (($request->has('async') || $request->wantsJson()) && !$request->header('X-Inertia')) {
            return response()->json([
                'success' => true,
                'message' => 'Proses cetak massal PDF rapor telah berhasil dijadwalkan ke antrean server (Background Worker).'
            ]);
        }

        return back()->with('success', 'Pencetakan rapor sedang diproses di latar belakang.');
    }
}
