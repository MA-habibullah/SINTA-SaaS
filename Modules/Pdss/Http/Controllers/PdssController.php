<?php

namespace Modules\Pdss\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Illuminate\Http\JsonResponse;
use Modules\Pdss\Services\PdssRankingService;
use Modules\Akademik\Entities\Jurusan;

class PdssController extends Controller
{
    public function index(Request $request, PdssRankingService $rankingService): InertiaResponse|JsonResponse
    {
        $jurusanList = Jurusan::where('is_active', true)->get(['id', 'nama_jurusan']);
        $jurusan = $request->input('jurusan', $jurusanList->first()?->nama_jurusan ?? 'MIPA');
        $angkatan = $request->input('angkatan', date('Y'));

        $tenantId = session('tenant_id');
        $rankingData = $rankingService->hitungRankingSnbp($tenantId, $jurusan, $angkatan);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $rankingData,
            ]);
        }

        return Inertia::render('Pdss/Index', [
            'jurusanList' => $jurusanList,
            'rankingData' => $rankingData,
            'filters'     => compact('jurusan', 'angkatan'),
        ]);
    }
}
