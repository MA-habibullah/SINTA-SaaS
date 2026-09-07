<?php

namespace Modules\Siswa\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Modules\Siswa\Entities\PpdbPendaftar;
use Modules\Siswa\Entities\Siswa;

class PpdbController extends Controller
{
    public function index(Request $request): InertiaResponse|JsonResponse
    {
        $query = PpdbPendaftar::query()
            ->when($request->search, function ($q, $search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('nama_lengkap', 'ILIKE', "%{$search}%")
                        ->orWhere('no_pendaftaran', 'ILIKE', "%{$search}%")
                        ->orWhere('nisn', 'ILIKE', "%{$search}%");
                });
            })
            ->when($request->status, function ($q, $status) {
                $q->where('status_verifikasi_berkas', $status);
            })
            ->when($request->jalur, function ($q, $jalur) {
                $q->where('jalur_pendaftaran', $jalur);
            })
            ->orderBy('created_at', 'desc');

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'data' => $query->paginate(20)]);
        }

        return Inertia::render('Siswa/Ppdb/Index', [
            'pendaftarList' => $query->paginate(20)->withQueryString(),
            'filters'       => $request->only(['search', 'status', 'jalur']),
        ]);
    }

    public function verifikasi(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $pendaftar = PpdbPendaftar::findOrFail($id);

        $validated = $request->validate([
            'status_verifikasi_berkas' => 'required|string|in:Menunggu,Terverifikasi,Ditolak',
            'catatan_verifikasi'       => 'nullable|string',
            'is_diterima'              => 'nullable|boolean',
        ]);

        $pendaftar->update($validated);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Status verifikasi berkas PPDB diperbarui.', 'data' => $pendaftar]);
        }

        return back()->with('success', 'Status verifikasi pendaftar berhasil diperbarui.');
    }

    /**
     * Konversi calon siswa PPDB yang diterima menjadi siswa aktif di Buku Induk
     */
    public function konversiKeBukuInduk(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $pendaftar = PpdbPendaftar::findOrFail($id);

        if (!$pendaftar->is_diterima) {
            abort(422, 'Pendaftar belum dinyatakan diterima seleksi PPDB.');
        }

        $siswa = Siswa::create([
            'tenant_id'        => $pendaftar->tenant_id,
            'nisn'             => $pendaftar->nisn ?? '-',
            'nis'              => $request->input('nis', 'AUTO-'.rand(1000, 9999)),
            'nik'              => $pendaftar->nik,
            'nama_lengkap'     => $pendaftar->nama_lengkap,
            'jenis_kelamin'    => $pendaftar->jenis_kelamin,
            'tempat_lahir'     => $pendaftar->tempat_lahir,
            'tanggal_lahir'    => $pendaftar->tanggal_lahir,
            'alamat_tinggal'   => $pendaftar->alamat,
            'jurusan'          => $pendaftar->pilihan_jurusan_1,
            'kelas_saat_ini'   => $request->input('kelas', 'X'),
            'status_siswa'     => 'Aktif',
            'tanggal_diterima' => now(),
        ]);

        $pendaftar->update(['status_pendaftaran' => 'Terkonversi']);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Calon siswa berhasil dikonversi ke Buku Induk Siswa.', 'siswa_id' => $siswa->id]);
        }

        return redirect()->route('siswa.buku-induk.show', $siswa->id)->with('success', 'Siswa baru berhasil masuk ke Buku Induk.');
    }
}
