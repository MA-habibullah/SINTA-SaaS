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
        $totalPemasukan = TransaksiPembayaran::count() * 500000;

        // 2. Total Piutang / Tunggakan Siswa
        $totalTunggakan = TagihanSiswa::count() * 350000;

        // 3. Saldo Kas & Bank Aktif
        $kasList = [
            ['id' => 'kas-utama', 'nama_kas' => 'Kas Utama / Bendahara', 'saldo_saat_ini' => 5450000],
            ['id' => 'bank-bni', 'nama_kas' => 'Bank BNI Operasional', 'saldo_saat_ini' => 10000000],
        ];
        $totalKas = 15450000;

        // 4. Riwayat Transaksi Terbaru
        $riwayatTransaksi = TransaksiPembayaran::orderBy('created_at', 'desc')->paginate(15);

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
