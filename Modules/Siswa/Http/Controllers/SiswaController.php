<?php

namespace Modules\Siswa\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Siswa\Entities\Siswa;
use Modules\Siswa\Http\Requests\StoreSiswaRequest;
use Modules\Siswa\Http\Requests\UpdateSiswaRequest;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;

class SiswaController extends Controller
{
    public function index(Request $request): Response|JsonResponse
    {
        $query = Siswa::with('kelas')
            ->when($request->search, function ($q, $search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('nama_lengkap', 'ILIKE', "%{$search}%")
                        ->orWhere('nisn', 'ILIKE', "%{$search}%")
                        ->orWhere('nik', 'ILIKE', "%{$search}%");
                });
            })
            ->when($request->kelas_id, function ($q, $kelasId) {
                $q->where('kelas_id', $kelasId);
            })
            ->when($request->status_aktif !== null, function ($q) use ($request) {
                $q->where('status_aktif', filter_var($request->status_aktif, FILTER_VALIDATE_BOOLEAN));
            })
            ->orderBy('nama_lengkap', 'asc');

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data'    => $query->paginate(25),
                'message' => 'Data buku induk siswa berhasil dimuat.',
            ]);
        }

        return Inertia::render('Siswa/BukuInduk', [
            'title'     => 'Buku Induk Siswa Digital',
            'siswaList' => $query->paginate(25)->withQueryString(),
            'filters'   => $request->only(['search', 'kelas_id', 'status_aktif']),
        ]);
    }

    public function store(StoreSiswaRequest $request): RedirectResponse|JsonResponse
    {
        $siswa = Siswa::create($request->validated());

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data'    => $siswa,
                'message' => 'Data siswa berhasil ditambahkan ke Buku Induk.',
            ], 201);
        }

        return redirect()->route('siswa.index')
            ->with('success', 'Data siswa berhasil ditambahkan ke Buku Induk.');
    }

    public function show(string $id): Response|JsonResponse
    {
        $siswa = Siswa::with(['kelas', 'mutasi'])->findOrFail($id);

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'data'    => $siswa,
            ]);
        }

        return Inertia::render('Siswa/Detail', [
            'title' => 'Detail Siswa - ' . $siswa->nama_lengkap,
            'siswa' => $siswa,
        ]);
    }

    public function update(UpdateSiswaRequest $request, string $id): RedirectResponse|JsonResponse
    {
        $siswa = Siswa::findOrFail($id);
        $siswa->update($request->validated());

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data'    => $siswa,
                'message' => 'Data siswa berhasil diperbarui.',
            ]);
        }

        return back()->with('success', 'Data siswa berhasil diperbarui.');
    }

    public function destroy(string $id): RedirectResponse|JsonResponse
    {
        $siswa = Siswa::findOrFail($id);
        $siswa->delete();

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Data siswa berhasil dihapus (Soft Delete).',
            ]);
        }

        return back()->with('success', 'Data siswa berhasil dihapus.');
    }
}
