<?php

namespace Modules\Siswa\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Modules\Siswa\Entities\SiswaMutasi;
use Modules\Siswa\Entities\Siswa;

class MutasiController extends Controller
{
    public function index(Request $request): InertiaResponse|JsonResponse
    {
        $query = SiswaMutasi::with('siswa')
            ->when($request->jenis, function ($q, $jenis) {
                $q->where('jenis_mutasi', $jenis);
            })
            ->orderBy('tanggal_mutasi', 'desc');

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'data' => $query->paginate(20)]);
        }

        return Inertia::render('Siswa/Mutasi/Index', [
            'mutasiList' => $query->paginate(20)->withQueryString(),
            'filters'    => $request->only(['jenis']),
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'siswa_id'            => 'required|uuid|exists:siswa.siswa,id',
            'jenis_mutasi'        => 'required|in:masuk,keluar',
            'tanggal_mutasi'      => 'required|date',
            'sekolah_asal_tujuan' => 'required|string|max:255',
            'alasan_mutasi'       => 'nullable|string',
            'nomor_surat_mutasi'  => 'nullable|string|max:100',
        ]);

        $mutasi = SiswaMutasi::create($validated);

        // Update status siswa jika mutasi keluar
        if ($validated['jenis_mutasi'] === 'keluar') {
            Siswa::where('id', $validated['siswa_id'])->update(['status_siswa' => 'Mutasi Keluar']);
        }

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Data mutasi berhasil disimpan.', 'data' => $mutasi], 201);
        }

        return back()->with('success', 'Pencatatan mutasi siswa berhasil disimpan.');
    }
}
