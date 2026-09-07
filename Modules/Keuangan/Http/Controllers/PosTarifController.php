<?php

namespace Modules\Keuangan\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Modules\Keuangan\Entities\PosKeuangan;
use Modules\Keuangan\Entities\TarifPembayaran;

class PosTarifController extends Controller
{
    public function index(): InertiaResponse|JsonResponse
    {
        $posList = PosKeuangan::where('is_active', true)->orderBy('created_at', 'desc')->get();

        if (request()->wantsJson()) {
            return response()->json(['success' => true, 'data' => $posList]);
        }

        return Inertia::render('Keuangan/PosTarif/Index', [
            'posList' => $posList,
        ]);
    }

    public function storePos(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'kode_pos'        => 'required|string|max:30',
            'nama_pos'        => 'required|string|max:150',
            'tipe_pembayaran' => 'required|in:Bulanan,Bebas',
            'keterangan'      => 'nullable|string',
            'is_active'       => 'boolean',
        ]);

        $pos = PosKeuangan::create($validated);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Pos keuangan berhasil ditambahkan.', 'data' => $pos], 201);
        }

        return back()->with('success', 'Pos keuangan berhasil disimpan.');
    }

    public function storeTarif(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'pos_keuangan_id' => 'required|uuid|exists:keuangan.pos_keuangan,id',
            'tingkat'         => 'required|string|max:10',
            'nominal'         => 'required|numeric|min:0',
            'keterangan'      => 'nullable|string',
        ]);

        $tarif = TarifPembayaran::create($validated);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Tarif pembayaran berhasil diset.', 'data' => $tarif], 201);
        }

        return back()->with('success', 'Tarif pembayaran berhasil disimpan.');
    }
}
