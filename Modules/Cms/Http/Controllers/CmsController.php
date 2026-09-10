<?php

namespace Modules\Cms\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Modules\Cms\Entities\Pengumuman;
use Modules\Cms\Entities\KategoriPengumuman;
use Modules\Cms\Entities\AgendaSekolah;

class CmsController extends Controller
{
    // ─────────────────────────────────────────
    // 1. PENGUMUMAN SEKOLAH (/informasi/pengumuman)
    // ─────────────────────────────────────────
    public function pengumuman(Request $request): InertiaResponse|JsonResponse
    {
        $perPage = (int) $request->input('per_page', 15);
        $search  = $request->input('search', '');
        $kategoriId = $request->input('kategori_id', '');
        $visibilitas = $request->input('visibilitas', '');
        $status = $request->input('status', '');

        $query = Pengumuman::with(['kategori', 'penulis'])
            ->orderBy('created_at', 'desc');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('judul', 'ILIKE', "%{$search}%")
                  ->orWhere('deskripsi', 'ILIKE', "%{$search}%");
            });
        }
        if ($kategoriId) {
            $query->where('kategori_id', $kategoriId);
        }
        if ($visibilitas) {
            $query->where('visibilitas', $visibilitas);
        }
        if ($status !== '' && $status !== null) {
            $query->where('is_active', $status === '1' || $status === 'true');
        }

        $pengumumanList = $query->paginate($perPage)->withQueryString();

        // Master Kategori Pengumuman
        $kategoriList = KategoriPengumuman::orderBy('nama_kategori')->get(['id', 'nama_kategori']);

        // Ringkasan Statistik
        $stats = [
            'total'     => Pengumuman::count(),
            'aktif'     => Pengumuman::where('is_active', true)->count(),
            'publik'    => Pengumuman::where('visibilitas', 'public')->count(),
            'khusus'    => Pengumuman::where('visibilitas', '!=', 'public')->count(),
        ];

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data'    => compact('pengumumanList', 'kategoriList', 'stats'),
            ]);
        }

        return Inertia::render('Cms/Pengumuman/Index', [
            'pengumumanList' => $pengumumanList,
            'kategoriList'   => $kategoriList,
            'stats'          => $stats,
            'filters'        => [
                'search'      => $search,
                'kategori_id' => $kategoriId,
                'visibilitas' => $visibilitas,
                'status'      => $status,
                'per_page'    => $perPage,
            ],
        ]);
    }

    // ─────────────────────────────────────────
    // 2. AGENDA & TIMELINE SEKOLAH (/informasi/agenda)
    // ─────────────────────────────────────────
    public function agenda(Request $request): InertiaResponse|JsonResponse
    {
        $perPage = (int) $request->input('per_page', 12);
        $search  = $request->input('search', '');
        $kategori = $request->input('kategori', '');
        $status = $request->input('status', '');

        $query = AgendaSekolah::orderBy('created_at', 'desc');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nama_agenda_sekolah', 'ILIKE', "%{$search}%")
                  ->orWhere('kategori', 'ILIKE', "%{$search}%")
                  ->orWhere('deskripsi', 'ILIKE', "%{$search}%");
            });
        }
        if ($kategori) {
            $query->where('kategori', $kategori);
        }
        if ($status !== '' && $status !== null) {
            $query->where('is_active', $status === '1' || $status === 'true');
        }

        $agendaList = $query->paginate($perPage)->withQueryString();

        // Kategori Agenda (distinct)
        $kategoriList = AgendaSekolah::select('kategori')
            ->groupBy('kategori')
            ->orderBy('kategori')
            ->pluck('kategori')
            ->filter()
            ->values();

        // Ringkasan Statistik
        $allAgendas = AgendaSekolah::all();
        $totalCount = $allAgendas->count();
        $activeCount = $allAgendas->where('is_active', true)->count();
        
        $today = date('Y-m-d');
        $upcomingCount = 0;
        $ongoingCount = 0;
        $pastCount = 0;

        foreach ($allAgendas as $item) {
            $tglMulai = $item->tanggal_mulai;
            $tglSelesai = $item->tanggal_selesai ?: $item->tanggal_mulai;

            if ($tglMulai && $tglSelesai) {
                if ($today < $tglMulai) {
                    $upcomingCount++;
                } elseif ($today >= $tglMulai && $today <= $tglSelesai) {
                    $ongoingCount++;
                } else {
                    $pastCount++;
                }
            } else {
                $upcomingCount++;
            }
        }

        $stats = [
            'total'     => $totalCount,
            'aktif'     => $activeCount,
            'mendatang' => $upcomingCount,
            'hari_ini'  => $ongoingCount,
            'selesai'   => $pastCount,
        ];

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data'    => compact('agendaList', 'kategoriList', 'stats'),
            ]);
        }

        return Inertia::render('Cms/Agenda/Index', [
            'agendaList'   => $agendaList,
            'kategoriList' => $kategoriList,
            'stats'        => $stats,
            'filters'      => [
                'search'   => $search,
                'kategori' => $kategori,
                'status'   => $status,
                'per_page' => $perPage,
            ],
        ]);
    }

    // ─────────────────────────────────────────
    // 3. BACKWARD COMPATIBILITY INDEX (/cms)
    // ─────────────────────────────────────────
    public function index(Request $request): InertiaResponse|JsonResponse|RedirectResponse
    {
        return $this->pengumuman($request);
    }

    // ─────────────────────────────────────────
    // 4. CRUD PENGUMUMAN
    // ─────────────────────────────────────────
    public function storePengumuman(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'judul'        => 'required|string|max:255',
            'deskripsi'    => 'required|string',
            'kategori_id'  => 'nullable|uuid',
            'visibilitas'  => 'required|in:public,guru,siswa,orang_tua',
            'target_roles' => 'nullable|array',
            'is_active'    => 'boolean',
        ]);

        $pengumuman = Pengumuman::create([
            'id'           => (string) Str::uuid(),
            'tenant_id'    => auth()->user()?->tenant_id,
            'judul'        => $validated['judul'],
            'deskripsi'    => $validated['deskripsi'],
            'kategori_id'  => $validated['kategori_id'] ?? null,
            'visibilitas'  => $validated['visibilitas'],
            'target_roles' => !empty($validated['target_roles']) ? json_encode($validated['target_roles']) : null,
            'is_active'    => $validated['is_active'] ?? true,
            'created_by'   => auth()->id(),
        ]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Pengumuman berhasil dipublikasikan.', 'data' => $pengumuman], 201);
        }

        return back()->with('success', 'Pengumuman berhasil dipublikasikan.');
    }

    public function updatePengumuman(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $pengumuman = Pengumuman::findOrFail($id);

        $validated = $request->validate([
            'judul'        => 'required|string|max:255',
            'deskripsi'    => 'required|string',
            'kategori_id'  => 'nullable|uuid',
            'visibilitas'  => 'required|in:public,guru,siswa,orang_tua',
            'target_roles' => 'nullable|array',
            'is_active'    => 'boolean',
        ]);

        $pengumuman->update([
            'judul'        => $validated['judul'],
            'deskripsi'    => $validated['deskripsi'],
            'kategori_id'  => $validated['kategori_id'] ?? null,
            'visibilitas'  => $validated['visibilitas'],
            'target_roles' => !empty($validated['target_roles']) ? json_encode($validated['target_roles']) : null,
            'is_active'    => $validated['is_active'] ?? true,
        ]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Pengumuman berhasil diperbarui.', 'data' => $pengumuman]);
        }

        return back()->with('success', 'Pengumuman berhasil diperbarui.');
    }

    public function destroyPengumuman(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $pengumuman = Pengumuman::findOrFail($id);
        $pengumuman->delete();

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Pengumuman berhasil dihapus.']);
        }

        return back()->with('success', 'Pengumuman berhasil dihapus.');
    }

    // ─────────────────────────────────────────
    // 5. CRUD KATEGORI PENGUMUMAN
    // ─────────────────────────────────────────
    public function storeKategoriPengumuman(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'nama_kategori' => 'required|string|max:100',
        ]);

        $kategori = KategoriPengumuman::create([
            'id'            => (string) Str::uuid(),
            'tenant_id'     => auth()->user()?->tenant_id,
            'nama_kategori' => $validated['nama_kategori'],
        ]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Kategori berhasil ditambahkan.', 'data' => $kategori], 201);
        }

        return back()->with('success', 'Kategori berhasil ditambahkan.');
    }

    public function destroyKategoriPengumuman(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $kategori = KategoriPengumuman::findOrFail($id);
        $kategori->delete();

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Kategori berhasil dihapus.']);
        }

        return back()->with('success', 'Kategori berhasil dihapus.');
    }

    // ─────────────────────────────────────────
    // 6. CRUD AGENDA SEKOLAH
    // ─────────────────────────────────────────
    public function storeAgenda(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'nama_agenda_sekolah' => 'required|string|max:255',
            'kategori'            => 'required|string|max:100',
            'isi'                 => 'nullable|string',
            'tanggal_mulai'       => 'required|date',
            'tanggal_selesai'     => 'nullable|date|after_or_equal:tanggal_mulai',
            'waktu_mulai'         => 'nullable|string',
            'waktu_selesai'       => 'nullable|string',
            'lokasi'              => 'nullable|string|max:255',
            'penanggung_jawab'    => 'nullable|string|max:255',
            'visibilitas'         => 'nullable|string',
            'is_active'           => 'boolean',
        ]);

        $deskripsiPayload = json_encode([
            'isi'              => $validated['isi'] ?? '',
            'tanggal_mulai'    => $validated['tanggal_mulai'],
            'tanggal_selesai'  => $validated['tanggal_selesai'] ?? $validated['tanggal_mulai'],
            'waktu_mulai'      => $validated['waktu_mulai'] ?? '',
            'waktu_selesai'    => $validated['waktu_selesai'] ?? '',
            'lokasi'           => $validated['lokasi'] ?? '',
            'penanggung_jawab' => $validated['penanggung_jawab'] ?? '',
            'visibilitas'      => $validated['visibilitas'] ?? 'public',
            'target_roles'     => [],
        ]);

        $agenda = AgendaSekolah::create([
            'id'                  => (string) Str::uuid(),
            'tenant_id'           => auth()->user()?->tenant_id,
            'nama_agenda_sekolah' => $validated['nama_agenda_sekolah'],
            'kategori'            => $validated['kategori'],
            'deskripsi'           => $deskripsiPayload,
            'is_active'           => $validated['is_active'] ?? true,
        ]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Agenda kegiatan berhasil disimpan.', 'data' => $agenda], 201);
        }

        return back()->with('success', 'Agenda kegiatan berhasil disimpan.');
    }

    public function updateAgenda(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $agenda = AgendaSekolah::findOrFail($id);

        $validated = $request->validate([
            'nama_agenda_sekolah' => 'required|string|max:255',
            'kategori'            => 'required|string|max:100',
            'isi'                 => 'nullable|string',
            'tanggal_mulai'       => 'required|date',
            'tanggal_selesai'     => 'nullable|date|after_or_equal:tanggal_mulai',
            'waktu_mulai'         => 'nullable|string',
            'waktu_selesai'       => 'nullable|string',
            'lokasi'              => 'nullable|string|max:255',
            'penanggung_jawab'    => 'nullable|string|max:255',
            'visibilitas'         => 'nullable|string',
            'is_active'           => 'boolean',
        ]);

        $deskripsiPayload = json_encode([
            'isi'              => $validated['isi'] ?? '',
            'tanggal_mulai'    => $validated['tanggal_mulai'],
            'tanggal_selesai'  => $validated['tanggal_selesai'] ?? $validated['tanggal_mulai'],
            'waktu_mulai'      => $validated['waktu_mulai'] ?? '',
            'waktu_selesai'    => $validated['waktu_selesai'] ?? '',
            'lokasi'           => $validated['lokasi'] ?? '',
            'penanggung_jawab' => $validated['penanggung_jawab'] ?? '',
            'visibilitas'      => $validated['visibilitas'] ?? 'public',
            'target_roles'     => [],
        ]);

        $agenda->update([
            'nama_agenda_sekolah' => $validated['nama_agenda_sekolah'],
            'kategori'            => $validated['kategori'],
            'deskripsi'           => $deskripsiPayload,
            'is_active'           => $validated['is_active'] ?? true,
        ]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Agenda kegiatan berhasil diperbarui.', 'data' => $agenda]);
        }

        return back()->with('success', 'Agenda kegiatan berhasil diperbarui.');
    }

    public function destroyAgenda(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $agenda = AgendaSekolah::findOrFail($id);
        $agenda->delete();

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Agenda kegiatan berhasil dihapus.']);
        }

        return back()->with('success', 'Agenda kegiatan berhasil dihapus.');
    }
}
