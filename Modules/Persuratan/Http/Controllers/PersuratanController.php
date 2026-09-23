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
use Modules\Core\Services\SecurityPayloadService;
use Illuminate\Support\Facades\DB;

class PersuratanController extends Controller
{
    /**
     * Index Dashboard & Kendali Persuratan
     */
    public function index(Request $request): InertiaResponse|JsonResponse
    {
        return $this->renderView($request, 'semua');
    }

    /**
     * Halaman Agenda Surat Masuk
     */
    public function suratMasukIndex(Request $request): InertiaResponse|JsonResponse
    {
        return $this->renderView($request, 'surat_masuk');
    }

    /**
     * Halaman Arsip Surat Keluar
     */
    public function suratKeluarIndex(Request $request): InertiaResponse|JsonResponse
    {
        return $this->renderView($request, 'surat_keluar');
    }

    /**
     * Helper Render Data Persuratan
     */
    private function renderView(Request $request, string $defaultTab): InertiaResponse|JsonResponse
    {
        $tenantId = session('tenant_id') ?? auth()->user()?->tenant_id;
        $activeTab = $request->input('tab', $defaultTab);
        $search = $request->input('search', '');
        $statusDisposisi = $request->input('status_disposisi', '');
        $statusSuratKeluar = $request->input('status_surat', '');

        // Query Surat Masuk
        $suratMasukQuery = SuratMasuk::with('disposisi')
            ->when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))
            ->when($search, function($q) use ($search) {
                $q->where(function($sq) use ($search) {
                    $sq->where('perihal', 'ILIKE', "%{$search}%")
                       ->orWhere('pengirim', 'ILIKE', "%{$search}%")
                       ->orWhere('nomor_surat_asal', 'ILIKE', "%{$search}%")
                       ->orWhere('nomor_agenda', 'ILIKE', "%{$search}%");
                });
            })
            ->when($statusDisposisi, fn($q) => $q->where('status_disposisi', $statusDisposisi))
            ->orderBy('created_at', 'desc');

        $suratMasukList = $suratMasukQuery->paginate(15);

        // Query Surat Keluar
        $suratKeluarQuery = SuratKeluar::when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))
            ->when($search, function($q) use ($search) {
                $q->where(function($sq) use ($search) {
                    $sq->where('perihal', 'ILIKE', "%{$search}%")
                       ->orWhere('tujuan_surat', 'ILIKE', "%{$search}%")
                       ->orWhere('nomor_surat', 'ILIKE', "%{$search}%");
                });
            })
            ->when($statusSuratKeluar, fn($q) => $q->where('status_surat', $statusSuratKeluar))
            ->orderBy('created_at', 'desc');

        $suratKeluarList = $suratKeluarQuery->paginate(15);

        // KPI Counts
        $totalSuratMasuk = SuratMasuk::when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))->count();
        $totalSuratKeluar = SuratKeluar::when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))->count();
        $totalMenungguDisposisi = SuratMasuk::when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))
            ->whereIn('status_disposisi', ['Belum Disposisi', 'Menunggu Disposisi', 'Pending'])
            ->count();
        $totalDisposisiSelesai = Disposisi::when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))
            ->where('status_tindak_lanjut', 'Selesai')
            ->count();

        $stats = [
            'total_surat_masuk' => $totalSuratMasuk,
            'total_surat_keluar' => $totalSuratKeluar,
            'menunggu_disposisi' => $totalMenungguDisposisi,
            'disposisi_selesai' => $totalDisposisiSelesai,
        ];

        $payload = [
            'suratMasukList' => $suratMasukList,
            'suratKeluarList' => $suratKeluarList,
            'stats' => $stats,
            'allowed_tabs' => \Modules\Core\Services\MenuService::getAllowedTabsForRoute(auth()->user(), '/persuratan'),
            'filters' => [
                'tab' => $activeTab,
                'search' => $search,
                'status_disposisi' => $statusDisposisi,
                'status_surat' => $statusSuratKeluar,
            ],
        ];

        $sanitized = SecurityPayloadService::sanitize($payload);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $sanitized,
            ]);
        }

        return Inertia::render('Persuratan/Index', $sanitized);
    }

    /**
     * Simpan Surat Masuk
     */
    public function storeSuratMasuk(Request $request): RedirectResponse|JsonResponse
    {
        $tenantId = session('tenant_id') ?? auth()->user()?->tenant_id;

        $validated = $request->validate([
            'nomor_agenda'      => 'required|string|max:100',
            'nomor_surat_asal'  => 'required|string|max:150',
            'tanggal_surat'     => 'required|date',
            'tanggal_diterima'  => 'required|date',
            'pengirim'          => 'required|string|max:255',
            'perihal'           => 'required|string|max:500',
            'kategori_surat'    => 'nullable|string|max:100',
            'sifat_surat'       => 'required|in:Biasa,Penting,Rahasia,Segera',
            'file_surat_url'    => 'nullable|string',
            'ringkasan_isi'     => 'nullable|string',
        ]);

        $validated['tenant_id'] = $tenantId;
        $validated['status_disposisi'] = 'Belum Disposisi';
        $validated['kategori_surat'] = $validated['kategori_surat'] ?? 'Dinas';

        $surat = SuratMasuk::create($validated);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Surat masuk berhasil dicatat.', 'data' => $surat], 201);
        }

        return back()->with('success', 'Surat masuk berhasil dicatat ke dalam buku agenda.');
    }

    /**
     * Update Surat Masuk
     */
    public function updateSuratMasuk(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $tenantId = session('tenant_id') ?? auth()->user()?->tenant_id;
        $surat = SuratMasuk::when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))->findOrFail($id);

        $validated = $request->validate([
            'nomor_agenda'      => 'required|string|max:100',
            'nomor_surat_asal'  => 'required|string|max:150',
            'tanggal_surat'     => 'required|date',
            'tanggal_diterima'  => 'required|date',
            'pengirim'          => 'required|string|max:255',
            'perihal'           => 'required|string|max:500',
            'kategori_surat'    => 'nullable|string|max:100',
            'sifat_surat'       => 'required|in:Biasa,Penting,Rahasia,Segera',
            'status_disposisi'  => 'nullable|string|max:50',
            'file_surat_url'    => 'nullable|string',
            'ringkasan_isi'     => 'nullable|string',
        ]);

        $surat->update($validated);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Surat masuk berhasil diperbarui.', 'data' => $surat]);
        }

        return back()->with('success', 'Data surat masuk berhasil diperbarui.');
    }

    /**
     * Hapus Surat Masuk
     */
    public function destroySuratMasuk(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $tenantId = session('tenant_id') ?? auth()->user()?->tenant_id;
        $surat = SuratMasuk::when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))->findOrFail($id);
        $surat->delete();

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Surat masuk berhasil dihapus.']);
        }

        return back()->with('success', 'Surat masuk berhasil dihapus dari agenda.');
    }

    /**
     * Simpan Surat Keluar
     */
    public function storeSuratKeluar(Request $request): RedirectResponse|JsonResponse
    {
        $tenantId = session('tenant_id') ?? auth()->user()?->tenant_id;

        $validated = $request->validate([
            'nomor_surat'           => 'required|string|max:150',
            'klasifikasi_kode'      => 'nullable|string|max:100',
            'tujuan_surat'          => 'required|string|max:255',
            'tanggal_surat'         => 'required|date',
            'perihal'               => 'required|string|max:500',
            'ringkasan_isi'         => 'nullable|string',
            'penandatangan_nama'    => 'nullable|string|max:255',
            'penandatangan_jabatan' => 'nullable|string|max:255',
            'status_surat'          => 'required|in:Draft,Menunggu Persetujuan,Disetujui,Diterbitkan,Terkirim',
            'file_surat_url'        => 'nullable|string',
        ]);

        $validated['tenant_id'] = $tenantId;
        $surat = SuratKeluar::create($validated);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Surat keluar berhasil diterbitkan.', 'data' => $surat], 201);
        }

        return back()->with('success', 'Surat keluar berhasil disimpan ke buku arsip.');
    }

    /**
     * Update Surat Keluar
     */
    public function updateSuratKeluar(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $tenantId = session('tenant_id') ?? auth()->user()?->tenant_id;
        $surat = SuratKeluar::when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))->findOrFail($id);

        $validated = $request->validate([
            'nomor_surat'           => 'required|string|max:150',
            'klasifikasi_kode'      => 'nullable|string|max:100',
            'tujuan_surat'          => 'required|string|max:255',
            'tanggal_surat'         => 'required|date',
            'perihal'               => 'required|string|max:500',
            'ringkasan_isi'         => 'nullable|string',
            'penandatangan_nama'    => 'nullable|string|max:255',
            'penandatangan_jabatan' => 'nullable|string|max:255',
            'status_surat'          => 'required|in:Draft,Menunggu Persetujuan,Disetujui,Diterbitkan,Terkirim',
            'file_surat_url'        => 'nullable|string',
        ]);

        $surat->update($validated);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Surat keluar berhasil diperbarui.', 'data' => $surat]);
        }

        return back()->with('success', 'Data surat keluar berhasil diperbarui.');
    }

    /**
     * Hapus Surat Keluar
     */
    public function destroySuratKeluar(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $tenantId = session('tenant_id') ?? auth()->user()?->tenant_id;
        $surat = SuratKeluar::when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))->findOrFail($id);
        $surat->delete();

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Surat keluar berhasil dihapus.']);
        }

        return back()->with('success', 'Surat keluar berhasil dihapus dari arsip.');
    }

    /**
     * Terbitkan Disposisi Surat
     */
    public function storeDisposisi(Request $request): RedirectResponse|JsonResponse
    {
        $tenantId = session('tenant_id') ?? auth()->user()?->tenant_id;

        $validated = $request->validate([
            'surat_masuk_id'            => 'required|uuid|exists:persuratan.surat_masuk,id',
            'dari_jabatan'              => 'required|string|max:150',
            'diteruskan_kepada'         => 'required|string|max:150',
            'instruksi_disposisi'       => 'required|string|max:500',
            'catatan_tambahan'          => 'nullable|string',
            'batas_waktu_tindak_lanjut' => 'nullable|date',
        ]);

        $validated['tenant_id'] = $tenantId;
        $validated['status_tindak_lanjut'] = 'Menunggu';

        $disposisi = Disposisi::create($validated);
        SuratMasuk::where('id', $validated['surat_masuk_id'])->update(['status_disposisi' => 'Sudah Disposisi']);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Lembar disposisi berhasil diterbitkan.', 'data' => $disposisi], 201);
        }

        return back()->with('success', 'Lembar disposisi pimpinan berhasil diterbitkan.');
    }
}
