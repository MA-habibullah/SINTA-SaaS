<?php

namespace Modules\Sarpras\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Modules\Sarpras\Entities\InventarisAset;

class SarprasController extends Controller
{
    public function index(Request $request): InertiaResponse|JsonResponse
    {
        $asetList = InventarisAset::query()
            ->when($request->search, function ($q, $search) {
                $q->where('nama_barang', 'ILIKE', "%{$search}%")
                  ->orWhere('kode_aset', 'ILIKE', "%{$search}%")
                  ->orWhere('lokasi_ruangan', 'ILIKE', "%{$search}%");
            })
            ->when($request->kategori, function ($q, $kategori) {
                $q->where('kategori', $kategori);
            })
            ->when($request->kondisi, function ($q, $kondisi) {
                $q->where('kondisi', $kondisi);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'data' => $asetList]);
        }

        return Inertia::render('Sarpras/Index', [
            'asetList' => $asetList,
            'filters'  => $request->only(['search', 'kategori', 'kondisi']),
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'kode_aset'         => 'required|string|max:50',
            'nama_barang'       => 'required|string|max:255',
            'kategori'          => 'required|string|max:50',
            'lokasi_ruangan'    => 'required|string|max:100',
            'jumlah'            => 'required|integer|min:1',
            'satuan'            => 'required|string|max:20',
            'kondisi'           => 'required|in:Baik,Rusak Ringan,Rusak Berat',
            'sumber_dana'       => 'nullable|string|max:50',
            'tanggal_pengadaan' => 'nullable|date',
            'harga_perolehan'   => 'nullable|numeric|min:0',
            'keterangan'        => 'nullable|string',
        ]);

        $validated['qr_code_token'] = Str::upper(Str::random(12));
        $aset = InventarisAset::create($validated);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Barang inventaris berhasil dicatat.', 'data' => $aset], 201);
        }

        return back()->with('success', 'Barang inventaris berhasil didaftarkan.');
    }
}
