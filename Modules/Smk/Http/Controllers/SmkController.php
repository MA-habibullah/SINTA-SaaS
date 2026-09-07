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

class SmkController extends Controller
{
    public function index(): InertiaResponse|JsonResponse
    {
        $mitraList = MitraDudi::withCount('pkl')->where('is_active', true)->orderBy('nama_perusahaan', 'asc')->paginate(15);
        $pklList = PrakerinPkl::with(['siswa', 'mitra'])->orderBy('created_at', 'desc')->paginate(15);

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => compact('mitraList', 'pklList'),
            ]);
        }

        return Inertia::render('Smk/Index', [
            'mitraList' => $mitraList,
            'pklList'   => $pklList,
        ]);
    }

    public function storeMitra(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'nama_perusahaan'      => 'required|string|max:255',
            'bidang_usaha'         => 'required|string|max:100',
            'alamat_perusahaan'    => 'nullable|string',
            'kota'                 => 'nullable|string|max:100',
            'contact_person_nama'  => 'nullable|string|max:100',
            'contact_person_hp'    => 'nullable|string|max:20',
            'nomor_mou_kerjasama'  => 'nullable|string|max:100',
            'kuota_penerimaan_pkl' => 'nullable|integer|min:0',
            'is_active'            => 'boolean',
        ]);

        $mitra = MitraDudi::create($validated);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Mitra DUDI berhasil ditambahkan.', 'data' => $mitra], 201);
        }

        return back()->with('success', 'Mitra industri DUDI berhasil didaftarkan.');
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

        $pkl = PrakerinPkl::create($validated);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Penempatan PKL berhasil dicatat.', 'data' => $pkl], 201);
        }

        return back()->with('success', 'Penempatan PKL siswa berhasil dicatat.');
    }
}
