<?php

namespace Modules\Keuangan\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Modules\Keuangan\Entities\TransaksiPembayaran;
use Modules\Keuangan\Entities\TagihanSiswa;
use Modules\Keuangan\Entities\KasBank;

class LaporanKeuanganController extends Controller
{
    public function index(Request $request): InertiaResponse|JsonResponse
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->endOfMonth()->toDateString());

        // 1. Total Pemasukan Periode Ini
        $totalPemasukan = TransaksiPembayaran::whereBetween('tanggal_bayar', [$startDate, $endDate . ' 23:59:59'])
            ->sum('nominal_bayar');

        // 2. Total Piutang / Tunggakan Siswa
        $totalTunggakan = TagihanSiswa::whereIn('status_pembayaran', ['Belum Bayar', 'Sebagian'])
            ->sum('sisa_tagihan');

        // 3. Saldo Kas & Bank Aktif
        $kasList = KasBank::where('is_active', true)->get();
        $totalKas = $kasList->sum('saldo_saat_ini');

        // 4. Riwayat Transaksi Terbaru
        $riwayatTransaksi = TransaksiPembayaran::with(['siswa', 'tagihan.pos', 'kasir'])
            ->whereBetween('tanggal_bayar', [$startDate, $endDate . ' 23:59:59'])
            ->orderBy('tanggal_bayar', 'desc')
            ->paginate(20);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => compact('totalPemasukan', 'totalTunggakan', 'totalKas', 'kasList', 'riwayatTransaksi'),
            ]);
        }

        return Inertia::render('Keuangan/Laporan/Index', [
            'totalPemasukan'   => $totalPemasukan,
            'totalTunggakan'   => $totalTunggakan,
            'totalKas'         => $totalKas,
            'kasList'          => $kasList,
            'riwayatTransaksi' => $riwayatTransaksi,
            'filters'          => compact('startDate', 'endDate'),
        ]);
    }
}
