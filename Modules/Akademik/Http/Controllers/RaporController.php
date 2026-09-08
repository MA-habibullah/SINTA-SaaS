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
use Modules\Core\Entities\Tenant;
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
        $siswa = Siswa::withoutTenant()->with(['orangTua'])->find($siswaId);
        if (!$siswa) {
            $siswa = Siswa::with(['orangTua'])->findOrFail($siswaId);
        }

        $identitas = Tenant::find($siswa->tenant_id);
        $nilaiList = NilaiRapor::withoutTenant()->with('mataPelajaran')
            ->where('siswa_id', $siswaId)
            ->where('semester', $semester)
            ->get();

        return view('akademik::identitas_peserta_didik', [
            'siswa'     => $siswa,
            'identitas' => $identitas,
            'tempat'    => $request->input('tempat', 'Jakarta'),
            'tanggal'   => $request->input('tanggal', date('d F Y')),
        ]);
    }

    /**
     * Preview HTML Rapor Siswa by Query Parameter (?id=...)
     */
    public function previewHtmlByQuery(Request $request)
    {
        $siswaId = $request->input('id');
        if (!$siswaId) {
            abort(404, 'Siswa ID wajib disertakan.');
        }
        return $this->previewHtml($siswaId, $request);
    }

    /**
     * Preview HTML Cetak Rapot Kelas / Bulk (?kelas_id=...)
     */
    public function previewHtmlKelas(Request $request)
    {
        $kelasId = $request->input('kelas_id');
        $statusFilter = $request->input('status', 'Aktif');
        $tenantId = $request->input('tenant_id');
        
        $namaKelas = $kelasId;
        if (\Illuminate\Support\Str::isUuid($kelasId)) {
            $kelas = Kelas::withoutTenant()->find($kelasId);
            if ($kelas) {
                $namaKelas = $kelas->nama_kelas;
            }
        }

        $query = Siswa::withoutTenant()
            ->with(['orangTua'])
            ->where(function($q) use ($kelasId, $namaKelas) {
                $q->where('kelas_saat_ini', $namaKelas)->orWhere('kelas_saat_ini', $kelasId);
            });

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        if ($statusFilter && strcasecmp($statusFilter, 'Semua Status') !== 0) {
            if (strcasecmp($statusFilter, 'Aktif') === 0) {
                $query->where('is_active', true);
            } elseif (strcasecmp($statusFilter, 'Lulus') === 0) {
                $query->where('status_siswa', 'ILIKE', 'lulus');
            } elseif (strcasecmp($statusFilter, 'Non-Aktif') === 0) {
                $query->where('is_active', false);
            }
        }

        $siswaList = $query->orderBy('nama_lengkap', 'asc')->get();

        if ($siswaList->isEmpty()) {
            return response("<h3 style='font-family:sans-serif; text-align:center; margin-top:50px;'>Tidak ada data siswa ditemukan untuk kelas {$namaKelas}.</h3>", 200, ['Content-Type' => 'text/html']);
        }

        $identitas = Tenant::find($siswaList->first()->tenant_id);

        return view('akademik::identitas_peserta_didik', [
            'siswa'     => $siswaList->first(),
            'siswaList' => $siswaList,
            'identitas' => $identitas,
            'tempat'    => $request->input('tempat', 'Jakarta'),
            'tanggal'   => $request->input('tanggal', date('d F Y')),
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
