<?php

namespace Modules\Tracer\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Modules\Tracer\Entities\TracerStudy;

class TracerController extends Controller
{
    public function index(Request $request): InertiaResponse|JsonResponse
    {
        $alumniList = TracerStudy::with('siswa')
            ->when($request->status, function ($q, $status) {
                $q->where('status_saat_ini', $status);
            })
            ->when($request->tahun, function ($q, $tahun) {
                $q->where('tahun_lulus', $tahun);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        // Rekapitulasi Statistik Vokasi
        $totalResponden = TracerStudy::count();
        $bekerjaCount = TracerStudy::where('status_saat_ini', 'Bekerja')->count();
        $wirausahaCount = TracerStudy::where('status_saat_ini', 'Wirausaha')->count();
        $kuliahCount = TracerStudy::where('status_saat_ini', 'Melanjutkan Kuliah')->count();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => compact('alumniList', 'totalResponden', 'bekerjaCount', 'wirausahaCount', 'kuliahCount'),
            ]);
        }

        return Inertia::render('Tracer/Index', [
            'alumniList'      => $alumniList,
            'totalResponden'  => $totalResponden,
            'bekerjaCount'    => $bekerjaCount,
            'wirausahaCount'  => $wirausahaCount,
            'kuliahCount'     => $kuliahCount,
            'filters'         => $request->only(['status', 'tahun']),
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'siswa_id'                => 'required|uuid|exists:siswa.siswa,id',
            'tahun_lulus'             => 'required|integer|min:2000|max:2099',
            'status_saat_ini'         => 'required|in:Bekerja,Wirausaha,Melanjutkan Kuliah,Mencari Kerja',
            'nama_instansi_usaha'     => 'nullable|string|max:255',
            'posisi_jabatan'          => 'nullable|string|max:100',
            'bidang_pekerjaan'        => 'nullable|string|max:100',
            'pendapatan_bulanan'      => 'nullable|numeric|min:0',
            'waktu_tunggu_bulan'      => 'nullable|integer|min:0',
            'keselarasan_jurusan'     => 'nullable|string|max:50',
            'saran_masukan_kurikulum' => 'nullable|string',
        ]);

        $tracer = TracerStudy::create($validated);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Survei tracer alumni berhasil disimpan.', 'data' => $tracer], 201);
        }

        return back()->with('success', 'Data penelusuran alumni berhasil disimpan.');
    }
}
