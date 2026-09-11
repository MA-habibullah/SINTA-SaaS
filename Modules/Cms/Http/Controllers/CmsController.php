<?php

namespace Modules\Cms\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Modules\Cms\Entities\Pengumuman;
use Modules\Cms\Entities\KategoriPengumuman;
use Modules\Cms\Entities\AgendaSekolah;
use Modules\Cms\Entities\KategoriAgenda;
use Modules\Core\Entities\Tenant;

class CmsController extends Controller
{
    /**
     * Helper to check if current user is Super Admin
     */
    private function checkIsSuperAdmin(): bool
    {
        $user = Auth::user();
        if (!$user) return false;
        
        $userRole = is_object($user->role) ? ($user->role->nama_role ?? '') : ($user->role ?? '');
        return ($user->isSuperAdmin() 
            || $userRole === 'super_admin' 
            || $user->tenant_id === '00000000-0000-0000-0000-000000000000' 
            || session('role') === 'super_admin' 
            || session('tenant_id') === '00000000-0000-0000-0000-000000000000');
    }

    // ─────────────────────────────────────────
    // 1. PENGUMUMAN SEKOLAH (/informasi/pengumuman)
    // ─────────────────────────────────────────
    public function pengumuman(Request $request): InertiaResponse|JsonResponse
    {
        $isSuperAdmin = $this->checkIsSuperAdmin();

        $perPage = (int) $request->input('per_page', 15);
        $search  = $request->input('search', '');
        $kategoriId = $request->input('kategori_id', '');
        $visibilitas = $request->input('visibilitas', '');
        $status = $request->input('status', '');
        $selectedTenantId = $request->input('tenant_id', '');

        $tenants = [];
        if ($isSuperAdmin) {
            $tenants = Tenant::select('id', 'nama_sekolah', 'npsn')
                ->where('id', '!=', '00000000-0000-0000-0000-000000000000')
                ->orderBy('nama_sekolah', 'asc')
                ->get();

            $query = Pengumuman::withoutTenant()->with(['tenant:id,nama_sekolah,npsn', 'kategori', 'penulis:id,nama_lengkap,username']);
            $kategoriQuery = KategoriPengumuman::withoutTenant();
            $statsQuery = Pengumuman::withoutTenant();

            if ($request->filled('tenant_id') && $selectedTenantId !== 'all' && $selectedTenantId !== '') {
                $query->where('sistem.pengumuman.tenant_id', $selectedTenantId);
                $kategoriQuery->where(function ($kq) use ($selectedTenantId) {
                    $kq->where('tenant_id', $selectedTenantId)->orWhereNull('tenant_id');
                });
                $statsQuery->where('tenant_id', $selectedTenantId);
            }
        } else {
            $query = Pengumuman::with(['kategori', 'penulis:id,nama_lengkap,username']);
            $kategoriQuery = KategoriPengumuman::query();
            $statsQuery = Pengumuman::query();
        }

        $query->orderBy('created_at', 'desc');

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
        $kategoriList = $kategoriQuery->orderBy('nama_kategori')->get(['id', 'nama_kategori', 'tenant_id']);

        // Ringkasan Statistik
        $stats = [
            'total'     => (clone $statsQuery)->count(),
            'aktif'     => (clone $statsQuery)->where('is_active', true)->count(),
            'publik'    => (clone $statsQuery)->where('visibilitas', 'public')->count(),
            'khusus'    => (clone $statsQuery)->where('visibilitas', '!=', 'public')->count(),
        ];

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data'    => compact('pengumumanList', 'kategoriList', 'stats', 'tenants', 'isSuperAdmin'),
            ]);
        }

        return Inertia::render('Cms/Pengumuman/Index', [
            'pengumumanList' => $pengumumanList,
            'kategoriList'   => $kategoriList,
            'stats'          => $stats,
            'tenants'        => $tenants,
            'isSuperAdmin'   => $isSuperAdmin,
            'filters'        => [
                'search'      => $search,
                'kategori_id' => $kategoriId,
                'visibilitas' => $visibilitas,
                'status'      => $status,
                'tenant_id'   => $selectedTenantId,
                'per_page'    => $perPage,
            ],
        ]);
    }

    // ─────────────────────────────────────────
    // 2. AGENDA & TIMELINE SEKOLAH (/informasi/agenda)
    // ─────────────────────────────────────────
    public function agenda(Request $request): InertiaResponse|JsonResponse
    {
        $isSuperAdmin = $this->checkIsSuperAdmin();

        $perPage = (int) $request->input('per_page', 12);
        $search  = $request->input('search', '');
        $kategori = $request->input('kategori', '');
        $status = $request->input('status', '');
        $selectedTenantId = $request->input('tenant_id', '');

        $tenants = [];
        if ($isSuperAdmin) {
            $tenants = Tenant::select('id', 'nama_sekolah', 'npsn')
                ->where('id', '!=', '00000000-0000-0000-0000-000000000000')
                ->orderBy('nama_sekolah', 'asc')
                ->get();

            $query = AgendaSekolah::withoutTenant()->with('tenant:id,nama_sekolah,npsn');
            $kategoriMasterQuery = KategoriAgenda::withoutTenant();
            $statsQuery = AgendaSekolah::withoutTenant();

            if ($request->filled('tenant_id') && $selectedTenantId !== 'all' && $selectedTenantId !== '') {
                $query->where('sistem.agenda_sekolah.tenant_id', $selectedTenantId);
                $kategoriMasterQuery->where(function ($kq) use ($selectedTenantId) {
                    $kq->where('tenant_id', $selectedTenantId)->orWhereNull('tenant_id');
                });
                $statsQuery->where('tenant_id', $selectedTenantId);
            }
        } else {
            $query = AgendaSekolah::query();
            $kategoriMasterQuery = KategoriAgenda::query();
            $statsQuery = AgendaSekolah::query();
        }

        $query->orderBy('created_at', 'desc');

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

        // Master Kategori Agenda
        $kategoriList = $kategoriMasterQuery->orderBy('nama_kategori')->get(['id', 'nama_kategori', 'tenant_id']);

        // Ringkasan Statistik
        $allAgendas = $statsQuery->get();
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
                'data'    => compact('agendaList', 'kategoriList', 'stats', 'tenants', 'isSuperAdmin'),
            ]);
        }

        return Inertia::render('Cms/Agenda/Index', [
            'agendaList'   => $agendaList,
            'kategoriList' => $kategoriList,
            'stats'        => $stats,
            'tenants'      => $tenants,
            'isSuperAdmin' => $isSuperAdmin,
            'filters'      => [
                'search'    => $search,
                'kategori'  => $kategori,
                'status'    => $status,
                'tenant_id' => $selectedTenantId,
                'per_page'  => $perPage,
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
        $user = Auth::user();
        $isSuperAdmin = $this->checkIsSuperAdmin();

        $validated = $request->validate([
            'judul'        => 'required|string|max:255',
            'deskripsi'    => 'required|string',
            'kategori_id'  => 'nullable|uuid',
            'visibilitas'  => 'required|in:public,guru,siswa,orang_tua',
            'target_roles' => 'nullable|array',
            'tenant_id'    => 'nullable|uuid',
            'is_active'    => 'boolean',
            'lampiran'     => 'nullable|file|mimes:jpeg,png,jpg,webp,svg,pdf,doc,docx,xls,xlsx,ppt,pptx,zip,rar|max:10240',
        ]);

        $tenantId = ($isSuperAdmin && !empty($validated['tenant_id']))
            ? $validated['tenant_id']
            : ($user?->tenant_id ?? session('tenant_id') ?? Tenant::where('id', '!=', '00000000-0000-0000-0000-000000000000')->value('id') ?? Tenant::first()?->id);

        $lampiranUrl = null;
        $lampiranNama = null;
        $lampiranUkuran = null;
        $lampiranTipe = null;

        if ($request->hasFile('lampiran')) {
            $file = $request->file('lampiran');
            $storedPath = $file->store('cms/pengumuman', 'public');
            $lampiranUrl = Storage::url($storedPath);
            $lampiranNama = $file->getClientOriginalName();
            $lampiranUkuran = $file->getSize();
            $lampiranTipe = $file->getClientMimeType();
        }

        $pengumuman = Pengumuman::create([
            'id'              => (string) Str::uuid(),
            'tenant_id'       => $tenantId,
            'judul'           => $validated['judul'],
            'deskripsi'       => $validated['deskripsi'],
            'kategori_id'     => $validated['kategori_id'] ?? null,
            'visibilitas'     => $validated['visibilitas'],
            'target_roles'    => !empty($validated['target_roles']) ? json_encode($validated['target_roles']) : null,
            'lampiran_url'    => $lampiranUrl,
            'lampiran_nama'   => $lampiranNama,
            'lampiran_ukuran' => $lampiranUkuran,
            'lampiran_tipe'   => $lampiranTipe,
            'is_active'       => $validated['is_active'] ?? true,
            'created_by'      => auth()->id(),
        ]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Pengumuman berhasil dipublikasikan.', 'data' => $pengumuman], 201);
        }

        return back()->with('success', 'Pengumuman berhasil dipublikasikan.');
    }

    public function updatePengumuman(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $user = Auth::user();
        $isSuperAdmin = $this->checkIsSuperAdmin();

        $pengumuman = ($isSuperAdmin ? Pengumuman::withoutTenant() : Pengumuman::query())->findOrFail($id);

        $validated = $request->validate([
            'judul'           => 'required|string|max:255',
            'deskripsi'       => 'required|string',
            'kategori_id'     => 'nullable|uuid',
            'visibilitas'     => 'required|in:public,guru,siswa,orang_tua',
            'target_roles'    => 'nullable|array',
            'tenant_id'       => 'nullable|uuid',
            'is_active'       => 'boolean',
            'lampiran'        => 'nullable|file|mimes:jpeg,png,jpg,webp,svg,pdf,doc,docx,xls,xlsx,ppt,pptx,zip,rar|max:10240',
            'delete_lampiran' => 'nullable|string',
        ]);

        $updateData = [
            'judul'        => $validated['judul'],
            'deskripsi'    => $validated['deskripsi'],
            'kategori_id'  => $validated['kategori_id'] ?? null,
            'visibilitas'  => $validated['visibilitas'],
            'target_roles' => !empty($validated['target_roles']) ? json_encode($validated['target_roles']) : null,
            'is_active'    => $validated['is_active'] ?? true,
        ];

        if ($isSuperAdmin && !empty($validated['tenant_id'])) {
            $updateData['tenant_id'] = $validated['tenant_id'];
        }

        // Handle File Upload or File Deletion
        if ($request->hasFile('lampiran')) {
            if ($pengumuman->lampiran_url) {
                $oldPath = str_replace('/storage/', '', $pengumuman->lampiran_url);
                if (Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }
            }
            $file = $request->file('lampiran');
            $storedPath = $file->store('cms/pengumuman', 'public');
            $updateData['lampiran_url'] = Storage::url($storedPath);
            $updateData['lampiran_nama'] = $file->getClientOriginalName();
            $updateData['lampiran_ukuran'] = $file->getSize();
            $updateData['lampiran_tipe'] = $file->getClientMimeType();
        } elseif ($request->input('delete_lampiran') === '1' || $request->input('delete_lampiran') === 'true') {
            if ($pengumuman->lampiran_url) {
                $oldPath = str_replace('/storage/', '', $pengumuman->lampiran_url);
                if (Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }
            }
            $updateData['lampiran_url'] = null;
            $updateData['lampiran_nama'] = null;
            $updateData['lampiran_ukuran'] = null;
            $updateData['lampiran_tipe'] = null;
        }

        $pengumuman->update($updateData);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Pengumuman berhasil diperbarui.', 'data' => $pengumuman->fresh()]);
        }

        return back()->with('success', 'Pengumuman berhasil diperbarui.');
    }

    public function destroyPengumuman(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $isSuperAdmin = $this->checkIsSuperAdmin();

        $pengumuman = ($isSuperAdmin ? Pengumuman::withoutTenant() : Pengumuman::query())->findOrFail($id);
        
        if ($pengumuman->lampiran_url) {
            $oldPath = str_replace('/storage/', '', $pengumuman->lampiran_url);
            if (Storage::disk('public')->exists($oldPath)) {
                Storage::disk('public')->delete($oldPath);
            }
        }

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
        $user = Auth::user();
        $isSuperAdmin = $this->checkIsSuperAdmin();

        $validated = $request->validate([
            'nama_kategori' => 'required|string|max:100',
            'tenant_id'     => 'nullable|uuid',
        ]);

        $tenantId = ($isSuperAdmin && !empty($validated['tenant_id']))
            ? $validated['tenant_id']
            : ($user?->tenant_id ?? session('tenant_id') ?? Tenant::where('id', '!=', '00000000-0000-0000-0000-000000000000')->value('id') ?? Tenant::first()?->id);

        $kategori = KategoriPengumuman::create([
            'id'            => (string) Str::uuid(),
            'tenant_id'     => $tenantId,
            'nama_kategori' => $validated['nama_kategori'],
        ]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Kategori berhasil ditambahkan.', 'data' => $kategori], 201);
        }

        return back()->with('success', 'Kategori berhasil ditambahkan.');
    }

    public function destroyKategoriPengumuman(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $isSuperAdmin = $this->checkIsSuperAdmin();

        $kategori = ($isSuperAdmin ? KategoriPengumuman::withoutTenant() : KategoriPengumuman::query())->findOrFail($id);
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
        $user = Auth::user();
        $isSuperAdmin = $this->checkIsSuperAdmin();

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
            'tenant_id'           => 'nullable|uuid',
            'is_active'           => 'boolean',
            'lampiran'            => 'nullable|file|mimes:jpeg,png,jpg,webp,svg,pdf,doc,docx,xls,xlsx,ppt,pptx,zip,rar|max:10240',
        ]);

        $lampiranUrl = null;
        $lampiranNama = null;
        $lampiranUkuran = null;
        $lampiranTipe = null;

        if ($request->hasFile('lampiran')) {
            $file = $request->file('lampiran');
            $storedPath = $file->store('cms/agenda', 'public');
            $lampiranUrl = Storage::url($storedPath);
            $lampiranNama = $file->getClientOriginalName();
            $lampiranUkuran = $file->getSize();
            $lampiranTipe = $file->getClientMimeType();
        }

        $deskripsiPayload = json_encode([
            'isi'              => $validated['isi'] ?? '',
            'tanggal_mulai'    => $validated['tanggal_mulai'],
            'tanggal_selesai'  => $validated['tanggal_selesai'] ?? $validated['tanggal_mulai'],
            'waktu_mulai'      => $validated['waktu_mulai'] ?? '',
            'waktu_selesai'    => $validated['waktu_selesai'] ?? '',
            'lokasi'           => $validated['lokasi'] ?? '',
            'penanggung_jawab' => $validated['penanggung_jawab'] ?? '',
            'visibilitas'      => $validated['visibilitas'] ?? 'public',
            'lampiran_url'     => $lampiranUrl,
            'lampiran_nama'    => $lampiranNama,
            'lampiran_ukuran'  => $lampiranUkuran,
            'lampiran_tipe'    => $lampiranTipe,
            'target_roles'     => [],
        ]);

        $tenantId = ($isSuperAdmin && !empty($validated['tenant_id']))
            ? $validated['tenant_id']
            : ($user?->tenant_id ?? session('tenant_id') ?? Tenant::where('id', '!=', '00000000-0000-0000-0000-000000000000')->value('id') ?? Tenant::first()?->id);

        $agenda = AgendaSekolah::create([
            'id'                  => (string) Str::uuid(),
            'tenant_id'           => $tenantId,
            'nama_agenda_sekolah' => $validated['nama_agenda_sekolah'],
            'kategori'            => $validated['kategori'],
            'deskripsi'           => $deskripsiPayload,
            'lampiran_url'        => $lampiranUrl,
            'lampiran_nama'       => $lampiranNama,
            'lampiran_ukuran'     => $lampiranUkuran,
            'lampiran_tipe'       => $lampiranTipe,
            'is_active'           => $validated['is_active'] ?? true,
        ]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Agenda kegiatan berhasil disimpan.', 'data' => $agenda], 201);
        }

        return back()->with('success', 'Agenda kegiatan berhasil disimpan.');
    }

    public function updateAgenda(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $user = Auth::user();
        $isSuperAdmin = $this->checkIsSuperAdmin();

        $agenda = ($isSuperAdmin ? AgendaSekolah::withoutTenant() : AgendaSekolah::query())->findOrFail($id);

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
            'tenant_id'           => 'nullable|uuid',
            'is_active'           => 'boolean',
            'lampiran'            => 'nullable|file|mimes:jpeg,png,jpg,webp,svg,pdf,doc,docx,xls,xlsx,ppt,pptx,zip,rar|max:10240',
            'delete_lampiran'     => 'nullable|string',
        ]);

        $lampiranUrl = $agenda->lampiran_url;
        $lampiranNama = $agenda->lampiran_nama;
        $lampiranUkuran = $agenda->lampiran_ukuran;
        $lampiranTipe = $agenda->lampiran_tipe;

        // Handle File Upload or File Deletion
        if ($request->hasFile('lampiran')) {
            if ($lampiranUrl) {
                $oldPath = str_replace('/storage/', '', $lampiranUrl);
                if (Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }
            }
            $file = $request->file('lampiran');
            $storedPath = $file->store('cms/agenda', 'public');
            $lampiranUrl = Storage::url($storedPath);
            $lampiranNama = $file->getClientOriginalName();
            $lampiranUkuran = $file->getSize();
            $lampiranTipe = $file->getClientMimeType();
        } elseif ($request->input('delete_lampiran') === '1' || $request->input('delete_lampiran') === 'true') {
            if ($lampiranUrl) {
                $oldPath = str_replace('/storage/', '', $lampiranUrl);
                if (Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }
            }
            $lampiranUrl = null;
            $lampiranNama = null;
            $lampiranUkuran = null;
            $lampiranTipe = null;
        }

        $deskripsiPayload = json_encode([
            'isi'              => $validated['isi'] ?? '',
            'tanggal_mulai'    => $validated['tanggal_mulai'],
            'tanggal_selesai'  => $validated['tanggal_selesai'] ?? $validated['tanggal_mulai'],
            'waktu_mulai'      => $validated['waktu_mulai'] ?? '',
            'waktu_selesai'    => $validated['waktu_selesai'] ?? '',
            'lokasi'           => $validated['lokasi'] ?? '',
            'penanggung_jawab' => $validated['penanggung_jawab'] ?? '',
            'visibilitas'      => $validated['visibilitas'] ?? 'public',
            'lampiran_url'     => $lampiranUrl,
            'lampiran_nama'    => $lampiranNama,
            'lampiran_ukuran'  => $lampiranUkuran,
            'lampiran_tipe'    => $lampiranTipe,
            'target_roles'     => [],
        ]);

        $updateData = [
            'nama_agenda_sekolah' => $validated['nama_agenda_sekolah'],
            'kategori'            => $validated['kategori'],
            'deskripsi'           => $deskripsiPayload,
            'lampiran_url'        => $lampiranUrl,
            'lampiran_nama'       => $lampiranNama,
            'lampiran_ukuran'     => $lampiranUkuran,
            'lampiran_tipe'       => $lampiranTipe,
            'is_active'           => $validated['is_active'] ?? true,
        ];

        if ($isSuperAdmin && !empty($validated['tenant_id'])) {
            $updateData['tenant_id'] = $validated['tenant_id'];
        }

        $agenda->update($updateData);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Agenda kegiatan berhasil diperbarui.', 'data' => $agenda->fresh()]);
        }

        return back()->with('success', 'Agenda kegiatan berhasil diperbarui.');
    }

    public function destroyAgenda(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $isSuperAdmin = $this->checkIsSuperAdmin();

        $agenda = ($isSuperAdmin ? AgendaSekolah::withoutTenant() : AgendaSekolah::query())->findOrFail($id);

        if ($agenda->lampiran_url) {
            $oldPath = str_replace('/storage/', '', $agenda->lampiran_url);
            if (Storage::disk('public')->exists($oldPath)) {
                Storage::disk('public')->delete($oldPath);
            }
        }

        $agenda->delete();

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Agenda kegiatan berhasil dihapus.']);
        }

        return back()->with('success', 'Agenda kegiatan berhasil dihapus.');
    }

    // ─────────────────────────────────────────
    // 7. CRUD KATEGORI AGENDA
    // ─────────────────────────────────────────
    public function storeKategoriAgenda(Request $request): RedirectResponse|JsonResponse
    {
        $user = Auth::user();
        $isSuperAdmin = $this->checkIsSuperAdmin();

        $validated = $request->validate([
            'nama_kategori' => 'required|string|max:100',
            'tenant_id'     => 'nullable|uuid',
        ]);

        $tenantId = ($isSuperAdmin && !empty($validated['tenant_id']))
            ? $validated['tenant_id']
            : ($user?->tenant_id ?? session('tenant_id') ?? Tenant::where('id', '!=', '00000000-0000-0000-0000-000000000000')->value('id') ?? Tenant::first()?->id);

        $kategori = KategoriAgenda::create([
            'id'            => (string) Str::uuid(),
            'tenant_id'     => $tenantId,
            'nama_kategori' => $validated['nama_kategori'],
        ]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Kategori agenda berhasil ditambahkan.', 'data' => $kategori], 201);
        }

        return back()->with('success', 'Kategori agenda berhasil ditambahkan.');
    }

    public function destroyKategoriAgenda(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $isSuperAdmin = $this->checkIsSuperAdmin();

        $kategori = ($isSuperAdmin ? KategoriAgenda::withoutTenant() : KategoriAgenda::query())->findOrFail($id);
        $kategori->delete();

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Kategori agenda berhasil dihapus.']);
        }

        return back()->with('success', 'Kategori agenda berhasil dihapus.');
    }
}
