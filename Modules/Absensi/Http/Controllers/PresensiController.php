<?php

namespace Modules\Absensi\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Modules\Absensi\Entities\PresensiHarian;

class PresensiController extends Controller
{
    public function index(Request $request): InertiaResponse|JsonResponse
    {
        $tanggal = $request->input('tanggal', now()->toDateString());

        $presensiList = PresensiHarian::where('tanggal', $tanggal)
            ->orderBy('created_at', 'desc')
            ->paginate(25);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'data' => $presensiList]);
        }

        return Inertia::render('Absensi/Index', [
            'presensiList' => $presensiList,
            'filters'      => compact('tanggal'),
        ]);
    }

    public function tapPresensi(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'subjek_type'      => 'required|in:siswa,gtk',
            'subjek_id'        => 'required|uuid',
            'status_kehadiran' => 'required|in:Hadir,Izin,Sakit,Alpa,Terlambat',
            'metode_presensi'  => 'required|in:QR_Code,Geolokasi_GPS,Manual_Guru',
            'latitude'         => 'nullable|numeric',
            'longitude'        => 'nullable|numeric',
            'keterangan'       => 'nullable|string',
        ]);

        $today = now()->toDateString();
        $presensi = PresensiHarian::updateOrCreate(
            [
                'tenant_id'   => session('tenant_id'),
                'subjek_type' => $validated['subjek_type'],
                'subjek_id'   => $validated['subjek_id'],
                'tanggal'     => $today,
            ],
            [
                'jam_masuk'        => now()->toTimeString(),
                'status_kehadiran' => $validated['status_kehadiran'],
                'metode_presensi'  => $validated['metode_presensi'],
                'latitude'         => $validated['latitude'] ?? null,
                'longitude'        => $validated['longitude'] ?? null,
                'keterangan'       => $validated['keterangan'] ?? null,
            ]
        );

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Presensi berhasil dicatat.', 'data' => $presensi], 201);
        }

        return back()->with('success', 'Presensi berhasil dicatat.');
    }
}
