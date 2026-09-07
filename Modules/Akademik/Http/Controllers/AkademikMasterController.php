<?php

namespace Modules\Akademik\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Modules\Akademik\Entities\TahunAjaran;
use Modules\Akademik\Entities\Jurusan;
use Modules\Akademik\Entities\Kelas;
use Modules\Akademik\Entities\MataPelajaran;

class AkademikMasterController extends Controller
{
    /**
     * Dashboard Master Data Akademik
     */
    public function index(): InertiaResponse|JsonResponse
    {
        $tahunAjaran = TahunAjaran::orderBy('created_at', 'desc')->get();
        $jurusan = Jurusan::where('is_active', true)->orderBy('nama_jurusan', 'asc')->get();
        $kelas = Kelas::with(['jurusan', 'tahunAjaran'])->where('is_active', true)->orderBy('nama_kelas', 'asc')->get();
        $mapel = MataPelajaran::where('is_active', true)->orderBy('nama_mapel', 'asc')->get();

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => compact('tahunAjaran', 'jurusan', 'kelas', 'mapel'),
            ]);
        }

        return Inertia::render('Akademik/Master/Index', [
            'tahunAjaranList' => $tahunAjaran,
            'jurusanList'     => $jurusan,
            'kelasList'       => $kelas,
            'mapelList'       => $mapel,
        ]);
    }

    /**
     * Simpan Kelas Baru
     */
    public function storeKelas(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'tingkat'         => 'required|string|max:10',
            'nama_kelas'      => 'required|string|max:100',
            'kode_kelas'      => 'nullable|string|max:50',
            'jurusan_id'      => 'nullable|uuid|exists:akademik.jurusan,id',
            'tahun_ajaran_id' => 'nullable|uuid|exists:akademik.tahun_ajaran,id',
            'kapasitas'       => 'nullable|integer|min:1|max:60',
            'is_active'       => 'boolean',
        ]);

        $kelas = Kelas::create($validated);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Kelas berhasil ditambahkan.', 'data' => $kelas], 201);
        }

        return back()->with('success', 'Kelas baru berhasil ditambahkan.');
    }

    /**
     * Simpan Mata Pelajaran Baru
     */
    public function storeMapel(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'kode_mapel'  => 'required|string|max:20',
            'nama_mapel'  => 'required|string|max:255',
            'kelompok'    => 'required|string|max:100',
            'kkm_default' => 'nullable|numeric|min:0|max:100',
            'is_active'   => 'boolean',
        ]);

        $mapel = MataPelajaran::create($validated);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Mata pelajaran berhasil ditambahkan.', 'data' => $mapel], 201);
        }

        return back()->with('success', 'Mata pelajaran berhasil didaftarkan.');
    }
}
