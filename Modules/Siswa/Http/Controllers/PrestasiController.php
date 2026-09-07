<?php

namespace Modules\Siswa\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Modules\Siswa\Entities\SiswaPrestasi;

class PrestasiController extends Controller
{
    public function index(Request $request): InertiaResponse|JsonResponse
    {
        $query = SiswaPrestasi::with('siswa')
            ->when($request->jenis, function ($q, $jenis) {
                $q->where('jenis_prestasi', $jenis);
            })
            ->when($request->tingkat, function ($q, $tingkat) {
                $q->where('tingkat', $tingkat);
            })
            ->orderBy('created_at', 'desc');

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'data' => $query->paginate(20)]);
        }

        return Inertia::render('Siswa/Prestasi/Index', [
            'prestasiList' => $query->paginate(20)->withQueryString(),
            'filters'      => $request->only(['jenis', 'tingkat']),
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'siswa_id'            => 'required|uuid|exists:siswa.siswa,id',
            'jenis_prestasi'      => 'required|in:akademik,non_akademik',
            'tingkat'             => 'required|string|max:50',
            'nama_kegiatan_lomba' => 'required|string|max:255',
            'peringkat_juara'     => 'required|string|max:50',
            'tahun'               => 'required|integer|min:2000|max:2099',
            'penyelenggara'       => 'nullable|string|max:255',
            'keterangan'          => 'nullable|string',
        ]);

        $prestasi = SiswaPrestasi::create($validated);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Prestasi berhasil dicatat.', 'data' => $prestasi], 201);
        }

        return back()->with('success', 'Prestasi siswa berhasil ditambahkan.');
    }
}
