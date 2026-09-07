<?php

namespace Modules\Akademik\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Modules\Akademik\Entities\NilaiRapor;
use Modules\Akademik\Entities\Kelas;
use Modules\Siswa\Entities\Siswa;
use Modules\Core\Entities\SekolahIdentitas;
use Modules\Akademik\Jobs\BulkPrintRaporJob;

class RaporController extends Controller
{
    public function index(Request $request): InertiaResponse|JsonResponse
    {
        $kelasList = Kelas::where('is_active', true)->get(['id', 'nama_kelas']);

        if (request()->wantsJson()) {
            return response()->json(['success' => true, 'kelas' => $kelasList]);
        }

        return Inertia::render('Akademik/Rapor/Index', [
            'kelasList' => $kelasList,
        ]);
    }

    /**
     * Preview HTML Rapor Siswa
     */
    public function previewHtml(string $siswaId, Request $request)
    {
        $semester = $request->input('semester', 'Ganjil');
        $siswa = Siswa::with(['prestasi'])->findOrFail($siswaId);
        $identitas = SekolahIdentitas::where('tenant_id', $siswa->tenant_id)->first();
        $nilaiList = NilaiRapor::with('mataPelajaran')
            ->where('siswa_id', $siswaId)
            ->where('semester', $semester)
            ->get();

        return view('akademik::rapor_cetak', [
            'siswa'     => $siswa,
            'identitas' => $identitas,
            'nilaiList' => $nilaiList,
            'semester'  => $semester,
        ]);
    }

    /**
     * Dispatch Bulk Cetak Rapor ke Worker Queue
     */
    public function bulkQueue(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'kelas_id'        => 'required|uuid|exists:akademik.kelas,id',
            'tahun_ajaran_id' => 'required|uuid|exists:akademik.tahun_ajaran,id',
            'semester'        => 'required|string|in:Ganjil,Genap',
        ]);

        $tenantId = session('tenant_id');
        $userId = auth()->id();

        BulkPrintRaporJob::dispatch(
            $tenantId,
            $validated['kelas_id'],
            $validated['tahun_ajaran_id'],
            $validated['semester'],
            $userId
        );

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Proses cetak massal rapor telah dijadwalkan ke antrean server (Background Worker).'
            ]);
        }

        return back()->with('success', 'Pencetakan rapor sedang diproses di latar belakang.');
    }
}
