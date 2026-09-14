<?php

namespace Modules\Kepegawaian\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Modules\Kepegawaian\Entities\SurveiGuru;
use Modules\Kepegawaian\Entities\SurveiGuruPertanyaan;
use Modules\Kepegawaian\Entities\SurveiGuruRespon;
use Modules\Core\Entities\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SurveiGuruController extends Controller
{
    public function index(Request $request): InertiaResponse|JsonResponse
    {
        $user = auth()->user();
        $isSuperAdmin = method_exists($user, 'isSuperAdmin') ? $user->isSuperAdmin() : (strtolower($user?->role?->nama_role ?? '') === 'super_admin');
        $tenants = $isSuperAdmin ? DB::table('core.tenants')->orderBy('nama_sekolah', 'asc')->get(['id', 'nama_sekolah', 'npsn']) : [];
        
        $activeTenantId = ($isSuperAdmin && $request->filled('tenant_id'))
            ? $request->input('tenant_id')
            : (session('tenant_id') ?? $user?->tenant_id);

        // 1. Kuesioner Survei Aktif & Jadwal
        $activeSurvei = SurveiGuru::when($activeTenantId, fn($q, $tId) => $q->where('tenant_id', $tId))
            ->where('status', 'Aktif')
            ->first();

        if (!$activeSurvei && $activeTenantId) {
            $activeSurvei = SurveiGuru::create([
                'id'              => Str::uuid()->toString(),
                'tenant_id'       => $activeTenantId,
                'judul_survei'    => 'Survei Evaluasi Kinerja Guru oleh Siswa Semester Ganjil 2026/2027',
                'deskripsi'       => 'Instrumen evaluasi kinerja mengajar, pedagogik, dan interaksi kelas oleh siswa secara berkala dan anonim.',
                'sasaran_survei'  => 'Asesmen Siswa',
                'tahun_ajaran'    => '2026/2027',
                'semester'        => 'Ganjil',
                'tanggal_mulai'   => now()->toDateString(),
                'tanggal_selesai' => now()->addMonths(2)->toDateString(),
                'status'          => 'Aktif',
            ]);
        }

        // 2. Daftar Butir Indikator Pertanyaan Dinamis (Skala Likert 5 Poin)
        $pertanyaanList = SurveiGuruPertanyaan::when($activeTenantId, fn($q, $tId) => $q->where('tenant_id', $tId))
            ->where('is_active', true)
            ->orderBy('nomor_urut', 'asc')
            ->get();

        // 3. Daftar Kuesioner Survei
        $surveiList = SurveiGuru::when($activeTenantId, fn($q, $tId) => $q->where('tenant_id', $tId))
            ->where('is_active', true)
            ->withCount('respons')
            ->when($request->search, function ($q, $search) {
                $q->where('judul_survei', 'ILIKE', "%{$search}%")
                  ->orWhere('deskripsi', 'ILIKE', "%{$search}%");
            })
            ->when($request->status, function ($q, $status) {
                $q->where('status', $status);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();

        // 4. Rekapitulasi Raport & Rating Evaluasi Guru (Skala Likert 1.00 - 5.00)
        $ratingSummary = SurveiGuruRespon::when($activeTenantId, fn($q, $tId) => $q->where('tenant_id', $tId))
            ->select(
                'guru_id',
                'nama_guru',
                DB::raw('COUNT(id) as total_responden'),
                DB::raw('ROUND(AVG(rata_rata_skor), 2) as rating_keseluruhan'),
                DB::raw('ROUND(AVG(skor_pedagogik), 2) as avg_pedagogik'),
                DB::raw('ROUND(AVG(skor_sosial_kepribadian), 2) as avg_sosial_kepribadian'),
                DB::raw('ROUND(AVG(skor_kedisiplinan), 2) as avg_kedisiplinan'),
                DB::raw('ROUND(AVG(skor_penguasaan_materi), 2) as avg_penguasaan_materi')
            )
            ->groupBy('guru_id', 'nama_guru')
            ->orderBy('rating_keseluruhan', 'desc')
            ->get()
            ->map(function ($item) {
                $skor = (float) $item->rating_keseluruhan;
                if ($skor >= 4.20) {
                    $item->kategori_raport = 'Sangat Baik';
                    $item->kategori_badge = 'bg-emerald-100 text-emerald-800 border-emerald-300';
                    $item->kategori_aksi = 'Kinerja Istimewa — Pertahankan & Apresiasi';
                } elseif ($skor >= 3.40) {
                    $item->kategori_raport = 'Baik';
                    $item->kategori_badge = 'bg-blue-100 text-blue-800 border-blue-300';
                    $item->kategori_aksi = 'Kinerja Memuaskan — Terus Tingkatkan';
                } elseif ($skor >= 2.60) {
                    $item->kategori_raport = 'Cukup';
                    $item->kategori_badge = 'bg-amber-100 text-amber-800 border-amber-300';
                    $item->kategori_aksi = 'Memenuhi Standar Minimal — Perlu Peningkatan';
                } elseif ($skor >= 1.80) {
                    $item->kategori_raport = 'Kurang';
                    $item->kategori_badge = 'bg-orange-100 text-orange-800 border-orange-300';
                    $item->kategori_aksi = 'Belum Memenuhi Standar — Butuh Perbaikan';
                } else {
                    $item->kategori_raport = 'Sangat Kurang';
                    $item->kategori_badge = 'bg-rose-100 text-rose-800 border-rose-300';
                    $item->kategori_aksi = 'Coaching Khusus oleh Kepala Sekolah';
                }
                return $item;
            });

        // 5. Umpan Balik Kualitatif Terbaru
        $recentFeedback = SurveiGuruRespon::when($activeTenantId, fn($q, $tId) => $q->where('tenant_id', $tId))
            ->with('survei:id,judul_survei')
            ->when($request->filter_guru, function ($q, $guruId) {
                $q->where('guru_id', $guruId);
            })
            ->when($request->filter_kelas, function ($q, $kelas) {
                $q->where('nama_kelas', $kelas);
            })
            ->latest('created_at')
            ->limit(20)
            ->get();

        // 6. KPI Statistik (Skala 1 - 5)
        $totalSurvei = SurveiGuru::when($activeTenantId, fn($q, $tId) => $q->where('tenant_id', $tId))->count();
        $totalRespon = SurveiGuruRespon::when($activeTenantId, fn($q, $tId) => $q->where('tenant_id', $tId))->count();
        $rataRataSekolah = $totalRespon > 0 ? round(SurveiGuruRespon::when($activeTenantId, fn($q, $tId) => $q->where('tenant_id', $tId))->avg('rata_rata_skor'), 2) : 0;
        
        $guruSangatBaik = $ratingSummary->where('kategori_raport', 'Sangat Baik')->count();
        $guruButuhBina = $ratingSummary->whereIn('kategori_raport', ['Kurang', 'Sangat Kurang'])->count();

        // 7. Selectors Data Pokok (Kelas, Mapel, Guru)
        $kelasSelector = DB::table('akademik.kelas')
            ->when($activeTenantId, fn($q, $tId) => $q->where('tenant_id', $tId))
            ->where('is_active', true)
            ->orderBy('nama_kelas', 'asc')
            ->get(['id', 'nama_kelas', 'kode_kelas']);

        if ($kelasSelector->isEmpty()) {
            $kelasSelector = collect([
                (object)['id' => Str::uuid()->toString(), 'nama_kelas' => 'X-1', 'kode_kelas' => 'X-1'],
                (object)['id' => Str::uuid()->toString(), 'nama_kelas' => 'X-2', 'kode_kelas' => 'X-2'],
                (object)['id' => Str::uuid()->toString(), 'nama_kelas' => 'XI-IPA 1', 'kode_kelas' => 'XI-IPA-1'],
                (object)['id' => Str::uuid()->toString(), 'nama_kelas' => 'XI-IPS 1', 'kode_kelas' => 'XI-IPS-1'],
                (object)['id' => Str::uuid()->toString(), 'nama_kelas' => 'XII-RPL 1', 'kode_kelas' => 'XII-RPL-1'],
            ]);
        }

        $mapelSelector = DB::table('akademik.mata_pelajaran')
            ->when($activeTenantId, fn($q, $tId) => $q->where('tenant_id', $tId))
            ->where('is_active', true)
            ->orderBy('nama_mata_pelajaran', 'asc')
            ->get(['id', 'nama_mata_pelajaran']);

        if ($mapelSelector->isEmpty()) {
            $mapelSelector = collect([
                (object)['id' => Str::uuid()->toString(), 'nama_mata_pelajaran' => 'Matematika'],
                (object)['id' => Str::uuid()->toString(), 'nama_mata_pelajaran' => 'Bahasa Indonesia'],
                (object)['id' => Str::uuid()->toString(), 'nama_mata_pelajaran' => 'Bahasa Inggris'],
                (object)['id' => Str::uuid()->toString(), 'nama_mata_pelajaran' => 'Fisika'],
                (object)['id' => Str::uuid()->toString(), 'nama_mata_pelajaran' => 'Kimia'],
                (object)['id' => Str::uuid()->toString(), 'nama_mata_pelajaran' => 'Biologi'],
                (object)['id' => Str::uuid()->toString(), 'nama_mata_pelajaran' => 'Pendidikan Pancasila'],
                (object)['id' => Str::uuid()->toString(), 'nama_mata_pelajaran' => 'Kejuruan / Produktif'],
            ]);
        }

        $guruSelector = User::when($activeTenantId, fn($q, $tId) => $q->where('tenant_id', $tId))
            ->where('is_active', true)
            ->whereHas('role', function ($r) {
                $r->whereIn('nama_role', ['guru', 'admin_sekolah', 'kepala_sekolah']);
            })
            ->orderBy('nama_lengkap', 'asc')
            ->get(['id', 'nama_lengkap', 'nip', 'nuptk', 'email']);

        if ($guruSelector->isEmpty()) {
            $guruSelector = User::when($activeTenantId, fn($q, $tId) => $q->where('tenant_id', $tId))
                ->where('is_active', true)
                ->orderBy('nama_lengkap', 'asc')
                ->limit(50)
                ->get(['id', 'nama_lengkap', 'nip', 'nuptk', 'email']);
        }

        $data = [
            'isSuperAdmin'      => $isSuperAdmin,
            'tenants'           => $tenants,
            'activeTenantId'    => $activeTenantId,
            'activeSurvei'      => $activeSurvei,
            'pertanyaanList'    => $pertanyaanList,
            'surveiList'        => $surveiList,
            'ratingSummary'     => $ratingSummary,
            'recentFeedback'    => $recentFeedback,
            'kpi'               => [
                'total_survei'      => $totalSurvei,
                'total_respon'      => $totalRespon,
                'rata_rata_sekolah' => $rataRataSekolah,
                'guru_sangat_baik'  => $guruSangatBaik,
                'guru_butuh_bina'   => $guruButuhBina,
            ],
            'kelasSelector'     => $kelasSelector,
            'mapelSelector'     => $mapelSelector,
            'guruSelector'      => $guruSelector,
            'filters'           => $request->only(['search', 'status', 'filter_guru', 'filter_kelas', 'tenant_id']),
        ];

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data'    => $data,
            ]);
        }

        return Inertia::render('KepalaSekolah/SurveiGuru/Index', $data);
    }

    /**
     * Submit Evaluasi Guru oleh Kepala Sekolah / Guru / Siswa (Backward Compatible Alias)
     */
    public function submitEvaluasi(Request $request): RedirectResponse|JsonResponse
    {
        return $this->submitEvaluasiSiswa($request);
    }

    /**
     * Submit Evaluasi Guru oleh Siswa (Anonim, Likert 5 Poin, Per Kelas & Mapel)
     */
    public function submitEvaluasiSiswa(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'survei_id'              => 'nullable|uuid',
            'guru_id'                => 'required|uuid',
            'nama_guru'              => 'nullable|string|max:255',
            'kelas_id'               => 'nullable|string',
            'nama_kelas'             => 'nullable|string|max:100',
            'mapel_id'               => 'nullable|string',
            'nama_mapel'             => 'nullable|string|max:150',
            'skor_items'             => 'nullable|array',
            'ratings'                => 'nullable|array',
            'umpan_balik_positif'    => 'nullable|string|max:1000',
            'area_pengembangan'      => 'nullable|string|max:1000',
        ]);

        $tenantId = session('tenant_id') ?? auth()->user()?->tenant_id;
        $user = auth()->user();

        // Auto-resolve nama_guru jika tidak dikirim
        if (empty($validated['nama_guru'])) {
            $guruUser = DB::table('core.users')->where('id', $validated['guru_id'])->first();
            $validated['nama_guru'] = $guruUser?->nama_lengkap ?? 'Guru Pengampu';
        }

        // Pastikan survei_id terisi dan masih dalam masa aktif
        if (empty($validated['survei_id'])) {
            $defaultSurvei = SurveiGuru::where('tenant_id', $tenantId)->where('status', 'Aktif')->first();
            $validated['survei_id'] = $defaultSurvei?->id;
        }

        // Normalisasi items skor
        $items = [];
        if (!empty($validated['ratings'])) {
            $items = array_values(array_map('intval', $validated['ratings']));
        } elseif (!empty($validated['skor_items'])) {
            $items = array_values(array_map('intval', $validated['skor_items']));
        } elseif ($request->filled('skor_pedagogik')) {
            $p = (int)$request->input('skor_pedagogik', 5);
            $d = (int)$request->input('skor_kedisiplinan', 5);
            $k = (int)$request->input('skor_komunikasi', 5);
            $m = (int)$request->input('skor_penguasaan_materi', 5);
            $items = [$m, $m, $p, $p, $p, $d, $d, $k, $k, $k];
        }

        if (empty($items)) {
            $items = [5, 5, 5, 5, 5, 5, 5, 5, 5, 5];
        }

        // Hitung Dimensi I (Pedagogik: Butir 1-5, skala 1-5)
        $skorPedagogik = round(array_sum(array_slice($items, 0, min(5, count($items)))) / min(5, count($items)), 2);
        // Hitung Dimensi II (Kepribadian & Sosial: Butir 6-10, skala 1-5)
        $skorSosialKepribadian = count($items) > 5 ? round(array_sum(array_slice($items, 5, 5)) / max(1, count($items) - 5), 2) : $skorPedagogik;
        // Hitung Rata-Rata Total Skala 1-5
        $rataRataTotal = round(array_sum($items) / count($items), 2);

        // Kategori Raport Evaluasi 5 Level
        if ($rataRataTotal >= 4.20) {
            $predikatKategori = 'Sangat Baik';
        } elseif ($rataRataTotal >= 3.40) {
            $predikatKategori = 'Baik';
        } elseif ($rataRataTotal >= 2.60) {
            $predikatKategori = 'Cukup';
        } elseif ($rataRataTotal >= 1.80) {
            $predikatKategori = 'Kurang';
        } else {
            $predikatKategori = 'Sangat Kurang';
        }

        $respon = SurveiGuruRespon::create([
            'id'                      => Str::uuid()->toString(),
            'tenant_id'               => $tenantId,
            'survei_id'               => $validated['survei_id'],
            'guru_id'                 => $validated['guru_id'],
            'nama_guru'               => $validated['nama_guru'],
            'kelas_id'                => $validated['kelas_id'] ?? null,
            'nama_kelas'              => $validated['nama_kelas'] ?? 'Semua Kelas',
            'mapel_id'                => $validated['mapel_id'] ?? null,
            'nama_mapel'              => $validated['nama_mapel'] ?? 'Mata Pelajaran',
            'is_anonim'               => true,
            'penilai_id'              => $user?->id, // Disimpan di backend untuk mencegah pengisian ganda
            'nama_penilai'            => 'Siswa (Anonim)',
            'peran_penilai'           => 'Siswa',
            'skor_detail_json'        => $items,
            'skor_pedagogik'          => $skorPedagogik,
            'skor_sosial_kepribadian' => $skorSosialKepribadian,
            'skor_kedisiplinan'       => round(($items[min(5, count($items)-1)] ?? 4), 2),
            'skor_komunikasi'         => round(($items[min(7, count($items)-1)] ?? 4), 2),
            'skor_penguasaan_materi'  => round(($items[0] ?? 4), 2),
            'rata_rata_skor'          => $rataRataTotal,
            'predikat_kategori'       => $predikatKategori,
            'umpan_balik_positif'     => $validated['umpan_balik_positif'] ?? 'Pengajaran sudah sangat baik.',
            'area_pengembangan'       => $validated['area_pengembangan'] ?? 'Pertahankan metode interaktif.',
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Terima kasih! Survei evaluasi guru berhasil dikirim secara anonim.',
                'data'    => $respon,
            ], 201);
        }

        return back()->with('success', 'Terima kasih! Survei evaluasi guru berhasil dikirim secara anonim.');
    }

    /**
     * Dapatkan Data Lembar Refleksi Individu Guru (Tampilan Cetak/Export Tertutup, Skala 1-5)
     */
    public function lembarRefleksi(Request $request, string $guruId): JsonResponse
    {
        $tenantId = session('tenant_id') ?? auth()->user()?->tenant_id;

        $respons = SurveiGuruRespon::when($tenantId, fn($q, $tId) => $q->where('tenant_id', $tId))
            ->where('guru_id', $guruId)
            ->get();

        if ($respons->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Belum ada data evaluasi untuk guru ini.',
            ], 404);
        }

        $namaGuru = $respons->first()->nama_guru;
        $totalResponden = $respons->count();
        $rataRataTotal = round($respons->avg('rata_rata_skor'), 2);
        $avgPedagogik = round($respons->avg('skor_pedagogik'), 2);
        $avgSosialKepribadian = round($respons->avg('skor_sosial_kepribadian'), 2);

        // Breakdown Rata-rata per 10 Butir Indikator
        $indikatorAverages = [];
        for ($i = 0; $i < 10; $i++) {
            $sum = 0;
            $count = 0;
            foreach ($respons as $r) {
                if (!empty($r->skor_detail_json) && isset($r->skor_detail_json[$i])) {
                    $sum += (float)$r->skor_detail_json[$i];
                    $count++;
                }
            }
            $indikatorAverages[$i + 1] = $count > 0 ? round($sum / $count, 2) : 0;
        }

        // Breakdown Skor per Kelas
        $kelasBreakdown = $respons->groupBy('nama_kelas')->map(function ($items, $kelas) {
            return [
                'nama_kelas'       => $kelas,
                'total_responden'  => $items->count(),
                'rata_rata_skor'   => round($items->avg('rata_rata_skor'), 2),
                'avg_pedagogik'    => round($items->avg('skor_pedagogik'), 2),
                'avg_kepribadian'  => round($items->avg('skor_sosial_kepribadian'), 2),
            ];
        })->values();

        // Kumpulan Umpan Balik Positif & Saran
        $umpanBalikPositif = $respons->pluck('umpan_balik_positif')->filter()->values();
        $saranPerbaikan = $respons->pluck('area_pengembangan')->filter()->values();

        // Kategori Raport
        if ($rataRataTotal >= 4.20) {
            $predikatKategori = 'Sangat Baik';
        } elseif ($rataRataTotal >= 3.40) {
            $predikatKategori = 'Baik';
        } elseif ($rataRataTotal >= 2.60) {
            $predikatKategori = 'Cukup';
        } elseif ($rataRataTotal >= 1.80) {
            $predikatKategori = 'Kurang';
        } else {
            $predikatKategori = 'Sangat Kurang';
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'guru_id'              => $guruId,
                'nama_guru'            => $namaGuru,
                'total_responden'      => $totalResponden,
                'rata_rata_total'      => $rataRataTotal,
                'avg_pedagogik'        => $avgPedagogik,
                'avg_sosial_kepribadian' => $avgSosialKepribadian,
                'indikator_averages'   => $indikatorAverages,
                'kelas_breakdown'      => $kelasBreakdown,
                'umpan_balik_positif'  => $umpanBalikPositif,
                'saran_perbaikan'      => $saranPerbaikan,
                'predikat_kategori'    => $predikatKategori,
            ]
        ]);
    }

    /**
     * Tambah Butir Pertanyaan Baru
     */
    public function storePertanyaan(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'survei_id'   => 'nullable|uuid',
            'dimensi'     => 'required|string|in:Pedagogik,Kepribadian & Sosial',
            'nomor_urut'  => 'required|integer|min:1',
            'pertanyaan'  => 'required|string|max:500',
            'tipe_skala'  => 'required|string|in:likert_5,likert_4,teks_terbuka',
        ]);

        $tenantId = session('tenant_id') ?? auth()->user()?->tenant_id;
        $validated['tenant_id'] = $tenantId;
        $validated['id'] = Str::uuid()->toString();

        $item = SurveiGuruPertanyaan::create($validated);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Butir pertanyaan berhasil ditambahkan.', 'data' => $item], 201);
        }

        return back()->with('success', 'Butir pertanyaan berhasil ditambahkan.');
    }

    /**
     * Hapus Butir Pertanyaan
     */
    public function deletePertanyaan(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $item = SurveiGuruPertanyaan::findOrFail($id);
        $item->delete();

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Butir pertanyaan berhasil dihapus.']);
        }

        return back()->with('success', 'Butir pertanyaan berhasil dihapus.');
    }

    /**
     * Buat Kuesioner Baru & Atur Jadwal
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'judul_survei'    => 'required|string|max:255',
            'deskripsi'       => 'nullable|string',
            'sasaran_survei'  => 'required|string|max:50',
            'tahun_ajaran'    => 'required|string|max:20',
            'semester'        => 'required|string|max:10',
            'tanggal_mulai'   => 'required|date',
            'tanggal_selesai' => 'nullable|date',
            'status'          => 'required|string|in:Aktif,Ditutup,Draft',
        ]);

        $validated['tenant_id'] = session('tenant_id') ?? auth()->user()?->tenant_id;
        $validated['id'] = Str::uuid()->toString();

        $survei = SurveiGuru::create($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Instrumen survei guru berhasil dibuat.',
                'data'    => $survei,
            ], 201);
        }

        return back()->with('success', 'Instrumen survei guru berhasil dibuat.');
    }

    public function update(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $survei = SurveiGuru::findOrFail($id);

        $validated = $request->validate([
            'judul_survei'    => 'required|string|max:255',
            'deskripsi'       => 'nullable|string',
            'sasaran_survei'  => 'required|string|max:50',
            'tahun_ajaran'    => 'required|string|max:20',
            'semester'        => 'required|string|max:10',
            'tanggal_mulai'   => 'required|date',
            'tanggal_selesai' => 'nullable|date',
            'status'          => 'required|string|in:Aktif,Ditutup,Draft',
        ]);

        $survei->update($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Jadwal & kuesioner survei guru berhasil diperbarui.',
                'data'    => $survei,
            ]);
        }

        return back()->with('success', 'Jadwal & kuesioner survei guru berhasil diperbarui.');
    }

    public function destroy(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $survei = SurveiGuru::findOrFail($id);
        $survei->respons()->delete();
        $survei->delete();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Kuesioner survei guru berhasil dihapus.',
            ]);
        }

        return back()->with('success', 'Kuesioner survei guru berhasil dihapus.');
    }

    /**
     * Endpoint API untuk mengecek status survei guru bagi siswa yang sedang login
     * Mengambil guru pengampu dari akademik.pemetaan_mapel berdasarkan kelas siswa
     */
    public function getStudentSurveyStatus(Request $request): JsonResponse
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['success' => false, 'is_mandatory_popup' => false]);
        }

        $tenantId = session('tenant_id') ?? $user->tenant_id;

        // 1. Temukan profil siswa
        $siswa = DB::table('siswa.siswa')
            ->where('tenant_id', $tenantId)
            ->where(function ($q) use ($user) {
                $q->where('id', $user->id)
                  ->orWhere('email', $user->email)
                  ->orWhere('nisn', $user->username)
                  ->orWhere('nis', $user->username);
            })
            ->first();

        // 2. Cek apakah ada survei aktif
        $activeSurvei = SurveiGuru::where('tenant_id', $tenantId)
            ->where('status', 'Aktif')
            ->where('tanggal_mulai', '<=', now()->toDateString())
            ->where(function ($q) {
                $q->whereNull('tanggal_selesai')
                  ->orWhere('tanggal_selesai', '>=', now()->toDateString());
            })
            ->first();

        if (!$activeSurvei) {
            return response()->json([
                'success'            => true,
                'is_mandatory_popup' => false,
                'message'            => 'Tidak ada survei evaluasi guru yang aktif.',
            ]);
        }

        // 3. Ambil daftar pertanyaan dinamis (Skala Likert 5 Poin)
        $pertanyaanList = SurveiGuruPertanyaan::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('nomor_urut', 'asc')
            ->get();

        // 4. Cari kelas siswa
        $kelasId = $siswa?->kelas_saat_ini;
        $kelas = null;
        if ($kelasId) {
            $kelas = DB::table('akademik.kelas')
                ->where('tenant_id', $tenantId)
                ->where(function ($q) use ($kelasId) {
                    $q->where('id', $kelasId)
                      ->orWhere('nama_kelas', $kelasId)
                      ->orWhere('kode_kelas', $kelasId);
                })
                ->first();
        }

        $resolvedKelasId = $kelas?->id ?? $kelasId;
        $namaKelas = $kelas?->nama_kelas ?? ($siswa?->kelas_saat_ini ?? 'Semua Kelas');

        // 5. Cari daftar guru pengampu dari akademik.pemetaan_mapel
        $pemetaanQuery = DB::table('akademik.pemetaan_mapel as pm')
            ->join('core.users as u', function ($join) {
                $join->on(DB::raw('u.id::text'), '=', DB::raw('pm.guru_id::text'));
            })
            ->leftJoin('akademik.mata_pelajaran as mp', function ($join) {
                $join->on(DB::raw('mp.id::text'), '=', DB::raw('pm.mapel_id::text'));
            })
            ->where('pm.tenant_id', $tenantId)
            ->where('pm.is_active', true);

        if ($resolvedKelasId) {
            $pemetaanQuery->where(function ($q) use ($resolvedKelasId, $namaKelas) {
                $q->where('pm.kelas_id', $resolvedKelasId)
                  ->orWhere('pm.kelas_id', $namaKelas);
            });
        }

        $pengampuList = $pemetaanQuery->select([
            'pm.id as pemetaan_id',
            'pm.guru_id',
            'u.nama_lengkap as nama_guru',
            DB::raw("NULL as foto_guru"),
            'pm.mapel_id',
            DB::raw("COALESCE(mp.nama_mata_pelajaran, pm.kelompok_id, 'Mata Pelajaran') as nama_mapel"),
        ])->get();

        // Fallback jika belum ada pemetaan_mapel, ambil guru aktif di tenant
        if ($pengampuList->isEmpty()) {
            $pengampuList = DB::table('core.users as u')
                ->leftJoin('core.roles as r', 'r.id', '=', 'u.role_id')
                ->where('u.tenant_id', $tenantId)
                ->where('u.is_active', true)
                ->where(function ($q) {
                    $q->where('r.nama_role', 'ILIKE', '%guru%')
                      ->orWhere('u.role_id', 'like', '%guru%');
                })
                ->select([
                    DB::raw("u.id as pemetaan_id"),
                    'u.id as guru_id',
                    'u.nama_lengkap as nama_guru',
                    DB::raw("NULL as foto_guru"),
                    DB::raw("NULL as mapel_id"),
                    DB::raw("'Guru Pengampu' as nama_mapel"),
                ])
                ->limit(6)
                ->get();
        }

        // 6. Cek riwayat respon yang sudah disubmit oleh siswa ini
        $submittedGuruIds = DB::table('kepegawaian.survei_guru_respon')
            ->where('survei_id', $activeSurvei->id)
            ->where('penilai_id', $user->id)
            ->pluck('guru_id')
            ->toArray();

        $guruFormatted = [];
        $totalBelum = 0;

        foreach ($pengampuList as $p) {
            $isSudah = in_array($p->guru_id, $submittedGuruIds);
            if (!$isSudah) {
                $totalBelum++;
            }
            $guruFormatted[] = [
                'pemetaan_id' => $p->pemetaan_id,
                'guru_id'     => $p->guru_id,
                'nama_guru'   => $p->nama_guru,
                'foto_guru'   => $p->foto_guru,
                'mapel_id'    => $p->mapel_id,
                'nama_mapel'  => $p->nama_mapel,
                'is_sudah'    => $isSudah,
            ];
        }

        return response()->json([
            'success'            => true,
            'is_mandatory_popup' => ($totalBelum > 0 && count($guruFormatted) > 0),
            'survei'             => $activeSurvei,
            'kelas'              => [
                'id'   => $resolvedKelasId,
                'nama' => $namaKelas,
            ],
            'pertanyaan'         => $pertanyaanList,
            'guru_list'          => $guruFormatted,
            'total_guru'         => count($guruFormatted),
            'total_selesai'      => count($guruFormatted) - $totalBelum,
            'total_belum'        => $totalBelum,
        ]);
    }
}

