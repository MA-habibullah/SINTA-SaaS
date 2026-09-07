<?php

namespace Modules\Akademik\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Modules\Akademik\Entities\NilaiRapor;
use Modules\Akademik\Entities\Kelas;
use Modules\Akademik\Entities\MataPelajaran;
use Modules\Siswa\Entities\Siswa;

class PenilaianController extends Controller
{
    /**
     * Lembar Input Nilai Berbasis Kelas & Mata Pelajaran
     */
    public function index(Request $request): InertiaResponse|JsonResponse
    {
        $kelasId = $request->input('kelas_id');
        $mapelId = $request->input('mapel_id');
        $semester = $request->input('semester', 'Ganjil');

        $siswaList = [];
        if (!empty($kelasId)) {
            $siswaList = Siswa::where('kelas_saat_ini', $kelasId)
                ->where('status_siswa', 'Aktif')
                ->orderBy('nama_lengkap', 'asc')
                ->get(['id', 'nisn', 'nis', 'nama_lengkap']);
        }

        $existingNilai = [];
        if (!empty($kelasId) && !empty($mapelId)) {
            $existingNilai = NilaiRapor::where('kelas_id', $kelasId)
                ->where('mata_pelajaran_id', $mapelId)
                ->where('semester', $semester)
                ->get()
                ->keyBy('siswa_id');
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'siswa' => $siswaList,
                    'nilai' => $existingNilai,
                ]
            ]);
        }

        return Inertia::render('Akademik/Penilaian/Index', [
            'kelasList'     => Kelas::where('is_active', true)->get(['id', 'nama_kelas']),
            'mapelList'     => MataPelajaran::where('is_active', true)->get(['id', 'nama_mapel']),
            'siswaList'     => $siswaList,
            'existingNilai' => $existingNilai,
            'filters'       => compact('kelasId', 'mapelId', 'semester'),
        ]);
    }

    /**
     * Batch simpan nilai rapor (Format Kurikulum Merdeka / K13)
     */
    public function batchStore(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'kelas_id'          => 'required|uuid|exists:akademik.kelas,id',
            'mata_pelajaran_id' => 'required|uuid|exists:akademik.mata_pelajaran,id',
            'semester'          => 'required|string|in:Ganjil,Genap',
            'nilai_data'        => 'required|array',
            'nilai_data.*.siswa_id'                      => 'required|uuid|exists:siswa.siswa,id',
            'nilai_data.*.nilai_formatif'                => 'nullable|numeric|min:0|max:100',
            'nilai_data.*.nilai_sumatif_materi'          => 'nullable|numeric|min:0|max:100',
            'nilai_data.*.nilai_sumatif_akhir_semester'  => 'nullable|numeric|min:0|max:100',
            'nilai_data.*.nilai_akhir'                   => 'required|numeric|min:0|max:100',
            'nilai_data.*.capaian_kompetensi_tinggi'     => 'nullable|string',
            'nilai_data.*.capaian_kompetensi_rendah'     => 'nullable|string',
        ]);

        foreach ($validated['nilai_data'] as $item) {
            NilaiRapor::updateOrCreate(
                [
                    'tenant_id'         => session('tenant_id'),
                    'siswa_id'          => $item['siswa_id'],
                    'kelas_id'          => $validated['kelas_id'],
                    'mata_pelajaran_id' => $validated['mata_pelajaran_id'],
                    'semester'          => $validated['semester'],
                ],
                [
                    'nilai_formatif'               => $item['nilai_formatif'] ?? null,
                    'nilai_sumatif_materi'         => $item['nilai_sumatif_materi'] ?? null,
                    'nilai_sumatif_akhir_semester' => $item['nilai_sumatif_akhir_semester'] ?? null,
                    'nilai_akhir'                  => $item['nilai_akhir'],
                    'capaian_kompetensi_tinggi'     => $item['capaian_kompetensi_tinggi'] ?? null,
                    'capaian_kompetensi_rendah'     => $item['capaian_kompetensi_rendah'] ?? null,
                ]
            );
        }

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Nilai rapor berhasil disimpan.']);
        }

        return back()->with('success', 'Nilai siswa berhasil disimpan.');
    }
}
