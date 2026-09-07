<?php

namespace Modules\Bk\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Modules\Bk\Entities\Pelanggaran;
use Modules\Bk\Entities\Konseling;
use Modules\Siswa\Entities\Siswa;

class BkController extends Controller
{
    public function index(Request $request): InertiaResponse|JsonResponse
    {
        $pelanggaranList = Pelanggaran::with('siswa')->orderBy('tanggal_kejadian', 'desc')->paginate(15);
        $konselingList = Konseling::with('siswa')->orderBy('tanggal_konseling', 'desc')->paginate(15);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => compact('pelanggaranList', 'konselingList'),
            ]);
        }

        return Inertia::render('Bk/Index', [
            'pelanggaranList' => $pelanggaranList,
            'konselingList'   => $konselingList,
        ]);
    }

    public function storePelanggaran(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'siswa_id'             => 'required|uuid|exists:siswa.siswa,id',
            'tanggal_kejadian'     => 'required|date',
            'kategori_pelanggaran' => 'required|in:Ringan,Sedang,Berat',
            'nama_pelanggaran'     => 'required|string|max:255',
            'poin_pelanggaran'     => 'required|integer|min:1',
            'tindakan_hukuman'     => 'nullable|string',
            'petugas_pencatat'     => 'nullable|string',
            'keterangan'           => 'nullable|string',
        ]);

        $pelanggaran = Pelanggaran::create($validated);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Pelanggaran berhasil dicatat.', 'data' => $pelanggaran], 201);
        }

        return back()->with('success', 'Pelanggaran siswa berhasil dicatat.');
    }

    public function storeKonseling(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'siswa_id'             => 'required|uuid|exists:siswa.siswa,id',
            'tanggal_konseling'    => 'required|date',
            'jenis_konseling'      => 'required|in:Pribadi,Sosial,Belajar,Karier',
            'topik_masalah'        => 'required|string|max:255',
            'ringkasan_konseling'  => 'nullable|string',
            'solusi_tindak_lanjut' => 'nullable|string',
            'status_kasus'         => 'required|in:Terbuka,Dalam Pendampingan,Selesai',
            'guru_bk_nama'         => 'nullable|string',
        ]);

        $konseling = Konseling::create($validated);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Sesi konseling berhasil dicatat.', 'data' => $konseling], 201);
        }

        return back()->with('success', 'Sesi konseling berhasil disimpan.');
    }
}
