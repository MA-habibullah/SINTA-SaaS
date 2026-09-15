<?php

namespace Modules\Smk\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Modules\Smk\Entities\MitraDudi;
use Modules\Smk\Entities\PrakerinPkl;
use Modules\Smk\Entities\PklJurnalHarian;
use Modules\Smk\Entities\UkkPenilaian;
use Modules\Siswa\Entities\Siswa;
use Modules\Core\Services\SecurityPayloadService;

class SmkController extends Controller
{
    public function index(Request $request): InertiaResponse|JsonResponse
    {
        $tab = $request->input('tab', 'mitra');

        if ($request->wantsJson() || $request->ajax() || $request->has('async')) {
            $data = match ($tab) {
                'pkl' => $this->getPklData($request),
                'jurnal' => $this->getJurnalData($request),
                'ukk' => $this->getUkkData($request),
                default => $this->getMitraData($request),
            };

            return response()->json([
                'success' => true,
                'tab'     => $tab,
                'data'    => SecurityPayloadService::sanitize($data),
            ]);
        }

        return Inertia::render('Smk/Index', [
            'initialTab' => $tab,
        ]);
    }

    private function getMitraData(Request $request)
    {
        $items = MitraDudi::withCount('pkl')
            ->when($request->search, function ($q, $search) {
                $q->where('nama_perusahaan', 'ILIKE', "%{$search}%")
                  ->orWhere('bidang_usaha', 'ILIKE', "%{$search}%")
                  ->orWhere('kota', 'ILIKE', "%{$search}%")
                  ->orWhere('contact_person_nama', 'ILIKE', "%{$search}%");
            })
            ->when($request->status_kerjasama, fn($q, $st) => $q->where('status_kerjasama', $st))
            ->orderBy('nama_perusahaan', 'asc')
            ->paginate(15);

        $stats = [
            'total_mitra' => MitraDudi::count(),
            'mitra_aktif' => MitraDudi::where('status_kerjasama', 'Aktif')->count(),
            'total_kuota' => (int) MitraDudi::where('status_kerjasama', 'Aktif')->sum('kuota_penerimaan_pkl'),
            'total_terisi' => PrakerinPkl::where('status_pkl', 'Sedang Berjalan')->count(),
        ];

        return compact('items', 'stats');
    }

    private function getPklData(Request $request)
    {
        $items = PrakerinPkl::with(['siswa', 'mitra'])
            ->when($request->search, function ($q, $search) {
                $q->where('nama_siswa', 'ILIKE', "%{$search}%")
                  ->orWhere('nisn', 'ILIKE', "%{$search}%")
                  ->orWhere('nama_perusahaan', 'ILIKE', "%{$search}%")
                  ->orWhere('nama_kelas', 'ILIKE', "%{$search}%");
            })
            ->when($request->status_pkl, fn($q, $st) => $q->where('status_pkl', $st))
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        $siswaList = Siswa::select('id', 'nama_lengkap', 'nisn', 'kelas_saat_ini')
            ->where('status_siswa', 'Aktif')
            ->orderBy('nama_lengkap', 'asc')
            ->limit(100)
            ->get();

        $mitraList = MitraDudi::select('id', 'nama_perusahaan', 'bidang_usaha', 'kota')
            ->where('status_kerjasama', 'Aktif')
            ->orderBy('nama_perusahaan', 'asc')
            ->get();

        return compact('items', 'siswaList', 'mitraList');
    }

    private function getJurnalData(Request $request)
    {
        $items = PklJurnalHarian::with(['penempatan.mitra', 'siswa'])
            ->when($request->status_verifikasi, fn($q, $st) => $q->where('status_verifikasi', $st))
            ->when($request->search, function ($q, $search) {
                $q->whereHas('siswa', function ($sub) use ($search) {
                    $sub->where('nama_lengkap', 'ILIKE', "%{$search}%");
                })->orWhere('kegiatan_pekerjaan', 'ILIKE', "%{$search}%");
            })
            ->orderBy('tanggal', 'desc')
            ->paginate(15);

        $penempatanList = PrakerinPkl::select('id', 'nama_siswa', 'nama_perusahaan', 'siswa_id')
            ->where('status_pkl', 'Sedang Berjalan')
            ->orderBy('nama_siswa', 'asc')
            ->get();

        return compact('items', 'penempatanList');
    }

    private function getUkkData(Request $request)
    {
        $items = UkkPenilaian::with('siswa')
            ->when($request->search, function ($q, $search) {
                $q->where('nama_siswa', 'ILIKE', "%{$search}%")
                  ->orWhere('nisn', 'ILIKE', "%{$search}%")
                  ->orWhere('jurusan', 'ILIKE', "%{$search}%")
                  ->orWhere('nama_paket_soal', 'ILIKE', "%{$search}%");
            })
            ->when($request->predikat, fn($q, $p) => $q->where('predikat', $p))
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        $siswaList = Siswa::select('id', 'nama_lengkap', 'nisn', 'kelas_saat_ini')
            ->where('status_siswa', 'Aktif')
            ->orderBy('nama_lengkap', 'asc')
            ->limit(100)
            ->get();

        return compact('items', 'siswaList');
    }

    public function storeMitra(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'nama_perusahaan'        => 'required|string|max:255',
            'bidang_usaha'           => 'required|string|max:100',
            'alamat_perusahaan'      => 'nullable|string',
            'kota'                   => 'nullable|string|max:100',
            'contact_person_nama'    => 'nullable|string|max:100',
            'contact_person_jabatan' => 'nullable|string|max:100',
            'contact_person_hp'      => 'nullable|string|max:30',
            'email_perusahaan'       => 'nullable|email|max:100',
            'nomor_mou_kerjasama'    => 'nullable|string|max:100',
            'tanggal_mulai_mou'      => 'nullable|date',
            'tanggal_akhir_mou'      => 'nullable|date',
            'kuota_penerimaan_pkl'   => 'nullable|integer|min:0',
            'status_kerjasama'       => 'required|in:Aktif,Kadaluarsa,Nonaktif',
            'is_active'              => 'boolean',
        ]);

        $mitra = MitraDudi::create($validated);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Mitra DUDI berhasil ditambahkan.', 'data' => $mitra], 201);
        }

        return back()->with('success', 'Mitra industri DUDI berhasil didaftarkan.');
    }

    public function updateMitra(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $mitra = MitraDudi::findOrFail($id);

        $validated = $request->validate([
            'nama_perusahaan'        => 'required|string|max:255',
            'bidang_usaha'           => 'required|string|max:100',
            'alamat_perusahaan'      => 'nullable|string',
            'kota'                   => 'nullable|string|max:100',
            'contact_person_nama'    => 'nullable|string|max:100',
            'contact_person_jabatan' => 'nullable|string|max:100',
            'contact_person_hp'      => 'nullable|string|max:30',
            'email_perusahaan'       => 'nullable|email|max:100',
            'nomor_mou_kerjasama'    => 'nullable|string|max:100',
            'tanggal_mulai_mou'      => 'nullable|date',
            'tanggal_akhir_mou'      => 'nullable|date',
            'kuota_penerimaan_pkl'   => 'nullable|integer|min:0',
            'status_kerjasama'       => 'required|in:Aktif,Kadaluarsa,Nonaktif',
            'is_active'              => 'boolean',
        ]);

        $mitra->update($validated);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Data Mitra DUDI berhasil diperbarui.', 'data' => $mitra]);
        }

        return back()->with('success', 'Data Mitra DUDI berhasil diperbarui.');
    }

    public function destroyMitra(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $mitra = MitraDudi::findOrFail($id);
        $mitra->delete();

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Mitra DUDI berhasil dihapus.']);
        }

        return back()->with('success', 'Mitra DUDI berhasil dihapus.');
    }

    public function storePkl(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'siswa_id'                => 'required|uuid|exists:siswa.siswa,id',
            'mitra_dudi_id'           => 'required|uuid|exists:smk.mitra_dudi,id',
            'pembimbing_sekolah_nama' => 'nullable|string|max:100',
            'pembimbing_dudi_nama'    => 'nullable|string|max:100',
            'tanggal_mulai'           => 'required|date',
            'tanggal_selesai'         => 'required|date',
            'status_pkl'              => 'required|in:Sedang Berjalan,Selesai,Ditarik',
        ]);

        $siswa = Siswa::find($validated['siswa_id']);
        $mitra = MitraDudi::find($validated['mitra_dudi_id']);

        $validated['nama_siswa'] = $siswa?->nama_lengkap ?? '';
        $validated['nisn'] = $siswa?->nisn ?? '';
        $validated['nama_kelas'] = $siswa?->kelas_saat_ini ?? '';
        $validated['nama_perusahaan'] = $mitra?->nama_perusahaan ?? '';

        $pkl = PrakerinPkl::create($validated);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Penempatan PKL berhasil dicatat.', 'data' => $pkl], 201);
        }

        return back()->with('success', 'Penempatan PKL siswa berhasil dicatat.');
    }

    public function updatePklNilai(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $pkl = PrakerinPkl::findOrFail($id);

        $validated = $request->validate([
            'nilai_kinerja_dudi'    => 'required|numeric|min:0|max:100',
            'nilai_laporan_sekolah' => 'required|numeric|min:0|max:100',
            'status_pkl'            => 'required|in:Sedang Berjalan,Selesai,Ditarik',
            'catatan_evaluasi'      => 'nullable|string',
        ]);

        $nilaiAkhir = ($validated['nilai_kinerja_dudi'] * 0.6) + ($validated['nilai_laporan_sekolah'] * 0.4);
        $validated['nilai_akhir_pkl'] = round($nilaiAkhir, 2);

        $pkl->update($validated);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Nilai PKL berhasil diperbarui.', 'data' => $pkl]);
        }

        return back()->with('success', 'Nilai PKL berhasil diperbarui.');
    }

    public function storeJurnal(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'penempatan_id'        => 'required|uuid|exists:smk.pkl_penempatan,id',
            'tanggal'              => 'required|date',
            'jam_masuk'            => 'nullable|string|max:10',
            'jam_pulang'           => 'nullable|string|max:10',
            'kegiatan_pekerjaan'   => 'required|string',
            'alat_bahan_digunakan' => 'nullable|string',
        ]);

        $penempatan = PrakerinPkl::find($validated['penempatan_id']);
        $validated['siswa_id'] = $penempatan?->siswa_id;

        $jurnal = PklJurnalHarian::create($validated);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Jurnal harian PKL berhasil dicatat.', 'data' => $jurnal], 201);
        }

        return back()->with('success', 'Jurnal harian PKL berhasil dicatat.');
    }

    public function verifikasiJurnal(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $jurnal = PklJurnalHarian::findOrFail($id);

        $validated = $request->validate([
            'status_verifikasi'  => 'required|in:Pending,Disetujui,Revisi',
            'catatan_pembimbing' => 'nullable|string',
        ]);

        $jurnal->update($validated);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Status verifikasi jurnal diperbarui.', 'data' => $jurnal]);
        }

        return back()->with('success', 'Status verifikasi jurnal diperbarui.');
    }

    public function storeUkk(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'siswa_id'               => 'required|uuid|exists:siswa.siswa,id',
            'jurusan'                => 'required|string|max:100',
            'tahun_ajaran'           => 'required|string|max:20',
            'nama_paket_soal'        => 'required|string|max:200',
            'penguji_internal'       => 'nullable|string|max:150',
            'penguji_eksternal_dudi' => 'nullable|string|max:150',
            'skor_perencanaan'       => 'required|numeric|min:0|max:100',
            'skor_proses_kerja'      => 'required|numeric|min:0|max:100',
            'skor_hasil_produk'      => 'required|numeric|min:0|max:100',
            'skor_sikap_k3'          => 'required|numeric|min:0|max:100',
            'nomor_sertifikat'       => 'nullable|string|max:100',
        ]);

        $siswa = Siswa::find($validated['siswa_id']);
        $validated['nama_siswa'] = $siswa?->nama_lengkap ?? '';
        $validated['nisn'] = $siswa?->nisn ?? '';

        // Bobot UKK standar: Perencanaan 15%, Proses 35%, Hasil 35%, Sikap 15%
        $skorTotal = ($validated['skor_perencanaan'] * 0.15)
                   + ($validated['skor_proses_kerja'] * 0.35)
                   + ($validated['skor_hasil_produk'] * 0.35)
                   + ($validated['skor_sikap_k3'] * 0.15);

        $validated['skor_total'] = round($skorTotal, 2);

        if ($skorTotal >= 90) {
            $validated['predikat'] = 'Sangat Kompeten';
        } elseif ($skorTotal >= 75) {
            $validated['predikat'] = 'Kompeten';
        } elseif ($skorTotal >= 60) {
            $validated['predikat'] = 'Cukup Kompeten';
        } else {
            $validated['predikat'] = 'Belum Kompeten';
        }

        $ukk = UkkPenilaian::create($validated);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Penilaian UKK berhasil disimpan.', 'data' => $ukk], 201);
        }

        return back()->with('success', 'Penilaian UKK berhasil disimpan.');
    }
}
