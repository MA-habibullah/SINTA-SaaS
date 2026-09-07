<?php

namespace Modules\Persuratan\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Modules\Persuratan\Entities\SuratMasuk;
use Modules\Persuratan\Entities\SuratKeluar;
use Modules\Persuratan\Entities\Disposisi;

class PersuratanController extends Controller
{
    public function index(): InertiaResponse|JsonResponse
    {
        $suratMasukList = SuratMasuk::with('disposisi')->orderBy('tanggal_diterima', 'desc')->paginate(15);
        $suratKeluarList = SuratKeluar::orderBy('tanggal_surat', 'desc')->paginate(15);

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => compact('suratMasukList', 'suratKeluarList'),
            ]);
        }

        return Inertia::render('Persuratan/Index', [
            'suratMasukList'  => $suratMasukList,
            'suratKeluarList' => $suratKeluarList,
        ]);
    }

    public function storeSuratMasuk(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'nomor_agenda'      => 'required|string|max:50',
            'nomor_surat_asal'  => 'required|string|max:100',
            'tanggal_surat'     => 'required|date',
            'tanggal_diterima'  => 'required|date',
            'pengirim'          => 'required|string|max:255',
            'perihal'           => 'required|string|max:255',
            'kategori_surat'    => 'required|string|max:50',
            'sifat_surat'       => 'required|in:Biasa,Penting,Rahasia,Segera',
            'file_surat_url'    => 'nullable|string',
        ]);

        $surat = SuratMasuk::create($validated);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Surat masuk berhasil dicatat.', 'data' => $surat], 201);
        }

        return back()->with('success', 'Surat masuk berhasil disimpan ke buku agenda.');
    }

    public function storeDisposisi(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'surat_masuk_id'            => 'required|uuid|exists:persuratan.surat_masuk,id',
            'dari_jabatan'              => 'required|string|max:100',
            'diteruskan_kepada'         => 'required|string|max:100',
            'instruksi_disposisi'       => 'required|string|max:255',
            'catatan_tambahan'          => 'nullable|string',
            'batas_waktu_tindak_lanjut' => 'nullable|date',
        ]);

        $disposisi = Disposisi::create($validated);
        SuratMasuk::where('id', $validated['surat_masuk_id'])->update(['status_disposisi' => 'Sudah Disposisi']);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Lembar disposisi berhasil diterbitkan.', 'data' => $disposisi], 201);
        }

        return back()->with('success', 'Lembar disposisi berhasil diterbitkan.');
    }
}
