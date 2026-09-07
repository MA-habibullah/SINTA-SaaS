<?php

namespace Modules\Cms\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Modules\Cms\Entities\Pengumuman;
use Modules\Cms\Entities\Berita;

class CmsController extends Controller
{
    public function index(): InertiaResponse|JsonResponse
    {
        $pengumumanList = Pengumuman::orderBy('tanggal_publikasi', 'desc')->paginate(15);
        $beritaList = Berita::orderBy('created_at', 'desc')->paginate(15);

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => compact('pengumumanList', 'beritaList'),
            ]);
        }

        return Inertia::render('Cms/Index', [
            'pengumumanList' => $pengumumanList,
            'beritaList'     => $beritaList,
        ]);
    }

    public function storePengumuman(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'judul'             => 'required|string|max:255',
            'kategori'          => 'required|string|max:50',
            'konten'            => 'required|string',
            'target_audiens'    => 'required|string|max:50',
            'tanggal_publikasi' => 'required|date',
            'file_lampiran_url' => 'nullable|string',
            'is_published'      => 'boolean',
        ]);

        $validated['slug'] = Str::slug($validated['judul']) . '-' . rand(100, 999);
        $pengumuman = Pengumuman::create($validated);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Pengumuman berhasil dipublikasikan.', 'data' => $pengumuman], 201);
        }

        return back()->with('success', 'Pengumuman berhasil disimpan.');
    }

    public function storeBerita(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'judul_berita'     => 'required|string|max:255',
            'ringkasan'        => 'nullable|string',
            'konten_lengkap'   => 'required|string',
            'gambar_utama_url' => 'nullable|string',
            'kategori'         => 'required|string|max:50',
            'is_published'     => 'boolean',
        ]);

        $validated['slug'] = Str::slug($validated['judul_berita']) . '-' . rand(100, 999);
        $validated['penulis_nama'] = auth()->user()?->nama_lengkap ?? 'Admin Sekolah';
        $validated['published_at'] = now();

        $berita = Berita::create($validated);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Berita berhasil dirilis.', 'data' => $berita], 201);
        }

        return back()->with('success', 'Artikel berita berhasil dirilis.');
    }
}
