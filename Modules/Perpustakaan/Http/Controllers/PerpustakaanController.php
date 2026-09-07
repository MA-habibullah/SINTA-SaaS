<?php

namespace Modules\Perpustakaan\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Modules\Perpustakaan\Entities\Buku;
use Modules\Perpustakaan\Entities\Sirkulasi;
use Modules\Siswa\Entities\Siswa;

class PerpustakaanController extends Controller
{
    public function index(Request $request): InertiaResponse|JsonResponse
    {
        $bukuList = Buku::query()
            ->when($request->search, function ($q, $search) {
                $q->where('nama_perpus_bibliografi', 'ILIKE', "%{$search}%")
                  ->orWhere('kategori', 'ILIKE', "%{$search}%");
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        $sirkulasiAktif = Sirkulasi::query()
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => compact('bukuList', 'sirkulasiAktif'),
            ]);
        }

        return Inertia::render('Perpustakaan/Index', [
            'bukuList'       => $bukuList,
            'sirkulasiAktif' => $sirkulasiAktif,
        ]);
    }

    public function storeBuku(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'kode_buku'             => 'required|string|max:50',
            'isbn'                  => 'nullable|string|max:30',
            'judul_buku'            => 'required|string|max:255',
            'pengarang'             => 'required|string|max:255',
            'penerbit'              => 'nullable|string|max:255',
            'tahun_terbit'          => 'nullable|integer|min:1900|max:2099',
            'nomor_klasifikasi_ddc' => 'nullable|string|max:20',
            'lokasi_rak'            => 'nullable|string|max:50',
            'jumlah_eksemplar'      => 'required|integer|min:1',
            'is_active'             => 'boolean',
        ]);

        $validated['jumlah_tersedia'] = $validated['jumlah_eksemplar'];
        $buku = Buku::create($validated);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Buku berhasil ditambahkan.', 'data' => $buku], 201);
        }

        return back()->with('success', 'Buku baru berhasil didaftarkan ke katalog.');
    }

    public function pinjamBuku(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'buku_id'        => 'required|uuid|exists:perpustakaan.buku,id',
            'peminjam_id'    => 'required|uuid|exists:siswa.siswa,id',
            'tanggal_pinjam' => 'required|date',
            'lama_hari'      => 'required|integer|min:1|max:30',
        ]);

        return DB::transaction(function () use ($validated, $request) {
            $buku = Buku::lockForUpdate()->findOrFail($validated['buku_id']);
            if ($buku->jumlah_tersedia <= 0) {
                abort(422, 'Eksemplar buku ini sedang habis dipinjam.');
            }

            $tglPinjam = Carbon::parse($validated['tanggal_pinjam']);
            $tglHarusKembali = $tglPinjam->copy()->addDays((int)$validated['lama_hari']);

            $sirkulasi = Sirkulasi::create([
                'tenant_id'             => session('tenant_id'),
                'nomor_transaksi'       => 'PINJAM/' . date('Ymd') . '/' . rand(1000, 9999),
                'buku_id'               => $buku->id,
                'peminjam_type'         => 'siswa',
                'peminjam_id'           => $validated['peminjam_id'],
                'tanggal_pinjam'        => $tglPinjam->toDateString(),
                'tanggal_harus_kembali' => $tglHarusKembali->toDateString(),
                'status_sirkulasi'      => 'Dipinjam',
                'denda_keterlambatan'   => 0,
                'status_denda'          => 'Nihil',
                'petugas_peminjaman'    => auth()->user()?->nama_lengkap ?? 'Petugas Perpustakaan',
            ]);

            $buku->decrement('jumlah_tersedia');

            if ($request->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'Peminjaman buku berhasil dicatat.', 'data' => $sirkulasi], 201);
            }

            return back()->with('success', 'Peminjaman buku berhasil diproses.');
        });
    }

    public function kembalikanBuku(Request $request, string $id): RedirectResponse|JsonResponse
    {
        return DB::transaction(function () use ($id, $request) {
            $sirkulasi = Sirkulasi::lockForUpdate()->findOrFail($id);
            if ($sirkulasi->status_sirkulasi === 'Kembali') {
                abort(422, 'Transaksi sirkulasi ini sudah dinyatakan kembali sebelumnya.');
            }

            $tglKembali = Carbon::now();
            $tglDeadline = Carbon::parse($sirkulasi->tanggal_harus_kembali);
            $terlambatHari = max(0, $tglKembali->diffInDays($tglDeadline, false) * -1);

            $tarifDendaPerHari = 1000; // Rp 1.000 / hari
            $totalDenda = $terlambatHari * $tarifDendaPerHari;

            $sirkulasi->update([
                'tanggal_kembali_aktual' => $tglKembali->toDateString(),
                'status_sirkulasi'       => 'Kembali',
                'denda_keterlambatan'    => $totalDenda,
                'status_denda'           => ($totalDenda > 0) ? 'Belum Lunas' : 'Nihil',
                'petugas_pengembalian'   => auth()->user()?->nama_lengkap ?? 'Petugas Perpustakaan',
            ]);

            Buku::where('id', $sirkulasi->buku_id)->increment('jumlah_tersedia');

            if ($request->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'Buku berhasil dikembalikan.', 'denda' => $totalDenda]);
            }

            return back()->with('success', 'Buku berhasil dikembalikan. Total denda: Rp ' . number_format($totalDenda, 0));
        });
    }
}
