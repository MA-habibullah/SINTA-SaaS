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
use Modules\Perpustakaan\Entities\Reservasi;
use Modules\Perpustakaan\Entities\BacaDiTempat;
use Modules\Perpustakaan\Entities\Opname;
use Modules\Perpustakaan\Entities\OpnameItem;
use Modules\Perpustakaan\Entities\KategoriDdc;
use Modules\Perpustakaan\Entities\Loker;
use Modules\Perpustakaan\Entities\LokerLog;
use Modules\Perpustakaan\Entities\Survey;
use Modules\Perpustakaan\Entities\SurveyPertanyaan;
use Modules\Perpustakaan\Entities\SurveyRespon;

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

        if ($user && !empty($user->tenant_id) && $user->tenant_id !== '00000000-0000-0000-0000-000000000000') {
            return $user->tenant_id;
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
                  ->orWhere('anak_judul', 'ILIKE', "%{$search}%")
                  ->orWhere('pengarang', 'ILIKE', "%{$search}%")
                  ->orWhere('pengarang_tambahan', 'ILIKE', "%{$search}%")
                  ->orWhere('penerbit', 'ILIKE', "%{$search}%")
                  ->orWhere('isbn', 'ILIKE', "%{$search}%")
                  ->orWhere('kode_buku', 'ILIKE', "%{$search}%")
                  ->orWhere('nomor_klasifikasi_ddc', 'ILIKE', "%{$search}%");
            });
        }

        if ($request->filled('ddc')) {
            $ddcParam = trim((string) $request->query('ddc'));
            if (preg_match('/^[0-9]00$/', $ddcParam)) {
                $prefix = substr($ddcParam, 0, 1);
                $query->where('nomor_klasifikasi_ddc', 'LIKE', $prefix . '%');
            } elseif (preg_match('/^[0-9][0-9]0$/', $ddcParam)) {
                $prefix = substr($ddcParam, 0, 2);
                $query->where('nomor_klasifikasi_ddc', 'LIKE', $prefix . '%');
            } else {
                $query->where('nomor_klasifikasi_ddc', 'LIKE', $ddcParam . '%');
            }
        }

        if ($request->filled('kategori')) {
            $query->where('kategori', $request->query('kategori'));
        }

        if ($request->filled('jenis_bahan')) {
            $query->where('jenis_bahan', $request->query('jenis_bahan'));
        }

        if ($request->filled('lokasi_rak')) {
            $query->where('lokasi_rak', $request->query('lokasi_rak'));
        }

        $perPageBuku = (int) $request->query('per_page', 10);
        if (!in_array($perPageBuku, [5, 10, 15, 25, 50, 100])) {
            $perPageBuku = 10;
        }
        $bukuList = $query->orderBy('created_at', 'desc')->paginate($perPageBuku)->withQueryString();

        // Data Eksemplar
        $eksemplarQuery = $isSuperAdmin && empty($request->query('tenant_id'))
            ? Eksemplar::withoutTenant()->with('buku')
            : Eksemplar::where('tenant_id', $activeTenantId)->with('buku');

        if ($request->filled('search_eksemplar')) {
            $se = trim($request->query('search_eksemplar'));
            $eksemplarQuery->where(function ($eq) use ($se) {
                $eq->where('barcode', 'ILIKE', "%{$se}%")
                   ->orWhere('no_induk', 'ILIKE', "%{$se}%")
                   ->orWhere('nomor_panggil_item', 'ILIKE', "%{$se}%")
                   ->orWhereHas('buku', function ($bq) use ($se) {
                       $bq->where('judul_buku', 'ILIKE', "%{$se}%");
                   });
            });
        }
        $perPageEks = (int) $request->query('per_page_eksemplar', 15);
        if (!in_array($perPageEks, [5, 10, 15, 20, 25, 50, 100])) {
            $perPageEks = 15;
        }
        $eksemplarList = $eksemplarQuery->orderBy('created_at', 'desc')->paginate($perPageEks)->withQueryString();

        // Master Rak, Usulan, Serial, DDC
        $rakList = LokasiRak::where('tenant_id', $activeTenantId)->orderBy('kode_rak', 'asc')->get();
        $usulanList = UsulanBuku::where('tenant_id', $activeTenantId)->orderBy('created_at', 'desc')->get();
        $serialList = SerialBerkala::where('tenant_id', $activeTenantId)->orderBy('created_at', 'desc')->get();
        $ddcList = KategoriDdc::withoutTenant()->where('tenant_id', $activeTenantId)->orderBy('kode_ddc', 'asc')->get();

        // Inisialisasi Kategori DDC Standar jika kosong
        if ($ddcList->isEmpty()) {
            $defaultDdcs = [
                ['kode' => '000', 'nama' => 'Karya Umum, Komputer & Informasi', 'warna' => '#3b82f6'],
                ['kode' => '100', 'nama' => 'Filsafat & Psikologi', 'warna' => '#8b5cf6'],
                ['kode' => '200', 'nama' => 'Agama & Spiritualitas', 'warna' => '#10b981'],
                ['kode' => '300', 'nama' => 'Ilmu Sosial, Politik & Hukum', 'warna' => '#f59e0b'],
                ['kode' => '400', 'nama' => 'Bahasa & Linguistik', 'warna' => '#ec4899'],
                ['kode' => '500', 'nama' => 'Sains Murni & Matematika', 'warna' => '#06b6d4'],
                ['kode' => '600', 'nama' => 'Teknologi & Ilmu Terapan', 'warna' => '#ef4444'],
                ['kode' => '700', 'nama' => 'Kesenian, Rekreasi & Olahraga', 'warna' => '#84cc16'],
                ['kode' => '800', 'nama' => 'Kesusastraan & Novel', 'warna' => '#14b8a6'],
                ['kode' => '900', 'nama' => 'Sejarah & Geografi', 'warna' => '#6366f1'],
            ];
            foreach ($defaultDdcs as $d) {
                KategoriDdc::updateOrCreate(
                    ['tenant_id' => $activeTenantId, 'kode_ddc' => $d['kode']],
                    [
                        'id'                      => Str::uuid()->toString(),
                        'nama_perpus_kategori_ddc'=> $d['nama'],
                        'nama_klasifikasi'        => $d['nama'],
                        'warna_label'             => $d['warna'],
                        'deskripsi_ddc'           => "Klasifikasi DDC Standar {$d['kode']}",
                        'is_active'               => true,
                    ]
                );
            }
            $ddcList = KategoriDdc::withoutTenant()->where('tenant_id', $activeTenantId)->orderBy('kode_ddc', 'asc')->get();
        }

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
                'data'    => compact('bukuList', 'eksemplarList', 'rakList', 'usulanList', 'serialList', 'ddcList', 'stats'),
            ]);
        }

        return Inertia::render('Perpustakaan/Katalog/Index', [
            'bukuList'       => $bukuList,
            'eksemplarList'  => $eksemplarList,
            'rakList'        => $rakList,
            'usulanList'     => $usulanList,
            'serialList'     => $serialList,
            'ddcList'        => $ddcList,
            'stats'          => $stats,
            'tenants'        => $this->getTenantListForSuperAdmin(),
            'isSuperAdmin'   => $isSuperAdmin,
            'activeTenantId' => $activeTenantId,
            'filters'        => $request->only(['search', 'ddc', 'kategori', 'jenis_bahan', 'lokasi_rak', 'tenant_id', 'per_page', 'page', 'search_eksemplar', 'per_page_eksemplar', 'page_eksemplar']),
        ]);
    }

    public function storeBuku(Request $request): RedirectResponse|JsonResponse
    {
        $tenantId = $this->resolveActiveTenantId($request->input('tenant_id'));

        $validated = $request->validate([
            'judul_buku'            => ['required', 'string', 'max:255'],
            'anak_judul'            => ['nullable', 'string', 'max:255'],
            'pengarang'             => ['required', 'string', 'max:255'],
            'pengarang_tambahan'    => ['nullable', 'string', 'max:255'],
            'penerbit'              => ['nullable', 'string', 'max:255'],
            'kota_terbit'           => ['nullable', 'string', 'max:100'],
            'tahun_terbit'          => ['nullable', 'integer', 'min:1800', 'max:2100'],
            'edisi'                 => ['nullable', 'string', 'max:50'],
            'jenis_bahan'           => ['nullable', 'string', 'max:50'],
            'isbn'                  => ['nullable', 'string', 'max:50'],
            'kode_buku'             => ['nullable', 'string', 'max:50'],
            'nomor_klasifikasi_ddc' => ['nullable', 'string', 'max:50'],
            'nomor_panggil'         => ['nullable', 'string', 'max:50'],
            'deskripsi_fisik'       => ['nullable', 'string', 'max:255'],
            'halaman'               => ['nullable', 'integer'],
            'dimensi'               => ['nullable', 'string', 'max:50'],
            'bahasa'                => ['nullable', 'string', 'max:50'],
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

        // Format Call Number Standar jika tidak diisi: [DDC] [3 Huruf Pengarang] [1 Huruf Judul]
        $ddc = $validated['nomor_klasifikasi_ddc'] ?: '000';
        $authorCode = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $validated['pengarang']), 0, 3));
        $titleCode = strtolower(substr(preg_replace('/[^a-zA-Z]/', '', $validated['judul_buku']), 0, 1));
        $nomorPanggil = $validated['nomor_panggil'] ?: "{$ddc} {$authorCode} {$titleCode}";

        $buku = Buku::create([
            'id'                    => $id,
            'tenant_id'             => $tenantId,
            'nama_perpus_bibliografi' => $validated['judul_buku'],
            'judul_buku'            => $validated['judul_buku'],
            'anak_judul'            => $validated['anak_judul'] ?? null,
            'kode_buku'             => $kodeBuku,
            'isbn'                  => $validated['isbn'] ?? null,
            'pengarang'             => $validated['pengarang'],
            'pengarang_tambahan'    => $validated['pengarang_tambahan'] ?? null,
            'penerbit'              => $validated['penerbit'] ?? null,
            'kota_terbit'           => $validated['kota_terbit'] ?? null,
            'tahun_terbit'          => $validated['tahun_terbit'] ?? (int)date('Y'),
            'edisi'                 => $validated['edisi'] ?? 'Cet. 1',
            'jenis_bahan'           => $validated['jenis_bahan'] ?? 'Buku Teks / Monograf',
            'nomor_klasifikasi_ddc' => $ddc,
            'nomor_panggil'         => $nomorPanggil,
            'deskripsi_fisik'       => $validated['deskripsi_fisik'] ?? null,
            'halaman'               => $validated['halaman'] ?? null,
            'dimensi'               => $validated['dimensi'] ?? null,
            'bahasa'                => $validated['bahasa'] ?? 'Indonesia',
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
            'is_quarantine'         => false,
            'is_active'             => true,
        ]);

        // Auto-generate eksemplar records dengan nomor panggil per item
        for ($i = 1; $i <= (int)$validated['jumlah_eksemplar']; $i++) {
            Eksemplar::create([
                'id'                   => Str::uuid()->toString(),
                'tenant_id'            => $tenantId,
                'bibliografi_id'       => $id,
                'nama_perpus_eksemplar'=> $validated['judul_buku'] . " (Kopi #{$i})",
                'barcode'              => $kodeBuku . '-' . str_pad($i, 3, '0', STR_PAD_LEFT),
                'no_induk'             => 'IND-' . date('Y') . '-' . str_pad($i, 4, '0', STR_PAD_LEFT),
                'nomor_panggil_item'   => "{$nomorPanggil} c.{$i}",
                'lokasi_rak'           => $validated['lokasi_rak'] ?? 'Rak Utama',
                'status_kondisi'       => 'Tersedia',
                'tipe_koleksi'         => 'Sirkulasi',
                'sumber_perolehan'     => 'Pengadaan BOS / Hibah',
                'harga_beli'           => 0,
                'tanggal_perolehan'    => Carbon::today()->toDateString(),
                'is_active'            => true,
            ]);
        }

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Buku berhasil didaftarkan dengan standar MARC/RDA.', 'data' => $buku], 201);
        }

        return back()->with('success', 'Buku berhasil didaftarkan ke katalog perpustakaan.');
    }

    public function updateBuku(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $buku = Buku::withoutTenant()->findOrFail($id);

        $validated = $request->validate([
            'judul_buku'            => ['required', 'string', 'max:255'],
            'anak_judul'            => ['nullable', 'string', 'max:255'],
            'pengarang'             => ['required', 'string', 'max:255'],
            'pengarang_tambahan'    => ['nullable', 'string', 'max:255'],
            'penerbit'              => ['nullable', 'string', 'max:255'],
            'kota_terbit'           => ['nullable', 'string', 'max:100'],
            'tahun_terbit'          => ['nullable', 'integer', 'min:1800', 'max:2100'],
            'edisi'                 => ['nullable', 'string', 'max:50'],
            'jenis_bahan'           => ['nullable', 'string', 'max:50'],
            'isbn'                  => ['nullable', 'string', 'max:50'],
            'kode_buku'             => ['nullable', 'string', 'max:50'],
            'nomor_klasifikasi_ddc' => ['nullable', 'string', 'max:50'],
            'nomor_panggil'         => ['nullable', 'string', 'max:50'],
            'deskripsi_fisik'       => ['nullable', 'string', 'max:255'],
            'halaman'               => ['nullable', 'integer'],
            'dimensi'               => ['nullable', 'string', 'max:50'],
            'bahasa'                => ['nullable', 'string', 'max:50'],
            'subjek'                => ['nullable', 'string', 'max:255'],
            'sinopsis'              => ['nullable', 'string'],
            'kategori'              => ['nullable', 'string', 'max:100'],
            'lokasi_rak'            => ['nullable', 'string', 'max:100'],
            'jumlah_eksemplar'      => ['required', 'integer', 'min:1'],
            'is_ebook'              => ['boolean'],
            'status_opac'           => ['boolean'],
            'is_quarantine'         => ['boolean'],
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
            return response()->json(['success' => true, 'message' => 'Data katalog berhasil diperbarui.', 'data' => $buku]);
        }

        return back()->with('success', 'Data buku berhasil diperbarui.');
    }

    public function destroyBuku(string $id): RedirectResponse|JsonResponse
    {
        $buku = Buku::withoutTenant()->findOrFail($id);
        // Hapus eksemplar terkait
        Eksemplar::where('bibliografi_id', $buku->id)->delete();
        $buku->delete();

        return back()->with('success', 'Buku dan seluruh eksemplarnya berhasil dihapus.');
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
            'bibliografi_id'     => ['required', 'uuid', 'exists:perpustakaan.perpus_bibliografi,id'],
            'barcode'            => ['required', 'string', 'max:100'],
            'no_induk'           => ['nullable', 'string', 'max:100'],
            'nomor_panggil_item' => ['nullable', 'string', 'max:100'],
            'lokasi_rak'         => ['nullable', 'string', 'max:100'],
            'status_kondisi'     => ['required', 'string', 'in:Tersedia,Dipinjam,Rusak,Hilang,Dalam Perbaikan,Dihapuskan (Weeding)'],
            'tipe_koleksi'       => ['required', 'string', 'in:Sirkulasi,Referensi / Tandon,Cadangan / Khusus'],
            'sumber_perolehan'   => ['nullable', 'string', 'max:100'],
            'harga_beli'         => ['nullable', 'numeric', 'min:0'],
            'rfid_tag'           => ['nullable', 'string', 'max:100'],
            'catatan_kondisi'    => ['nullable', 'string'],
        ]);

        $buku = Buku::withoutTenant()->findOrFail($validated['bibliografi_id']);

        $copyNum = Eksemplar::where('bibliografi_id', $buku->id)->count() + 1;
        $callNumber = $validated['nomor_panggil_item'] ?: "{$buku->nomor_panggil} c.{$copyNum}";

        $eksemplar = Eksemplar::create([
            'id'                   => Str::uuid()->toString(),
            'tenant_id'            => $tenantId,
            'bibliografi_id'       => $buku->id,
            'nama_perpus_eksemplar'=> $buku->judul_buku . " (Eksemplar #{$copyNum})",
            'barcode'              => $validated['barcode'],
            'no_induk'             => $validated['no_induk'] ?? 'IND-' . strtoupper(Str::random(6)),
            'nomor_panggil_item'   => $callNumber,
            'lokasi_rak'           => $validated['lokasi_rak'] ?? $buku->lokasi_rak,
            'status_kondisi'       => $validated['status_kondisi'],
            'tipe_koleksi'         => $validated['tipe_koleksi'],
            'sumber_perolehan'     => $validated['sumber_perolehan'] ?? 'Pengadaan Sekolah',
            'harga_beli'           => $validated['harga_beli'] ?? 0,
            'rfid_tag'             => $validated['rfid_tag'] ?? null,
            'tanggal_perolehan'    => Carbon::today()->toDateString(),
            'catatan_kondisi'      => $validated['catatan_kondisi'] ?? null,
            'is_active'            => true,
        ]);

        $buku->increment('jumlah_eksemplar');
        if ($validated['status_kondisi'] === 'Tersedia') {
            $buku->increment('jumlah_tersedia');
        }

        return back()->with('success', 'Eksemplar buku fisik berhasil ditambahkan.');
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

    public function storeDdc(Request $request): RedirectResponse|JsonResponse
    {
        $tenantId = $this->resolveActiveTenantId($request->input('tenant_id'));

        $validated = $request->validate([
            'kode_ddc'         => ['required', 'string', 'max:50'],
            'nama_klasifikasi' => ['required', 'string', 'max:255'],
            'warna_label'      => ['nullable', 'string', 'max:50'],
            'deskripsi_ddc'    => ['nullable', 'string'],
        ]);

        KategoriDdc::create([
            'id'                      => Str::uuid()->toString(),
            'tenant_id'               => $tenantId,
            'nama_perpus_kategori_ddc'=> $validated['nama_klasifikasi'],
            'kode_ddc'                => $validated['kode_ddc'],
            'nama_klasifikasi'        => $validated['nama_klasifikasi'],
            'warna_label'             => $validated['warna_label'] ?? '#3b82f6',
            'deskripsi_ddc'           => $validated['deskripsi_ddc'] ?? '-',
            'is_active'               => true,
        ]);

        return back()->with('success', 'Kategori DDC berhasil ditambahkan.');
    }

    public function destroyDdc(string $id): RedirectResponse|JsonResponse
    {
        KategoriDdc::withoutTenant()->findOrFail($id)->delete();
        return back()->with('success', 'Kategori DDC berhasil dihapus.');
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
        $perPageAktif = (int) $request->query('per_page_aktif', 10);
        if (!in_array($perPageAktif, [5, 10, 15, 25, 50, 100])) $perPageAktif = 10;
        $sirkulasiAktif = $aktifQuery->orderBy('tanggal_harus_kembali', 'asc')->paginate($perPageAktif, ['*'], 'p_aktif')->withQueryString();

        // 2. Riwayat Sirkulasi Selesai
        $riwayatQuery = $isSuperAdmin && empty($request->query('tenant_id'))
            ? Sirkulasi::withoutTenant()->where('status_sirkulasi', '!=', 'Dipinjam')->with(['buku', 'eksemplar', 'tenant:id,nama_sekolah'])
            : Sirkulasi::where('tenant_id', $activeTenantId)->where('status_sirkulasi', '!=', 'Dipinjam')->with(['buku', 'eksemplar', 'tenant:id,nama_sekolah']);
        $perPageRiwayat = (int) $request->query('per_page_riwayat', 10);
        if (!in_array($perPageRiwayat, [5, 10, 15, 25, 50, 100])) $perPageRiwayat = 10;
        $sirkulasiRiwayat = $riwayatQuery->orderBy('tanggal_kembali_aktual', 'desc')->paginate($perPageRiwayat, ['*'], 'p_riwayat')->withQueryString();

        // 3. Buku Paket Pelajaran
        $paketQuery = $isSuperAdmin && empty($request->query('tenant_id'))
            ? PaketBuku::withoutTenant()->with('buku')
            : PaketBuku::where('tenant_id', $activeTenantId)->with('buku');
        $paketList = $paketQuery->orderBy('created_at', 'desc')->get();

        // 4. Kas Denda Keterlambatan, Kerusakan, Kehilangan
        $dendaQuery = $isSuperAdmin && empty($request->query('tenant_id'))
            ? Sirkulasi::withoutTenant()->where(function ($dq) {
                $dq->where('denda_keterlambatan', '>', 0)
                   ->orWhere('denda_kerusakan', '>', 0)
                   ->orWhere('denda_kehilangan', '>', 0);
            })->with(['buku', 'eksemplar'])
            : Sirkulasi::where('tenant_id', $activeTenantId)->where(function ($dq) {
                $dq->where('denda_keterlambatan', '>', 0)
                   ->orWhere('denda_kerusakan', '>', 0)
                   ->orWhere('denda_kehilangan', '>', 0);
            })->with(['buku', 'eksemplar']);
        $perPageDenda = (int) $request->query('per_page_denda', 10);
        if (!in_array($perPageDenda, [5, 10, 15, 25, 50, 100])) $perPageDenda = 10;
        $dendaList = $dendaQuery->orderBy('created_at', 'desc')->paginate($perPageDenda, ['*'], 'p_denda')->withQueryString();

        // 5. Stock Opname Sessions
        $opnameList = Opname::where('tenant_id', $activeTenantId)->with('items')->orderBy('created_at', 'desc')->get();

        // 6. Baca di Tempat (In-House Reading Records)
        $perPageBaca = (int) $request->query('per_page_baca', 10);
        if (!in_array($perPageBaca, [5, 10, 15, 25, 50, 100])) $perPageBaca = 10;
        $bacaList = BacaDiTempat::where('tenant_id', $activeTenantId)->with(['buku', 'eksemplar'])->orderBy('waktu_baca', 'desc')->paginate($perPageBaca, ['*'], 'p_baca')->withQueryString();

        // 7. Reservasi Online
        $reservasiList = Reservasi::where('tenant_id', $activeTenantId)->with(['buku', 'eksemplar'])->orderBy('tanggal_reservasi', 'desc')->get();

        // Daftar Buku Tersedia untuk Selector Peminjaman
        $bukuTersedia = Buku::where('tenant_id', $activeTenantId)
            ->where('jumlah_tersedia', '>', 0)
            ->select('id', 'judul_buku', 'pengarang', 'jumlah_tersedia', 'kode_buku', 'nomor_panggil')
            ->orderBy('judul_buku', 'asc')
            ->get();

        // Daftar Eksemplar Tersedia untuk Scan Barcode / Autocomplete Fisik
        $eksemplarTersedia = Eksemplar::where('tenant_id', $activeTenantId)
            ->where('status_kondisi', 'Tersedia')
            ->with('buku:id,judul_buku,pengarang,nomor_panggil,jumlah_tersedia')
            ->orderBy('barcode', 'asc')
            ->get(['id', 'bibliografi_id', 'barcode', 'no_induk', 'lokasi_rak', 'status_kondisi', 'tipe_koleksi']);

        // 7. Loker Penitipan Barang
        $lokerList = Loker::where('tenant_id', $activeTenantId)
            ->with(['activeLog'])
            ->orderBy('nomor_loker', 'asc')
            ->get();

        // 8. Survey IKM
        $surveyList = Survey::where('tenant_id', $activeTenantId)
            ->with(['pertanyaans', 'respons'])
            ->latest('created_at')
            ->get();

        // Anggota Federasi Lengkap untuk Selector Peminjaman
        $anggotaSelector = Anggota::withoutTenant()
            ->where('tenant_id', $activeTenantId)
            ->where('is_active', true)
            ->orderBy('nama_lengkap', 'asc')
            ->get(['id', 'nama_lengkap', 'no_anggota', 'tipe_anggota', 'identitas_no', 'kelas_jurusan']);

        // Pengaturan Perpus
        $pengaturan = PengaturanPerpus::where('tenant_id', $activeTenantId)->first();

        // Statistik Sirkulasi
        $totalPinjamAktif = (clone $aktifQuery)->count();
        $totalTerlambat = (clone $aktifQuery)->where('tanggal_harus_kembali', '<', Carbon::today()->toDateString())->count();
        $totalDendaBelumLunas = (clone $dendaQuery)->where('status_denda', 'Belum Lunas')->sum(DB::raw('denda_keterlambatan + denda_kerusakan + denda_kehilangan - denda_dibayar'));
        $totalDendaTerkumpul = (clone $dendaQuery)->sum('denda_dibayar');

        $stats = [
            'total_pinjam_aktif'    => $totalPinjamAktif,
            'total_terlambat'       => $totalTerlambat,
            'total_denda_tunggakan' => max(0, $totalDendaBelumLunas),
            'total_denda_lunas'     => $totalDendaTerkumpul,
            'total_baca_hari_ini'   => BacaDiTempat::where('tenant_id', $activeTenantId)->whereDate('waktu_baca', Carbon::today())->count(),
            'total_reservasi_antre' => Reservasi::where('tenant_id', $activeTenantId)->where('status_reservasi', 'Menunggu')->count(),
            'total_loker_terisi'    => Loker::where('tenant_id', $activeTenantId)->where('status', 'terisi')->count(),
            'total_loker_tersedia'  => Loker::where('tenant_id', $activeTenantId)->where('status', 'tersedia')->count(),
        ];

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data'    => compact('sirkulasiAktif', 'sirkulasiRiwayat', 'paketList', 'dendaList', 'opnameList', 'bacaList', 'reservasiList', 'lokerList', 'surveyList', 'stats', 'bukuTersedia', 'eksemplarTersedia', 'anggotaSelector'),
            ]);
        }

        return Inertia::render('Perpustakaan/Sirkulasi/Index', [
            'sirkulasiAktif'    => $sirkulasiAktif,
            'sirkulasiRiwayat'  => $sirkulasiRiwayat,
            'paketList'         => $paketList,
            'dendaList'         => $dendaList,
            'opnameList'        => $opnameList,
            'bacaList'          => $bacaList,
            'reservasiList'     => $reservasiList,
            'lokerList'         => $lokerList,
            'surveyList'        => $surveyList,
            'bukuTersedia'      => $bukuTersedia,
            'eksemplarTersedia' => $eksemplarTersedia,
            'anggotaSelector'   => $anggotaSelector,
            'pengaturan'        => $pengaturan,
            'stats'             => $stats,
            'tenants'           => $this->getTenantListForSuperAdmin(),
            'isSuperAdmin'      => $isSuperAdmin,
            'activeTenantId'    => $activeTenantId,
            'filters'           => $request->only(['search', 'tenant_id', 'per_page_aktif', 'p_aktif', 'per_page_riwayat', 'p_riwayat', 'per_page_denda', 'p_denda', 'per_page_baca', 'p_baca']),
        ]);
    }

    public function pinjamBuku(Request $request): RedirectResponse|JsonResponse
    {
        $tenantId = $this->resolveActiveTenantId($request->input('tenant_id'));

        $validated = $request->validate([
            'buku_id'          => ['required', 'uuid', 'exists:perpustakaan.perpus_bibliografi,id'],
            'eksemplar_id'     => ['nullable', 'uuid'],
            'barcode'          => ['nullable', 'string'],
            'peminjam_type'    => ['required', 'string', 'in:Siswa,Guru,Tendik,Umum,Alumni'],
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

            // Cari eksemplar spesifik atau sirkulasi tersedia
            $eksemplar = null;
            if (!empty($validated['eksemplar_id'])) {
                $eksemplar = Eksemplar::where('id', $validated['eksemplar_id'])->where('status_kondisi', 'Tersedia')->first();
            } elseif (!empty($validated['barcode'])) {
                $eksemplar = Eksemplar::where('tenant_id', $tenantId)->where('barcode', trim($validated['barcode']))->where('status_kondisi', 'Tersedia')->first();
            }
            
            if (!$eksemplar) {
                $eksemplar = Eksemplar::where('bibliografi_id', $buku->id)
                    ->where('status_kondisi', 'Tersedia')
                    ->first();
            }

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
                'denda_kerusakan'       => 0,
                'denda_kehilangan'      => 0,
                'denda_dibayar'         => 0,
                'status_denda'          => 'Nihil',
                'kondisi_kembali'       => 'Baik',
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

            $kondisiKembali = $request->input('kondisi_kembali', 'Baik');
            $dendaKerusakan = (float) $request->input('denda_kerusakan', 0);
            $dendaKehilangan = (float) $request->input('denda_kehilangan', 0);

            $tglKembali = Carbon::now();
            $tglDeadline = Carbon::parse($sirkulasi->tanggal_harus_kembali);

            // Hitung hari terlambat dengan toleransi grace period
            $pengaturan = PengaturanPerpus::where('tenant_id', $sirkulasi->tenant_id)->first();
            $graceDays = $pengaturan ? (int)$pengaturan->toleransi_keterlambatan : 0;

            $diffDays = (int) $tglKembali->diffInDays($tglDeadline, false) * -1;
            $terlambatHari = max(0, $diffDays - $graceDays);

            $tarifDenda = (float)($sirkulasi->tarif_denda_harian ?: 1000);
            $totalDendaKeterlambatan = $terlambatHari * $tarifDenda;
            $grandTotalDenda = $totalDendaKeterlambatan + $dendaKerusakan + $dendaKehilangan;

            $sirkulasi->update([
                'tanggal_kembali_aktual' => $tglKembali->toDateString(),
                'status_sirkulasi'       => ($kondisiKembali === 'Hilang') ? 'Hilang' : 'Kembali',
                'hari_keterlambatan'     => $terlambatHari,
                'denda_keterlambatan'    => $totalDendaKeterlambatan,
                'denda_kerusakan'        => $dendaKerusakan,
                'denda_kehilangan'       => $dendaKehilangan,
                'status_denda'           => ($grandTotalDenda > 0) ? 'Belum Lunas' : 'Nihil',
                'kondisi_kembali'        => $kondisiKembali,
                'petugas_pengembalian'   => Auth::user()?->nama_lengkap ?? 'Petugas Perpustakaan',
            ]);

            $buku = Buku::find($sirkulasi->buku_id);
            if ($buku && $kondisiKembali !== 'Hilang') {
                $buku->increment('jumlah_tersedia');
            }

            if ($sirkulasi->eksemplar_id) {
                $statusEks = ($kondisiKembali === 'Hilang') ? 'Hilang' : (($kondisiKembali === 'Rusak Berat') ? 'Rusak' : 'Tersedia');
                Eksemplar::where('id', $sirkulasi->eksemplar_id)->update(['status_kondisi' => $statusEks]);
            }

            $pesan = $grandTotalDenda > 0 
                ? "Buku berhasil dikembalikan. Total denda (Terlambat: Rp " . number_format($totalDendaKeterlambatan, 0, ',', '.') . " + Ganti Fisik: Rp " . number_format($dendaKerusakan + $dendaKehilangan, 0, ',', '.') . ") sebesar Rp " . number_format($grandTotalDenda, 0, ',', '.')
                : "Buku berhasil diterima kembali dalam kondisi baik & tepat waktu.";

            if ($request->wantsJson()) {
                return response()->json(['success' => true, 'message' => $pesan, 'denda' => $grandTotalDenda]);
            }

            return back()->with('success', $pesan);
        });
    }

    // Quick Return (Pengembalian Kilat berbasis Barcode Scanner tanpa pilih peminjam)
    public function quickReturn(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'barcode' => ['required', 'string'],
        ]);

        $barcode = trim($validated['barcode']);
        $tenantId = $this->resolveActiveTenantId($request->input('tenant_id'));

        $eksemplar = Eksemplar::where('tenant_id', $tenantId)->where('barcode', $barcode)->first();
        if (!$eksemplar) {
            abort(404, "Barcode eksemplar '{$barcode}' tidak ditemukan dalam basis data.");
        }

        $sirkulasi = Sirkulasi::where('tenant_id', $tenantId)
            ->where('eksemplar_id', $eksemplar->id)
            ->where('status_sirkulasi', 'Dipinjam')
            ->first();

        if (!$sirkulasi) {
            abort(422, "Eksemplar dengan barcode '{$barcode}' ({$eksemplar->nama_perpus_eksemplar}) tidak sedang tercatat dipinjam.");
        }

        return $this->kembalikanBuku($request, $sirkulasi->id);
    }

    public function perpanjangBuku(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $sirkulasi = Sirkulasi::findOrFail($id);
        if ($sirkulasi->status_sirkulasi !== 'Dipinjam') {
            abort(422, 'Hanya peminjaman aktif yang dapat diperpanjang.');
        }

        $pengaturan = PengaturanPerpus::where('tenant_id', $sirkulasi->tenant_id)->first();
        $maxPerpanjang = ($sirkulasi->peminjam_type === 'Guru') 
            ? ($pengaturan ? $pengaturan->max_perpanjangan_guru : 2) 
            : ($pengaturan ? $pengaturan->max_perpanjangan_siswa : 1);

        if ($sirkulasi->jumlah_perpanjangan >= $maxPerpanjang) {
            abort(422, "Maksimal perpanjangan untuk {$sirkulasi->peminjam_type} adalah {$maxPerpanjang} kali.");
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
        $totalDenda = $sirkulasi->denda_keterlambatan + $sirkulasi->denda_kerusakan + $sirkulasi->denda_kehilangan;

        $metode = $request->input('metode_pembayaran', 'Tunai');

        $sirkulasi->update([
            'denda_dibayar'          => $totalDenda,
            'status_denda'           => 'Lunas',
            'metode_pembayaran_denda'=> $metode,
            'tanggal_bayar_denda'    => Carbon::now(),
        ]);

        return back()->with('success', "Denda sebesar Rp " . number_format($totalDenda, 0, ',', '.') . " telah lunas via {$metode}.");
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
    // 3. STOCK OPNAME & BACA DI TEMPAT & RESERVASI
    // =========================================================================
    public function storeOpname(Request $request): RedirectResponse|JsonResponse
    {
        $tenantId = $this->resolveActiveTenantId($request->input('tenant_id'));

        $validated = $request->validate([
            'nama_sesi'      => ['required', 'string', 'max:255'],
            'tanggal_mulai'  => ['required', 'date'],
            'keterangan'     => ['nullable', 'string'],
        ]);

        $opname = Opname::create([
            'id'                 => Str::uuid()->toString(),
            'tenant_id'          => $tenantId,
            'nama_perpus_opname' => $validated['nama_sesi'],
            'nama_sesi'          => $validated['nama_sesi'],
            'tanggal_mulai'      => $validated['tanggal_mulai'],
            'keterangan'         => $validated['keterangan'] ?? '-',
            'status_opname'      => 'Berjalan',
            'is_active'          => true,
        ]);

        return back()->with('success', "Sesi Stock Opname '{$validated['nama_sesi']}' berhasil dimulai.");
    }

    public function scanOpnameItem(Request $request): JsonResponse
    {
        $tenantId = $this->resolveActiveTenantId($request->input('tenant_id'));

        $validated = $request->validate([
            'opname_id' => ['required', 'uuid', 'exists:perpustakaan.perpus_opname,id'],
            'barcode'   => ['required', 'string'],
        ]);

        $barcode = trim($validated['barcode']);
        $opname = Opname::findOrFail($validated['opname_id']);

        $eksemplar = Eksemplar::where('tenant_id', $tenantId)->where('barcode', $barcode)->with('buku')->first();

        $statusTemuan = 'Sesuai di Rak';
        $kondisiFisik = 'Baik';

        if (!$eksemplar) {
            $statusTemuan = 'Tidak Terdaftar';
        } else {
            if ($eksemplar->status_kondisi === 'Dipinjam') {
                $statusTemuan = 'Sedang Dipinjam';
            } elseif ($eksemplar->status_kondisi === 'Rusak') {
                $statusTemuan = 'Rusak di Rak';
                $kondisiFisik = 'Rusak';
            }
        }

        $item = OpnameItem::create([
            'id'            => Str::uuid()->toString(),
            'tenant_id'     => $tenantId,
            'opname_id'     => $opname->id,
            'eksemplar_id'  => $eksemplar ? $eksemplar->id : null,
            'barcode'       => $barcode,
            'status_temuan' => $statusTemuan,
            'kondisi_fisik' => $kondisiFisik,
            'scanned_at'    => Carbon::now(),
            'petugas_scan'  => Auth::user()?->nama_lengkap ?? 'Petugas Stock Opname',
            'is_active'     => true,
        ]);

        return response()->json([
            'success'   => true,
            'message'   => "Barcode {$barcode} terverifikasi: {$statusTemuan}",
            'item'      => $item,
            'eksemplar' => $eksemplar,
        ]);
    }

    public function closeOpname(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $opname = Opname::findOrFail($id);
        $opname->update([
            'status_opname'   => 'Selesai',
            'tanggal_selesai' => Carbon::today()->toDateString(),
        ]);

        return back()->with('success', "Sesi Stock Opname '{$opname->nama_sesi}' telah resmi ditutup.");
    }

    public function destroyOpname(string $id): RedirectResponse|JsonResponse
    {
        $opname = Opname::findOrFail($id);
        OpnameItem::where('opname_id', $opname->id)->delete();
        $opname->delete();

        return back()->with('success', 'Sesi Stock Opname berhasil dihapus.');
    }

    public function storeBacaDiTempat(Request $request): RedirectResponse|JsonResponse
    {
        $tenantId = $this->resolveActiveTenantId($request->input('tenant_id'));

        $validated = $request->validate([
            'barcode'       => ['required', 'string'],
            'nama_pembaca'  => ['nullable', 'string', 'max:255'],
            'tipe_pembaca'  => ['nullable', 'string', 'in:Siswa,Guru,Tendik,Tamu,Umum'],
            'ruang_baca'    => ['nullable', 'string', 'max:100'],
        ]);

        $barcode = trim($validated['barcode']);
        $eksemplar = Eksemplar::where('tenant_id', $tenantId)->where('barcode', $barcode)->first();

        $bukuId = $eksemplar ? $eksemplar->bibliografi_id : null;

        $log = BacaDiTempat::create([
            'id'           => Str::uuid()->toString(),
            'tenant_id'    => $tenantId,
            'eksemplar_id' => $eksemplar ? $eksemplar->id : null,
            'buku_id'      => $bukuId,
            'peminjam_id'  => null,
            'nama_pembaca' => $validated['nama_pembaca'] ?: 'Pengunjung Ruang Baca',
            'tipe_pembaca' => $validated['tipe_pembaca'] ?: 'Siswa',
            'ruang_baca'   => $validated['ruang_baca'] ?: 'Ruang Baca Utama',
            'waktu_baca'   => Carbon::now(),
            'is_active'    => true,
        ]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Pemanfaatan buku di ruangan berhasil dicatat.', 'data' => $log]);
        }

        return back()->with('success', 'Pemanfaatan buku di ruangan berhasil dicatat.');
    }

    public function destroyBacaDiTempat(string $id): RedirectResponse|JsonResponse
    {
        BacaDiTempat::withoutTenant()->findOrFail($id)->delete();
        return back()->with('success', 'Catatan baca di tempat berhasil dihapus.');
    }

    public function storeReservasi(Request $request): RedirectResponse|JsonResponse
    {
        $tenantId = $this->resolveActiveTenantId($request->input('tenant_id'));
        $user = Auth::user();

        $validated = $request->validate([
            'buku_id'         => ['required', 'uuid', 'exists:perpustakaan.perpus_bibliografi,id'],
            'nama_peminjam'   => ['nullable', 'string', 'max:255'],
            'nomor_identitas' => ['nullable', 'string', 'max:100'],
        ]);

        $buku = Buku::findOrFail($validated['buku_id']);

        $peminjamType = $user ? ($user->role?->nama_role ?: 'Siswa') : 'Siswa';
        $peminjamId = $user ? $user->id : Str::uuid()->toString();
        $namaPeminjam = $validated['nama_peminjam'] ?: ($user?->nama_lengkap ?? 'Pemustaka');
        $noIdentitas = $validated['nomor_identitas'] ?: ($user?->username ?? '-');

        $tglReservasi = Carbon::today();
        $tglBerakhir = $tglReservasi->copy()->addDays(3);

        $reservasi = Reservasi::create([
            'id'                   => Str::uuid()->toString(),
            'tenant_id'            => $tenantId,
            'nama_perpus_reservasi'=> "Booking: {$buku->judul_buku} oleh {$namaPeminjam}",
            'buku_id'              => $buku->id,
            'peminjam_type'        => $peminjamType,
            'peminjam_id'          => $peminjamId,
            'nama_peminjam'        => $namaPeminjam,
            'nomor_identitas'      => $noIdentitas,
            'tanggal_reservasi'    => $tglReservasi->toDateString(),
            'tanggal_berakhir'     => $tglBerakhir->toDateString(),
            'status_reservasi'     => 'Menunggu',
            'is_active'            => true,
        ]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => "Reservasi buku '{$buku->judul_buku}' berhasil dicatat.", 'data' => $reservasi]);
        }

        return back()->with('success', "Reservasi buku '{$buku->judul_buku}' berhasil dicatat. Anda akan diberi tahu saat buku siap diambil.");
    }

    public function cancelReservasi(string $id): RedirectResponse|JsonResponse
    {
        $res = Reservasi::findOrFail($id);
        $res->update(['status_reservasi' => 'Dibatalkan']);

        return back()->with('success', 'Reservasi buku berhasil dibatalkan.');
    }

    // =========================================================================
    // 4. ANGGOTA, BUKU TAMU & BEBAS PUSTAKA (/perpustakaan/anggota)
    // =========================================================================
    public function anggota(Request $request): InertiaResponse|JsonResponse
    {
        $user = Auth::user();
        $isSuperAdmin = $user ? $user->isSuperAdmin() : false;
        $activeTenantId = $this->resolveActiveTenantId($request->query('tenant_id'));

        $page = (int) $request->query('page', 1);
        $perPageMembers = (int) $request->query('per_page', 15);
        if (!in_array($perPageMembers, [5, 10, 15, 25, 50, 100])) $perPageMembers = 15;

        $search = $request->query('search');
        $kategori = $request->query('kategori');
        $kelas = $request->query('kelas');
        $status = $request->query('status');

        $allMembers = $this->getUnifiedMembersList($activeTenantId, 1000, $search, $kategori, $kelas, $status);
        $totalMembers = count($allMembers);
        $slicedItems = array_slice($allMembers, max(0, ($page - 1) * $perPageMembers), $perPageMembers);
        $unifiedMembers = new \Illuminate\Pagination\LengthAwarePaginator(
            $slicedItems,
            $totalMembers,
            $perPageMembers,
            $page,
            [
                'path'  => $request->url(),
                'query' => $request->query(),
            ]
        );

        // Statistik Anggota Terinci
        $anggotaBaseQuery = Anggota::withoutTenant()->where('tenant_id', $activeTenantId);
        $statsAnggota = [
            'total_anggota'     => (clone $anggotaBaseQuery)->count(),
            'total_siswa_aktif' => (clone $anggotaBaseQuery)->where('tipe_anggota', 'Siswa')->where('is_active', true)->count(),
            'total_guru_aktif'  => (clone $anggotaBaseQuery)->whereIn('tipe_anggota', ['Guru', 'Tendik'])->where('is_active', true)->count(),
            'total_alumni'      => (clone $anggotaBaseQuery)->where(function ($q) {
                $q->where('tipe_anggota', 'Alumni')
                  ->orWhere('kelas_jurusan', 'ILIKE', '%Alumni%')
                  ->orWhere('kelas_jurusan', 'ILIKE', '%Lulus%');
            })->count(),
            'total_non_aktif'   => (clone $anggotaBaseQuery)->where('is_active', false)->count(),
        ];

        // Daftar Rombel / Kelas untuk Filter Dropdown
        $kelasList = Anggota::withoutTenant()
            ->where('tenant_id', $activeTenantId)
            ->whereNotNull('kelas_jurusan')
            ->where('kelas_jurusan', '!=', '')
            ->where('kelas_jurusan', '!=', '-')
            ->distinct()
            ->orderBy('kelas_jurusan', 'asc')
            ->pluck('kelas_jurusan')
            ->toArray();

        // Visitor Log / Buku Tamu
        $tamuQuery = $isSuperAdmin && empty($request->query('tenant_id'))
            ? BukuTamu::withoutTenant()
            : BukuTamu::where('tenant_id', $activeTenantId);
        $perPageTamu = (int) $request->query('per_page_tamu', 15);
        if (!in_array($perPageTamu, [5, 10, 15, 25, 50, 100])) $perPageTamu = 15;
        $bukuTamuList = $tamuQuery->orderBy('tanggal_kunjungan', 'desc')->paginate($perPageTamu, ['*'], 'p_tamu')->withQueryString();

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
                'id'                      => Str::uuid()->toString(),
                'tenant_id'               => $activeTenantId,
                'nama_perpus_pengaturan'  => 'Perpustakaan Digital',
                'nama_perpustakaan'       => 'Perpustakaan Digital SINTA',
                'kepala_perpustakaan'     => 'Pustakawan Utama',
                'nip_kepala'              => '-',
                'tarif_denda_per_hari'    => 1000,
                'max_hari_pinjam_siswa'   => 7,
                'max_hari_pinjam_guru'    => 14,
                'max_buku_pinjam_siswa'   => 3,
                'max_buku_pinjam_guru'    => 10,
                'toleransi_keterlambatan' => 0,
                'max_perpanjangan_siswa'  => 1,
                'max_perpanjangan_guru'   => 2,
                'hitung_libur_denda'      => false,
                'format_nomor_surat_bebas'=> '421.3/{NOMOR}/PERPUS/{TAHUN}',
                'opac_aktif'              => true,
                'is_active'               => true,
            ]);
        }

        // Anggota Selector untuk Autocomplete Modal Presensi / Buku Tamu
        $allMembersSelector = Anggota::withoutTenant()
            ->where('tenant_id', $activeTenantId)
            ->where('is_active', true)
            ->orderBy('nama_lengkap', 'asc')
            ->get(['id', 'nama_lengkap', 'no_anggota', 'tipe_anggota', 'identitas_no', 'kelas_jurusan']);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data'    => compact('unifiedMembers', 'statsAnggota', 'kelasList', 'bukuTamuList', 'statsTamu', 'pengaturan', 'allMembersSelector'),
            ]);
        }

        return Inertia::render('Perpustakaan/Anggota/Index', [
            'members'            => $unifiedMembers,
            'statsAnggota'       => $statsAnggota,
            'kelasList'          => $kelasList,
            'bukuTamuList'       => $bukuTamuList,
            'statsTamu'          => $statsTamu,
            'pengaturan'         => $pengaturan,
            'allMembersSelector' => $allMembersSelector,
            'tenants'            => $this->getTenantListForSuperAdmin(),
            'isSuperAdmin'       => $isSuperAdmin,
            'activeTenantId'     => $activeTenantId,
            'filters'            => $request->only(['search', 'kategori', 'kelas', 'status', 'tenant_id', 'per_page', 'page', 'per_page_tamu', 'p_tamu']),
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

    public function syncAnggotaFromMaster(Request $request): RedirectResponse|JsonResponse
    {
        $targetTenantId = $this->resolveActiveTenantId($request->input('tenant_id'));
        $tenant = Tenant::find($targetTenantId);
        $namaSekolah = $tenant ? $tenant->nama_sekolah : 'Sekolah Terpilih';
        $scope = $request->input('scope', 'all'); // 'all' | 'siswa' | 'guru'

        $createdCount = 0;
        $updatedCount = 0;

        DB::beginTransaction();
        try {
            // 1. Sinkronisasi Data Siswa (siswa.siswa)
            if ($scope === 'all' || $scope === 'siswa') {
                $siswas = Siswa::withoutTenant()->where('tenant_id', $targetTenantId)->get();
                foreach ($siswas as $s) {
                    $identitasNo = !empty($s->nisn) ? trim($s->nisn) : (!empty($s->nis) ? trim($s->nis) : (string) $s->id);
                    $noAnggota = 'SIS-' . (!empty($s->nisn) ? trim($s->nisn) : (!empty($s->nis) ? trim($s->nis) : substr((string) $s->id, 0, 8)));
                    
                    // Deteksi Status Kelulusan / Keaktifan Siswa
                    $statusSiswaRaw = strtolower(trim((string)($s->status_siswa ?? '')));
                    $isLulus = in_array($statusSiswaRaw, ['lulus', 'alumni']);
                    $isKeluar = in_array($statusSiswaRaw, ['keluar', 'pindah', 'drop out', 'do', 'non-aktif', 'nonaktif', 'mutasi']);

                    $tipeAnggota = $isLulus ? 'Alumni' : 'Siswa';
                    $isActive = !$isKeluar;
                    $kelasNama = $isLulus
                        ? ('Alumni' . (!empty($s->tahun_lulus) ? " ({$s->tahun_lulus})" : (!empty($s->kelas_saat_ini) ? " - {$s->kelas_saat_ini}" : '')))
                        : ($s->kelas_saat_ini ?: 'Kelas Reguler');

                    $existing = Anggota::withoutTenant()->where('tenant_id', $targetTenantId)
                        ->where(function ($q) use ($s, $identitasNo, $noAnggota) {
                            $q->where('identitas_no', $identitasNo)
                              ->orWhere('no_anggota', $noAnggota)
                              ->orWhere('id', (string) $s->id);
                        })
                        ->first();

                    if ($existing) {
                        $existing->update([
                            'nama_perpus_anggota' => $s->nama_lengkap,
                            'nama_lengkap'        => $s->nama_lengkap,
                            'tipe_anggota'        => $tipeAnggota,
                            'identitas_no'        => $identitasNo,
                            'kelas_jurusan'       => $kelasNama,
                            'jenis_kelamin'       => in_array(strtoupper($s->jenis_kelamin ?? 'L'), ['L', 'P']) ? strtoupper($s->jenis_kelamin) : 'L',
                            'no_telepon'          => $s->no_hp ?: '-',
                            'alamat'              => $s->alamat ?: '-',
                            'foto_url'            => $s->foto_url ?? $existing->foto_url,
                            'is_active'           => $isActive,
                        ]);
                        $updatedCount++;
                    } else {
                        Anggota::create([
                            'id'                  => (string) $s->id,
                            'tenant_id'           => $targetTenantId,
                            'nama_perpus_anggota' => $s->nama_lengkap,
                            'no_anggota'          => $noAnggota,
                            'nama_lengkap'        => $s->nama_lengkap,
                            'tipe_anggota'        => $tipeAnggota,
                            'identitas_no'        => $identitasNo,
                            'kelas_jurusan'       => $kelasNama,
                            'jenis_kelamin'       => in_array(strtoupper($s->jenis_kelamin ?? 'L'), ['L', 'P']) ? strtoupper($s->jenis_kelamin) : 'L',
                            'no_telepon'          => $s->no_hp ?: '-',
                            'alamat'              => $s->alamat ?: '-',
                            'foto_url'            => $s->foto_url ?? null,
                            'is_active'           => $isActive,
                        ]);
                        $createdCount++;
                    }
                }
            }

            // 2. Sinkronisasi Data Guru & Pegawai (core.users)
            if ($scope === 'all' || $scope === 'guru') {
                $users = User::where('tenant_id', $targetTenantId)->with('role')->get();
                foreach ($users as $u) {
                    $tipe = ($u->role && in_array(strtolower($u->role->nama_role), ['guru', 'pendidik'])) || (!empty($u->jenis_gtk) && stripos($u->jenis_gtk, 'guru') !== false) ? 'Guru' : 'Tendik';
                    $identitasNo = !empty($u->nip) ? trim($u->nip) : (!empty($u->nuptk) ? trim($u->nuptk) : trim($u->username));
                    $noAnggota = ($tipe === 'Guru' ? 'GUR-' : 'STF-') . (!empty($u->nip) ? trim($u->nip) : trim($u->username));
                    $jabatan = $tipe === 'Guru' ? ($u->jabatan_struktural ?: 'Dewan Guru') : ($u->jabatan_struktural ?: 'Tenaga Kependidikan');

                    $existing = Anggota::withoutTenant()->where('tenant_id', $targetTenantId)
                        ->where(function ($q) use ($u, $identitasNo, $noAnggota) {
                            $q->where('identitas_no', $identitasNo)
                              ->orWhere('no_anggota', $noAnggota)
                              ->orWhere('id', (string) $u->id);
                        })
                        ->first();

                    if ($existing) {
                        $existing->update([
                            'nama_perpus_anggota' => $u->nama_lengkap,
                            'nama_lengkap'        => $u->nama_lengkap,
                            'tipe_anggota'        => $tipe,
                            'identitas_no'        => $identitasNo,
                            'kelas_jurusan'       => $jabatan,
                            'jenis_kelamin'       => in_array(strtoupper($u->jenis_kelamin ?? 'L'), ['L', 'P']) ? strtoupper($u->jenis_kelamin) : 'L',
                            'no_telepon'          => $u->no_hp ?: ($u->email ?: '-'),
                            'alamat'              => $u->alamat ?: '-',
                            'is_active'           => true,
                        ]);
                        $updatedCount++;
                    } else {
                        Anggota::create([
                            'id'                  => (string) $u->id,
                            'tenant_id'           => $targetTenantId,
                            'nama_perpus_anggota' => $u->nama_lengkap,
                            'no_anggota'          => $noAnggota,
                            'nama_lengkap'        => $u->nama_lengkap,
                            'tipe_anggota'        => $tipe,
                            'identitas_no'        => $identitasNo,
                            'kelas_jurusan'       => $jabatan,
                            'jenis_kelamin'       => in_array(strtoupper($u->jenis_kelamin ?? 'L'), ['L', 'P']) ? strtoupper($u->jenis_kelamin) : 'L',
                            'no_telepon'          => $u->no_hp ?: ($u->email ?: '-'),
                            'alamat'              => $u->alamat ?: '-',
                            'foto_url'            => null,
                            'is_active'           => true,
                        ]);
                        $createdCount++;
                    }
                }
            }

            DB::commit();

            $totalProcessed = $createdCount + $updatedCount;
            $msg = "Sinkronisasi berhasil: {$totalProcessed} anggota diproses ({$createdCount} data baru ditambahkan, {$updatedCount} data diperbarui, 0 data ganda) untuk {$namaSekolah}.";

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $msg,
                    'created' => $createdCount,
                    'updated' => $updatedCount,
                    'total'   => $totalProcessed,
                ]);
            }

            return back()->with('success', $msg);
        } catch (\Exception $e) {
            DB::rollBack();
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Gagal sinkronisasi data anggota: ' . $e->getMessage()], 500);
            }
            return back()->with('error', 'Gagal sinkronisasi data anggota: ' . $e->getMessage());
        }
    }

    public function updateAnggota(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $anggota = Anggota::withoutTenant()->findOrFail($id);

        $validated = $request->validate([
            'nama_lengkap'  => ['required', 'string', 'max:255'],
            'tipe_anggota'  => ['required', 'string', 'max:50'],
            'identitas_no'  => ['nullable', 'string', 'max:100'],
            'kelas_jurusan' => ['nullable', 'string', 'max:100'],
            'jenis_kelamin' => ['required', 'string', 'in:L,P'],
            'no_telepon'    => ['nullable', 'string', 'max:50'],
            'alamat'        => ['nullable', 'string'],
            'is_active'     => ['nullable', 'boolean'],
        ]);

        $anggota->update([
            'nama_perpus_anggota' => $validated['nama_lengkap'],
            'nama_lengkap'        => $validated['nama_lengkap'],
            'tipe_anggota'        => $validated['tipe_anggota'],
            'identitas_no'        => $validated['identitas_no'] ?? '-',
            'kelas_jurusan'       => $validated['kelas_jurusan'] ?? '-',
            'jenis_kelamin'       => $validated['jenis_kelamin'],
            'no_telepon'          => $validated['no_telepon'] ?? '-',
            'alamat'              => $validated['alamat'] ?? '-',
            'is_active'           => $request->has('is_active') ? (bool) $request->input('is_active') : $anggota->is_active,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Data anggota {$anggota->nama_lengkap} ({$anggota->no_anggota}) berhasil diperbarui.",
                'data'    => $anggota,
            ]);
        }

        return back()->with('success', "Data anggota {$anggota->nama_lengkap} ({$anggota->no_anggota}) berhasil diperbarui.");
    }

    public function destroyAnggota(string $id): RedirectResponse|JsonResponse
    {
        Anggota::withoutTenant()->findOrFail($id)->delete();
        return back()->with('success', 'Data anggota berhasil dihapus.');
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
        $totalDenda = $circRows->where('status_denda', 'Belum Lunas')->sum(DB::raw('denda_keterlambatan + denda_kerusakan + denda_kehilangan - denda_dibayar'));

        $isClear = ($pinjamanAktif->count() === 0 && $totalDenda <= 0);

        $pengaturan = PengaturanPerpus::where('tenant_id', $activeTenantId)->first();
        $nomorSuratFormat = $pengaturan ? $pengaturan->format_nomor_surat_bebas : '421.3/{NOMOR}/PERPUS/{TAHUN}';
        $nomorSurat = str_replace(['{NOMOR}', '{TAHUN}'], [rand(100, 999), date('Y')], $nomorSuratFormat);

        return response()->json([
            'success'                => true,
            'is_clear'               => $isClear,
            'status_label'           => $isClear ? 'BEBAS PUSTAKA (CLEAR)' : 'MASIH MEMILIKI TANGGUNGAN',
            'total_pinjam_aktif'     => $pinjamanAktif->count(),
            'total_denda_tertunggak' => max(0, $totalDenda),
            'pinjaman_aktif'         => $pinjamanAktif,
            'riwayat_pinjam'         => $riwayatPinjam,
            'nomor_surat'            => $nomorSurat,
            'tanggal_terbit'         => Carbon::now()->translatedFormat('d F Y'),
            'nama_perpustakaan'      => $pengaturan ? $pengaturan->nama_perpustakaan : 'Perpustakaan Digital SINTA',
            'kepala_perpustakaan'    => $pengaturan ? $pengaturan->kepala_perpustakaan : 'Pustakawan Utama',
            'nip_kepala'             => $pengaturan ? $pengaturan->nip_kepala : '-',
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
            'nama_perpustakaan'        => ['required', 'string', 'max:255'],
            'kepala_perpustakaan'      => ['nullable', 'string', 'max:255'],
            'nip_kepala'               => ['nullable', 'string', 'max:100'],
            'tarif_denda_per_hari'     => ['required', 'numeric', 'min:0'],
            'max_hari_pinjam_siswa'    => ['required', 'integer', 'min:1'],
            'max_hari_pinjam_guru'     => ['required', 'integer', 'min:1'],
            'max_buku_pinjam_siswa'    => ['required', 'integer', 'min:1'],
            'max_buku_pinjam_guru'     => ['required', 'integer', 'min:1'],
            'toleransi_keterlambatan'  => ['required', 'integer', 'min:0'],
            'max_perpanjangan_siswa'   => ['required', 'integer', 'min:0'],
            'max_perpanjangan_guru'    => ['required', 'integer', 'min:0'],
            'hitung_libur_denda'       => ['boolean'],
            'format_nomor_surat_bebas' => ['nullable', 'string', 'max:100'],
            'opac_aktif'               => ['boolean'],
            'syarat_bebas_pustaka'     => ['nullable', 'string'],
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

        return back()->with('success', 'Pengaturan kebijakan perpustakaan berhasil disimpan.');
    }

    // =========================================================================
    // 5. OPAC PUBLIK DIGITAL (/perpustakaan/opac)
    // =========================================================================
    public function opac(Request $request): InertiaResponse|JsonResponse
    {
        $user = Auth::user();
        $isSuperAdmin = $user ? $user->isSuperAdmin() : false;
        $activeTenantId = $this->resolveActiveTenantId($request->query('tenant_id'));

        $query = $isSuperAdmin && empty($request->query('tenant_id'))
            ? Buku::withoutTenant()->where('status_opac', true)->where('is_quarantine', false)->with(['eksemplar', 'tenant:id,nama_sekolah,npsn'])
            : Buku::where('tenant_id', $activeTenantId)->where('status_opac', true)->where('is_quarantine', false)->with(['eksemplar', 'tenant:id,nama_sekolah,npsn']);

        if ($request->filled('q')) {
            $q = trim($request->query('q'));
            $query->where(function ($sub) use ($q) {
                $sub->where('judul_buku', 'ILIKE', "%{$q}%")
                    ->orWhere('anak_judul', 'ILIKE', "%{$q}%")
                    ->orWhere('pengarang', 'ILIKE', "%{$q}%")
                    ->orWhere('pengarang_tambahan', 'ILIKE', "%{$q}%")
                    ->orWhere('penerbit', 'ILIKE', "%{$q}%")
                    ->orWhere('subjek', 'ILIKE', "%{$q}%")
                    ->orWhere('nomor_klasifikasi_ddc', 'ILIKE', "%{$q}%")
                    ->orWhere('isbn', 'ILIKE', "%{$q}%");
            });
        }

        if ($request->filled('ddc')) {
            $ddcParam = trim((string) $request->query('ddc'));
            if (preg_match('/^[0-9]00$/', $ddcParam)) {
                $prefix = substr($ddcParam, 0, 1);
                $query->where('nomor_klasifikasi_ddc', 'LIKE', $prefix . '%');
            } elseif (preg_match('/^[0-9][0-9]0$/', $ddcParam)) {
                $prefix = substr($ddcParam, 0, 2);
                $query->where('nomor_klasifikasi_ddc', 'LIKE', $prefix . '%');
            } else {
                $query->where('nomor_klasifikasi_ddc', 'LIKE', $ddcParam . '%');
            }
        }

        if ($request->filled('jenis_bahan')) {
            $query->where('jenis_bahan', $request->query('jenis_bahan'));
        }

        if ($request->filled('ebook_only')) {
            $query->where('is_ebook', true);
        }

        $perPage = (int)$request->query('per_page', 12);
        if ($perPage <= 0 || $perPage > 100) {
            $perPage = 12;
        }

        $bukuList = $query->orderBy('judul_buku', 'asc')->paginate($perPage)->withQueryString();

        $pengaturan = PengaturanPerpus::where('tenant_id', $activeTenantId)->first();
        if (!$pengaturan && $isSuperAdmin) {
            $pengaturan = PengaturanPerpus::withoutTenant()->first();
        }

        $ddcList = KategoriDdc::withoutTenant()
            ->where('tenant_id', $activeTenantId)
            ->orderBy('kode_ddc', 'asc')
            ->get()
            ->unique('kode_ddc')
            ->values();

        if ($ddcList->isEmpty()) {
            $ddcList = KategoriDdc::withoutTenant()
                ->orderBy('kode_ddc', 'asc')
                ->get()
                ->unique('kode_ddc')
                ->values();
        }

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'data' => $bukuList]);
        }

        return Inertia::render('Perpustakaan/Opac/Index', [
            'bukuList'       => $bukuList,
            'ddcList'        => $ddcList,
            'pengaturan'     => $pengaturan,
            'tenants'        => $this->getTenantListForSuperAdmin(),
            'isSuperAdmin'   => $isSuperAdmin,
            'activeTenantId' => $activeTenantId,
            'filters'        => $request->only(['q', 'ddc', 'jenis_bahan', 'ebook_only', 'tenant_id', 'per_page', 'page']),
        ]);
    }

    // =========================================================================
    // 6. RIWAYAT SAYA (/perpustakaan/riwayat-saya)
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

        $circQuery = ($user->isSuperAdmin() && empty($tenantId))
            ? Sirkulasi::withoutTenant()
            : Sirkulasi::where('tenant_id', $tenantId);

        $circQuery->where(function ($q) use ($user, $username, $namaLengkap) {
                $q->where('peminjam_id', $user->id)
                  ->orWhere('nomor_identitas', $username)
                  ->orWhere('nama_peminjam', 'ILIKE', "%{$namaLengkap}%");
            })
            ->with(['buku', 'eksemplar']);

        $perPageAktif = (int)$request->query('per_page_aktif', 10);
        if ($perPageAktif <= 0 || $perPageAktif > 100) {
            $perPageAktif = 10;
        }

        $perPageRiwayat = (int)$request->query('per_page_riwayat', 10);
        if ($perPageRiwayat <= 0 || $perPageRiwayat > 100) {
            $perPageRiwayat = 10;
        }

        $pinjamanAktif = (clone $circQuery)->where('status_sirkulasi', 'Dipinjam')->orderBy('tanggal_harus_kembali', 'asc')->paginate($perPageAktif, ['*'], 'p_aktif')->withQueryString();
        $riwayatSelesai = (clone $circQuery)->where('status_sirkulasi', '!=', 'Dipinjam')->orderBy('tanggal_kembali_aktual', 'desc')->paginate($perPageRiwayat, ['*'], 'p_riwayat')->withQueryString();
        $totalDenda = (clone $circQuery)->where('status_denda', 'Belum Lunas')->sum(DB::raw('denda_keterlambatan + denda_kerusakan + denda_kehilangan - denda_dibayar'));

        // Reservasi Aktif User
        $resQuery = ($user->isSuperAdmin() && empty($tenantId))
            ? Reservasi::withoutTenant()
            : Reservasi::where('tenant_id', $tenantId);

        $perPageReservasi = (int)$request->query('per_page_reservasi', 10);
        if ($perPageReservasi <= 0 || $perPageReservasi > 100) {
            $perPageReservasi = 10;
        }

        $reservasiSaya = $resQuery->where(function ($rq) use ($user, $username, $namaLengkap) {
                $rq->where('peminjam_id', $user->id)
                   ->orWhere('nomor_identitas', $username)
                   ->orWhere('nama_peminjam', 'ILIKE', "%{$namaLengkap}%");
            })
            ->with('buku')
            ->orderBy('created_at', 'desc')
            ->paginate($perPageReservasi, ['*'], 'p_reservasi')
            ->withQueryString();

        $pengaturan = PengaturanPerpus::where('tenant_id', $tenantId)->first();
        if (!$pengaturan) {
            $pengaturan = PengaturanPerpus::withoutTenant()->first();
        }

        $isBebasPustaka = ($pinjamanAktif->total() === 0 && $totalDenda <= 0);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data'    => compact('pinjamanAktif', 'riwayatSelesai', 'reservasiSaya', 'totalDenda', 'isBebasPustaka'),
            ]);
        }

        return Inertia::render('Perpustakaan/RiwayatSaya/Index', [
            'pinjamanAktif'  => $pinjamanAktif,
            'riwayatSelesai' => $riwayatSelesai,
            'reservasiSaya'  => $reservasiSaya,
            'totalDenda'     => max(0, $totalDenda),
            'isBebasPustaka' => $isBebasPustaka,
            'pengaturan'     => $pengaturan,
            'userProfile'    => [
                'id'           => $user->id,
                'nama_lengkap' => $namaLengkap,
                'username'     => $username,
                'role'         => $user->role?->nama_role ?? 'Pengguna',
            ],
            'filters'        => $request->only(['per_page_aktif', 'p_aktif', 'per_page_riwayat', 'p_riwayat', 'per_page_reservasi', 'p_reservasi']),
        ]);
    }

    // =========================================================================
    // HELPER AUTO-FEDERASI ANGGOTA (SISWA, GURU, UMUM)
    // =========================================================================
    private function getUnifiedMembersList(string $tenantId, int $limit = 1000, ?string $search = null, ?string $kategori = null, ?string $kelas = null, ?string $status = null): array
    {
        $members = [];

        // 1. Cek apakah tabel perpustakaan.perpus_anggota sudah memiliki data tersinkron untuk tenant ini
        $anggotaQuery = Anggota::withoutTenant()->where('tenant_id', $tenantId);

        // Filter Kategori / Tipe Pemustaka
        if (!empty($kategori)) {
            $anggotaQuery->where('tipe_anggota', $kategori);
        }

        // Filter Rombel / Kelas
        if (!empty($kelas)) {
            $anggotaQuery->where('kelas_jurusan', $kelas);
        }

        // Filter Status Keanggotaan / Kelulusan
        if (!empty($status)) {
            if ($status === 'aktif') {
                $anggotaQuery->where('is_active', true)->where('tipe_anggota', '!=', 'Alumni');
            } elseif ($status === 'non-aktif') {
                $anggotaQuery->where('is_active', false);
            } elseif ($status === 'alumni' || $status === 'lulus') {
                $anggotaQuery->where(function ($q) {
                    $q->where('tipe_anggota', 'Alumni')
                      ->orWhere('kelas_jurusan', 'ILIKE', '%Alumni%')
                      ->orWhere('kelas_jurusan', 'ILIKE', '%Lulus%');
                });
            }
        }

        if (!empty($search)) {
            $anggotaQuery->where(function ($q) use ($search) {
                $q->where('nama_lengkap', 'ILIKE', "%{$search}%")
                  ->orWhere('identitas_no', 'ILIKE', "%{$search}%")
                  ->orWhere('no_anggota', 'ILIKE', "%{$search}%")
                  ->orWhere('kelas_jurusan', 'ILIKE', "%{$search}%");
            });
        }

        $anggotas = $anggotaQuery->orderBy('nama_lengkap', 'asc')->limit($limit)->get();

        if ($anggotas->isNotEmpty()) {
            $peminjamIds = $anggotas->pluck('id')->filter()->toArray();
            $identitasList = $anggotas->pluck('identitas_no')->filter()->toArray();

            $activeBorrows = Sirkulasi::where('tenant_id', $tenantId)
                ->where('status_sirkulasi', 'Dipinjam')
                ->where(function ($q) use ($peminjamIds, $identitasList) {
                    $q->whereIn('peminjam_id', $peminjamIds)
                      ->orWhereIn('nomor_identitas', $identitasList);
                })
                ->select('peminjam_id', 'nomor_identitas', DB::raw('count(*) as total'))
                ->groupBy('peminjam_id', 'nomor_identitas')
                ->get();

            $activeBorrowMap = [];
            foreach ($activeBorrows as $ab) {
                if ($ab->peminjam_id) $activeBorrowMap[$ab->peminjam_id] = ($activeBorrowMap[$ab->peminjam_id] ?? 0) + $ab->total;
                if ($ab->nomor_identitas) $activeBorrowMap[$ab->nomor_identitas] = ($activeBorrowMap[$ab->nomor_identitas] ?? 0) + $ab->total;
            }

            $unpaidFines = Sirkulasi::where('tenant_id', $tenantId)
                ->where('status_denda', 'Belum Lunas')
                ->where(function ($q) use ($peminjamIds, $identitasList) {
                    $q->whereIn('peminjam_id', $peminjamIds)
                      ->orWhereIn('nomor_identitas', $identitasList);
                })
                ->select('peminjam_id', 'nomor_identitas', DB::raw('SUM(denda_keterlambatan + denda_kerusakan + denda_kehilangan - denda_dibayar) as sisa_denda'))
                ->groupBy('peminjam_id', 'nomor_identitas')
                ->get();

            $unpaidFineMap = [];
            foreach ($unpaidFines as $uf) {
                if ($uf->peminjam_id) $unpaidFineMap[$uf->peminjam_id] = ($unpaidFineMap[$uf->peminjam_id] ?? 0) + $uf->sisa_denda;
                if ($uf->nomor_identitas) $unpaidFineMap[$uf->nomor_identitas] = ($unpaidFineMap[$uf->nomor_identitas] ?? 0) + $uf->sisa_denda;
            }

            foreach ($anggotas as $a) {
                $pinjamAktif = $activeBorrowMap[$a->id] ?? ($activeBorrowMap[$a->identitas_no] ?? 0);
                $denda = max(0, (float)($unpaidFineMap[$a->id] ?? ($unpaidFineMap[$a->identitas_no] ?? 0)));

                $members[] = [
                    'id'                   => $a->id,
                    'no_anggota'           => $a->no_anggota ?: 'LIB-' . substr($a->id, 0, 8),
                    'nama_lengkap'         => $a->nama_lengkap,
                    'tipe_anggota'         => $a->tipe_anggota ?: 'Umum',
                    'identitas_no'         => $a->identitas_no ?: '-',
                    'kelas_jurusan'        => $a->kelas_jurusan ?: '-',
                    'jenis_kelamin'        => $a->jenis_kelamin ?: 'L',
                    'no_telepon'           => $a->no_telepon ?: '-',
                    'alamat'               => $a->alamat ?: '-',
                    'foto_url'             => $a->foto_url ?? null,
                    'is_active'            => (bool) $a->is_active,
                    'pinjam_aktif'         => $pinjamAktif,
                    'total_denda'          => $denda,
                    'status_bebas_pustaka' => ($pinjamAktif === 0 && $denda <= 0) ? 1 : 0,
                ];
            }

            return $members;
        }

        // 2. Fallback Live Federation jika belum pernah ditarik/sinkron
        if (empty($kategori) || $kategori === 'Siswa') {
            $siswaQuery = Siswa::where('tenant_id', $tenantId)->select('id', 'nama_lengkap', 'nisn', 'nis', 'kelas_saat_ini', 'no_hp', 'alamat', 'foto_url');
            if (!empty($search)) {
                $siswaQuery->where(function ($q) use ($search) {
                    $q->where('nama_lengkap', 'ILIKE', "%{$search}%")
                      ->orWhere('nisn', 'ILIKE', "%{$search}%")
                      ->orWhere('nis', 'ILIKE', "%{$search}%");
                });
            }
            $siswas = $siswaQuery->limit(100)->get();
            foreach ($siswas as $s) {
                $pinjamAktif = Sirkulasi::where('tenant_id', $tenantId)->where('peminjam_id', $s->id)->where('status_sirkulasi', 'Dipinjam')->count();
                $denda = Sirkulasi::where('tenant_id', $tenantId)->where('peminjam_id', $s->id)->where('status_denda', 'Belum Lunas')->sum(DB::raw('denda_keterlambatan + denda_kerusakan + denda_kehilangan - denda_dibayar'));

                $members[] = [
                    'id'                   => $s->id,
                    'no_anggota'           => 'SIS-' . ($s->nisn ?: ($s->nis ?: substr($s->id, 0, 6))),
                    'nama_lengkap'         => $s->nama_lengkap,
                    'tipe_anggota'         => 'Siswa',
                    'identitas_no'         => $s->nisn ?: ($s->nis ?: '-'),
                    'kelas_jurusan'        => $s->kelas_saat_ini ?: 'Kelas Reguler',
                    'no_telepon'           => $s->no_hp ?: '-',
                    'foto_url'             => $s->foto_url ?? null,
                    'pinjam_aktif'         => $pinjamAktif,
                    'total_denda'          => max(0, $denda),
                    'status_bebas_pustaka' => ($pinjamAktif === 0 && $denda <= 0) ? 1 : 0,
                ];
            }
        }

        if (empty($kategori) || $kategori === 'Guru' || $kategori === 'Tendik') {
            $userQuery = User::where('tenant_id', $tenantId)->with('role');
            if (!empty($search)) {
                $userQuery->where(function ($q) use ($search) {
                    $q->where('nama_lengkap', 'ILIKE', "%{$search}%")
                      ->orWhere('username', 'ILIKE', "%{$search}%")
                      ->orWhere('email', 'ILIKE', "%{$search}%");
                });
            }
            $users = $userQuery->limit(50)->get();
            foreach ($users as $u) {
                $tipe = ($u->role && in_array(strtolower($u->role->nama_role), ['guru', 'pendidik'])) ? 'Guru' : 'Tendik';
                $pinjamAktif = Sirkulasi::where('tenant_id', $tenantId)->where('peminjam_id', $u->id)->where('status_sirkulasi', 'Dipinjam')->count();
                $denda = Sirkulasi::where('tenant_id', $tenantId)->where('peminjam_id', $u->id)->where('status_denda', 'Belum Lunas')->sum(DB::raw('denda_keterlambatan + denda_kerusakan + denda_kehilangan - denda_dibayar'));

                $members[] = [
                    'id'                   => $u->id,
                    'no_anggota'           => ($tipe === 'Guru' ? 'GUR-' : 'STF-') . $u->username,
                    'nama_lengkap'         => $u->nama_lengkap,
                    'tipe_anggota'         => $tipe,
                    'identitas_no'         => $u->username,
                    'kelas_jurusan'        => $tipe === 'Guru' ? 'Dewan Guru' : 'Tenaga Kependidikan',
                    'no_telepon'           => $u->no_hp ?: $u->email,
                    'foto_url'             => null,
                    'pinjam_aktif'         => $pinjamAktif,
                    'total_denda'          => max(0, $denda),
                    'status_bebas_pustaka' => ($pinjamAktif === 0 && $denda <= 0) ? 1 : 0,
                ];
            }
        }

        return array_slice($members, 0, $limit);
    }

    // =========================================================================
    // 6. ANJUNGAN KIOSK PRESENSI MANDIRI (/perpustakaan/kiosk)
    // =========================================================================
    public function kiosk(Request $request): InertiaResponse|JsonResponse
    {
        $tenantId = $this->resolveActiveTenantId($request->query('tenant_id'));
        $tenant = Tenant::find($tenantId);

        $todayVisitors = BukuTamu::where('tenant_id', $tenantId)
            ->whereDate('tanggal_kunjungan', Carbon::today())
            ->latest('created_at')
            ->limit(20)
            ->get();

        $stats = [
            'total_hari_ini'  => BukuTamu::where('tenant_id', $tenantId)->whereDate('tanggal_kunjungan', Carbon::today())->count(),
            'total_siswa'     => BukuTamu::where('tenant_id', $tenantId)->whereDate('tanggal_kunjungan', Carbon::today())->where('tipe_pengunjung', 'Siswa')->count(),
            'total_guru'      => BukuTamu::where('tenant_id', $tenantId)->whereDate('tanggal_kunjungan', Carbon::today())->whereIn('tipe_pengunjung', ['Guru', 'Tendik'])->count(),
            'total_rombongan' => BukuTamu::where('tenant_id', $tenantId)->whereDate('tanggal_kunjungan', Carbon::today())->where('keperluan', 'ILIKE', '%Rombongan%')->count(),
        ];

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data'    => compact('todayVisitors', 'stats', 'tenant'),
            ]);
        }

        return Inertia::render('Perpustakaan/Kiosk/Index', [
            'todayVisitors' => $todayVisitors,
            'stats'         => $stats,
            'tenant'        => $tenant,
            'activeTenantId'=> $tenantId,
        ]);
    }

    public function scanKioskKta(Request $request): JsonResponse
    {
        $tenantId = $this->resolveActiveTenantId($request->input('tenant_id'));
        $barcode = trim($request->input('barcode', ''));

        if (empty($barcode)) {
            return response()->json(['success' => false, 'message' => 'Barcode / NISN tidak boleh kosong.'], 422);
        }

        // Cari di Siswa
        $siswa = Siswa::where('tenant_id', $tenantId)
            ->where(function ($q) use ($barcode) {
                $q->where('nisn', $barcode)
                  ->orWhere('nis', $barcode)
                  ->orWhere('nik', $barcode);
            })
            ->first();

        if ($siswa) {
            $visitor = BukuTamu::create([
                'id'                   => Str::uuid()->toString(),
                'tenant_id'            => $tenantId,
                'nama_perpus_buku_tamu'=> "Kunjungan Siswa: {$siswa->nama_lengkap}",
                'nama_pengunjung'      => $siswa->nama_lengkap,
                'tipe_pengunjung'      => 'Siswa',
                'identitas_no'         => $siswa->nisn ?: $siswa->nis,
                'kelas_instansi'       => 'Siswa SINTA',
                'keperluan'            => 'Membaca / Literasi Mandiri (Kiosk Scan)',
                'tanggal_kunjungan'    => Carbon::today()->toDateString(),
            ]);

            return response()->json([
                'success'      => true,
                'message'      => "Selamat datang, {$siswa->nama_lengkap}!",
                'audio_speech' => "Selamat datang di perpustakaan, {$siswa->nama_lengkap}.",
                'data'         => [
                    'nama'      => $siswa->nama_lengkap,
                    'tipe'      => 'Siswa',
                    'identitas' => $siswa->nisn ?: $siswa->nis,
                    'waktu'     => now()->format('H:i:s'),
                ],
            ]);
        }

        // Cari di Guru / User
        $user = User::where('tenant_id', $tenantId)
            ->where(function ($q) use ($barcode) {
                $q->where('username', $barcode)
                  ->orWhere('email', $barcode);
            })
            ->first();

        if ($user) {
            $visitor = BukuTamu::create([
                'id'                   => Str::uuid()->toString(),
                'tenant_id'            => $tenantId,
                'nama_perpus_buku_tamu'=> "Kunjungan Guru/Staf: {$user->nama_lengkap}",
                'nama_pengunjung'      => $user->nama_lengkap,
                'tipe_pengunjung'      => 'Guru',
                'identitas_no'         => $user->username,
                'kelas_instansi'       => 'Tenaga Pendidik SINTA',
                'keperluan'            => 'Kunjungan Literasi Guru (Kiosk Scan)',
                'tanggal_kunjungan'    => Carbon::today()->toDateString(),
            ]);

            return response()->json([
                'success'      => true,
                'message'      => "Selamat datang Bapak/Ibu, {$user->nama_lengkap}!",
                'audio_speech' => "Selamat datang di perpustakaan, Bapak Ibu {$user->nama_lengkap}.",
                'data'         => [
                    'nama'      => $user->nama_lengkap,
                    'tipe'      => 'Guru',
                    'identitas' => $user->username,
                    'waktu'     => now()->format('H:i:s'),
                ],
            ]);
        }

        // Cari di Anggota Umum
        $anggota = Anggota::where('tenant_id', $tenantId)
            ->where(function ($q) use ($barcode) {
                $q->where('no_anggota', $barcode)
                  ->orWhere('identitas_no', $barcode);
            })
            ->first();

        if ($anggota) {
            $visitor = BukuTamu::create([
                'id'                   => Str::uuid()->toString(),
                'tenant_id'            => $tenantId,
                'nama_perpus_buku_tamu'=> "Kunjungan Pemustaka: {$anggota->nama_lengkap}",
                'nama_pengunjung'      => $anggota->nama_lengkap,
                'tipe_pengunjung'      => $anggota->tipe_anggota,
                'identitas_no'         => $anggota->no_anggota,
                'kelas_instansi'       => $anggota->kelas_jurusan ?: 'Anggota Terdaftar',
                'keperluan'            => 'Kunjungan Perpustakaan (Kiosk Scan)',
                'tanggal_kunjungan'    => Carbon::today()->toDateString(),
            ]);

            return response()->json([
                'success'      => true,
                'message'      => "Selamat datang, {$anggota->nama_lengkap}!",
                'audio_speech' => "Selamat datang di perpustakaan, {$anggota->nama_lengkap}.",
                'data'         => [
                    'nama'      => $anggota->nama_lengkap,
                    'tipe'      => $anggota->tipe_anggota,
                    'identitas' => $anggota->no_anggota,
                    'waktu'     => now()->format('H:i:s'),
                ],
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => "Nomor ID / Barcode '{$barcode}' tidak ditemukan dalam database anggota.",
        ], 404);
    }

    public function storeKioskRombongan(Request $request): RedirectResponse|JsonResponse
    {
        $tenantId = $this->resolveActiveTenantId($request->input('tenant_id'));

        $validated = $request->validate([
            'nama_rombongan'  => ['required', 'string', 'max:255'],
            'jumlah_peserta'  => ['required', 'integer', 'min:1', 'max:500'],
            'ketua_pendamping'=> ['required', 'string', 'max:255'],
            'asal_instansi'   => ['nullable', 'string', 'max:255'],
            'keperluan'       => ['required', 'string', 'max:500'],
        ]);

        $bukuTamu = BukuTamu::create([
            'id'                   => Str::uuid()->toString(),
            'tenant_id'            => $tenantId,
            'nama_perpus_buku_tamu'=> "Rombongan: {$validated['nama_rombongan']} ({$validated['jumlah_peserta']} Orang)",
            'nama_pengunjung'      => "{$validated['nama_rombongan']} (PJ: {$validated['ketua_pendamping']})",
            'tipe_pengunjung'      => 'Rombongan',
            'identitas_no'         => "JML: {$validated['jumlah_peserta']} Orang",
            'kelas_instansi'       => $validated['asal_instansi'] ?? 'Internal Sekolah',
            'keperluan'            => "Rombongan: {$validated['keperluan']}",
            'tanggal_kunjungan'    => Carbon::today()->toDateString(),
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Presensi rombongan berhasil direkam.',
                'data'    => $bukuTamu,
            ], 201);
        }

        return back()->with('success', 'Presensi rombongan berhasil direkam ke buku tamu.');
    }

    // =========================================================================
    // 7. LOKER PENITIPAN BARANG
    // =========================================================================
    public function storeLoker(Request $request): RedirectResponse|JsonResponse
    {
        $tenantId = $this->resolveActiveTenantId($request->input('tenant_id'));

        $validated = $request->validate([
            'nomor_loker'    => ['required', 'string', 'max:50'],
            'lokasi_ruangan' => ['nullable', 'string', 'max:100'],
            'keterangan'     => ['nullable', 'string'],
        ]);

        $loker = Loker::create([
            'id'             => Str::uuid()->toString(),
            'tenant_id'      => $tenantId,
            'nomor_loker'    => $validated['nomor_loker'],
            'lokasi_ruangan' => $validated['lokasi_ruangan'] ?? 'Lobi Utama Perpustakaan',
            'status'         => 'tersedia',
            'keterangan'     => $validated['keterangan'] ?? null,
            'is_active'      => true,
        ]);

        return back()->with('success', "Loker nomor {$validated['nomor_loker']} berhasil ditambahkan.");
    }

    public function updateLoker(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'nomor_loker'    => ['required', 'string', 'max:50'],
            'lokasi_ruangan' => ['nullable', 'string', 'max:100'],
            'status'         => ['required', 'string', 'in:tersedia,terisi,rusak,kunci_hilang'],
            'keterangan'     => ['nullable', 'string'],
        ]);

        $loker = Loker::withoutTenant()->findOrFail($id);
        $loker->update($validated);

        return back()->with('success', 'Data loker berhasil diperbarui.');
    }

    public function destroyLoker(string $id): RedirectResponse|JsonResponse
    {
        $loker = Loker::withoutTenant()->findOrFail($id);
        if ($loker->status === 'terisi') {
            return back()->with('error', 'Loker sedang terisi kunci, tidak dapat dihapus.');
        }
        $loker->delete();
        return back()->with('success', 'Loker berhasil dihapus.');
    }

    public function pinjamLoker(Request $request): RedirectResponse|JsonResponse
    {
        $tenantId = $this->resolveActiveTenantId($request->input('tenant_id'));

        $validated = $request->validate([
            'loker_id'          => ['required', 'uuid', 'exists:perpustakaan.perpus_loker,id'],
            'nama_peminjam'     => ['required', 'string', 'max:255'],
            'identitas_jaminan' => ['required', 'string', 'in:KTA,Kartu Pelajar,KTP,Lainnya'],
            'catatan'           => ['nullable', 'string'],
        ]);

        return DB::transaction(function () use ($validated, $tenantId) {
            $loker = Loker::lockForUpdate()->findOrFail($validated['loker_id']);
            if ($loker->status !== 'tersedia') {
                abort(422, 'Loker tidak dalam status tersedia.');
            }

            $log = LokerLog::create([
                'id'                => Str::uuid()->toString(),
                'tenant_id'         => $tenantId,
                'loker_id'          => $loker->id,
                'nama_peminjam'     => $validated['nama_peminjam'],
                'identitas_jaminan' => $validated['identitas_jaminan'],
                'waktu_pinjam'      => now(),
                'status_pinjam'     => 'dipinjam',
                'denda'             => 0,
                'petugas_id'        => Auth::id(),
                'catatan'           => $validated['catatan'] ?? null,
            ]);

            $loker->status = 'terisi';
            $loker->save();

            return back()->with('success', "Kunci loker {$loker->nomor_loker} berhasil dipinjamkan ke {$validated['nama_peminjam']}.");
        });
    }

    public function kembaliLoker(Request $request, string $id): RedirectResponse|JsonResponse
    {
        return DB::transaction(function () use ($id, $request) {
            $loker = Loker::lockForUpdate()->findOrFail($id);
            $activeLog = LokerLog::where('loker_id', $loker->id)->where('status_pinjam', 'dipinjam')->latest('waktu_pinjam')->first();

            $denda = (float) $request->input('denda', 0);
            $statusPinjam = $request->input('kunci_hilang') ? 'pelanggaran' : 'kembali';

            if ($activeLog) {
                $activeLog->update([
                    'waktu_kembali' => now(),
                    'status_pinjam' => $statusPinjam,
                    'denda'         => $denda,
                    'catatan'       => $request->input('catatan', $activeLog->catatan),
                ]);
            }

            $loker->status = $request->input('kunci_hilang') ? 'kunci_hilang' : 'tersedia';
            $loker->save();

            return back()->with('success', "Kunci loker {$loker->nomor_loker} telah dikembalikan.");
        });
    }

    // =========================================================================
    // 8. SURVEY IKM / KEPUASAN PEMUSTAKA
    // =========================================================================
    public function storeSurvey(Request $request): RedirectResponse|JsonResponse
    {
        $tenantId = $this->resolveActiveTenantId($request->input('tenant_id'));

        $validated = $request->validate([
            'judul_survey'  => ['required', 'string', 'max:255'],
            'deskripsi'     => ['nullable', 'string'],
            'tanggal_buka'  => ['required', 'date'],
            'tanggal_tutup' => ['nullable', 'date', 'after_or_equal:tanggal_buka'],
            'pertanyaan'    => ['required', 'array', 'min:1'],
            'pertanyaan.*'  => ['required', 'string', 'max:500'],
        ]);

        return DB::transaction(function () use ($validated, $tenantId) {
            $survey = Survey::create([
                'id'           => Str::uuid()->toString(),
                'tenant_id'    => $tenantId,
                'judul_survey' => $validated['judul_survey'],
                'deskripsi'    => $validated['deskripsi'] ?? null,
                'tanggal_buka' => $validated['tanggal_buka'],
                'tanggal_tutup'=> $validated['tanggal_tutup'] ?? null,
                'is_active'    => true,
            ]);

            foreach ($validated['pertanyaan'] as $idx => $teksPertanyaan) {
                SurveyPertanyaan::create([
                    'id'             => Str::uuid()->toString(),
                    'tenant_id'      => $tenantId,
                    'survey_id'      => $survey->id,
                    'urutan'         => $idx + 1,
                    'pertanyaan'     => $teksPertanyaan,
                    'tipe_pertanyaan'=> 'skala_likert',
                    'is_active'      => true,
                ]);
            }

            return back()->with('success', 'Survey Indeks Kepuasan Pemustaka berhasil dibuat.');
        });
    }

    public function storeResponSurvey(Request $request): RedirectResponse|JsonResponse
    {
        $tenantId = $this->resolveActiveTenantId($request->input('tenant_id'));

        $validated = $request->validate([
            'survey_id'       => ['required', 'uuid', 'exists:perpustakaan.perpus_survey,id'],
            'nama_responden'  => ['nullable', 'string', 'max:255'],
            'skor'            => ['required', 'array'],
            'skor.*'          => ['required', 'integer', 'min:1', 'max:5'],
            'saran_masukan'   => ['nullable', 'string', 'max:1000'],
        ]);

        return DB::transaction(function () use ($validated, $tenantId) {
            foreach ($validated['skor'] as $pertanyaanId => $nilai) {
                SurveyRespon::create([
                    'id'             => Str::uuid()->toString(),
                    'tenant_id'      => $tenantId,
                    'survey_id'      => $validated['survey_id'],
                    'pertanyaan_id'  => $pertanyaanId,
                    'anggota_id'     => Auth::id(),
                    'nama_responden' => $validated['nama_responden'] ?? (Auth::user()?->nama_lengkap ?: 'Anonim'),
                    'skor_nilai'     => $nilai,
                    'jawaban_teks'   => $validated['saran_masukan'] ?? null,
                    'created_at'     => now(),
                ]);
            }

            return back()->with('success', 'Terima kasih atas partisipasi dan penilaian Anda terhadap layanan perpustakaan!');
        });
    }
}
