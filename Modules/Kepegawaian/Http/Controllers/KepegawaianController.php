<?php

namespace Modules\Kepegawaian\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Modules\Kepegawaian\Entities\Gtk;

class KepegawaianController extends Controller
{
    public function index(Request $request): InertiaResponse|JsonResponse
    {
        $gtkList = Gtk::query()
            ->when($request->search, function ($q, $search) {
                $q->where('nama_lengkap', 'ILIKE', "%{$search}%")
                  ->orWhere('nip', 'ILIKE', "%{$search}%")
                  ->orWhere('nuptk', 'ILIKE', "%{$search}%");
            })
            ->when($request->jenis, function ($q, $jenis) {
                $q->where('jenis_ptk', $jenis);
            })
            ->orderBy('nama_lengkap', 'asc')
            ->paginate(15);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'data' => $gtkList]);
        }

        return Inertia::render('Kepegawaian/Index', [
            'gtkList' => $gtkList,
            'filters' => $request->only(['search', 'jenis']),
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'nip'                 => 'nullable|string|max:30',
            'nuptk'               => 'nullable|string|max:30',
            'nik'                 => 'nullable|string|max:20',
            'nama_lengkap'        => 'required|string|max:255',
            'jenis_kelamin'       => 'required|in:L,P',
            'jenis_ptk'           => 'required|string|max:50',
            'status_kepegawaian'  => 'required|string|max:50',
            'pendidikan_terakhir' => 'nullable|string|max:20',
            'no_hp'               => 'nullable|string|max:20',
            'email'               => 'nullable|email|max:100',
            'is_active'           => 'boolean',
        ]);

        $gtk = Gtk::create($validated);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Data GTK berhasil disimpan.', 'data' => $gtk], 201);
        }

        return back()->with('success', 'Data pendidik/tenaga kependidikan berhasil ditambahkan.');
    }
}
