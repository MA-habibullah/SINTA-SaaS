<?php

namespace Modules\Siswa\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Modules\Siswa\Entities\Siswa;

class BukuIndukController extends Controller
{
    /**
     * Tampilkan daftar siswa / Buku Induk
     */
    public function index(Request $request): InertiaResponse|JsonResponse
    {
        $query = Siswa::query()
            ->when($request->search, function ($q, $search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('nama_lengkap', 'ILIKE', "%{$search}%")
                        ->orWhere('nisn', 'ILIKE', "%{$search}%")
                        ->orWhere('nis', 'ILIKE', "%{$search}%");
                });
            })
            ->when($request->kelas, function ($q, $kelas) {
                $q->where('kelas_saat_ini', $kelas);
            })
            ->when($request->jurusan, function ($q, $jurusan) {
                $q->where('jurusan', $jurusan);
            })
            ->when($request->angkatan, function ($q, $angkatan) {
                $q->where('angkatan', $angkatan);
            })
            ->when($request->status, function ($q, $status) {
                $q->where('status_siswa', $status);
            })
            ->orderBy('nama_lengkap', 'asc');

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $query->paginate($request->input('per_page', 20)),
            ]);
        }

        return Inertia::render('Siswa/BukuInduk/Index', [
            'siswaList' => $query->paginate(20)->withQueryString(),
            'filters'   => $request->only(['search', 'kelas', 'jurusan', 'angkatan', 'status']),
        ]);
    }

    /**
     * Tampilkan detail lengkap profil siswa 8 Tab
     */
    public function show(string $id): InertiaResponse|JsonResponse
    {
        $siswa = Siswa::with(['mutasi', 'prestasi'])->findOrFail($id);

        if (request()->wantsJson()) {
            return response()->json(['success' => true, 'data' => $siswa]);
        }

        return Inertia::render('Siswa/BukuInduk/Show', [
            'siswa' => $siswa,
        ]);
    }

    /**
     * Simpan data siswa baru
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'nisn'          => 'required|string|max:20',
            'nis'           => 'required|string|max:20',
            'nik'           => 'nullable|string|max:20',
            'nama_lengkap'  => 'required|string|max:255',
            'jenis_kelamin' => 'required|string|in:L,P',
            'tempat_lahir'  => 'nullable|string|max:100',
            'tanggal_lahir' => 'nullable|date',
            'agama'         => 'nullable|string|max:50',
            'kelas_saat_ini'=> 'nullable|string|max:50',
            'jurusan'       => 'nullable|string|max:100',
            'angkatan'      => 'nullable|string|max:20',
            'nama_ayah'     => 'nullable|string|max:255',
            'nama_ibu'      => 'nullable|string|max:255',
            'no_hp'         => 'nullable|string|max:20',
            'alamat_tinggal'=> 'nullable|string',
            'status_siswa'  => 'nullable|string|default:Aktif',
        ]);

        $siswa = Siswa::create($validated);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Siswa berhasil ditambahkan.', 'data' => $siswa], 201);
        }

        return redirect()->route('siswa.buku-induk.show', $siswa->id)->with('success', 'Data siswa berhasil ditambahkan.');
    }

    /**
     * Perbarui data siswa
     */
    public function update(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $siswa = Siswa::findOrFail($id);

        $validated = $request->validate([
            'nama_lengkap'  => 'required|string|max:255',
            'nisn'          => 'required|string|max:20',
            'nis'           => 'required|string|max:20',
            'nik'           => 'nullable|string|max:20',
            'jenis_kelamin' => 'required|string|in:L,P',
            'tempat_lahir'  => 'nullable|string|max:100',
            'tanggal_lahir' => 'nullable|date',
            'agama'         => 'nullable|string|max:50',
            'kelas_saat_ini'=> 'nullable|string|max:50',
            'jurusan'       => 'nullable|string|max:100',
            'angkatan'      => 'nullable|string|max:20',
            'nama_ayah'     => 'nullable|string|max:255',
            'nama_ibu'      => 'nullable|string|max:255',
            'no_hp'         => 'nullable|string|max:20',
            'alamat_tinggal'=> 'nullable|string',
            'status_siswa'  => 'nullable|string',
        ]);

        $siswa->update($validated);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Data siswa berhasil diperbarui.', 'data' => $siswa]);
        }

        return back()->with('success', 'Data siswa berhasil diperbarui.');
    }

    /**
     * Hapus / Arsipkan siswa (Soft Delete)
     */
    public function destroy(string $id): RedirectResponse|JsonResponse
    {
        $siswa = Siswa::findOrFail($id);
        $siswa->delete();

        if (request()->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Data siswa berhasil diarsipkan.']);
        }

        return redirect()->route('siswa.buku-induk.index')->with('success', 'Data siswa berhasil diarsipkan.');
    }
}
