<?php

namespace Modules\Sarpras\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Sarpras\Entities\InventarisAset;
use Modules\Sarpras\Entities\BarangHabisPakai;
use Modules\Sarpras\Entities\Bangunan;
use Modules\Sarpras\Entities\Ruangan;
use Modules\Sarpras\Entities\PeminjamanSarpras;
use Modules\Sarpras\Entities\RiwayatPemeliharaan;
use Modules\Sarpras\Entities\KategoriBarang;
use App\Services\SecurityPayloadService;

class SarprasController extends Controller
{
    /**
     * Halaman Utama Sarana & Prasarana — 5 Tab Bespoke
     * Zero-SSR Pattern: initial GET render shell, data via ?async=1&tab=...
     */
    public function index(Request $request): InertiaResponse|JsonResponse
    {
        $user = Auth::user();

        // === ZERO-SSR: Shell kosong untuk initial SSR request ===
        $isInitialSsr = !$request->header('X-Inertia') && !$request->has('async');

        if ($isInitialSsr) {
            return Inertia::render('Sarpras/Index', [
                'initialData' => null,
            ]);
        }

        // === ASYNC: Routing per tab ===
        $tab = $request->query('tab', 'barang-modal');
        $allowedTabs = \Modules\Core\Services\MenuService::getAllowedTabsForRoute($user, '/sarpras');

        $response = match($tab) {
            'bhp'          => $this->getBarangHabisPakai($request),
            'kir'          => $this->getKartuInventarisRuangan($request),
            'peminjaman'   => $this->getPeminjaman($request),
            'pemeliharaan' => $this->getPemeliharaan($request),
            default        => $this->getBarangModal($request),
        };

        if ($response instanceof JsonResponse) {
            $originalData = $response->getData(true);
            $originalData['allowed_tabs'] = $allowedTabs;
            return response()->json($originalData, $response->getStatusCode());
        }

        return $response;
    }

    // =============================================
    // TAB 1: BARANG MODAL / ASET TETAP
    // =============================================
    private function getBarangModal(Request $request): JsonResponse
    {
        $tenantId = session('tenant_id') ?? Auth::user()?->tenant_id;

        $query = InventarisAset::query();
        if (!empty($request->search))    $query->where(fn($q) => $q->where('nama_barang', 'ILIKE', "%{$request->search}%")->orWhere('kode_aset', 'ILIKE', "%{$request->search}%"));
        if (!empty($request->kategori))  $query->where('kategori', $request->kategori);
        if (!empty($request->kondisi))   $query->where('kondisi', $request->kondisi);
        if (!empty($request->ruangan))   $query->where('lokasi_ruangan', 'ILIKE', "%{$request->ruangan}%");

        $asetList     = $query->orderBy('created_at', 'desc')->paginate(20);
        $kategoriList = KategoriBarang::where('tipe', 'Barang Modal')->orderBy('nama_kategori')->get(['id', 'nama_kategori', 'kode_kategori']);
        $ruanganList  = Ruangan::orderBy('nama_ruangan')->get(['id', 'nama_ruangan', 'kode_ruangan']);

        return response()->json(['success' => true, 'tab' => 'barang-modal', 'data' => [
            'asetList'     => $asetList,
            'kategoriList' => $kategoriList,
            'ruanganList'  => $ruanganList,
        ]]);
    }

    // =============================================
    // TAB 2: KIR — KARTU INVENTARIS RUANGAN
    // =============================================
    private function getKartuInventarisRuangan(Request $request): JsonResponse
    {
        $bangunanList = Bangunan::with(['ruangans' => function ($q) {
            $q->orderBy('nama_ruangan');
        }])->orderBy('nama_bangunan')->get();

        // Untuk setiap ruangan, hitung total barang
        $bangunanList->each(function ($bangunan) {
            $bangunan->ruangans->each(function ($ruangan) {
                $ruangan->total_barang = InventarisAset::where('lokasi_ruangan', $ruangan->nama_ruangan)->count();
                $ruangan->barang_baik  = InventarisAset::where('lokasi_ruangan', $ruangan->nama_ruangan)->where('kondisi', 'Baik')->count();
            });
        });

        $selectedRuanganId = $request->query('ruangan_id');
        $inventarisRuangan = [];
        if ($selectedRuanganId) {
            $ruangan = Ruangan::find($selectedRuanganId);
            if ($ruangan) {
                $inventarisRuangan = InventarisAset::where('lokasi_ruangan', $ruangan->nama_ruangan)
                    ->orderBy('kategori')->orderBy('nama_barang')->get();
            }
        }

        return response()->json(['success' => true, 'tab' => 'kir', 'data' => [
            'bangunanList'      => $bangunanList,
            'inventarisRuangan' => $inventarisRuangan,
            'selectedRuanganId' => $selectedRuanganId,
        ]]);
    }

    // =============================================
    // TAB 3: BHP — BARANG HABIS PAKAI
    // =============================================
    private function getBarangHabisPakai(Request $request): JsonResponse
    {
        $query = BarangHabisPakai::query();
        if (!empty($request->search))   $query->where(fn($q) => $q->where('nama_barang', 'ILIKE', "%{$request->search}%")->orWhere('kode_bhp', 'ILIKE', "%{$request->search}%"));
        if (!empty($request->kategori)) $query->where('kategori', $request->kategori);
        if ($request->query('stok_minimum') == '1') {
            $query->whereRaw('stok_saat_ini <= stok_minimum');
        }

        $bhpList     = $query->orderBy('kategori')->orderBy('nama_barang')->paginate(25);
        $stokMinimum = BarangHabisPakai::whereRaw('stok_saat_ini <= stok_minimum')->count();

        return response()->json(['success' => true, 'tab' => 'bhp', 'data' => [
            'bhpList'        => $bhpList,
            'stokMinimumCount' => $stokMinimum,
        ]]);
    }

    // =============================================
    // TAB 4: PEMINJAMAN SARPRAS & FASILITAS
    // =============================================
    private function getPeminjaman(Request $request): JsonResponse
    {
        $query = PeminjamanSarpras::with(['items', 'peminjam:id,nama_lengkap'])->orderBy('created_at', 'desc');
        if (!empty($request->status)) $query->where('status', $request->status);
        if (!empty($request->search)) $query->where(fn($q) => $q->where('nomor_peminjaman', 'ILIKE', "%{$request->search}%")->orWhere('nama_peminjam', 'ILIKE', "%{$request->search}%"));

        $peminjamanList = $query->paginate(20);
        $asetOptions    = InventarisAset::orderBy('nama_barang')->get(['id', 'kode_aset', 'nama_barang', 'jumlah', 'lokasi_ruangan', 'kondisi']);

        return response()->json(['success' => true, 'tab' => 'peminjaman', 'data' => [
            'peminjamanList' => $peminjamanList,
            'asetOptions'    => $asetOptions,
        ]]);
    }

    // =============================================
    // TAB 5: RIWAYAT PEMELIHARAAN & SERVICE
    // =============================================
    private function getPemeliharaan(Request $request): JsonResponse
    {
        $query = RiwayatPemeliharaan::with(['aset:id,kode_aset,nama_barang'])->orderBy('tanggal_laporan', 'desc');
        if (!empty($request->status))  $query->where('status', $request->status);
        if (!empty($request->search))  $query->where(fn($q) => $q->where('nomor_pemeliharaan', 'ILIKE', "%{$request->search}%")->orWhere('deskripsi_kerusakan', 'ILIKE', "%{$request->search}%"));
        if (!empty($request->aset_id)) $query->where('aset_id', $request->aset_id);

        $pemeliharaanList  = $query->paginate(20);
        $asetOptions       = InventarisAset::orderBy('nama_barang')->get(['id', 'kode_aset', 'nama_barang']);
        $totalBiaya        = RiwayatPemeliharaan::where('status', 'Selesai')->sum('biaya_pemeliharaan');

        return response()->json(['success' => true, 'tab' => 'pemeliharaan', 'data' => [
            'pemeliharaanList' => $pemeliharaanList,
            'asetOptions'      => $asetOptions,
            'totalBiaya'       => $totalBiaya,
        ]]);
    }

    // =============================================
    // CRUD BARANG MODAL
    // =============================================
    public function storeBarangModal(Request $request): JsonResponse
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

        $validated['qr_code_token'] = strtoupper(Str::random(12));
        $aset = InventarisAset::create($validated);

        return response()->json(['success' => true, 'message' => 'Barang inventaris berhasil dicatat.', 'data' => $aset], 201);
    }

    public function updateBarangModal(Request $request, string $id): JsonResponse
    {
        $aset = InventarisAset::findOrFail($id);
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

        $aset->update($validated);
        return response()->json(['success' => true, 'message' => 'Barang inventaris berhasil diperbarui.', 'data' => $aset]);
    }

    public function destroyBarangModal(string $id): JsonResponse
    {
        $aset = InventarisAset::findOrFail($id);
        $aset->delete();
        return response()->json(['success' => true, 'message' => 'Barang inventaris berhasil dihapus.']);
    }

    // =============================================
    // CRUD BARANG HABIS PAKAI
    // =============================================
    public function storeBhp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'kode_bhp'          => 'required|string|max:30',
            'nama_barang'       => 'required|string|max:255',
            'kategori'          => 'required|string|max:50',
            'satuan'            => 'required|string|max:20',
            'stok_saat_ini'     => 'required|integer|min:0',
            'stok_minimum'      => 'required|integer|min:0',
            'harga_satuan'      => 'nullable|numeric|min:0',
            'lokasi_penyimpanan' => 'nullable|string|max:100',
            'keterangan'        => 'nullable|string',
        ]);

        $bhp = BarangHabisPakai::create($validated);
        return response()->json(['success' => true, 'message' => 'Barang habis pakai berhasil dicatat.', 'data' => $bhp], 201);
    }

    public function updateBhpStok(Request $request, string $id): JsonResponse
    {
        $bhp = BarangHabisPakai::findOrFail($id);
        $validated = $request->validate([
            'tipe'   => 'required|in:masuk,keluar',
            'jumlah' => 'required|integer|min:1',
            'catatan' => 'nullable|string',
        ]);

        if ($validated['tipe'] === 'keluar' && $validated['jumlah'] > $bhp->stok_saat_ini) {
            return response()->json(['success' => false, 'message' => 'Stok tidak mencukupi.'], 422);
        }

        if ($validated['tipe'] === 'masuk') {
            $bhp->increment('stok_saat_ini', $validated['jumlah']);
        } else {
            $bhp->decrement('stok_saat_ini', $validated['jumlah']);
        }

        return response()->json(['success' => true, 'message' => 'Stok BHP berhasil diperbarui.', 'data' => $bhp->fresh()]);
    }

    // =============================================
    // CRUD PEMINJAMAN
    // =============================================
    public function storePeminjaman(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nama_peminjam'            => 'required|string|max:100',
            'tujuan_peminjaman'        => 'required|string|max:500',
            'tanggal_pinjam'           => 'required|date',
            'tanggal_rencana_kembali'  => 'required|date|after_or_equal:tanggal_pinjam',
            'items'                    => 'required|array|min:1',
            'items.*.aset_id'          => 'required|uuid',
            'items.*.jumlah_dipinjam'  => 'required|integer|min:1',
        ]);

        $tenantId = session('tenant_id') ?? Auth::user()?->tenant_id;

        $peminjaman = PeminjamanSarpras::create([
            'tenant_id'               => $tenantId,
            'nomor_peminjaman'        => 'PNJ/' . date('Ymd') . '/' . strtoupper(Str::random(5)),
            'peminjam_id'             => Auth::id(),
            'nama_peminjam'           => $validated['nama_peminjam'],
            'tujuan_peminjaman'       => $validated['tujuan_peminjaman'],
            'tanggal_pinjam'          => $validated['tanggal_pinjam'],
            'tanggal_rencana_kembali' => $validated['tanggal_rencana_kembali'],
            'status'                  => 'Menunggu Persetujuan',
        ]);

        // Simpan items peminjaman via raw query (model belum dibuat di sini)
        foreach ($validated['items'] as $item) {
            DB::table('sarpras.peminjaman_sarpras_item')->insert([
                'id'              => Str::uuid()->toString(),
                'peminjaman_id'   => $peminjaman->id,
                'aset_id'         => $item['aset_id'],
                'jumlah_dipinjam' => $item['jumlah_dipinjam'],
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
        }

        return response()->json(['success' => true, 'message' => 'Permohonan peminjaman berhasil diajukan. Menunggu persetujuan.', 'data' => $peminjaman], 201);
    }

    public function approvePeminjaman(Request $request, string $id): JsonResponse
    {
        $peminjaman = PeminjamanSarpras::findOrFail($id);
        $validated  = $request->validate(['aksi' => 'required|in:setuju,tolak', 'catatan' => 'nullable|string']);

        $peminjaman->update([
            'status'              => $validated['aksi'] === 'setuju' ? 'Disetujui' : 'Ditolak',
            'disetujui_oleh'      => Auth::user()?->nama_lengkap,
            'catatan_persetujuan' => $validated['catatan'] ?? null,
        ]);

        return response()->json(['success' => true, 'message' => 'Status peminjaman berhasil diperbarui.']);
    }

    public function kembalikanSarpras(Request $request, string $id): JsonResponse
    {
        $peminjaman = PeminjamanSarpras::findOrFail($id);
        $validated  = $request->validate(['catatan_pengembalian' => 'nullable|string']);

        $peminjaman->update([
            'status'                  => 'Dikembalikan',
            'tanggal_kembali_aktual'  => now(),
            'catatan_pengembalian'    => $validated['catatan_pengembalian'] ?? null,
        ]);

        return response()->json(['success' => true, 'message' => 'Pengembalian sarpras berhasil dicatat.']);
    }

    // =============================================
    // CRUD PEMELIHARAAN
    // =============================================
    public function storePemeliharaan(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'aset_id'             => 'required|uuid|exists:sarpras.barang_modal,id',
            'jenis_pemeliharaan'  => 'required|string|max:50',
            'tanggal_laporan'     => 'required|date',
            'pelapor'             => 'required|string|max:100',
            'deskripsi_kerusakan' => 'required|string',
            'tindakan'            => 'nullable|string',
            'biaya_pemeliharaan'  => 'nullable|numeric|min:0',
            'status'              => 'required|in:Dilaporkan,Dalam Proses,Selesai,Ditunda',
            'teknisi_vendor'      => 'nullable|string|max:100',
            'tanggal_selesai'     => 'nullable|date',
            'hasil_pemeliharaan'  => 'nullable|string|max:100',
        ]);

        $tenantId = session('tenant_id') ?? Auth::user()?->tenant_id;

        $pemeliharaan = RiwayatPemeliharaan::create(array_merge($validated, [
            'tenant_id'          => $tenantId,
            'nomor_pemeliharaan' => 'PMH/' . date('Ymd') . '/' . strtoupper(Str::random(5)),
        ]));

        return response()->json(['success' => true, 'message' => 'Laporan pemeliharaan berhasil dicatat.', 'data' => $pemeliharaan->load('aset:id,kode_aset,nama_barang')], 201);
    }

    public function updateStatusPemeliharaan(Request $request, string $id): JsonResponse
    {
        $pemeliharaan = RiwayatPemeliharaan::findOrFail($id);
        $validated    = $request->validate([
            'status'             => 'required|in:Dilaporkan,Dalam Proses,Selesai,Ditunda',
            'tindakan'           => 'nullable|string',
            'biaya_pemeliharaan' => 'nullable|numeric|min:0',
            'tanggal_selesai'    => 'nullable|date',
            'hasil_pemeliharaan' => 'nullable|string|max:100',
        ]);

        $pemeliharaan->update($validated);
        return response()->json(['success' => true, 'message' => 'Status pemeliharaan berhasil diperbarui.']);
    }
}
