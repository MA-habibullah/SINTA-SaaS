<?php

namespace Modules\Perpustakaan\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Modules\Core\Entities\Tenant;
use Modules\Core\Entities\User;
use Modules\Siswa\Entities\Siswa;
use Modules\Akademik\Entities\Kelas;
use Modules\Perpustakaan\Entities\Buku;
use Modules\Perpustakaan\Entities\Eksemplar;
use Modules\Perpustakaan\Entities\Sirkulasi;
use Modules\Perpustakaan\Entities\Anggota;
use Modules\Perpustakaan\Entities\BukuTamu;
use Modules\Perpustakaan\Entities\PengaturanPerpus;
use Modules\Perpustakaan\Entities\PaketBuku;
use Modules\Perpustakaan\Entities\LokasiRak;
use Modules\Perpustakaan\Entities\UsulanBuku;
use Modules\Perpustakaan\Entities\SerialBerkala;

class PerpustakaanController extends Controller
{
    private function resolveActiveTenantId(?string $requestedTenantId = null): string
    {
        $user = Auth::user();
        if ($user && $user->isSuperAdmin() && !empty($requestedTenantId) && $requestedTenantId !== '00000000-0000-0000-0000-000000000000') {
            return $requestedTenantId;
        }

        $tenantId = session('tenant_id');
        if (!empty($tenantId) && $tenantId !== '00000000-0000-0000-0000-000000000000') {
            return $tenantId;
        }

        $firstTenant = Tenant::whereIn('status', ['active', 'aktif'])
            ->where('id', '!=', '00000000-0000-0000-0000-000000000000')
            ->orderBy('nama_sekolah', 'asc')
            ->first();

        return $firstTenant ? $firstTenant->id : '11111111-1111-1111-1111-111111111111';
    }

    private function getTenantListForSuperAdmin(): array
    {
        if (Auth::user()?->isSuperAdmin()) {
            return Tenant::whereIn('status', ['active', 'aktif'])
                ->where('id', '!=', '00000000-0000-0000-0000-000000000000')
                ->select('id', 'nama_sekolah', 'npsn')
                ->orderBy('nama_sekolah', 'asc')
                ->get()
                ->toArray();
        }
        return [];
    }

    // =========================================================================
    // 1. KATALOG & INVENTORI BUKU (/perpustakaan/katalog)
    // =========================================================================
    public function katalog(Request $request): InertiaResponse|JsonResponse
    {
        $user = Auth::user();
        $isSuperAdmin = $user ? $user->isSuperAdmin() : false;
        $activeTenantId = $this->resolveActiveTenantId($request->query('tenant_id'));

        $query = $isSuperAdmin && empty($request->query('tenant_id'))
            ? Buku::withoutTenant()->with(['eksemplar', 'tenant:id,nama_sekolah,npsn'])
            : Buku::where('tenant_id', $activeTenantId)->with(['eksemplar', 'tenant:id,nama_sekolah,npsn']);

        if ($request->filled('search')) {
            $search = trim($request->query('search'));
            $query->where(function ($q) use ($search) {
                $q->where('judul_buku', 'ILIKE', "%{$search}%")
                  ->orWhere('pengarang', 'ILIKE', "%{$search}%")
                  ->orWhere('penerbit', 'ILIKE', "%{$search}%")
                  ->orWhere('isbn', 'ILIKE', "%{$search}%")
                  ->orWhere('kode_buku', 'ILIKE', "%{$search}%")
                  ->orWhere('nomor_klasifikasi_ddc', 'ILIKE', "%{$search}%");
            });
        }

        if ($request->filled('ddc')) {
            $query->where('nomor_klasifikasi_ddc', 'LIKE', $request->query('ddc') . '%');
        }

        if ($request->filled('kategori')) {
            $query->where('kategori', $request->query('kategori'));
        }

        if ($request->filled('lokasi_rak')) {
            $query->where('lokasi_rak', $request->query('lokasi_rak'));
        }

        $bukuList = $query->orderBy('created_at', 'desc')->paginate(15)->withQueryString();

        // Data Eksemplar
        $eksemplarQuery = $isSuperAdmin && empty($request->query('tenant_id'))
            ? Eksemplar::withoutTenant()->with('buku')
            : Eksemplar::where('tenant_id', $activeTenantId)->with('buku');
        $eksemplarList = $eksemplarQuery->orderBy('created_at', 'desc')->paginate(20)->withQueryString();

        // Master Rak, Usulan, Serial
        $rakList = LokasiRak::where('tenant_id', $activeTenantId)->orderBy('kode_rak', 'asc')->get();
        $usulanList = UsulanBuku::where('tenant_id', $activeTenantId)->orderBy('created_at', 'desc')->get();
        $serialList = SerialBerkala::where('tenant_id', $activeTenantId)->orderBy('created_at', 'desc')->get();

        // Metrik Statistik
        $statsBase = $isSuperAdmin && empty($request->query('tenant_id')) ? Buku::withoutTenant() : Buku::where('tenant_id', $activeTenantId);
        $totalJudul = (clone $statsBase)->count();
        $totalEksemplar = (clone $statsBase)->sum('jumlah_eksemplar');
        $totalTersedia = (clone $statsBase)->sum('jumlah_tersedia');
        $totalEbook = (clone $statsBase)->where('is_ebook', true)->count();

        $stats = [
            'total_judul'     => $totalJudul,
            'total_eksemplar' => $totalEksemplar,
            'total_tersedia'  => $totalTersedia,
            'total_dipinjam'  => max(0, $totalEksemplar - $totalTersedia),
            'total_ebook'     => $totalEbook,
        ];

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data'    => compact('bukuList', 'eksemplarList', 'rakList', 'usulanList', 'serialList', 'stats'),
            ]);
        }

        return Inertia::render('Perpustakaan/Katalog/Index', [
            'bukuList'       => $bukuList,
            'eksemplarList'  => $eksemplarList,
            'rakList'        => $rakList,
            'usulanList'     => $usulanList,
            'serialList'     => $serialList,
            'stats'          => $stats,
            'tenants'        => $this->getTenantListForSuperAdmin(),
            'isSuperAdmin'   => $isSuperAdmin,
            'activeTenantId' => $activeTenantId,
            'filters'        => $request->only(['search', 'ddc', 'kategori', 'lokasi_rak', 'tenant_id']),
        ]);
    }

    public function storeBuku(Request $request): RedirectResponse|JsonResponse
    {
        $tenantId = $this->resolveActiveTenantId($request->input('tenant_id'));

        $validated = $request->validate([
            'judul_buku'            => ['required', 'string', 'max:255'],
            'pengarang'             => ['required', 'string', 'max:255'],
            'penerbit'              => ['nullable', 'string', 'max:255'],
            'kota_terbit'           => ['nullable', 'string', 'max:100'],
            'tahun_terbit'          => ['nullable', 'integer', 'min:1800', 'max:2100'],
            'isbn'                  => ['nullable', 'string', 'max:50'],
            'kode_buku'             => ['nullable', 'string', 'max:50'],
            'nomor_klasifikasi_ddc' => ['nullable', 'string', 'max:50'],
            'nomor_panggil'         => ['nullable', 'string', 'max:50'],
            'subjek'                => ['nullable', 'string', 'max:255'],
            'sinopsis'              => ['nullable', 'string'],
            'kategori'              => ['nullable', 'string', 'max:100'],
            'lokasi_rak'            => ['nullable', 'string', 'max:100'],
            'jumlah_eksemplar'      => ['required', 'integer', 'min:1'],
            'is_ebook'              => ['boolean'],
            'status_opac'           => ['boolean'],
            'cover_file'            => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'ebook_file'            => ['nullable', 'mimes:pdf,epub', 'max:20480'],
        ]);

        $coverUrl = null;
        if ($request->hasFile('cover_file')) {
            $path = $request->file('cover_file')->store('perpustakaan/covers', 'public');
            $coverUrl = Storage::url($path);
        }

        $ebookUrl = null;
        if ($request->hasFile('ebook_file')) {
            $path = $request->file('ebook_file')->store('perpustakaan/ebooks', 'public');
            $ebookUrl = Storage::url($path);
        }

        $id = Str::uuid()->toString();
        $kodeBuku = $validated['kode_buku'] ?: 'BK-' . strtoupper(Str::random(6));

        $buku = Buku::create([
            'id'                    => $id,
            'tenant_id'             => $tenantId,
            'nama_perpus_bibliografi' => $validated['judul_buku'],
            'judul_buku'            => $validated['judul_buku'],
            'kode_buku'             => $kodeBuku,
            'isbn'                  => $validated['isbn'] ?? null,
            'pengarang'             => $validated['pengarang'],
            'penerbit'              => $validated['penerbit'] ?? null,
            'kota_terbit'           => $validated['kota_terbit'] ?? null,
            'tahun_terbit'          => $validated['tahun_terbit'] ?? (int)date('Y'),
            'nomor_klasifikasi_ddc' => $validated['nomor_klasifikasi_ddc'] ?? '000',
            'nomor_panggil'         => $validated['nomor_panggil'] ?? ($validated['nomor_klasifikasi_ddc'] ?? '000'),
            'subjek'                => $validated['subjek'] ?? null,
            'sinopsis'              => $validated['sinopsis'] ?? null,
            'kategori'              => $validated['kategori'] ?? 'Umum',
            'lokasi_rak'            => $validated['lokasi_rak'] ?? 'Rak Utama',
            'jumlah_eksemplar'      => (int)$validated['jumlah_eksemplar'],
            'jumlah_tersedia'       => (int)$validated['jumlah_eksemplar'],
            'cover_url'             => $coverUrl,
            'ebook_url'             => $ebookUrl,
            'is_ebook'              => (bool)($validated['is_ebook'] ?? false),
            'status_opac'           => (bool)($validated['status_opac'] ?? true),
            'is_active'             => true,
        ]);

        // Auto-generate eksemplar records
        for ($i = 1; $i <= (int)$validated['jumlah_eksemplar']; $i++) {
            Eksemplar::create([
                'id'                   => Str::uuid()->toString(),
                'tenant_id'            => $tenantId,
                'bibliografi_id'       => $id,
                'nama_perpus_eksemplar'=> $validated['judul_buku'] . " (Kopi #{$i})",
                'barcode'              => $kodeBuku . '-' . str_pad($i, 3, '0', STR_PAD_LEFT),
                'no_induk'             => 'IND-' . date('Y') . '-' . str_pad($i, 4, '0', STR_PAD_LEFT),
                'lokasi_rak'           => $validated['lokasi_rak'] ?? 'Rak Utama',
                'status_kondisi'       => 'Tersedia',
                'sumber_perolehan'     => 'Pengadaan BOS / Hibah',
                'harga_beli'           => 0,
                'is_active'            => true,
            ]);
        }

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Buku berhasil ditambahkan ke katalog.', 'data' => $buku], 201);
        }

        return back()->with('success', 'Buku berhasil didaftarkan ke katalog perpustakaan.');
    }

    public function updateBuku(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $buku = Buku::withoutTenant()->findOrFail($id);

        $validated = $request->validate([
            'judul_buku'            => ['required', 'string', 'max:255'],
            'pengarang'             => ['required', 'string', 'max:255'],
            'penerbit'              => ['nullable', 'string', 'max:255'],
            'kota_terbit'           => ['nullable', 'string', 'max:100'],
            'tahun_terbit'          => ['nullable', 'integer', 'min:1800', 'max:2100'],
            'isbn'                  => ['nullable', 'string', 'max:50'],
            'kode_buku'             => ['nullable', 'string', 'max:50'],
            'nomor_klasifikasi_ddc' => ['nullable', 'string', 'max:50'],
            'nomor_panggil'         => ['nullable', 'string', 'max:50'],
            'subjek'                => ['nullable', 'string', 'max:255'],
            'sinopsis'              => ['nullable', 'string'],
            'kategori'              => ['nullable', 'string', 'max:100'],
            'lokasi_rak'            => ['nullable', 'string', 'max:100'],
            'jumlah_eksemplar'      => ['required', 'integer', 'min:1'],
            'is_ebook'              => ['boolean'],
            'status_opac'           => ['boolean'],
            'cover_file'            => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'ebook_file'            => ['nullable', 'mimes:pdf,epub', 'max:20480'],
        ]);

        if ($request->hasFile('cover_file')) {
            $path = $request->file('cover_file')->store('perpustakaan/covers', 'public');
            $validated['cover_url'] = Storage::url($path);
        }

        if ($request->hasFile('ebook_file')) {
            $path = $request->file('ebook_file')->store('perpustakaan/ebooks', 'public');
            $validated['ebook_url'] = Storage::url($path);
        }

        $validated['nama_perpus_bibliografi'] = $validated['judul_buku'];
        $buku->update($validated);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Data buku berhasil diperbarui.', 'data' => $buku]);
        }

        return back()->with('success', 'Data buku berhasil diperbarui.');
    }

    public function destroyBuku(string $id): RedirectResponse|JsonResponse
    {
        $buku = Buku::withoutTenant()->findOrFail($id);
        $buku->delete();

        return back()->with('success', 'Buku berhasil dihapus dari katalog.');
    }

    public function toggleStatusBuku(string $id): RedirectResponse|JsonResponse
    {
        $buku = Buku::withoutTenant()->findOrFail($id);
        $buku->status_opac = !$buku->status_opac;
        $buku->save();

        return back()->with('success', 'Visibilitas OPAC buku berhasil diubah.');
    }

    public function storeEksemplar(Request $request): RedirectResponse|JsonResponse
    {
        $tenantId = $this->resolveActiveTenantId($request->input('tenant_id'));

        $validated = $request->validate([
            'bibliografi_id'   => ['required', 'uuid', 'exists:perpustakaan.perpus_bibliografi,id'],
            'barcode'          => ['required', 'string', 'max:100'],
            'no_induk'         => ['nullable', 'string', 'max:100'],
            'lokasi_rak'       => ['nullable', 'string', 'max:100'],
            'status_kondisi'   => ['required', 'string', 'in:Tersedia,Dipinjam,Rusak,Hilang'],
            'sumber_perolehan' => ['nullable', 'string', 'max:100'],
            'harga_beli'       => ['nullable', 'numeric', 'min:0'],
        ]);

        $buku = Buku::withoutTenant()->findOrFail($validated['bibliografi_id']);

        $eksemplar = Eksemplar::create([
            'id'                   => Str::uuid()->toString(),
            'tenant_id'            => $tenantId,
            'bibliografi_id'       => $buku->id,
            'nama_perpus_eksemplar'=> $buku->judul_buku . ' (Eksemplar)',
            'barcode'              => $validated['barcode'],
            'no_induk'             => $validated['no_induk'] ?? 'IND-' . strtoupper(Str::random(6)),
            'lokasi_rak'           => $validated['lokasi_rak'] ?? $buku->lokasi_rak,
            'status_kondisi'       => $validated['status_kondisi'],
            'sumber_perolehan'     => $validated['sumber_perolehan'] ?? 'Pengadaan Sekolah',
            'harga_beli'           => $validated['harga_beli'] ?? 0,
            'is_active'            => true,
        ]);

        $buku->increment('jumlah_eksemplar');
        if ($validated['status_kondisi'] === 'Tersedia') {
            $buku->increment('jumlah_tersedia');
        }

        return back()->with('success', 'Eksemplar buku berhasil ditambahkan.');
    }

    public function destroyEksemplar(string $id): RedirectResponse|JsonResponse
    {
        $eks = Eksemplar::withoutTenant()->findOrFail($id);
        $buku = Buku::withoutTenant()->find($eks->bibliografi_id);
        if ($buku) {
            $buku->decrement('jumlah_eksemplar');
            if ($eks->status_kondisi === 'Tersedia') {
                $buku->decrement('jumlah_tersedia');
            }
        }
        $eks->delete();

        return back()->with('success', 'Eksemplar berhasil dihapus.');
    }

    public function storeRak(Request $request): RedirectResponse|JsonResponse
    {
        $tenantId = $this->resolveActiveTenantId($request->input('tenant_id'));

        $validated = $request->validate([
            'kode_rak'       => ['required', 'string', 'max:50'],
            'nama_rak'       => ['required', 'string', 'max:100'],
            'lantai_gedung'  => ['nullable', 'string', 'max:100'],
            'kapasitas_buku' => ['nullable', 'integer', 'min:1'],
        ]);

        LokasiRak::create([
            'id'                   => Str::uuid()->toString(),
            'tenant_id'            => $tenantId,
            'nama_perpus_lokasi_rak' => $validated['nama_rak'],
            'kode_rak'             => $validated['kode_rak'],
            'nama_rak'             => $validated['nama_rak'],
            'lantai_gedung'        => $validated['lantai_gedung'] ?? 'Lantai 1',
            'kapasitas_buku'       => $validated['kapasitas_buku'] ?? 100,
            'is_active'            => true,
        ]);

        return back()->with('success', 'Lokasi rak berhasil ditambahkan.');
    }

    public function destroyRak(string $id): RedirectResponse|JsonResponse
    {
        LokasiRak::withoutTenant()->findOrFail($id)->delete();
        return back()->with('success', 'Lokasi rak berhasil dihapus.');
    }

    public function storeUsulan(Request $request): RedirectResponse|JsonResponse
    {
        $tenantId = $this->resolveActiveTenantId($request->input('tenant_id'));

        $validated = $request->validate([
            'judul_buku'    => ['required', 'string', 'max:255'],
            'pengarang'     => ['nullable', 'string', 'max:255'],
            'penerbit'      => ['nullable', 'string', 'max:255'],
            'pengusul_nama' => ['required', 'string', 'max:255'],
            'alasan_usulan' => ['nullable', 'string'],
        ]);

        UsulanBuku::create([
            'id'                     => Str::uuid()->toString(),
            'tenant_id'              => $tenantId,
            'nama_perpus_usulan_buku'=> $validated['judul_buku'],
            'judul_buku'             => $validated['judul_buku'],
            'pengarang'              => $validated['pengarang'] ?? '-',
            'penerbit'               => $validated['penerbit'] ?? '-',
            'pengusul_nama'          => $validated['pengusul_nama'],
            'alasan_usulan'          => $validated['alasan_usulan'] ?? '-',
            'status_usulan'          => 'Pending',
            'is_active'              => true,
        ]);

        return back()->with('success', 'Usulan buku berhasil dikirimkan.');
    }

    public function updateStatusUsulan(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'status_usulan' => ['required', 'string', 'in:Pending,Disetujui,Ditolak,Terbeli'],
        ]);

        $usulan = UsulanBuku::withoutTenant()->findOrFail($id);
        $usulan->status_usulan = $validated['status_usulan'];
        $usulan->save();

        return back()->with('success', 'Status usulan buku berhasil diperbarui.');
    }

    public function storeSerial(Request $request): RedirectResponse|JsonResponse
    {
        $tenantId = $this->resolveActiveTenantId($request->input('tenant_id'));

        $validated = $request->validate([
            'nama_serial'      => ['required', 'string', 'max:255'],
            'jenis_serial'     => ['required', 'string', 'in:Majalah,Jurnal,Surat Kabar,Tabloid'],
            'issn'             => ['nullable', 'string', 'max:50'],
            'edisi_nomor'      => ['nullable', 'string', 'max:100'],
            'frekuensi_terbit' => ['nullable', 'string', 'max:50'],
        ]);

        SerialBerkala::create([
            'id'                        => Str::uuid()->toString(),
            'tenant_id'                 => $tenantId,
            'nama_perpus_serial_berkala'=> $validated['nama_serial'],
            'nama_serial'               => $validated['nama_serial'],
            'jenis_serial'              => $validated['jenis_serial'],
            'issn'                      => $validated['issn'] ?? '-',
            'edisi_nomor'               => $validated['edisi_nomor'] ?? 'Edisi Terbaru',
            'frekuensi_terbit'          => $validated['frekuensi_terbit'] ?? 'Bulanan',
            'is_active'                 => true,
        ]);

        return back()->with('success', 'Serial berkala / majalah berhasil ditambahkan.');
    }

    public function destroySerial(string $id): RedirectResponse|JsonResponse
    {
        SerialBerkala::withoutTenant()->findOrFail($id)->delete();
        return back()->with('success', 'Serial berkala berhasil dihapus.');
    }

    // =========================================================================
    // 2. SIRKULASI & LAYANAN PERPUSTAKAAN (/perpustakaan/sirkulasi)
    // =========================================================================
    public function sirkulasi(Request $request): InertiaResponse|JsonResponse
    {
        $user = Auth::user();
        $isSuperAdmin = $user ? $user->isSuperAdmin() : false;
        $activeTenantId = $this->resolveActiveTenantId($request->query('tenant_id'));

        // 1. Peminjaman Aktif
        $aktifQuery = $isSuperAdmin && empty($request->query('tenant_id'))
            ? Sirkulasi::withoutTenant()->where('status_sirkulasi', 'Dipinjam')->with(['buku', 'eksemplar', 'tenant:id,nama_sekolah'])
            : Sirkulasi::where('tenant_id', $activeTenantId)->where('status_sirkulasi', 'Dipinjam')->with(['buku', 'eksemplar', 'tenant:id,nama_sekolah']);

        if ($request->filled('search')) {
            $search = trim($request->query('search'));
            $aktifQuery->where(function ($q) use ($search) {
                $q->where('nama_peminjam', 'ILIKE', "%{$search}%")
                  ->orWhere('nomor_identitas', 'ILIKE', "%{$search}%")
                  ->orWhere('nomor_transaksi', 'ILIKE', "%{$search}%")
                  ->orWhereHas('buku', function ($bq) use ($search) {
                      $bq->where('judul_buku', 'ILIKE', "%{$search}%");
                  });
            });
        }
        $sirkulasiAktif = $aktifQuery->orderBy('tanggal_harus_kembali', 'asc')->paginate(15, ['*'], 'p_aktif')->withQueryString();

        // 2. Riwayat Sirkulasi Selesai
        $riwayatQuery = $isSuperAdmin && empty($request->query('tenant_id'))
            ? Sirkulasi::withoutTenant()->where('status_sirkulasi', '!=', 'Dipinjam')->with(['buku', 'eksemplar', 'tenant:id,nama_sekolah'])
            : Sirkulasi::where('tenant_id', $activeTenantId)->where('status_sirkulasi', '!=', 'Dipinjam')->with(['buku', 'eksemplar', 'tenant:id,nama_sekolah']);
        $sirkulasiRiwayat = $riwayatQuery->orderBy('tanggal_kembali_aktual', 'desc')->paginate(15, ['*'], 'p_riwayat')->withQueryString();

        // 3. Buku Paket Pelajaran
        $paketQuery = $isSuperAdmin && empty($request->query('tenant_id'))
            ? PaketBuku::withoutTenant()->with('buku')
            : PaketBuku::where('tenant_id', $activeTenantId)->with('buku');
        $paketList = $paketQuery->orderBy('created_at', 'desc')->get();

        // 4. Kas Denda Keterlambatan
        $dendaQuery = $isSuperAdmin && empty($request->query('tenant_id'))
            ? Sirkulasi::withoutTenant()->where('denda_keterlambatan', '>', 0)->with('buku')
            : Sirkulasi::where('tenant_id', $activeTenantId)->where('denda_keterlambatan', '>', 0)->with('buku');
        $dendaList = $dendaQuery->orderBy('created_at', 'desc')->paginate(15, ['*'], 'p_denda')->withQueryString();

        // Daftar Buku Tersedia untuk Selector Peminjaman
        $bukuTersedia = Buku::where('tenant_id', $activeTenantId)
            ->where('jumlah_tersedia', '>', 0)
            ->select('id', 'judul_buku', 'pengarang', 'jumlah_tersedia', 'kode_buku')
            ->orderBy('judul_buku', 'asc')
            ->get();

        // Anggota Federasi Ringkas untuk Selector Peminjaman
        $anggotaSelector = $this->getUnifiedMembersList($activeTenantId, 100);

        // Pengaturan Perpus
        $pengaturan = PengaturanPerpus::where('tenant_id', $activeTenantId)->first();

        // Statistik Sirkulasi
        $totalPinjamAktif = (clone $aktifQuery)->count();
        $totalTerlambat = (clone $aktifQuery)->where('tanggal_harus_kembali', '<', Carbon::today()->toDateString())->count();
        $totalDendaBelumLunas = (clone $dendaQuery)->where('status_denda', 'Belum Lunas')->sum('denda_keterlambatan');
        $totalDendaTerkumpul = (clone $dendaQuery)->sum('denda_dibayar');

        $stats = [
            'total_pinjam_aktif'    => $totalPinjamAktif,
            'total_terlambat'       => $totalTerlambat,
            'total_denda_tunggakan' => $totalDendaBelumLunas,
            'total_denda_lunas'     => $totalDendaTerkumpul,
        ];

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data'    => compact('sirkulasiAktif', 'sirkulasiRiwayat', 'paketList', 'dendaList', 'stats'),
            ]);
        }

        return Inertia::render('Perpustakaan/Sirkulasi/Index', [
            'sirkulasiAktif'   => $sirkulasiAktif,
            'sirkulasiRiwayat' => $sirkulasiRiwayat,
            'paketList'        => $paketList,
            'dendaList'        => $dendaList,
            'bukuTersedia'     => $bukuTersedia,
            'anggotaSelector'  => $anggotaSelector,
            'pengaturan'       => $pengaturan,
            'stats'            => $stats,
            'tenants'          => $this->getTenantListForSuperAdmin(),
            'isSuperAdmin'     => $isSuperAdmin,
            'activeTenantId'   => $activeTenantId,
            'filters'          => $request->only(['search', 'tenant_id']),
        ]);
    }

    public function pinjamBuku(Request $request): RedirectResponse|JsonResponse
    {
        $tenantId = $this->resolveActiveTenantId($request->input('tenant_id'));

        $validated = $request->validate([
            'buku_id'          => ['required', 'uuid', 'exists:perpustakaan.perpus_bibliografi,id'],
            'peminjam_type'    => ['required', 'string', 'in:Siswa,Guru,Tendik,Umum'],
            'peminjam_id'      => ['required', 'string'],
            'nama_peminjam'    => ['required', 'string', 'max:255'],
            'nomor_identitas'  => ['nullable', 'string', 'max:100'],
            'kelas_unit'       => ['nullable', 'string', 'max:100'],
            'tanggal_pinjam'   => ['required', 'date'],
            'durasi_hari'      => ['required', 'integer', 'min:1', 'max:60'],
            'catatan'          => ['nullable', 'string'],
        ]);

        return DB::transaction(function () use ($validated, $tenantId, $request) {
            $buku = Buku::lockForUpdate()->findOrFail($validated['buku_id']);
            if ($buku->jumlah_tersedia <= 0) {
                abort(422, "Eksemplar buku '{$buku->judul_buku}' sedang habis.");
            }

            $tglPinjam = Carbon::parse($validated['tanggal_pinjam']);
            $tglHarusKembali = $tglPinjam->copy()->addDays((int)$validated['durasi_hari']);

            // Cari eksemplar tersedia jika ada
            $eksemplar = Eksemplar::where('bibliografi_id', $buku->id)
                ->where('status_kondisi', 'Tersedia')
                ->first();

            $nomorTransaksi = 'PINJAM/' . date('Ymd') . '/' . strtoupper(Str::random(5));

            $sirkulasi = Sirkulasi::create([
                'id'                    => Str::uuid()->toString(),
                'tenant_id'             => $tenantId,
                'nama_perpus_sirkulasi' => "Pinjam: {$buku->judul_buku} oleh {$validated['nama_peminjam']}",
                'nomor_transaksi'       => $nomorTransaksi,
                'buku_id'               => $buku->id,
                'eksemplar_id'          => $eksemplar ? $eksemplar->id : null,
                'peminjam_type'         => $validated['peminjam_type'],
                'peminjam_id'           => $validated['peminjam_id'],
                'nama_peminjam'         => $validated['nama_peminjam'],
                'nomor_identitas'       => $validated['nomor_identitas'] ?? '-',
                'kelas_unit'            => $validated['kelas_unit'] ?? '-',
                'tanggal_pinjam'        => $tglPinjam->toDateString(),
                'tanggal_harus_kembali' => $tglHarusKembali->toDateString(),
                'jumlah_perpanjangan'   => 0,
                'status_sirkulasi'      => 'Dipinjam',
                'tarif_denda_harian'    => 1000,
                'hari_keterlambatan'    => 0,
                'denda_keterlambatan'   => 0,
                'denda_dibayar'         => 0,
                'status_denda'          => 'Nihil',
                'catatan'               => $validated['catatan'] ?? null,
                'petugas_peminjaman'    => Auth::user()?->nama_lengkap ?? 'Petugas Perpustakaan',
                'is_active'             => true,
            ]);

            $buku->decrement('jumlah_tersedia');
            if ($eksemplar) {
                $eksemplar->update(['status_kondisi' => 'Dipinjam']);
            }

            if ($request->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'Peminjaman berhasil dicatat.', 'data' => $sirkulasi], 201);
            }

            return back()->with('success', "Peminjaman buku '{$buku->judul_buku}' berhasil diproses.");
        });
    }

    public function kembalikanBuku(Request $request, string $id): RedirectResponse|JsonResponse
    {
        return DB::transaction(function () use ($id, $request) {
            $sirkulasi = Sirkulasi::lockForUpdate()->findOrFail($id);
            if ($sirkulasi->status_sirkulasi === 'Kembali') {
                abort(422, 'Buku ini sudah dinyatakan kembali sebelumnya.');
            }

            $tglKembali = Carbon::now();
            $tglDeadline = Carbon::parse($sirkulasi->tanggal_harus_kembali);
            $terlambatHari = max(0, $tglKembali->diffInDays($tglDeadline, false) * -1);

            $tarifDenda = (float)($sirkulasi->tarif_denda_harian ?: 1000);
            $totalDenda = $terlambatHari * $tarifDenda;

            $sirkulasi->update([
                'tanggal_kembali_aktual' => $tglKembali->toDateString(),
                'status_sirkulasi'       => 'Kembali',
                'hari_keterlambatan'     => $terlambatHari,
                'denda_keterlambatan'    => $totalDenda,
                'status_denda'           => ($totalDenda > 0) ? 'Belum Lunas' : 'Nihil',
                'petugas_pengembalian'   => Auth::user()?->nama_lengkap ?? 'Petugas Perpustakaan',
            ]);

            $buku = Buku::find($sirkulasi->buku_id);
            if ($buku) {
                $buku->increment('jumlah_tersedia');
            }

            if ($sirkulasi->eksemplar_id) {
                Eksemplar::where('id', $sirkulasi->eksemplar_id)->update(['status_kondisi' => 'Tersedia']);
            }

            $pesan = $totalDenda > 0 
                ? "Buku berhasil dikembalikan. Terdapat denda keterlambatan ({$terlambatHari} hari) sebesar Rp " . number_format($totalDenda, 0, ',', '.')
                : "Buku berhasil dikembalikan tepat waktu tanpa denda.";

            if ($request->wantsJson()) {
                return response()->json(['success' => true, 'message' => $pesan, 'denda' => $totalDenda]);
            }

            return back()->with('success', $pesan);
        });
    }

    public function perpanjangBuku(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $sirkulasi = Sirkulasi::findOrFail($id);
        if ($sirkulasi->status_sirkulasi !== 'Dipinjam') {
            abort(422, 'Hanya peminjaman aktif yang dapat diperpanjang.');
        }

        $durasiTambah = (int) $request->input('hari', 7);
        $deadlineLama = Carbon::parse($sirkulasi->tanggal_harus_kembali);
        $deadlineBaru = $deadlineLama->copy()->addDays($durasiTambah);

        $sirkulasi->update([
            'tanggal_harus_kembali' => $deadlineBaru->toDateString(),
            'jumlah_perpanjangan'   => $sirkulasi->jumlah_perpanjangan + 1,
            'catatan'               => ($sirkulasi->catatan ? $sirkulasi->catatan . ' | ' : '') . "Diperpanjang +{$durasiTambah} hari pada " . date('d/m/Y'),
        ]);

        return back()->with('success', "Masa pinjam berhasil diperpanjang hingga {$deadlineBaru->format('d/m/Y')}.");
    }

    public function bayarDenda(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $sirkulasi = Sirkulasi::findOrFail($id);
        $sirkulasi->update([
            'denda_dibayar' => $sirkulasi->denda_keterlambatan,
            'status_denda'  => 'Lunas',
        ]);

        return back()->with('success', 'Denda keterlambatan telah dilunasi.');
    }

    public function distribusiBukuPaket(Request $request): RedirectResponse|JsonResponse
    {
        $tenantId = $this->resolveActiveTenantId($request->input('tenant_id'));

        $validated = $request->validate([
            'kelas_id'          => ['required', 'string', 'max:100'],
            'tahun_ajaran'      => ['required', 'string', 'max:50'],
            'mata_pelajaran'    => ['required', 'string', 'max:100'],
            'buku_id'           => ['required', 'uuid', 'exists:perpustakaan.perpus_bibliografi,id'],
            'jumlah_distribusi' => ['required', 'integer', 'min:1'],
        ]);

        $buku = Buku::withoutTenant()->findOrFail($validated['buku_id']);

        PaketBuku::create([
            'id'                     => Str::uuid()->toString(),
            'tenant_id'              => $tenantId,
            'nama_perpus_paket_buku' => "Paket {$validated['mata_pelajaran']} Kelas {$validated['kelas_id']}",
            'kelas_id'               => $validated['kelas_id'],
            'tahun_ajaran'           => $validated['tahun_ajaran'],
            'mata_pelajaran'         => $validated['mata_pelajaran'],
            'buku_id'                => $buku->id,
            'jumlah_distribusi'      => $validated['jumlah_distribusi'],
            'status_distribusi'      => 'Terdistribusi',
            'is_active'              => true,
        ]);

        return back()->with('success', "Alokasi {$validated['jumlah_distribusi']} buku paket ke kelas {$validated['kelas_id']} berhasil dicatat.");
    }

    // =========================================================================
    // 3. ANGGOTA, BUKU TAMU & BEBAS PUSTAKA (/perpustakaan/anggota)
    // =========================================================================
    public function anggota(Request $request): InertiaResponse|JsonResponse
    {
        $user = Auth::user();
        $isSuperAdmin = $user ? $user->isSuperAdmin() : false;
        $activeTenantId = $this->resolveActiveTenantId($request->query('tenant_id'));

        $unifiedMembers = $this->getUnifiedMembersList($activeTenantId, 100, $request->query('search'), $request->query('kategori'));

        // Visitor Log / Buku Tamu
        $tamuQuery = $isSuperAdmin && empty($request->query('tenant_id'))
            ? BukuTamu::withoutTenant()
            : BukuTamu::where('tenant_id', $activeTenantId);
        $bukuTamuList = $tamuQuery->orderBy('tanggal_kunjungan', 'desc')->paginate(20, ['*'], 'p_tamu')->withQueryString();

        // Visitor Stats
        $hariIni = Carbon::today()->toDateString();
        $awalBulan = Carbon::today()->startOfMonth()->toDateString();
        $statsTamu = [
            'total_hari_ini'  => (clone $tamuQuery)->where('tanggal_kunjungan', $hariIni)->count(),
            'total_bulan_ini' => (clone $tamuQuery)->where('tanggal_kunjungan', '>=', $awalBulan)->count(),
            'total_siswa'     => (clone $tamuQuery)->where('tipe_pengunjung', 'Siswa')->count(),
            'total_guru'      => (clone $tamuQuery)->whereIn('tipe_pengunjung', ['Guru', 'Tendik'])->count(),
        ];

        // Pengaturan Perpustakaan
        $pengaturan = PengaturanPerpus::where('tenant_id', $activeTenantId)->first();
        if (!$pengaturan) {
            $pengaturan = PengaturanPerpus::create([
                'id'                   => Str::uuid()->toString(),
                'tenant_id'            => $activeTenantId,
                'nama_perpus_pengaturan' => 'Perpustakaan Digital',
                'nama_perpustakaan'    => 'Perpustakaan Digital SINTA',
                'kepala_perpustakaan'  => 'Pustakawan Utama',
                'nip_kepala'           => '-',
                'tarif_denda_per_hari' => 1000,
                'max_hari_pinjam_siswa'=> 7,
                'max_hari_pinjam_guru' => 14,
                'max_buku_pinjam_siswa'=> 3,
                'max_buku_pinjam_guru' => 10,
                'opac_aktif'           => true,
                'is_active'            => true,
            ]);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data'    => compact('unifiedMembers', 'bukuTamuList', 'statsTamu', 'pengaturan'),
            ]);
        }

        return Inertia::render('Perpustakaan/Anggota/Index', [
            'members'        => $unifiedMembers,
            'bukuTamuList'   => $bukuTamuList,
            'statsTamu'      => $statsTamu,
            'pengaturan'     => $pengaturan,
            'tenants'        => $this->getTenantListForSuperAdmin(),
            'isSuperAdmin'   => $isSuperAdmin,
            'activeTenantId' => $activeTenantId,
            'filters'        => $request->only(['search', 'kategori', 'tenant_id']),
        ]);
    }

    public function storeAnggota(Request $request): RedirectResponse|JsonResponse
    {
        $tenantId = $this->resolveActiveTenantId($request->input('tenant_id'));

        $validated = $request->validate([
            'nama_lengkap'  => ['required', 'string', 'max:255'],
            'tipe_anggota'  => ['required', 'string', 'in:Umum,Alumni,Tamu,Mitra'],
            'identitas_no'  => ['nullable', 'string', 'max:100'],
            'kelas_jurusan' => ['nullable', 'string', 'max:100'],
            'jenis_kelamin' => ['required', 'string', 'in:L,P'],
            'no_telepon'    => ['nullable', 'string', 'max:50'],
            'alamat'        => ['nullable', 'string'],
        ]);

        $noAnggota = 'LIB-' . date('Y') . '-' . strtoupper(Str::random(5));

        Anggota::create([
            'id'                  => Str::uuid()->toString(),
            'tenant_id'           => $tenantId,
            'nama_perpus_anggota' => $validated['nama_lengkap'],
            'no_anggota'          => $noAnggota,
            'nama_lengkap'        => $validated['nama_lengkap'],
            'tipe_anggota'        => $validated['tipe_anggota'],
            'identitas_no'        => $validated['identitas_no'] ?? '-',
            'kelas_jurusan'       => $validated['kelas_jurusan'] ?? 'Umum',
            'jenis_kelamin'       => $validated['jenis_kelamin'],
            'no_telepon'          => $validated['no_telepon'] ?? '-',
            'alamat'              => $validated['alamat'] ?? '-',
            'is_active'           => true,
        ]);

        return back()->with('success', "Anggota baru ({$noAnggota}) berhasil didaftarkan.");
    }

    public function destroyAnggota(string $id): RedirectResponse|JsonResponse
    {
        Anggota::withoutTenant()->findOrFail($id)->delete();
        return back()->with('success', 'Data anggota luar berhasil dihapus.');
    }

    public function cekBebasPustaka(Request $request, string $id): JsonResponse
    {
        $activeTenantId = $this->resolveActiveTenantId($request->query('tenant_id'));

        $circRows = Sirkulasi::where('tenant_id', $activeTenantId)
            ->where('peminjam_id', $id)
            ->orderBy('created_at', 'desc')
            ->get();

        $pinjamanAktif = $circRows->where('status_sirkulasi', 'Dipinjam')->values();
        $riwayatPinjam = $circRows->where('status_sirkulasi', '!=', 'Dipinjam')->values();
        $totalDenda = $circRows->where('status_denda', 'Belum Lunas')->sum('denda_keterlambatan');

        $isClear = ($pinjamanAktif->count() === 0 && $totalDenda <= 0);

        return response()->json([
            'success'                => true,
            'is_clear'               => $isClear,
            'status_label'           => $isClear ? 'BEBAS PUSTAKA (CLEAR)' : 'MASIH MEMILIKI TANGGUNGAN',
            'total_pinjam_aktif'     => $pinjamanAktif->count(),
            'total_denda_tertunggak' => $totalDenda,
            'pinjaman_aktif'         => $pinjamanAktif,
            'riwayat_pinjam'         => $riwayatPinjam,
            'nomor_surat'            => '421.3/' . rand(100, 999) . '/PERPUS/' . date('Y'),
            'tanggal_terbit'         => Carbon::now()->translatedFormat('d F Y'),
        ]);
    }

    public function storeBukuTamu(Request $request): RedirectResponse|JsonResponse
    {
        $tenantId = $this->resolveActiveTenantId($request->input('tenant_id'));

        $validated = $request->validate([
            'nama_pengunjung' => ['required', 'string', 'max:255'],
            'tipe_pengunjung' => ['required', 'string', 'in:Siswa,Guru,Tendik,Tamu,Alumni'],
            'identitas_no'    => ['nullable', 'string', 'max:100'],
            'kelas_instansi'  => ['nullable', 'string', 'max:100'],
            'keperluan'       => ['required', 'string', 'max:255'],
        ]);

        BukuTamu::create([
            'id'                    => Str::uuid()->toString(),
            'tenant_id'             => $tenantId,
            'nama_perpus_buku_tamu' => $validated['nama_pengunjung'],
            'nama_pengunjung'       => $validated['nama_pengunjung'],
            'tipe_pengunjung'       => $validated['tipe_pengunjung'],
            'identitas_no'          => $validated['identitas_no'] ?? '-',
            'kelas_instansi'        => $validated['kelas_instansi'] ?? 'Umum',
            'keperluan'             => $validated['keperluan'],
            'tanggal_kunjungan'     => Carbon::today()->toDateString(),
            'is_active'             => true,
        ]);

        return back()->with('success', 'Presensi kunjungan perpustakaan berhasil dicatat.');
    }

    public function updatePengaturan(Request $request): RedirectResponse|JsonResponse
    {
        $tenantId = $this->resolveActiveTenantId($request->input('tenant_id'));

        $validated = $request->validate([
            'nama_perpustakaan'    => ['required', 'string', 'max:255'],
            'kepala_perpustakaan'  => ['nullable', 'string', 'max:255'],
            'nip_kepala'           => ['nullable', 'string', 'max:100'],
            'tarif_denda_per_hari' => ['required', 'numeric', 'min:0'],
            'max_hari_pinjam_siswa'=> ['required', 'integer', 'min:1'],
            'max_hari_pinjam_guru' => ['required', 'integer', 'min:1'],
            'max_buku_pinjam_siswa'=> ['required', 'integer', 'min:1'],
            'max_buku_pinjam_guru' => ['required', 'integer', 'min:1'],
            'opac_aktif'           => ['boolean'],
            'syarat_bebas_pustaka' => ['nullable', 'string'],
        ]);

        $pengaturan = PengaturanPerpus::where('tenant_id', $tenantId)->first();
        if ($pengaturan) {
            $pengaturan->update($validated);
        } else {
            $validated['id'] = Str::uuid()->toString();
            $validated['tenant_id'] = $tenantId;
            $validated['nama_perpus_pengaturan'] = $validated['nama_perpustakaan'];
            $validated['is_active'] = true;
            PengaturanPerpus::create($validated);
        }

        return back()->with('success', 'Pengaturan perpustakaan berhasil disimpan.');
    }

    // =========================================================================
    // 4. OPAC PUBLIK DIGITAL (/perpustakaan/opac)
    // =========================================================================
    public function opac(Request $request): InertiaResponse|JsonResponse
    {
        $activeTenantId = $this->resolveActiveTenantId($request->query('tenant_id'));

        $query = Buku::where('tenant_id', $activeTenantId)->where('status_opac', true);

        if ($request->filled('q')) {
            $q = trim($request->query('q'));
            $query->where(function ($sub) use ($q) {
                $sub->where('judul_buku', 'ILIKE', "%{$q}%")
                    ->orWhere('pengarang', 'ILIKE', "%{$q}%")
                    ->orWhere('penerbit', 'ILIKE', "%{$q}%")
                    ->orWhere('subjek', 'ILIKE', "%{$q}%")
                    ->orWhere('nomor_klasifikasi_ddc', 'ILIKE', "%{$q}%");
            });
        }

        if ($request->filled('ddc')) {
            $query->where('nomor_klasifikasi_ddc', 'LIKE', $request->query('ddc') . '%');
        }

        if ($request->filled('ebook_only')) {
            $query->where('is_ebook', true);
        }

        $bukuList = $query->orderBy('judul_buku', 'asc')->paginate(12)->withQueryString();

        $pengaturan = PengaturanPerpus::where('tenant_id', $activeTenantId)->first();

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'data' => $bukuList]);
        }

        return Inertia::render('Perpustakaan/Opac/Index', [
            'bukuList'       => $bukuList,
            'pengaturan'     => $pengaturan,
            'tenants'        => $this->getTenantListForSuperAdmin(),
            'isSuperAdmin'   => Auth::user()?->isSuperAdmin() ?? false,
            'activeTenantId' => $activeTenantId,
            'filters'        => $request->only(['q', 'ddc', 'ebook_only', 'tenant_id']),
        ]);
    }

    // =========================================================================
    // 5. RIWAYAT SAYA (/perpustakaan/riwayat-saya)
    // =========================================================================
    public function riwayatSaya(Request $request): InertiaResponse|JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        $tenantId = session('tenant_id') ?: $user->tenant_id;
        $username = $user->username;
        $namaLengkap = $user->nama_lengkap;

        $circQuery = Sirkulasi::where('tenant_id', $tenantId)
            ->where(function ($q) use ($user, $username, $namaLengkap) {
                $q->where('peminjam_id', $user->id)
                  ->orWhere('nomor_identitas', $username)
                  ->orWhere('nama_peminjam', 'ILIKE', "%{$namaLengkap}%");
            })
            ->with('buku');

        $pinjamanAktif = (clone $circQuery)->where('status_sirkulasi', 'Dipinjam')->orderBy('tanggal_harus_kembali', 'asc')->get();
        $riwayatSelesai = (clone $circQuery)->where('status_sirkulasi', '!=', 'Dipinjam')->orderBy('tanggal_kembali_aktual', 'desc')->paginate(15);
        $totalDenda = (clone $circQuery)->where('status_denda', 'Belum Lunas')->sum('denda_keterlambatan');

        $isBebasPustaka = ($pinjamanAktif->count() === 0 && $totalDenda <= 0);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data'    => compact('pinjamanAktif', 'riwayatSelesai', 'totalDenda', 'isBebasPustaka'),
            ]);
        }

        return Inertia::render('Perpustakaan/RiwayatSaya/Index', [
            'pinjamanAktif'  => $pinjamanAktif,
            'riwayatSelesai' => $riwayatSelesai,
            'totalDenda'     => $totalDenda,
            'isBebasPustaka' => $isBebasPustaka,
            'userProfile'    => [
                'nama_lengkap' => $namaLengkap,
                'username'     => $username,
                'role'         => $user->role?->nama_role ?? 'Pengguna',
            ],
        ]);
    }

    // =========================================================================
    // HELPER AUTO-FEDERASI ANGGOTA (SISWA, GURU, UMUM)
    // =========================================================================
    private function getUnifiedMembersList(string $tenantId, int $limit = 100, ?string $search = null, ?string $kategori = null): array
    {
        $members = [];

        // 1. Siswa
        if (empty($kategori) || $kategori === 'Siswa') {
            $siswaQuery = Siswa::where('tenant_id', $tenantId)->select('id', 'nama_lengkap', 'nisn', 'nis', 'kelas_saat_ini', 'no_hp', 'alamat', 'foto_url');
            if (!empty($search)) {
                $siswaQuery->where(function ($q) use ($search) {
                    $q->where('nama_lengkap', 'ILIKE', "%{$search}%")
                      ->orWhere('nisn', 'ILIKE', "%{$search}%")
                      ->orWhere('nis', 'ILIKE', "%{$search}%");
                });
            }
            $siswas = $siswaQuery->limit(50)->get();
            foreach ($siswas as $s) {
                $pinjamAktif = Sirkulasi::where('tenant_id', $tenantId)->where('peminjam_id', $s->id)->where('status_sirkulasi', 'Dipinjam')->count();
                $denda = Sirkulasi::where('tenant_id', $tenantId)->where('peminjam_id', $s->id)->where('status_denda', 'Belum Lunas')->sum('denda_keterlambatan');

                $members[] = [
                    'id'                   => $s->id,
                    'no_anggota'           => 'SIS-' . ($s->nisn ?: ($s->nis ?: substr($s->id, 0, 6))),
                    'nama_lengkap'         => $s->nama_lengkap,
                    'tipe_anggota'         => 'Siswa',
                    'identitas_no'         => $s->nisn ?: ($s->nis ?: '-'),
                    'kelas_jurusan'        => $s->kelas_saat_ini ?: 'Kelas Reguler',
                    'no_telepon'           => $s->no_hp ?: '-',
                    'pinjam_aktif'         => $pinjamAktif,
                    'total_denda'          => $denda,
                    'status_bebas_pustaka' => ($pinjamAktif === 0 && $denda <= 0) ? 1 : 0,
                ];
            }
        }

        // 2. Guru & Tendik
        if (empty($kategori) || $kategori === 'Guru' || $kategori === 'Tendik') {
            $userQuery = User::where('tenant_id', $tenantId)->with('role');
            if (!empty($search)) {
                $userQuery->where(function ($q) use ($search) {
                    $q->where('nama_lengkap', 'ILIKE', "%{$search}%")
                      ->orWhere('username', 'ILIKE', "%{$search}%")
                      ->orWhere('email', 'ILIKE', "%{$search}%");
                });
            }
            $users = $userQuery->limit(30)->get();
            foreach ($users as $u) {
                $tipe = ($u->role && in_array(strtolower($u->role->nama_role), ['guru', 'pendidik'])) ? 'Guru' : 'Tendik';
                $pinjamAktif = Sirkulasi::where('tenant_id', $tenantId)->where('peminjam_id', $u->id)->where('status_sirkulasi', 'Dipinjam')->count();
                $denda = Sirkulasi::where('tenant_id', $tenantId)->where('peminjam_id', $u->id)->where('status_denda', 'Belum Lunas')->sum('denda_keterlambatan');

                $members[] = [
                    'id'                   => $u->id,
                    'no_anggota'           => ($tipe === 'Guru' ? 'GUR-' : 'STF-') . $u->username,
                    'nama_lengkap'         => $u->nama_lengkap,
                    'tipe_anggota'         => $tipe,
                    'identitas_no'         => $u->username,
                    'kelas_jurusan'        => $tipe === 'Guru' ? 'Dewan Guru' : 'Tenaga Kependidikan',
                    'no_telepon'           => $u->no_hp ?: $u->email,
                    'pinjam_aktif'         => $pinjamAktif,
                    'total_denda'          => $denda,
                    'status_bebas_pustaka' => ($pinjamAktif === 0 && $denda <= 0) ? 1 : 0,
                ];
            }
        }

        // 3. Anggota Umum
        if (empty($kategori) || in_array($kategori, ['Umum', 'Alumni', 'Tamu'])) {
            $anggotaQuery = Anggota::where('tenant_id', $tenantId);
            if (!empty($search)) {
                $anggotaQuery->where(function ($q) use ($search) {
                    $q->where('nama_lengkap', 'ILIKE', "%{$search}%")
                      ->orWhere('no_anggota', 'ILIKE', "%{$search}%");
                });
            }
            $anggotas = $anggotaQuery->limit(30)->get();
            foreach ($anggotas as $a) {
                $pinjamAktif = Sirkulasi::where('tenant_id', $tenantId)->where('peminjam_id', $a->id)->where('status_sirkulasi', 'Dipinjam')->count();
                $denda = Sirkulasi::where('tenant_id', $tenantId)->where('peminjam_id', $a->id)->where('status_denda', 'Belum Lunas')->sum('denda_keterlambatan');

                $members[] = [
                    'id'                   => $a->id,
                    'no_anggota'           => $a->no_anggota,
                    'nama_lengkap'         => $a->nama_lengkap,
                    'tipe_anggota'         => $a->tipe_anggota,
                    'identitas_no'         => $a->identitas_no,
                    'kelas_jurusan'        => $a->kelas_jurusan,
                    'no_telepon'           => $a->no_telepon,
                    'pinjam_aktif'         => $pinjamAktif,
                    'total_denda'          => $denda,
                    'status_bebas_pustaka' => ($pinjamAktif === 0 && $denda <= 0) ? 1 : 0,
                ];
            }
        }

        return array_slice($members, 0, $limit);
    }
}
