<?php

namespace Modules\Keuangan\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Modules\Keuangan\Entities\TagihanSiswa;
use Modules\Keuangan\Jobs\GenerateMonthlyInvoicesJob;

class TagihanSiswaController extends Controller
{
    public function index(Request $request): InertiaResponse|JsonResponse
    {
        $query = TagihanSiswa::with(['siswa', 'pos'])
            ->when($request->search, function ($q, $search) {
                $q->whereHas('siswa', function ($sub) use ($search) {
                    $sub->where('nama_lengkap', 'ILIKE', "%{$search}%")
                        ->orWhere('nisn', 'ILIKE', "%{$search}%");
                });
            })
            ->when($request->status, function ($q, $status) {
                $q->where('status_pembayaran', $status);
            })
            ->when($request->bulan, function ($q, $bulan) {
                $q->where('bulan', $bulan);
            })
            ->orderBy('created_at', 'desc');

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'data' => $query->paginate(20)]);
        }

        return Inertia::render('Keuangan/Tagihan/Index', [
            'tagihanList' => $query->paginate(20)->withQueryString(),
            'filters'     => $request->only(['search', 'status', 'bulan']),
        ]);
    }

    /**
     * Trigger Batch Pembuatan Invoice Bulanan
     */
    public function triggerMonthlyInvoices(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'bulan' => 'required|integer|min:1|max:12',
            'tahun' => 'required|integer|min:2020|max:2099',
        ]);

        $tenantId = session('tenant_id');

        GenerateMonthlyInvoicesJob::dispatch(
            $tenantId,
            (int)$validated['bulan'],
            (int)$validated['tahun']
        );

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Proses generate tagihan bulanan SPP telah dijadwalkan di antrean sistem.'
            ]);
        }

        return back()->with('success', 'Pembuatan tagihan bulanan sedang diproses di antrean background.');
    }
}
